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

abstract class learningtimecheck_exporter {

    /**
     * The imput data that is used by the content formatter to produce file content
     */
    var $data;

    /**
     * Some potential additionnal data a content formatter may need
     */
    var $globals;

    /**
     * The content that will be produced by the exporter
     */
    var $content;

    /**
     * An object giving export context such as ecxplicit output type and exported item information
     */
    var $exportcontext;
    
    /**
     * the pathed filename
     */
    var $filename;

    function __construct($exportcontext = null) {
        $this->exportcontext = $exportcontext;
    }

    /**
     * Feeds the exporter with source data.
     * @param array $data source data
     * @param mixed $globals some additional globals that will feed header or common parts of the document
     */
    function set_data(&$data, &$globals) {
        $this->data = $data;
        $this->globals = (array)$globals;
    }

    /**
     * The generic output function. Can return a true document content, 
     * or an HTTP ready to send document if required.
     */
    function output($return = false) {
        if ($this->data == null) {
            throw new coding_exception('Data not initialized');
        }

        // return true payload without http headers (for storage)
        if ($return) {
            return $this->output_content();
        }

        $this->output_http_headers();
        $this->output_content();
        echo $this->content;
    }

    /**
     * Sends HTTP headers. this should be defined by each subclass
     */
    abstract function output_http_headers();

    /**
     * this builds the effective content and stores it in the $this->content var.
     */
    abstract function output_content();

    /**
     *
     */
    function save_content($tempdirectory = null) {
        global $CFG, $DB;

        if ($this->data == null) {
            throw new coding_exception('Data not initialized');
        }

        if ($this->content == null) {
            return;
        }

        $fs = get_file_storage();

        $itemidentifier = report_learningtimecheck_get_itemidentifier($this->exportcontext->exporttype, $this->exportcontext->exportitem);

        if (is_string($this->content)) {
            if (empty($tempdirectory)) {

                $fs->delete_area_files($this->exportcontext->contextid, 'report_learningtimecheck', 'batchresult', 0);

                $filerecord = new StdClass;
                $filerecord->contextid = $this->exportcontext->contextid;
                $filerecord->component = 'report_learningtimecheck';
                $filerecord->filearea = 'batchresult';
                $filerecord->itemid = 0;
                $filerecord->filepath = '/';
                $filerecord->filename = $this->exportcontext->exporttype.'_'.$itemidentifier.'_'.strftime('%Y%m%d%H%M', time()).'.'.$this->exportcontext->output;
                $this->filename = '<moodlefiles>/report_learningtimecheck/batchresult/0/'.$filerecord->filepath.$filerecord->filename;
                return $fs->create_file_from_string($filerecord, $this->content);
            } else {
                $filename = $this->exportcontext->exporttype.'_'.$itemidentifier.'.'.$this->exportcontext->output;
                $this->filename = $CFG->tempdir.'/'.$tempdirectory.'/'.$filename;
                if ($FILE = fopen($CFG->tempdir.'/'.$tempdirectory.'/'.$filename, 'w')) {
                    fputs($FILE, $this->content);
                    fclose($FILE);
                    return $tempdirectory.'/'.$filename;
                }
            }
        }
    }

    function get_filename() {
        return $this->filename;
    }
}