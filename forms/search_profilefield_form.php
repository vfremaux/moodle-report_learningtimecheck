<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class SearchProfileForm extends moodleform{

    function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('select', 'profilefield', get_string('profilefield', 'report_learningtimecheck'), $this->_customdata['profilefields']);

        $mform->addElement('text', 'conditions', get_string('conditions', 'report_learningtimecheck'), array('size' => 120));
        $mform->setType('conditions', PARAM_TEXT);

        $mform->addElement('hidden', 'view', 'profile');
        $mform->setType('view', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));
    }
}