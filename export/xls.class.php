<?php

require_once($CFG->dirroot.'/report/learningtimecheck/export/export.class.php');

class xls_exporter extends learningtimecheck_exporter {

    var $csvfieldseparator;
    var $csvlineseparator;
    var $csvencoding;

    function __construct($exportcontext) {

        $ENDLINES = array('CR' => "\r", 'LF' => "\n", 'CRLF' => "\r\n");

        $this->csvfieldseparator = get_config('csvfieldseparator', 'learningtimecheck');
        if ($this->csvfieldseparator == 'TAB') $this->csvfieldseparator = "\t";
        if (empty($this->csvfieldseparator)) $this->csvfieldseparator = ';';
        $this->csvlineseparator = @$ENDLINES[get_config('csvlineseparator', 'learningtimecheck')];
        if (empty($this->csvlineseparator)) $this->csvlineseparator = "\n";
        $this->csvencoding = get_config('csvencoding', 'learningtimecheck');
        // if (empty($this->csvencoding)) $this->csvencoding = 'UTF-8';

        if (empty($this->csvencoding)) {
            $this->csvencoding = 'HTML';
        }

        parent::__construct($exportcontext);
    }

    function output_http_headers() {
        if ($this->csvencoding == 'HTML') {
            header("Content-Type: text/html\n\n");
        } else {
            header("Content-Type: text/csv\n\n");
        }
    }

    function output_content() {

        if ($this->csvencoding == 'HTML') {
            $this->content = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><pre>';
        }

        $this->content .= implode($this->data->headcodes, $this->csvfieldseparator);

        foreach ($this->data->rawdata as $dataline) {
            $this->content .= $this->csvlineseparator.implode($dataline, $this->csvfieldseparator);
        }

        if ($this->csvencoding == 'HTML') {
            $this->content .= '</pre></body></html>';
        }
    }
}