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
 * Settings and links
 *
 * @package    report_learningtimecheck
 * @category   report
 * @copyright  2014 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if (!class_exists('admin_setting_configimage')) {
    require_once($CFG->dirroot.'/report/learningtimecheck/adminlib.php');
}
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');

$options = array(
    24 * HOURSECS => '1 '.get_string('day'),
    48 * HOURSECS => '2 '.get_string('days'),
    7 * DAYSECS => '1 '.get_string('week')
);

$settings->add(new admin_setting_configselect('report_learningtimecheck/pruneprocessdbatchsafter', get_string('pruneprocessedbatchsafter', 'report_learningtimecheck'),
                   get_string('pruneprocessedbatchsafter_desc', 'report_learningtimecheck'), 48 * HOURSECS, $options));

$separationoptions = array('groups' => get_string('groups'), 'groupings' => get_string('groupings', 'group'));
$settings->add(new admin_setting_configselect('report_learningtimecheck/groupseparation', get_string('groupseparation', 'report_learningtimecheck'),
                   get_string('groupseparation_desc', 'report_learningtimecheck'), 'groups', $separationoptions));

$settings->add(new admin_setting_configtext('report_learningtimecheck/recipient', get_string('recipient', 'report_learningtimecheck'),
                   get_string('recipient_desc', 'report_learningtimecheck'), ''));

$settings->add(new admin_setting_configimage('pdfreportheader', get_string('pdfreportheader', 'report_learningtimecheck'),
                   get_string('pdfreportheader_desc', 'report_learningtimecheck'), 'report_learningtimecheck'));

$settings->add(new admin_setting_configimage('pdfreportinnerheader', get_string('pdfreportinnerheader', 'report_learningtimecheck'),
                   get_string('pdfreportinnerheader_desc', 'report_learningtimecheck'), 'report_learningtimecheck'));

$settings->add(new admin_setting_configimage('pdfreportfooter', get_string('pdfreportfooter', 'report_learningtimecheck'),
                   get_string('pdfreportfooter_desc', 'report_learningtimecheck'), 'report_learningtimecheck'));

$timeformatoptions = array('Ymd' => 'Ymd',
    'Y-m-d' => 'Y-m-d',
    'Y-m-d H:i' => 'Y-m-d H:i',
    'd/m/Y' => 'd/m/Y',
    'd/m/Y H:i' => 'd/m/Y H:i',
);

$settings->add(new admin_setting_configselect('report_learningtimecheck/marktimeformat', get_string('marktimeformat', 'report_learningtimecheck'),
                   '', 'd/m/Y', $timeformatoptions));

$coursefilenameoptions = array(
    REPORT_IDENTIFIER_ID => 'id',
    REPORT_IDENTIFIER_IDNUMBER => get_string('idnumber'),
    REPORT_IDENTIFIER_NAME => get_string('shortname')
);

$settings->add(new admin_setting_configselect('report_learningtimecheck/infilenamecourseidentifier', get_string('infilenamecourseidentifier', 'report_learningtimecheck'),
                   get_string('infilenamecourseidentifier_desc', 'report_learningtimecheck'), 0, $coursefilenameoptions));

$userfilenameoptions = array(
    REPORT_IDENTIFIER_ID => 'id',
    REPORT_IDENTIFIER_IDNUMBER => get_string('idnumber'),
    REPORT_IDENTIFIER_NAME => get_string('username')
);

$settings->add(new admin_setting_configselect('report_learningtimecheck/infilenameuseridentifier', get_string('infilenameuseridentifier', 'report_learningtimecheck'),
                   get_string('infilenameuseridentifier_desc', 'report_learningtimecheck'), 0, $userfilenameoptions));

$cohortfilenameoptions = array(
    REPORT_IDENTIFIER_ID => 'id',
    REPORT_IDENTIFIER_IDNUMBER => get_string('idnumber'),
    REPORT_IDENTIFIER_NAME => get_string('name')
);

$settings->add(new admin_setting_configselect('report_learningtimecheck/infilenamecohortidentifier', get_string('infilenamecohortidentifier', 'report_learningtimecheck'),
                   get_string('infilenamecohortidentifier_desc', 'report_learningtimecheck'), 0, $cohortfilenameoptions));

$settings->add(new admin_setting_heading('workinghours', get_string('workinghours', 'report_learningtimecheck'),
                   get_string('workinghours_desc', 'report_learningtimecheck')));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/checkworkinghours', get_string('checkworkinghours', 'report_learningtimecheck'),
                   get_string('checkworkinghours_desc', 'report_learningtimecheck'), 0));

$settings->add(new admin_setting_configtime('report_learningtimecheck/workingtimestart_h', 'workingtimestart_m', get_string('workingstart', 'report_learningtimecheck'),
                   get_string('workingstart_desc', 'report_learningtimecheck'), array('h' => 8, 'm' => 0)));

$settings->add(new admin_setting_configtime('report_learningtimecheck/workingtimeend_h', 'workingtimeend_m', get_string('workingend', 'report_learningtimecheck'),
                   get_string('workingend_desc', 'report_learningtimecheck'), array('h' => 18, 'm' => 0)));

// Using ISO-8601 reference for weekdays
$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday1', get_string('monday', 'calendar'),
                   '', 1));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday2', get_string('tuesday', 'calendar'),
                   '', 1));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday3', get_string('wednesday', 'calendar'),
                   '', 1));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday4', get_string('thursday', 'calendar'),
                   '', 1));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday5', get_string('friday', 'calendar'),
                   '', 1));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday6', get_string('saturday', 'calendar'),
                   '', 0));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/workday7', get_string('sunday', 'calendar'),
                   '', 0));

$settings->add(new admin_setting_configtext('report_learningtimecheck/vacationdays', get_string('vacationdays', 'report_learningtimecheck'),
                   get_string('vacationdays_desc', 'report_learningtimecheck'), '1,365', PARAM_TEXT));

$importurl = new moodle_url('/report/learningtimecheck/import/importwdmarks.php');
$checkurl = new moodle_url('/report/learningtimecheck/checkwdmarks.php');
$settings->add(new admin_setting_heading('workingdays', get_string('workingdays', 'report_learningtimecheck'),
                   get_string('workingdays_desc', 'report_learningtimecheck').' <a href="'.$importurl.'">'.get_string('workingdays_link', 'report_learningtimecheck').'</a><br>'.get_string('alsoworkingdayscheck', 'report_learningtimecheck').' <a href="'.$checkurl.'">'.get_string('workingdayscheck_link', 'report_learningtimecheck').'</a>'));

$settings->add(new admin_setting_configcheckbox('report_learningtimecheck/checkworkingdays', get_string('checkworkingdays', 'report_learningtimecheck'),
                   get_string('checkworkingdays_desc', 'report_learningtimecheck'), 0));

$settings->add(new admin_setting_configtext('report_learningtimecheck/wdsecret', get_string('wdsecret', 'report_learningtimecheck'),
                   get_string('wdsecret_desc', 'report_learningtimecheck'), '', PARAM_TEXT));

$useridoptions = array('id' => get_string('id', 'report_learningtimecheck'),
                       'idnumber' => get_string('idnumber'),
                       'username' => get_string('username'),
                       'email' => get_string('email'));

$settings->add(new admin_setting_configselect('report_learningtimecheck/wdimportuseridentifier', get_string('wdimportuseridentifier', 'report_learningtimecheck'),
                   get_string('wdimportuseridentifier_desc', 'report_learningtimecheck'), 'username', $useridoptions));
