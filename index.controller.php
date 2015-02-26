<?php

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