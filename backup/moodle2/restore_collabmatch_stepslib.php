<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore steps for mod_collabmatch.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore the collabmatch activity.
 */
class restore_collabmatch_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure paths to process.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('collabmatch', '/activity/collabmatch');

        // IMPORTANT:
        // Wrap the module's custom paths in Moodle's standard activity restore structure.
        // This is required for proper activity restore processing.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the main activity data.
     *
     * @param array|stdClass $data
     */
    protected function process_collabmatch($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // This restored activity belongs to the destination course.
        $data->course = $this->get_courseid();

        // Insert the main activity record.
        $newitemid = $DB->insert_record('collabmatch', $data);

        // Register this as the restored activity instance.
        $this->apply_activity_instance($newitemid);

        // Store the mapping with file restore support enabled.
        $this->set_mapping('collabmatch', $oldid, $newitemid, true);
    }

    /**
     * After execution.
     */
    protected function after_execute() {
        // No related files restored yet.
    }
}