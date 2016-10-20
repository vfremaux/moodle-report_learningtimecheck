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

class report_learningtimecheck_renderer extends plugin_renderer_base {

    function print_export_excel_button($origincourseid, $type = 'user', $itemid = 0, $detail = false) {
        global $CFG;

        $context = context_course::instance($origincourseid);
        if (!has_capability('report/learningtimecheck:export', $context)) {
            return;
        }

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

    function print_export_pdf_button($origincourseid, $type = 'user', $itemid = 0, $detail = false, $options = null, $alternatelabel = false) {
        global $CFG;

        $context = context_course::instance($origincourseid);
        if (!has_capability('report/learningtimecheck:export', $context)) {
            return;
        }

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
            $label = ($alternatelabel) ? $alternatelabel : get_string('exportpdfdetail', 'report_learningtimecheck') ;
            $str .= '<input type="hidden" name="detail" value="1" />';
            $str .= ' <input type="submit" name="export" value="'.$label.'" />';
        } else {
            $label = ($alternatelabel) ? $alternatelabel : get_string('exportpdf', 'report_learningtimecheck') ;
            $str .= '<input type="hidden" name="detail" value="0" />';
            $str .= ' <input type="submit" name="export" value="'.$label.'" />';
        }
        $str .= '</form>';

        return $str;
    }

    function print_back_search_button($type, $id) {
        global $CFG;

        $context = context_course::instance($id);
        if (!has_capability('report/learningtimecheck:viewother', $context)) {
            return;
        }

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/index.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$id.'" />';
        $str .= '<input type="hidden" name="search" value="1" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('backtoindex', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }

    function batch_list($origincourseid) {
        global $OUTPUT, $DB, $USER;

        $typestr = get_string('type', 'report_learningtimecheck');
        $itemnamestr = get_string('name');
        $repeatstr = get_string('repeat', 'report_learningtimecheck');
        $outputstr = get_string('output', 'report_learningtimecheck');

        $str = '';

        $str .= $OUTPUT->heading(get_string('globalbatchs', 'report_learningtimecheck'), 3);

        $sharedbatchs = $DB->get_records('report_learningtimecheck_btc', array('userid' => 0));

        if (empty($sharedbatchs)) {
            $str .= $OUTPUT->box(get_string('nobatchs', 'report_learningtimecheck'));
        } else {
            $table = new html_table();
            $table->head = array('', $typestr, $outputstr, $itemnamestr, $repeatstr);
            $table->align = array();
            $table->size = array();
            $table->width = '100%';
            foreach ($sharedbatchs as $batch) {
                $row = array();
                $fromnow = $batch->runtime - time();
                $row[] = userdate($batch->runtime).'<br/><span class="learningtimecheck-fromnow">'.get_string('fromnow', 'report_learningtimecheck', format_time($fromnow)).'</span>';
                $type = get_string($batch->type, 'report_learningtimecheck');
                if ($batch->detail) $type .= '<br/>('.get_string('detail', 'report_learningtimecheck').')';
                $row[] = $type;
                $row[] = $batch->output;
                switch ($batch->type) {
                    case 'user':
                        $users = $DB->get_records_list('user', 'id', explode(',', $batch->itemids), 'id,'.get_all_user_name_fields(true, ''), 'lastname,firstname');
                        if ($users) {
                            $usernames = array();
                            foreach ($users as $u) {
                                $usernames[] = $u->lastname.' '.$u->firstname;
                            }
                            $row[] = implode(', ', $usernames);
                        }
                        break;
                    case 'course':
                        $courses = $DB->get_records_list('course', 'id', explode(',', $batch->itemids), 'id,shortname,idnumber,fullname', '*');
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

                $deleteurl = new moodle_url('/report/learningtimecheck/batch.php', array('view' => 'batchs', 'id' => $origincourseid, 'what' => 'delete', 'batchid' => $batch->id, 'sesskey' => sesskey()));
                $cmd = '<a href="'.$deleteurl.'"><img src="'.$OUTPUT->pix_url('t/delete').'" /></a>';

                $runnowurl = new moodle_url('/report/learningtimecheck/batch_worker.php', array('id' => $origincourseid, 'joblist' => $batch->id, 'sesskey' => sesskey(), 'interactive' => 1));
                $cmd .= '<a href="'.$runnowurl.'" title="'.get_string('runnow', 'report_learningtimecheck').'" target="_blank"><img src="'.$OUTPUT->pix_url('t/reload').'" /></a>';

                $row[] = $cmd;
                $table->data[] = $row;
            }
            $str .= html_writer::table($table);
        }

        if (empty($seeprocessed)) {
            $newbatchsclause = ' AND processed IS NULL OR processed = 0 OR (processed > runtime AND repeatdelay > 0)';
        }

        $mybatchs = $DB->get_records_select('report_learningtimecheck_btc', " userid =  $USER->id $newbatchsclause");

        $str .= $OUTPUT->heading(get_string('ownedbatchs', 'report_learningtimecheck'), 3);

        if (empty($mybatchs)) {
            $str .= $OUTPUT->box(get_string('nobatchs', 'report_learningtimecheck'));
        } else {
            $table = new html_table();
            $table->head = array('', $typestr, $outputstr, $itemnamestr, $repeatstr);
            $table->align = array();
            $table->size = array();
            $table->width = '100%';
            foreach ($mybatchs as $batch) {
                $row = array();

                $fromnow = $batch->runtime - time();
                if ($fromnow > 0) {
                    $row[] = userdate($batch->runtime).'<br/><span class="learningtimecheck-fromnow">'.get_string('fromnow', 'report_learningtimecheck', format_time($fromnow)).'</span>';
                } else {
                    $row[] = userdate($batch->runtime).'<br/><span class="learningtimecheck-runnow">'.get_string('torun', 'report_learningtimecheck').'</span>';
                }
                $type = get_string($batch->type, 'report_learningtimecheck');
                if ($batch->detail) {
                    $type .= '<br/>('.get_string('detail', 'report_learningtimecheck').')';
                }
                $row[] = $type;
                $row[] = $batch->output;

                switch ($batch->type) {
                    case 'user':
                        $user = $DB->get_record('user', array('id' => $batch->itemids), 'id,firstname,lastname');
                        $row[] = $batch->name.'<br/>'.$user->lastname.' '.$user->firstname;
                        break;

                    case 'course':
                        $course = $DB->get_record('course', array('id' => $batch->itemids));
                        if (!$course) {
                            // Course has gone away
                            continue;
                        }
                        $row[] = $batch->name.'<br/>['.$course->shortname.'] '.$course->fullname;
                        break;

                    case 'cohort':
                        $cohort = $DB->get_record('cohort', array('id' => $batch->itemids), 'id,name');
                        if (!$cohort) {
                            // Cohort has gone away.
                            continue;
                        }
                        $row[] = $batch->name.'<br/>'.$cohort->name;
                        break;
                }
                $row[] = learningtimecheck_format_time($batch->repeatdelay, 'min');

                $deleteurl = new moodle_url('/report/learningtimecheck/batch.php', array('view' => 'batchs', 'id' => $origincourseid, 'what' => 'delete', 'batchid' => $batch->id, 'sesskey' => sesskey()));
                $cmd = '<a href="'.$deleteurl.'"><img src="'.$OUTPUT->pix_url('t/delete').'" /></a>';

                $runnowurl = new moodle_url('/report/learningtimecheck/batch_worker.php', array('id' => $origincourseid, 'joblist' => $batch->id, 'sesskey' => sesskey(), 'interactive' => 1));
                $cmd .= '<a href="'.$runnowurl.'" title="'.get_string('runnow', 'report_learningtimecheck').'" target="_blank"><img src="'.$OUTPUT->pix_url('t/reload').'" /></a>';

                $row[] = $cmd;

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

        $sharedfileareas = $fs->get_area_files($systemcontext->id, 'report_learningtimecheck', 'batchresult', false, false, false);
        $ownedfileareas = $fs->get_area_files($usercontext->id, 'report_learningtimecheck', 'batchresult', false, false, false);

        $str .= '<div class="span6 report-learningtimecheck-batchcell results">';
        $str .= $OUTPUT->heading(get_string('globalbatchs', 'report_learningtimecheck'), 3);

        if (!empty($sharedfileareas)) {
            foreach ($sharedfileareas as $file) {
                $filename = $file->get_filename();
                $url = moodle_url::make_pluginfile_url($file->get_contextid(), 'report_learningtimecheck', 'batchresult', 0, $file->get_filepath(), $filename);
                $str .= html_writer::link($url, $filename).'<br/>';
            }
        } else {
            $str .= $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'), 'learningtimecheck-batch-noresults');
        }

        $str .= '</div>';
        $str .= '<div class="span6 report-learningtimecheck-batchcell results">';
        $str .= $OUTPUT->heading(get_string('ownedbatchs', 'report_learningtimecheck'), 3);

        if (!empty($ownedfileareas)) {
            foreach ($ownedfileareas as $file) {
                $filename = $file->get_filename();
                $url = moodle_url::make_pluginfile_url($file->get_contextid(), 'report_learningtimecheck', 'batchresult', $file->get_itemid(), $file->get_filepath(), $filename);
                $str .= html_writer::link($url, $filename).'<br/>';
            }
        } else {
            $str .= $OUTPUT->box(get_string('noresults', 'report_learningtimecheck'), 'learningtimecheck-batch-noresults');
        }
        $str .= '</div>';

        return $str;
    }

    public function print_user_options_button($type, $courseid, $itemid, $return = '') {
        global $CFG;

        $context = context_course::instance($courseid);
        if (!has_capability('report/learningtimecheck:export', $context)) {
            return;
        }

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/options.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$courseid.'" />';
        $str .= '<input type="hidden" name="return" value="'.$return.'" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('changeoptions', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }

    public function print_send_to_batch_button($type, $courseid, $itemid, $params) {
        global $CFG;

        $context = context_course::instance($courseid);
        if (!has_capability('report/learningtimecheck:export', $context)) {
            return;
        }

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

        $context = context_course::instance($courseid);
        if (!has_capability('report/learningtimecheck:export', $context)) {
            return;
        }

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/batch.php');
        $str .= '<form style="display: inline;" action="'.$formurl.'" method="get">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$type.'" />';
        $str .= '<input type="hidden" name="type" value="'.$type.'" />';
        $str .= '<input type="hidden" name="id" value="'.$courseid.'" />';
        $str .= '<input type="hidden" name="param" value="'.$courseid.'" />';
        $str .= '<input type="hidden" name="itemid" value="'.$itemid.'" />';
        $str .= '<input type="hidden" name="detail" value="1" />';
        $str .= '<input type="hidden" name="params" value="'.base64_encode(json_encode($params)).'" />';
        $str .= ' <input type="submit" name="back" value="'.get_string('senddetailtobatch', 'report_learningtimecheck').'" />';
        $str .= '</form>';

        return $str;
    }
    
    public function batch_commands($view, $id) {

        $clearallstr = get_string('clearall', 'report_learningtimecheck');
        $clearallresultsstr = get_string('clearallresults', 'report_learningtimecheck');
        $clearownedstr = get_string('clearowned', 'report_learningtimecheck');
        $clearownedresultsstr = get_string('clearownedresults', 'report_learningtimecheck');
        $clearmarksstr = get_string('clearmarks', 'report_learningtimecheck');
        $addbatchstr = get_string('addbatch', 'report_learningtimecheck');
        $makebatchstr = get_string('makebatch', 'report_learningtimecheck');

        $str = '';

        $formurl = new moodle_url('/report/learningtimecheck/batch_controller.php');
        $str .= '<form name="batch_commands" action="'.$formurl.'" id="report-learningtimecheck-batch-commands">';
        $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $str .= '<input type="hidden" name="view" value="'.$view.'" />';
        $str .= '<input type="hidden" name="id" value="'.$id.'" />';
        $str .= '<input type="submit" name="clearowned" value="'.$clearownedstr.'"/>';
        // $str .= '<input type="submit" name="clearmarks" value="'.$clearmarksstr.'" />';
        $str .= '<input type="submit" name="addbatch" value="'.$addbatchstr.'" />';
        // $str .= '<input type="submit" name="makebatchfrommarks" value="'.$makebatchstr.'" />';
        $str .= '<input type="submit" name="clearownedresults" value="'.$clearownedresultsstr.'"/>';
        
        $systemcontext = context_system::instance();
        if (has_capability('moodle/site:config', $systemcontext)) {
            $str .= '<br/>';
            $str .= '<div class="report-learningtimecheck-siteadmin">';
            $str .= '<input type="submit" name="clearall" value="'.$clearallstr.'" />';
            $str .= '<input type="submit" name="clearallresults" value="'.$clearallresultsstr.'" />';
            $str .= '</div>';
        }
        $str .= '</form>';

        return $str;
    }

    public function job_info($job) {

        $joboptions = '';
        $options = json_decode($job->options);
        if (!empty($options)) {
            foreach($options as $optkey => $optvalue) {
                if ($optkey == 'return') continue;
                if (preg_match('/range$/', $optkey) && $optvalue) {
                    $optvalue = userdate($optvalue);
                }
                $joboptions .= get_string($optkey, 'report_learningtimecheck').' : '.$optvalue.'<br/>';
            }
        }

        $table = new html_table();
        $table->head = array();
        $table->size = array('40%', '60%');

        $table->data[] = array(get_string('name'), $job->name);
        $table->data[] = array(get_string('type', 'report_learningtimecheck'), $job->type);
        $table->data[] = array(get_string('item', 'report_learningtimecheck'), $job->itemids);
        $table->data[] = array(get_string('detail', 'report_learningtimecheck'), $job->detail ? get_string('yes') : get_string('no'));
        $table->data[] = array(get_string('options', 'report_learningtimecheck'), $joboptions );
        $table->data[] = array(get_string('runtime', 'report_learningtimecheck'), $job->runtime ? userdate($job->runtime) : '--' );
        $table->data[] = array(get_string('repeatdelay', 'report_learningtimecheck'), $job->repeatdelay ? learningtimecheck_format_time($job->repeatdelay, 'min') : '--' );
        $table->data[] = array(get_string('distributionlist', 'report_learningtimecheck'), $job->notifymails);

        $str = html_writer::table($table);

        return $str;
    }

    public function batchresults_buttons($originid, $storedfile, $job) {
        $url = moodle_url::make_pluginfile_url($storedfile->get_contextid(), 'report_learningtimecheck', 'batchresult', $storedfile->get_itemid(), $storedfile->get_filepath(), $storedfile->get_filename());
        $str = '<div class="report-learningtimecheck-button">';
        $str .= '<form action="'.$url.'" method="get" target="_blank" style="display:inline-block">';
        $str .= '<input type="submit" name="go_btn" value="'.get_string('download').'" />';
        $str .= '</form>';

        if ($job->notifymails) {
            $resulturl = new moodle_url('/report/learningtimecheck/batch_worker.php');
            $str .= '<form action="'.$resulturl.'" method="get" style="display:inline-block">';
            $str .= '<input type="hidden" name="interactive" value="1" />';
            $str .= '<input type="hidden" name="distribute" value="1" />';
            $str .= '<input type="hidden" name="id" value="'.$originid.'" />';
            $str .= '<input type="hidden" name="resultfile" value="'.$storedfile->get_id().'" />';
            $str .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $str .= '<input type="submit" name="send_btn" value="'.get_string('distribute', 'report_learningtimecheck').'" />';
            $str .= '</form>';
            $str .= '</div>';
        }

        return $str;
    }

    function tabs($fromcourse) {

        $context = context_course::instance($fromcourse->id);
        $view = optional_param('view', 'course', PARAM_TEXT);

        // Print tabs with options for user.
        $userurl = new moodle_url('/report/learningtimecheck/index.php', array('id' => $fromcourse->id, 'view' => 'user'));
        $rows[0][] = new tabobject('user', $userurl, get_string('user', 'report_learningtimecheck'));
        if (has_capability('report/learningtimecheck:viewother', $context)) {
            $courseurl = new moodle_url('/report/learningtimecheck/index.php', array('id' => $fromcourse->id, 'view' => 'course'));
            $rows[0][] = new tabobject('course', $courseurl, get_string('course', 'report_learningtimecheck'));
        }
        if (has_capability('report/learningtimecheck:viewother', $context)) {
            $cohorturl = new moodle_url('/report/learningtimecheck/index.php', array('id' => $fromcourse->id, 'view' => 'cohort'));
            $rows[0][] = new tabobject('cohort', $cohorturl, get_string('cohort', 'report_learningtimecheck'));
        }
        if (has_capability('report/learningtimecheck:viewother', $context)) {
            $batchurl = new moodle_url('/report/learningtimecheck/index.php', array('id' => $fromcourse->id, 'view' => 'batchs'));
            $rows[0][] = new tabobject('batchs', $batchurl, get_string('batchs', 'report_learningtimecheck'));
        }
        print_tabs($rows, $view);
    }

    function options($view, $id, $itemid) {
        global $OUTPUT;

        $useroptions = report_learningtimecheck_get_user_options();

        $str = '';

        foreach($useroptions as $key => $value) {
            if ($key == 'sortby') {
                $sortby = ($value) ? $value : 'name';
                $str .= '<img src="'.$OUTPUT->pix_url($key.$sortby, 'report_learningtimecheck').'"> ';
            } else {
                if ($value) {
                    if (preg_match('/range$/', $key)) {
                        $title = get_string($key, 'report_learningtimecheck').': '.userdate($value);
                    } else {
                        $title = get_string($key, 'report_learningtimecheck');
                    }
                    $str .= '<img src="'.$OUTPUT->pix_url($key, 'report_learningtimecheck').'" title="'.$title.'"> ';
                }
            }
        }

        $optionsbutton = $this->print_user_options_button($view, $id, $itemid);
        $str = '<div id="learningtimecheck-user-options">'.get_string('useroptions', 'report_learningtimecheck').' '.$str.' '.$optionsbutton.'</div>';

        return $str;
    }
}