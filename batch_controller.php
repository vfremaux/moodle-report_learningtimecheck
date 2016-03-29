<?php

require('../../config.php');

$id = required_param('id', PARAM_INT);
$view = required_param('view', PARAM_TEXT);

$coursecontext = context_course::instance($id);
$PAGE->set_context($coursecontext);

// Security.
require_login();

$url = new moodle_url('/report/learningtimecheck/index.php', array('id' => $id, 'view' => $view));

if (optional_param('addbatch', false, PARAM_TEXT)) {

    redirect(new moodle_url('/report/learningtimecheck/batch.php', array('id' => $id, 'view' => $view)));
    die;
}

if (optional_param('clearall', false, PARAM_TEXT)) {
    $DB->delete_records('report_learningtimecheck_btc', array('processed' => 0, 'userid' => 0));
    redirect($url);
}

if (optional_param('clearallresults', false, PARAM_TEXT)) {
    $fs = get_file_storage();
    
    $context = context_system::instance();
    $fs->delete_area_files($context->id, 'report_learningtimecheck', 'batchresult');
    redirect($url);
}

if (optional_param('clearowned', false, PARAM_TEXT)) {
    $DB->delete_records('report_learningtimecheck_btc', array('processed' => 0, 'userid' => $USER->id));
    redirect($url);
}


if (optional_param('clearownedresults', false, PARAM_TEXT)) {
    global $USER;

    $fs = get_file_storage();

    $context = context_user::instance($USER->id);
    $fs->delete_area_files($context->id, 'report_learningtimecheck', 'batchresult');
    redirect($url);
}

if (optional_param('clearmarks', false, PARAM_TEXT)) {
    unset($SESSION->ltc_report_marks);
    redirect($url);
}