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
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->libdir.'/formslib.php';

class BatchForm extends moodleform {

    public function definition() {
        global $PAGE;

        $mform = $this->_form;

        if (empty($this->_customdata['type'])) {
            $PAGE->requires->yui_module('moodle-report_learningtimecheck-reporttypechooser', 'M.report_learningtimecheck.init_reporttypechooser',
                array(array('formid' => $mform->getAttribute('id'))));
        }

        // Course id (browsing context).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Current view.
        $mform->addElement('hidden', 'view');
        $mform->setType('view', PARAM_TEXT);

        $mform->addElement('checkbox', 'shared', get_string('sharebatch', 'report_learningtimecheck'));
        $mform->addHelpButton('shared', 'sharebatch', 'report_learningtimecheck');
        $mform->setType('shared', PARAM_BOOL);

        // Name for the batch job.
        $mform->addElement('text', 'name', get_string('batchname', 'report_learningtimecheck'), array('size' => 80));
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', get_string('newbatch', 'report_learningtimecheck'));

        // View option (browsing context).
        $typeoptions = array(
            'user' => get_string('user'),
            'cohort' => get_string('cohort', 'report_learningtimecheck'),
            'course' => get_string('course'),
        );

        if (empty($this->_customdata['type'])) {
            $mform->addElement('select', 'type', get_string('type', 'report_learningtimecheck'), $typeoptions);
            $mform->addHelpButton('type', 'type', 'report_learningtimecheck');
            $mform->setType('type', PARAM_TEXT);

            // Button to update type-specific options on type change (will be hidden by JavaScript).
            $mform->registerNoSubmitButton('updatetype');
            $mform->addElement('submit', 'updatetype', get_string('updatetype', 'report_learningtimecheck'));
    
            // Just a placeholder for the type options.
            $mform->addElement('hidden', 'addtypeoptionshere');
            $mform->setType('addtypeoptionshere', PARAM_BOOL);
        } else {
            $mform->addElement('static', 'typelabel', get_string('type', 'report_learningtimecheck'));
            $mform->addHelpButton('typelabel', 'type', 'report_learningtimecheck');
            $mform->addElement('hidden', 'type');
            $mform->setType('type', PARAM_TEXT);

            // Itemid
            $mform->addElement('static', 'itemlabel', get_string('item', 'report_learningtimecheck'), array('size' => 40));
            $mform->addHelpButton('itemlabel', 'item', 'report_learningtimecheck');
            $mform->addElement('hidden', 'itemids');
            $mform->setType('itemids', PARAM_TEXT);
        }

        $mform->addElement('checkbox', 'detail', get_string('detail', 'report_learningtimecheck'));
        $mform->addHelpButton('detail', 'detail', 'report_learningtimecheck');
        $mform->setType('detail', PARAM_BOOL);

        // Accessory param.
        $mform->addElement('hidden', 'param');
        // $mform->addElement('hidden', 'param', get_string('param', 'report_learningtimecheck'), array('size' => 40));
        // $mform->addHelpButton('param', 'param', 'report_learningtimecheck');
        $mform->setType('param', PARAM_TEXT);

        // Extraction user options.
        $mform->addElement('textarea', 'options', get_string('options', 'report_learningtimecheck'), array('rows' => 5, 'cols' => 60));
        $mform->addHelpButton('options', 'options', 'report_learningtimecheck');
        $mform->setType('options', PARAM_TEXT);

        // Extraction contextual filters.
        $mform->addElement('textarea', 'filters', get_string('filters', 'report_learningtimecheck'), array('rows' => 5, 'cols' => 60));
        $mform->addHelpButton('filters', 'filters', 'report_learningtimecheck');
        $mform->setType('filters', PARAM_TEXT);

        $thisyear = (int)date('Y', time());
        $options = array(
            'startyear' => $thisyear,
            'stopyear'  => $thisyear + 2,
            'timezone'  => 99,
            'step'      => 5,
            'optional' => false
        );
        $mform->addElement('date_time_selector', 'runtime', get_string('runtime', 'report_learningtimecheck'), $options);
        $mform->setDefault('runtime', time() + HOURSECS);

        $delayopts = array(
            '' => get_string('singlerun', 'report_learningtimecheck'),
            '60' => get_string('onehour', 'report_learningtimecheck'),
            '360' => get_string('sixhours', 'report_learningtimecheck'),
            '720' => get_string('twelvehours', 'report_learningtimecheck'),
            '1440' => get_string('oneday', 'report_learningtimecheck'),
            '10080' => get_string('oneweek', 'report_learningtimecheck'),
            '20160' => get_string('twoweeks', 'report_learningtimecheck'),
            '43200' => get_string('thirtydays', 'report_learningtimecheck'),
        );
        $mform->addElement('select', 'repeatdelay', get_string('repeatdelay', 'report_learningtimecheck'), $delayopts);
        $mform->addHelpButton('repeatdelay', 'repeatdelay', 'report_learningtimecheck');
        $mform->setType('repeatdelay', PARAM_INT);

        // Output format.
        $outputoptions = array(
            'pdf' => get_string('exportpdf', 'report_learningtimecheck'),
            'xls' => get_string('exportxls', 'report_learningtimecheck'),
            'csv' => get_string('exportcsv', 'report_learningtimecheck'),
            'xml' => get_string('exportxml', 'report_learningtimecheck'),
        );

        $mform->addElement('select', 'output', get_string('output', 'report_learningtimecheck'), $outputoptions);
        $mform->setType('output', PARAM_TEXT);
        $mform->setDefault('output', 'exportpdf');

        // Notify Emails.
        $mform->addElement('textarea', 'notifymails', get_string('notifyemails', 'report_learningtimecheck'), array('rows' => 8, 'cols' => 40));
        $mform->setType('params', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('schedule', 'report_learningtimecheck'));
    }

    public function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        // add type options
        $typevalue = $mform->getElementValue('type');
        if (is_array($typevalue) && !empty($typevalue)) {
            $type = array_shift($typevalue);

            $elements = report_learningtimecheck_create_form_elements($mform, $type);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($elements[$i], 'addtypeoptionshere');
            }
        }
    }

    function validation($data, $files = array()) {
    }
}