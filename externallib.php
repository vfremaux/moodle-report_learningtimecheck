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

    public static function get_user_data($userid, $useridnumber = false, $courseid = false) {
        // invoke in silent mode
    }

    public static function get_user_data_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'userid'),
                'useridnumber' => new external_value(PARAM_TEXT, 'useridnumber'),
                'courseid' => new external_value(PARAM_INT, 'courseid'),
            )
        );
    }

    public static function get_user_data_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_RAW, 'Json data'),
                'tokenkey' => new external_value(PARAM_RAW, 'a Token key for ensuring security'),
            )
        );
    }

}