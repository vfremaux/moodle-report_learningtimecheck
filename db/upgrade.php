<?php

function xmldb_report_learningtimecheck_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    
    $dbman = $DB->get_manager();

    $result = true;
    //removed old upgrade stuff, as it now uses install.xml by default to install.
    
    if ($oldversion < 2014041600) {

        // Define table report_learningtimecheck_btc to be created
        $table = new xmldb_table('report_learningtimecheck_btc');

        // Adding fields to table report_learningtimecheck_btc
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('runtime', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('repeatdelay', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('processed', XMLDB_TYPE_INTEGER, '11', null, null, null, null);

        // Adding keys to table report_learningtimecheck_btc
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_learningtimecheck_btc
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // learningtimecheck savepoint reached
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

    if ($oldversion < 2015022100) {

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
        upgrade_plugin_savepoint(true, 2015022100, 'report', 'learningtimecheck');
    }

    return $result;
}
