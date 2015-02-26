<?php

require('../../../config.php');
require($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

// Just need to login but even this not so mandatory.
require_login();

$url = new moodle_url('/report/learningtimecheck/ajax/services.php');
$PAGE->set_url($url);

$action = required_param('what', PARAM_TEXT);
$view = required_param('view', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);

switch ($view) {
    case 'course':
        $context = context_course::instance($id);
        break;

    case 'user':
        $context = context_user::instance($userid);
        break;

    case 'cohort':
    default:
        $context = context_system::instance();
}
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('mod_learningtimecheck');

if ($action == 'getfilterruleform') {
    $reporturl = new moodle_url('/report/learningtimecheck/index.php', array('id' => $id, 'view' => $view, 'itemid' => $itemid));
    echo $renderer->filter_rule_form($reporturl, $view);
}