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
 * Backup steps for mod_collabmatch.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete CollabMatch structure for backup.
 *
 * This backs up the activity settings only.
 * It deliberately does not back up live game sessions or move history.
 *
 * @package    mod_collabmatch
 */
class backup_collabmatch_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure for the backup file.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $collabmatch = new backup_nested_element('collabmatch', ['id'], [
            'name',
            'intro',
            'introformat',
            'prompttext',
            'targetzones',
            'matchpairs',
            'grade',
            'passingpercentage',
            'timecreated',
            'timemodified',
        ]);

        $collabmatch->set_source_table('collabmatch', ['id' => backup::VAR_ACTIVITYID]);

        // Intro files are not annotated here yet.
        // This keeps duplication safe for plain-text descriptions.

        return $this->prepare_activity_structure($collabmatch);
    }
}
