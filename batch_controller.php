<?php

require('../../config.php');

$id = required_param('id', PARAM_INT);
$view = required_param('view', PARAM_TEXT);

$coursecontext = context_course::instance($id);
$PAGE->set_context($coursecontext);

// Security.
require_login();

$url = new moodle_url('/report_learningtimecheck/index.php', array('id' => $id, 'view' => $view));

if (optional_param('addbatch', false, PARAM_TEXT)) {

    redirect(new moodle_url('/report/learningtimecheck/batch.php', array('id' => $id, 'view' => $view)));
    die;
}

if (optional_param('clearall', false, PARAM_TEXT)) {
    $DB->delete_records('report_learningtimecheck_btc', array('processed' => 0, 'userid' => 0));
    redirect($url); 
}

if (optional_param('clearowned', false, PARAM_TEXT)) {
    $DB->delete_records('report_learningtimecheck_btc', array('processed' => 0, 'userid' => $USER->id));
    redirect($url); 
}

if (optional_param('clearmarks', false, PARAM_TEXT)) {
    unset($SESSION->ltc_report_marks);
    redirect($url); 
}