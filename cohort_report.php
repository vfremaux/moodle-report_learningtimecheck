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
 * Course learningtimecheck cohort report view
 *
 * Aggregate all achievement summaries of a cohort
 *
 * @package    report
 * @version    moodle 2.x
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/report/learningtimecheck/forms/search_cohort_form.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

require_capability('report/learningtimecheck:viewother', $viewcontext);

if (empty($itemid)) {

     $form = new SearchCohortForm();

    $contextsystem = context_system::instance();

    if ($data = $form->get_data()) {
        $select = " name LIKE ? AND (contextid = ? OR contextid = ?) ";
        $params = ["%{$data->searchpattern}%", $context->id, $contextsystem->id];
        $results = $DB->get_records_select('cohort', $select, $params, 'name', 'id,name,description');
        $canhavemore = false;
    } else {
        $results = $DB->get_records_select('cohort', "contextid = ? OR contextid = ?", [$context->id, $contextsystem->id], 'name', '*', 0, 10);
        $canhavemore = true;
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

        foreach ($results as $cid => $cohort) {
            $countusers = $DB->count_records('cohort_members', array('cohortid' => $cohort->id));
            $linkurl = $thisurl->out_omit_querystring().'?id='.$id.'&view=cohort&itemid='.$cohort->id;
            $link = '<a href="'.$linkurl.'">'.$cohort->name.'</a> ('.$countusers.' '.get_string('users').')<br>';
            $link .= $cohort->description;
            $table->data[] = array($link);
        }

        echo html_writer::table($table);

        if ($canhavemore) {
            echo '...<br/>';
        }

    } else {
        echo $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'));
    }
    echo $OUTPUT->footer();
    die;
}

$cohort = $DB->get_record('cohort', array('id' => $itemid));

$renderer = $PAGE->get_renderer('mod_learningtimecheck');
$cohortmembers = report_learningtimecheck::get_cohort_users($itemid);
echo $renderer->print_event_filter($thisurl, $thisurl, 'report', $view, $itemid);
echo $reportrenderer->options($view, $id, $itemid);
$useroptions = report_learningtimecheck::get_user_options();

learningtimecheck_apply_rules($cohortmembers);

if ($cohortmembers) {

    $table = report_learningtimecheck::cohort_results($id, $cohortmembers, $globals, $useroptions);

    echo $OUTPUT->heading(get_string('cohortreport', 'report_learningtimecheck', $cohort));

    echo '<div id="report-learningtimecheck-buttons">';
    echo $reportrenderer->print_export_excel_button($id, 'cohort', $itemid, false);
    echo $reportrenderer->print_export_excel_button($id, 'cohort', $itemid, true);
    echo $reportrenderer->print_export_pdf_button($id, 'cohort', $itemid, false);
    echo $reportrenderer->print_export_pdf_button($id, 'cohort', $itemid, true);
    echo $reportrenderer->print_back_search_button('cohort', $id);
    echo $reportrenderer->print_send_to_batch_button('cohort', $id, $itemid, $useroptions);
    echo $reportrenderer->print_send_detail_to_batch_button('cohort', $id, $itemid, $useroptions);
    echo '</div>';

    echo $OUTPUT->box_start('learningtimecheck-progressbar');
    echo html_writer::table($table);
    echo $OUTPUT->box_end();
} else {
    echo $OUTPUT->box(get_string('noresults'));
}
