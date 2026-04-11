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
 * English language strings for CollabMatch.
 *
 * @package    mod_collabmatch
 * @category   string
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Collaborative Match';
$string['modulename'] = 'Collaborative Match';
$string['modulenameplural'] = 'Collaborative Matches';
$string['pluginadministration'] = 'Collaborative Match administration';
$string['collabmatchsettings'] = 'Collaborative Match settings';

$string['collabmatchname'] = 'Activity name';
$string['collabmatchname_help'] = 'Enter a meaningful name for this Collaborative Match activity.';

$string['modulename_help'] = 'Collaborative Match is a turn-based shared-state activity for one learner or two learners.';
$string['pluginname_help'] = 'Collaborative Match is a turn-based shared-state activity for one learner or two learners.';

$string['prompttext'] = 'Prompt text';
$string['prompttext_help'] = 'Introductory text shown to learners before they make a move.';

$string['targetzones'] = 'Target zones';
$string['targetzones_help'] = 'Enter one target zone per line.';

$string['matchpairs'] = 'Match pairs';
$string['matchpairs_help'] = 'Enter one pair per line in the format Item | Zone.';

$string['singleplayermode'] = 'Single-player mode';
$string['multiplayermode'] = 'Multiplayer mode';
$string['playmode'] = 'Play mode';

$string['configuredzones'] = 'Configured zones';
$string['configureditems'] = 'Configured items';
$string['setting'] = 'Setting';
$string['value'] = 'Value';
$string['property'] = 'Property';

$string['status'] = 'Status';
$string['active'] = 'Active';
$string['invited'] = 'Invited';
$string['waiting'] = 'Waiting';
$string['finished'] = 'Finished';

$string['playera'] = 'Player A';
$string['playerb'] = 'Player B';
$string['currentturn'] = 'Current turn';
$string['remainingitems'] = 'Remaining items';

$string['participant'] = 'Participant';
$string['score'] = 'Score';
$string['choice'] = 'Choice';
$string['chosenby'] = 'Chosen by';
$string['result'] = 'Result';
$string['lastsharedmove'] = 'Last shared move';

$string['winner'] = 'Winner';
$string['winneris'] = 'Winner: {$a}';
$string['winners'] = 'Winner';
$string['draw'] = 'Draw';

$string['correct'] = 'Correct';
$string['incorrect'] = 'Incorrect';

$string['pendinginvites'] = 'Pending invitations';
$string['joingame'] = 'Join game';
$string['gamejoined'] = 'You have joined the game.';
$string['invitesent'] = 'Invitation sent to {$a}.';
$string['invitesubject'] = 'Game invitation: {$a}';
$string['invitefullmessage'] = 'has invited you to join a Collaborative Match game.';
$string['invitesmallmessage'] = 'Game invitation from {$a}';

$string['nogameyet'] = 'No game yet';
$string['nomovesyet'] = 'No moves yet';
$string['interactivitymovedlater'] = 'Interactive move controls will be connected in a later build step.';
$string['viewphpnointactions'] = 'This page is currently using the new compliant architecture. Some interactive actions may still be under construction.';

$string['eventcoursemoduleviewed'] = 'Collaborative Match activity viewed';

$string['nogame'] = 'No active game was found for you in this activity.';
$string['notyourturn'] = 'It is not your turn.';
$string['invaliditem'] = 'The selected item is not valid.';
$string['invalidzone'] = 'The selected zone is not valid.';
$string['itemalreadyused'] = 'That item has already been used.';
$string['invalidinvitee'] = 'The selected learner cannot be invited.';
$string['inviteenotenrolled'] = 'The selected learner is not enrolled in this course.';
$string['playeralreadybusy'] = 'One of these learners is already busy in another game.';
$string['notinvitedtothisgame'] = 'You were not invited to this game.';
$string['gamecannotbejoined'] = 'This game can no longer be joined.';

$string['singleplayernotenabled'] = 'Single-player mode is not enabled for this activity.';
$string['singleplayergamereused'] = 'Your existing single-player game has been reopened.';
$string['singleplayergamestarted'] = 'A new single-player game has been started.';

$string['teacherinfotext'] = 'Teacher information';
$string['descriptionguidance'] = 'Put full learner instructions in the Description box above and tick "Display description on course page" if you want learners to see the instructions before opening the activity.';
$string['collabmatchcontent'] = 'Collaborative Match content';
$string['defaultprompttext'] = 'Choose the most appropriate collaborative move.';
$string['gradingguidance'] = 'Use Moodle’s standard grading controls above. Set the maximum grade there, and if you want completion by pass, set the Grade to pass in the gradebook or grading settings.';
$string['errorpromptrequired'] = 'Please enter a prompt or instruction for learners.';
$string['errorzonecount'] = 'Please enter at least one target zone.';
$string['errorpaircount'] = 'Please enter at least one match pair.';
$string['errorpairformat'] = 'Each match pair must use exactly one pipe character, like this: Springbok|South Africa';
$string['errorpairemptyparts'] = 'Each match pair must contain both an item and a correct zone.';
$string['teacherinfotext_desc'] = 'Enter one target zone per line, and one item-to-zone pair per line using the format Item|Zone. These settings define the playable content of the activity.';

$string['timerenabled'] = 'Enable turn timer';
$string['timerenabled_desc'] = 'Show a turn timer in the learner interface.';
$string['timerenabled_help'] = 'When enabled, learners will see a time limit for each turn.';

$string['timeseconds'] = 'Time per turn (seconds)';
$string['timeseconds_help'] = 'Enter the number of seconds learners have for each turn.';

$string['howitworkstext'] = 'How it works text';
$string['howitworkstext_help'] = 'This text is shown when learners click the How it works button. You may refer to the timer in your instructions.';
$string['defaulthowitworkstext'] = 'You have {seconds} seconds to choose your question and your answer. Choose the ones you know your opponent will find easy to do.';
$string['errorhowitworkstextrequired'] = 'Please enter the How it works text.';

$string['errortimesecondsmin'] = 'The timer must be at least 5 seconds.';
$string['errortimesecondsmax'] = 'The timer must not exceed 600 seconds.';

$string['turnexpiredmessage'] = '{$a} ran out of time. No point scored. Turn passed.';

// Strings used by view.php.
$string['howitworksbutton'] = 'ℹ How it works';
$string['yourturn'] = 'Your turn';
$string['pleasewait'] = 'Please wait';
$string['yourturnmessage'] = 'Choose the best answer and submit your move.';
$string['waitingmessage'] = 'Waiting for the other learner to complete the next move.';

$string['playsolo'] = 'Play solo';
$string['playsolodesc'] = 'Start a one-player round and work through the matches at your own pace.';
$string['startsingleplayergame'] = 'Start single-player game';

$string['inviteanotherlearner'] = 'Invite another learner';
$string['noavailablelearners'] = 'No available learners have been active in this course in the last few minutes.';
$string['invite'] = 'Invite';

$string['joingamedesc'] = 'Click Join game to start the shared activity.';

$string['makethebestmatch'] = 'Make the best match';
$string['makethebestmatchdesc'] = 'Choose a question, choose the best answer, then submit your move.';
$string['choosequestion'] = '1. Choose a question';
$string['choosequestiondesc'] = 'Read the list and choose the question you want to play this turn.';
$string['chooseanswer'] = '2. Choose the best answer';
$string['chooseanswerdesc'] = 'Choose the answer that best matches the selected question.';
$string['submitmove'] = 'Submit move';

$string['gameprogress'] = 'Game progress';
$string['you'] = 'You';
$string['partner'] = 'Partner';
$string['versus'] = 'vs';
$string['recentmoves'] = 'Recent moves';

$string['privacy:metadata:collabmatch_game'] = 'Information about shared and single-player CollabMatch game instances.';
$string['privacy:metadata:collabmatch_game:playera'] = 'The user ID of player A in the game.';
$string['privacy:metadata:collabmatch_game:playerb'] = 'The user ID of player B in the game.';
$string['privacy:metadata:collabmatch_game:currentturn'] = 'The user ID whose turn it currently is.';
$string['privacy:metadata:collabmatch_game:lastplayer'] = 'The user ID who made the last move.';
$string['privacy:metadata:collabmatch_game:lastmove'] = 'The last recorded move in the game.';
$string['privacy:metadata:collabmatch_game:lastmovetime'] = 'The time when the last move was made.';
$string['privacy:metadata:collabmatch_game:timecreated'] = 'The time when the game record was created.';
$string['privacy:metadata:collabmatch_game:timemodified'] = 'The time when the game record was last modified.';

$string['privacy:metadata:collabmatch_move'] = 'Information about individual moves made in CollabMatch.';
$string['privacy:metadata:collabmatch_move:userid'] = 'The user ID of the learner who made the move.';
$string['privacy:metadata:collabmatch_move:choice'] = 'The move choice recorded for the learner.';
$string['privacy:metadata:collabmatch_move:correct'] = 'Whether the recorded move was correct.';
$string['privacy:metadata:collabmatch_move:timecreated'] = 'The time when the move was recorded.';

$string['privacy:metadata:core_grades'] = 'CollabMatch sends grade information to Moodle\'s gradebook subsystem.';

$string['privacy:you'] = 'You';
$string['privacy:collabmatchdata'] = 'CollabMatch data';

$string['messageprovider:gameinvite'] = 'Game invitation';

$string['jsinvaliduserselected'] = 'Invalid user selected.';
$string['jssending'] = 'Sending...';
$string['jsinvitationnotsent'] = 'Invitation could not be sent.';

$string['jsinvalidgameselected'] = 'Invalid game selected.';
$string['jsgamenotjoined'] = 'The game could not be joined.';

$string['jsitemzonebothrequired'] = 'Please choose both an item and a zone.';
$string['jsmovesubmitfailed'] = 'The move could not be submitted.';

$string['jssingleplayerstartfailed'] = 'The single-player game could not be started.';

$string['noactivegamefound'] = 'No active game found.';
$string['turnalreadychanged'] = 'Turn already changed.';

$string['cleanupstalegamestask'] = 'Cleanup stale CollabMatch games';
