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
 * Screen for selecting personal options related to reports
 *
 * @package    report
 * @version    moodle 2.x
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/forms/user_options_form.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

$id = required_param('id', PARAM_INT); // Course id
$view = optional_param('view', 'course', PARAM_ALPHA);
$itemid = optional_param('itemid', null, PARAM_INT);
$return = optional_param('return', '', PARAM_TEXT);

// This is the origin course. But reports are given over the course level.
if (!$fromcourse = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

$PAGE->set_url('/report/learningtimecheck/options.php', array('id' => $id, 'view' => $view));
$PAGE->set_pagelayout('report');

// Security.

require_login($fromcourse);
$context = context_course::instance($fromcourse->id);
require_capability('report/learningtimecheck:view', $context);
require_capability('report/learningtimecheck:viewother', $context);

$form = new UserOptionsForm();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/report/learningtimecheck/index.php', array('id' => $id, 'view' => $view)));
}

if ($data = $form->get_data()) {
    if (!isset($data->hideidnumber)) {
        $data->hideidnumber = 0;
    }
    if (!isset($data->hidegroup)) {
        $data->hidegroup = 0;
    }
    if (!isset($data->hideunmarkedchecks)) {
        $data->hideunmarkedchecks = 0;
    }
    if (!isset($data->hideheadings)) {
        $data->hideheadings = 0;
    }
    if (!isset($data->hidenocredittime)) {
        $data->hidenocredittime = 0;
    }
    $DB->delete_records('report_learningtimecheck_opt', array('userid' => $USER->id));
    foreach ($data as $key => $value) {
        if (in_array($key, array('submitbutton', 'view', 'id', 'itemid'))) {
            continue;
        }
        $rec = new StdClass();
        $rec->userid = $USER->id;
        $rec->name = $key;
        $rec->value = trim($value);
        $DB->insert_record('report_learningtimecheck_opt', $rec);
    }
    if ($data->return) {
        list($plugin, $cmid, $view) = explode('/', $data->return);
        if ($plugin == 'mod') {
            $url = new moodle_url('/mod/learningtimecheck/view.php', array('view' => $view, 'id' => $cmid, 'sesskey' => sesskey()));
            redirect($url);
        }
    }
    redirect(new moodle_url('/report/learningtimecheck/index.php', array('view' => $view, 'id' => $id, 'itemid' => $itemid)));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('myreportoptions', 'report_learningtimecheck'));

$formdata = (object)report_learningtimecheck_get_user_options();
if (array_key_exists('workdays', $formdata)) {
    $formdata->workday = explode(',', $formdata->workdays);
    unset($formdata->workdays);
}
$formdata->id = $id;
$formdata->itemid = $itemid;
$formdata->view = $view;
$formdata->return = $return;
$form->set_data($formdata);
echo $OUTPUT->box_start('learningtimecheck-progressbar');
$form->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
