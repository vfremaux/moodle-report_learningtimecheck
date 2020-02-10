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
 * @package    report_learningtimecheck
 * @category   report
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Standard upgrade handler.
 * @param int $oldversion
 */
function xmldb_report_learningtimecheck_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = true;
    // Removed old upgrade stuff, as it now uses install.xml by default to install.

    if ($oldversion < 2014041600) {

        // Define table report_learningtimecheck_btc to be created.
        $table = new xmldb_table('report_learningtimecheck_btc');

        // Adding fields to table report_learningtimecheck_btc.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('runtime', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('repeatdelay', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('processed', XMLDB_TYPE_INTEGER, '11', null, null, null, null);

        // Adding keys to table report_learningtimecheck_btc.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_learningtimecheck_btc.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2014041600, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2014060900) {

        // Define table report_learningtimecheck_opts to be created, adding capability of storing options per user.
        $table = new xmldb_table('report_learningtimecheck_opt');

        // Adding fields to table report_learningtimecheck_opt.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

        // Adding keys to table report_learningtimecheck_opt.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_learningtimecheck_opt.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2014060900, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2014061300) {

        // Add field to store mail recipients.
        $table = new xmldb_table('report_learningtimecheck_btc');
        $field = new xmldb_field('notifymails', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'processed');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2014061300, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2015021900) {

        // Add field for storing contextual value of filters.
        $table = new xmldb_table('report_learningtimecheck_btc');
        $field = new xmldb_field('filters', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015021900, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2015022101) {

        // Add field for storing detail indicator.
        $table = new xmldb_table('report_learningtimecheck_btc');
        $field = new xmldb_field('detail', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'itemid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field for storing output document format.
        $field = new xmldb_field('output', XMLDB_TYPE_CHAR, '8', null, null, null, null, 'detail');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015022101, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2015022102) {

        // Add field for storing detail indicator.
        $table = new xmldb_table('report_learningtimecheck_btc');
        $field = new xmldb_field('param', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'itemid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015022102, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2015042302) {
        $table = new xmldb_table('report_learningtimecheck_btc');
        $field = new xmldb_field('itemid', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'itemids');
            $field = new xmldb_field('itemids', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $dbman->change_field_type($table, $field);
            $dbman->change_field_precision($table, $field);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015042302, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2015050200) {
        $table = new xmldb_table('report_learningtimecheck_btc');
        $field = new xmldb_field('options', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'filters');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2015050200, 'report', 'learningtimecheck');
    }

    if ($oldversion < 2019041300) {

        // Define table report_learningtimecheck_btc to be created.
        $table = new xmldb_table('report_learningtimecheck_ud');

        // Adding fields to table report_learningtimecheck_btc.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('name', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('charvalue', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('intvalue', XMLDB_TYPE_INTEGER, '11', null, null, null, null);

        // Adding keys to table report_learningtimecheck_btc.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_index('ix_userid_contextid_name', XMLDB_INDEX_UNIQUE, array('userid','contextid', 'name'));

        // Conditionally launch create table for report_learningtimecheck_btc.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Learningtimecheck savepoint reached.
        upgrade_plugin_savepoint(true, 2019041300, 'report', 'learningtimecheck');
    }

    return $result;
}
