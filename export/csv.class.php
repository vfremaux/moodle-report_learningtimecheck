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
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/report/learningtimecheck/export/export.class.php');

class csv_exporter extends learningtimecheck_exporter {

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
            $this->csvencoding = 'CSV';
        }

        parent::__construct($exportcontext);
    }

    function output_http_headers() {
        if ($this->csvencoding == 'HTML') {
            header("Content-Type: text/htm");
        } else {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$this->exportcontext->exportfilename.'.csv"');
        }
    }

    function output_content() {

        if ($this->csvencoding == 'HTML') {
            $this->content = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><pre>';
        }

        $headers = array();
        foreach($this->data->xlsprintinfo as $print) {
            $header = array_shift($this->data->xlshead);
            if ($print) {
                $headers[] = $header;
            }
        }

        $this->content .= implode($headers, $this->csvfieldseparator);

        foreach ($this->data->rawdata as $predataline) {
            // Filter some unwanted columns.
            $dataline = array();
            foreach ($this->data->xlsprintinfo as $print) {
                $value = array_shift($predataline);
                if ($print) {
                    $dataline[] = $value;
                }
            }
            $this->content .= $this->csvlineseparator.implode($dataline, $this->csvfieldseparator);
        }

        if ($this->csvencoding == 'HTML') {
            $this->content .= '</pre></body></html>';
        }
    }
}