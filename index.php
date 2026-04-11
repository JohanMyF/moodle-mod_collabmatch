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
 * Index page for all CollabMatch instances in a given course.
 *
 * This page lists all Collaborative Match activities in one course.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url('/mod/collabmatch/index.php', ['id' => $course->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('modulenameplural', 'mod_collabmatch'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('modulenameplural', 'mod_collabmatch'));

if (!$collabmatches = get_all_instances_in_course('collabmatch', $course)) {
    notice(
        get_string('noinstances', 'mod_collabmatch'),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$table->head = [
    get_string('name'),
];

foreach ($collabmatches as $collabmatch) {
    $link = html_writer::link(
        new moodle_url('/mod/collabmatch/view.php', ['id' => $collabmatch->coursemodule]),
        format_string($collabmatch->name)
    );

    $table->data[] = [$link];
}

echo html_writer::table($table);

echo $OUTPUT->footer();