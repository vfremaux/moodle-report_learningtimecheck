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

/**
 * A batch worker is a "threaded" request for processing batch outputs.
 * Each CURL launched worker processes a list of "batchs to do" and can be paralellized
 * with other worker instances;
 *
 * @package    report_learningtimecheck
 * @category   report
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');

$joblist = optional_param('joblist', '', PARAM_TEXT);
$interactive = optional_param('interactive', 0, PARAM_INT);
$sendresult = optional_param('distribute', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());

// Security.
// this script can be run through a cron CURL call using an internal static security key without anny 
// user session.
// In case there is no security key provided, this could be result of an interactive online run request
// from a logged user, thus we require for valid sesskey to allow the script to run.

$securekey = optional_param('securekey', '', PARAM_RAW);
$internalkey = md5($SITE->fullname.@$CFG->passwordsaltmain);

if (!empty($securekey)) {
    if ($internalkey != $securekey) die('You cannot fire workers this way');
} else {
    $id = required_param('id', PARAM_INT);

    require_login();

    if ($id) {
        $context = context_course::instance($id);
    } else {
        $context = context_system::instance();
    }

    require_capability('report/learningtimecheck:export', $context);
    require_sesskey();
}

set_time_limit(1200);

if ($interactive) {
    $PAGE->set_url(new moodle_url('/report/learningtimecheck/batch_worker.php', array('joblist' => $joblist, 'interactive' => $interactive)));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagetype('admin');
    $PAGE->navbar->add(get_string('pluginname', 'report_learningtimecheck'), new moodle_url('/report/learningtimecheck/index.php', array('view' => 'batchs', 'id' => $id)));
    $PAGE->navbar->add(get_string('batchwork', 'report_learningtimecheck'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('batchwork', 'report_learningtimecheck'));

    $reportrenderer = $PAGE->get_renderer('report_learningtimecheck');
}

$fs = get_file_storage();

if (!$sendresult) {
    // If not distributing a previously compiled result, we are compiling a result.

    $jobs = $DB->get_records_list('report_learningtimecheck_btc', 'id', explode(',', $joblist));

    foreach ($jobs as $job) {

        if ($interactive) {
            echo $reportrenderer->job_info($job);
            echo '<pre>';
        }

        // Process the job.
        if (!empty($job->userid)) {
            $contextid = context_user::instance($job->userid)->id;
        } else {
            $contextid = context_system::instance()->id;
        }

        $reportidentifier = report_learningtimecheck_get_itemidentifier($job->type, $job->itemids);
        $exportname = $job->type.'_'.$reportidentifier.'_'.date('YmdHi', time()).'.'.$job->output;

        // Two arrays to be filled
        $data = array();
        $globals = array();
        report_learningtimecheck_prepare_data($job, $data, $globals);

        // Output engine selection.
        $exportclassfile = $CFG->dirroot.'/report/learningtimecheck/export/'.$job->output.'.class.php';
    
        if (!file_exists($exportclassfile)) {
            print_error('errornoexporterclass', 'report_learningtimecheck', $exportclassfile);
        }

        require_once($exportclassfile);

        $exportcontext = new StdClass();
        $exportcontext->exporttype = $job->type;
        $exportcontext->exportitem = $job->itemids;
        $exportcontext->param = $job->param;
        $exportcontext->contextid = $contextid;
        $exportcontext->exportfilename = $job->type.'_'.$job->itemids.'_'.date('Ymd-Hi', time());

        $classname = $job->output.'_exporter';
        $exporter = new $classname($exportcontext);

        // Output production in a temp file.
        if (!$job->detail) {
            $exporter->set_data($data, $globals);

            $exporter->output_content();
            $exporter->save_content();
        } else {
            // When producing a detailed, we need make a temp dir, then produce all single docs inside
            // Zip all files and present the final zip as document.

            // Make temp
            $gendate = date('Ymd_His', time());
            $tempdirectory = 'ltc_report_'.$gendate;

            make_temp_directory($tempdirectory);

            switch($job->type) {
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

            // Produce.
            foreach ($data as $itemid => $reportdata) {
                $exportcontext = new StdClass();
                $exportcontext->exporttype = $detailexporttype;
                $exportcontext->exportitem = $itemid;
                $exportcontext->param = $job->param;
                $exportcontext->contextid = $contextid;
                $exportcontext->output = $job->output;
                $exportcontext->exportfilename = $detailexporttype.'_'.$itemid.'_'.date('Ymd-Hi', time());

                $exporter = new $classname($exportcontext);
                $exporter->set_data($reportdata->data, $globals[$itemid]);
                $exporter->output_content();
                $exporter->save_content($tempdirectory);
                mtrace("Export ".$exporter->get_filename());
            }

            $files['reports'] = $CFG->tempdir.'/'.$tempdirectory;

            // Finally zip everything
            $packer = new zip_packer();
            $exportname = preg_replace('/\\.'.$job->output.'$/', '.zip', $exportname);
            mtrace("Exporting as $exportname ");

            if (!$storedfile = $packer->archive_to_storage($files, $contextid, 'report_learningtimecheck', 'batchresult', 0, '/', $exportname)) {
                mtrace('Failure in archiving.');
            }
        }
    }

    // Send files by mail.

    if (!$interactive || $sendresult) {

        if ($sendresult) {
            // We dpo not have yet a storedfile but it is present in storage
            $resultfileid = optional_param('resultfile', 0, PARAM_INT);

            // Reinforce security to access the file.
            require_sesskey();

            if (!$resultfileid) {
                print_error('Missing File ID');
            }

            $storedfile = $fs->get_file_by_id($resultfileid);
            if (!$storedfile) {
                print_error('Previous batch result is not existing with this id');
            }
        }

        $recipients = explode(',', $job->notifymails);
        if (!empty($recipients)) {

            if (!$storedfile) {
                // This case is mostly to handle a notification after a result construction failure (not interactive).
                foreach ($recipients as $r) {
                    $destination = $DB->get_record('user', array('email' => trim($r)));
                    if ($destination) {
                        $postsubject = get_string('batchfailed', 'report_learningtimecheck', $job->output);
                        $posttext = '
                            JobID: $job->id
                            Output: $job->output
                        ';
                        $posthtml = '
                        ';
                        @email_to_user($destination, $USER, $postsubject, $posttext, $posthtml);
                    }
                }
            } else {

                // Get report physical location for attachment.
                $contenthash = $storedfile->get_contenthash();
                $pathhash = report_learningtimecheck_get_path_from_hash($contenthash);
                $attachment = '/filedir/'.$pathhash.'/'.$contenthash;

                foreach ($recipients as $r) {
                    $destination = $DB->get_record('user', array('email' => trim($r)));
                    if ($destination) {
                        $postsubject = get_string('batchsent', 'report_learningtimecheck', $storedfile->get_filename());
                        $posttext = '';
                        $posthtml = '';
                        @email_to_user($destination, $USER, $postsubject, $posttext, $posthtml, $attachment, $storedfile->get_filename());
                    }
                }
            }
        }
    }

    // Process delay bounce.
    if ($job->repeatdelay && !$interactive) {
        // Repeatdelay in minutes.
        $DB->set_field('report_learningtimecheck_btc', 'runtime', $job->runtime + $job->repeatdelay * 60, array('id' => $job->id));

        // Shift option dates accordingly
        $options = json_decode($job->options);
        if (!empty($options)) {
            if ($options->startrange) {
                $options->startrange += $job->repeatdelay * 60;
            }
            if ($options->endrange) {
                $options->endrange += $job->repeatdelay * 60;
            }
            $job->options = json_encode($options);
            $DB->set_field('report_learningtimecheck_btc', 'options', $job->options, array('id' => $job->id));
        }

        mtrace('Batch Worker : Bouncing task '.$job->id.' to '.userdate($job->runtime));
    } else {
        $DB->set_field('report_learningtimecheck_btc', 'processed', time(), array('id' => $job->id));
    }

    if ($interactive) {
        echo '</pre>';
        echo $reportrenderer->batchresults_buttons($id, $storedfile, $job);
    }
}

if ($interactive) {
    echo $OUTPUT->footer();
}
