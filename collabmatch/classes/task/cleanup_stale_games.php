<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Scheduled task to clean up stale Collabmatch games.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabmatch\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/collabmatch/lib.php');

/**
 * Scheduled cleanup of stale games.
 *
 * This first version is intentionally conservative.
 *
 * It deletes:
 * 1. invited games older than 48 hours
 * 2. active/waiting games with no moves ever made, older than 7 days
 * 3. active/waiting games with a recorded last move older than 14 days
 *
 * The actual deletion is delegated to collabmatch_delete_game()
 * in lib.php so that one shared deletion path is used.
 */
class cleanup_stale_games extends \core\task\scheduled_task {

    /**
     * Human-readable task name shown in Moodle scheduled tasks UI.
     *
     * @return string
     */
    public function get_name() {
        return 'Cleanup stale Collabmatch games';
    }

    /**
     * Run the cleanup.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $now = time();

        // Thresholds.
        $inviteexpiry = $now - (48 * 60 * 60);         // 48 hours.
        $nomoveexpiry = $now - (7 * 24 * 60 * 60);     // 7 days.
        $stalledexpiry = $now - (14 * 24 * 60 * 60);   // 14 days.

        mtrace('mod_collabmatch: stale game cleanup started');

        $deletedcount = 0;

        /*
         * ---------------------------------------------------------
         * 1. Delete invited games that were never accepted
         * ---------------------------------------------------------
         */
        $invitedgames = $DB->get_records_select(
            'collabmatch_game',
            'status = ? AND timecreated < ?',
            ['invited', $inviteexpiry],
            '',
            'id'
        );

        foreach ($invitedgames as $game) {
            collabmatch_delete_game($game->id);
            $deletedcount++;
            mtrace('Deleted stale invited game ID ' . $game->id);
        }

        /*
         * ---------------------------------------------------------
         * 2. Delete active/waiting games where nobody ever made a move
         * ---------------------------------------------------------
         *
         * These are games that were created, perhaps joined,
         * but never really got going.
         */
        $nomovegames = $DB->get_records_select(
            'collabmatch_game',
            'status IN (?, ?) AND lastmovetime = 0 AND timecreated < ?',
            ['active', 'waiting', $nomoveexpiry],
            '',
            'id'
        );

        foreach ($nomovegames as $game) {
            collabmatch_delete_game($game->id);
            $deletedcount++;
            mtrace('Deleted stale no-move game ID ' . $game->id);
        }

        /*
         * ---------------------------------------------------------
         * 3. Delete active/waiting games that stalled after moves began
         * ---------------------------------------------------------
         */
        $stalledgames = $DB->get_records_select(
            'collabmatch_game',
            'status IN (?, ?) AND lastmovetime > 0 AND lastmovetime < ?',
            ['active', 'waiting', $stalledexpiry],
            '',
            'id'
        );

        foreach ($stalledgames as $game) {
            collabmatch_delete_game($game->id);
            $deletedcount++;
            mtrace('Deleted stalled game ID ' . $game->id);
        }

        mtrace('mod_collabmatch: stale game cleanup finished. Deleted ' . $deletedcount . ' game(s).');
    }
}