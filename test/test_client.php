<?php

class test_client {

    protected $t; // target.

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = '3b66dfd43773ea36126195e5af79109b'; // the service token for access.
        $this->t->filepath = ''; // Some physical location on your system.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';
    }

    public function test_get_user_data($courseidsource = '', $courseid = 0, $useridsource = '', $userid = 0, $underratioscope = '', $underratio = 0) {

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'report_learningtimecheck_get_user_data',
                        'moodlewsrestformat' => 'json',
                        'courseidsource' => $courseidsource,
                        'courseid' => $courseid,
                        'useridsource' => $useridsource,
                        'userid' => $userid,
                        'underratioscope' => $underratioscope,
                        'underratio' => $underratio);

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }


    protected function send($serviceurl, $params) {
        $ch = curl_init($serviceurl);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

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

$client->test_get_user_data('id', 2); // Test one course all users.
$client->test_get_user_data('', '', 'id', 3); // Test one user, all courses.
$client->test_get_user_data('id', 2, 'id', 3); // Test a user a course.
$client->test_get_user_data('', '', 'id', 3, 'all', 50); // Test all user having less or equal to 50%
