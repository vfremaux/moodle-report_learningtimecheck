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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');

$options = array(
    24 * HOURSECS => '1 '.get_string('day'),
    48 * HOURSECS => '2 '.get_string('days'),
    7 * DAYSECS => '1 '.get_string('week')
);

$key = 'report_learningtimecheck/pruneprocessdbatchsafter';
$label = get_string('pruneprocessedbatchsafter', 'report_learningtimecheck');
$desc = get_string('pruneprocessedbatchsafter_desc', 'report_learningtimecheck');
$default = 48 * HOURSECS;
$settings->add(new admin_setting_configselect($key, $label, $desc, $default, $options));

$key = 'report_learningtimecheck/groupseparation';
$label = get_string('groupseparation', 'report_learningtimecheck');
$desc = get_string('groupseparation_desc', 'report_learningtimecheck');
$default = 'groups';
$separationoptions = array('groups' => get_string('groups'), 'groupings' => get_string('groupings', 'group'));
$settings->add(new admin_setting_configselect($key, $label, $desc, $default, $separationoptions));

$key = 'report_learningtimecheck/recipient';
$label = get_string('recipient', 'report_learningtimecheck');
$desc = get_string('recipient_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configtext($key, $label, $desc, ''));

if (report_learningtimecheck_supports_feature('format/pdf')) {
    $key = 'report_learningtimecheck/pdfreportheader';
    $label = get_string('pdfreportheader', 'report_learningtimecheck');
    $desc = get_string('pdfreportheader_desc', 'report_learningtimecheck');
    $options = array('subdirs' => false, 'maxfiles' => 1);
    $settings->add(new admin_setting_configstoredfile($key, $label, $desc, 'pdfreportheader', 0, $options));

    $key = 'report_learningtimecheck/pdfreportinnerheader';
    $label = get_string('pdfreportinnerheader', 'report_learningtimecheck');
    $desc = get_string('pdfreportinnerheader_desc', 'report_learningtimecheck');
    $options = array('subdirs' => false, 'maxfiles' => 1);
    $settings->add(new admin_setting_configstoredfile($key, $label, $desc, 'pdfreportinnerheader', 0, $options));

    $key = 'report_learningtimecheck/pdfreportfooter';
    $label = get_string('pdfreportfooter', 'report_learningtimecheck');
    $desc = get_string('pdfreportfooter_desc', 'report_learningtimecheck');
    $options = array('subdirs' => false, 'maxfiles' => 1);
    $settings->add(new admin_setting_configstoredfile($key, $label, $desc, 'pdfreportfooter', 0, $options));
}

$timeformatoptions = array('Ymd' => 'Ymd',
    'Y-m-d' => 'Y-m-d',
    'Y-m-d H:i' => 'Y-m-d H:i',
    'd/m/Y' => 'd/m/Y',
    'd/m/Y H:i' => 'd/m/Y H:i',
);

$key = 'report_learningtimecheck/marktimeformat';
$label = get_string('marktimeformat', 'report_learningtimecheck');
$desc = '';
$default = 'd/m/Y';
$settings->add(new admin_setting_configselect($key, $label, $desc, $default, $timeformatoptions));

$coursefilenameoptions = array(
    REPORT_IDENTIFIER_ID => 'id',
    REPORT_IDENTIFIER_IDNUMBER => get_string('idnumber'),
    REPORT_IDENTIFIER_NAME => get_string('shortname')
);

$key = 'report_learningtimecheck/infilenamecourseidentifier';
$label = get_string('infilenamecourseidentifier', 'report_learningtimecheck');
$desc = get_string('infilenamecourseidentifier_desc', 'report_learningtimecheck');
$default = 0;
$settings->add(new admin_setting_configselect($key, $label, $desc, $default, $coursefilenameoptions));

$userfilenameoptions = array(
    REPORT_IDENTIFIER_ID => 'id',
    REPORT_IDENTIFIER_IDNUMBER => get_string('idnumber'),
    REPORT_IDENTIFIER_NAME => get_string('username')
);

$key = 'report_learningtimecheck/infilenameuseridentifier';
$label = get_string('infilenameuseridentifier', 'report_learningtimecheck');
$desc = get_string('infilenameuseridentifier_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configselect($key, $label, $desc, 0, $userfilenameoptions));

$cohortfilenameoptions = array(
    REPORT_IDENTIFIER_ID => 'id',
    REPORT_IDENTIFIER_IDNUMBER => get_string('idnumber'),
    REPORT_IDENTIFIER_NAME => get_string('name')
);

$key = 'report_learningtimecheck/infilenamecohortidentifier';
$label = get_string('infilenamecohortidentifier', 'report_learningtimecheck');
$desc = get_string('infilenamecohortidentifier_desc', 'report_learningtimecheck');
$default = 0;
$settings->add(new admin_setting_configselect($key, $label, $desc, $default, $cohortfilenameoptions));

$settings->add(new admin_setting_heading('workinghours', get_string('workinghours', 'report_learningtimecheck'),
                   get_string('workinghours_desc', 'report_learningtimecheck')));

$key = 'report_learningtimecheck/checkworkinghours';
$label = get_string('checkworkinghours', 'report_learningtimecheck');
$desc = get_string('checkworkinghours_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

$keyh = 'report_learningtimecheck/workingtimestart_h';
$keym = 'report_learningtimecheck/workingtimestart_m';
$label = get_string('workingstart', 'report_learningtimecheck');
$desc = get_string('workingstart_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configtime($keyh, $keym, $label, $desc, array('h' => 8, 'm' => 0)));

$keyh = 'report_learningtimecheck/workingtimeend_h';
$keym = 'report_learningtimecheck/workingtimeend_m';
$label = get_string('workingend', 'report_learningtimecheck');
$desc = get_string('workingend_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configtime($keyh, $keym, $label, $desc, array('h' => 18, 'm' => 0)));

// Using ISO-8601 reference for weekdays
$key = 'report_learningtimecheck/workday1';
$label = get_string('monday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

$key = 'report_learningtimecheck/workday2';
$label = get_string('tuesday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

$key = 'report_learningtimecheck/workday3';
$label = get_string('wednesday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

$key = 'report_learningtimecheck/workday4';
$label = get_string('thursday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

$key = 'report_learningtimecheck/workday5';
$label = get_string('friday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, '', 1));

$key = 'report_learningtimecheck/workday6';
$label = get_string('saturday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

$key = 'report_learningtimecheck/workday7';
$label = get_string('sunday', 'calendar');
$desc = '';
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

$key = 'report_learningtimecheck/vacationdays';
$label = get_string('vacationdays', 'report_learningtimecheck');
$desc = get_string('vacationdays_desc', 'report_learningtimecheck');
$default = '1,365';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default, PARAM_TEXT));

$importurl = new moodle_url('/report/learningtimecheck/import/importwdmarks.php');
$checkurl = new moodle_url('/report/learningtimecheck/checkwdmarks.php');
$label = get_string('workingdays', 'report_learningtimecheck');
$html = get_string('workingdays_desc', 'report_learningtimecheck');
$html .= ' <a href="'.$importurl.'">'.get_string('workingdays_link', 'report_learningtimecheck').'</a><br>';
$html .= get_string('alsoworkingdayscheck', 'report_learningtimecheck');
$html .= ' <a href="'.$checkurl.'">'.get_string('workingdayscheck_link', 'report_learningtimecheck').'</a>';
$settings->add(new admin_setting_heading('workingdays', $label, $html));

$key = 'report_learningtimecheck/checkworkingdays';
$label = get_string('checkworkingdays', 'report_learningtimecheck');
$desc = get_string('checkworkingdays_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

$key = 'report_learningtimecheck/wdsecret';
$label = get_string('wdsecret', 'report_learningtimecheck');
$desc = get_string('wdsecret_desc', 'report_learningtimecheck');
$settings->add(new admin_setting_configtext($key, $label, $desc, '', PARAM_TEXT));

$useridoptions = array('id' => get_string('id', 'report_learningtimecheck'),
                       'idnumber' => get_string('idnumber'),
                       'username' => get_string('username'),
                       'email' => get_string('email'));

$key = 'report_learningtimecheck/wdimportuseridentifier';
$label = get_string('wdimportuseridentifier', 'report_learningtimecheck');
$desc = get_string('wdimportuseridentifier_desc', 'report_learningtimecheck');
$desfault = 'username';
$settings->add(new admin_setting_configselect($key, $label, $desc, $default, $useridoptions));

if (report_learningtimecheck_supports_feature('format/pdf')) {

    $settings->add(new admin_setting_heading('pdfcolors', get_string('pdfcolors', 'report_learningtimecheck'), ''));

    // Background colors.
    $name = 'report_learningtimecheck/pdfbgcolor1';
    $label = get_string('backgroundcolor', 'report_learningtimecheck').' 1';
    $desc = '';
    $default = '#444444';
    $previewconfig = null;
    $settings->add($setting = new admin_setting_configcolourpicker($name, $label, $desc, $default, $previewconfig));
    $setting->set_updatedcallback('theme_reset_all_caches');

    $name = 'report_learningtimecheck/pdfbgcolor2';
    $label = get_string('backgroundcolor', 'report_learningtimecheck').' 2';
    $desc = '';
    $default = '#888888';
    $previewconfig = null;
    $settings->add($setting = new admin_setting_configcolourpicker($name, $label, $desc, $default, $previewconfig));
    $setting->set_updatedcallback('theme_reset_all_caches');

    // Text colors.
    $name = 'report_learningtimecheck/pdfcolor1';
    $label = get_string('textcolor', 'report_learningtimecheck').' 1';
    $desc = '';
    $default = '#eeeeee';
    $previewconfig = null;
    $settings->add($setting = new admin_setting_configcolourpicker($name, $label, $desc, $default, $previewconfig));
    $setting->set_updatedcallback('theme_reset_all_caches');

    $name = 'report_learningtimecheck/pdfcolor2';
    $label = get_string('textcolor', 'report_learningtimecheck').' 2';
    $desc = '';
    $default = '#ffffff';
    $previewconfig = null;
    $settings->add($setting = new admin_setting_configcolourpicker($name, $label, $desc, $default, $previewconfig));
    $setting->set_updatedcallback('theme_reset_all_caches');

    $settings->add(new admin_setting_heading('mischdr', get_string('misc', 'report_learningtimecheck'), ''));

    $key = 'report_learningtimecheck/allowdisabledenrols';
    $label = get_string('allowdisabledenrols', 'report_learningtimecheck');
    $desc = get_string('allowdisabledenrols_desc', 'report_learningtimecheck');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));
}

if (report_learningtimecheck_supports_feature('emulate/community') == 'pro') {
    include_once($CFG->dirroot.'/report/learningtimecheck/pro/prolib.php');
    $promanager = \report_learningtimecheck\pro_manager::instance();
    $promanager->add_settings($ADMIN, $settings);
} else {
    $label = get_string('plugindist', 'report_learningtimecheck');
    $desc = get_string('plugindist_desc', 'report_learningtimecheck');
    $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
}
