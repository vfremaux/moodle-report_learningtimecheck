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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     report_learningtimecheck
 * @category    report
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/lib/form/filemanager.php');
require_once($CFG->dirroot.'/repository/lib.php');

if (!class_exists('admin_setting_configimage')) {

    class admin_setting_configimage extends admin_setting {

        public $component;

        public $options;

        public $context;

        /**
         * Config text constructor
         *
         * @param string $name unique ascii name, either 'mysetting' for settings that in config, or
         * 'myplugin/mysetting' for ones in config_plugins.
         * @param string $visiblename localised
         * @param string $description long localised info
         * @param string $component component to attach the generated filearea
         * @param array $options (file manager options)
         */
        public function __construct($name, $visiblename, $description, $component, $options = null) {
            global $CFG;

            $this->context = context_system::instance();

            $this->component = $component;

            if ($options) {
                $this->options = $options;
            } else {
                $this->options['accepted_types'] = '*';
            }

            $this->options['return_types'] = FILE_INTERNAL;
            $this->options['maxbytes'] = -1;
            $this->options['areamaxbytes'] = -1;
            parent::__construct($name, $visiblename, $description, 0);
        }

        /**
         * Return the setting
         *
         * @return mixed returns config if successful else null
         */
        public function get_setting() {
            global $CFG;

            $setting = $this->config_read($this->name);

            $fs = get_file_storage();
            $url = '';
            if ($setting) {
                if ($file = $fs->get_file_by_id($setting)) {
                    $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                                                           $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                }
            }
            return $url;
        }

        public function write_setting($data) {
            global $USER;

            if (empty($USER->id)) {
                return;
            }

            // Data is a string.
            $validated = $this->validate($data);
            if ($validated !== true) {
                return $validated;
            }

            $usercontext = context_user::instance($USER->id);

            $fs = get_file_storage();

            if ($fs->is_area_empty($usercontext->id, 'user', 'draft', $data, true)) {
                $fs->delete_area_files($this->context->id, $this->component, $this->name);
                return $this->config_write($this->name, 0) ? '' : get_string('errorsetting', 'admin');
            } else {
                $file = file_save_draft_area_files($data, $this->context->id, $this->component, $this->name, 0);
                $savedfiles = $fs->get_area_files($this->context->id, $this->component, $this->name, 0);
                $savedfile = array_pop($savedfiles);

                return $this->config_write($this->name, $savedfile->get_id()) ? '' : get_string('errorsetting', 'admin');
            }
        }

        /**
         * Validate data before storage
         * @param string data
         * @return mixed true if ok string if error found
         */
        public function validate($data) {
            return true;
        }

        /**
         * Return an XHTML string for the setting
         * @return string Returns an XHTML string
         */
        public function output_html($data, $query = '') {
            $default = 0;

            $draftitemid = file_get_submitted_draft_itemid($this->get_id());
            file_prepare_draft_area($draftitemid, $this->context->id, $this->component, $this->name, 0, array('subdirs'=>true));

            return format_admin_setting($this, $this->visiblename, $this->toHTML($draftitemid), $this->description,
                                        true, '', $default, $query);
        }

        protected function toHTML($draftitemid) {
            global $COURSE, $PAGE, $OUTPUT;

            $client_id = uniqid();

            // Filemanager options.
            $options = new stdClass();
            $options->mainfile  = false;
            $options->maxbytes  = $this->options['maxbytes'];
            $options->maxfiles  = 1;
            $options->client_id = $client_id;
            $options->itemid    = $draftitemid;
            $options->subdirs   = false;
            $options->target    = 'id_'.$this->name;
            $options->accepted_types = $this->options['accepted_types'];
            $options->return_types = $this->options['return_types'];
            $options->context = $PAGE->context;
            $options->areamaxbytes = $this->options['areamaxbytes'];

            $html = '';
            $fm = new form_filemanager($options);
            $output = $PAGE->get_renderer('core', 'files');
            $html .= $output->render($fm);

            $html .= '<input value="'.$draftitemid.'" id="'.$this->get_id().'" name="'.$this->get_full_name().'" type="hidden" />';
            // Label element needs 'for' attribute work.
            $html .= '<input value="" id="id_'.$this->name.'" type="hidden" />';

            return $html;
        }
    }

}