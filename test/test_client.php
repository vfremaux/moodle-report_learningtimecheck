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
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


class test_client {

    protected $t; // target.

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = 'dd969135dcb56e5dced63bbf2834e95c'; // the service token for access.
        $this->t->filepath = ''; // Some physical location on your system.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
    }

    public function test_get_user_data($cidsource = '', $cid = 0, $uidsource = '', $uid = 0, $underratioscope = '', $underratio = 0) {

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'report_learningtimecheck_get_user_data',
                        'moodlewsrestformat' => 'json',
                        'cidsource' => $cidsource,
                        'cid' => $cid,
                        'uidsource' => $uidsource,
                        'uid' => $uid,
                        'underratioscope' => $underratioscope,
                        'underratio' => $underratio);

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_get_users_data($cidsource = '', $cid = 0, $uidsource = '', $uids = array(), $underratioscope = '', $underratio = 0) {

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'report_learningtimecheck_get_users_data',
                        'moodlewsrestformat' => 'json',
                        'cidsource' => $cidsource,
                        'cid' => $cid,
                        'uidsource' => $uidsource,
                        'uids' => $uids,
                        'underratioscope' => $underratioscope,
                        'underratio' => $underratio);

        print_r($params);

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }


    protected function send($serviceurl, $params) {
        $ch = curl_init($serviceurl);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        echo "Firing CUrl $serviceurl ... \n";
        if (!$result = curl_exec($ch)) {
            echo "CURL Error : ".curl_errno($ch).' '.curl_error($ch)."\n";
            return;
        }

        echo $result;
        if (preg_match('/EXCEPTION/', $result)) {
            echo $result;
            return;
        }

        $result = json_decode($result);
        print_r($result);
        return $result;
    }
}

// Effective test scénario

$client = new test_client();

$ix = 1;

echo "\n\nTest $ix ###########";
$client->test_get_user_data('id', 2, 'id', 3); // Test a user a course.
$client->test_get_user_data('id', 2); // Test one course all users.
$client->test_get_user_data('', '', 'id', 3); // Test one user, all courses.
$client->test_get_user_data('', '', 'id', 3, 'all', 50); // Test all user having less or equal to 50%

$ix++;

echo "\n\nTest $ix ###########";
$client->test_get_users_data('id', '2', 'id', array(2, 3, 4, 5)); // Test a set of users, one course.
