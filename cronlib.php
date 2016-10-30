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
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/report/learningtimecheck/multi_curl.php');

/**
 * This is the main cron function of report for executing batchs
 * This cron task will launch worker independant queries to handle some reports
 * builds in parallel. This is mainly intended to feed a nightly high processing
 * availability of your computing resources. Daytime compilations should be done
 * as less as possible, or using interactive, unity report build.
 */
function report_learningtimecheck_cron() {
    global $DB, $SITE, $CFG;

    mtrace("Starting Learningtimecheck Cron.");

    if (!$tasks = $DB->get_records_select('report_learningtimecheck_btc', " processed IS NULL OR processed = 0 OR (processed < runtime AND repeatdelay > 0)  ")) {
        mtrace('Empty task stack...');
        return;
    }

    $i = 0;
    $jb = 0;
    foreach ($tasks as $tid => $task) {
        if (time() < $task->runtime) {
            mtrace("\t\t $tid not yet.");
            continue;
        } else {
            mtrace("Preparing jobgroup ".($i+1));
            $jobgroups[$i][] = $tid;
            $i++;
            if ($i == REPORT_LEARNINGTIMECHECK_MAX_WORKERS) {
                $i = 0;
            }
        }
    }

    if (!empty($jobgroups)) {
        $curlroller = new RollingCurl('learningtimecheck_curl_callback');
        foreach ($jobgroups as $jobgroup) {

            /*
             * This will parallelize processing. Usually batchs are deferred to nightly processing
             * so we could spend all the multicore power by parallelizing.
             * The security key avoids weird use of the workers by anyone
             */
            $rq = 'joblist='.implode(',', $jobgroup).'&securekey='.urlencode(md5($SITE->fullname.@$CFG->passwordsaltmain));

            // Launch tasks by firing CURL shooting.
            $uri = new moodle_url('/report/learningtimecheck/batch_worker.php');

            $maxexectime = ini_get('max_execution_time');
            $maxexectime += 60;

            $request = new RollingCurlRequest($uri.'?'.$rq);
            $request->options = array(CURLOPT_TIMEOUT => $maxexectime,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT => 'Moodle LearningtimeCheck Report Batch',
                CURLOPT_HTTPHEADER => array("Content-Type: text/xml charset=UTF-8"));
            debug_trace("Spawning Batch Worker: Firing curl ($maxexectime secs timeout) : {$uri}?{$rq}\n");
            $curlroller->add($request);

            /*
            $ch = curl_init($uri.'?'.$rq);
            debug_trace("Spawning Batch Worker: Firing curl ($maxexectime secs timeout) : {$uri}?{$rq}\n");

            curl_setopt($ch, CURLOPT_TIMEOUT, $maxexectime);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle Report Batch');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rq);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $raw = curl_exec($ch);

            // Check for curl errors.
            $curlerrno = curl_errno($ch);
            if ($curlerrno != 0) {
                mtrace("Request for $uri?$rq failed with curl error $curlerrno: ".curl_error($ch));
                continue;
            }

            // Check HTTP error code.
            $info =  curl_getinfo($ch);
            if (!empty($info['http_code']) and ($info['http_code'] != 200)) {
                mtrace("Request for $uri failed with HTTP code ".$info['http_code']);
                continue;
            } else {
                mtrace('Success');
            }
            curl_close($ch);
            */
        }
        debug_trace("Executing Workers\n");
        $curlroller->execute();
    }

    mtrace("\tdone.");
}
