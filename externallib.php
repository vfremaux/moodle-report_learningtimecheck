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
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->libdir.'/externallib.php');

class report_learningtimecheck_external extends external_api {

    public static function get_user_report($uidsource = 'id', $uid = false, $cidsource = 'id', $cid = false, $output = 'csv') {
        global $CFG;
        // Invoke in silent mode.

        if (report_learningtimecheck_supports_feature('export/ws')) {
            include_once($CFG->dirroot.'/report/learningtimecheck/pro/externallib.php');
            return report_learningtimecheck_external_pro::get_user_report($uidsource, $uid, $cidsource, $cid, $output);
        } else {
            throw new moodle_exception('WS only provided in "pro" versions. Please contact distributors (see global settings).');
        }
    }

    public static function get_user_report_parameters() {
        return new external_function_parameters(
            array(
                'uidsource' => new external_value(PARAM_TEXT, 'The source field for the user id. Can be id,idnumber,username or email.'),
                'uid' => new external_value(PARAM_TEXT, 'The effective user identifier'),
                'cidsource' => new external_value(PARAM_TEXT, 'The course id'),
                'cid' => new external_value(PARAM_TEXT, 'The course id'),
                'output' => new external_value(PARAM_TEXT, 'The output format of the document. May be csv, xls, pdf, txt. Some formats may require pro license.'),
            )
        );
    }

    public static function get_user_report_returns() {
        return new external_single_structure(
            array(
                'url' => new external_value(PARAM_URL, 'Url of the report that has been generated'),
                'tokenkey' => new external_value(PARAM_RAW, 'a Token key for ensuring security'),
            )
        );
    }

    /* ******************************************* Get user data ****************************************** */

    public static function get_user_data($uidsource, $uid, $cidsource, $cid, $underratioscope = '', $underratio = 0) {
        global $CFG;

        if (report_learningtimecheck_supports_feature('export/ws')) {
            include_once($CFG->dirroot.'/report/learningtimecheck/pro/externallib.php');
            return report_learningtimecheck_external_pro::get_user_data($uidsource, $uid, $cidsource, $cid, $underratioscope, $underratio);
        } else {
            throw new moodle_exception('WS only provided in "pro" versions. Please contact distributors (see global settings).');
        }
    }

    public static function get_user_data_parameters() {
        return new external_function_parameters(
            array(
                'uidsource' => new external_value(PARAM_TEXT, 'The source for user id'),
                'uid' => new external_value(PARAM_TEXT, 'The user identifier'),
                'cidsource' => new external_value(PARAM_TEXT, 'The source for the course'),
                'cid' => new external_value(PARAM_TEXT, 'The course identifier'),
                'underratioscope' => new external_value(PARAM_TEXT, 'The scope of the ratio examined for filtering per ratio, either \'optional\' or \'mandatory\' or \'all\' ', VALUE_DEFAULT, ''),
                'underratio' => new external_value(PARAM_INT, 'If score is over this ratio, will not be reported', VALUE_DEFAULT, 0),
            )
        );
    }

    public static function get_user_data_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_INT, 'User id'),
                    'useridnumber' => new external_value(PARAM_TEXT, 'User idnumber'),
                    'username' => new external_value(PARAM_TEXT, 'User username'),
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'courseidnumber' => new external_value(PARAM_TEXT, 'Course idnumber'),
                    'courseshortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                    'coursefullname' => new external_value(PARAM_TEXT, 'Course fullname'),
                    'ltcid' => new external_value(PARAM_INT, 'LTC instance id'),
                    'ltcidnumber' => new external_value(PARAM_TEXT, 'LTC cm idnumber'),
                    'ltcname' => new external_value(PARAM_TEXT, 'LTC full name'),
                    'mandatoryitems' => new external_value(PARAM_INT, 'Number of mandatory items in this LTC'),
                    'optionalitems' => new external_value(PARAM_INT, 'Number of optional items in this LTC'),
                    'mandatorychecks' => new external_value(PARAM_INT, 'Number of mandatory items marked in this LTC'),
                    'optionalchecks' => new external_value(PARAM_INT, 'Number of optional items marked in this LTC'),
                    'mandatoryratio' => new external_value(PARAM_INT, 'Ratio of mandatory items in this LTC'),
                    'optionalratio' => new external_value(PARAM_INT, 'Ratio of optional items in this LTC'),
                    'overalratio' => new external_value(PARAM_INT, 'Ratio of marked items in LTC'),
                    'mandatorycredittime' => new external_value(PARAM_INT, 'Available credit time on mandatory items in this LTC'),
                    'optionalcredittime' => new external_value(PARAM_INT, 'Available credit time on optional items in this LTC'),
                    'mandatoryacquiredtime' => new external_value(PARAM_INT, 'Acquired time in secs by mandatory items in this LTC'),
                    'optionalacquiredtime' => new external_value(PARAM_INT, 'Acquired time in secs by optional items in this LTC'),
                ),
            "An array of results for one single LTC")
        );
    }

    /* ******************************************* Get a set of users data ****************************************** */

    public static function get_users_data($uidsource, $uids, $cidsource, $cid, $underratioscope = '', $underratio = 0) {
        global $CFG;

        if (report_learningtimecheck_supports_feature('export/ws')) {
            include_once($CFG->dirroot.'/report/learningtimecheck/pro/externallib.php');
            return report_learningtimecheck_external_pro::get_users_data($uidsource, $uids, $cidsource, $cid, $underratioscope, $underratio);
        } else {
            throw new moodle_exception('WS only provided in "pro" versions. Please contact distributors (see global settings).');
        }
    }

    public static function get_users_data_parameters() {
        return new external_function_parameters(
            array(
                'uidsource' => new external_value(PARAM_TEXT, 'The source for user id'),
                'uids' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'an array of user identifiers')
                ),
                'cidsource' => new external_value(PARAM_TEXT, 'The source for the course'),
                'cid' => new external_value(PARAM_TEXT, 'The course identifier'),
                'underratioscope' => new external_value(PARAM_TEXT, 'The scope of the ratio examined for filtering per ratio, either \'optional\' or \'mandatory\' or \'all\' ', VALUE_DEFAULT, ''),
                'underratio' => new external_value(PARAM_INT, 'If score is over this ratio, will not be reported', VALUE_DEFAULT, 0),
            )
        );
    }

    public static function get_users_data_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'userid' => new external_value(PARAM_INT, 'User id'),
                    'useridnumber' => new external_value(PARAM_TEXT, 'User idnumber'),
                    'username' => new external_value(PARAM_TEXT, 'User username'),
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'courseidnumber' => new external_value(PARAM_TEXT, 'Course idnumber'),
                    'courseshortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                    'coursefullname' => new external_value(PARAM_TEXT, 'Course fullname'),
                    'ltcid' => new external_value(PARAM_INT, 'LTC instance id'),
                    'ltcidnumber' => new external_value(PARAM_TEXT, 'LTC cm idnumber'),
                    'ltcname' => new external_value(PARAM_TEXT, 'LTC full name'),
                    'mandatoryitems' => new external_value(PARAM_INT, 'Number of mandatory items in this LTC'),
                    'optionalitems' => new external_value(PARAM_INT, 'Number of optional items in this LTC'),
                    'mandatorychecks' => new external_value(PARAM_INT, 'Number of mandatory items marked in this LTC'),
                    'optionalchecks' => new external_value(PARAM_INT, 'Number of optional items marked in this LTC'),
                    'mandatoryratio' => new external_value(PARAM_INT, 'Ratio of mandatory items in this LTC'),
                    'optionalratio' => new external_value(PARAM_INT, 'Ratio of optional items in this LTC'),
                    'overalratio' => new external_value(PARAM_INT, 'Ratio of marked items in LTC'),
                    'mandatorycredittime' => new external_value(PARAM_INT, 'Available credit time on mandatory items in this LTC'),
                    'optionalcredittime' => new external_value(PARAM_INT, 'Available credit time on optional items in this LTC'),
                    'mandatoryacquiredtime' => new external_value(PARAM_INT, 'Acquired time in secs by mandatory items in this LTC'),
                    'optionalacquiredtime' => new external_value(PARAM_INT, 'Acquired time in secs by optional items in this LTC'),
                ),
            "An array of results for a set of users in a course")
        );
    }

    /* ******************************************* Validations ****************************************** */

    protected static function validate_course_parameters($parameters) {
        global $DB;

        if (!in_array($parameters['cidsource'], array('', 'id', 'idnumber', 'shortname'))) {
            throw invalid_parameter_exception('course source not in expected range');
        }

        switch ($parameters['cidsource']) {
            case '':
                return null;
                break;

            case 'id':
                $course = $DB->get_record('course', array('id' => $parameters['cid']));
                if (!$course) {
                    throw new invalid_parameter_exception('Invalid course by id '.$parameters['cid']);
                }
                break;

            case 'idnumber':
                $course = $DB->get_record('course', array('idnumber' => $parameters['cid']));
                if (!$course) {
                    throw new invalid_parameter_exception('Invalid course by idumber '.$parameters['cid']);
                }
                break;

            case 'shortname':
                $course = $DB->get_record('course', array('shortname' => $parameters['cid']));
                if (!$course) {
                    throw new invalid_parameter_exception('Invalid course by shortname '.$parameters['cid']);
                }
        }

        return $course;
    }

    protected static function validate_user_parameters($parameters) {
        global $DB;

        if (!in_array($parameters['uidsource'], array('', 'id', 'username', 'idnumber', 'email'))) {
            throw invalid_parameter_exception('user source not in expected range');
        }

        switch ($parameters['uidsource']) {
            case '':
                return null;
                break;

            case 'id':
                $user = $DB->get_record('user', array('id' => $parameters['uid']));
                if (!$user) {
                    throw new invalid_parameter_exception('Invalid user by id '.$parameters['uid']);
                }
                break;

            case 'idnumber':
                $user = $DB->get_record('user', array('idnumber' => $parameters['uid']));
                if (!$user) {
                    throw new invalid_parameter_exception('Invalid user by idumber '.$parameters['uid']);
                }
                break;

            case 'username':
                $user = $DB->get_record('user', array('username' => $parameters['uid']));
                if (!$user) {
                    throw new invalid_parameter_exception('Invalid user by username '.$parameters['uid']);
                }
                break;

            case 'email':
                $user = $DB->get_record('user', array('email' => $parameters['uid']));
                if (!$user) {
                    throw new invalid_parameter_exception('Invalid user by email '.$parameters['uid']);
                }
        }

        return $user;
    }
}

