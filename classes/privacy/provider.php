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

namespace report_learningtimecheck\privacy;

use core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        // The 'forum' table does not store any specific user data.
        $collection->add_database_table('report_learningtimecheck_ud', [
            'courseid' => 'privacy:metadata:report_learningtimecheck_ud:contextid',
            'userid' => 'privacy:metadata:report_learningtimecheck_ud:userid',
            'name' => 'privacy:metadata:report_learningtimecheck_ud:name',
            'charvalue' => 'privacy:metadata:report_learningtimecheck_ud:charvalue',
            'intvalue' => 'privacy:metadata:report_learningtimecheck_ud:intvalue',
        ], 'privacy:metadata:report_learningtimecheck_ud');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of forum, that is any forum where the user has made any post, rated any content, or has any preferences.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        // Fetch all contexts where data is recorded.
        $sql = "
            SELECT DISTINCT
                c.id
            FROM
                {context} c,
                {report_learningtimecheck_ud} rltcud
            WHERE
                rltcud.contextid = c.id AND
                rltcud.userid = :userid
        ";

        $params = array('userid' => $userid);

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
            'contextid'    => $context->instanceid,
        ];

        // Discussion authors.
        $sql = "
            SELECT DISTINCT
                rltcud.userid
            FROM
                {report_learninttimecheck_ud} rltcud
            WHERE
                rltcud.contextid = :contextid
        ";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    rltcud.*
                  FROM {report_learningtimecheck_ud} rltcud
                 WHERE (
                    rltcud.id {$contextsql}
                )
        ";

        $ltccached = $DB->get_recordset_sql($sql, $contextparams);
        foreach ($ltccached as $ltcdatum) {
            $context = \context::instance_by_id($ltcdatum->contextid);

            // Store the cached data.
            $data = request_helper::get_context_data($context, $user);
            writer::with_context($context)
                ->export_data([$ltcdatum], $data);
        }
        $forums->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_course.
        if (!$context instanceof \context_course) {
            return;
        }

        $DB->delete_records('report_learningtimecheck_ud', ['contextid' => $context->id]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist as $context) {
            $DB->delete_records('report_learningtimecheck_ud', ['userid' => $userid, 'contextid' => $context->id]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        $DB->delete_records_select('report_learningtimecheck_ud', "userid {$userinsql}", $userinparams);
    }

}