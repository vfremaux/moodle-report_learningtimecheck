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
 * Course learningtimecheck user report view
 *
 * Aggregate all courses of the user having learningtime checks
 *
 * @package    report
 * @version    moodle 2.x
 * @subpackage learningtimecheck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/report/learningtimecheck/forms/search_user_form.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

$itemid = optional_param('itemid', null, PARAM_INT); // Item is a user
$page = optional_param('page', false, PARAM_INT);
$searchpattern = optional_param('searchpattern', '', PARAM_TEXT);
$allgroupsaccess = has_capability('moodle/site:accessallgroups', $context);
$mygroups = groups_get_user_groups($fromcourse->id, $USER->id);
$pagesize = 50;
$alluserscount = 0;

// echo "$itemid/$page/$searchpattern";

if (empty($itemid) && has_capability('report/learningtimecheck:viewother', $context)) {

    $form = new SearchUserForm();
    $formdata = array('searchpattern' => $searchpattern);
    $form->set_data($formdata);

    $pagerecnum = $page * $pagesize;

    $searchpattern = core_text::strtoupper($searchpattern);

    if (($page !== false) || !empty($searchattern) || !empty($itemid)) {
         $select = "(UPPER(firstname) LIKE '%{$searchpattern}%' OR UPPER(lastname) LIKE '%{$searchpattern}%') AND deleted = 0";
         $alluserscount = $DB->count_records_select('user', $select, array());
         $results = $DB->get_records_select('user', $select, array(), 'lastname, firstname', 'id,'.get_all_user_name_fields(true, ''), $pagerecnum, $pagesize);
    }

    $formdata = new Stdclass;
    $formdata->id = $id;
    $form->set_data($formdata);
    $form->display();

    echo $OUTPUT->heading(get_string('results', 'report_learningtimecheck'));

    if (!empty($results)) {

        foreach ($results as $uid => $user) {
            if (!report_learningtimecheck::is_user_visible($user, $allgroupsaccess, $mygroups)) {
                $alluserscount --;
                unset($results[$uid]);
                continue;
            }
        }
    }

    if (!empty($results)) {
        $table = new html_table();
        $table->head = array('');
        $table->width = '100%';

        foreach ($results as $uid => $user) {
            if (!report_learningtimecheck::is_user_visible($user, $allgroupsaccess, $mygroups)) {
                $alluserscount --;
                continue;
            }
            $table->data[] = array('<a href="'.$thisurl.'?id='.$id.'&view=user&itemid='.$user->id.'">'.fullname($user).'</a>');
        }

        echo $OUTPUT->paging_bar($alluserscount, $page, $pagesize, $thisurl.'&searchpattern='.$searchpattern);

        echo html_writer::table($table);

        echo $OUTPUT->paging_bar($alluserscount, $page, $pagesize, $thisurl.'&searchpattern='.$searchpattern);

    } else {
        echo $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'));
    }
    echo $OUTPUT->footer();
    die;
}

if ($itemid != $USER->id && !has_capability('report/learningtimecheck:viewother', $context)) {
      $itemid = $USER->id;
}

$user = $DB->get_record('user', array('id' => $itemid));

echo $OUTPUT->user_picture($user, array('size' => 45));
echo $OUTPUT->heading(get_string('userreport', 'report_learningtimecheck', $user));
echo $reportrenderer->options('user', $id, $itemid);
$useroptions = report_learningtimecheck::get_user_options();

$usertable = report_learningtimecheck::user_results_by_course($id, $user, $globals, $useroptions, true);

echo '<div id="report-learningtimecheck-buttons">';
echo $reportrenderer->print_export_excel_button($id, 'user', $itemid);
echo $reportrenderer->print_export_pdf_button($id, 'user', $itemid);
echo $reportrenderer->print_export_pdf_button($id, 'usercursus', $itemid, false, null, get_string('exportpdfcursus', 'report_learningtimecheck'));
echo $reportrenderer->print_back_search_button('user', $id);
echo $reportrenderer->print_send_to_batch_button('user', $id, $itemid, $useroptions);
echo '</div>';

echo $OUTPUT->box_start('learningtimecheck-progressbar');
echo html_writer::table($usertable);
echo $OUTPUT->box_end();
