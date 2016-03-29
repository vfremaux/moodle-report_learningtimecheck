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

class UserOptionsForm extends moodleform {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'view');
        $mform->setType('view', PARAM_ALPHA);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_TEXT);

        $mform->addElement('header', 'head1', get_string('timerangefilter', 'report_learningtimecheck'));

        $mform->addElement('date_selector', 'startrange', get_string('startrange', 'report_learningtimecheck'), array('optional' => true));
        $mform->setType('startrange', PARAM_INT);

        $mform->addElement('date_selector', 'endrange', get_string('endrange', 'report_learningtimecheck'), array('optional' => true));
        $mform->setType('endrange', PARAM_INT);

        $mform->addElement('header', 'head1', get_string('columnoptions', 'report_learningtimecheck'));

        $mform->addElement('checkbox', 'hideidnumber', get_string('hideidnumber', 'report_learningtimecheck'));
        $mform->setType('hideidnumber', PARAM_BOOL);

        $mform->addElement('checkbox', 'hidegroup', get_string('hidegroup', 'report_learningtimecheck'));
        $mform->setType('hidegroup', PARAM_BOOL);

        $mform->addElement('checkbox', 'hideheadings', get_string('hideheadings', 'report_learningtimecheck'));
        $mform->setType('hideheadings', PARAM_BOOL);
        $mform->addHelpButton('hideheadings', 'hideheadings', 'report_learningtimecheck');

        $mform->addElement('checkbox', 'hideunmarkedchecks', get_string('hideunmarkedchecks', 'report_learningtimecheck'));
        $mform->setType('hideunmarkedchecks', PARAM_BOOL);
        $mform->addHelpButton('hideunmarkedchecks', 'hideunmarkedchecks', 'report_learningtimecheck');

        $mform->addElement('checkbox', 'hidenocredittime', get_string('hidenocredittime', 'report_learningtimecheck'));
        $mform->setType('hidenocredittime', PARAM_BOOL);
        $mform->addHelpButton('hidenocredittime', 'hidenocredittime', 'report_learningtimecheck');

        $progressbaroptions = array('0' => get_string('itemsprogress', 'report_learningtimecheck'),
            '1' => get_string('timeprogress', 'report_learningtimecheck'),
            '2' => get_string('both', 'report_learningtimecheck'));

        $mform->addElement('select', 'progressbars', get_string('progressbars', 'report_learningtimecheck'), $progressbaroptions);
        $mform->setType('progressbars', PARAM_INT);

        $sortoptions = array('name' => get_string('sortbyname', 'report_learningtimecheck'), 'rank' => get_string('sortbyachievement', 'report_learningtimecheck'));
        $mform->addElement('select', 'sortby', get_string('sortby', 'report_learningtimecheck'), $sortoptions);
        $mform->setDefault('sortby', 'name');
        $mform->setType('sortby', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('update'));
    }
}