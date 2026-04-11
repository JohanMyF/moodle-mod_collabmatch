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
 * External service: start a single-player game.
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
use stdClass;

/**
 * Class start_single_player
 *
 * Starts or reuses a single-player game.
 *
 * @package mod_collabmatch
 */
class start_single_player extends external_api {

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
     * Start or reuse a single-player game.
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

        $existinggames = $DB->get_records_select(
            'collabmatch_game',
            'collabmatchid = ? AND playera = ? AND (playerb = 0 OR playerb IS NULL) AND status IN (?, ?, ?)',
            [$collabmatch->id, $USER->id, 'active', 'waiting', 'invited'],
            'id DESC',
            '*',
            0,
            1
        );

        $existinggame = $existinggames ? reset($existinggames) : false;
        if ($existinggame) {
            return [
                'success' => true,
                'gameid' => (int)$existinggame->id,
                'status' => (string)$existinggame->status,
                'currentturn' => (int)$existinggame->currentturn,
                'message' => get_string('singleplayergamereused', 'mod_collabmatch'),
            ];
        }

        $transaction = $DB->start_delegated_transaction();

        $newgame = new stdClass();
        $newgame->collabmatchid = $collabmatch->id;
        $newgame->playera = (int)$USER->id;
        $newgame->playerb = 0;
        $newgame->currentturn = (int)$USER->id;
        $newgame->status = 'active';
        $newgame->timecreated = time();
        $newgame->timemodified = time();
        $newgame->lastmove = '';
        $newgame->lastplayer = 0;
        $newgame->lastmovetime = 0;

        $newgame->id = $DB->insert_record('collabmatch_game', $newgame);

        collabmatch_update_grades($collabmatch, $USER->id);

        $transaction->allow_commit();

        return [
            'success' => true,
            'gameid' => (int)$newgame->id,
            'status' => (string)$newgame->status,
            'currentturn' => (int)$newgame->currentturn,
            'message' => get_string('singleplayergamestarted', 'mod_collabmatch'),
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the game was created or reused'),
            'gameid' => new external_value(PARAM_INT, 'Game ID'),
            'status' => new external_value(PARAM_TEXT, 'Game status'),
            'currentturn' => new external_value(PARAM_INT, 'User ID whose turn it is'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable result message'),
        ]);
    }
}