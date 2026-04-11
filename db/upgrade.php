<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade script for the collabmatch activity module.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute collabmatch upgrade steps.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_collabmatch_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    /*
     * -----------------------------------------------------------------
     * 2026031900
     *
     * Recovery / alignment step for experimental development versions.
     * -----------------------------------------------------------------
     */
    if ($oldversion < 2026031900) {

        $table = new xmldb_table('collabmatch_game');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('collabmatchid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('playera', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('playerb', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('currentturn', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'waiting');
            $table->add_field('lastmove', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('lastplayer', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lastmovetime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('collabmatch_idx', XMLDB_INDEX_NOTUNIQUE, ['collabmatchid']);
            $table->add_index('playera_idx', XMLDB_INDEX_NOTUNIQUE, ['playera']);
            $table->add_index('playerb_idx', XMLDB_INDEX_NOTUNIQUE, ['playerb']);

            $dbman->create_table($table);
        } else {
            $field = new xmldb_field('lastmove', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('lastplayer', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lastmove');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('lastmovetime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lastplayer');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $index = new xmldb_index('playera_idx', XMLDB_INDEX_NOTUNIQUE, ['playera']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $index = new xmldb_index('playerb_idx', XMLDB_INDEX_NOTUNIQUE, ['playerb']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        $table = new xmldb_table('collabmatch');

        $field = new xmldb_field('prompttext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('targetzones', XMLDB_TYPE_TEXT, null, null, null, null, null, 'prompttext');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('matchpairs', XMLDB_TYPE_TEXT, null, null, null, null, null, 'targetzones');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '100.00', 'matchpairs');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('passingpercentage', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, '50.00', 'grade');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('collabmatch_move');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('gameid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('choice', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('correct', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('gameid_idx', XMLDB_INDEX_NOTUNIQUE, ['gameid']);
            $table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);

            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026031900, 'collabmatch');
    }

    /*
     * -----------------------------------------------------------------
     * 2026041000
     *
     * Add timer and How it works configuration fields.
     * -----------------------------------------------------------------
     */
    if ($oldversion < 2026041000) {
        $table = new xmldb_table('collabmatch');

        $field = new xmldb_field('timerenabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'passingpercentage');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timeseconds', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '60', 'timerenabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('howitworkstext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timeseconds');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $defaulttext = 'You have {seconds} seconds to choose your question and your answer. Choose the ones you know your opponent will find easy to do.';

        $records = $DB->get_records('collabmatch', null, '', 'id, howitworkstext');
        foreach ($records as $record) {
            $updaterecord = new stdClass();
            $updaterecord->id = $record->id;

            if (!isset($record->howitworkstext) || trim((string)$record->howitworkstext) === '') {
                $updaterecord->howitworkstext = $defaulttext;
                $DB->update_record('collabmatch', $updaterecord);
            }
        }

        upgrade_mod_savepoint(true, 2026041000, 'collabmatch');
    }

    return true;
}
