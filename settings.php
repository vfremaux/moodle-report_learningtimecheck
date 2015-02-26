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
 * @package    report
 * @subpackage learningtimecheck
 * @copyright  2014 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!class_exists('admin_setting_configimage')) {
    require_once($CFG->dirroot.'/report/learningtimecheck/adminlib.php');
}

defined('MOODLE_INTERNAL') || die;

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

