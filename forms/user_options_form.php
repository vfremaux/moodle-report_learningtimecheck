<?php

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

        $mform->addElement('header', 'head0', get_string('worktimefilter', 'report_learningtimecheck'));

        $hours = array();
        for ($i = 0; $i < 24 ; $i++) {
            $hours[$i] = $i;
        }

        $minrange = array();
        for ($i = 0; $i < 60 ; $i = $i + 5) {
            $mins[$i] = $i;
        }
        $group1[] = & $mform->createElement('select', 'workstarttime_h', get_string('workstarttime', 'report_learningtimecheck'), $hours);
        $mform->setDefault('workstarttime_h', get_config('report_learningtimecheck', 'work_timerange_start_h'));
        $group1[] = & $mform->createElement('select', 'workstarttime_m', get_string('workstarttime', 'report_learningtimecheck'), $mins);
        $mform->setDefault('workstarttime_m', get_config('report_learningtimecheck', 'work_timerange_start_m'));
        $mform->addGroup($group1, 'servicestarttime', get_string('workstarttime', 'report_learningtimecheck'), array(''), false);

        $group2[] = & $mform->createElement('select', 'workendtime_h', get_string('workendtime', 'report_learningtimecheck'), $hours);
        $mform->setDefault('workendtime_h', get_config('report_learningtimecheck', 'work_timerange_end_h'));
        $group2[] = & $mform->createElement('select', 'workendtime_m', get_string('workendtime', 'report_learningtimecheck'), $mins);
        $mform->setDefault('workendtime_m', get_config('report_learningtimecheck', 'work_timerange_end_m'));
        $mform->addGroup($group2, 'workendtime', get_string('workendtime', 'report_learningtimecheck'), array(''), false);
        
        $yesnooptions = array(0 => get_string('no'), 1 => get_string('yes'));

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[0]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[0]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar0', get_string('monday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[0]', 1);

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[1]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[1]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar1', get_string('tuesday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[1]', 1);

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[2]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[2]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar2', get_string('wednesday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[2]', 1);

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[3]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[3]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar3', get_string('thursday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[3]', 1);

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[4]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[4]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar4', get_string('friday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[4]', 1);

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[5]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[5]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar5', get_string('saturday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[5]', 0);

        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'workday[6]', '', get_string('yes'), 1);
        $radioarray[] =& $mform->createElement('radio', 'workday[6]', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar6', get_string('sunday', 'calendar'), array(' '), false);
        $mform->setDefault('workday[6]', 0);

        $mform->addElement('text', 'vacationdays', get_string('vacationdays', 'report_learningtimecheck'), array('size' => 100));
        $mform->setType('vacationdays', PARAM_TEXT);
        $mform->addHelpButton('vacationdays', 'vacationdays', 'report_learningtimecheck');
        $mform->setDefault('vacationdays', '1,365');

        $mform->addElement('header', 'head1', get_string('weeksfilter', 'report_learningtimecheck'));

        $weeks = array();
        $weeks[0] = get_string('disabled', 'report_learningtimecheck');
        for ($i = -20 ; $i <= 52 ; $i++) {
            if ($i == 0) continue; // do not mention week 0 : Week -1 is last week of last year.
            $weeks[$i] = $i;
        }

        $mform->addElement('select', 'startweek', get_string('startweek', 'report_learningtimecheck'), $weeks);
        $mform->setType('startweek', PARAM_INT);

        $mform->addElement('select', 'endweek', get_string('endweek', 'report_learningtimecheck'), $weeks);
        $mform->setType('endweek', PARAM_INT);

        $mform->addElement('header', 'head1', get_string('pdfoptions', 'report_learningtimecheck'));

        $mform->addElement('checkbox', 'hideidnumber', get_string('pdfhideidnumbers', 'report_learningtimecheck'));
        $mform->setType('hideidnumber', PARAM_BOOL);

        $mform->addElement('checkbox', 'hidegroup', get_string('pdfhidegroups', 'report_learningtimecheck'));
        $mform->setType('hidegroup', PARAM_BOOL);

        $this->add_action_buttons(false, get_string('update'));
    }
}