<?php
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
 * External service: expire the current learner turn when time runs out.
 *
 * @package    mod_collabmatch
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
     * Rule:
     * - no point awarded
     * - turn passes to the other learner
     * - a timeout message is recorded as the last move
     *
     * This method is intentionally tolerant:
     * if the turn has already changed, it returns a harmless response
     * instead of throwing an exception.
     *
     * @param int $cmid
     * @return array
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

        // Quietly ignore stale calls when no active game exists.
        if (!$game) {
            return [
                'success' => false,
                'gameid' => 0,
                'status' => 'finished',
                'currentturn' => 0,
                'message' => 'No active game found.',
            ];
        }

        // Quietly ignore stale calls when the turn already moved on.
        if ((int)$game->currentturn !== (int)$USER->id) {
            return [
                'success' => false,
                'gameid' => (int)$game->id,
                'status' => (string)$game->status,
                'currentturn' => (int)$game->currentturn,
                'message' => 'Turn already changed.',
            ];
        }

        $transaction = $DB->start_delegated_transaction();

        $timeoutmessage = get_string('turnexpiredmessage', 'mod_collabmatch', fullname($USER));

        $game->lastmove = $timeoutmessage;
        $game->lastplayer = (int)$USER->id;
        $game->lastmovetime = time();

        // Multiplayer: pass turn to the other learner.
        if (!empty($game->playerb)) {
            if ((int)$USER->id === (int)$game->playera) {
                $game->currentturn = (int)$game->playerb;
            } else {
                $game->currentturn = (int)$game->playera;
            }
        } else {
            // Single-player mode: keep the turn on the same learner.
            $game->currentturn = (int)$USER->id;
        }

        $game->timemodified = time();
        $DB->update_record('collabmatch_game', $game);

        // Grades do not change, but keep grade state in sync.
        collabmatch_update_grades($collabmatch, $USER->id);
        if (!empty($game->playerb)) {
            if ((int)$USER->id === (int)$game->playera) {
                collabmatch_update_grades($collabmatch, $game->playerb);
            } else {
                collabmatch_update_grades($collabmatch, $game->playera);
            }
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