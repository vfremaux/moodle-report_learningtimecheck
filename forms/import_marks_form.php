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

defined('MOODLE_INTERNAL') || die;

/**
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->dirroot.'/lib/formslib.php';

class ImportMarksForm extends moodleform{

    function definition(){
        global $COURSE;

        $mform = $this->_form;

        $mform->addElement('filepicker', 'wdfile', get_string('wdfile', 'report_learningtimecheck'), array('courseid' => $COURSE->id, 'accepted_types' => '.csv'));
        $mform->addHelpButton('wdfile', 'wdfile', 'report_learningtimecheck');

        $this->add_action_buttons();

        $mform->addElement('header', 'clear_marks', get_string('clearmarks', 'report_learningtimecheck'));

        $mform->addElement('submit', 'clearallmarks', get_string('clearallmarks', 'report_learningtimecheck'));
    }

}
