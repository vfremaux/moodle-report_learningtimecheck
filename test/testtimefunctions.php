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

defined('MOODLE_INTERNAL') || die;

/**
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');

$url = new moodle_url('/report/learningtimecheck/test/testtimefunctions.php');

$systemcontext = context_system::instance();
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);

$config = new StdClass();
$config->workingtimestart_h = 8;
$config->workingtimestart_m = 0;
$config->workingtimeend_h = 18;
$config->workingtimeend_m = 30;

// Security.

require_login();
require_capability('moodle/site:config', $systemcontext);

echo $OUTPUT->header();

echo '<h2>Test : report_learningtimecheck::crop_session</h2>';

echo "<p>session 1 : start 02/01/2015 6:00 end 02/01/2015 7:00<br/>";
echo "<p>session 2 : start 02/01/2015 6:00 end 02/01/2015 10:00<br/>";
echo "<p>session 3 : start 02/01/2015 6:00 end 02/01/2015 19:00<br/>";
echo "<p>session 4 : start 02/01/2015 13:00 end 02/01/2015 15:00<br/>";
echo "<p>session 5 : start 02/01/2015 13:00 end 02/01/2015 19:00<br/>";
echo "<p>session 6 : start 02/01/2015 19:00 end 02/01/2015 21:00<br/>";
echo "valid range : 8h00 - 18h30</p>";

$session1 = new StdClass();
$session1->sessionstart = mktime(6, 0, 0, 1, 2, 2015);
$session1->sessionend = mktime(7, 0, 0, 1, 2, 2015);
$session1->elapsed = $session1->sessionend - $session1->sessionstart;

$session2 = new StdClass();
$session2->sessionstart = mktime(6, 0, 0, 1, 2, 2015);
$session2->sessionend = mktime(10, 0, 0, 1, 2, 2015);
$session2->elapsed = $session2->sessionend - $session2->sessionstart;

$session3 = new StdClass();
$session3->sessionstart = mktime(6, 0, 0, 1, 2, 2015);
$session3->sessionend = mktime(19, 0, 0, 1, 2, 2015);
$session3->elapsed = $session3->sessionend - $session3->sessionstart;

$session4 = new StdClass();
$session4->sessionstart = mktime(13, 0, 0, 1, 2, 2015);
$session4->sessionend = mktime(15, 0, 0, 1, 2, 2015);
$session4->elapsed = $session4->sessionend - $session4->sessionstart;

$session5 = new StdClass();
$session5->sessionstart = mktime(13, 0, 0, 1, 2, 2015);
$session5->sessionend = mktime(19, 0, 0, 1, 2, 2015);
$session5->elapsed = $session5->sessionend - $session5->sessionstart;

$session6 = new StdClass();
$session6->sessionstart = mktime(19, 0, 0, 1, 2, 2015);
$session6->sessionend = mktime(21, 0, 0, 1, 2, 2015);
$session6->elapsed = $session6->sessionend - $session6->sessionstart;

report_learningtimecheck::crop_session($session1, $config);
print_object($session1);
print_visible_dates($session1);

report_learningtimecheck::crop_session($session2, $config);
print_object($session2);
print_visible_dates($session2);

report_learningtimecheck::crop_session($session3, $config);
print_object($session3);
print_visible_dates($session3);

report_learningtimecheck::crop_session($session4, $config);
print_object($session4);
print_visible_dates($session4);

report_learningtimecheck::crop_session($session5, $config);
print_object($session5);
print_visible_dates($session5);

report_learningtimecheck::crop_session($session6, $config);
print_object($session6);
print_visible_dates($session6);

echo '<h2>Test : report_learningtimecheck::splice_session</h2>';

echo "<p>session 1 : start 02/01/2015 6:00 end 02/01/2015 7:00<br/>";
echo "<p>session 2 : start 02/01/2015 6:00 end 03/01/2015 6:00<br/>";
echo "<p>session 3 : start 02/01/2015 6:00 end 03/01/2015 10:00<br/>";
echo "<p>session 4 : start 02/01/2015 15:00 end 03/01/2015 7:00<br/>";
echo "<p>session 5 : start 02/01/2015 10:00 end 07/01/2015 10:00<br/>";

// Single day session
$session1 = new StdClass();
$session1->sessionstart = mktime(6, 0, 0, 1, 2, 2015);
$session1->sessionend = mktime(7, 0, 0, 1, 2, 2015);
$session1->elapsed = $session1->sessionend - $session1->sessionstart;

$session2 = new StdClass();
$session2->sessionstart = mktime(6, 0, 0, 1, 2, 2015);
$session2->sessionend = mktime(6, 0, 0, 1, 3, 2015);
$session2->elapsed = $session2->sessionend - $session2->sessionstart;

$session3 = new StdClass();
$session3->sessionstart = mktime(6, 0, 0, 1, 2, 2015);
$session3->sessionend = mktime(10, 0, 0, 1, 3, 2015);
$session3->elapsed = $session3->sessionend - $session3->sessionstart;

$session4 = new StdClass();
$session4->sessionstart = mktime(15, 0, 0, 1, 2, 2015);
$session4->sessionend = mktime(7, 0, 0, 1, 3, 2015);
$session4->elapsed = $session4->sessionend - $session4->sessionstart;

$session5 = new StdClass();
$session5->sessionstart = mktime(10, 0, 0, 1, 2, 2015);
$session5->sessionend = mktime(10, 0, 0, 1, 7, 2015);
$session5->elapsed = $session5->sessionend - $session5->sessionstart;

echo '<h3>single day session</h3>';
$sessions = report_learningtimecheck::splice_session($session1);
print_object($sessions);
foreach($sessions as $s) {
    print_visible_dates($s);
}

echo '<h3>one day session</h3>';
$sessions = report_learningtimecheck::splice_session($session2);
print_object($sessions);
foreach($sessions as $s) {
    print_visible_dates($s);
}

echo '<h3>one day session later start</h3>';
$sessions = report_learningtimecheck::splice_session($session3);
print_object($sessions);
foreach($sessions as $s) {
    print_visible_dates($s);
}

echo '<h3>two days session</h3>';
$sessions = report_learningtimecheck::splice_session($session4);
print_object($sessions);
foreach($sessions as $s) {
    print_visible_dates($s);
}

echo '<h3>five days session</h3>';
$sessions = report_learningtimecheck::splice_session($session5);
print_object($sessions);
foreach($sessions as $s) {
    print_visible_dates($s);
}

echo $OUTPUT->footer();

function print_visible_dates($session) {
    echo date('d/m/Y H:i:s', $session->sessionstart);
    echo ' - '.date('d/m/Y H:i:s', $session->sessionend).'</br/>';
}