<?php

abstract class learningtimecheck_exporter {

    /**
     * The imput data that is used by the content formatter to produce file content
     */
    var $data;

    /**
     * Some potential addiitonal data a content formatter may need
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
        $this->globals = $globals;
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
    function save_content() {
        if ($this->data == null) {
            throw new coding_exception('Data not initialized');
        }

        if ($this->content == null) {
            return;
        }

        $fs = get_file_storage();

        if (is_string($this->content)) {
            $filerecord = new StdClass;
            $filerecord->contextid = $this->exportcontext->contextid;
            $filerecord->component = 'report_learningtimecheck';
            $filerecord->filearea = 'batchresult';
            $filerecord->itemid = 0;
            $filerecord->filepath = '/'.date('Y-m-d', time()).'/';
            $filerecord->filename = $this->exportcontext->type.'_'.$this->exportcontext->id.'.'.$this->exportcontext->output;
            $fs->create_file_from_string($filerecord, $this->content);
        }
    }
}