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

defined('MOODLE_INTERNAL') || die();

/**
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->libdir.'/externallib.php');

class report_learningtimecheck_external extends external_api {

    public static function get_user_report($userid, $useridnumber = false, $courseid = false) {
        // invoke in silent mode

        // Ensure the current user is allowed to run this function
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/learningtimecheck:viewother', $context);

        // Do basic automatic PARAM checks on incoming data, using params description
        // If any problems are found then exceptions are thrown with helpful error messages
        $params = self::validate_parameters(self::get_user_report_parameters(), array('userid' => $userid, 'useridnumber' => $useridnumber, 'courseid' => $courseid));

        if (!empty($params['useridnumber'])) {
            $user = $DB->get_record('user', array('idnumber' => $params['useridnumber']));
            $userid = $user->id;
        } else {
            $userid = $params['userid'];
        }

        // Emulate a userdetail job on a given course.
        $job = new StdClass;
        $job->type = 'userdetail';
        $job->itemids = $userid;
        $job->filters = json_encode(array());
        $job->detail = true;
        $job->courseid = $params['courseid'];
        $data = array();
        $globals = array();
        report_learningtimecheck_prepare_data($job, $data, $globals);

        $exportclassfile = $CFG->dirroot.'/report/learningtimecheck/export/'.$output.'.class.php';
        if (!file_exists($exportclassfile)) {
            print_error('errornoexporterclass', 'report_learningtimecheck', $exportclassfile);
        }
        require_once($exportclassfile);

        $exportcontext = new StdClass();
        $exportcontext->exporttype = $exporttype;
        $exportcontext->exportitem = $exportitem;
        $exportcontext->param = $courseid;

        $classname = $output.'_exporter';
        $exporter = new $classname($exportcontext);

        $exporter->set_data($data, $globals);

        // Output production.
        $filecontent = $exporter->output();

        // TODO : Record the content obtained into a file into a temporary file area
        // generate a token for accessing the file and give back a pluginfile.php url
        // opened by the token.

        return array('', '');
    }

    public static function get_user_report_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid'),
                'useridnumber' => new external_value(PARAM_TEXT, 'useridnumber'),
                'courseid' => new external_value(PARAM_INT, 'courseid'),
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
        global $DB;

        // First proceed to standard validation (param arity, param data type).
        $parameters = array('uidsource' => $uidsource,
                            'uid' => $uid,
                            'cidsource' => $cidsource,
                            'cid' => $cid,
                            'underratioscope' => $underratioscope,
                            'underratio' => $underratio);

        $params = self::validate_parameters(self::get_user_data_parameters(), $parameters);

        // Fetch the course object.
        $parameters = array('cidsource' => $cidsource,
                            'cid' => $cid);
        $course = self::validate_course_parameters($parameters);

        // Fetch the user object.
        $parameters = array('uidsource' => $uidsource,
                            'uid' => $uid);
        $user = self::validate_user_parameters($parameters);

        $results = array();

        if ($user && !$course) {
            // Fetch all ltcs in all use courses
            $usercourses = enrol_get_users_courses($user->id);

            foreach ($usercourses as $cid => $ucourse) {
                $ltcs = $DB->get_records('learningtimecheck', array('course' => $ucourse->id));
                if ($ltcs) {
                    foreach ($ltcs as $ltcrec) {

                        $results[] = self::get_ltc_data($ltcrec, $user, $ucourse);
                    }
                }
            }

        } else {
            $ltcs = $DB->get_records('learningtimecheck', array('course' => $course->id));
            if ($ltcs) {
                foreach ($ltcs as $ltcrec) {

                    $results[] = self::get_ltc_data($ltcrec, $user, $course);
                }
            }
        }

        if (!empty($underratioscope)) {
            if (!empty($results)) {
                $filteredresults = array();
                foreach ($results as $r) {
                    switch ($underratioscope) {
                        case 'all':
                            $baseratio = $r['overalratio'];
                            break;
                        case 'mandatory':
                            $baseratio = $r['mandatoryratio'];
                            break;
                        case 'optional':
                            $baseratio = $r['optionalratio'];
                            break;
                    }
                    if ($baseratio < $underratio) {
                        $filteredresults[] = $r;
                    }
                }
                return $filteredresults;
            }
        }

        return $results;
    }

    protected static function get_ltc_data(&$ltcrec, &$user, &$ucourse) {

        $cm = get_coursemodule_from_instance('learningtimecheck', $ltcrec->id);
        $ltc = new learningtimecheck_class($cm->id, $user->id, $ltcrec, $cm);

        $result = new StdClass;
        $result->userid = $user->id;
        $result->useridnumber = $user->idnumber;
        $result->username = $user->username;
        $result->courseid = $ucourse->id;
        $result->courseidnumber = $ucourse->idnumber;
        $result->courseshortname = $ucourse->shortname;
        $result->coursefullname = $ucourse->fullname;
        $result->ltcid = $ltc->id;
        $result->ltcidnumber = $cm->idnumber;
        $result->ltcname = format_string($ltc->name);
        $mi = $ltc->counters['mandatories'];
        $oi = $ltc->counters['optionals'];
        $mc = $ltc->counters['mandatorieschecked'];
        $oc = $ltc->counters['optionalschecked'];
        $result->mandatoryitems = $mi;
        $result->optionalitems = $oi;
        $result->mandatorychecks = $mc;
        $result->optionalchecks = $oc;
        $result->mandatoryratio = ($mi) ? round(($mc / $mi) * 100) : 0;
        $result->optionalratio = ($oi) ? round(($oc / $oi) * 100) : 0;
        $result->overalratio = ($mi + $oi) ? round(($mc + $oc) / ($mi + $oi) * 100): 0;

        $mct = $ltc->counters['mandatorycredittime'];
        $oct = $ltc->counters['optionalcredittime'];
        $mat = $ltc->counters['mandatoryacquiredtime'];
        $oat = $ltc->counters['optionalacquiredtime'];
        $result->mandatorycredittime = $mct;
        $result->optionalcredittime = $oct;
        $result->mandatoryacquiredtime = $mat;
        $result->optionalacquiredtime = $oat;

        return (array) $result;
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
                    throw new invalid_parameter_exception('Invalid course by idumber '.$parameters['cid']);
                }
        }

        return $course;
    }

    protected static function validate_user_parameters($parameters) {
        global $DB;

        if (!in_array($parameters['uidsource'], array('', 'id', 'username', 'username', 'email'))) {
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
                    throw new invalid_parameter_exception('Invalid user by username '.$parameters['uid']);
                }
        }

        return $user;
    }
}

