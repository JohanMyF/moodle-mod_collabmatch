<?php
namespace mod_collabmatch\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_module;
use invalid_parameter_exception;

class get_state extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    public static function execute($cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid
        ]);

        $cm = get_coursemodule_from_id('collabmatch', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/collabmatch:view', $context);

        // Get latest game for user
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
                'timemodified' => 0
            ];
        }

        return [
            'hasgame' => true,
            'gameid' => (int)$game->id,
            'status' => (string)$game->status,
            'currentturn' => (int)$game->currentturn,
            'timemodified' => (int)$game->timemodified
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'hasgame' => new external_value(PARAM_BOOL, 'Whether user has a game'),
            'gameid' => new external_value(PARAM_INT, 'Game ID'),
            'status' => new external_value(PARAM_TEXT, 'Game status'),
            'currentturn' => new external_value(PARAM_INT, 'User ID of current turn'),
            'timemodified' => new external_value(PARAM_INT, 'Last modification time'),
        ]);
    }
}
