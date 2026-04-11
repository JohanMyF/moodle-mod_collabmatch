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
 * Restore task for mod_collabmatch.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/collabmatch/backup/moodle2/restore_collabmatch_stepslib.php');

/**
 * Restore task that provides all the settings and steps to perform one
 * complete restore of the CollabMatch activity.
 *
 * @package    mod_collabmatch
 */
class restore_collabmatch_activity_task extends restore_activity_task {

    /**
     * Define any particular settings for this activity.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define the restore steps for this activity.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_collabmatch_activity_structure_step('collabmatch_structure', 'collabmatch.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('collabmatch', ['intro'], 'collabmatch');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Define restore log rules for this activity.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('collabmatch', 'add', 'view.php?id={course_module}', '{collabmatch}');
        $rules[] = new restore_log_rule('collabmatch', 'update', 'view.php?id={course_module}', '{collabmatch}');
        $rules[] = new restore_log_rule('collabmatch', 'view', 'view.php?id={course_module}', '{collabmatch}');

        return $rules;
    }

    /**
     * Define restore log rules for course-level actions.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('collabmatch', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
