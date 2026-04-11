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

namespace mod_collabmatch\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when the CollabMatch activity is viewed.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialise event data.
     */
    protected function init(): void {
        $this->data['objecttable'] = 'collabmatch';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Return the localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventcoursemoduleviewed', 'mod_collabmatch');
    }

    /**
     * Return event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' viewed the CollabMatch activity with course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Return the URL for this event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/collabmatch/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Return mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'collabmatch', 'restore' => 'collabmatch'];
    }
}