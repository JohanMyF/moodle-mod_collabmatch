<?php
// This file is part of Moodle - https://moodle.org/
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
 * Upgrade script for the collabmatch activity module.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter
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
        }

        upgrade_mod_savepoint(true, 2026031900, 'collabmatch');
    }

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

        $defaulttext = get_string('default_howitworks', 'mod_collabmatch');

        $recordset = $DB->get_recordset('collabmatch', null, '', 'id, howitworkstext');
        foreach ($recordset as $record) {
            if (!isset($record->howitworkstext) || trim((string)$record->howitworkstext) === '') {
                $updaterecord = new stdClass();
                $updaterecord->id = $record->id;
                $updaterecord->howitworkstext = $defaulttext;
                $DB->update_record('collabmatch', $updaterecord);
            }
        }
        $recordset->close();

        upgrade_mod_savepoint(true, 2026041000, 'collabmatch');
    }

    return true;
}
