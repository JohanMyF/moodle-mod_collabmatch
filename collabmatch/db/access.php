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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Capability definitions for the CollabMatch activity module.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    /**
     * Add a new CollabMatch instance.
     */
    'mod/collabmatch:addinstance' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,

        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    /**
     * View the CollabMatch activity.
     */
    'mod/collabmatch:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,

        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /**
     * Play the game (submit moves, join games, take turns).
     */
    'mod/collabmatch:play' => [
        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,

        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /**
     * Manage games (reset, override, inspect).
     */
    'mod/collabmatch:manage' => [
        'riskbitmask' => RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,

        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];