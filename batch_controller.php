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
 * Screen for registering a batch from a report screen
 *
 * @package     report_learningtimecheck
 * @category    report
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2014 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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