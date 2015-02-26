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

require_once($CFG->dirroot.'/report/learningtimecheck/export/export.class.php');
require_once($CFG->dirroot.'/backup/util/xml/output/memory_xml_output.class.php');
require_once($CFG->dirroot.'/backup/util/xml/xml_writer.class.php');

class xml_exporter extends learningtimecheck_exporter {

    protected $xml_writer;
    protected $xml_buffer;

    function __construct($exportcontext) {

        $xml_buffer = new memory_xml_output();
        $xml_writer = new xml_writer($xml_buffer);

        parent::__construct($exportcontext);
    }

    public function output_http_headers() {
        if ($this->csvencoding == 'HTML') {
            header("Content-Type: text/html\n\n");
        } else {
            header("Content-Type: text/xml\n\n");
        }
    }

    public function output_content() {

        $this->xml_writer->start();
        $this->xml_writer->open_tag('learningtimechecks');

        foreach ($this->data->rawdata as $dataline) {
            $this->xml_writer->open_tag('check');
            for ($i = 0 ; $i < count($dataline) ; $i++) {
                $this->xml_writer->full_tag($this->data->headcodes[$i], $dataline[$i]);
            }
            $this->xml_writer->close_tag('check');
        }

        $this->xml_writer->close_tag('learningtimechecks');

        $this->xml_writer->stop();

        // Save stream in in content and free resources;
        $this->content = $this->xml_buffer->get_allcontents();
        unset($this->xml_writer);
        unset($this->xml_buffer);
    }
}