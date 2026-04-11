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
 * External service: expire the current learner turn when time runs out.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabmatch\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/collabmatch/lib.php');

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Class expire_turn
 *
 * Handles automatic expiry of a learner's turn.
 *
 * @package mod_collabmatch
 */
class expire_turn extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Expire the current learner turn.
     *
     * @param int $cmid Course module ID
     * @return array Result data
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        $cm = get_coursemodule_from_id('collabmatch', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

        require_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/collabmatch:view', $context);

        $games = $DB->get_records_select(
            'collabmatch_game',
            'collabmatchid = ? AND (playera = ? OR playerb = ?) AND status = ?',
            [$collabmatch->id, $USER->id, $USER->id, 'active'],
            'id DESC',
            '*',
            0,
            1
        );

        $game = $games ? reset($games) : false;

        if (!$game) {
            return [
                'success' => false,
                'gameid' => 0,
                'status' => 'finished',
                'currentturn' => 0,
                'message' => get_string('noactivegamefound', 'mod_collabmatch'),
            ];
        }

        if ((int)$game->currentturn !== (int)$USER->id) {
            return [
                'success' => false,
                'gameid' => (int)$game->id,
                'status' => (string)$game->status,
                'currentturn' => (int)$game->currentturn,
                'message' => get_string('turnalreadychanged', 'mod_collabmatch'),
            ];
        }

        $transaction = $DB->start_delegated_transaction();

        $timeoutmessage = get_string('turnexpiredmessage', 'mod_collabmatch', fullname($USER));

        $game->lastmove = $timeoutmessage;
        $game->lastplayer = (int)$USER->id;
        $game->lastmovetime = time();

        if (!empty($game->playerb)) {
            if ((int)$USER->id === (int)$game->playera) {
                $game->currentturn = (int)$game->playerb;
            } else {
                $game->currentturn = (int)$game->playera;
            }
        } else {
            $game->currentturn = (int)$USER->id;
        }

        $game->timemodified = time();
        $DB->update_record('collabmatch_game', $game);

        collabmatch_update_grades($collabmatch, $USER->id);

        if (!empty($game->playerb)) {
            $other = ((int)$USER->id === (int)$game->playera) ? $game->playerb : $game->playera;
            collabmatch_update_grades($collabmatch, $other);
        }

        $transaction->allow_commit();

        return [
            'success' => true,
            'gameid' => (int)$game->id,
            'status' => (string)$game->status,
            'currentturn' => (int)$game->currentturn,
            'message' => $timeoutmessage,
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the turn expiry was accepted'),
            'gameid' => new external_value(PARAM_INT, 'Game ID'),
            'status' => new external_value(PARAM_TEXT, 'Updated game status'),
            'currentturn' => new external_value(PARAM_INT, 'User ID whose turn it is now'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable result message'),
        ]);
    }
}