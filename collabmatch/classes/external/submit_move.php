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
 * External service: submit one move for the current user.
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
use stdClass;

/**
 * Class submit_move
 *
 * Handles submission of a learner move.
 *
 * @package mod_collabmatch
 */
class submit_move extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'item' => new external_value(PARAM_TEXT, 'Item selected by the learner'),
            'zone' => new external_value(PARAM_TEXT, 'Zone selected by the learner'),
        ]);
    }

    public static function execute(int $cmid, string $item, string $zone): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'item' => $item,
            'zone' => $zone,
        ]);

        $cm = get_coursemodule_from_id('collabmatch', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

        require_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/collabmatch:view', $context);

        $item = trim($params['item']);
        $zone = trim($params['zone']);

        if ($item === '' || $zone === '') {
            throw new moodle_exception('invalidparameter', 'error');
        }

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
            throw new moodle_exception('nogame', 'mod_collabmatch');
        }

        if ((int)$game->currentturn !== (int)$USER->id) {
            throw new moodle_exception('notyourturn', 'mod_collabmatch');
        }

        $zones = [];
        if (!empty($collabmatch->targetzones)) {
            $zonelines = preg_split('/\r\n|\r|\n/', $collabmatch->targetzones);
            foreach ($zonelines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $zones[$line] = true;
                }
            }
        }

        $pairs = [];
        if (!empty($collabmatch->matchpairs)) {
            $pairlines = preg_split('/\r\n|\r|\n/', $collabmatch->matchpairs);
            foreach ($pairlines as $line) {
                $line = trim($line);
                if ($line === '' || substr_count($line, '|') !== 1) {
                    continue;
                }

                [$pairitem, $pairzone] = array_map('trim', explode('|', $line, 2));
                if ($pairitem !== '' && $pairzone !== '') {
                    $pairs[$pairitem] = $pairzone;
                }
            }
        }

        if (!array_key_exists($item, $pairs)) {
            throw new moodle_exception('invaliditem', 'mod_collabmatch');
        }

        if (!array_key_exists($zone, $zones)) {
            throw new moodle_exception('invalidzone', 'mod_collabmatch');
        }

        $existingmoves = $DB->get_records('collabmatch_move', ['gameid' => $game->id], 'id ASC', 'id, choice');
        foreach ($existingmoves as $existingmove) {
            if (!empty($existingmove->choice) && strpos($existingmove->choice, '->') !== false) {
                [$useditem] = array_map('trim', explode('->', $existingmove->choice, 2));
                if ($useditem === $item) {
                    throw new moodle_exception('itemalreadyused', 'mod_collabmatch');
                }
            }
        }

        $correct = ((string)$pairs[$item] === (string)$zone) ? 1 : 0;

        $transaction = $DB->start_delegated_transaction();

        $move = new stdClass();
        $move->gameid = $game->id;
        $move->userid = $USER->id;
        $move->choice = $item . ' -> ' . $zone;
        $move->correct = $correct;
        $move->timecreated = time();
        $DB->insert_record('collabmatch_move', $move);

        $game->lastmove = $move->choice;
        $game->lastplayer = $USER->id;
        $game->lastmovetime = $move->timecreated;

        if (!empty($game->playerb)) {
            if ((int)$USER->id === (int)$game->playera) {
                $game->currentturn = (int)$game->playerb;
            } else if ((int)$USER->id === (int)$game->playerb) {
                $game->currentturn = (int)$game->playera;
            }
        } else {
            $game->currentturn = (int)$USER->id;
        }

        $game->timemodified = time();

        $useditems = [];
        foreach ($existingmoves as $existingmove) {
            if (!empty($existingmove->choice) && strpos($existingmove->choice, '->') !== false) {
                [$useditem] = array_map('trim', explode('->', $existingmove->choice, 2));
                if ($useditem !== '') {
                    $useditems[$useditem] = true;
                }
            }
        }
        $useditems[$item] = true;

        $remainingcount = 0;
        foreach ($pairs as $pairitem => $pairzone) {
            if (!isset($useditems[$pairitem])) {
                $remainingcount++;
            }
        }

        if ($remainingcount === 0) {
            $game->status = 'finished';
        }

        $DB->update_record('collabmatch_game', $game);

        collabmatch_update_grades($collabmatch, $USER->id);
        if (!empty($game->playerb)) {
            collabmatch_update_grades($collabmatch, $game->playerb);
        }

        $transaction->allow_commit();

        return [
            'success' => true,
            'gameid' => (int)$game->id,
            'status' => (string)$game->status,
            'currentturn' => (int)$game->currentturn,
            'correct' => (int)$correct,
            'remainingcount' => (int)$remainingcount,
            'message' => get_string('movesubmittedsuccessfully', 'mod_collabmatch'),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the move was accepted'),
            'gameid' => new external_value(PARAM_INT, 'Game ID'),
            'status' => new external_value(PARAM_TEXT, 'Updated game status'),
            'currentturn' => new external_value(PARAM_INT, 'User ID whose turn it is now'),
            'correct' => new external_value(PARAM_INT, '1 if correct, otherwise 0'),
            'remainingcount' => new external_value(PARAM_INT, 'How many items remain'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable result message'),
        ]);
    }
}
