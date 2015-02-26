<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class SearchCohortForm extends moodleform{
	
	function definition(){
		
		$mform = $this->_form;
		
		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);

		$mform->addElement('text', 'searchpattern');
		$mform->setType('searchpattern', PARAM_TEXT);

		$mform->addElement('hidden', 'view', 'cohort');
		$mform->setType('view', PARAM_TEXT);

		$this->add_action_buttons(false, get_string('search'));				
	}
}