<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class SearchUserForm extends moodleform {

    public function definition(){

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'view', 'user');
        $mform->setType('view', PARAM_ALPHA);

        $mform->addElement('text', 'searchpattern');
        $mform->setType('searchpattern', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));
    }
}