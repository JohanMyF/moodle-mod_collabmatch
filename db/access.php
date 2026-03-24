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
 * Capability definitions for the collabmatch activity module.
 *
 * Capabilities tell Moodle who is allowed to do what.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    /*
     * Who is allowed to add this activity to a course?
     *
     * Typically teachers, course creators, and managers.
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

    /*
     * Who is allowed to view the activity?
     *
     * Both teachers and students should be able to open it.
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

    /*
     * Who is allowed to play / take a turn / submit a move?
     *
     * This is the learner interaction capability.
     */
    'mod/collabmatch:play' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,

        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Who is allowed to manage or review game instances?
     *
     * This will be useful later for teacher oversight,
     * reset options, diagnostics, and reporting.
     */
    'mod/collabmatch:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,

        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];