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
 * @package    report
 * @subpackage learningtimecheck
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$joblist = required_param('joblist', PARAM_TEXT);

// Security.

$securekey = required_param('securekey', PARAM_RAW);
$internalkey = md5($SITE->name.$CFG->passwordsaltmain);
if ($internalkey != $securekey) die('You cannot fire workers this way');

$jobs = $DB->get_records_list('report_learningtimecheck_btc', 'id', explode(',', $joblist));

$fs = get_file_storage();

foreach ($jobs as $job) {
    // Process the job.

    $data = learningtimecheck_prepare_data($job);

    // Output engine selection.
    $rangeext = ($job->detail) ? 'multiple' : 'single';

    $exportclassfile = $CFG->dirroot.'/report/learningtimecheck/export/'.$job->output.'.class.php';

    if (!file_exists($exportclassfile)) {
        print_error('errornoexporterclass', 'report_learningtimecheck', $exportclassfile);
    }

    require_once($exportclassfile);

    if (!empty($job->userid)) {
        $contextid = context_user::instance($job->userid)->id;
    } else {
        $contextid = context_system::instance()->id;
    }

    $exportcontext = new StdClass();
    $exportcontext->exporttype = $exporttype;
    $exportcontext->exportitem = $exportitem;
    $exportcontext->contextid = $contextid;

    $classname = $job->output.'_exporter';
    $exporter = new $classname($exportcontext);
    $exporter->set_data($data);

    // Output production in a temp file.
    if (!$job->detail) {
        $exporter->output_content();
        $exporter->save_content();
    } else {
        // When producing a detailed, we need make a temp dir, then produce all single docs inside
        // Zip all files and present the final zip as document.
        
        // Make temp
        
        // Produce
        foreach($data as $itemid => $reportdata) {
            $exportcontext = new StdClass();
            $exportcontext->exporttype = $exporttype;
            $exportcontext->exportitem = $exportitem;
            $exportcontext->contextid = $contextid;

            $exporter = new $classname($exportcontext);

            $exporter->set_data($reportdata->data, $reportdata->item);
            $exporter->output_content();
            $exporter->save_content();
        }
        
        // Finally zip everything
    }


    // Send files by mail.

    $recipients = explode(',', $job->notifyemails);
    if (!empty($recipients)) {

        // get report physical location for attachment
        $contenthash = $storedfile->get_contenthash();
        $pathhash = report_leargingtimecheck_get_path_from_hash($contenthash);
        $attachment = '/filedir/'.$pathhash.'/'.$contenthash;

        foreach ($recipients as $r) {
            $destination = $DB->get_record('user', array('email' => trim($r)));
            if ($destination) {
                $postsubject = get_string('sessionclosing', 'tool_delivery', $SITE->shortname);
                @email_to_user($destination, $USER, $postsubject, $posttext, $posthtml, $attachment, get_string('deliveryreport', 'tool_delivery').'.pdf');
            }
        }
    }

    // Process delay bounce.
    if ($job->repeatdelay) {
        // Repeatdelay in seconds.
        $DB->set_field('report_learningtimecheck_btc', 'runtime', $job->runtime + $job->repeatdelay * 60, array('id' => $job->id));
        mtrace('Batch Worker : Bouncing task '.$job->id.' to '.userdate($job->runtime));
    } else {
        $DB->set_field('report_learningtimecheck_btc', 'processed', time(), array('id' => $job->id));
    }
}
