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
 * This file contains functions used by the trainingsessions report
 *
 * @package    report_trainingsessions
 * @category   report
 * @copyright  2012 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/renderer.php');
require_once($CFG->dirroot.'/mod/learningtimecheck/xlib.php');
require_once($CFG->dirroot.'/report/learningtimecheck/classes/report_learningtimecheck.class.php');

// The max number of report build workers. This will depend on your processing capabilities (number of clusters/cores/threads).
define('REPORT_LTC_MAX_WORKERS', 4);
define('REPORT_IDENTIFIER_ID', 0);
define('REPORT_IDENTIFIER_IDNUMBER', 1);
define('REPORT_IDENTIFIER_NAME', 2);

// Controls progressbars display
define('PROGRESSBAR_ITEMS', 0);
define('PROGRESSBAR_TIME', 1);
define('PROGRESSBAR_BOTH', 2);

define('PROGRESSBAR_MANDATORY', 0);
define('PROGRESSBAR_OPTIONAL', 1);
define('PROGRESSBAR_ALL', 2);

define('DEBUG_LTC_CHECK', 0);

/**
 * Called by the storage subsystem to give back a report.
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param boolean $forcedownload
 */
function report_learningtimecheck_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    require_course_login($course);

    if ($filearea !== 'batchresult') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'report_learningtimecheck', $filearea, 0,
                               $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    $forcedownload = true;

    \core\session\manager::write_close();
    send_stored_file($file, 60 * 60, 0, $forcedownload);
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node &$navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 * @return void
 */
function report_learningtimecheck_extend_navigation_course(&$navigation, $course, $context) {

    if (has_capability('report/learningtimecheck:view', $context)) {
        $url = new moodle_url('/report/learningtimecheck/index.php', array('id' => $course->id));
        $navstr = get_string('pluginname', 'report_learningtimecheck');
        $navigation->add($navstr, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Gives page types html classes used by this report
 * @param string $pagetype
 * @param object $parentcontext
 * @param object $currentcontext
 * @return array of page types
 * @todo check input params consistance, review code
 */
function report_learningtimecheck_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*' => get_string('page-x', 'pagetype'),
        'report-*' => get_string('page-report-x', 'pagetype'),
        'report-learningtimecheck-*' => get_string('page-report-learningtimecheck-x', 'report_learningtimecheck'),
        'report-learningtimecheck-index' => get_string('page-report-learningtimecheck-index', 'report_learningtimecheck'),
    );
    return $array;
}

function report_learningtimecheck_curl_callback($response, $info) {
    if (!empty($info['http_code']) and ($info['http_code'] != 200)) {
        mtrace('Request for '.$info['url'].' failed with HTTP code '.$info['http_code']);
    } else {
        mtrace('Successfully completed in '.$info['total_time'].' seconds.');
    }
}

/**
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 */
function report_learningtimecheck_supports_feature($feature = null, $getsupported = null) {
    global $CFG;
    static $supports;

    if (!during_initial_install()) {
        $config = get_config('report_learningtimecheck');
    }

    if (!isset($supports)) {
        $supports = array(
            'pro' => array(
                'format' => array('xls', 'csv', 'pdf', 'xml'),
                'mode' => array('batch'),
                'export' => array('ws'),
                'emulate' => 'community',
            ),
            'community' => array(
                'format' => array('xml', 'csv'),
            ),
        );
        $prefer = array('format' => array(
            'xml' => 'community',
            'csv' => 'community'
        ));
    }

    if ($getsupported) {
        return $supports;
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    if (empty($feature)) {
        // Just return version.
        return $versionkey;
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    // Special condition for pdf dependencies.
    if (($feature == 'format/pdf') && !is_dir($CFG->dirroot.'/local/vflibs')) {
        return false;
    }

    if (array_key_exists($feat, $supports['community'])) {
        if (in_array($subfeat, $supports['community'][$feat])) {
            // If community exists, default path points community code.
            if (isset($prefer[$feat][$subfeat])) {
                // Configuration tells which location to prefer if explicit.
                $versionkey = $prefer[$feat][$subfeat];
            } else {
                $versionkey = 'community';
            }
        }
    }

    return $versionkey;
}

function report_learningtimecheck_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (isguestuser($user)) {
        // The guest user cannot post, so it is not possible to view any posts.
        // May as well just bail aggressively here.
        return false;
    }

    if ($course) {
        $context = context_course::instance($course->id);
        $courseid = $course->id;
    } else {
        $context = context_system::instance();
        $courseid = SITEID;
    }
    if (has_capability('report/trainingsessions:viewother', $context)) {
        $params = ['itemid' => $user->id, 'view' => 'user', 'id' => $courseid];
        $node = new core_user\output\myprofile\node('reports', 'learningtimecheck',
                get_string('userreportlink', 'report_learningtimecheck'), null, new moodle_url('/report/learningtimecheck/index.php', $params));
        $tree->add_node($node);
    }
    return true;

}

// Achievement is in fifth col (tickeditems);
function sortbyachievementdesc($a, $b) {
    if ($a[5] > $b[5]) {
        return -1;
    }
    if ($a[5] < $b[5]) {
        return 1;
    }
    return sortbyname($a, $b);
}

// Achievement is in fifth col (tickeditems);
function sortrawbyachievementdesc($a, $b) {
    if ($a[6] > $b[6]) {
        return -1;
    }
    if ($a[6] < $b[6]) {
        return 1;
    }
    return sortbyname($a, $b);
}

// Sort function.
function sortbyname($a, $b) {
    if (strip_tags($a[1]) > strip_tags($b[1])) {
        return 1;
    }
    if (strip_tags($a[1]) < strip_tags($b[1])) {
        return -1;
    }
    return 0;
}

// Sort function.
function sortrawbyname($a, $b) {
    if ($a[1] > $b[1]) {
        return 1;
    }
    if ($a[1] < $b[1]) {
        return -1;
    }
    if ($a[2] > $b[2]) {
        return -1;
    }
    if ($a[2] < $b[2]) {
        return 1;
    }
    return 0;
}
