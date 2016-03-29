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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/forms/generate_marks_form.php');
require_once($CFG->dirroot.'/report/learningtimecheck/import/lib.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');

$url = new moodle_url('/report/learningtimecheck/import/importmarks.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);

// Security.

require_login();
require_capability('moodle/site:config', $systemcontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('generatemarks', 'report_learningtimecheck'));

$config = get_config('report_learningtimecheck');

$mform = new GenerateMarksForm();

$previousdata = $mform->get_data();
if ($previousdata) {
    $SESSION->generatemarks = $previousdata;
} else {
    $previousdata = $SESSION->generatemarks;
}

$options = array(
    'extrafields' => array('lang','username','email'),
    'file' => 'report/learningtimecheck/import/lib.php'
);
$userselector = new wdmarks_generator_user_selector(get_string('userchooser', 'report_learningtimecheck'), $options);
$userselector->set_multiselect(true);

$action = '';
if (optional_param('generate', false, PARAM_TEXT)) {
    $action = 'generate';
}

if (optional_param('remove', false, PARAM_TEXT)) {
    $action = 'remove';
}

if ($action) {
    if ($users = $userselector->get_selected_users()) {
        $result = '';
        foreach($users as $u) {
            if ($action == 'remove') {
                $count = $DB->delete_records('event', array('uuid' => 'learningtimecheck', 'userid' => $u->id));
                $result = "$count events deleted for user {$u->username} ";
            } elseif ($action == 'generate') {
                $result .= learningtimecheck_generate_wdmark_events_from_session($u, $config);
            }
        }
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('generateselectusers', 'report_learningtimecheck'));
        echo '<div class="notification">'.$result.'</div>';
        echo $OUTPUT->continue_button(new moodle_url('/report/learningtimecheck/import/importwdmarks.php'));
        echo $OUTPUT->footer();
        die;
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('generateselectusers', 'report_learningtimecheck'));

echo '<form name="generatemarksform" method="post">';

$table = new html_table();
$table->head = array('', '');
$table->align = array('right', 'left');
$table->data = array();
foreach ($previousdata as $key => $value) {
    if ($key == 'submitbutton') continue;
    if (is_array($value)) {
        foreach ($value as $valkey => $scalar) {
            $table->data[] = array(get_string($key, 'report_learningtimecheck').' '.$valkey, $scalar);
        }
    } else {
        $table->data[] = array(get_string($key, 'report_learningtimecheck'), $value);
    }
}
echo html_writer::table($table);

$userselector->display();
echo '<div><input type="submit" name="generate" value="'.get_string('generate', 'report_learningtimecheck').'" /> <input type="submit" name="remove" value="'.get_string('removemarks', 'report_learningtimecheck').'" /></div>';
echo '</form>';

echo $OUTPUT->footer();