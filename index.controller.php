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

switch($action) {

    case 'newfilterrule':
        $rule = new StdClass;
        $rule->rule = required_param('rule', PARAM_TEXT);
        $rule->ruleop = required_param('ruleop', PARAM_TEXT);
        $rule->logop = optional_param('logop', '', PARAM_TEXT);
        $rule->datetime = required_param('datetime', PARAM_TEXT);

        if (!$rule->datetime) {
            redirect($url.'&view='.$view.'&filtererror=errornodate');
        }

        $rules = @$SESSION->learningtimecheck->filterrules;

        if ($rules) {
            // Check for having th logop
            if (empty($rule->logop)) {
                redirect($thisurl.'&view='.$view.'&filtererror=errornologop');
            }

            $rule->id = count($rules) + 1;
        } else {
            $rule->id = 1;
            if (!isset($SESSION->learningtimecheck)) {
                $SESSION->learningtimecheck = new Stdclass;
            }
            $SESSION->learningtimecheck->filterrules = array();
        }
        $SESSION->learningtimecheck->filterrules[$rule->id] = $rule;
        redirect($thisurl.'&view='.$view);
        break;

    case 'deleterule':
        $ruleid = required_param('ruleid', PARAM_INT);
        if ($rules = @$SESSION->learningtimecheck->filterrules) {
            unset($rules[$ruleid]);
            if (!empty($rules)) {
                $i = 1;
                $updatedrules = array();
                foreach ($rules as $r) {
                    $r->id = $i;
                    $updatedrules[$i] = $r;
                    $i++;
                }
                $SESSION->learningtimecheck->filterrules = $updatedrules;
            } else {
                unset($SESSION->learningtimecheck->filterrules);
            }
        }
        break;
}