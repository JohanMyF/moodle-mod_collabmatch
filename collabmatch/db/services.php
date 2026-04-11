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
 * External service definitions for the CollabMatch activity.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'mod_collabmatch_get_state' => [
        'classname'   => 'mod_collabmatch\\external\\get_state',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get current game state for a user',
        'type'        => 'read',
        'ajax'        => true,
    ],

    'mod_collabmatch_submit_move' => [
        'classname'   => 'mod_collabmatch\\external\\submit_move',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Submit a move in the game',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_collabmatch_invite_player' => [
        'classname'   => 'mod_collabmatch\\external\\invite_player',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Invite another player to a game',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_collabmatch_join_game' => [
        'classname'   => 'mod_collabmatch\\external\\join_game',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Join an invited game',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_collabmatch_start_single_player' => [
        'classname'   => 'mod_collabmatch\\external\\start_single_player',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Start or reopen a single-player game',
        'type'        => 'write',
        'ajax'        => true,
    ],

    'mod_collabmatch_expire_turn' => [
        'classname'   => 'mod_collabmatch\\external\\expire_turn',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Expire the current learner turn when the timer runs out',
        'type'        => 'write',
        'ajax'        => true,
    ],
];