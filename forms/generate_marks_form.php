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
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

class GenerateMarksForm extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addelement('header', 'head0', get_string('generatemarksparams', 'report_learningtimecheck'));

        $mform->addElement('date_selector', 'fromdate', get_string('fromdate', 'report_learningtimecheck'));
        $mform->addElement('date_selector', 'todate', get_string('todate', 'report_learningtimecheck'));

        $mform->addElement('checkbox', 'days[1]', get_string('monday', 'calendar'));
        $mform->setType('days[1]', PARAM_BOOL);
        $mform->setDefault('days[1]', 1);

        $mform->addElement('checkbox', 'days[2]', get_string('tuesday', 'calendar'));
        $mform->setType('days[2]', PARAM_BOOL);
        $mform->setDefault('days[2]', 1);

        $mform->addElement('checkbox', 'days[3]', get_string('wednesday', 'calendar'));
        $mform->setType('days[3]', PARAM_BOOL);
        $mform->setDefault('days[3]', 1);

        $mform->addElement('checkbox', 'days[4]', get_string('thursday', 'calendar'));
        $mform->setType('days[4]', PARAM_BOOL);
        $mform->setDefault('days[4]', 1);

        $mform->addElement('checkbox', 'days[5]', get_string('friday', 'calendar'));
        $mform->setType('days[5]', PARAM_BOOL);
        $mform->setDefault('days[5]', 1);

        $mform->addElement('checkbox', 'days[6]', get_string('saturday', 'calendar'));
        $mform->setType('days[6]', PARAM_BOOL);
        $mform->setDefault('days[6]', 0);

        $mform->addElement('checkbox', 'days[0]', get_string('sunday', 'calendar'));
        $mform->setType('days[0]', PARAM_BOOL);
        $mform->setDefault('days[0]', 0);

        $this->add_action_buttons(true, get_string('next'));
    }

}
