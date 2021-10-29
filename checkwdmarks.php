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
 * @package     report_learningtimecheck
 * @category    report
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->dirroot.'/report/learningtimecheck/import/lib.php');

$url = new moodle_url('/report/learningtimecheck/checkwdmarks.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);

require_login();
require_capability('moodle/site:config', $systemcontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('checkwdmarks', 'report_learningtimecheck'));

$config = get_config('report_learningtimecheck');

$userselector = new wdmarks_generator_user_selector(get_string('userchooser', 'report_learningtimecheck'), array());
$userselector->set_multiselect(false);

$results = false;

if ($users = $userselector->get_selected_users()) {

    $u = array_pop($users);

    if (optional_param('clearmarks', false, PARAM_TEXT)) {
        $DB->delete_records('event', array('userid' => $u->id, 'eventtype' => 'user', 'uuid' => 'learningtimecheck'));
    }

    $marks = report_learningtimecheck::get_user_workdays($u->id);
    $results = true;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('checkwdmarks', 'report_learningtimecheck'));

$importurl = new moodle_url('/report/learningtimecheck/import/importwdmarks.php');
echo '<div class="menu"><a href="'.$importurl.'">'.get_string('generatemarks', 'report_learningtimecheck').'</a></div>';

echo '<form name="checkmarksform" method="post">';
$userselector->display();
echo '<div>';
echo '<input type="submit" name="generate" value="'.get_string('checkusermarks', 'report_learningtimecheck').'" />';
echo ' <input type="submit" name="clearmarks" value="'.get_string('clearusermarks', 'report_learningtimecheck').'" />';
echo '</div>';
echo '</form>';

if ($results) {
    if (empty($marks)) {
        echo $OUTPUT->box($OUTPUT->notification('nomarks', 'report_learningtimecheck'));
    } else {
        $table = new html_table();
        $table->head = array('<b>'.get_string('date').'</b>', '<b>'.get_string('eventkey', 'report_learningtimecheck').'</b>');
        $table->align = array('left', 'left');
        $table->width = '90%';
        $table->size = array('50%', '50%');
        foreach ($marks as $ev) {
            $table->data[] = array(userdate($ev->timestart), report_learningtimecheck::extract_eventkey($ev));
        }
        echo html_writer::table($table);
    }
} else {
    echo $OUTPUT->box($OUTPUT->notification(get_string('chooseauser', 'report_learningtimecheck')));
}

echo $OUTPUT->footer();
