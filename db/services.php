<?php
// This file is NOT part of Moodle - http://moodle.org/
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
 * Web service for report_learningtimecheck
 * @package    report_learningtimecheck
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    'report_learningtimecheck_get_user_report' => array(
            'classname'   => 'report_learningtimecheck_external',
            'methodname'  => 'get_user_report',
            'classpath'   => 'report/learningtimecheck/externallib.php',
            'description' => 'Get the report document content for a user',
            'testclientpath' => '/report/learningtimecheck/externallib_forms.php',
            'type'        => 'read'
    ),

    'report_learningtimecheck_get_user_data' => array(
            'classname'   => 'report_learningtimecheck_external',
            'methodname'  => 'get_user_data',
            'classpath'   => 'report/learningtimecheck/externallib.php',
            'description' => 'Get the report document content for a user',
            'testclientpath' => '/report/learningtimecheck/externallib_forms.php',
            'type'        => 'read'
    ),
);

$services = array(
    'learningtimecheck' => array(
        'functions' => array ('report_learningtimecheck_get_user_report'), //web service function name
        'requiredcapability' => 'report/learning',
        'restrictedusers' => 1,
        'enabled' => 0, //used only when installing the services
    ),
    'learningtimecheck' => array(
        'functions' => array ('report_learningtimecheck_get_user_data'), //web service function name
        'requiredcapability' => '',
        'restrictedusers' => 1,
        'enabled' => 0, //used only when installing the services
    )
);