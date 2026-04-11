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
 * Privacy provider for mod_collabmatch.
 *
 * Strategy:
 * - Single-player data for the requesting user is deleted completely.
 * - Two-player shared game rows are retained, but the requesting user's ID
 *   is anonymised to 0 in shared game fields.
 * - Move rows owned by the requesting user are deleted.
 *
 * @package    mod_collabmatch
 * @category   privacy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabmatch\privacy;

defined('MOODLE_INTERNAL') || die();

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\plugin\provider as request_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use dml_exception;
use stdClass;

/**
 * Privacy provider implementation for CollabMatch.
 */
class provider implements metadata_provider, request_provider {

    /**
     * Describe the personal data stored by the plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('collabmatch_game', [
            'playera' => 'privacy:metadata:collabmatch_game:playera',
            'playerb' => 'privacy:metadata:collabmatch_game:playerb',
            'currentturn' => 'privacy:metadata:collabmatch_game:currentturn',
            'lastplayer' => 'privacy:metadata:collabmatch_game:lastplayer',
            'lastmove' => 'privacy:metadata:collabmatch_game:lastmove',
            'lastmovetime' => 'privacy:metadata:collabmatch_game:lastmovetime',
            'timecreated' => 'privacy:metadata:collabmatch_game:timecreated',
            'timemodified' => 'privacy:metadata:collabmatch_game:timemodified',
        ], 'privacy:metadata:collabmatch_game');

        $collection->add_database_table('collabmatch_move', [
            'userid' => 'privacy:metadata:collabmatch_move:userid',
            'choice' => 'privacy:metadata:collabmatch_move:choice',
            'correct' => 'privacy:metadata:collabmatch_move:correct',
            'timecreated' => 'privacy:metadata:collabmatch_move:timecreated',
        ], 'privacy:metadata:collabmatch_move');

        $collection->link_subsystem('core_grades', 'privacy:metadata:core_grades');

        return $collection;
    }

    /**
     * Get the list of contexts containing user data for the specified user.
     *
     * @param int $userid
     * @return contextlist
     * @throws dml_exception
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid
                   AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m
                    ON m.id = cm.module
                  JOIN {collabmatch} c
                    ON c.id = cm.instance
                 WHERE m.name = :modname
                   AND (
                        EXISTS (
                            SELECT 1
                              FROM {collabmatch_game} g
                             WHERE g.collabmatchid = c.id
                               AND (g.playera = :userid1 OR g.playerb = :userid2
                                    OR g.currentturn = :userid3 OR g.lastplayer = :userid4)
                        )
                        OR EXISTS (
                            SELECT 1
                              FROM {collabmatch_game} g
                              JOIN {collabmatch_move} mv
                                ON mv.gameid = g.id
                             WHERE g.collabmatchid = c.id
                               AND mv.userid = :userid5
                        )
                   )";

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modname' => 'collabmatch',
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
            'userid4' => $userid,
            'userid5' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @throws dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('collabmatch', $context->instanceid, 0, false, MUST_EXIST);
            $collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

            $games = $DB->get_records_select(
                'collabmatch_game',
                'collabmatchid = ? AND (playera = ? OR playerb = ? OR currentturn = ? OR lastplayer = ?)',
                [$collabmatch->id, $userid, $userid, $userid, $userid],
                'id ASC'
            );

            $movesql = "SELECT mv.*, g.collabmatchid
                          FROM {collabmatch_move} mv
                          JOIN {collabmatch_game} g
                            ON g.id = mv.gameid
                         WHERE g.collabmatchid = ?
                           AND mv.userid = ?
                      ORDER BY mv.id ASC";
            $moves = $DB->get_records_sql($movesql, [$collabmatch->id, $userid]);

            $exportgames = [];
            foreach ($games as $game) {
                $exportgames[] = (object) [
                    'id' => $game->id,
                    'playera' => ($game->playera == $userid) ? get_string('privacy:you', 'mod_collabmatch') : $game->playera,
                    'playerb' => ($game->playerb == $userid) ? get_string('privacy:you', 'mod_collabmatch') : $game->playerb,
                    'currentturn' => ($game->currentturn == $userid) ? get_string('privacy:you', 'mod_collabmatch') : $game->currentturn,
                    'status' => $game->status,
                    'lastmove' => $game->lastmove,
                    'lastplayer' => ($game->lastplayer == $userid) ? get_string('privacy:you', 'mod_collabmatch') : $game->lastplayer,
                    'lastmovetime' => transform::datetime($game->lastmovetime),
                    'timecreated' => transform::datetime($game->timecreated),
                    'timemodified' => transform::datetime($game->timemodified),
                ];
            }

            $exportmoves = [];
            foreach ($moves as $move) {
                $exportmoves[] = (object) [
                    'id' => $move->id,
                    'gameid' => $move->gameid,
                    'choice' => $move->choice,
                    'correct' => (bool) $move->correct,
                    'timecreated' => transform::datetime($move->timecreated),
                ];
            }

            $data = (object) [
                'activityname' => format_string($collabmatch->name, true, ['context' => $context]),
                'games' => array_values($exportgames),
                'moves' => array_values($exportmoves),
            ];

            $subcontext = [
                get_string('privacy:collabmatchdata', 'mod_collabmatch'),
            ];

            helper::export_context_files($context, 'mod_collabmatch', 'intro', 0, $subcontext);
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Delete all user data in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @throws dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('collabmatch', $context->instanceid, 0, false, MUST_EXIST);
            self::delete_user_data_in_activity((int) $cm->instance, $userid);
        }
    }

    /**
     * Get the list of users who have data in the given context.
     *
     * @param userlist $userlist
     * @throws dml_exception
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('collabmatch', $context->instanceid, 0, false, MUST_EXIST);

        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                 WHERE u.id IN (
                        SELECT g.playera
                          FROM {collabmatch_game} g
                         WHERE g.collabmatchid = :cmid1 AND g.playera > 0
                        UNION
                        SELECT g.playerb
                          FROM {collabmatch_game} g
                         WHERE g.collabmatchid = :cmid2 AND g.playerb > 0
                        UNION
                        SELECT g.currentturn
                          FROM {collabmatch_game} g
                         WHERE g.collabmatchid = :cmid3 AND g.currentturn > 0
                        UNION
                        SELECT g.lastplayer
                          FROM {collabmatch_game} g
                         WHERE g.collabmatchid = :cmid4 AND g.lastplayer > 0
                        UNION
                        SELECT mv.userid
                          FROM {collabmatch_move} mv
                          JOIN {collabmatch_game} g
                            ON g.id = mv.gameid
                         WHERE g.collabmatchid = :cmid5 AND mv.userid > 0
                 )";

        $params = [
            'cmid1' => $cm->instance,
            'cmid2' => $cm->instance,
            'cmid3' => $cm->instance,
            'cmid4' => $cm->instance,
            'cmid5' => $cm->instance,
        ];

        $userlist->add_from_sql('id', $sql, $params);
    }

    /**
     * Delete data for multiple users in a single context.
     *
     * @param approved_userlist $userlist
     * @throws dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('collabmatch', $context->instanceid, 0, false, MUST_EXIST);

        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_data_in_activity((int) $cm->instance, (int) $userid);
        }
    }

    /**
     * Apply the CollabMatch deletion strategy for one user in one activity.
     *
     * Strategy:
     * - Delete the user's move rows.
     * - For games that become single-player-only after deletion, remove the whole game row.
     * - For shared games, retain the row but anonymise user references to 0.
     *
     * @param int $collabmatchid
     * @param int $userid
     * @throws dml_exception
     */
    protected static function delete_user_data_in_activity(int $collabmatchid, int $userid): void {
        global $DB;

        $games = $DB->get_records_select(
            'collabmatch_game',
            'collabmatchid = ? AND (playera = ? OR playerb = ? OR currentturn = ? OR lastplayer = ?)',
            [$collabmatchid, $userid, $userid, $userid, $userid],
            'id ASC'
        );

        if (!$games) {
            return;
        }

        foreach ($games as $game) {
            // Always remove the requesting user's move rows first.
            $DB->delete_records('collabmatch_move', ['gameid' => $game->id, 'userid' => $userid]);

            $otherplayerpresent = (
                ($game->playera > 0 && $game->playera != $userid) ||
                ($game->playerb > 0 && $game->playerb != $userid)
            );

            if (!$otherplayerpresent) {
                // Single-player / requester-only record: delete the whole game row.
                $DB->delete_records('collabmatch_game', ['id' => $game->id]);
                continue;
            }

            // Shared record: anonymise the requesting user's references to 0.
            $update = new stdClass();
            $update->id = $game->id;
            $needsupdate = false;

            if ((int)$game->playera === $userid) {
                $update->playera = 0;
                $needsupdate = true;
            }
            if ((int)$game->playerb === $userid) {
                $update->playerb = 0;
                $needsupdate = true;
            }
            if ((int)$game->currentturn === $userid) {
                $update->currentturn = 0;
                $needsupdate = true;
            }
            if ((int)$game->lastplayer === $userid) {
                $update->lastplayer = 0;
                $needsupdate = true;
            }

            if ($needsupdate) {
                $update->timemodified = time();
                $DB->update_record('collabmatch_game', $update);
            }
        }
    }
}
