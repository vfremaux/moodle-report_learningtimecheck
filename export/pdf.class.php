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

require_once($CFG->dirroot.'/report/learningtimecheck/export/export.class.php');
require_once($CFG->dirroot.'/local/vflibs/tcpdf/tcpdf.php');
require_once($CFG->dirroot.'/report/learningtimecheck/pdfgeneratelib.php');

class pdf_exporter extends learningtimecheck_exporter {

    public function __construct($exportcontext) {
        parent::__construct($exportcontext);
    }

    public function output_http_headers() {
        header("Content-Type:application/pdf\n\n");
    }

    public function output_content() {
        global $USER, $DB;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetTitle(get_string('reportdoctitle', 'report_learningtimecheck'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();

        // Define variables
        // Portrait
        $x = 20;
        $y = 70;
        $lineincr = (!empty($this->data->lineincr)) ? $this->data->lineincr : 8;
        $dblelineincr = $lineincr * 2;
        $smalllineincr = (!empty($this->data->smalllineincr)) ? $this->data->smalllineincr : 4;

        // Set alpha to no-transparency
        // $pdf->SetAlpha(1);

        // Add images and lines.
        report_learningtimecheck_draw_frame($pdf);

        // Add images and lines.
        report_learningtimecheck_print_header($pdf);

        // Add images and lines.
        report_learningtimecheck_print_footer($pdf);

        // Make report.
        $pdf->SetTextColor(0, 0, 120);
        $title = get_config('reporttitle', 'report_learningtimecheck');
        $y = report_learningtimecheck_print_text($pdf, $title, $x + 40, $y, '', '', 'C', 'freesans', '', 20);
    
        $pdf->SetTextColor(0);
    
        // $y += $dblelineincr;
        $label = get_string('recipient', 'report_learningtimecheck').':';
        report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
        $recipient = get_config('report_learningtimecheck', 'recipient');
        $y = report_learningtimecheck_print_text($pdf, $recipient, $x + 50, $y, '', '', 'L', 'freesans', '', 13);

        $label = get_string('reportdate', 'report_learningtimecheck').':';
        report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
        $y = report_learningtimecheck_print_text($pdf, userdate(time()), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

        switch ($this->exportcontext->exporttype) {
            case 'userdetail':
                $y += $lineincr;
                $label = get_string('reportforuser', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $user = $DB->get_record('user', array('id' => $this->exportcontext->exportitem));
                $y = report_learningtimecheck_print_text($pdf, fullname($user), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                $course = $DB->get_record('course', array('id' => $this->exportcontext->param));
                // $y += $dblelineincr;
                $label = get_string('reportforcourse', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, $course->fullname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                if(!empty($course->idnumber)) {
                    // $y += $dblelineincr;
                    $label = get_string('idnumber').':';
                    report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                    $y = report_learningtimecheck_print_text($pdf, $course->idnumber, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                } else {
                    // $y += $dblelineincr;
                    $label = get_string('shortname').':';
                    report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                    $y = report_learningtimecheck_print_text($pdf, $course->shortname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                }

                $label = get_string('usercourseprogress', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, $this->globals['courseprogressratio'], $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                $label = get_string('usertimeearned', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, learningtimecheck_format_time($this->globals['courseearnedtime']), $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                break;

            case 'usercursus':
                $y += $lineincr;
                $label = get_string('reportforuser', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $user = $DB->get_record('user', array('id' => $this->exportcontext->exportitem));
                $y = report_learningtimecheck_print_text($pdf, fullname($user), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                $label = get_string('usercursusprogress', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, $this->globals['userprogressratio'], $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                $label = get_string('usertimeearned', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, learningtimecheck_format_time($this->globals['userearnedtime']), $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                break;

            case 'user':
                // $y += $dblelineincr;
                $y += $lineincr;
                $label = get_string('reportforuser', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $user = $DB->get_record('user', array('id' => $this->exportcontext->exportitem));
                $y = report_learningtimecheck_print_text($pdf, fullname($user), $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                break;

            case 'course':
                $course = $DB->get_record('course', array('id' => $this->exportcontext->exportitem));
                // $y += $dblelineincr;
                $label = get_string('reportforcourse', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, $course->fullname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                if(!empty($course->idnumber)) {
                    // $y += $dblelineincr;
                    $label = get_string('idnumber').':';
                    report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                    $y = report_learningtimecheck_print_text($pdf, $course->idnumber, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                } else {
                    // $y += $dblelineincr;
                    $label = get_string('shortname').':';
                    report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                    $y = report_learningtimecheck_print_text($pdf, $course->shortname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                }

                if ($groupid = optional_param('groupid', 0, PARAM_INT)) {
                    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
                    $label = get_string('group').':';
                    report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                    $y = report_learningtimecheck_print_text($pdf, $groupname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                }
                
                if ($groupingid = optional_param('groupingid', 0, PARAM_INT)) {
                    $groupingname = $DB->get_field('groupings', 'name', array('id' => $groupingid));
                    $label = get_string('grouping', 'group').':';
                    report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                    $y = report_learningtimecheck_print_text($pdf, $groupingname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                }


                $label = get_string('allusers', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, (0 + @$this->globals['allusers']), $x + 60, $y, '', '', 'L', 'freesans', '', 13);

                $label = get_string('activeusers', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 12);
                $y = report_learningtimecheck_print_text($pdf, (0 + @$this->globals['allusers'] - @$this->globals['nullusers']), $x + 60, $y, '', '', 'L', 'freesans', '', 12);

                $label = get_string('nullusers', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 12);
                $y = report_learningtimecheck_print_text($pdf, (0 + @$this->globals['nullusers']), $x + 60, $y, '', '', 'L', 'freesans', '', 12);

                $label = get_string('fullusers', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 12);
                $y = report_learningtimecheck_print_text($pdf, (0 + @$this->globals['fullusers']), $x + 60, $y, '', '', 'L', 'freesans', '', 12);

                $label = get_string('midrangeusers', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 12);
                $y = report_learningtimecheck_print_text($pdf, (0 + @$this->globals['halfusers']), $x + 60, $y, '', '', 'L', 'freesans', '', 12);

                break;

            case 'cohort':
            case 'cohortdetail':
                $cohort = $DB->get_record('cohort', array('id' => $this->exportcontext->exportitem));
                // $y += $dblelineincr;
                $label = get_string('reportforcohort', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, $cohort->name, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                break;
        }

        $y += $lineincr;

        if (!empty($this->globals['startrange'])) {
            $label = get_string('startrange', 'report_learningtimecheck').':';
            report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 12);
            $y = report_learningtimecheck_print_text($pdf, userdate(0 + $this->globals['startrange']), $x + 50, $y, '', '', 'L', 'freesans', '', 12);
        }
        if (!empty($this->globals['endrange'])) {
            $label = get_string('endrange', 'report_learningtimecheck').':';
            report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 12);
            $y = report_learningtimecheck_print_text($pdf, userdate(0 + $this->globals['endrange']), $x + 50, $y, '', '', 'L', 'freesans', '', 12);
        }

        $y += $lineincr;

        // redraw headers labels for pdf layout

        $dataarr = array();
        if ($this->exportcontext->exporttype == 'cohortdetail') {
            // this may need to be reorganised for other interactive "detail" report queries.
            foreach ($this->data as $stubid => $datastub) {
                $dataarr[$stubid] = $datastub->data;
            }
        } else {
            $dataarr[] = $this->data;
        }

        foreach ($dataarr as $dataid => $data) {
            if ($this->exportcontext->exporttype == 'cohortdetail') {
                $user = $DB->get_record('user', array('id' => $dataid));
                $y += $lineincr;
                $y = report_learningtimecheck_print_pdf_studentline($pdf, $y, fullname($user));
                $y += $lineincr;
            }

            if ($this->exportcontext->exporttype != 'userdetail') {
                if (!empty($table->pdfhead1)) {
                    $y = report_learningtimecheck_print_pdf_overheadline($pdf, $y, $data);
                    $y += $lineincr;
                }
            }
            $y = report_learningtimecheck_print_pdf_headline($pdf, $y, $data);
            $y += $lineincr;
            $y += $dblelineincr;
    
            $datacount = count($data->pdfdata);
            if ($datacount) {
                for ($i = 0 ; $i < $datacount ; $i++) {
                    if (is_object($data->pdfdata[$i])) {
                        $dataline = array();
                        foreach ($data->pdfdata[$i]->cells as $acell) {
                            $dataline[] = $acell;
                            if (!empty($acell->colspan)) {
                                for ($j = 1 ; $j < $acell->colspan ; $j++) {
                                    $dataline[] = '';
                                }
                            }
                        }
                    } else {
                        $dataline = $data->pdfdata[$i];
                    }
                    if ($i == $datacount - 1) {
                        // This is last line.
                        $y = report_learningtimecheck_print_pdf_sumline($pdf, $y, $dataline, $data, $i);
                    } else {
                        $y = report_learningtimecheck_print_pdf_dataline($pdf, $y, $dataline, $data, $i);
                    }
                    $isnewpage = false;
                    $y = report_learningtimecheck_check_page_break($pdf, $y, $isnewpage, false);
                    if ($isnewpage) {
                        // Add a page column header
                        if ($this->exportcontext->exporttype != 'userdetail') {
                            // $y = report_learningtimecheck_print_pdf_overheadline($pdf, $y, $data);
                            $y += $lineincr;
                        }
                        $y = report_learningtimecheck_print_pdf_headline($pdf, $y, $data);
                        $y += $lineincr;
                        $y += $lineincr;
                    }
                    $y += $dblelineincr;
                }
            }

            $y = report_learningtimecheck_check_page_break($pdf, $y, $foo, true);
        }

        $return = $pdf->Output('', 'S');

        $this->content = $return;
    }
}