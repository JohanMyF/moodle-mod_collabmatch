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
 * Restore steps for mod_collabmatch.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore the CollabMatch activity.
 *
 * @package    mod_collabmatch
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

        // Wrap the module's custom paths in Moodle's standard activity restore structure.
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

        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('collabmatch', $data);

        $this->apply_activity_instance($newitemid);
        $this->set_mapping('collabmatch', $oldid, $newitemid, true);
    }

    /**
     * After execution.
     */
    protected function after_execute() {
        // No related files restored yet.
    }
}
