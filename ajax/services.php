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
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

// Security.
require_login();

$url = new moodle_url('/report/learningtimecheck/ajax/services.php');
$PAGE->set_url($url);

$action = required_param('what', PARAM_TEXT);
$view = required_param('view', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);

switch ($view) {
    case 'course':
        $context = context_course::instance($id);
        break;

    case 'user':
        $context = context_user::instance($userid);
        break;

    case 'cohort':
    default:
        $context = context_system::instance();
}
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('mod_learningtimecheck');

if ($action == 'getfilterruleform') {
    $reporturl = new moodle_url('/report/learningtimecheck/index.php', array('id' => $id, 'view' => $view, 'itemid' => $itemid));
    echo $renderer->filter_rule_form($reporturl, $view);
}