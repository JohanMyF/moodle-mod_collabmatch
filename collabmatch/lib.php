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
 * Core library functions for the CollabMatch activity module.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */






defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

/**
 * ============================================
 * FEATURE SUPPORT
 * ============================================
 */
function collabmatch_supports($feature) {
    switch ($feature) {

        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;

        case FEATURE_COMPLETION_HAS_RULES:
            return false;

        case FEATURE_GRADE_HAS_GRADE:
            return true;

        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

/**
 * ============================================
 * ADD INSTANCE
 * ============================================
 */
function collabmatch_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    if (!isset($data->grade) || $data->grade === '') {
        $data->grade = 100;
    }

    if (!isset($data->passingpercentage) || $data->passingpercentage === '') {
        $data->passingpercentage = 50;
    }

    $id = $DB->insert_record('collabmatch', $data);

    $collabmatch = $DB->get_record('collabmatch', ['id' => $id], '*', MUST_EXIST);
    collabmatch_grade_item_update($collabmatch);

    return $id;
}

/**
 * ============================================
 * UPDATE INSTANCE
 * ============================================
 */
function collabmatch_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    if (!isset($data->grade) || $data->grade === '') {
        $data->grade = 100;
    }

    if (!isset($data->passingpercentage) || $data->passingpercentage === '') {
        $data->passingpercentage = 50;
    }

    $result = $DB->update_record('collabmatch', $data);

    $collabmatch = $DB->get_record('collabmatch', ['id' => $data->id], '*', MUST_EXIST);
    collabmatch_grade_item_update($collabmatch);
    collabmatch_update_grades($collabmatch);

    return $result;
}

/**
 * ============================================
 * 🔥 NEW: DELETE A SINGLE GAME SAFELY
 * ============================================
 *
 * This is now the SINGLE SOURCE OF TRUTH
 * for deleting a game and its related data.
 *
 * Parent → Child → Grandchild logic:
 *   Game → Moves
 *
 * @param int $gameid
 * @return void
 */
function collabmatch_delete_game($gameid) {
    global $DB;

    // First delete all moves for this game
    $DB->delete_records('collabmatch_move', ['gameid' => $gameid]);

    // Then delete the game itself
    $DB->delete_records('collabmatch_game', ['id' => $gameid]);
}

/**
 * ============================================
 * DELETE INSTANCE (ACTIVITY)
 * ============================================
 */
function collabmatch_delete_instance($id) {
    global $DB;

    if (!$collabmatch = $DB->get_record('collabmatch', ['id' => $id])) {
        return false;
    }

    // 🔁 Get all games for this activity
    $games = $DB->get_records('collabmatch_game', ['collabmatchid' => $collabmatch->id], '', 'id');

    // 🔁 Delete each game using the shared function
    foreach ($games as $game) {
        collabmatch_delete_game($game->id);
    }

    // Delete main activity record
    $DB->delete_records('collabmatch', ['id' => $collabmatch->id]);

    // Clean up gradebook
    collabmatch_grade_item_delete($collabmatch);

    return true;
}

/**
 * ============================================
 * GRADE FUNCTIONS (UNCHANGED)
 * ============================================
 */
function collabmatch_grade_item_update($collabmatch, $grades = null) {
    $itemdetails = [
        'itemname' => $collabmatch->name,
        'idnumber' => $collabmatch->id,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => (float)$collabmatch->grade,
        'grademin' => 0,
    ];

    $passingpercentage = isset($collabmatch->passingpercentage) ? (float)$collabmatch->passingpercentage : 50.0;
    $grademax = isset($collabmatch->grade) ? (float)$collabmatch->grade : 100.0;

    $itemdetails['gradepass'] = round(($passingpercentage / 100) * $grademax, 2);

    return grade_update(
        'mod/collabmatch',
        $collabmatch->course,
        'mod',
        'collabmatch',
        $collabmatch->id,
        0,
        $grades,
        $itemdetails
    );
}

function collabmatch_grade_item_delete($collabmatch) {
    return grade_update(
        'mod/collabmatch',
        $collabmatch->course,
        'mod',
        'collabmatch',
        $collabmatch->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * ============================================
 * REMAINING FUNCTIONS UNCHANGED
 * ============================================
 */

function collabmatch_count_total_pairs($collabmatch) {
    if (empty($collabmatch->matchpairs)) {
        return 0;
    }

    $count = 0;
    $pairlines = preg_split('/\r\n|\r|\n/', $collabmatch->matchpairs);

    foreach ($pairlines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (substr_count($line, '|') !== 1) {
            continue;
        }

        list($item, $zone) = array_map('trim', explode('|', $line, 2));
        if ($item !== '' && $zone !== '') {
            $count++;
        }
    }

    return $count;
}

function collabmatch_calculate_user_percentage($collabmatch, $userid) {
    global $DB;

    $totalpairs = collabmatch_count_total_pairs($collabmatch);
    if ($totalpairs <= 0) {
        return null;
    }

    $games = $DB->get_records_select(
        'collabmatch_game',
        'collabmatchid = ? AND status = ? AND (playera = ? OR playerb = ?)',
        [$collabmatch->id, 'finished', $userid, $userid],
        '',
        'id'
    );

    if (!$games) {
        return null;
    }

    $bestpercentage = null;

    foreach ($games as $game) {
        $correctcount = $DB->count_records('collabmatch_move', [
            'gameid' => $game->id,
            'userid' => $userid,
            'correct' => 1,
        ]);

        $percentage = ($correctcount / $totalpairs) * 100;

        if ($bestpercentage === null || $percentage > $bestpercentage) {
            $bestpercentage = $percentage;
        }
    }

    return $bestpercentage;
}

function collabmatch_update_grades($collabmatch, $userid = 0) {
    global $DB;

    $grades = [];
    $grademax = isset($collabmatch->grade) ? (float)$collabmatch->grade : 100.0;

    if ($userid) {
        $percentage = collabmatch_calculate_user_percentage($collabmatch, $userid);

        if ($percentage !== null) {
            $grades[$userid] = (object)[
                'userid' => $userid,
                'rawgrade' => round(($percentage / 100) * $grademax, 2),
            ];
        }

        collabmatch_grade_item_update($collabmatch, $grades ? $grades : null);
        return;
    }

    $games = $DB->get_records('collabmatch_game', ['collabmatchid' => $collabmatch->id, 'status' => 'finished'], '', 'playera, playerb');

    if (!$games) {
        collabmatch_grade_item_update($collabmatch, null);
        return;
    }

    $userids = [];
    foreach ($games as $game) {
        if (!empty($game->playera)) {
            $userids[$game->playera] = $game->playera;
        }
        if (!empty($game->playerb)) {
            $userids[$game->playerb] = $game->playerb;
        }
    }

    foreach ($userids as $uid) {
        $percentage = collabmatch_calculate_user_percentage($collabmatch, $uid);
        if ($percentage !== null) {
            $grades[$uid] = (object)[
                'userid' => $uid,
                'rawgrade' => round(($percentage / 100) * $grademax, 2),
            ];
        }
    }

    collabmatch_grade_item_update($collabmatch, $grades ? $grades : null);
}