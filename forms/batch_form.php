<?php

require_once $CFG->libdir.'/formslib.php';

class BatchForm extends moodleform {

    public function definition() {
        global $PAGE;

        $mform = $this->_form;

        /*
        $PAGE->requires->yui_module('moodle-report-learningtimecheck-reporttypechooser', 'M.report_learningtimecheck.init_reporttypechooser',
            array(array('formid' => $mform->getAttribute('id'))));
        */
        
        // Course id (browsing context).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Current view.
        $mform->addElement('hidden', 'view');
        $mform->setType('view', PARAM_TEXT);

        $mform->addElement('checkbox', 'shared', get_string('sharebatch', 'report_learningtimecheck'));
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
            $mform->setType('type', PARAM_TEXT);
        } else {
            $mform->addElement('static', 'typelabel', get_string('type', 'report_learningtimecheck'));
            $mform->addElement('hidden', 'type');
            $mform->setType('type', PARAM_TEXT);
        }

        // List of itemids.
        $mform->addElement('text', 'itemid', get_string('item', 'report_learningtimecheck'), array('size' => 40));
        $mform->setType('itemid', PARAM_TEXT);

        // Extraction user options.
        $mform->addElement('textarea', 'params', get_string('params', 'report_learningtimecheck'), array('rows' => 5, 'cols' => 60));
        $mform->setType('params', PARAM_TEXT);

        // Extraction contextual filters.
        $mform->addElement('textarea', 'filters', get_string('filters', 'report_learningtimecheck'), array('rows' => 5, 'cols' => 60));
        $mform->setType('filters', PARAM_TEXT);

        /*
        // Button to update type-specific options on type change (will be hidden by JavaScript).
        $mform->registerNoSubmitButton('updatetype');
        $mform->addElement('submit', 'updatetype', get_string('updatetype', 'report_learningtimecheck'));

        // Just a placeholder for the type options.
        $mform->addElement('hidden', 'addtypeoptionshere');
        $mform->setType('addtypeoptionshere', PARAM_BOOL);
        */

        $mform->addElement('date_time_selector', 'runtime', get_string('runtime', 'report_learningtimecheck'));

        $mform->addElement('text', 'repeatdelay', get_string('repeatdelay', 'report_learningtimecheck'));
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
            $type = 'user';

            $elements = report_learningtimecheck_create_form_elements($mform, $type);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($mform->removeElement($elements[$i]->getName(), false),
                        'addtypeoptionshere');
            }
        }
    }

    function validation($data, $files = array()) {
    }
}