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
 * Backup task for mod_collabmatch.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/collabmatch/backup/moodle2/backup_collabmatch_stepslib.php');

/**
 * Backup task class for CollabMatch.
 *
 * @package    mod_collabmatch
 */
class backup_collabmatch_activity_task extends backup_activity_task {

    /**
     * No special settings for now.
     */
    protected function define_my_settings() {
        // No activity-specific backup settings.
    }

    /**
     * Define the backup steps for this activity.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_collabmatch_activity_structure_step(
            'collabmatch_structure',
            'collabmatch.xml'
        ));
    }

    /**
     * Encode links to the activity so they can be restored elsewhere.
     *
     * Moodle requires every backup_activity_task subclass
     * to implement this method, even if the activity does not
     * currently need any special link transformations.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the activity by course module id.
        $search = '/(' . $base . '\/mod\/collabmatch\/view\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@COLLABMATCHVIEWBYID*$2@$', $content);

        // Link to the activity index by course id.
        $search = '/(' . $base . '\/mod\/collabmatch\/index\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@COLLABMATCHINDEX*$2@$', $content);

        return $content;
    }
}
