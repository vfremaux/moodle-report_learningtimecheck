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
 * Course learningtimecheck user report view
 *
 * Aggregate all courses of the user having learningtime checks
 *
 * @package    report
 * @version    moodle 2.x
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$thisurl = new moodle_url('/report/learningtimecheck/index.php');
require_once $CFG->dirroot.'/report/learningtimecheck/forms/search_profilefield_form.php'; 
require_once $CFG->dirroot.'/report/learningtimecheck/lib.php'; 
require_once $CFG->dirroot.'/mod/learningtimecheck/renderer.php';
require_once $CFG->dirroot.'/report/learningtimecheck/renderers.php';
require_once $CFG->dirroot.'/mod/learningtimecheck/locallib.php';

$profilefieldid = optional_param('profilefield', null, PARAM_INT);
$conditions = optional_param('conditions', null, PARAM_TEXT);

$candidates = array();

if (!has_capability('report/learningtimecheck:viewother', $context)) {
    print_error('noaccess', 'report_learningtimecheck');
}

$profilefieldmenu = $DB->get_records_menu('user_info_field', array(), 'name', 'id,name');
$profilefield = $DB->get_record('user_info_field', array('id' => $profilefieldid));

$form = new SearchProfileForm($thisurl, array('profilefields' => $profilefieldmenu));

$context = context_system::instance();

$formdata = new StdClass();
$formdata->id = $COURSE->id;
$formdata->conditions = $conditions;
$formdata->profilefieldid = $profilefieldid;

$form->set_data($formdata);

if ($data = $form->get_data()) {

    if (preg_match('/^[><=]+\s/', $conditions)) {
        $valueconditionclause = ' ua.data '.$conditions;
    } elseif (preg_match('/^%(.*)%$/', $conditions, $matches)) {
        $valueconditionclause = ' ua.data REGEXP \''.$matches[1].'\'';
    } elseif (is_numeric($conditions)) {
        $valueconditionclause = ' ua.data = '.$conditions;
    } else {
        $valueconditionclause = ' ua.data = \''.$conditions.'\'';
    }

    $profilefield->condition = $valueconditionclause;

    $sql = "
        SELECT
            u.*
        FROM 
            {user} u,
            {user_info_data} ua
        WHERE
            ua.userid = u.id AND
            ua.fieldid = ? AND
            $valueconditionclause
    ";
    echo $sql;
    $candidates = $DB->get_records_sql($sql, array($profilefieldid));
} else {
    echo $OUTPUT->heading(get_string('searchbyprofile', 'report_learningtimecheck'));
}

$form->display();

if (!has_capability('report/learningtimecheck:viewother', $context)) {
     print_error('You have no system wide permission to view cohort results');
}

if ($candidates) {
    echo $OUTPUT->heading(get_string('profilefieldreport', 'report_learningtimecheck', $profilefield));
    $table = report_learningtimecheck_cohort_results($id, $candidates);

    $params = array('profilefield' => $profilefieldid, 'conditions' => $conditions);

    echo report_learningtimecheck_renderer::print_export_excel_button('profile', $params, false);
    echo report_learningtimecheck_renderer::print_export_excel_button('profile', $params, true);
    echo report_learningtimecheck_renderer::print_export_pdf_button('profile', $params, false);
    echo report_learningtimecheck_renderer::print_export_pdf_button('profile', $params, true);
    echo report_learningtimecheck_renderer::print_back_search_button('profile', $id);
    echo report_learningtimecheck_renderer::print_user_options_button('profile', $id, $params);
    $options = report_learningtimecheck_get_user_options();
    echo report_learningtimecheck_renderer::print_send_to_batch_button('profile', $id, $params, $options);

    echo $OUTPUT->box_start('learningtimecheck-progressbar');
    echo html_writer::table($table);
    echo $OUTPUT->box_end();
} else {
    echo $OUTPUT->box(get_string('noresults'));
    echo report_learningtimecheck_renderer::print_back_search_button('profile', $id);
}
