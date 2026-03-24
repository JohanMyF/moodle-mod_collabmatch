<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup steps for mod_collabmatch.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete collabmatch structure for backup.
 *
 * This backs up the activity settings only.
 * It deliberately does NOT back up live game sessions or move history.
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
            'timemodified'
        ]);

        $collabmatch->set_source_table('collabmatch', ['id' => backup::VAR_ACTIVITYID]);

        // IMPORTANT:
        // We are NOT annotating intro files yet.
        // This keeps duplication safe for plain-text descriptions.

        return $this->prepare_activity_structure($collabmatch);
    }
}