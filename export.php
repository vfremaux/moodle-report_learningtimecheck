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
require('../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/rulefilterlib.php');

// Context.

$id = optional_param('id', SITEID, PARAM_INT);

// Security.

require_login();

$context = context_course::instance($id);
require_capability('report/learningtimecheck:export', $context);

// Export params.

$exporttype = required_param('exporttype', PARAM_TEXT);
$courseid = required_param('id', PARAM_INT);
$exportitem = required_param('itemid', PARAM_INT);
$output = required_param('output', PARAM_TEXT);
$detail = required_param('detail', PARAM_TEXT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$groupingid = optional_param('groupingid', 0, PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

// Some page params.

// Security.
if (!check_group_authorisation($courseid, $groupid, $groupingid)) {
    print_error('invalidgroupaccess', 'report_learningtimecheck');
}

$PAGE->set_url(new moodle_url('/report/learningtimecheck/export.php'));
$PAGE->set_context($context);

// Data recollection.

$job = new StdClass;
$job->type = $exporttype;
$job->itemids = $exportitem;
$job->filters = json_encode(@$SESSION->learningtimecheck->rulefilters);
$job->detail = $detail;
$job->courseid = $courseid;
$job->groupid = $groupid;
$job->options = json_encode(report_learningtimecheck_get_user_options());
$data = array();
$globals = array();

$syscontext = context_system::instance();

if (!empty($debug) && has_capability('moodle/site:config', $syscontext)) {
    print_object($job);
}

report_learningtimecheck_prepare_data($job, $data, $globals);

if (!empty($debug) && has_capability('moodle/site:config', $syscontext)) {
    // Do not render.
    die;
}

$exportclassfile = $CFG->dirroot.'/report/learningtimecheck/export/'.$output.'.class.php';
if (!file_exists($exportclassfile)) {
    if (is_dir($CFG->dirroot.'/report/learningtimecheck/pro/export')) {
        $exportclassfile = $CFG->dirroot.'/report/learningtimecheck/pro/export/'.$output.'.class.php';
        if (!file_exists($exportclassfile)) {
            print_error('errornoexporterclass', 'report_learningtimecheck', $exportclassfile);
        }
    } else {
        print_error('errordistribution', 'report_learningtimecheck');
    }
}
require_once($exportclassfile);

$exportcontext = new StdClass();
$exportcontext->exporttype = $exporttype;
$exportcontext->exportitem = $exportitem;
$exportcontext->param = $courseid;
$exportcontext->exportfilename = $exporttype.'_'.$exportitem.'_'.date('Ymd-Hi', time());

// This is a temp hack.
if ($exporttype == 'cohort' && $detail) {
    $exportcontext->exporttype = 'cohortdetail';
}

$classname = $output.'_exporter';
$exporter = new $classname($exportcontext);

$exporter->set_data($data, $globals);

// Output production.
$exporter->output();