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
 * English language strings for the collabmatch activity module.
 *
 * Moodle uses these strings throughout the plugin interface.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Plugin names.
 */
$string['pluginname'] = 'Collaborative Match';
$string['modulename'] = 'Collaborative Match';
$string['modulenameplural'] = 'Collaborative Matches';
$string['pluginadministration'] = 'Collaborative Match administration';

/*
 * General activity strings.
 */
$string['collabmatchname'] = 'Activity name';
$string['collabmatchname_help'] = 'Enter a meaningful name for this activity. Examples include Flags of the World, Animals and Habitats, or Great Inventors.';

$string['modulename_help'] = 'Collaborative Match is a turn-based shared-state activity. Learners take turns making moves in response to a shared prompt. The activity state is saved on the server so that another learner can continue from the latest shared state.';

$string['waitingforotherplayer'] = 'Waiting for the other learner to take a turn.';
$string['yourturn'] = 'It is your turn.';
$string['notyourturn'] = 'It is not your turn.';
$string['gamefinished'] = 'This activity is finished.';
$string['inviteplayer'] = 'Invite another learner';
$string['currentboard'] = 'Current board';
$string['score'] = 'Score';
$string['noinstances'] = 'There are no Collaborative Match activities in this course.';

/*
 * Teacher-defined content fields.
 */
$string['prompttext'] = 'Prompt / instruction';
$string['prompttext_help'] = 'Enter the instruction learners should respond to during their turn. Example: Choose the most appropriate collaborative move.';

$string['targetzones'] = 'Target zones - these could be answers to questions (one per line)';
$string['matchpairs'] = 'Match pairs - these could be questions for the answers listed above. Scramble the order if you wish to vary the challenge (one per line)';

$string['targetzones_help'] = 'Enter one target zone per line. These could be answers to questions, categories, destinations, or other valid targets that learner choices can be matched against.';
$string['matchpairs_help'] = 'Enter one match pair per line using the pipe character to separate the item from its correct target zone. These could be questions matched to the answers listed above. Example: Springbok|South Africa';

$string['messageprovider:gameinvite'] = 'Game invitation';
$string['messageprovider:gameinvite_desc'] = 'Notifications sent when one learner invites another to a Collaborative Match game.';


$string['teacherinfotext'] = 'Enter one target zone per line, then enter one item-to-zone pair per line using the pipe character. Example pair: Springbok|South Africa. Later, learners will choose items and zones, and the activity will evaluate whether the zone is correct.';

/*
 * Legacy strings still used by earlier steps of the prototype.
 */
$string['moveoptions'] = 'Move options (one per line)';
$string['moveoptions_help'] = 'Enter one move option per line. Each non-empty line becomes a selectable learner option in the activity. This field was used in the earlier prototype stage before target zones and correctness logic were introduced.';

/*
 * Privacy metadata placeholder.
 */
$string['privacy:metadata'] = 'The Collaborative Match activity stores shared game state, teacher-defined matching content, and learner moves for participating users.';