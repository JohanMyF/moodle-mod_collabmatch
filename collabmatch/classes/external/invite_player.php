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
 * External service: invite another learner to a game.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabmatch\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/messagelib.php');
require_once($CFG->dirroot . '/mod/collabmatch/lib.php');

use context_module;
use core_user;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use moodle_exception;
use stdClass;

/**
 * Class invite_player
 *
 * Handles inviting another learner to a game.
 *
 * @package mod_collabmatch
 */
class invite_player extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'inviteeuserid' => new external_value(PARAM_INT, 'User ID of learner being invited'),
        ]);
    }

    /**
     * Send an invitation and create an invited game row.
     *
     * @param int $cmid Course module ID
     * @param int $inviteeuserid User ID of invitee
     * @return array Result data
     */
    public static function execute(int $cmid, int $inviteeuserid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'inviteeuserid' => $inviteeuserid,
        ]);

        $cm = get_coursemodule_from_id('collabmatch', $params['cmid'], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

        require_login($course, true, $cm);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/collabmatch:view', $context);

        $inviteeuserid = (int)$params['inviteeuserid'];

        if ($inviteeuserid <= 0 || $inviteeuserid === (int)$USER->id) {
            throw new moodle_exception('invalidinvitee', 'mod_collabmatch');
        }

        $invitee = core_user::get_user($inviteeuserid, '*', MUST_EXIST);
        if ((int)$invitee->deleted === 1 || (int)$invitee->suspended === 1) {
            throw new moodle_exception('invalidinvitee', 'mod_collabmatch');
        }

        // Require the invitee to be enrolled on the course.
        $coursecontext = \context_course::instance($course->id);
        if (!is_enrolled($coursecontext, $inviteeuserid, '', true)) {
            throw new moodle_exception('inviteenotenrolled', 'mod_collabmatch');
        }

        // Prevent either learner from being tied up in another non-finished game for this activity.
        $busyparams = [
            $collabmatch->id,
            'active',
            'invited',
            'waiting',
            $USER->id,
            $USER->id,
            $inviteeuserid,
            $inviteeuserid,
        ];

        $busyrecord = $DB->get_records_sql(
            "SELECT *
               FROM {collabmatch_game}
              WHERE collabmatchid = ?
                AND status IN (?, ?, ?)
                AND (
                    playera = ? OR playerb = ? OR
                    playera = ? OR playerb = ?
                )
           ORDER BY id DESC",
            $busyparams,
            0,
            1
        );

        if ($busyrecord) {
            throw new moodle_exception('playeralreadybusy', 'mod_collabmatch');
        }

        $transaction = $DB->start_delegated_transaction();

        $newsession = new stdClass();
        $newsession->collabmatchid = $collabmatch->id;
        $newsession->playera = (int)$USER->id;
        $newsession->playerb = $inviteeuserid;
        $newsession->currentturn = (int)$USER->id;
        $newsession->status = 'invited';
        $newsession->timecreated = time();
        $newsession->timemodified = time();
        $newsession->lastmove = '';
        $newsession->lastplayer = 0;
        $newsession->lastmovetime = 0;

        $newsession->id = $DB->insert_record('collabmatch_game', $newsession);

        // Send Moodle notification.
        $joinurl = new \moodle_url('/mod/collabmatch/view.php', [
            'id' => $cm->id,
            'gameid' => $newsession->id,
            'join' => 1,
        ]);

        $message = new \core\message\message();
        $message->component = 'mod_collabmatch';
        $message->name = 'gameinvite';
        $message->userfrom = $USER;
        $message->userto = $invitee;
        $message->subject = get_string('invitesubject', 'mod_collabmatch', format_string($collabmatch->name));
        $message->fullmessage =
            fullname($USER) . ' ' .
            get_string('invitefullmessage', 'mod_collabmatch') . "\n\n" .
            $joinurl->out(false);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml =
            '<p><strong>' . s(fullname($USER)) . '</strong> ' .
            s(get_string('invitefullmessage', 'mod_collabmatch')) . '</p>' .
            '<p><a href="' . s($joinurl->out(false)) . '">' .
            s(get_string('joingame', 'mod_collabmatch')) .
            '</a></p>';
        $message->smallmessage = get_string('invitesmallmessage', 'mod_collabmatch', fullname($USER));
        $message->notification = 1;
        $message->contexturl = $joinurl->out(false);
        $message->contexturlname = format_string($collabmatch->name);

        message_send($message);

        $transaction->allow_commit();

        return [
            'success' => true,
            'gameid' => (int)$newsession->id,
            'status' => (string)$newsession->status,
            'inviteeuserid' => $inviteeuserid,
            'message' => get_string('invitesent', 'mod_collabmatch', fullname($invitee)),
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether invitation was sent'),
            'gameid' => new external_value(PARAM_INT, 'New game ID'),
            'status' => new external_value(PARAM_TEXT, 'New game status'),
            'inviteeuserid' => new external_value(PARAM_INT, 'User ID invited'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable message'),
        ]);
    }
}