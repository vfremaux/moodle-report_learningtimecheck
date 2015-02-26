<?php

require_once($CFG->dirroot.'/report/learningtimecheck/export/export.class.php');
require_once($CFG->dirroot.'/report/learningtimecheck/tcpdf/tcpdf.php');
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
        $lineincr = 8;
        $dblelineincr = 16;
        $smalllineincr = 5;

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

                $course = $DB->get_record('course', array('id' => $this->exportcontext->exportitem ));
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
                $y = report_learningtimecheck_print_text($pdf, $this->data->globals->courseprogressratio, $x + 50, $y, '', '', 'L', 'freesans', '', 13);

                $label = get_string('usertimeearned', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, learningtimecheck_format_time($this->data->globals->courseearnedtime), $x + 50, $y, '', '', 'L', 'freesans', '', 13);
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
                $course = $DB->get_record('course', array('id' => $this->exportcontext->exportitem ));
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
                $cohort = $DB->get_record('cohort', array('id' => $this->exportcontext->exportitem));
                // $y += $dblelineincr;
                $label = get_string('reportforcohort', 'report_learningtimecheck').':';
                report_learningtimecheck_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
                $y = report_learningtimecheck_print_text($pdf, $cohort->name, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
                break;
        }

        $y += $lineincr;

        // redraw headers labels for pdf layout

        if ($this->exportcontext->exporttype != 'userdetail') {
            $y = report_learningtimecheck_print_pdf_overheadline($pdf, $y, $this->data);
            $y += $lineincr;
        }
        $y = report_learningtimecheck_print_pdf_headline($pdf, $y, $this->data);
        $y += $lineincr;
        $y += $lineincr;

        $datacount = count($this->data->data);
        if ($datacount) {
            for ($i = 0 ; $i < $datacount ; $i++) {
                if (is_object($this->data->data[$i])) {
                    $dataline = array();
                    foreach ($this->data->data[$i]->cells as $acell) {
                        $dataline[] = $acell;
                        if (!empty($acell->colspan)) {
                            for ($j = 1 ; $j < $acell->colspan ; $j++) {
                                $dataline[] = '';
                            }
                        }
                    }
                } else {
                    $dataline = $this->data->data[$i];
                }
                if ($i == $datacount - 1) {
                    // This is last line.
                    $y = report_learningtimecheck_print_pdf_sumline($pdf, $y, $dataline, $this->data, $i);
                } else {
                    $y = report_learningtimecheck_print_pdf_dataline($pdf, $y, $dataline, $this->data, $i);
                }
                $y = report_learningtimecheck_check_page_break($pdf, $y);
                $y += 10;
            }
        }

        $y = report_learningtimecheck_check_page_break($pdf, $y, true);

        $return = $pdf->Output('', 'S');
        $this->content = $return;
    }
}