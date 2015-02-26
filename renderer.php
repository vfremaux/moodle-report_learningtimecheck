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

class report_learningtimecheck_renderer extends plugin_renderer_base {

    function print_export_excel_button($origincourseid, $type = 'user', $itemid = 0, $detail = false) {
        global $CFG;

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/export.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="exporttype" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$origincourseid.'" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        $str .= '<input type="hidden" name="output" value="xls" />';
        if ($detail) {
            $str .= '<input type="hidden" name="detail" value="1" />';
            $str .= ' <input type="submit" name="export" value="'.get_string('exportxlsdetail', 'report_learningtimecheck').'" />';
        } else {
            $str .= '<input type="hidden" name="detail" value="0" />';
            $str .= ' <input type="submit" name="export" value="'.get_string('exportxls', 'report_learningtimecheck').'" />';
        }
        $str .= '</form>';

        return $str;
    }

    function print_export_pdf_button($origincourseid, $type = 'user', $itemid = 0, $detail = false, $options = null) {
        global $CFG;

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/export.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get" target="_blank">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="id" value="'.$origincourseid.'" />';
        $str .= '<input type="hidden" name="exporttype" value="'.$type.'" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        if (!empty($options)) {
            foreach($options as $optname => $optvalue) {
                $str .= '<input type="hidden" name="'.$optname.'" value="'.$optvalue.'" />';
            }
        }
        $str .= '<input type="hidden" name="output" value="pdf" />';
        if ($detail) {
            $str .= '<input type="hidden" name="detail" value="1" />';
            $str .= ' <input type="submit" name="export" value="'.get_string('exportpdfdetail', 'report_learningtimecheck').'" />';
        } else {
            $str .= '<input type="hidden" name="detail" value="0" />';
            $str .= ' <input type="submit" name="export" value="'.get_string('exportpdf', 'report_learningtimecheck').'" />';
        }
        $str .= '</form>';

        return $str;
    }

    function print_back_search_button($type, $id) {
        global $CFG;

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/index.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$id.'" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('backtoindex', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }

    function batch_list() {
        global $OUTPUT, $DB, $USER;

        $str = '';

        $str .= $OUTPUT->heading(get_string('globalbatchs', 'report_learningtimecheck'), 3);

        $sharedbatchs = $DB->get_records('report_learningtimecheck_btc', array('userid' => 0));

        if (empty($sharedbatchs)) {
            $str .= $OUTPUT->box(get_string('nobatchs', 'report_learningtimecheck'));
        } else {
            $table = new html_table();
            $table->head = array();
            $table->align = array();
            $table->size = array();
            foreach ($sharedbatchs as $batch) {
                $row = array();
                $fromnow = $batch->runtime - time();
                $row[] = userdate($batch->runtime).'<br/><span class="learningtimecheck-fromnow">'.get_string('fromnow', 'report_learningtimecheck', format_time($fromnow)).'</span>';
                $type = get_string($batch->type, 'report_learningtimecheck');
                if ($batch->detail) $type .= '<br/>('.get_string('detail', 'report_learningtimecheck').')';
                $row[] = $type;
                switch ($batch->type) {
                    case 'user':
                        $users = $DB->get_record_list('user', 'id', explode(',', $batch->itemids), 'id,firstname,lastname', 'lastname,firstname');
                        if ($users) {
                            $usernames = array();
                            foreach ($users as $u) {
                                $usernames[] = $u->lastname.' '.$u->firstname;
                            }
                            $row[] = implode(', ', $usernames);
                        }
                        break;
                    case 'course':
                        $courses = $DB->get_records_list('course', 'id', explode(',', $batch->itemids), 'id,shortname,fullname', '*');
                        if ($courses) {
                            $shortnames = array();
                            foreach ($courses as $c) {
                                $shortnames[] = $c->shortname;
                            }
                            $row[] = implode(', ', $shortnames);
                        }
                        break;
                    case 'cohort':
                        // Note we wont allow multiple cohort batch.
                        $cohort = $DB->get_record('cohort', array('id' => $batch->itemids), 'id,name');
                        $row[] = $cohort->name;
                        break;
                }
                $row[] = learningtimecheck_format_time($batch->repeatdelay, 'min');

                $table->data[] = $row;
            }
            $str .= html_writer::table($table);
        }

        $mybatchs = $DB->get_records('report_learningtimecheck_btc', array('userid' => $USER->id));

        $str .= $OUTPUT->heading(get_string('ownedbatchs', 'report_learningtimecheck'), 3);

        if (empty($mybatchs)) {
            $str .= $OUTPUT->box(get_string('nobatchs', 'report_learningtimecheck'));
        } else {
            $typestr = get_string('type', 'report_learningtimecheck');
            $itemnamestr = get_string('name');
            $repeatstr = get_string('repeat', 'report_learningtimecheck');
            $table = new html_table();
            $table->head = array('', $typestr, $itemnamestr, $repeatstr);
            $table->align = array();
            $table->size = array();
            foreach ($mybatchs as $batch) {
                $row = array();

                $fromnow = $batch->runtime - time();
                if ($fromnow > 0) {
                    $row[] = userdate($batch->runtime).'<br/><span class="learningtimecheck-fromnow">'.get_string('fromnow', 'report_learningtimecheck', format_time($fromnow)).'</span>';
                } else {
                    $row[] = userdate($batch->runtime).'<br/><span class="learningtimecheck-runnow">'.get_string('torun', 'report_learningtimecheck').'</span>';
                }
                $type = get_string($batch->type, 'report_learningtimecheck');
                if ($batch->detail) $type .= '<br/>('.get_string('detail', 'report_learningtimecheck').')';
                $row[] = $type;

                switch ($batch->type) {
                    case 'user':
                        $user = $DB->get_record('user', array('id' => $batch->itemid), 'id,firstname,lastname');
                        $row[] = fullname($user);
                        break;

                    case 'course':
                        $course = $DB->get_record('course', array('id' => $batch->itemid));
                        $row[] = '['.$course->shortname.'] '.$course->fullname;
                        break;

                    case 'cohort':
                        $cohort = $DB->get_record('cohort', array('id' => $batch->itemid), 'id,name');
                        $row[] = $cohort->name;
                        break;
                }
                $row[] = learningtimecheck_format_time($batch->repeatdelay, 'min');

                $table->data[] = $row;
            }
            $str .= html_writer::table($table);
        }

        return $str;
    }

    /**
     * a batch result filearea is a file storage area that stores all production from
     * a single batch. the widget prints a list of available areas and the content of one selected area
     * batch results are stored into a file area identified by : 
     *
     * Shared results :
     * context : systemcontext
     * component : report_learningtimecheck
     * filearea : batchresult
     * item : <batchstart timestamp>
     *
     * User specific results :
     * context : usercontext
     * component : report_learningtimecheck
     * filearea : batchresult
     * item : <batchstart timestamp>
     */
    public function batch_result_area() {
        global $DB, $USER, $OUTPUT;

        $str = '';

        $fs = get_file_storage();
        $systemcontext = context_system::instance();
        $usercontext = context_user::instance($USER->id);

        $sharedfileareas = $fs->get_area_files($systemcontext->id, 'report_learningtimecheck', 'batchresult');
        $ownedfileareas = $fs->get_area_files($usercontext->id, 'report_learningtimecheck', 'batchresult');

        $str .= $OUTPUT->heading(get_string('globalbatchs', 'report_learningtimecheck'), 3);

        if (!empty($sharedfileareas)) {
            foreach ($sharedfileareas as $file) {
                $filename = $file->get_filename();
                $url = moodle_url::make_file_url('/pluginfile.php', array($file->get_contextid(), 'report_learningtimecheck', 'batchesult', 0, $file->get_filepath(), $filename));
                echo html_writer::link($url, $filename);
            }
        } else {
            $str .= $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'), 'learningtimecheck-batch-noresults');
        }

        $str .= $OUTPUT->heading(get_string('ownedbatchs', 'report_learningtimecheck'), 3);
        
        if (!empty($ownedfileareas)) {
            foreach ($ownedfileareas as $file) {
                $filename = $file->get_filename();
                $url = moodle_url::make_file_url('/pluginfile.php', array($file->get_contextid(), 'report_learningtimecheck', 'batchesult', $file->get_itemid(), 0, $filename));
                echo html_writer::link($url, $filename);
            }
        } else {
            $str .= $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'), 'learningtimecheck-batch-noresults');
        }

        return $str;
    }

    public function print_user_options_button($type, $courseid, $itemid) {
        global $CFG;

        $str = '';

        $str .= '<form style="display: inline;" action="'.$CFG->wwwroot.'/report/learningtimecheck/options.php" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$courseid.'" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('changeoptions', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }

    public function print_send_to_batch_button($type, $courseid, $itemid, $params) {
        global $CFG;

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/batch.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="type" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$courseid.'" />';
        $str .= '<input type="hidden" name="detail" value="0" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        $str .= '<input type="hidden" name="params" value="'.base64_encode(json_encode($params)).'" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('sendtobatch', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }

    public function print_send_detail_to_batch_button($type, $courseid, $itemid, $params) {
        global $CFG;

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/batch.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="type" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$courseid.'" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        $str .= '<input type="hidden" name="detail" value="1" />';
        $str .= '<input type="hidden" name="params" value="'.base64_encode(json_encode($params)).'" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('senddetailtobatch', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }
}