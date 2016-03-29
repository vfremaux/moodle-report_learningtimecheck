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
 * Course learningtimecheck course report view
 *
 * Assembles all learningtime checks in the given course for a course level report
 *
 * @package    report
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) die('You cannot use this script this way');

require_once($CFG->dirroot.'/report/learningtimecheck/forms/search_course_form.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

$id = optional_param('id', null, PARAM_INT); // Course ID (origin context)
$itemid = optional_param('itemid', $id, PARAM_INT); // Display context
$searchmode = optional_param('search', false, PARAM_BOOL);
$systemcontext = context_system::instance();
$coursecontext = context_course::instance($itemid);
$groupid = optional_param('groupid', optional_param('group', 0, PARAM_INT), PARAM_INT);
$groupingid = optional_param('groupingid', 0, PARAM_INT);

$thisurl->params(array('itemid' => $itemid));

// Do not even try if you can only see your stuff.
if (!has_capability('report/learningtimecheck:viewother', $coursecontext)) {
    print_error('You have no system wide permission to view other results');
}

if ($searchmode) {

    // Precheck categoryid from direct params.
    $categoryid = optional_param('category', 0, PARAM_INT);
    if ($categoryid) {
        $selectedcategory = $DB->get_record('course_categories', array('id' => $categoryid));
    } else {
        $selectedcategory = null;
    }

    $categories = $DB->get_records('course_categories', array('parent' => $categoryid), 'sortorder', 'id, name, visible');
    $catmenu = array();
    foreach ($categories as $catid => $category) {
        $catcontext = context_coursecat::instance($catid);
        if (!$category->visible && !has_capability('moodle/category:viewhiddencategories', $catcontext)) {
            unset($categories[$catid]);
        } else {
            $catmenu[$catid] = $category->name;
        }
    }

    $form = new SearchCourseForm($thisurl, array('current' => $selectedcategory, 'categories' => $catmenu));

    if ($data = $form->get_data()) {
        if (!empty($data->searchpattern)) {
            $select = " fullname LIKE '%{$data->searchpattern}%' ";
            $results = $DB->get_records_select('course', $select, array(), 'sortorder', 'id,shortname,idnumber,fullname,summary');
            if ($results){
                $mycourses = get_my_courses();
                $mycourseids = array_keys($mycourses);
                foreach ($results as $resid => $result) {
                    if (!in_array($resid, $results) && !has_capability('moodle/site:config', $systemcontext)) {
                        unset($results[$resid]);
                    }
                }
            }

            $categories = null;
        } else {
            if ($categoryid) {
                $results = $DB->get_records('course', array('category' => $categoryid), 'sortorder', 'id,shortname,idnumber,fullname,summary');
            }
        }
    }

    $formdata = new Stdclass;
    $formdata->id = $id;
    $form->set_data($formdata);
    $form->display();

    echo $OUTPUT->heading(get_string('results', 'report_learningtimecheck'));

    if (!empty($results)) {

        $table = new html_table();
        $table->head = array('', '', '');
        $table->size = array('10%', '10%', '80%');
        $table->width = '100%';

        foreach ($results as $cid => $course) {
            $row = array();
            $row[] = '<a href="'.$thisurl.'?id='.$id.'&view=course&itemid='.$course->id.'">'.$course->shortname.'</a><br>'.$course->summary;
            $row[] = $course->idnumber;
            $row[] = $course->fullname;

            $table->data[] = $row;
        }

        echo html_writer::table($table);

    } else {
        echo $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'));
    }
    echo $OUTPUT->footer();
    die;
}

// If we are not in search mode, we display a course result and will default to origin course.
if (empty($itemid)) {
    $itemid = $id;
}

$pagesize = 30;
$page = optional_param('page', 0, PARAM_INT);
$from = $pagesize * $page;
$reportconfig = get_config('report_learningtimecheck');

$course = $DB->get_record('course', array('id' => $itemid));
$coursecontext = context_course::instance($course->id);

$allgroupsaccess = has_capability('moodle/site:accessallgroups', $coursecontext);

if (!$allgroupsaccess) {

    $mygroups = groups_get_my_groups();

    if ($mygroups) {
        echo groups_print_course_menu($course, new moodle_url('/report/learningtimecheck/index.php', array('id' => $course->id, 'view' => 'course')), true);
        if ($groupid) {
            $groupid = groups_get_course_group($course, true); // update currently registered active group
        }
        $targetusers = report_learningtimecheck_get_users($course->id, $groupid);
    } else {
        echo $OUTPUT->notification(get_string('errornotingroups', 'report_trainingsessions'));
        echo $OUTPUT->footer($course);
        die;
    }
} else {
    if (@$reportconfig->groupseparation == 'groupings') {
        $groupings = groups_get_all_groupings($itemid);
        $groupingid = optional_param('groupingid', 0, PARAM_INT);
        if (!empty($groupings)) {
            echo get_string('groupings', 'group').': ';
            echo report_learningtimecheck_groupings_print_menu($course, new moodle_url('/report/learningtimecheck/index.php', array('id' => $course->id, 'view' => 'course')));
        }
        if ($groupingid) {
            $targetusers = array();
            if ($groupinggroups = $DB->get_records('groupings_groups', array('groupingid' => $groupingid))) {
                $alluserscount;
                foreach ($groupinggroups as $gpm) {
                    // Aggregate all groups in grouping.
                    $allusers = get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u'), $orderby, 0, 0, $gpm->groupid, '', false);
                    if ($allusers) {
                        $alluserscount = $alluserscount + count($allusers);
                        $targetusers = $targetusers + get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u'), $orderby, $from, $pagesize, $gpm->groupid, '', false);
                    }
                }
            }
        } else {
            // This capability is usually given to students.
            $allusers = get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u').',u.idnumber', $orderby, 0, 0, 0, '', false);
            $targetusers = get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u').',u.idnumber', $orderby, $from, $pagesize, 0, '', false);
            $alluserscount = count($allusers);
        }
    } else {
        // Group separation mode is "groups"
        echo groups_print_course_menu($course, new moodle_url('/report/learningtimecheck/index.php', array('id' => $course->id, 'view' => 'course')), true);
        $groupid = optional_param('group', 0, PARAM_INT);
        groups_get_course_group($course, true); // update currently registered active group
        $allusers = get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u').',u.idnumber', 'lastname, firstname', 0, 0, $groupid, '', false);
        $targetusers = get_users_by_capability($coursecontext, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u').',u.idnumber', 'lastname, firstname', $from, $pagesize, $groupid, '', false);
        $alluserscount = count($allusers);
    }
}

// Process user list against active rule filter

learningtimecheck_apply_rules($targetusers);
$renderer = $PAGE->get_renderer('learningtimecheck');

echo $renderer->print_event_filter($thisurl, $thisurl, 'report', $itemid);
echo $reportrenderer->options($view, $id, $itemid);
$useroptions = report_learningtimecheck_get_user_options();

$userurl = new moodle_url('/course/view.php', array('id' => $course->id));
$course->fullname = '<a href="'.$userurl.'">'.$course->fullname.'</a>';

if (!empty($targetusers)) {
    $globals = array();
    $coursetable = report_learningtimecheck_course_results($id, $targetusers, $course->id, $globals, $useroptions);

    echo $OUTPUT->heading(get_string('coursereport', 'report_learningtimecheck', $course));

    echo '<div id="report-learningtimecheck-buttons">';
    echo $reportrenderer->print_export_excel_button($id, 'course', $course->id);
    $params = array('groupid' => $groupid, 'groupingid' => $groupingid);
    echo $reportrenderer->print_export_pdf_button($id, 'course', $course->id, false, $params);
    echo $reportrenderer->print_back_search_button('course', $id);
    echo $reportrenderer->print_send_to_batch_button('course', $id, $course->id, $useroptions);
    echo $reportrenderer->print_send_detail_to_batch_button('course', $id, $course->id, $useroptions);
    echo '</div>';

    echo $OUTPUT->paging_bar($alluserscount, $page, $pagesize, $thisurl);

    echo $OUTPUT->box_start('learningtimecheck-report');
    echo html_writer::table($coursetable);
    echo $OUTPUT->box_end();

    echo $OUTPUT->paging_bar($alluserscount, $page, $pagesize, $thisurl);

} else {
    echo $OUTPUT->heading(get_string('coursereport', 'report_learningtimecheck', $course));
    echo $OUTPUT->box(get_string('nousers', 'report_learningtimecheck'));
}

echo '<p><center>';
$thisurl->params(array('id' => $id, 'view' => 'course', 'search' => 1));
echo $OUTPUT->single_button($thisurl, get_string('searchcourses', 'report_learningtimecheck'));
echo '</center></p>';
