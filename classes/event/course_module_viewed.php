<?php
namespace mod_collabmatch\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when the CollabMatch activity is viewed.
 *
 * @package    mod_collabmatch
 * @copyright  Johan
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