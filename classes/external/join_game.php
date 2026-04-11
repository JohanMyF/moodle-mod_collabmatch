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
 * External service: join an invited game.
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
use moodle_exception;

/**
 * Class join_game
 *
 * Accepts an invitation and activates a game.
 *
 * @package mod_collabmatch
 */
class join_game extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'gameid' => new external_value(PARAM_INT, 'Game ID to join'),
        ]);
    }

    /**
     * Accept an invitation and activate the game.
     *
     * @param int $cmid Course module ID
     * @param int $gameid Game ID
     * @return array Result data
     */
    public static function execute(int $cmid, int $gameid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'gameid' => $gameid,
        ]);

        $cm = get_coursemodule_from_id('collabmatch', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

        require_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/collabmatch:view', $context);

        $gameid = (int)$params['gameid'];

        $game = $DB->get_record(
            'collabmatch_game',
            ['id' => $gameid, 'collabmatchid' => $collabmatch->id],
            '*',
            MUST_EXIST
        );

        if ((int)$game->playerb !== (int)$USER->id) {
            throw new moodle_exception('notinvitedtothisgame', 'mod_collabmatch');
        }

        if ($game->status !== 'invited') {
            throw new moodle_exception('gamecannotbejoined', 'mod_collabmatch');
        }

        $transaction = $DB->start_delegated_transaction();

        $game->status = 'active';
        $game->timemodified = time();
        $DB->update_record('collabmatch_game', $game);

        // Update grades now that the invited learner has entered the game.
        collabmatch_update_grades($collabmatch, $USER->id);

        $transaction->allow_commit();

        return [
            'success' => true,
            'gameid' => (int)$game->id,
            'status' => (string)$game->status,
            'currentturn' => (int)$game->currentturn,
            'message' => get_string('gamejoined', 'mod_collabmatch'),
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the learner joined the game'),
            'gameid' => new external_value(PARAM_INT, 'Game ID'),
            'status' => new external_value(PARAM_TEXT, 'Updated game status'),
            'currentturn' => new external_value(PARAM_INT, 'User ID whose turn it is'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable message'),
        ]);
    }
}