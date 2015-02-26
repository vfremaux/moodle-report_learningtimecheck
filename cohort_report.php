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

require_once($CFG->dirroot.'/report/learningtimecheck/forms/search_cohort_form.php'); 
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php'); 
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

if (empty($itemid) && has_capability('report/learningtimecheck:viewother', $context)) {

     $form = new SearchCohortForm();

     $context = context_system::instance();

     if ($data = $form->get_data()) {
         $select = " name LIKE '%{$data->searchpattern}%' ";
         $results = $DB->get_records_select('cohort', $select, array('contextid' => $context->id), 'name', 'id,name,description');
     } else {
         $results = $DB->get_records('cohort', array('contextid' => $context->id), 'name', '*', 0,10);
     }

    $formdata = new Stdclass;
    $formdata->id = $id;
    $form->set_data($formdata);
    $form->display();

    echo $OUTPUT->heading(get_string('results', 'report_learningtimecheck'));

    if (!empty($results)) {
        $table = new html_table();
        $table->head = array('');
        $table->width = '100%';

        foreach($results as $cid => $cohort) {
            $countusers = $DB->count_records('cohort_members', array('cohortid' => $cohort->id));
            $table->data[] = array('<a href="'.$thisurl->out_omit_querystring().'?id='.$id.'&view=cohort&itemid='.$cohort->id.'">'.$cohort->name.'</a> ('.$countusers.' '.get_string('users').')<br>'.$cohort->description);
        }

        echo html_writer::table($table);

    } else {
        echo $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'));
    }
    echo $OUTPUT->footer();
    die;

}

$cohort = $DB->get_record('cohort', array('id' => $itemid));

if (!has_capability('report/learningtimecheck:viewother', $context)) {
    print_error('You have no system wide permission to view cohort results');
}

$ltcrenderer = $PAGE->get_renderer('mod_learningtimecheck');
$cohortmembers = report_learningtimecheck_get_cohort_users($itemid);
echo $ltcrenderer->print_event_filter($thisurl, $thisurl, 'report', $view, $itemid);
learningtimecheck_apply_rules($cohortmembers);

if ($cohortmembers) {

    $table = report_learningtimecheck_cohort_results($id, $cohortmembers);

    echo $OUTPUT->heading(get_string('cohortreport', 'report_learningtimecheck', $cohort));

    echo $reportrenderer->print_export_excel_button($id, 'cohort', $itemid, false);
    echo $reportrenderer->print_export_excel_button($id, 'cohort', $itemid, true);
    echo $reportrenderer->print_export_pdf_button($id, 'cohort', $itemid, false);
    echo $reportrenderer->print_export_pdf_button($id, 'cohort', $itemid, true);
    echo $reportrenderer->print_back_search_button('cohort', $id);
    echo $reportrenderer->print_user_options_button('cohort', $id, $itemid);
    $options = report_learningtimecheck_get_user_options();
    echo $reportrenderer->print_send_to_batch_button('cohort', $id, $itemid, $options);
    echo $reportrenderer->print_send_detail_to_batch_button('cohort', $id, $itemid, $options);

    echo $OUTPUT->box_start('learningtimecheck-progressbar');
    echo html_writer::table($table);
    echo $OUTPUT->box_end();
} else {
    echo $OUTPUT->box(get_string('noresults'));
}
