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
require_once($CFG->dirroot.'/report/learningtimecheck/forms/import_marks_form.php');
require_once($CFG->dirroot.'/report/learningtimecheck/forms/generate_marks_form.php');

$url = new moodle_url('/report/learningtimecheck/import/importmarks.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url($url);

// Security.

require_login();
require_capability('moodle/site:config', $systemcontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('importmarks', 'report_learningtimecheck'));

$config = get_config('report_learningtimecheck');

echo $OUTPUT->header();

$checkurl = new moodle_url('/report/learningtimecheck/checkwdmarks.php');
echo '<div class="menu"><a href="'.$checkurl.'">'.get_string('checkwdmarks', 'report_learningtimecheck').'</a></div>';

echo $OUTPUT->heading(get_string('importmarks', 'report_learningtimecheck'));

$mform = new ImportMarksForm();

$config = get_config('report_learningtimecheck');

if ($data = $mform->get_data()) {
    // Process file

    if (!empty($data->clearallmarks)) {
        $DB->delete_records('event', array('uuid' => 'learningtimecheck'));
        echo $OUTPUT->notification("All learningtimechecks deleted");
    } else {

        $filepickerid = $data->wdfile;

        $fs = get_file_storage();

        $usercontext = context_user::instance($USER->id);
        $USERCACHE = array();

        if (!$fs->is_area_empty($usercontext->id, 'user', 'draft')) {
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $filepickerid, '', false);
            $uploadedfile = array_pop($files);
            $FILE = $uploadedfile->get_content_file_handle();

            $i = 0;
            while (report_learningtimecheck_is_empty_line_or_format($text, $i == 0)) {
                $text = fgets($FILE, 1024);
                $i++;
            }

            $headers = explode(';', $text);

            if (!in_array('userid', $headers) || !in_array('date', $headers)) {
                print_error(get_string('errorbadcsvformat', 'report_learningtimecheck'));
            }

            echo '<pre>';
            while (!feof($FILE)) {
                $text = fgets($FILE, 1024);
                if (report_learningtimecheck_is_empty_line_or_format($text, false)) {
                    $i++;
                    continue;
                }

                // Process 
                $data = explode(';', $text);

                if (!in_array($config->wdimportuseridentifier, array('id', 'idnumber', 'username', 'email'))) {
                    $field = 'username';
                } else {
                    $field = $config->wdimportuseridentifier;
                }

                if (!array_key_exists($data[0], $USERCACHE)) {
                    $userrec = $DB->get_record('user', array($field => $data[0]), 'id,'.get_all_user_name_fields(true, ''));
                    if (empty($userrec)) {
                        mtrace("User $data[0] as $field does not exist in database.");
                        continue;
                    } else {
                        $USERCACHE[$data[0]] = $userrec;
                    }
                }

                if (preg_match('/(\\d{4})[-](\\d{2})[-](\\d{2})/', $data[1], $matches)) {
                    $day = $matches[3];
                    $month = $matches[2];
                    $year = $matches[1];
                } elseif (preg_match('#(\\d{2})[/](\\d{2})[/](\\d{4})#', $data[1], $matches)) {
                    $day = $matches[1];
                    $month = $matches[2];
                    $year = $matches[3];
                }
                if (report_learningtimecheck_generate_event($USERCACHE[$data[0]], $day, $month, $year)) {
                    echo $OUTPUT->notification("Generated for $data[0] at $day/$month/$year");
                }
            }
            echo '</pre>';
        }
    }
}

$mform->display();

echo $OUTPUT->heading(get_string('generatemarks', 'report_learningtimecheck'));

$mform = new GenerateMarksForm($CFG->wwwroot.'/report/learningtimecheck/import/generateselectusers.php');

$mform->display();

echo $OUTPUT->footer();