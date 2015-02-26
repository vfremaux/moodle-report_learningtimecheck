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
 * Screen for regisytering a batch from a report screen
 *
 * @package    report
 * @version    moodle 2.x
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/forms/batch_form.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

$id = required_param('id', PARAM_INT); // Course id
$view = optional_param('view', 'course', PARAM_ALPHA);
$itemid = optional_param('itemid', 0, PARAM_INT);

// This is the origin course. But reports are given over the course level.
if (!$fromcourse = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

$thisurl = new moodle_url('/report/learningtimecheck/batch.php', array('id' => $id, 'view' => $view));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('report');

// Security : we yet are controlled against our originating course.

require_login($fromcourse);
$context = context_course::instance($fromcourse->id);
require_capability('report/learningtimecheck:view', $context);
require_capability('report/learningtimecheck:viewother', $context);

if ($view == 'batchs') {
    $form = new BatchForm($thisurl);
} else {
    $form = new BatchForm($thisurl, array('type' => $view));
}

if ($form->is_cancelled()) {
    if (empty($itemids)) {
        redirect(new moodle_url('/report/learningtimecheck/index.php', array('id' => $id, 'view' => 'batchs')));
    } else {
        redirect(new moodle_url('/report/learningtimecheck/index.php', array('id' => $id, 'view' => $view)));
    }
}

if ($data = $form->get_data()) {

    if (!empty($data->shared)) {
        $data->userid = 0; // Unpersonalize the batch.
    } else {
        $data->userid = $USER->id;
    }

    unset($data->id);
    if (!empty($data->batchid) && $oldrec = $DB->get_record('report_learningtimecheck_btc', array('id' => $data->batchid))) {
        $oldrec = $data;
        $olderec->id = $data->batchid;
        $DB->update_record('report_learningtimecheck_btc', $data);
    } else {
        $DB->insert_record('report_learningtimecheck_btc', $data);
    }
    redirect(new moodle_url('/report/learningtimecheck/index.php', array('view' => 'batchs', 'id' => $id)));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('scheduleabatch', 'report_learningtimecheck'));

$formdata = new StdClass();
$formdata->params = json_encode(report_learningtimecheck_get_user_options());
$formdata->filters = json_encode((!empty($SESSION->learningtimecheck->filterrules)) ? $SESSION->learningtimecheck->filterrules : array());
$formdata->id = $id;
$formdata->view = $view;
$formdata->type = $view;
$formdata->itemid = $itemid;
$formdata->typelabel = get_string($view, 'report_learningtimecheck');

$form->set_data($formdata);

echo $OUTPUT->box_start('learningtimecheck-progressbar');

// echo $OUTPUT->heading(get_string('newbatch', 'report_learningtimecheck'));

$form->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
