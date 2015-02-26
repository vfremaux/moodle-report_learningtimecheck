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
 * Course learningtimecheck report
 *
 * @package    report
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$id = required_param('id', PARAM_INT); // Course id
$output = optional_param('output', 'html', PARAM_ALPHA);
$view = optional_param('view', 'course', PARAM_ALPHA);
$itemid = optional_param('itemid', 0, PARAM_INT);
$time = optional_param('time', 0, PARAM_INT);

// This is the origin course. But reports are given over the course level.
if (!$fromcourse = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

$thisurl = new moodle_url('/report/learningtimecheck/index.php', array('view' => $view, 'id' => $id, 'itemid' => $itemid));

$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('report');
$context = context_course::instance($fromcourse->id);
$PAGE->set_context($context);

$PAGE->requires->js('/mod/learningtimecheck/js/jquery.easyui.min.js');
$PAGE->requires->js('/mod/learningtimecheck/js/locale/easyui-lang-'.current_language().'.js');
$PAGE->requires->css('/mod/learningtimecheck/css/default/easyui.css');
$PAGE->requires->css('/mod/learningtimecheck/css/icons.css');
$PAGE->requires->js('/mod/learningtimecheck/js/jquery.report.js');

// Security : we yet are controlled against our originating course.

require_login($fromcourse);
$systemcontext = context_system::instance();
require_capability('report/trainingsessions:view', $context);


$action = optional_param('what', '', PARAM_TEXT);
if ($action) {
    include($CFG->dirroot.'/report/learningtimecheck/index.controller.php');
}

$strreports = get_string('reports');
$strcourseoverview = get_string('learningtimecheck', 'report_learningtimecheck');

if ($output == 'csv') {
    if (file_exists($CFG->dirroot."/report/trainingsessions/{$view}report_csv.php")){
        include_once $CFG->dirroot."/report/trainingsessions/{$view}report_csv.php";
        die;
    } else {
        print_error('errorbadviewid', 'report_trainingsessions');
    }
} elseif($output == 'pdf') {
    if (file_exists($CFG->dirroot."/report/trainingsessions/{$view}report_csv.php")){
        include_once $CFG->dirroot."/report/trainingsessions/{$view}report_csv.php";
        die;
    } else {
        print_error('errorbadviewid', 'report_trainingsessions');
    }
}

$renderer = $PAGE->get_renderer('learningtimecheck');
$reportrenderer = $PAGE->get_renderer('report_learningtimecheck');

echo $OUTPUT->header();
$OUTPUT->container_start();

// Print tabs with options for user.
$rows[0][] = new tabobject('user', "index.php?id={$fromcourse->id}&amp;view=user", get_string('user', 'report_learningtimecheck'));
if (has_capability('report/learningtimecheck:viewother', $context)) {
    $rows[0][] = new tabobject('course', "index.php?id={$fromcourse->id}&amp;view=course", get_string('course', 'report_learningtimecheck'));
}
if (has_capability('report/learningtimecheck:viewother', $systemcontext)) {
    $rows[0][] = new tabobject('cohort', "index.php?id={$fromcourse->id}&amp;view=cohort", get_string('cohort', 'report_learningtimecheck'));
}
if (has_capability('report/learningtimecheck:viewother', $context)) {
    $rows[0][] = new tabobject('batchs', "index.php?id={$fromcourse->id}&amp;view=batchs", get_string('batchs', 'report_learningtimecheck'));
}

print_tabs($rows, $view);

$OUTPUT->container_end();

@ini_set('max_execution_time','3000');
raise_memory_limit('250M');

if (file_exists($CFG->dirroot."/report/learningtimecheck/{$view}_report.php")) {
    include($CFG->dirroot."/report/learningtimecheck/{$view}_report.php");
} else {
    print_error('errorbadviewid', 'report_learningtimecheck');
}

echo $OUTPUT->footer();
