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
 * External service: retrieve the current game state for the user.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabmatch\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Class get_state
 *
 * Returns the latest game state for the current user.
 *
 * @package mod_collabmatch
 */
class get_state extends external_api {

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
     * Execute the service.
     *
     * @param int $cmid Course module ID
     * @return array Game state data
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        $cm = get_coursemodule_from_id('collabmatch', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/collabmatch:view', $context);

        // Get latest game for user.
        $game = $DB->get_records_select(
            'collabmatch_game',
            'collabmatchid = ? AND (playera = ? OR playerb = ?)',
            [$cm->instance, $USER->id, $USER->id],
            'id DESC',
            '*',
            0,
            1
        );

        $game = $game ? reset($game) : false;

        if (!$game) {
            return [
                'hasgame' => false,
                'gameid' => 0,
                'status' => '',
                'currentturn' => 0,
                'timemodified' => 0,
            ];
        }

        return [
            'hasgame' => true,
            'gameid' => (int)$game->id,
            'status' => (string)$game->status,
            'currentturn' => (int)$game->currentturn,
            'timemodified' => (int)$game->timemodified,
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'hasgame' => new external_value(PARAM_BOOL, 'Whether the user has a game'),
            'gameid' => new external_value(PARAM_INT, 'Game ID'),
            'status' => new external_value(PARAM_TEXT, 'Game status'),
            'currentturn' => new external_value(PARAM_INT, 'User ID of current turn'),
            'timemodified' => new external_value(PARAM_INT, 'Last modification time'),
        ]);
    }
}