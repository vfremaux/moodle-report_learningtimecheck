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
    public $data;

    /**
     * Some potential additionnal data a content formatter may need
     */
    public $globals;

    /**
     * The content that will be produced by the exporter
     */
    public $content;

    /**
     * An object giving export context such as ecxplicit output type and exported item information
     */
    public $exportcontext;

    /**
     * the pathed filename
     */
    public $filename;

    public function __construct($exportcontext = null) {
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
    public function output($return = false) {
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
    public function save_content($tempdirectory = null) {
        global $CFG;

        if ($this->data == null) {
            throw new coding_exception('Data not initialized');
        }

        if ($this->content == null) {
            return;
        }

        $fs = get_file_storage();

        $itemidentifier = report_learningtimecheck::get_itemidentifier($this->exportcontext->exporttype, $this->exportcontext->exportitem);

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

    public function get_filename() {
        return $this->filename;
    }

    /**
     * Splits all details and render them in files, then pack the file and delivers its back as a storedfile.
     */
    public static function export_detail($job, $data, $contextid, $filearea) {
        global $CFG;

        // Make temp.
        $gendate = date('Ymd_His', time());
        $tempdirectory = 'ltc_report_'.$gendate;

        make_temp_directory($tempdirectory);

        switch ($job->type) {
            case 'user':
                $detailexporttype = 'userdetail';
                break;

            case 'course':
                $detailexporttype = 'userdetail';
                break;

            case 'cohort':
                $detailexporttype = 'usercursus';
                break;
        }

        $reportidentifier = report_learningtimecheck::get_itemidentifier($job->type, $job->itemids);
        $exportname = $job->type.'_'.$reportidentifier.'_'.date('YmdHi', time()).'.'.$job->output;

        // Produce.
        foreach ($data as $itemid => $reportdata) {
            $exportcontext = new StdClass();
            $exportcontext->exporttype = $detailexporttype;
            $exportcontext->exportitem = $itemid;
            $exportcontext->param = $job->param;
            $exportcontext->contextid = $contextid;
            $exportcontext->output = $job->output;
            $exportcontext->exportfilename = $detailexporttype.'_'.$itemid.'_'.date('Ymd-Hi', time());

            $classname = $job->output.'_exporter';
            $exporter = new $classname($exportcontext);
            $exporter->set_data($reportdata->data, $globals[$itemid]);
            $exporter->output_content();
            $exporter->save_content($tempdirectory);
        }

        $files['reports'] = $CFG->tempdir.'/'.$tempdirectory;

        // Finally zip everything.

        $packer = new zip_packer();
        $exportname = preg_replace('/\\.'.$job->output.'$/', '.zip', $exportname);

        if (!$storedfile = $packer->archive_to_storage($files, $contextid, 'report_learningtimecheck',
                                                       $filearea, 0, '/', $exportname)) {
            mtrace('Failure in archiving.');
            die;
        }
        return $storedfile;
    }
}