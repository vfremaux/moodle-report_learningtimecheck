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

require_once($CFG->dirroot.'/user/selector/lib.php');

class wdmarks_generator_user_selector extends user_selector_base {

    public function find_users($search) {
        global $DB;

        $select = " firstname LIKE '%$search%' OR lastname LIKE '%$search%' ";
        $fields = $this->required_fields_sql('');
        $users = $DB->get_records_select('user', $select, array(), 'lastname,firstname', $fields);
        return array('' => $users);
    }
}

function learningtimecheck_generate_wdmark_events_from_session(&$user, &$config) {
    global $SESSION;

    $result = '';

    $start = $SESSION->generatemarks->fromdate;
    $end = $SESSION->generatemarks->todate;

    while ($start <= $end) {
        // Generate from start to end.
        $parts = getdate($start);
        if (!empty($SESSION->generatemarks->days[$parts['wday']])) {
            // Generate only on allowed week days.
            if (report_learningtimecheck_generate_event($user, $parts['mday'], $parts['mon'], $parts['year'])) {
                $result .= "Generated for {$user->username} at ".date('d-m-Y', $start).'<br/>';
            }
        }
        $start += DAYSECS;
    }

    return $result;
}