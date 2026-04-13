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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main view page for the CollabMatch activity.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/collabmatch/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('collabmatch', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/collabmatch:view', $context);

$PAGE->set_url(new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]));
$PAGE->set_context($context);
$PAGE->set_title(format_string($collabmatch->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

$event = \mod_collabmatch\event\course_module_viewed::create([
    'objectid' => $collabmatch->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('collabmatch', $collabmatch);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$currentuserid = (int)$USER->id;

/**
 * Parse line-based textarea content into trimmed rows.
 *
 * @param string|null $rawtext
 * @return array
 */
function mod_collabmatch_parse_lines(?string $rawtext): array {
    if (empty($rawtext)) {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($rawtext));
    $results = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $results[] = $line;
        }
    }

    return $results;
}

/**
 * Parse pipe-delimited match pairs into item => zone form.
 *
 * @param string|null $rawpairs
 * @return array
 */
function mod_collabmatch_parse_pairs(?string $rawpairs): array {
    if (empty($rawpairs)) {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($rawpairs));
    $pairs = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || substr_count($line, '|') !== 1) {
            continue;
        }

        [$item, $zone] = array_map('trim', explode('|', $line, 2));
        if ($item !== '' && $zone !== '') {
            $pairs[$item] = $zone;
        }
    }

    return $pairs;
}

$zones = mod_collabmatch_parse_lines($collabmatch->targetzones ?? '');
$pairs = mod_collabmatch_parse_pairs($collabmatch->matchpairs ?? '');

$timerenabled = !empty($collabmatch->timerenabled);
$timeseconds = isset($collabmatch->timeseconds) ? max(5, (int)$collabmatch->timeseconds) : 60;

$howitworkstext = trim((string)($collabmatch->howitworkstext ?? ''));
if ($howitworkstext === '') {
    $howitworkstext = get_string('defaulthowitworkstext', 'mod_collabmatch');
}
$howitworkstext = str_replace('{seconds}', (string)$timeseconds, $howitworkstext);

$currentgame = false;
$usergames = $DB->get_records_select(
    'collabmatch_game',
    'collabmatchid = ? AND (playera = ? OR playerb = ?)',
    [$collabmatch->id, $currentuserid, $currentuserid],
    'id DESC'
);

foreach ($usergames as $candidate) {
    if (in_array($candidate->status, ['active', 'waiting', 'invited', 'finished'], true)) {
        $currentgame = $candidate;
        break;
    }
}

$allmoves = [];
$remainingpairs = $pairs;
$playerascore = 0;
$playerbscore = 0;

if ($currentgame) {
    $allmoves = $DB->get_records('collabmatch_move', ['gameid' => $currentgame->id], 'id ASC');

    if ($allmoves) {
        $useditems = [];
        foreach ($allmoves as $move) {
            if (!empty($move->choice) && strpos($move->choice, '->') !== false) {
                [$useditem] = explode('->', $move->choice, 2);
                $useditem = trim($useditem);
                if ($useditem !== '') {
                    $useditems[$useditem] = true;
                }
            }
        }

        foreach (array_keys($useditems) as $useditem) {
            unset($remainingpairs[$useditem]);
        }
    }

    $scoresql = "SELECT userid, COUNT(id) AS score
                   FROM {collabmatch_move}
                  WHERE gameid = ? AND correct = 1
               GROUP BY userid";
    $scores = $DB->get_records_sql($scoresql, [$currentgame->id]);

    $playerascore = isset($scores[$currentgame->playera]) ? (int)$scores[$currentgame->playera]->score : 0;
    $playerbscore = !empty($currentgame->playerb) && isset($scores[$currentgame->playerb])
        ? (int)$scores[$currentgame->playerb]->score
        : 0;
}

$availableusers = [];
if (!$currentgame || $currentgame->status === 'finished') {
    $cutoff = time() - 180;

    // Only block users who are already tied up with the current user.
    $busygames = $DB->get_records_select(
        'collabmatch_game',
        'collabmatchid = ? AND status IN (?, ?, ?)',
        [$collabmatch->id, 'active', 'invited', 'waiting'],
        '',
        'playera, playerb'
    );

    $busyuserids = [];
    foreach ($busygames as $busygame) {
        $playera = (int)$busygame->playera;
        $playerb = (int)$busygame->playerb;

        if ($playera === $currentuserid && $playerb > 0) {
            $busyuserids[$playerb] = true;
        }

        if ($playerb === $currentuserid && $playera > 0) {
            $busyuserids[$playera] = true;
        }
    }

    $onlineusers = $DB->get_records_sql(
        "SELECT u.*
           FROM {user} u
           JOIN {user_lastaccess} ula
             ON ula.userid = u.id
            AND ula.courseid = ?
          WHERE ula.timeaccess >= ?
            AND u.id <> ?
            AND u.deleted = 0
            AND u.suspended = 0
       ORDER BY ula.timeaccess DESC, u.lastname ASC, u.firstname ASC",
        [$course->id, $cutoff, $currentuserid]
    );

    foreach ($onlineusers as $onlineuser) {
        if (!isset($busyuserids[(int)$onlineuser->id])) {
            $availableusers[] = $onlineuser;
        }
    }
}

$usersbyid = [];
$recentmoves = $allmoves ? array_slice(array_reverse(array_values($allmoves)), 0, 4) : [];

if ($currentgame) {
    $userfields = 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename';

    if (!empty($currentgame->playera)) {
        $playerauser = $DB->get_record('user', ['id' => (int)$currentgame->playera], $userfields);
        if ($playerauser) {
            $usersbyid[(int)$currentgame->playera] = $playerauser;
        }
    }

    if (!empty($currentgame->playerb) && (int)$currentgame->playerb !== (int)$currentgame->playera) {
        $playerbuser = $DB->get_record('user', ['id' => (int)$currentgame->playerb], $userfields);
        if ($playerbuser) {
            $usersbyid[(int)$currentgame->playerb] = $playerbuser;
        }
    }
}

$playeraname = get_string('unknownuser');
$playerbname = get_string('none');

if ($currentgame && !empty($currentgame->playera) && !empty($usersbyid[$currentgame->playera])) {
    $playeraname = fullname($usersbyid[$currentgame->playera]);
}

if ($currentgame && !empty($currentgame->playerb) && !empty($usersbyid[$currentgame->playerb])) {
    $playerbname = fullname($usersbyid[$currentgame->playerb]);
}

$myturn = $currentgame
    && $currentgame->status === 'active'
    && (int)$currentgame->currentturn === $currentuserid;

$defaultitem = !empty($remainingpairs) ? reset(array_keys($remainingpairs)) : '';
$defaultzone = !empty($zones) ? reset($zones) : '';

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_collabmatch');

echo html_writer::start_div('collabmatch-min');
echo $renderer->render_help_panel($howitworkstext);

$totalpairs = collabmatch_count_total_pairs($collabmatch);

if ($currentgame && $currentgame->status === 'finished') {
    $issologame = empty($currentgame->playerb);
    $youname = (int)$currentgame->playera === $currentuserid ? $playeraname : $playerbname;
    $youscore = (int)$currentgame->playera === $currentuserid ? $playerascore : $playerbscore;
    $partnername = (int)$currentgame->playera === $currentuserid ? $playerbname : $playeraname;
    $partnerscore = (int)$currentgame->playera === $currentuserid ? $playerbscore : $playerascore;

    echo $renderer->render_finished_state(
        $issologame,
        $youname,
        $youscore,
        $partnername,
        $partnerscore,
        $totalpairs
    );

    echo html_writer::start_div('', [
        'id' => 'collabmatch-delayed-options',
        'style' => 'display:none;',
    ]);
    echo $renderer->render_empty_state($availableusers);
    echo html_writer::end_div();

    $PAGE->requires->js_call_amd('mod_collabmatch/delayed_reveal', 'init', [
        'collabmatch-delayed-options',
        5000,
    ]);

    echo html_writer::end_div();

    $PAGE->requires->js_call_amd('mod_collabmatch/help_panel', 'init', [
        'collabmatch-help-btn',
        'collabmatch-how-panel',
        5000,
    ]);
    $PAGE->requires->js_call_amd('mod_collabmatch/poller', 'init', [$cm->id]);
    $PAGE->requires->js_call_amd('mod_collabmatch/invite_sender', 'init', [$cm->id]);
    $PAGE->requires->js_call_amd('mod_collabmatch/join_handler', 'init', [$cm->id]);
    $PAGE->requires->js_call_amd('mod_collabmatch/start_single_player', 'init', [$cm->id]);

    echo $OUTPUT->footer();
    exit;
}

if ($currentgame && $currentgame->status === 'active') {
    echo $renderer->render_turn_banner($myturn, $timerenabled, $timeseconds);
}

if (!$currentgame) {
    echo $renderer->render_empty_state($availableusers);
    echo html_writer::end_div();

    $PAGE->requires->js_call_amd('mod_collabmatch/help_panel', 'init', [
        'collabmatch-help-btn',
        'collabmatch-how-panel',
        5000,
    ]);
    $PAGE->requires->js_call_amd('mod_collabmatch/poller', 'init', [$cm->id]);
    $PAGE->requires->js_call_amd('mod_collabmatch/invite_sender', 'init', [$cm->id]);
    $PAGE->requires->js_call_amd('mod_collabmatch/join_handler', 'init', [$cm->id]);
    $PAGE->requires->js_call_amd('mod_collabmatch/start_single_player', 'init', [$cm->id]);

    echo $OUTPUT->footer();
    exit;
}

if ($currentgame->status === 'invited' && (int)$currentgame->playerb === $currentuserid) {
    echo $renderer->render_invited_state((int)$currentgame->id);
}

echo html_writer::start_div('collabmatch-grid');
echo html_writer::start_div('collabmatch-col');

if ($myturn && $currentgame->status === 'active' && !empty($remainingpairs) && !empty($zones)) {
    $actionurl = new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]);
    echo $renderer->render_move_form($actionurl, $remainingpairs, $zones, $defaultitem, $defaultzone);
}

echo html_writer::end_div();

echo html_writer::start_div('collabmatch-col');

$issologame = empty($currentgame->playerb);
$youname = (int)$currentgame->playera === $currentuserid ? $playeraname : $playerbname;
$youscore = (int)$currentgame->playera === $currentuserid ? $playerascore : $playerbscore;
$partnername = (int)$currentgame->playera === $currentuserid ? $playerbname : $playeraname;
$partnerscore = (int)$currentgame->playera === $currentuserid ? $playerbscore : $playerascore;

echo $renderer->render_game_progress(
    $issologame,
    $youname,
    $youscore,
    $partnername,
    $partnerscore,
    $recentmoves,
    $usersbyid
);

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

$PAGE->requires->js_call_amd('mod_collabmatch/help_panel', 'init', [
    'collabmatch-help-btn',
    'collabmatch-how-panel',
    5000,
]);

if ($currentgame->status === 'active' && $myturn) {
    $PAGE->requires->js_call_amd('mod_collabmatch/timer_circle', 'init', [
        'collabmatch-timer',
        $cm->id,
    ]);
}

$PAGE->requires->js_call_amd('mod_collabmatch/poller', 'init', [$cm->id]);
$PAGE->requires->js_call_amd('mod_collabmatch/invite_sender', 'init', [$cm->id]);
$PAGE->requires->js_call_amd('mod_collabmatch/join_handler', 'init', [$cm->id]);
$PAGE->requires->js_call_amd('mod_collabmatch/move_submitter', 'init', [$cm->id, 'collabmatch-move-form']);
$PAGE->requires->js_call_amd('mod_collabmatch/start_single_player', 'init', [$cm->id]);

echo $OUTPUT->footer();
