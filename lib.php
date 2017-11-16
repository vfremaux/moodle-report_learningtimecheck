<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains functions used by the trainingsessions report
 *
 * @package    report_trainingsessions
 * @category   report
 * @copyright  2012 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/renderer.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/xlib.php');
require_once($CFG->dirroot.'/report/learningtimecheck/multi_curl.php');

// The max number of report build workers. This will depend on your processing capabilities (number of clusters/cores/threads).
define('REPORT_LTC_MAX_WORKERS', 4);
define('REPORT_IDENTIFIER_ID', 0);
define('REPORT_IDENTIFIER_IDNUMBER', 1);
define('REPORT_IDENTIFIER_NAME', 2);

define('DEBUG_LTC_CHECK', 0);
/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node &$navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 * @return void
 */
function report_learningtimecheck_extend_navigation_course(&$navigation, $course, $context) {

    if (has_capability('report/learningtimecheck:view', $context)) {
        $url = new moodle_url('/report/learningtimecheck/index.php', array('id' => $course->id));
        $navstr = get_string('pluginname', 'report_learningtimecheck');
        $navigation->add($navstr, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Gives page types html classes used by this report
 * @param string $pagetype
 * @param object $parentcontext
 * @param object $currentcontext
 * @return array of page types
 * @todo check input params consistance, review code
 */
function report_learningtimecheck_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*' => get_string('page-x', 'pagetype'),
        'report-*' => get_string('page-report-x', 'pagetype'),
        'report-learningtimecheck-*' => get_string('page-report-learningtimecheck-x', 'report_learningtimecheck'),
        'report-learningtimecheck-index' => get_string('page-report-learningtimecheck-index', 'report_learningtimecheck'),
    );
    return $array;
}

/**
 * Is current user allowed to access this report
 *
 * @private defined in lib.php for performance reasons
 *
 * @param stdClass $user
 * @param stdClass $course
 * @return bool
 */
function report_learningtimecheck_can_access_user_report($user, $course) {
    global $USER;

    $coursecontext = context_course::instance($course->id);
    $personalcontext = context_user::instance($user->id);

    if (has_capability('report/learningtimecheck:view', $coursecontext)) {
        return true;
    } else if ($user->id == $USER->id) {
        if ($course->showreports and (is_viewing($coursecontext, $USER) or is_enrolled($coursecontext, $USER))) {
            return true;
        }
    }

    return false;
}

/**
 * Called by the storage subsystem to give back a report.
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param boolean $forcedownload
 */
function report_learningtimecheck_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    require_course_login($course);

    if ($filearea !== 'batchresult') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'report_learningtimecheck', $filearea, 0,
                               $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    $forcedownload = true;

    session_get_instance()->write_close();
    send_stored_file($file, 60*60, 0, $forcedownload);
}

/**
 * get all users in a cohort
 * @param int $cohortid
 * @return an array of users
 */
function report_learningtimecheck_get_cohort_users($cohortid){
    global $DB;

    $sql = "
        SELECT
            u.id,
            u.idnumber,
            u.firstname,
            u.lastname
        FROM
            {user} u,
            {cohort_members} cm
        WHERE
            u.id = cm.userid AND
            cm.cohortid = ?
        ORDER BY
            lastname,
            firstname
    ";

    return $DB->get_records_sql($sql, array('cohortid' => $cohortid));
}

/**
 * compile all results in a table object, that will be rendered
 * on line or dumped onto a report file format
 * @param int $id
 * @param array $cohortmembers
 * @param objectref &$globals an object to be filled by the function with global aggregates
 * @param array $useroptions an array of display options from the current user's session
 * @return a table definition with all data for pdf, csv or html output
 */
function report_learningtimecheck_cohort_results($id, $cohortmembers, &$globals, $useroptions) {

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');

    $idnumberstr = get_string('idnumber');
    $fullnamestr = get_string('fullname');
    $progressstr = get_string('progressbar', 'learningtimecheck');
    $itemstodostr = get_string('itemstodo', 'learningtimecheck');
    $itemsdonestr = get_string('itemsdone', 'learningtimecheck');
    $timetodostr = get_string('timetodo', 'report_learningtimecheck');
    $timedonestr = get_string('timedone', 'learningtimecheck');
    $timeleftstr = get_string('timeleft', 'learningtimecheck');

    // For pdf output.
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $progresspdfstr = get_string('progressbarpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $itemsdonepdfstr = get_string('itemsdonepdf', 'report_learningtimecheck');
    $timetodopdfstr = get_string('timetodopdf', 'report_learningtimecheck');
    $timedonepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();

    // Screen generation.
    $table->head = array($idnumberstr, $fullnamestr, $progressstr, $itemstodostr, $itemsdonestr, $timetodostr, $timedonestr, $timeleftstr);
    $table->size = array('10%', '20%', '20%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', 'highlighted', '', '');
    $table->printinfo = array(true, true, true, true, true, true, true, true);

    // Reports generation.
    $table->xlshead = array('idnumber', 'lastname', 'firstname', 'progress', 'itemstodo', 'itemsdone', 'timetodo', 'timedone', 'doneratio', 'timeleft', 'leftratio');
    $table->xlsprintctl = array('final', 'final', 'final', 'raw', 'final', 'final', 'final', 'final', 'final', 'raw', 'raw');
    $table->xlsprintinfo = array(true, true, true, true, true, true, true, true, true, true, true);

    // Pdf header 1.
    $table->pdfhead1 = array('', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '10%', '20%', '30%', '30%');
    $table->pdfalign1 = array('L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');

    // Pdf header 2.
    $table->pdfhead2 = array($idnumberpdfstr, $fullnamestr, $progresspdfstr, $itemstodopdfstr, $itemsdonepdfstr, $timetodopdfstr, $timedonepdfstr, $timeleftpdfstr);
    $table->pdfsize2 = array('10%', '30%', '10%', '10%', '10%', '10%', '10%', '10%');
    $table->pdfalign2 = array('L', 'L', 'L', 'C', 'C', 'C', 'C', 'L');

    // Pdf footer and summators.
    $table->pdfalign3 = array('L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor3 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');

    $table->pdfprintctl = array('final', 'final', 'raw', 'final', 'final', 'final', 'final', 'final');
    $table->pdfprintinfo = array(true, true, true, true, true, true, true, true);

    $table->rawdata = array();

    $globals = new StdClass();
    $globals->startrange = @$useroptions['startrange'];
    $globals->endrange = @$useroptions['endrange'];

    // Encode user options for tuning the report columns.
    if (!empty($useroptions['hideidnumber'])) {
        $table->printinfo[0] = false;
        $table->colclasses[0] = 'zerowidth';
        $table->pdfprintinfo[0] = false;
        $table->xlsprintinfo[0] = false;
    }

    $countusers = count($cohortmembers);

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;
    $reportsettings->forceshowunmarked = true;

    $table->data = array();

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $i = 0;
    foreach ($cohortmembers as $u) {
        $allchecklists = learningtimecheck_get_my_checks($u->id, 'flat');
        $reportlines = array();

        $data = array();
        $data[0] = $u->idnumber;
        $data[1] = '<a href="'.$thisurl.'?id='.$id.'&view=user&itemid='.$u->id.'">'.fullname($u).'</a>';
        $useraggregate = array('totalitems' => 0, 'totaltime' => 0, 'tickeditems' => 0, 'tickedtimes' => 0);
        foreach ($allchecklists as $checklist) {
            // Care that all checklists should manage a disjonctive set of items.
            $itemstates = $checklist->get_items_for_user($u, $reportsettings, $useroptions);
            $userdata = $itemstates['mandatory'];
            $useraggregate['totalitems'] += $userdata['items'];
            $useraggregate['totaltime'] += $userdata['time'];
            $useraggregate['tickeditems'] += $userdata['ticked'];
            $useraggregate['tickedtimes'] += $userdata['tickedtime'];
        }

        if ($useraggregate['totalitems']) {
            $percentcomplete = ($useraggregate['totalitems']) ? ($useraggregate['tickeditems'] * 100) / $useraggregate['totalitems'] : 0 ;
        } else {
            $percentcomplete = 0;
            $courseaggregate['tickeditems'] = 0;
        }

        $timeleft = $useraggregate['totaltime'] - $useraggregate['tickedtimes'];
        if ($useraggregate['totaltime']) {
            $timedoneratio = floor($useraggregate['tickedtimes'] / $useraggregate['totaltime'] * 100);
            $timeleftratio = 100 - $timedoneratio;
        } else {
            $timedoneratio = 0;
            $timeleftratio = 100;
        }

        $sumcomplete += $percentcomplete;
        $sumitems += $useraggregate['totalitems'];
        $sumticked += $useraggregate['tickeditems'];
        $sumtimetodo += $useraggregate['totaltime'];
        $sumtickedtime += $useraggregate['tickedtimes'];
        $sumtimeleft += $timeleft;

        $data[2] = mod_learningtimecheck_renderer::progressbar_thin($percentcomplete);
        $data[3] = $useraggregate['totalitems'];
        $data[4] = $useraggregate['tickeditems'];
        $data[5] = learningtimecheck_format_time($useraggregate['totaltime']);
        $data[6] = learningtimecheck_format_time($useraggregate['tickedtimes']).' ('.sprintf('%0d', $timedoneratio).'&nbsp;%)';
        $data[7] = learningtimecheck_format_time($timeleft).' ('.sprintf('%0d', $timeleftratio).'&nbsp;%)';

        // Prepare raw data for export.
        $rawdata[0] = $u->idnumber;
        $rawdata[1] = $u->lastname;
        $rawdata[2] = $u->firstname;
        $rawdata[3] = sprintf('%0.2f', $percentcomplete).'&nbsp;%'; // Change for row data.
        $rawdata[4] = $useraggregate['totalitems'];
        $rawdata[5] = $useraggregate['tickeditems'];
        $rawdata[6] = $useraggregate['totaltime']; // Change for row data for further export conversion.
        $rawdata[7] = $useraggregate['tickedtimes']; // Change for row data for further export conversion.
        $rawdata[8] = $timedoneratio.'%'; // Change for row data for further export conversion.
        $rawdata[9] = $timeleft; // Change for row data for further export conversion.
        $rawdata[10] = $timeleftratio.'%'; // Change for row data for further export conversion.
        $table->rawdata[] = $rawdata;

        $table->data[] = $data;

        $pdfdata = $data;
        $pdfdata[1] = fullname($u);
        $pdfdata[2] = sprintf('%0.1f', $percentcomplete).'&nbsp;%';

        $table->pdfdata[] = $pdfdata;
    }

    // Make last row with average and sums.

    $row1 = new html_table_row();

    // ID Number.
    $cell1 = new html_table_cell();
    $cell1->text = '';
    $cell1->align = 'right';
    $row1->cells[] = $cell1;

    // Username.
    $cell2 = new html_table_cell();
    $cell2->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
    $cell2->align = 'right';
    $row1->cells[] = $cell2;

    // Progress indicator.
    $cell3 = new html_table_cell();

    if ($countusers) {
        $cell3->text = sprintf('%0.1f', $sumcomplete / $countusers).'&nbsp;% '.get_string('average', 'learningtimecheck');
    } else {
        $cell3->text = sprintf('%0d', 0).'&nbsp;% '.get_string('average', 'learningtimecheck');
    }
    $row1->cells[] = $cell3;

    // Items to do.
    $cell4 = new html_table_cell();
    $cell4->text = '<span class="totalizer"></span>';
    $row1->cells[] = $cell4;

    // Items done.
    $cell5 = new html_table_cell();
    $cell5->text = '<span class="totalizer"></span>';
    $row1->cells[] = $cell5;

    // Time done.
    $cell6 = new html_table_cell();
    $cell6->text = '<span class="totalizer">'.$sumtickedtime.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $cell6->attributes['class'] = 'learningtimecheck-result';
    $row1->cells[] = $cell6;

    // Ratio time completed.
    $cell7 = new html_table_cell();
    $remains = ($sumitems) ? ($sumitems - $sumticked) / $sumitems * 100 : 0;
    $cell7->text = ($sumitems) ? sprintf('%0d', round($remains)).'&nbsp;%' : 0;
    $cell7->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell7;

    // Yet to do.
    $cell8 = new html_table_cell();
    $cell8->text = learningtimecheck_format_time($sumtimeleft).' '.get_string('totalized', 'learningtimecheck');
    $cell8->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell8;

    $table->data[] = $row1;
    $table->pdfdata[] = $row1;

    return $table;
}

/**
 * Compile all results of a single course, per user, in a table object, that will be rendered
 * on line or dumped onto a report file format
 * @param int $id
 * @param array $courseusers
 * @param int $courseid
 * @param objectref $globals
 * @param array $useroptions
 * @return a table definition with all data for pdf, csv or html output
 */
function report_learningtimecheck_course_results($id, $courseusers, $courseid, &$globals, $useroptions) {
    global $CFG, $DB;

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $idnumberstr = get_string('idnumber', 'report_learningtimecheck');
    $fullnamestr = get_string('fullname');
    $groupsstr = get_string('groups');
    $progressstr = get_string('progressbar', 'learningtimecheck');
    $itemstodostr = get_string('itemstodo', 'learningtimecheck');
    $itemsdonestr = get_string('itemsdone', 'learningtimecheck');
    $timedonestr = get_string('timedone', 'learningtimecheck');
    $timetodostr = get_string('timetodo', 'report_learningtimecheck');
    $timedoneratiostr = get_string('timedoneratio', 'report_learningtimecheck');
    $timeleftstr = get_string('timeleft', 'learningtimecheck');
    $timeleftratiostr = get_string('timeleftratio', 'report_learningtimecheck');

    // For pdf output.
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $progresspdfstr = get_string('progressbarpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $itemsdonepdfstr = get_string('itemsdonepdf', 'report_learningtimecheck');
    $timetodopdfstr = get_string('timetodopdf', 'report_learningtimecheck');
    $timedonepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $timedoneratiopdfstr = get_string('timedoneratiopdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');
    $leftratiopdfstr = get_string('timeleftratiopdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();

    $table->head = array($idnumberstr, $fullnamestr, $groupsstr, $progressstr, $itemstodostr, $itemsdonestr, $timetodostr,
                         $timedonestr, $timeleftstr);
    $table->printinfo = array(true, true, true, true, true, true, true, true, true);
    $table->size = array('10%', '26%', '8%', '8%', '8%', '8%', '8%', '8%', '8%');
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'left', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', '', '', 'highlighted', '');

    // Xls output.
    $table->xlshead = array('shortname', 'lastname', 'firstname', 'groups', 'progress', 'itemstodo', 'doneitems', 'totaltime',
                            'donetime', 'doneratio', 'timeleft', 'leftratio');
    $table->xlsprintinfo = array(true, true, true, true, true, true, true, true, true, true, true, true);

    // The overline.
    $table->pdfhead1 = array('', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '20%', '10%', '30%', '30%');
    $table->pdfalign1 = array('L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');

    $table->pdfhead2 = array($idnumberpdfstr, $fullnamestr, $groupsstr, $progresspdfstr, $itemstodopdfstr, $itemsdonepdfstr, $timetodopdfstr,
                             $timedonepdfstr, $timeleftpdfstr);
    $table->pdfsize2 = array('10%', '20%', '10%', '10%', '10%', '10%', '10%', '10%', '10%');
    $table->pdfalign2 = array('L', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'C');

    $table->pdfbgcolor3 = array('#000000', '#000000', '#000000', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000', '#000000', '#000000');
    $table->pdfalign3 = array('L', 'L', 'L', 'C', 'C', 'C', 'C');

    $table->pdfprintinfo = array(true, true, true, true, true, true, true, true, true);

    $globals = new StdClass;
    $globals->startrange = @$useroptions['startrange'];
    $globals->endrange = @$useroptions['endrange'];

    // Encode user options for tuning the report columns.
    if (!empty($useroptions['hideidnumber'])) {
        $table->printinfo[0] = false;
        $table->colclasses[0] = 'zerowidth';
        $table->size[0] = '';
        $table->xlsprintinfo[0] = false;
        $table->pdfprintinfo[0] = false;
    }

    if (!empty($useroptions['hidegroup'])) {
        $table->printinfo[2] = false;
        $table->colclasses[2] = 'zerowidth';
        $table->size[2] = '';
        $table->xlsprintinfo[3] = false;
        $table->pdfprintinfo[2] = false;
    }

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;
    $reportsettings->forceshowunmarked = true;

    $countusers = count($courseusers);

    $table->data = array();
    $table->rawdata = array();

    // Returns a course arranged array of checks.
    $allchecklists = learningtimecheck_get_checklists(0, $courseid);

    foreach ($courseusers as $u) {

        $globals->allusers = @$globals->allusers + 1;

        $reportlines = array();

        $usergroups = groups_get_user_groups($courseid, $u->id);
        $gnames = array();
        $gids = array();

        if (!empty($usergroups)) {
            foreach ($usergroups as $groupinggroups) {
                foreach ($groupinggroups as $g) { // Unduple groups.
                    if (!in_array($g, $gids)) {
                        $gnames[] = $DB->get_field('groups', 'name', array('id' => $g));
                        $gids[] = $g;
                    }
                }
            }
        }
        $groupnames = implode(',', $gnames);
        unset($gids); /// free unused mem

        $params = array('view' => 'user', 'itemid' => $u->id, 'id' => $courseid);
        $userreporturl = new moodle_url('/report/learningtimecheck/index.php', $params);

        $data = array();
        $data[0] = $u->idnumber;
        $data[1] = '<a href="'.$userreporturl.'">'.fullname($u).'</a>';
        $useraggregate = array('totalitems' => 0, 'totaltime' => 0, 'tickeditems' => 0, 'tickedtimes' => 0);

        if (!empty($allchecklists)) {
            foreach ($allchecklists as $checklist) {
                // Care that all checklists should manage a disjonctive set of items. Do not consider optional items in reports.
                $results = $checklist->get_items_for_user($u, $reportsettings, $useroptions);
                $useraggregate['totalitems'] += $results['mandatory']['items'];
                $useraggregate['totaltime'] += $results['mandatory']['time'];
                $useraggregate['tickeditems'] += $results['mandatory']['ticked'];
                $useraggregate['tickedtimes'] += $results['mandatory']['tickedtime'];
            }
        }

        if ($useraggregate['totalitems']) {
            $percentcomplete = ($useraggregate['totalitems']) ? ($useraggregate['tickeditems'] * 100) / $useraggregate['totalitems'] : 0;
            if ($percentcomplete > 90) {
                $globals->fullusers  = @$globals->fullusers + 1;
            }
            if ($percentcomplete > 50) {
                $globals->halfusers  = @$globals->halfusers + 1;
            }
            if ($percentcomplete > 0) {
                $globals->activeusers  = @$globals->activeusers + 1;
            }
        } else {
            $percentcomplete = 0;
            $courseaggregate['tickeditems'] = 0;
            $globals->nullusers = @$globals->nullusers + 1;
        }

        $timeleft = $useraggregate['totaltime'] - $useraggregate['tickedtimes'];
        $timedoneratio = ($useraggregate['totaltime']) ? floor(100 * (($useraggregate['tickedtimes'] /$useraggregate['totaltime']))) : 0;
        $timeleftratio = 100 - floor($timedoneratio);

        // Summarizes over all users.
        $sumcomplete += $percentcomplete;
        $sumitems += $useraggregate['totalitems'];
        $sumticked += $useraggregate['tickeditems'];
        $sumtimetodo += $useraggregate['totaltime'];
        $sumtickedtime += $useraggregate['tickedtimes'];
        $sumtimeleft += $timeleft;

        $data[2] = $groupnames;
        $data[3] = mod_learningtimecheck_renderer::progressbar_thin($percentcomplete);
        $data[4] = $useraggregate['totalitems'];
        $data[5] = $useraggregate['tickeditems'];
        $data[6] = learningtimecheck_format_time($useraggregate['totaltime']);
        $data[7] = learningtimecheck_format_time($useraggregate['tickedtimes']).' ('.sprintf('%0d', $timedoneratio).'&nbsp;%)';
        $data[8] = learningtimecheck_format_time($timeleft).' ('.sprintf('%0d', $timeleftratio).'&nbsp;%)';
        $table->data[] = $data;

        // Prepare row data for export.
        $rawdata = array();
        $rawdata[0] = $u->idnumber;
        $rawdata[1] = $u->lastname;
        $rawdata[2] = $u->firstname;
        $rawdata[3] = $groupnames;
        $rawdata[4] = sprintf('%0.2f', $percentcomplete).'%'; // Change for row data.
        $rawdata[5] = $useraggregate['totalitems'];
        $rawdata[6] = $useraggregate['tickeditems'];
        $rawdata[7] = $useraggregate['totaltime'];
        $rawdata[8] = $useraggregate['tickedtimes']; // Change for row data for further export conversion.
        $rawdata[9] = $timedoneratio.'%';
        $rawdata[10] = $timeleft; // Change for row data for further export conversion.
        $rawdata[12] = $timeleftratio.'%';
        $table->rawdata[] = $rawdata;

        $pdfdata = $data;
        $pdfdata[1] = fullname($u);
        $pdfdata[3] = sprintf('%0.1f', $percentcomplete).' %';
        $table->pdfdata[] = $pdfdata;
    }

    // Achievement is in fifth col (tickeditems);
    function sortbyachievementdesc($a, $b) {
        if ($a[5] > $b[5]) {
            return -1;
        }
        if ($a[5] < $b[5]) {
            return 1;
        }
        return sortbyname($a,$b);
    }

    // Achievement is in fifth col (tickeditems);
    function sortrawbyachievementdesc($a, $b) {
        if ($a[6] > $b[6]) {
            return -1;
        }
        if ($a[6] < $b[6]) {
            return 1;
        }
        return sortbyname($a,$b);
    }

    // Sort function.
    function sortbyname($a, $b) {
        if (strip_tags($a[1]) > strip_tags($b[1])) {
            return 1;
        }
        if (strip_tags($a[1]) < strip_tags($b[1])) {
            return -1;
        }
        return 0;
    }

    // Sort function.
    function sortrawbyname($a, $b) {
        if ($a[1] > $b[1]) {
            return 1;
        }
        if ($a[1] < $b[1]) {
            return -1;
        }
        if ($a[2] > $b[2]) {
            return -1;
        }
        if ($a[2] < $b[2]) {
            return 1;
        }
        return 0;
    }

    if (@$useroptions->sortby == 'name') {
        usort($table->data, 'sortbyname');
        usort($table->rawdata, 'sortrawbyname');
        usort($table->pdfdata, 'sortbyname');
    } else {
        usort($table->data, 'sortbyachievementdesc');
        usort($table->pdfdata, 'sortbyachievementdesc');
    }

    // Make last row with average and sums.

    $row1 = new html_table_row();

    // ID number.
    $cell1 = new html_table_cell();
    $cell1->text = '';
    $cell1->align = 'right';
    $row1->cells[] = $cell1;

    // Name.
    $cell2 = new html_table_cell();
    $cell2->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
    $cell2->align = 'right';
    $row1->cells[] = $cell2;

    // Groups.
    $cell3 = new html_table_cell();
    $cell3->text = '';
    $cell3->align = 'right';
    $row1->cells[] = $cell3;

    // Progression.
    $cell4 = new html_table_cell();
    if ($countusers) {
        $cell4->text = sprintf('%0.1f', $sumcomplete / $countusers).' % '.get_string('average', 'learningtimecheck');
    } else {
        $cell4->text = sprintf('%0d', 0).' % '.get_string('average', 'learningtimecheck');
    }
    $row1->cells[] = $cell4;

    // Items to do.
    $cell7 = new html_table_cell();
    $cell7->text = '<span class="totalizer"></span>';
    $row1->cells[] = $cell7;

    // Items done.
    $cell8 = new html_table_cell();
    $cell8->text = '<span class="totalizer"></span>';
    $row1->cells[] = $cell8;

    // Total time.
    $cell9 = new html_table_cell();
    $cell9->text = '<span class="totalizer"></span>';
    $row1->cells[] = $cell9;

    // Ticked time.
    $cell10 = new html_table_cell();
    $str = learningtimecheck_format_time($sumtickedtime).' '.get_string('totalized', 'learningtimecheck');
    $str .= ' ('.(($sumtimetodo) ? sprintf('%0.1f', $sumtickedtime / $sumtimetodo * 100) : '0.0' ).'&nbsp;%)';
    $cell10->text = '<span class="totalizer">'.$str.'</span>';
    $cell10->attributes['class'] = 'learningtimecheck-result';
    $row1->cells[] = $cell10;

    $cell12 = new html_table_cell();
    $str = learningtimecheck_format_time($sumtimeleft).' '.get_string('totalized', 'learningtimecheck');
    $str .= ' ('.(($sumtimetodo) ? sprintf('%0.1f', $sumtimeleft / $sumtimetodo* 100) : '0.0' ).'&nbsp;%)';
    $cell12->text = '<span class="totalizer">'.$str.'</span>';
    $cell12->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell12;

    $table->data[] = $row1;
    $table->pdfdata[] = $row1;

    return $table;
}

/**
 * get results for a single user within a single course. Get's detailed information anout each
 * trackable item.
 * @param int $courseid
 * @param object $user
 * @param objectref &$globals an object being filled by the function with some global aggregations
 * @param array $useroptions display options from the user session
 * @return a table definition with all data for pdf, csv or html output
 */
function report_learningtimecheck_user_course_results($courseid, $user, &$globals, $useroptions) {
    global $CFG, $DB, $OUTPUT;
    static $CUSER = array();

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $idnumberstr = get_string('idnumber');
    $itemtimecreditstr = get_string('itemtimecredit', 'report_learningtimecheck');
    $earnedtimestr = get_string('earnedtime', 'report_learningtimecheck');
    $marktimestr = get_string('marktime', 'report_learningtimecheck');
    $isvalidstr = get_string('isvalid', 'report_learningtimecheck');
    $validatedbystr = get_string('validatedby', 'report_learningtimecheck');

    // For pdf output.
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $itemnamestr = get_string('itemnamepdf', 'report_learningtimecheck');
    $itemtimecreditpdfstr = get_string('itemtimecreditpdf', 'report_learningtimecheck');
    $earnedtimepdfstr = get_string('earnedtimepdf', 'report_learningtimecheck');
    $marktimepdfstr = get_string('marktimepdf', 'report_learningtimecheck');
    $isvalidpdfstr = get_string('isvalidpdf', 'report_learningtimecheck');
    $validatedbypdfstr = get_string('validatedbypdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();

    // Screen output.
    $table->head = array($idnumberstr, $itemnamestr, $itemtimecreditstr, $earnedtimestr, $marktimestr, $isvalidstr, $validatedbystr);
    $table->size = array('10%', '40%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center');
    $table->colclasses = array('', '', '', 'highlighted', '', '', '');

    // Xls output.
    $table->xlshead = array('idnumber', 'name', 'credittime', 'earnedtime', 'marktime', 'isvalid', 'markedby');
    $table->xlsprintinfo = array(true, true, true, true, true, true, true);

    // Pdf output.
    $table->pdfhead1 = array('', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '10%', '20%', '30%', '30%');

    $table->pdfalign1 = array('L', 'L', 'L', 'C', 'C', 'L', 'L');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');

    $table->pdfhead2 = array($idnumberpdfstr, $itemnamestr, $itemtimecreditpdfstr, $earnedtimepdfstr, $marktimepdfstr, $isvalidpdfstr, $validatedbypdfstr);
    $table->pdfsize2 = array('10%', '40%', '10%', '10%', '10%', '10%', '10%');
    $table->pdfalign2 = array('L', 'L', 'L', 'L', 'L', 'C', 'L');
    $table->pdfprintinfo = array(true, true, true, true, true, true, true);

    $table->pdfbgcolor3 = array('#000000', '#000000', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#000000', '#000000', '#000000', '#000000');
    $table->pdfalign3 = array('L', 'L', 'L', 'L', 'C', 'L');

    $globals = new StdClass();
    $globals->startrange = @$useroptions['startrange'];
    $globals->endrange = @$useroptions['endrange'];

    if (!empty($useroptions['hideidnumber'])) {
        $table->printinfo[0] = false;
        $table->pdfprintinfo[0] = false;
        $table->xlsprintinfo[0] = false;
    }

    // The raw data for exports.
    $table->rawdata = array();
    $table->pdfdata = array();

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;

    $globals->courseearneditems = 0;
    $globals->courseearnedtime = 0;
    $globals->totalcourseitems = 0;
    $globals->totalcoursetime = 0;
    $globals->totalvaliditems = 0;

    $tclmodule = $DB->get_record('modules', array('name' => 'learningtimecheck'));
    $params = array('course' => $courseid, 'module' => $tclmodule->id);
    $timechecklistmodules = $DB->get_records('course_modules', $params, 'section', '*');
    if ($timechecklistmodules) {
        foreach ($timechecklistmodules as $clcm) {
            $clobj = new learningtimecheck_class($clcm->id);
            if (count($timechecklistmodules) > 1) {
                // Learning time check list name.
                $row1 = new html_table_row();

                $cell1 = new html_table_cell();
                $cell1->text = '<b>'.$clobj->learningtimecheck->name.'</b>';
                $cell1->colspan = 6;
                $cell1->align = 'left';
                $row1->cells[] = $cell1;
                $table->data[] = $row1;
                $table->pdfdata[] = $row1;
            }

            if ($checks = $clobj->get_checks($user->id)) {

                foreach ($checks as $ck => $check) {

                    if (!report_learningtimecheck_meet_report_conditions($check, $reportsettings, $useroptions, $user, $idnumber)) {
                        continue;
                    }

                    // TODO : might we use $mods to be a bit faster ? 
                    $data = array();
                    $data[0] = $idnumber;
                    $data[1] = $check->displaytext;
                    $data[2] = 0 + $check->credittime.' min';

                    // Earned time and check status.
                    $marked = report_learningtimecheck_is_marked($check, $clobj);
                    if ($marked > 1) {
                        // Has been marked either by student or teacher.
                        $pix = '<img src="'.$OUTPUT->pix_url('good').'">';
                        $earnedtime = '<span class="good">'.$check->credittime.'</span>';
                        $globals->courseearneditems++;
                        $globals->courseearnedtime += $check->credittime;
                    } else if ($marked == -1) {
                        $pix = '<img src="'.$OUTPUT->pix_url('bad').'">';
                        $earnedtime = '';
                    } else {
                        $pix = '';
                        $earnedtime = '';
                    }
                    $data[3] = $earnedtime.' '.$pix;
                    $globals->totalcourseitems++;
                    $globals->totalcoursetime += $check->credittime;

                    $marker = '';
                    $marktime = '';
                    $isvalid = '';
                    if ($check->teachermark) {
                        // Has been marked by a teacher or a student able to self validate.
                        if (!array_key_exists($check->teacherid, $CUSER)) {
                            $CUSER[$check->teacherid] = $DB->get_record('user', array('id' => $check->teacherid));
                        }
                        $teacher = $CUSER[$check->teacherid];
                        $data[4] = $marktime = report_learningtimecheck_get_marktime($check, $clobj, true);
                        $data[5] = $isvalid = report_learningtimecheck_is_valid($check);
                        $data[6] = $marker = fullname($teacher);
                    } else if ($check->usertimestamp) {
                        $data[4] = $marktime = report_learningtimecheck_get_marktime($check, $clobj, true);
                        $data[5] = $isvalid = report_learningtimecheck_is_valid($check);
                        $data[6] = $marker = get_string('selfmarked', 'report_learningtimecheck');
                    }
                    $table->data[] = $data;

                    if ($isvalid) {
                        $globals->totalvaliditems++;
                    }

                    // This is  a check raw information array for exports.
                    if ($check->itemoptional == LTC_OPTIONAL_HEADING) {
                        $rawdata = array();
                        $rawdata[0] = '';
                        $rawdata[1] = '<h>'.$check->displaytext;
                        $rawdata[2] = '';
                        $rawdata[3] = '';
                        $rawdata[4] = '';
                        $rawdata[5] = '';
                        $rawdata[6] = '';
                    } else {
                        $rawdata = array();
                        $rawdata[0] = $idnumber;
                        $rawdata[1] = $check->displaytext;
                        $rawdata[2] = $check->credittime;
                        $rawdata[3] = ($marked == 2) ? $check->credittime : '';
                        $rawdata[4] = $marktime;
                        $rawdata[5] = ($isvalid) ? 'yes' : '';
                        $rawdata[6] = $marker;
                    }
                    $table->rawdata[] = $rawdata;

                    $pdfdata = $rawdata;
                    if ($check->itemoptional != LTC_OPTIONAL_HEADING) {
                        $pdfdata[2] .= ' min';
                        $pdfdata[3] .= ' min';
                    }
                    $table->pdfdata[] = $pdfdata;
                }
            }
        }

        // Collect sums and aggregations.

        // Learning time check list name.
        $row2 = new html_table_row();

        $cell1 = new html_table_cell();
        $cell1->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
        $cell1->colspan = 2;
        $cell1->align = 'right';
        $row2->cells[] = $cell1;

        $cell2 = new html_table_cell();
        $str = learningtimecheck_format_time(0 + $globals->totalcoursetime).' '.get_string('totalized', 'learningtimecheck');
        $cell2->text = '<span class="totalizer">'.$str.'</span>';
        $row2->cells[] = $cell2;

        $cell3 = new html_table_cell();
        $str = learningtimecheck_format_time(0 + $globals->courseearnedtime).' '.get_string('totalized', 'learningtimecheck');
        $cell3->text = '<span class="totalizer">'.$str.'</span>';
        $cell3->attributes['class'] = 'learningtimecheck-result';
        $row2->cells[] = $cell3;

        $cell4 = new html_table_cell();
        $cell4->text = '';
        $row2->cells[] = $cell4;

        $cell5 = new html_table_cell();
        $cell5->text = '<span class="totalizer">'.$globals->totalvaliditems.'/'.$globals->totalcourseitems.'</span>';
        $row2->cells[] = $cell5;

        $cell6 = new html_table_cell();
        $cell6->text = '';
        $row2->cells[] = $cell6;

        $table->data[] = $row2;
        $table->pdfdata[] = $row2;

        $table->lineincr = 4;
        $table->smalllineincr = 2;

        $globals->courseprogressratio = sprintf('%0.1f', $globals->courseearneditems / $globals->totalcourseitems * 100). ' %';
    } else {
        $table->data = array();
        $globals->courseprogressratio = '0 %';
    }

    return $table;
}

/**
 * Get's one single user results across all courses where the user has a learningtimecheck module
 * active.
 * @param objectref &$user the user
 * @param objectref &$globals an object to be filled by the function with global aggregates
 * @param array $useroptions display options from the user session
 * @return a table definition with all data for pdf, csv or html output
 */
function report_learningtimecheck_user_results(&$user, &$globals, $useroptions) {
    global $CFG, $DB, $OUTPUT;
    static $CMCACHE = array();
    static $CUSER = array();
    static $CCACHE = array();

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');
    $config = get_config('report_learningtimecheck');

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $idnumberstr = get_string('idnumber');
    $itemtimecreditstr = get_string('itemtimecredit', 'report_learningtimecheck');
    $earnedtimestr = get_string('earnedtime', 'report_learningtimecheck');
    $marktimestr = get_string('marktime', 'report_learningtimecheck');
    $isvalidstr = get_string('isvalid', 'report_learningtimecheck');
    $validatedbystr = get_string('validatedby', 'report_learningtimecheck');

    // For pdf output.
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $itemnamestr = get_string('itemnamepdf', 'report_learningtimecheck');
    $itemtimecreditpdfstr = get_string('itemtimecreditpdf', 'report_learningtimecheck');
    $earnedtimepdfstr = get_string('earnedtimepdf', 'report_learningtimecheck');
    $marktimepdfstr = get_string('marktimepdf', 'report_learningtimecheck');
    $isvalidpdfstr = get_string('isvalidpdf', 'report_learningtimecheck');
    $validatedbypdfstr = get_string('validatedbypdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $yesstr = get_string('yes');
    $nostr = get_string('no');

    $table = new html_table();

    // Screen output.
    $table->head = array($idnumberstr, $itemnamestr, $itemtimecreditstr, $earnedtimestr, $marktimestr, $isvalidstr, $validatedbystr);
    $table->size = array('10%', '40%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center');
    $table->colclasses = array('', '', '', 'highlighted', '', '', '');
    $table->printinfo = array(true, true, true, true, true, true, true);

    // Xls output.
    $table->xlshead = array('idnumber', 'name', 'credittime', 'earnedtime', 'marktime', 'isvalid', 'markedby');
    $table->xlsprintinfo = array(true, true, true, true, true, true, true);

    // Pdf output.

    $table->pdfhead2 = array($idnumberpdfstr, $itemnamestr, $itemtimecreditpdfstr, $earnedtimepdfstr, $marktimepdfstr,
                             $isvalidpdfstr, $validatedbypdfstr);
    $table->pdfsize2 = array('10%', '40%', '10%', '10%', '10%', '10%', '10%');
    $table->pdfalign2 = array('L', 'L', 'L', 'L', 'L', 'C', 'L');
    $table->pdfprintinfo = array(true, true, true, true, true, true, true);

    $table->pdfbgcolor3 = array('#808080', '#808080', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#808080');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#000000', '#000000', '#000000', '#000000', '#ffffff');
    $table->pdfalign3 = array('L', 'L', 'L', 'L', 'L', 'C', 'L');

    $globals = new StdClass();
    $globals->startrange = @$useroptions['startrange'];
    $globals->endrange = @$useroptions['endrange'];

    if (!empty($useroptions['hideidnumber'])) {
        $table->printinfo[0] = false;
        $table->pdfprintinfo[0] = false;
        $table->xlsprintinfo[0] = false;
    }

    // The raw data for exports.
    $table->rawdata = array();

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;
    $reportsettings->marktimeformat = ($config->marktimeformat) ? $config->marktimeformat : 'd/m/Y';

    $globals->userearneditems = 0;
    $globals->userearnedtime = 0;
    $globals->totaluseritems = 0;
    $globals->totalperioditems = 0;
    $globals->totalusertime = 0;
    $globals->totalperiodtime = 0;
    $globals->totalvaliditems = 0;

    $tclmodule = $DB->get_record('modules', array('name' => 'learningtimecheck'));

    // Returns a course arranged array of checklist instances.
    $timechecklistmodules = learningtimecheck_get_my_checks($user->id, 'bycourses');
    $countcourses = count($timechecklistmodules);

    if ($timechecklistmodules) {
        foreach ($timechecklistmodules as $courseid => $coursechecks) {
            if (!array_key_exists($courseid, $CCACHE)) {
                $field = 'id,shortname,fullname,idnumber';
                $CCACHE[$courseid] = $DB->get_record('course', array('id' => $courseid), $field);
            }

            // Course name.
            $row1 = new html_table_row();

            $cell1 = new html_table_cell();
            $cell1->text = '<b>'.$CCACHE[$courseid]->shortname.'</b>';
            $cell1->align = 'left';
            $row1->cells[] = $cell1;

            $cell2 = new html_table_cell();
            $cell2->text = '<b>'.get_string('course').': '.format_string($CCACHE[$courseid]->fullname).'</b>';
            $cell2->colspan = 5;
            $cell2->align = 'left';
            $row1->cells[] = $cell2;

            // This is necessary to trigger the pdf row spanner.
            for ($j = 0; $j < 4 ; $j++) {
                $cell = new html_table_cell();
                $cell->text = '';
                $cell->align = 'left';
                $row1->cells[] = $cell;
            }

            $table->data[] = $row1;
            $table->pdfdata[] = $row1;

            $rawdata = array();
            $rawdata[0] = $CCACHE[$courseid]->shortname;
            $rawdata[1] = $CCACHE[$courseid]->fullname;
            $rawdata[2] = '';
            $rawdata[3] = '';
            $rawdata[4] = '';
            $rawdata[5] = '';
            $rawdata[6] = '';
            $table->rawdata[] = $rawdata;

            foreach ($coursechecks as $clcm) {
                $clobj = new learningtimecheck_class($clcm->cm->id);

                if (count($coursechecks) > 1) {
                    // Learning time check list name.
                    $row2 = new html_table_row();

                    $cell1 = new html_table_cell();
                    $cell1->text = '';
                    $cell1->align = 'left';
                    $row2->cells[] = $cell1;

                    $cell2 = new html_table_cell();
                    $cell2->text = '<b>'.format_string($clobj->learningtimecheck->name).'</b>';
                    $cell2->colspan = 5;
                    $cell2->align = 'left';
                    $row2->cells[] = $cell2;

                    for ($j = 0; $j < 4 ; $j++) {
                        $cell = new html_table_cell();
                        $cell->text = '';
                        $cell->align = 'left';
                        $row2->cells[] = $cell;
                    }

                    $table->data[] = $row2;
                    $table->pdfdata[] = $row2;
                }

                if ($checks = $clobj->get_checks($user->id)) {
                    foreach ($checks as $ck => $check) {

                        if (!report_learningtimecheck_meet_report_conditions($check, $reportsettings, $useroptions, $user, $idnumber)) {
                            continue;
                        }

                        if ($check->itemoptional == LTC_OPTIONAL_NO) {
                            // This is a real marking module.
                            $globals->totaluseritems++;
                            $globals->totalusertime += $check->credittime;

                            $globals->totalperioditems++;
                            $globals->totalperiodtime += $check->credittime;
                        }

                        // TODO : might we use $mods to be a bit faster ? 
                        $data = array();
                        $data[0] = $idnumber;
                        $data[1] = $check->displaytext;
                        $data[2] = 0 + $check->credittime.' min';

                        // Earned time and check status.
                        $marked = report_learningtimecheck_is_marked($check, $clobj);
                        if ($marked > 1) {
                            // Has been marked either by student or teacher.
                            $pix = '<img src="'.$OUTPUT->pix_url('good').'">';
                            $earnedtime = '<span class="good">'.$check->credittime.'</span>';
                            $globals->userearneditems++;
                            $globals->userearnedtime += $check->credittime;
                        } else if ($marked == -1) {
                            $pix = '<img src="'.$OUTPUT->pix_url('bad').'">';
                            $earnedtime = '';
                        } else {
                            $pix = '';
                            $earnedtime = '';
                        }
                        $data[3] = $earnedtime.' '.$pix;
    
                        $marker = '';
                        $marktime = '';
                        $isvalid = '';
                        if ($check->teachermark) {
                            // Has been marked by a teacher or a student able to self validate.
                            if (!array_key_exists($check->teacherid, $CUSER)) {
                                $CUSER[$check->teacherid] = $DB->get_record('user', array('id' => $check->teacherid));
                            }
                            $teacher = $CUSER[$check->teacherid];
                            $data[4] = $marktime = report_learningtimecheck_get_marktime($check, $clobj, $reportsettings->marktimeformat);
                            $data[5] = $isvalid = report_learningtimecheck_is_valid($check, $config);
                            $data[6] = $marker = fullname($teacher);
                        } else if ($check->usertimestamp) {
                            $data[4] = $marktime = report_learningtimecheck_get_marktime($check, $clobj, $reportsettings->marktimeformat);
                            $data[5] = $isvalid = report_learningtimecheck_is_valid($check, $config);
                            $data[6] = $marker = get_string('selfmarked', 'report_learningtimecheck');
                        }
                        $table->data[] = $data;

                        if ($isvalid) {
                            $globals->totalvaliditems++;
                        }

                        // This is  a check raw information array for exports.
                        if ($check->itemoptional == LTC_OPTIONAL_HEADING) {
                            $rawdata = array();
                            $rawdata[0] = '';
                            $rawdata[1] = $check->displaytext;
                            $rawdata[2] = '';
                            $rawdata[3] = '';
                            $rawdata[4] = '';
                            $rawdata[5] = '';
                            $rawdata[6] = '';
                        } else {
                            $rawdata = array();
                            $rawdata[0] = $idnumber;
                            $rawdata[1] = $check->displaytext;
                            $rawdata[2] = 0 + $check->credittime;
                            $rawdata[3] = ($marked == 2) ? 0 + $check->credittime : '';
                            $rawdata[4] = $marktime;
                            $rawdata[5] = ($isvalid) ? $yesstr : $nostr;
                            $rawdata[6] = $marker;
                        }
                        $table->rawdata[] = $rawdata;

                        $pdfdata = $rawdata;
                        if ($check->itemoptional != LTC_OPTIONAL_HEADING) {
                            $pdfdata[2] .= ' min';
                            $pdfdata[3] .= ($pdfdata[3]) ? ' min' : '';
                            $pdfdata[5] = ($isvalid) ? $yesstr : $nostr;
                        }
                        $table->pdfdata[] = $pdfdata;
                    }
                }
            }
        }

        // Collect sums and aggregations.

        // Learning time check list name.
        $row3 = new html_table_row();

        $cell1 = new html_table_cell();
        $cell1->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
        $cell1->colspan = 2;
        $cell1->align = 'right';
        $row3->cells[] = $cell1;

        $cell2 = new html_table_cell();
        $str = learningtimecheck_format_time(0 + $globals->totalusertime).' '.get_string('totalized', 'learningtimecheck');
        $cell2->text = '<span class="totalizer">'.$str.'</span>';
        $row3->cells[] = $cell2;

        $cell3 = new html_table_cell();
        $str= learningtimecheck_format_time(0 + $globals->userearnedtime).' '.get_string('totalized', 'learningtimecheck');
        $cell3->text = '<span class="totalizer">'.$str.'</span>';
        $cell3->attributes['class'] = 'learningtimecheck-result';
        $row3->cells[] = $cell3;

        $cell4 = new html_table_cell();
        $cell4->text = '';
        $row3->cells[] = $cell4;

        $cell5 = new html_table_cell();
        $cell5->text = '<span class="totalizer">'.$globals->totalvaliditems.'/'.$globals->totaluseritems.'</span>';
        $row3->cells[] = $cell5;

        $cell6 = new html_table_cell();
        $cell6->text = '';
        $row3->cells[] = $cell6;

        $table->data[] = $row3;
        $table->pdfdata[] = $row3;

        $table->lineincr = 4;
        $table->smalllineincr = 2;

        $globals->userprogressratio = ($globals->totaluseritems) ? sprintf('%0.1f', $globals->userearneditems / $globals->totaluseritems * 100). '&nbsp;%' : '0 %';
        $globals->userperiodprogressratio = ($globals->totalperioditems) ? sprintf('%0.1f', $globals->userearneditems / $globals->totalperioditems * 100). '&nbsp;%' : '0 %';
    } else {
        $table->data = array();
        $table->pdfdata = array();
        $table->rawdata = array();
        $globals->userprogressratio = '0 %';
        $globals->userperiodprogressratio = '0 %';
    }
    return $table;
}

/**
 * compile all results in a table object, that will be rendered
 * on line or dumped onto a report file format.
 * Gets all results of a single user aggregated course by course
 * @param int $id
 * @param object $user
 * @param arrayref &$globals
 * @param array $useroptions
 * @param boolean $onscreen
 * @return a table definition with all data for pdf, csv or html output
 */
function report_learningtimecheck_user_results_by_course($id, $user, &$globals, $useroptions, $onscreen = false) {
    global $CFG, $DB, $OUTPUT;
    static $CCACHE = array();

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');

    $reportlines = array();

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $shortnamestr = get_string('shortname');
    $idnumberstr = get_string('idnumber');
    $fullnamestr = get_string('fullname');
    $progressstr = get_string('progressbar', 'learningtimecheck');
    $itemstodostr = get_string('itemstodo', 'learningtimecheck');
    $itemsdonestr = get_string('itemsdone', 'learningtimecheck');
    $timetodostr = get_string('timetodo', 'report_learningtimecheck');
    $timedonestr = get_string('timedone', 'learningtimecheck');
    $ratioleftstr = get_string('ratioleft', 'learningtimecheck');
    $timeleftstr = get_string('timeleft', 'learningtimecheck');

    // For pdf output.
    $shortnamepdfstr = get_string('shortnamepdf', 'report_learningtimecheck');
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $progresspdfstr = get_string('progressbarpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $itemsdonepdfstr = get_string('itemsdonepdf', 'report_learningtimecheck');
    $timetodopdfstr = get_string('timetodopdf', 'report_learningtimecheck');
    $timedonepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $timedonepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();

    // Screen output.
    $table->head = array($shortnamestr, $idnumberstr, $fullnamestr, $progressstr, $itemstodostr, $itemsdonestr,
                         $timetodostr, $timedonestr, $timeleftstr);
    $table->size = array('10%', '10%', '30%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', '', 'highlighted', '');
    $table->printinfo = array(true, true, true, true, true, true, true, true);

    // Xls output.
    $table->xlshead = array('shortname', 'idnumber', 'fullname', 'progress', 'itemstodo', 'itemsdone', 'donetime',
                            'timeleft', 'timeleftratio');
    $table->xlsprintinfo = array(true, true, true, true, true, true, true, true, true);

    // Pdf output.
    $table->pdfhead1 = array('', '', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '10%', '30%', '10%', '20%', '20%');
    $table->pdfalign1 = array('L', 'L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#ffffff', '#808080', '#808080');

    $table->pdfhead2 = array($shortnamepdfstr, $idnumberpdfstr, $fullnamestr, $progresspdfstr, $itemstodopdfstr, $itemsdonepdfstr,
                             $timetodopdfstr, $timedonepdfstr, $timeleftpdfstr);
    $table->pdfsize2 = array('10%', '10%', '30%', '10%', '5%', '5%', '10%', '10%', '10%');
    $table->pdfalign2 = array('L', 'L', 'L', 'L', 'C', 'C', 'L', 'L', 'L');

    $table->pdfbgcolor3 = array('#808080', '#808080', '#808080', '#808080', '#f0f0f0', '#f0f0f0', '#808080', '#808080');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#ffffff', '#ffffff', '#808080', '#808080', '#ffffff', '#ffffff');
    $table->pdfalign3 = array('L', 'L', 'L', 'L', 'C', 'C', 'L', 'L', 'L');

    $table->pdfprintinfo = array(true, true, true, true, true, true, true, true, true);

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;

    $table->data = array();
    $table->rawdata = array();

    $globals = new StdClass;
    $globals->startrange = @$useroptions['startrange'];
    $globals->endrange = @$useroptions['endrange'];

    if (!empty($useroptions->hideidnumber)) {
        $table->printinfo[1] = false;
        $table->pdfprintinfo[1] = false;
    }

    // Returns a course arranged array of checklist instances.
    $allchecklists = learningtimecheck_get_my_checks($user->id, 'bycourses');
    $countcourses = count($allchecklists);

    foreach ($allchecklists as $courseid => $coursechecklists) {

        if (!array_key_exists($courseid, $CCACHE)) {
            $CCACHE[$courseid] = $DB->get_record('course', array('id' => $courseid), 'id,shortname,fullname,idnumber');
        }

        $data = array();

        $reporturl = clone($thisurl);
        $reporturl->params(array('id' => $id, 'view' => 'course', 'itemid' => $courseid));
        $courselink = '<a href="'.$reporturl.'">'.$CCACHE[$courseid]->shortname.'</a>';
        $data[0] = $courselink;

        $data[1] = $CCACHE[$courseid]->idnumber;
        $coursename = $CCACHE[$courseid]->fullname;
        $coursecontext = context_course::instance($courseid);

        if (has_capability('moodle/course:view', $coursecontext) && $onscreen) {
            $courseurl = new moodle_url('/course/view', array('id' => $courseid));
            $pixurl = $OUTPUT->pix_url('follow_link', 'report_learningtimecheck');
            $coursename .= ' <a href="'.$courseurl.'"><img src="'.$pixurl.'" /></a>';
        }

        $data[2] = $coursename;

        // Care that all checklists should manage a disjonctive set of items.
        $courseaggregate = array('totalitems' => 0, 'totaltime' => 0, 'tickeditems' => 0, 'tickedtimes' => 0);

        foreach ($coursechecklists as $checklist) {
            $itemstates = $checklist->get_items_for_user($user, $reportsettings, $useroptions);
            $userdata = $itemstates['mandatory'];
            $courseaggregate['totalitems'] += $userdata['items'];
            $courseaggregate['totaltime'] += $userdata['time'];
            $courseaggregate['tickeditems'] += $userdata['ticked'];
            $courseaggregate['tickedtimes'] += $userdata['tickedtime'];
        }

        if ($courseaggregate['totalitems']) {
            $percentcomplete = round(($courseaggregate['tickeditems'] * 100) / $courseaggregate['totalitems']);
        } else {
            $percentcomplete = 0;
            $courseaggregate['tickeditems'] = 0;
        }

        $timeleft = $courseaggregate['totaltime'] - $courseaggregate['tickedtimes'];
        $timedoneratio = (($courseaggregate['totaltime']) ? round($courseaggregate['tickedtimes'] / $courseaggregate['totaltime'] * 100) : 0 );
        $timeleftratio = 100 - $timedoneratio;

        $sumcomplete += $percentcomplete;
        $sumitems += $courseaggregate['totalitems'];
        $sumticked += $courseaggregate['tickeditems'];
        $sumtimetodo += $courseaggregate['totaltime'];
        $sumtickedtime += $courseaggregate['tickedtimes'];
        $sumtimeleft += $timeleft;

        $data[3] = mod_learningtimecheck_renderer::progressbar_thin($percentcomplete);
        $data[4] = $courseaggregate['totalitems'];
        $data[5] = $courseaggregate['tickeditems'];
        $data[6] = learningtimecheck_format_time($courseaggregate['totaltime']);
        $data[7] = learningtimecheck_format_time($courseaggregate['tickedtimes']).' ('.sprintf('%0d', $timedoneratio).'&nbsp;%)';
        $data[8] = learningtimecheck_format_time($timeleft).' ('.sprintf('%0d', $timeleftratio).'&nbsp;%)';

        $table->data[] = $data;

        // Prepare raw data for export.
        $rawdata = array();
        $rawdata[0] = $CCACHE[$courseid]->shortname;
        $rawdata[1] = $CCACHE[$courseid]->idnumber;
        $rawdata[2] = format_string($CCACHE[$courseid]->fullname);
        $rawdata[3] = sprintf('%0.2f', $percentcomplete).' %'; // Change for row data.
        $rawdata[4] = $courseaggregate['totalitems']; // Change for row data for further export conversion.
        $rawdata[5] = $courseaggregate['tickeditems']; // Change for row data for further export conversion.
        $rawdata[6] = $courseaggregate['totaltime']; // Change for row data for further export conversion.
        $rawdata[7] = $courseaggregate['tickedtimes']; // Change for row data for further export conversion.
        $rawdata[8] = $timedoneratio; // Change for row data for further export conversion.
        $rawdata[9] = $timeleft; // Change for row data for further export conversion.
        $rawdata[10] = $timeleftratio; // Change for row data for further export conversion.

        $table->rawdata[] = $rawdata;

        $pdfdata = $data;
        $pdfdata[0] = $CCACHE[$courseid]->shortname;
        $pdfdata[1] = $CCACHE[$courseid]->idnumber;
        $pdfdata[2] = format_string($CCACHE[$courseid]->fullname);
        $pdfdata[3] = sprintf('%0.2f', $percentcomplete);

        $table->pdfdata[] = $pdfdata;
    }

    // Make last row with average and sums.

    $row1 = new html_table_row();

    $cell1 = new html_table_cell();
    $cell1->text = '';
    $cell1->align = 'right';
    $row1->cells[] = $cell1;

    $cell2 = new html_table_cell();
    $cell2->text = '';
    $cell2->align = 'right';
    $row1->cells[] = $cell2;

    $cell3 = new html_table_cell();
    $cell3->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
    $cell3->align = 'right';
    $row1->cells[] = $cell3;

    $cell4 = new html_table_cell();
    if ($countcourses) {
        $cell4->text = sprintf('%0.1f', $sumcomplete / $countcourses).' % '.get_string('average', 'learningtimecheck');
    } else {
        $cell4->text = sprintf('%0d', 0).' % '.get_string('average', 'learningtimecheck');
    }
    $row1->cells[] = $cell4;

    $cell5 = new html_table_cell();
    $cell5->text = '<span class="totalizer">'.$sumitems.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell5;

    $cell6 = new html_table_cell();
    $cell6->text = '<span class="totalizer">'.$sumticked.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell6;

    $cell7 = new html_table_cell();
    $cell7->text = '';
    $cell7->attributes['class'] = '';
    $row1->cells[] = $cell7;

    $cell8 = new html_table_cell();
    $str = learningtimecheck_format_time($sumtickedtime).' '.get_string('totalized', 'learningtimecheck');
    $cell8->text = '<span class="totalizer">'.$str.'</span>';
    $cell8->attributes['class'] = 'learningtimecheck-result';
    $row1->cells[] = $cell8;

    $cell9 = new html_table_cell();
    $cell9->text = learningtimecheck_format_time($sumtimeleft).' '.get_string('totalized', 'learningtimecheck');
    $cell9->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell9;

    $table->data[] = $row1;
    $table->pdfdata[] = $row1;
    return $table;
}

/**
 * Centralizes several checks that can be done on checks for figuring on reports
 * @param objectref &$check the check
 * @param objectref &$reportsettings some report bound options for reports
 * @param arrayref &$useroptions personal options for reports
 * @param objectref &$user the check's owner
 * @param textref &$idnumber, a scalar to fill with module idnumber if the check has one assigned.
 * @return boolean true if conditions are met, else false
 */
function report_learningtimecheck_meet_report_conditions(&$check, &$reportsettings, &$useroptions, &$user, &$idnumber) {
    global $CB, $COURSE, $DB;
    static $CMCACHE = array();
    static $modinfo;

    if (empty($modinfo)) {
        $modinfo = get_fast_modinfo($COURSE->id, $user->id);
    }

    if (empty($reportsettings->showoptional) && ($check->itemoptional == LTC_OPTIONAL_YES)) {
        return false;
    }

    if (!empty($useroptions['hideheadings']) && ($check->itemoptional == LTC_OPTIONAL_HEADING)) {
        return false;
    }

    // Not credited and therefore hidden.
    if (!$check->credittime && !empty($useroptions['hidenocredittime'])) {
        return false;
    }

    // Not checked and therefore hidden. some reports f.e. course level summary need force unmarked to be considered.
    if ((!$check->usertimestamp) && (!empty($useroptions['hideunmarkedchecks']) && empty($reportsettings->forceshowunmarked))) {
        return false;
    }

    // Not in report's range requirements.
    if (!report_learningtimecheck_check_report_range($useroptions, $check)) {
        return false;
    }

    // Check module can be elected.
    if (!empty($check->moduleid)) {
        if (!array_key_exists($check->moduleid, $CMCACHE)) {
            try {
                $cm = $modinfo->get_cm($check->moduleid);
                $CMCACHE[$check->moduleid] = $cm;
            } catch (Exception $e) {
                rebuild_course_cache($COURSE->id);
            }

            // Second try once course rebuilt.
            try {
                $cm = $modinfo->get_cm($check->moduleid);
                $CMCACHE[$check->moduleid] = $cm;
            } catch (Exception $e) {
                // Third try.
                $cm = $CMCACHE[$check->moduleid] = $DB->get_record('course_modules', array('id' => $check->moduleid));
                if (empty($cm)) {
                    // Forget this module we tried everything.
                    return false;
                }
                $cm->uservisible = $cm->visible;
            }
        } else {
            $cm = $CMCACHE[$check->moduleid];
        }

        if (empty($CMCACHE[$check->moduleid])) {
            // Those modules were deleted ?
            return false;
        }

        if ($COURSE->format == 'page') {
            // If paged, check the module is on a visible page.
            if (!course_page::is_module_visible($CMCACHE[$check->moduleid], false)) {
                return false;
            }
        }

        if (!groups_course_module_visible($CMCACHE[$check->moduleid], $user->id)) {
            return false;
        }
        $idnumber = $CMCACHE[$check->moduleid]->idnumber;
    } else {
        $idnumber = '';
    }

    return true;
}

function learningtimecheck_curl_callback($response, $info) {
    if (!empty($info['http_code']) and ($info['http_code'] != 200)) {
        mtrace('Request for '.$info['url'].' failed with HTTP code '.$info['http_code']);
    } else {
        mtrace('Successfully completed in '.$info['total_time'].' seconds.');
    }
}

/**
 * TODO : study scalability for many users, courses, etc
 * @param objectref &$mform
 * @param string $type
 * @return an array of form elements (created but not yet added).
 */
function report_learningtimecheck_create_form_elements(&$mform, $type) {
    global $DB;

    $elements = array();

    switch ($type) {
        case 'user': {
            $users = $DB->get_records('user', array('deleted' => 0, 'confirmed' => 1), 'lastname,firstname', 'id,lastname,firstname');
            $useroptions = array();
            foreach ($users as $u) {
                $useroptions[$u->id] = $u->lastname.' '.$u->firstname;
            }
            $elements[] = &$mform->createElement('select', 'itemids', get_string('user'), $useroptions);
            break;
        }

        case 'cohort': {
            $contextsystem = context_system::instance();
            $cohortoptions = $DB->get_records_menu('cohort', array('contextid' => $contextsystem->id), 'name', 'id, name');
            $elements[] = &$mform->createElement('select', 'itemids', get_string('cohort', 'report_learningtimecheck'), $cohortoptions);
            break;
        }

        case 'course': {
            $courseoptions = $DB->get_records_menu('course', array(), 'sortorder', 'id, fullname');
            $elements[] = &$mform->createElement('select', 'itemids', get_string('course'), $courseoptions);
            break;
        }
    }

    return $elements;
}

/**
 * get all current user related options for reports.
 * this option set are calendary switch to filter out which checks
 * are to be considered or not, depending on the marking time.
 */
function report_learningtimecheck_get_user_options() {
    global $DB, $USER;

    if (!$optionrecs = $DB->get_records('report_learningtimecheck_opt', array('userid' => $USER->id))) {
        return array();
    }

    $options = array();
    foreach ($optionrecs as $recid => $option) {
        $options[$option->name] = $option->value;
    }

    return $options;
}

/**
 * Checks if a check record is marked depending on learningtimecheck settings
 * @param object $check
 * @param obj $checklist
 * @return an int status in (-1: refused, 0: undefined, 1:checked not validated, 2:checked validated)
 */
function report_learningtimecheck_is_marked($check, &$checklist) {

    if ($checklist->learningtimecheck->teacheredit == LTC_MARKING_TEACHER) {
        if ($check->teachermark == LTC_TEACHERMARK_YES) {
            return 2;
        }
        if ($check->teachermark == LTC_TEACHERMARK_NO) {
            return -1;
        }
        if (!empty($check->usertimestamp)) {
            return 1;
        }
        return 0;
    }

    if ($checklist->learningtimecheck->teacheredit == LTC_MARKING_BOTH) {
        if ($check->teachermark == LTC_TEACHERMARK_YES && !empty($check->usertimestamp)) {
            return 2;
        }
        if ($check->teachermark == LTC_TEACHERMARK_NO) {
            return -1;
        }
        if (!empty($check->usertimestamp)) {
            return 1;
        }
        return -1;
    }

    if ($checklist->learningtimecheck->teacheredit == LTC_MARKING_EITHER) {
        // Only when strictly marking teachers. If both rely on student marking by skipping this.
        if ($check->teachermark == LTC_TEACHERMARK_YES || !empty($check->usertimestamp)) {
            return 2;
        }
        if ($check->teachermark == LTC_TEACHERMARK_NO) {
            return -1;
        }
        return 0;
    }

    if ($checklist->learningtimecheck->teacheredit == LTC_MARKING_STUDENT) {
        if (!empty($check->usertimestamp)) {
            // Student marked and allowed to self validate.
            return 2;
        }
    }
    return 0;
}

/**
 * We check several conditions for a user being viewable :
 * - User belongs to a course i have sufficient capability on users
 * - I have no group visibility and user is in my groups
 */
function report_learningtimecheck_is_user_visible($user, $viewallgroups, &$mygroups) {
    global $USER;

    $cansee = false;

    // First check you are sharing a course.
    $courses = enrol_get_users_courses($user->id, true, 'id,shortname', 'sortorder');
    foreach ($courses as $cid => $foo) {
        $ctx = context_course::instance($cid);
        if (has_capability('report/learningtimecheck:viewother', $ctx)) {
            $cansee = true;
            break;
        }
    }

    if (!$cansee) {
        return false;
    }

    // Now check groups.
    if ($viewallgroups) {
        // definitely can see
        return true;
    }

    $cangroupsee = false;

    foreach ($mygroups as $g) {
        // Only one is enough.
        if (groups_is_member($g->id, $user->id)) {
            return true;
        }
    }

    return $cangroupsee;
}

/**
 * prints a menu form for groupings
 * @param object $course
 * @param string or moodleurl $url
 * @param string $view
 * @todo move to renderer, unify to $courseid input param
 * @return the group change menu
 */
function report_learningtimecheck_groupings_print_menu($course, $url, $view = 'course') {
    $str = '';

    $groupings = groups_get_all_groupings($course->id);

    if (!$groupings) {
        return '';
    }

    $currentgroupingid = optional_param('groupingid', 0, PARAM_INT);
    $currentgroupid = optional_param('groupid', 0, PARAM_INT);
    $courseid = optional_param('id', 0, PARAM_INT);
    $str = '<form name="groupingselectform" action="'.$url.'" method="get">';
    $str .= '<input type="hidden" name="groupid" value="'.$currentgroupid.'" />';
    $str .= '<input type="hidden" name="id" value="'.$courseid.'" />';
    $str .= '<input type="hidden" name="view" value="'.$view.'" />';
    $str .= '<select name="groupingid" onchange="this.form.submit()">';
    $groupingselected = (!$currentgroupingid) ? 'selected="selected"' : '';
    $str .= '<option value="0" '.$groupingselected.'>'.get_string('allusers', 'report_learningtimecheck').'</option>';
    foreach ($groupings as $groupingid => $grouping) {
        $groupingselected = ($groupingid == $currentgroupingid) ? 'selected="selected"' : '';
        $str .= '<option value="'.$grouping->id.'" '.$groupingselected.'>'.$grouping->name.'</option>';
    }
    $str .= '</select>';
    $str .= '</form>';

    return $str;
}

/**
 * Get enrolled users with some filtering
 * TODO : cleanup this strategy, bound on role which should not
 * @param int $courseid
 * @param int $groupid
 * @param int $groupingid
 * @param int $from the starting record number
 * @param int $pagesize size of results page
 */
function report_learningtimecheck_get_users($courseid, $groupid = 0, $groupingid = 0, $from = 0, $pagesize = 0) {
    global $DB;

    $role = $DB->get_record('role', array('shortname' => 'student'));
    $sqlvars = array($courseid, $role->id);
    $selectjoin = '';
    $tablejoin = '';
    if ($groupid) {
        $tablejoin = ", {groups_members} gm ";
        $selectjoin = " u.id = gm.userid AND gm.groupid = ? AND ";
        $sqlvars[] = $groupid;
    }

    $sql = "
        SELECT
            u.*
        FROM
            {user} u,
            {user_enrolments} ue,
            {context} ctx,
            {role_assignments} ra,
            {enrol} e
            $tablejoin
        WHERE
            u.id = ue.id AND
            e.id = ue.enrolid AND
            ctx.contextlevel = 50 AND
            ctx.instanceid = ? AND
            ra.contextid = ctx.id AND
            ra.userid = u.id AND
            ra.roleid = ? AND
            $selectjoin
            u.deleted = 0
    ";

    $users = $DB->get_records_sql($sql, $sqlvars, $from, $pagesize);

    return $users;
}

/**
 * Checks all access policeies against required group and user situation
 * In separated groups mode, you can only view your groups
 * @param int $courseid
 * @param int $groupid
 * @param int $groupingid
 * @return boolean
 */
function check_group_authorisation($courseid, $groupid = 0, $groupingid = 0) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid));
    $coursecontext = context_course::instance($courseid);

    $accessallgroups = has_capability('moodle/site:accessallgroups', $coursecontext);
    $mygroups = groups_get_user_groups($courseid);
    $groupmode = groups_get_course_groupmode($course);

    if ($groupmode == SEPARATEGROUPS) {
        if ($accessallgroups) {
            return true;
        }

        if ($groupingid) {
            // check if one of my groups matches the grouping.
            foreach ($mygroups as $gm) {
                $groupgroupingid = $DB->get_field('groupings_groups', 'groupingid', array('groupid' => $gm->groupid));
                if ($groupingid == $groupgroupingid) {
                    return true;
                }
            }
        }

        if ($groupid) {
            foreach ($mygroups as $gm) {
                if ($gm->groupid == $groupid) {
                    return true;
                }
            }
        }
    } else {
        return true;
    }

    return false;
}

/**
 * this function publicizes retrieval of the physical path to 
 * a file stored for mail attachement or for PDF generation.
 */
function report_learningtimecheck_get_path_from_hash($contenthash) {
    $l1 = $contenthash[0].$contenthash[1];
    $l2 = $contenthash[2].$contenthash[3];
    return "$l1/$l2";
}

/**
 * @param object $job a report job descriptor
 * @param ref $data a report data stub
 * @param ref $globals a global data stub
 */
function report_learningtimecheck_prepare_data($job, &$data, &$globals) {
    global $DB;

    $options = (array) json_decode(@$job->options);

    if (!$job->detail) {
        // Global reports print a single document with summarize lines
        switch ($job->type) {
            case 'userdetail': {
                // Global user document prints a single document with course by course summary.
                $fooid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemids));
                $data = report_learningtimecheck_user_course_results($job->courseid, $user, $globals, $options);
                break;
            }

            case 'usercursus': {
                // Global user document prints a single document with course by course detail.
                $fooid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemids));
                $data = report_learningtimecheck_user_results($user, $globals, $options);
                break;
            }

            case 'user': {
                // Global user document prints a single document with course by course summary.
                $fooid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemids));
                $data = report_learningtimecheck_user_results_by_course($fooid, $user, $globals, $options);
                break;
            }

            case 'course': {
                // Global course document prints a single document with user by user summary.
                $course = $DB->get_record('course', array('id' => $job->itemids));
                $coursecontext = context_course::instance($course->id);
                $targetusers = get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id, '.get_all_user_name_fields(true, 'u').', u.idnumber', 'lastname, firstname', 0, 0, 0 + @$job->groupid, '', false);
                learningtimecheck_apply_rules($targetusers, $job->filters);
                $data = report_learningtimecheck_course_results($job->itemids, $targetusers, $course->id, $globals, $options);
                break;
            }

            case 'cohort': {
                // Global cohort document prints a single document with user by user summary.
                $cohort = $DB->get_record('cohort', array('id' => $job->itemids));
                $cohortmembers = report_learningtimecheck_get_cohort_users($cohort->id);
                learningtimecheck_apply_rules($cohortmembers, $job->filters);
                $data = report_learningtimecheck_cohort_results($job->itemids, $cohortmembers, $globals, $options);
                break;
            }
        }
    } else {
        // Detail reports will prepare a composite array of report data for further outputing.
        switch ($job->type) {
            case 'user' : {
                // Detail user document prints a document per course with activity detail, then zips the document list.
                $notusedid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemids));
                $allchecks = learningtimecheck_get_my_checks($user->id, 'bycourses');
                foreach ($allchecks as $courseid => $coursechecksfoo) {
                    $reportdata = new StdClass();
                    $reportdata->type = 'userdetail';
                    $reportdata->itemid = $job->itemids;
                    $reportdata->jobcontext = $DB->get_record('course', array('id' => $courseid));
                    $reportdata->data = report_learningtimecheck_user_course_results($notusedid, $user, $userglobals, $options);
                    $globals[$courseid] = $userglobals;
                    $data[$courseid] = $reportdata;
                }
                break;
            }

            case 'course': {
                // Detail course document prints a document per user with activity detail in the course, then zips the document list.
                $course = $DB->get_record('course', array('id' => $job->itemids));
                $coursecontext = context_course::instance($course->id);
                $courseusers = get_enrolled_users($coursecontext);
                learningtimecheck_apply_rules($courseusers, $job->filters);
                foreach ($courseusers as $uid => $u) {
                    $reportdata = new StdClass();
                    $reportdata->type = 'userdetail';
                    $reportdata->itemid = $uid;
                    $reportdata->jobcontext = $course;
                    $reportdata->data = report_learningtimecheck_user_course_results($course->id, $u, $userglobals, $options);
                    $globals[$uid] = $userglobals;
                    $data[$uid] = $reportdata;
                }
                break;
            }

            case 'cohort': {
                // Detail cohort document prints a document per user with user detail, then zips the document list.
                $notusedid = 0;

                $cohortusers = $DB->get_records('cohort_members', array('cohortid' => $job->itemids));
                learningtimecheck_apply_rules($cohortusers, $job->filters);
                if (!empty($cohortusers)) {
                    foreach ($cohortusers as $uid => $cm) {
                        $reportdata = new StdClass();
                        // Global user document prints a single document with course by course summary.
                        $user = $DB->get_record('user', array('id' => $cm->userid));
                        $reportdata->itemid = $cm->userid;
                        $reportdata->type = 'usercursus';
                        $reportdata->jobcontext = $DB->get_record('cohort', array('id' => $job->itemids));
                        $reportdata->data = report_learningtimecheck_user_results($user, $userglobals, $options);
                        $globals[$cm->userid] = $userglobals;
                        $data[$cm->userid] = $reportdata;
                    }
                }
                break;
            }
        }
    }
}

/**
 * Gets the appropriate identifier to use for an entity given the type and the primary id.
 * @param string $type
 * @param int $id
 */
function report_learningtimecheck_get_itemidentifier($type, $id) {
    global $DB;

    if (!in_array($type, array('course', 'user', 'cohort', 'userdetail', 'usercursus'))) {
        throw new coding_exception("Invalid report type $type ");
    } 

    $config = get_config('report_learningtimecheck');

    switch ($type) {
        case 'userdetail':
        case 'usercursus':
        case 'user':
                switch ($config->infilenameuseridentifier) {

                    case REPORT_IDENTIFIER_IDNUMBER:
                        $itemidentifier = $DB->get_field('user', 'idnumber', array('id' => $id));
                        if (empty($itemidentifier)) {
                            $itemidentifier = 'id$'.$id;
                        }
                        break;

                    case REPORT_IDENTIFIER_NAME:
                        $itemidentifier = $DB->get_field('user', 'username', array('id' => $id));
                        break;

                    case REPORT_IDENTIFIER_ID:
                    default:
                        $itemidentifier = $id;
                        break;

                }
            break;

        case 'course':
                switch ($config->infilenamecourseidentifier) {

                    case REPORT_IDENTIFIER_IDNUMBER:
                        $itemidentifier = $DB->get_field('course', 'idnumber', array('id' => $id));
                        if (empty($itemidentifier)) {
                            $itemidentifier = 'id$'.$id;
                        }
                        break;

                    case REPORT_IDENTIFIER_NAME:
                        $itemidentifier = $DB->get_field('course', 'shortname', array('id' => $id));
                        break;

                    case REPORT_IDENTIFIER_ID:
                    default:
                        $itemidentifier = $id;
                        break;

                }
            break;

        case 'cohort':
                switch ($config->infilenamecohortidentifier) {

                    case REPORT_IDENTIFIER_IDNUMBER:
                        $itemidentifier = $DB->get_field('cohort', 'idnumber', array('id' => $id));
                        if (empty($itemidentifier)) {
                            $itemidentifier = 'id$'.$id;
                        }
                        break;

                    case REPORT_IDENTIFIER_NAME:
                        $itemidentifier = $DB->get_field('cohort', 'name', array('id' => $id));
                        break;

                    case REPORT_IDENTIFIER_ID:
                    default:
                        $itemidentifier = $id;
                        break;

                }
            break;

        default:
            break;
    }

    return $itemidentifier;
}

/**
 * Get a readable time of the mark
 * @param object $check
 * @param object the originating learningtimecheck instance
 * @param string $formatted a time format (function date) or false for a default time format.
 */
function report_learningtimecheck_get_marktime($check, $learningtimecheck, $formatted = false) {

    if ($learningtimecheck->teacheredit >= LTC_MARKING_TEACHER) {
        $time = $check->teachertimestamp;
    } else {
        $time = $check->usertimestamp;
    }

    if ($formatted === true) {
        return date('Y/m/d h:i', $time);
    }
    if (!empty($formatted)) {
        return date($formatted, $time);
    }

    return $time;
}

/**
 * Validity is checked againsts several criteria :
 * - A time criteria, when a working day is validated
 * - A working day, based on calendar events. A special user scope
 * - checking can be forced over some users depending on a capability marking
 * event should be added to the user's calendar, marking the day that can be 
 * validated as work time
 * @param objectref $check the checkrecord to validate
 * @param objectref $config the global configuration of learningtimecheck reports 
 * @param object $context
 */
function report_learningtimecheck_is_valid(&$check, &$config = null, $context = null) {
    global $DB;

    // Defaults to a global marking role being setup at site level for those users.
    if (is_null($context)) {
        $context = context_system::instance();
    }

    if (is_null($config)) {
        $config = get_config('report_learningtimecheck');
    }

    if ($config->checkworkingdays ||
            has_capability('report/learningtimecheck:iswdsensitive', $context, $check->userid)) {

        if (defined("DEBUG_LTC_CHECK") && DEBUG_LTC_CHECK) {
            if ($config->checkworkingdays) {
                if (function_exists('debug_trace')) {
                    debug_trace('checking on by configuration');
                }
            } else {
                if (function_exists('debug_trace')) {
                    debug_trace('checking on by capability');
                }
            }
        }

        $checkdate = date('j_n_Y', $check->usertimestamp);

        // Check if we have "working day" events for this check.
        $select = " 
            userid = ? AND
            CONCAT(DAY(FROM_UNIXTIME(timestart)), '_', MONTH(FROM_UNIXTIME(timestart)), '_', YEAR(FROM_UNIXTIME(timestart))) = ? AND
            eventtype = 'user' AND
            uuid = 'learningtimecheck'
        ";

        if (defined("DEBUG_LTC_CHECK") && DEBUG_LTC_CHECK) {
            mtrace("checking events for $check->userid ar $checkdate<br/>");
        }
        $possibleevents = $DB->get_records_select('event', $select, array($check->userid, $checkdate));

        // Get some identifying fields for user.
        $user = $DB->get_record('user', array('id' => $check->userid), 'id,username,email,idnumber');

        if ($possibleevents) {

            if (defined("DEBUG_LTC_CHECK") && DEBUG_LTC_CHECK) {
                mtrace('possible events');
            }
            foreach ($possibleevents as $ev) {
                if (defined("DEBUG_LTC_CHECK") &&DEBUG_LTC_CHECK) {
                    mtrace('Event '.$ev->description);
                }
                if ($eventkey = report_learningtimecheck_extract_eventkey($ev)) {

                    // The event is a "Working Day" event.
                    $userkey = report_learningtimecheck_get_eventkey($ev, $user);
                    if (defined("DEBUG_LTC_CHECK") && DEBUG_LTC_CHECK) {
                        mtrace('key match '.$eventkey.' == '.$userkey);
                    }
                    if ($eventkey == $userkey) {
                        $result = report_learningtimecheck_check_time($check, $config);
                        if (defined("DEBUG_LTC_CHECK") && DEBUG_LTC_CHECK) {
                            mtrace('checking time '.$result);
                        }
                        return $result;
                    }
                }
            }
        }

        if (defined("DEBUG_LTC_CHECK") && DEBUG_LTC_CHECK) {
            mtrace('Not agreeing event ');
        }
        return false;
    }

    return true;
}

/**
 * Extracts the keyed workday mark from the event message
 * @param object $event an event with supposed workday crypto inside. 
 */
function report_learningtimecheck_extract_eventkey($event) {
    if (preg_match('/WD\\[([0-9a-zA-Z=]+)\\]/', $event->description, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * Build a unique event key per user and date.
 * @param object|string $ev an event or a timestamp
 * @param object|string $user the event owner user or a username as string
 */
function report_learningtimecheck_get_eventkey($ev, $user) {
    $config = get_config('report_learningtimecheck');

    if (is_string($user)) {
        $username = $user;
    } else {
        $username = $user->username;
    }
    if (!is_object($ev)) {
        $evtime = $ev;
    } else {
        $evtime = $ev->timestart;
    }

    $userkey = $username.'_'.date('d_m_Y', $evtime).'_'.$config->wdsecret;

    return base64_encode(md5($userkey));
}

/**
 * Checks each event against the global settings. All calculations in secs.
 * @param object $check the check
 * @param object $config the global configuration or the report engine
 */
function report_learningtimecheck_check_time(&$check, &$config) {

    if (!report_learningtimecheck_check_day($check, $config)) {
        return false;
    }

    // Check we are in working hours range.
    $workstart = $config->workingtimestart_h * HOURSECS + $config->workingtimestart_m * MINSECS;
    $workend = $config->workingtimeend_h * HOURSECS + $config->workingtimeend_m * MINSECS;

    $evtime = date('G', $check->usertimestamp) * HOURSECS + date('i', $check->usertimestamp) * MINSECS;
    if ($evtime <= $workstart || $evtime >= $workend) {
        return false;
    }

    return true;
}

/**
 * Checks the days constraint.
 * @param objectref &$check a check timestamp to match
 * @param objectref &$config the LTC configuration
 * @return false if not in acceptable days, either true.
 */
function report_learningtimecheck_check_day(&$check, &$config) {

    // Check this is NOT a vacation day.
    $evdayofyear = date('z', $check->usertimestamp);
    $vacationdays = explode(',', $config->vacationdays);
    if (in_array($evdayofyear, $vacationdays)) {
        return false;
    }

    // Check this is a natural working days as set in options.
    $evworkday = date('N', $check->usertimestamp);
    $workdaykey = 'workday'.$evworkday;
    if (!$config->$workdaykey) {
        return false;
    }

    return true;
}

/**
 * Crops a session against a valid range. Invalidates session (null components) if the session
 * and range are not sequent
 */
function report_learningtimecheck_crop_session(&$session, &$config) {

    $start = $session->sessionstart;
    $end = $session->sessionend;

    $ss = date('G', $start) * HOURSECS + date('i', $start) * MINSECS + date('s', $start);
    $se = date('G', $end) * HOURSECS + date('i', $end) * MINSECS + date('s', $end);
    $rs = $config->workingtimestart_h * HOURSECS + $config->workingtimestart_m * MINSECS;
    $re = $config->workingtimeend_h * HOURSECS + $config->workingtimeend_m * MINSECS;

    if ($se < $rs || $ss > $re) {
        // All session is before range or after range.
        // Invalidate session.
        $session->sessionstart = 0;
        $session->sessionend = 0;
    } else if ($ss < $rs && $se > $re) {
        // Session is containing range. Crop to range (shifting session boundaries).
        $session->sessionstart += $rs - $ss;
        $session->sessionend -= $se - $re;
    } else if ($ss < $rs && $se > $rs) {
        // Session match range start, Crop session start (shifting session start).
        $session->sessionstart += $rs - $ss;
    } else if ($ss < $re && $se > $re) {
        // Session match range end, Crop session end (shifting session end).
        $session->sessionend -= $se - $re;
    } else {
        // All session in valid range.
        assert(1);
    }
    $session->elapsed = $session->sessionend - $session->sessionstart;
}

/**
 * Given a session that might overpass day boundaries, splice into single day sessions.
 */
function report_learningtimecheck_splice_session($session) {
    $daytimestart = date('G', $session->sessionstart) * HOURSECS + date('i', $session->sessionstart) * MINSECS + date('s', $session->sessionstart);
    $endofday = 24 * HOURSECS;
    $daygap = $endofday - $daytimestart;
    $startstamp = $session->sessionstart;

    $sessions = array();

    while ($startstamp + $daygap < $session->sessionend) {
        $daysess = new StdClass();
        $daysess->sessionstart = $startstamp;
        $daysess->sessionend = $startstamp + $daygap;
        $daysess->elapsed = $daygap;
        $daytimestart = 0; // Back to midnight;
        $daygap = $endofday - $daytimestart;
        $startstamp = $daysess->sessionend;
        $sessions[] = $daysess;
    }

    // We now need to keep the last segment.
    if ($startstamp < $session->sessionend) {
        $daysess = new stdClass();
        $daysess->sessionstart = $startstamp;
        $daysess->sessionend = $session->sessionend;
        $daysess->elapsed = $session->sessionend - $daysess->sessionstart;
        $sessions[] = $daysess;
    }

    return $sessions;
}

/**
 * @param string $what what to remove : all removes all events for marking cheks, 'invalid' removes improperly
 * encoded events.
 */
function report_learningtimecheck_remove_events($what = 'all') {
    global $DB;

    if ($what == 'all') {
        $DB->delete_records('event', array('uuid' => 'learningtimecheck'));
    } else if ($what == 'invalid') {
        // Use recordset to scan records with less memory foot print.
        $rs = $DB->get_recordset('event', array('uuid' => 'learningtimecheck'));
        foreach ($rs as $record) {
            if (!preg_match('/WD\\[([0-9A-F]+)\\]/', $record->description, $matches)) {
                $DB->delete_records('event', array('id' => $record->id));
            } else {
                $username = $DB->get_field('user', 'username', array('id' => $record->userid));
                $evkey = report_learningtimecheck_get_eventkey($ev, $username);
                if ($evkey != $matches[1]) {
                    // This is an invalid key, maybe because the secret has changed in the meanwhile.
                    $DB->delete_records('event', array('id' => $record->id));
                }
            }
        }
        $rs->close();
    }
}

/**
 * Generates an avent in the user calendar, marked and encoded to be a Working Day marker.
 * @param object $user
 * @param int $day
 * @param int $month
 * @param int $year
 */
function report_learningtimecheck_generate_event($user, $day, $month, $year) {
    global $DB;

    $time = mktime(12, 0, 0, $month, $day, $year);
    $stringmgr = get_string_manager();

    $event = new StdClass();
    $event->name = $stringmgr->get_string('event', 'report_learningtimecheck', '', $user->lang);
    $lkey = report_learningtimecheck_get_eventkey($time, $user->username);
    $event->description = $stringmgr->get_string('eventbody', 'report_learningtimecheck', $lkey, $user->lang);
    $event->format = FORMAT_MOODLE;
    $event->courseid = 0;
    $event->groupid = 0;
    $event->userid = $user->id;
    $event->repeatid = 0;
    $event->modulename = 0;
    $event->instance = 0;
    $event->eventtype = 'user';
    $event->timestart = $time;
    $event->timeduration = 0;
    $event->visible = 1;
    $event->uuid = 'learningtimecheck';
    $event->sequence = 0;
    $event->timemodified = time();
    $event->subscriptionid = 0;

    $params = array('eventtype' => 'user', 'userid' => $user->id, 'uuid' => 'learningtimecheck', 'timestart' => $time);
    if (!$DB->record_exists('event', $params)) {
        $DB->insert_record('event', $event);
        return true;
    }
    return false;
}

/**
 * Check a CSV input line format for empty or commented lines
 * Ensures compatbility to UTF-8 BOM or unBOM formats
 * @param stringref &$text
 * @param boolean $resetfirst
 */
function report_learningtimecheck_is_empty_line_or_format(&$text, $resetfirst = false) {
    global $CFG;
    static $textlib;
    static $first = true;

    // We may have a risk the BOM is present on first line.
    if ($resetfirst) {
        $first = true;
    }
    if (!isset($textlib)) {
        $textlib = new core_text(); // Singleton.
    }
    if ($first) {
        $text = $textlib->trim_utf8_bom($text);
        $first = false;
    }

    $text = preg_replace("/\n?\r?/", '', $text);

    return preg_match('/^$/', $text) || preg_match('/^(\(|\[|-|#|\/| )/', $text);
}

/**
 * checks a learningtimecheck item against report user requirements
 * @param array $useroptions
 * @param object $itemcheck
 */
function report_learningtimecheck_check_report_range($useroptions, $itemcheck) {

    $useroptions = (object)$useroptions;

    if (!empty($useroptions->startrange)) {
        if ($useroptions->startrange > 0) {
            if ($itemcheck->usertimestamp < $useroptions->startrange) {
                return false;
            }
        }
    }

    if (!empty($useroptions->endrange)) {
        if ($useroptions->endrange > 0) {
            if ($itemcheck->usertimestamp > $useroptions->endrange) {
                return false;
            }
        }
    }

    return true;
}

function report_learningtimecheck_get_user_workdays($userid) {
    global $DB;

    $params = array('userid' => $userid, 'eventtype' => 'user', 'uuid' => 'learningtimecheck');
    return $DB->get_records('event', $params);
}