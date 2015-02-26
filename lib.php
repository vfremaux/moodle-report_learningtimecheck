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
 * @package    report
 * @subpackage trainingsessions
 * @copyright  2012 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/renderer.php');

defined('MOODLE_INTERNAL') || die;

// The max number of report build workers. This will depend on your processing capabilities (number of clusters/cores/threads)
define('REPORT_LEARNINGTIMECHECK_MAX_WORKERS', 4);

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_learningtimecheck_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $OUTPUT;

    if (has_capability('report/learningtimecheck:view', $context)) {
        $url = new moodle_url('/report/learningtimecheck/index.php', array('id' => $course->id));
        $navstr = get_string('pluginname', 'report_learningtimecheck');
        $navigation->add($navstr, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

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
* Called by the storage subsystem to give back a report
*
*/
function report_learningtimecheck_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    require_course_login($course);

    if ($filearea !== 'userreports' && $filearea !== 'coursereports' && $filearea !== 'cohortreports') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'report_learningtimecheck', $filearea, $course->id, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    $forcedownload = true;

    session_get_instance()->write_close();
    send_stored_file($file, 60*60, 0, $forcedownload);
}

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
*
*/
function report_learningtimecheck_cohort_results($id, $cohortmembers) {
    global $CFG;

     $thisurl = $CFG->wwwroot.'/report/learningtimecheck/index.php';

    $idnumberstr = get_string('idnumber');
    $fullnamestr = get_string('fullname');
    $progressstr = get_string('progressbar', 'learningtimecheck');
    $itemstodostr = get_string('itemstodo', 'learningtimecheck');
    $doneitemsstr = get_string('doneitems', 'learningtimecheck');
    $donetimestr = get_string('timedone', 'learningtimecheck');
    $leftratiostr = get_string('ratioleft', 'learningtimecheck');
    $timeleftstr = get_string('timeleft', 'learningtimecheck');

    // for pdf output
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $progresspdfstr = get_string('progressbarpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $doneitemspdfstr = get_string('doneitemspdf', 'report_learningtimecheck');
    $donetimepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $leftratiopdfstr = get_string('ratioleftpdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();
    $table->head = array($idnumberstr, $fullnamestr, $progressstr, $itemstodostr, $doneitemsstr, $donetimestr, $leftratiostr, $timeleftstr);
    $table->headcodes = array('idnumber', 'fullname', 'progress', 'itemstodo', 'doneitems', 'donetime', 'leftratio', 'timeleft');
    $table->size = array('10%', '20%', '20%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', 'highlighted', '', '');
    $table->noprint = array('', '', '', '', '', '', '', '');
    $table->pdfprintctl = array('final', 'final', 'raw', 'final', 'final', 'final', 'final', 'final');

    $table->pdfhead1 = array('', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '10%', '20%', '30%', '30%');
    $table->pdfalign1 = array('L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');

    $table->pdfhead2 = array($idnumberpdfstr, $fullnamestr, $progresspdfstr, $itemstodopdfstr, $doneitemspdfstr, $donetimepdfstr, $leftratiopdfstr, $timeleftpdfstr);
    $table->size2 = array('10%', '30%', '10%', '10%', '10%', '10%', '10%', '10%');
    $table->align2 = array('L', 'L', 'L', 'C', 'C', 'C', 'C', 'L');

    $table->rawdata = array();

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;

    $countusers = count($cohortmembers);

    $table->data = array();

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $i = 0;
    foreach ($cohortmembers as $u) {
        $allchecks = learningtimecheck_get_my_checks($u->id, 'flat');
        $reportlines = array();

        $data = array();
        $data[] = $u->idnumber;
        $data[] = '<a href="'.$thisurl.'?id='.$id.'&view=user&userid='.$u->id.'">'.fullname($u).'</a>';
        $useraggregate = array('totalitems' => 0, 'totaltime' => 0, 'tickeditems' => 0, 'tickedtimes' => 0);
        foreach ($allchecks as $check) {
            // care that all checklists should manage a disjonctive set of items
            list($totalitems, $totaltime, $tickeditems, $tickedtimes) = $check->get_items_for_user($u->id, $reportsettings);
            $useraggregate['totalitems'] += $totalitems;
            $useraggregate['totaltime'] += $totaltime;
            $useraggregate['tickeditems'] += $tickeditems;
            $useraggregate['tickedtimes'] += $tickedtimes;
        }

        if ($useraggregate['totalitems']) {
            $percentcomplete = ($useraggregate['totalitems']) ? ($useraggregate['tickeditems'] * 100) / $useraggregate['totalitems'] : 0 ;
        } else {
            $percentcomplete = 0;
            $courseaggregate['tickeditems'] = 0;
        }

        $timeleft = $useraggregate['totaltime'] - $useraggregate['tickedtimes'];
        $leftratio = 100 - floor($percentcomplete);

        $sumcomplete += $percentcomplete;
        $sumitems += $useraggregate['totalitems'];
        $sumticked += $useraggregate['tickeditems'];
        $sumtimetodo += $useraggregate['totaltime'];
        $sumtickedtime += $useraggregate['tickedtimes'];
        $sumtimeleft += $timeleft;

        $data[] = mod_learningtimecheck_renderer::progressbar_thin($percentcomplete);
        $data[] = $useraggregate['totalitems'];
        $data[] = $useraggregate['tickeditems'];
        $data[] = learningtimecheck_format_time($useraggregate['tickedtimes']);
        $data[] = sprintf('%0d', $leftratio).' %';
        $data[] = learningtimecheck_format_time($timeleft);

        // prepare raw data for export
        $rawdata = $data;
        $rawdata[2] = sprintf('%0.2f', $percentcomplete).' %'; // change for row data 
        $rawdata[5] = $useraggregate['tickedtimes']; // change for row data for further export conversion
        $rawdata[7] = $timeleft; // change for row data for further export conversion
        $table->rawdata[] = $rawdata;

        $table->data[] = $data;
    }

    // make last row with average and sums.

    $row1 = new html_table_row();

    $cell1 = new html_table_cell();
    $cell1->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
    $cell1->colspan = 2;
    $cell1->align = 'right';
    $row1->cells[] = $cell1;

    $cell2 = new html_table_cell();

    if ($countusers) {
        $cell2->text = sprintf('%0.1f', $sumcomplete / $countusers).' % '.get_string('average', 'learningtimecheck');
    } else {
        $cell2->text = sprintf('%0d', 0).' % '.get_string('average', 'learningtimecheck');
    }
    $row1->cells[] = $cell2;

    $cell3 = new html_table_cell();
    $cell3->text = '<span class="totalizer">'.$sumitems.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell3;

    $cell4 = new html_table_cell();
    $cell4->text = '<span class="totalizer">'.$sumticked.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell4;

    $cell5 = new html_table_cell();
    $cell5->text = '<span class="totalizer">'.$sumtickedtime.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $cell5->attributes['class'] = 'learningtimecheck-result';
    $row1->cells[] = $cell5;

    $cell6 = new html_table_cell();
    $remains = ($sumitems) ? ($sumitems - $sumticked) / $sumitems * 100 : 0 ;
    $cell6->text = ($sumitems) ? sprintf('%0d', round($remains)).' %' : 0 ;
    $cell6->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell6;

    $cell7 = new html_table_cell();
    $cell7->text = learningtimecheck_format_time($sumtimeleft).' '.get_string('totalized', 'learningtimecheck');
    $cell7->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell7;

    $table->data[] = $row1;
    return $table;
}

/**
 * compile all results in a table object, that will be rendered
 * on line or dumped onto a report file format
 *
 * @param int $id
 */
function report_learningtimecheck_course_results($id, $courseusers, $courseid, &$globals) {
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
    $doneitemsstr = get_string('doneitems', 'learningtimecheck');
    $donetimestr = get_string('timedone', 'learningtimecheck');
    $doneratiostr = get_string('doneratio', 'report_learningtimecheck');
    $timeleftstr = get_string('timeleft', 'learningtimecheck');

    // for pdf output
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $progresspdfstr = get_string('progressbarpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $doneitemspdfstr = get_string('doneitemspdf', 'report_learningtimecheck');
    $donetimepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $doneratiopdfstr = get_string('doneratiopdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();
    $table->head = array($idnumberstr, $fullnamestr, $groupsstr, $progressstr, $itemstodostr, $doneitemsstr, $donetimestr, $doneratiostr, $timeleftstr);
    $table->pdfhead1 = array('', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '10%', '20%', '30%', '30%');
    $table->pdfalign1 = array('L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');
    $table->pdfhead2 = array($idnumberpdfstr, $fullnamestr, $groupsstr, $progresspdfstr, $itemstodopdfstr, $doneitemspdfstr, $donetimepdfstr, $doneratiopdfstr, $timeleftpdfstr);
    $table->size2 = array('10%', '20%', '10%', '10%', '10%', '10%', '10%', '10%', '10%');
    $table->pdfbgcolor3 = array('#000000', '#000000', '#000000', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000', '#000000', '#000000', '#000000');
    $table->headcodes = array('shortname', 'fullname', 'groups', 'progress', 'itemstodo', 'doneitems', 'donetime', 'doneratio', 'timeleft');
    $table->pdfprintctl = array('final', 'final', 'final', 'raw', 'final', 'final', 'final', 'final', 'final', 'final', 'final');
    $table->noprint = array('', '', '', '', '', '', '', '', '');
    $table->size = array('10%', '10%', '10%', '20%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', '', 'highlighted', '', '');
    $table->rawdata = array();

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;

    $countusers = count($courseusers);

    $table->data = array();

    foreach ($courseusers as $u) {

        $globals['allusers'] = @$globals['allusers'] + 1;

        // Remove teachers and managers from reports.
        $coursecontext = context_course::instance($courseid);
        if (has_capability('report/learningtimecheck:viewother', $coursecontext, $u->id)) {
            continue;
        }

        // Returns a course arranged array of checks.
        $allchecks = learningtimecheck_get_my_checks($u->id, 'flat', $courseid);
        $reportlines = array();

        $usergroups = groups_get_user_groups($courseid, $u->id);
        $gnames = array();
        $gids = array();

        if (!empty($usergroups)) {
            foreach ($usergroups as $groupinggroups) {
                foreach ($groupinggroups as $g) { // unduple groups
                    if (!in_array($g, $gids)) {
                        $gnames[] = $DB->get_field('groups', 'name', array('id' => $g));
                        $gids[] = $g;
                    }
                }
            }
        }
        $groupnames = implode(',', $gnames);
        unset($gids); /// free unused mem

        $data = array();
        $data[] = $u->idnumber;
        $data[] = '<a href="'.$thisurl.'?id='.$id.'&view=user&userid='.$u->id.'">'.fullname($u).'</a>';
        $useraggregate = array('totalitems' => 0, 'totaltime' => 0, 'tickeditems' => 0, 'tickedtimes' => 0);

        foreach ($allchecks as $check) {
            // Care that all checklists should manage a disjonctive set of items. Dpo not consider optional items in reports
            $results = $check->get_items_for_user($u->id);
            $useraggregate['totalitems'] += $results['mandatory']['items'];
            $useraggregate['totaltime'] += $results['mandatory']['time'];
            $useraggregate['tickeditems'] += $results['mandatory']['ticked'];
            $useraggregate['tickedtimes'] += $results['mandatory']['tickedtime'];
        }

        if ($useraggregate['totalitems']) {
            $percentcomplete = ($useraggregate['totalitems']) ? ($useraggregate['tickeditems'] * 100) / $useraggregate['totalitems'] : 0 ;
            if ($percentcomplete > 90) {
                $globals['fullusers']  = @$globals['fullusers'] + 1;
            }
            if ($percentcomplete > 50) {
                $globals['halfusers']  = @$globals['halfusers'] + 1;
            }
            if ($percentcomplete > 0) {
                $globals['activeusers']  = @$globals['activeusers'] + 1;
            }
        } else {
            $percentcomplete = 0;
            $courseaggregate['tickeditems'] = 0;
            $globals['nullusers'] = @$globals['nullusers'] + 1;
        }

        $timeleft = $useraggregate['totaltime'] - $useraggregate['tickedtimes'];
        $leftratio = 100 - floor($percentcomplete);
        $timedoneratio = ($useraggregate['totaltime']) ? floor(100 * (($useraggregate['tickedtimes'] /$useraggregate['totaltime']))) : 0;

        // summarizes over all users
        $sumcomplete += $percentcomplete;
        $sumitems += $useraggregate['totalitems'];
        $sumticked += $useraggregate['tickeditems'];
        $sumtimetodo += $useraggregate['totaltime'];
        $sumtickedtime += $useraggregate['tickedtimes'];
        $sumtimeleft += $timeleft;

        $data[] = $groupnames;
        $data[] = mod_learningtimecheck_renderer::progressbar_thin($percentcomplete);
        $data[] = $useraggregate['totalitems'];
        $data[] = $useraggregate['tickeditems'];
        $data[] = learningtimecheck_format_time($useraggregate['tickedtimes']);
        $data[] = sprintf('%0d', $timedoneratio).' %';
        $data[] = learningtimecheck_format_time($timeleft);

        // Prepare row data for export.
        $rawdata = $data;
        $rawdata[3] = sprintf('%0.2f', $percentcomplete).' %'; // change for row data 
        $rawdata[6] = $useraggregate['tickedtimes']; // change for row data for further export conversion
        $rawdata[8] = $timeleft; // change for row data for further export conversion
        $table->rawdata[] = $rawdata;

        $table->data[] = $data;
    }

    // Achievement is in fifth col (tickeditems);
    function sortbyachievementdesc($a, $b) {
        if ($a[5] > $b[5]) return -1;
        if ($a[5] < $b[5]) return 1;
        return 0;
    }

    usort($table->data, 'sortbyachievementdesc');
    usort($table->rawdata, 'sortbyachievementdesc');

    // Make last row with average and sums.

    $row1 = new html_table_row();

    $cell1 = new html_table_cell();
    $cell1->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
    $cell1->colspan = 3;
    $cell1->align = 'right';
    $row1->cells[] = $cell1;

    $cell2 = new html_table_cell();
    if ($countusers) {
        $cell2->text = sprintf('%0.1f', $sumcomplete / $countusers).' % '.get_string('average', 'learningtimecheck');
    } else {
        $cell2->text = sprintf('%0d', 0).' % '.get_string('average', 'learningtimecheck');
    }
    $row1->cells[] = $cell2;

    $cell3 = new html_table_cell();
    $cell3->text = '<span class="totalizer">'.$sumitems.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell3;

    $cell4 = new html_table_cell();
    $cell4->text = '<span class="totalizer">'.$sumticked.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell4;

    $cell5 = new html_table_cell();
    $cell5->text = '<span class="totalizer">'.$sumtickedtime.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $cell5->attributes['class'] = 'learningtimecheck-result';
    $row1->cells[] = $cell5;

    $cell6 = new html_table_cell();
    $remains = ($sumitems) ? (($sumitems - $sumticked) / $sumitems * 100) : 0 ;
    $cell6->text = sprintf('%0d', round($remains)).' %';
    $cell6->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell6;

    $cell7 = new html_table_cell();
    $cell7->text = learningtimecheck_format_time($sumtimeleft).' '.get_string('totalized', 'learningtimecheck');
    $cell7->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell7;

    $table->data[] = $row1;

    return $table;
}

function report_learningtimecheck_user_course_results($courseid, $user) {
    global $CFG, $DB, $OUTPUT;

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    // for pdf output
    $idnumberpdfstr = get_string('idnumberpdf', 'report_learningtimecheck');
    $itemnamestr = get_string('itemnamepdf', 'report_learningtimecheck');
    $itemtimecreditpdfstr = get_string('itemtimecreditpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $doneitemspdfstr = get_string('doneitemspdf', 'report_learningtimecheck');
    $donetimepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $doneratiopdfstr = get_string('doneratiopdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');
    $validatedbypdfstr = get_string('validatedbypdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();
    $table->pdfhead2 = array($idnumberpdfstr, $itemnamestr, $itemtimecreditpdfstr, $doneitemspdfstr, $donetimepdfstr, $validatedbypdfstr);
    $table->size2 = array('10%', '40%', '10%', '10%', '10%', '10%', '10%');
    $table->pdfbgcolor3 = array('#000000', '#000000', '#f0f0f0', '#f0f0f0', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor3 = array('#ffffff', '#ffffff', '#000000', '#000000', '#000000', '#000000');
    $table->headcodes = array('idnumber', 'name', 'itemstodo', 'doneitems', 'donetime', 'doneratio', 'timeleft');
    $table->pdfprintctl = array('final', 'final', 'final', 'raw', 'final', 'final', 'final', 'final', 'final', 'final', 'final');
    $table->noprint = array('', '', '', '', '', '', '', '', '');
    $table->size = array('10%', '10%', '10%', '20%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', '', 'highlighted', '', '');
    $table->rawdata = array();

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;

    $table->globals = new StdClass;
    $table->globals->courseearneditems = 0;
    $table->globals->courseearnedtime = 0;
    $table->globals->totalcourseitems = 0;
    $table->globals->totalcoursetime = 0;

    $tclmodule = $DB->get_record('modules', array('name' => 'learningtimecheck'));
    $timechecklistmodules = $DB->get_records('course_modules', array('course' => $courseid, 'module' => $tclmodule->id), 'section', '*');
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
            }
            if ($checks = $clobj->get_checks($user->id)) {
                foreach ($checks as $ck => $check) {

                    // Check module can be elected.
                    if (!empty($check->moduleid)) {
                        $cm = $DB->get_field('course_modules', 'idnumber', array('id' => $check->moduleid));

                        if (!groups_course_module_visible($cm, $user->id)) {
                            continue;
                        }
                        $idnumber = $DB->get_field('course_modules', 'idnumber', array('id' => $check->moduleid));
                    } else {
                        $idnumber = '';
                    }

                    // TODO : might we use $mods to be a bit faster ? 
                    $row = array();
                    $row[] = $idnumber;
                    $row[] = $check->displaytext;
                    $row[] = 0 + $check->credittime;

                    $marked = report_learningtimecheck_is_marked($clobj, $check);
                    if ($marked > 0) {
                        // Has been marked either by student or teacher.
                        $pixurl = $OUTPUT->pix_url('good');
                    } else {
                        $pixurl = $OUTPUT->pix_url('good');
                    }
                    $row[] = '<img src="'.$pixurl.'" />';
                    $table->globals->totalcourseitems++;
                    $table->globals->totalcoursetime = $check->credittime;
                    if ($marked > 1) {
                        // Has been marked by a teacher or a student able to self validate.
                        $teacher = $DB->get_record('user', array('id' => $check->teacherid));
                        $row[] = fullname($teacher);
                        $row[] = $check->credittime;
                        $table->globals->courseearneditems++;
                        $table->globals->courseearnedtime = $check->credittime;
                    } elseif ($marked > 0) {
                        $row[] = '';
                        $row[] = get_string('selfmarked', 'report_learningtimecheck');
                    }
                    $table->data[] = $row;
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
        $cell2->text = '';
        $row2->cells[] = $cell2;

        $cell3 = new html_table_cell();
        $cell3->text = '<span class="totalizer">'.$table->globals->courseearneditems.' '.get_string('totalized', 'learningtimecheck').'</span>';
        $row2->cells[] = $cell3;

        $cell4 = new html_table_cell();
        $cell4->text = '<span class="totalizer">'.learningtimecheck_format_time(0 + $table->globals->courseearnedtime).' '.get_string('totalized', 'learningtimecheck').'</span>';
        $cell4->attributes['class'] = 'learningtimecheck-result';
        $row2->cells[] = $cell4;

        $cell5 = new html_table_cell();
        $cell5->text = '';
        $row2->cells[] = $cell5;

        $table->data[] = $row2;
        $table->globals->courseprogressratio = sprintf('%0.2f', $table->globals->courseearneditems / $table->globals->totalcourseitems). ' %';
    } else {
        $table->data = array();
        $table->globals->courseprogressratio = '0 %';
    }

    return $table;
}

/**
 * compile all results in a table object, that will be rendered
 * on line or dumped onto a report file format.
 * Gets all results of a single user 
 * @param int $id
 * @param int $userid
 */
function report_learningtimecheck_user_results_by_course($id, $user) {
    global $CFG, $DB, $OUTPUT;

    $thisurl = new moodle_url('/report/learningtimecheck/index.php');

    // Returns a course arranged array of checks.
    $allchecks = learningtimecheck_get_my_checks($user->id, 'bycourses');

    $reportlines = array();

    $sumcomplete = 0;
    $sumitems = 0;
    $sumticked = 0;
    $sumtimetodo = 0;
    $sumtickedtime = 0;
    $sumtimeleft = 0;

    $shortnamestr = get_string('shortname');
    $fullnamestr = get_string('fullname');
    $progressstr = get_string('progressbar', 'learningtimecheck');
    $itemstodostr = get_string('itemstodo', 'learningtimecheck');
    $doneitemsstr = get_string('doneitems', 'learningtimecheck');
    $donetimestr = get_string('timedone', 'learningtimecheck');
    $ratioleftstr = get_string('ratioleft', 'learningtimecheck');
    $timeleftstr = get_string('timeleft', 'learningtimecheck');

    // For pdf output.
    $shortnamepdfstr = get_string('shortnamepdf', 'report_learningtimecheck');
    $progresspdfstr = get_string('progressbarpdf', 'report_learningtimecheck');
    $itemstodopdfstr = get_string('itemstodopdf', 'report_learningtimecheck');
    $doneitemspdfstr = get_string('doneitemspdf', 'report_learningtimecheck');
    $donetimepdfstr = get_string('timedonepdf', 'report_learningtimecheck');
    $ratioleftpdfstr = get_string('ratioleftpdf', 'report_learningtimecheck');
    $timeleftpdfstr = get_string('timeleftpdf', 'report_learningtimecheck');

    $itemspdfstr = get_string('itemspdf', 'report_learningtimecheck');
    $timepdfstr = get_string('timepdf', 'report_learningtimecheck');

    $table = new html_table();
    $table->head = array($shortnamestr, $fullnamestr, $progressstr, $itemstodostr, $doneitemsstr, $ratioleftstr, $donetimestr, $timeleftstr);
    $table->pdfhead1 = array('', '', '', $itemspdfstr, $timepdfstr);
    $table->pdfsize1 = array('10%', '10%', '20%', '30%', '30%');
    $table->pdfalign1 = array('L', 'L', 'L', 'C', 'C');
    $table->pdfbgcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#f0f0f0', '#f0f0f0');
    $table->pdfcolor1 = array('#ffffff', '#ffffff', '#ffffff', '#000000', '#000000');
    $table->pdfhead2 = array($shortnamepdfstr, $fullnamestr, $progresspdfstr, $itemstodopdfstr, $doneitemspdfstr, $ratioleftpdfstr, $donetimepdfstr, $timeleftpdfstr);
    $table->size2 = array('10%', '30%', '10%', '10%', '10%', '10%', '10%', '10%');
    $table->headcodes = array('shortname', 'fullname', 'progress', 'itemstodo', 'doneitems', 'donetime', 'leftratio', 'timeleft');
    $table->pdfprintctl = array('final', 'final', 'raw', 'final', 'final', 'final', 'final', 'final');
    $table->noprint = array('', '', '', '', '', '', '', '');
    $table->size = array('20%', '30%', '10%', '10%', '10%', '10%', '10%');
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'left');
    $table->colclasses = array('', '', '', '', '', 'highlighted', '');
    $table->rawdata = array();

    $reportsettings = new StdClass;
    $reportsettings->showoptional = false;

    $countcourses = count($allchecks);

    $table->data = array();

    foreach ($allchecks as $courseid => $coursechecks) {

        $course = $DB->get_record('course', array('id' => $courseid));

        $data = array();

        $reporturl = clone($thisurl);
        $reporturl->params(array('id' => $id, 'view' => 'course', 'itemid' => $courseid));
        $courselink = '<a href="'.$reporturl.'">'.$course->shortname.'</a>';
        $data[] = $courselink;

        // $data[] = $course->idnumber;
        $coursename = $course->fullname;
        $coursecontext = context_course::instance($courseid);

        if (has_capability('moodle/course:view', $coursecontext)) {
            $courseurl = new moodle_url('/course/view', array('id' => $courseid));
            $coursename .= ' <a href="'.$courseurl.'"><img src="'.$OUTPUT->pix_url('follow_link', 'report_learningtimecheck').'" /></a>';
        }

        $data[] = $coursename;

        // care that all checklists should manage a disjonctive set of items
        $courseaggregate = array('totalitems' => 0, 'totaltime' => 0, 'tickeditems' => 0, 'tickedtimes' => 0);
        foreach ($coursechecks as $check) {
            $itemstates = $check->get_items_for_user($user->id, $reportsettings);
            $userdata = $itemstates['mandatory'];
            $courseaggregate['totalitems'] += $userdata['items'];
            $courseaggregate['totaltime'] += $userdata['time'];
            $courseaggregate['tickeditems'] += $userdata['ticked'];
            $courseaggregate['tickedtimes'] += $userdata['tickedtime'];
        }

        if ($courseaggregate['totalitems']) {
            $percentcomplete = ($courseaggregate['tickeditems'] * 100) / $courseaggregate['totalitems'];
        } else {
            $percentcomplete = 0;
            $courseaggregate['tickeditems'] = 0;
        }

        $timeleft = $courseaggregate['totaltime'] - $courseaggregate['tickedtimes'];
        $leftratio = 100 - floor($percentcomplete);

        $sumcomplete += $percentcomplete;
        $sumitems += $courseaggregate['totalitems'];
        $sumticked += $courseaggregate['tickeditems'];
        $sumtimetodo += $courseaggregate['totaltime'];
        $sumtickedtime += $courseaggregate['tickedtimes'];
        $sumtimeleft += $timeleft;

        $data[] = mod_learningtimecheck_renderer::progressbar_thin($percentcomplete);
        $data[] = $courseaggregate['totalitems'];
        $data[] = $courseaggregate['tickeditems'];
        $data[] = sprintf('%0d', $leftratio).' %';
        $data[] = learningtimecheck_format_time($courseaggregate['tickedtimes']);
        $data[] = learningtimecheck_format_time($timeleft);

        // Prepare raw data for export.
        $rawdata = $data;
        $rawdata[2] = sprintf('%0.2f', $percentcomplete).' %'; // change for row data 
        $rawdata[5] = $courseaggregate['tickedtimes']; // change for row data for further export conversion
        $rawdata[7] = $timeleft; // change for row data for further export conversion
        $table->rawdata[] = $rawdata;

        $table->data[] = $data;
    }

    // Make last row with average and sums.

    $row1 = new html_table_row();

    $cell1 = new html_table_cell();
    $cell1->text = '<b>'.get_string('summators', 'learningtimecheck').'</b>';
    $cell1->colspan = 2;
    $cell1->align = 'right';
    $row1->cells[] = $cell1;

    $cell2 = new html_table_cell();
    if ($countcourses) {
        $cell2->text = sprintf('%0.1f', $sumcomplete / $countcourses).' % '.get_string('average', 'learningtimecheck');
    } else {
        $cell2->text = sprintf('%0d', 0).' % '.get_string('average', 'learningtimecheck');
    }
    $row1->cells[] = $cell2;

    $cell3 = new html_table_cell();
    $cell3->text = '<span class="totalizer">'.$sumitems.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell3;

    $cell4 = new html_table_cell();
    $cell4->text = '<span class="totalizer">'.$sumticked.' '.get_string('totalized', 'learningtimecheck').'</span>';
    $row1->cells[] = $cell4;

    $cell5 = new html_table_cell();
    $remains = ($sumitems) ? ($sumitems - $sumticked) / $sumitems * 100 : 0 ;
    $cell5->text = sprintf('%0d', round($remains)).' % '.get_string('average', 'learningtimecheck');
    $cell5->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell5;

    $cell6 = new html_table_cell();
    $cell6->text = '<span class="totalizer">'.learningtimecheck_format_time($sumtickedtime).' '.get_string('totalized', 'learningtimecheck').'</span>';
    $cell6->attributes['class'] = 'learningtimecheck-result';
    $row1->cells[] = $cell6;

    $cell7 = new html_table_cell();
    $cell7->text = learningtimecheck_format_time($sumtimeleft).' '.get_string('totalized', 'learningtimecheck');
    $cell7->attributes['class'] = 'learningtimecheck-remain-result';
    $row1->cells[] = $cell7;

    $table->data[] = $row1;
    return $table;
}

/**
 * This is the main cron function of report for executing batchs
 * This cron task will launch worker independant queries to handle some reports
 * builds in parallel. This is mainly intended to feed a nightly high processing
 * availability of your computing resources. Daytime compilations should be done
 * as less as possible, or using interactive, unity report build.
 */
function report_learningtimecheck_cron() {
    global $DB;

    mtrace("Starting trainingsession cron.");

    if (!$tasks = $DB->get_records('report_learningtimecheck_btc', array('processed' => 0))){
        mtrace('empty task stack...');
        return;
    }

    $i = 0;
    $jb = 0;
    mtrace("\tStarting generating jobgroup ".($i+1));
    foreach ($tasks as $tid => $task) {
        if (time() < $task->runtime) {
            mtrace("\t\t $tid not yet.");
            continue;
        } else {
            $jobgroups[$i][] = $tid;
            $i++;
            if ($i == REPORT_LEARNINGTIMECHECK_MAX_WORKERS) {
                $i = 0;
            }
        }
    }

    if (!empty($jobgroups)) {
        foreach ($jobgroups as $jobgroup) {

            /*
             * This will parallelize processing. Usually batchs are deferred to nightly processing
             * so we could spend all the multicore power by parallelizing.
             * The security key avoids weird use of the workers by anyone
             */
            $rq = 'joblist='.implode(',', $jobgroup).'&securekey='.urlencode(md5($SITE->name.$CFG->passwordsaltmain));

            // Launch tasks by firing CURL shooting.
            $uri = $CFG->wwwroot.'/report/learningtimecheck/batch_worker.php';

            $ch = curl_init($uri.'?'.$rq);
            debug_trace("Firing curl : {$uri}?{$rq}\n");

            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle Report Batch');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rq);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $raw = curl_exec($ch);

            // Check for curl errors.
            $curlerrno = curl_errno($ch);
            if ($curlerrno != 0) {
                mtrace("Request for $uri failed with curl error $curlerrno");
            }

            // Check HTTP error code.
            $info =  curl_getinfo($ch);
            if (!empty($info['http_code']) and ($info['http_code'] != 200)) {
                mtrace("Request for $uri failed with HTTP code ".$info['http_code']);
            } else {
                mtrace('Success');
            }
            curl_close($ch);
        }

        if ($task->repeatdelay) {
            // replaydelay in seconds
            $DB->set_field('report_learningtimecheck_btc', 'runtime', $task->runtime + $task->repeatdelay * 60, array('id' => $task->id));
            mtrace('Bouncing task '.$task->id.' to '.userdate($task->runtime));
        } else {
            $DB->set_field('report_learningtimecheck_btc', 'processed', time(), array('id' => $task->id));
        }
    }

    mtrace("\tdone.");
}

/**
 * TODO : study scalability for many users, courses, etc
 *
 */
function report_learningtimecheck_create_form_elements($mform, $type) {
    global $DB;

    switch($type) {
        case 'user':
            $useroptions = $DB->get_records_menu('user', array('deleted' => 0), 'id, CONCAT(lastname, \' \', firstname)', 'id, lastname, firstname');
            $mform->addElement('select', 'itemid', get_string('user'), $useroptions);
            break;

        case 'cohort':
            $contextsystem = context_system::instance();
            $cohortoptions = $DB->get_records_menu('cohort', array('contextid' => $contextsystem->id), 'id, name', 'name');
            $mform->addElement('select', 'itemid', get_string('user'), $useroptions);
            break;

        case 'course':
            $courseoptions = $DB->get_records_menu('course', array(), 'id, fullname', 'sortorder');
            $mform->addElement('select', 'itemid', get_string('course'), $courseoptions);
            break;        
    }
}

function report_learningtimecheck_get_user_options() {
    global $DB, $USER;
    
    if (!$optionrecs = $DB->get_records('report_learningtimecheck_opt', array('userid' => $USER->id))){
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
 * @param obj $checklist
 * @param object $check
 * @return an int status in (-1: refused, 0: undefined, 1:checked not validated, 2:checked validated)
 */
function report_learningtimecheck_is_marked($checklist, $check){

    if ($checklist->learningtimecheck->teacheredit == LEARNINGTIMECHECK_MARKING_TEACHER
         || $checklist->learningtimecheck->teacheredit == LEARNINGTIMECHECK_MARKING_BOTH){
        if ($check->teachermark == LEARNINGTIMECHECK_TEACHERMARK_YES){
            return 2;
        }
        if ($check->teachermark == LEARNINGTIMECHECK_TEACHERMARK_NO){
            return -1;
        }
        if (!empty($check->usertimestamp)) {
            // Student marked, but not allowed to self validate (not a validaton mark).
            return 1;
        }
        return 0;
    }

    if ($checklist->learningtimecheck->teacheredit == LEARNINGTIMECHECK_MARKING_STUDENT) {
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

    // First check you are sharing a course
    $courses = enrol_get_users_courses($user->id, true, 'id,shortname', 'sortorder');
    foreach ($courses as $cid => $foo) {
        $ctx = context_course::instance($cid);
        if (has_capability('report/learningtimecheck:viewother', $ctx)) {
            $cansee = true;
            break;
        }
    }

    if (!$cansee) return false;

    // Now check groups
    if ($viewallgroups) {
        // definitely can see
        return true;
    }
    
    $cangroupsee = false;

    foreach ($mygroups as $g) {
        // only one is enough
        if (groups_is_member($g->id, $user->id)) {
            return true;
        }
    }

    return $cangroupsee;
}

function report_learningtimecheck_groupings_print_menu($course, $url, $view = 'course') {
    $str = '';

    $groupings = groups_get_all_groupings($course->id);

    if (!$groupings) return '';

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
 *
 *
 */
function report_learningtimecheck_get_users($courseid, $groupid = 0, $groupingid = 0) {
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

    $users = $DB->get_records_sql($sql, $sqlvars);

    return $users;
}

/**
 * Checks all access policeies against required group and user situation
 * In separated groups mode, you can only view your groups
 */
function check_group_authorisation($courseid, $groupid = 0, $groupingid = 0) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid));
    $coursecontext = context_course::instance($courseid);

    $accessallgroups = has_capability('moodle/site:accessallgroups', $coursecontext);
    $mygroups = groups_get_user_groups($courseid);
    $groupmode = groups_get_course_groupmode($course);

    if ($groupmode == SEPARATEGROUPS) {
        if ($accessallgroups) return true;

        if ($groupingid) {
            // check if one of my groups matches the grouping.
            foreach ($mygroups as $gm) {
                $groupgroupingid = $DB->get_field('groupings_groups', 'groupingid', array('groupid' => $gm->groupid));
                if ($groupingid == $groupgroupingid) return true;
            }
        }

        if ($groupid) {
            foreach ($mygroups as $gm) {
                if ($gm->groupid == $groupid) return true;
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

    if (!$job->detail) {
        // Global reports print a single document with summarize lines
        switch ($job->type) {
            case 'userdetail':
                // Global user document prints a single document with course by course summary.
                $fooid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemid));
                $data = report_learningtimecheck_user_course_results($job->courseid, $user);
                break;

            case 'user':
                // Global user document prints a single document with course by course summary.
                $fooid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemid));
                $data = report_learningtimecheck_user_results_by_course($fooid, $user);
                break;

            case 'course':
                // Global course document prints a single document with user by user summary
                $course = $DB->get_record('course', array('id' => $job->itemid));
                $coursecontext = context_course::instance($course->id);
                $courseusers = get_enrolled_users($coursecontext);
                learningtimecheck_apply_rules($courseusers, $job->filters);
                $data = report_learningtimecheck_course_results($id, $courseusers, $course->id, $globals);
                break;

            case 'cohort':
                // Global cohort document prints a single document with user by user summary
                $cohort = $DB->get_record('cohort', array('id' => $job->itemid));
                $cohortmembers = report_learningtimecheck_get_cohort_users($cohort->id);
                learningtimecheck_apply_rules($cohortmembers, $job->filters);
                $data = report_learningtimecheck_cohort_results($id, $cohortmembers);
                break;
        }
    } else {
        // Detail reports will prepare a composite array of report data for further outputing
        switch ($job->type) {
            case 'user' :
                // Detail user document prints a document per course with activity detail, then zips the document list.
                $notusedid = 0;
                $user = $DB->get_record('user', array('id' => $job->itemid));
                $allchecks = learningtimecheck_get_my_checks($user->id, 'bycourses');
                foreach ($allchecks as $courseid => $coursechecksfoo) {
                    $reportdata = new StdClass();
                    $reportdata->type = 'userdetail';
                    $reportdata->itemid = $job->itemid;
                    $reportdata->jobcontext = $DB->get_record('course', array('id' => $courseid));
                    $reportdata->data = report_learningtimecheck_user_course_results($notusedid, $user);
                    $data[$courseid] = $reportdata;
                }
                break;

            case 'course':
                // Detail course document prints a document per user with activity detail in the course, then zips the document list.
                $notusedid = 0;

                $course = $DB->get_record('course', array('id' => $exportitem));
                $coursecontext = context_course::instance($course->id);
                $courseusers = get_enrolled_users($coursecontext);
                learningtimecheck_apply_rules($courseusers, $job->filters);
                foreach ($courseusers as $uid => $u) {
                    $reportdata = new StdClass();
                    $reportdata->type = 'userdetail';
                    $reportdata->itemid = $uid;
                    $reportdata->jobcontext = $course;
                    $reportdata->data = report_learningtimecheck_user_course_results($notusedid, $u);
                    $data[$userid] = $reportdata;
                }
                break;

            case 'cohort':
                // Detail cohort document prints a document per user with user detail, then zips the document list.
                $notusedid = 0;

                $cohortusers = $DB->get_records('cohort_members', array('cohortid' => $job->itemid));
                learningtimecheck_apply_rules($cohortusers, $job->filters);
                if (!empty($cohortusers)) {
                    foreach ($cohortusers as $uid => $u) {
                        $reportdata = new StdClass();
                        // Global user document prints a single document with course by course summary.
                        $user = $DB->get_record('user', array('id' => $u->userid));
                        $reportdata->itemid = $uid;
                        $reportdata->type = 'user';
                        $reportdata->jobcontext = $DB->get_record('cohort', array('id' => $job->itemid));
                        $reportdata->data = report_learningtimecheck_user_results_by_course($notusedid, $u);
                        $data[$userid] = $reportdata;
                    }
                }
                break;
        }
    }
}