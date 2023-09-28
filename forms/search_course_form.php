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

class SearchCourseForm extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        $categories = $this->_customdata['categories'];
        $current = $this->_customdata['current'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'search', 1);
        $mform->setType('search', PARAM_BOOL);

        $mform->addElement('header', 'head0', get_string('searchbytext', 'report_learningtimecheck'));

        $mform->addElement('text', 'searchpattern');
        $mform->setType('searchpattern', PARAM_TEXT);

        $mform->addElement('header', 'head0', get_string('searchincategories', 'report_learningtimecheck'));
        $categorymenu = array();

        if (!is_null($current)) {

            $scan = $current;

            $parents = array();
            while (!is_null($scan) && $scan->parent) {
                $parents[$current->parent] = $DB->get_field('course_categories', 'name', array('id' => $scan->parent));
                $scan = $parents[$current->parent];
            }

            $categorymenu[0] = get_string('top', 'report_learningtimecheck');
            foreach (array_reverse($parents) as $pid => $pname) {
                $categorymenu[$pid] = $pname;
            }
        }

        if (!empty($categories)) {
            $categorymenu += $categories;
        }

        $mform->addElement('select', 'category', get_string('category'), $categorymenu);
        $mform->setType('category', PARAM_INT);

        $mform->addElement('hidden', 'view', 'course');
        $mform->setType('view', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));
    }

    public function definition_after_data() {
    }
}