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
use stdClass;

/**
 * External service: start a single-player game.
 *
 * @package    mod_collabmatch
 */
class start_single_player extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

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
