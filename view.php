<?php
require('../../config.php');
require_once($CFG->libdir . '/messagelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/collabmatch/lib.php');

$id = required_param('id', PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_BOOL);
$gameid = optional_param('gameid', 0, PARAM_INT);
$join = optional_param('join', 0, PARAM_BOOL);
$newgame = optional_param('newgame', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('collabmatch', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$collabmatch = $DB->get_record('collabmatch', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$currentuserid = $USER->id;
$currentuser = $USER;

/*
 * -------------------------------------------------------------
 * Helper: detect whether this is a real AJAX polling request
 * -------------------------------------------------------------
 *
 * Why this exists:
 * The activity uses ?ajax=1 for polling. If a user later lands on that URL
 * directly after a session timeout/login roundtrip, raw JSON appears in the
 * browser. We therefore only return JSON when the request is clearly an AJAX
 * call made by JavaScript.
 */
function collabmatch_is_real_ajax_request(): bool {
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        return false;
    }

    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/*
 * -------------------------------------------------------------
 * IMPORTANT PAGE SETUP
 * -------------------------------------------------------------
 */
$pageparams = ['id' => $id];
if ($gameid > 0) {
    $pageparams['gameid'] = $gameid;
}
if ($join) {
    $pageparams['join'] = 1;
}
if ($newgame) {
    $pageparams['newgame'] = 1;
}

$PAGE->set_url(new moodle_url('/mod/collabmatch/view.php', $pageparams));
$PAGE->set_context($context);
$PAGE->set_title(format_string($collabmatch->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

/*
 * -------------------------------------------------------------
 * If someone lands directly on ?ajax=1 in a browser, normalize
 * back to the normal activity URL instead of showing raw JSON.
 * -------------------------------------------------------------
 */
if ($ajax && !collabmatch_is_real_ajax_request()) {
    redirect(new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]));
}

/*
 * -------------------------------------------------------------
 * Helper: send invitation notification
 * -------------------------------------------------------------
 */
function collabmatch_send_invitation($currentuser, $invitee, $cm, $newsession, $collabmatch) {
    $joinurl = new moodle_url('/mod/collabmatch/view.php', [
        'id' => $cm->id,
        'gameid' => $newsession->id,
        'join' => 1,
    ]);

    $message = new \core\message\message();
    $message->component = 'mod_collabmatch';
    $message->name = 'gameinvite';
    $message->userfrom = $currentuser;
    $message->userto = $invitee;
    $message->subject = 'Game invitation: ' . format_string($collabmatch->name);

    $message->fullmessage =
        fullname($currentuser) . " has invited you to play a game.\n\n" .
        "Click here to join:\n" .
        $joinurl->out(false);

    $message->fullmessageformat = FORMAT_PLAIN;

    $message->fullmessagehtml =
        '<p><strong>' . s(fullname($currentuser)) . '</strong> has invited you to play a game.</p>' .
        '<p><a href="' . s($joinurl->out(false)) . '" style="font-size:18px;font-weight:bold;">Join game</a></p>';

    $message->smallmessage = 'Game invite from ' . fullname($currentuser);
    $message->notification = 1;
    $message->contexturl = $joinurl->out(false);
    $message->contexturlname = 'Join Collaborative Match';

    return message_send($message);
}

/*
 * -------------------------------------------------------------
 * Teacher-defined content
 * -------------------------------------------------------------
 */
$prompttext = !empty($collabmatch->prompttext)
    ? trim($collabmatch->prompttext)
    : 'Choose the most appropriate pair.';

/*
 * Parse target zones
 */
$zones = [];
if (!empty($collabmatch->targetzones)) {
    $zonelines = preg_split('/\r\n|\r|\n/', $collabmatch->targetzones);
    foreach ($zonelines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $zones[] = $line;
        }
    }
}

/*
 * Parse match pairs into:
 *   item => correct zone
 */
$pairs = [];
if (!empty($collabmatch->matchpairs)) {
    $pairlines = preg_split('/\r\n|\r|\n/', $collabmatch->matchpairs);
    foreach ($pairlines as $line) {
        $line = trim($line);
        if ($line === '' || substr_count($line, '|') !== 1) {
            continue;
        }

        list($item, $zone) = array_map('trim', explode('|', $line, 2));

        if ($item !== '' && $zone !== '') {
            $pairs[$item] = $zone;
        }
    }
}

/*
 * -------------------------------------------------------------
 * Styles
 * -------------------------------------------------------------
 */
$styles = '
.collabmatch-shell {
    max-width: 760px;
}

.collabmatch-page-title {
    margin: 0 0 0.4rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.collabmatch-introbox {
    margin-bottom: 1rem;
}

.collabmatch-turnbanner {
    border-radius: 14px;
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
    border: 1px solid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.collabmatch-turnbanner h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.8rem;
    line-height: 1.1;
}
.collabmatch-turnbanner p {
    margin: 0;
    font-size: 1rem;
}
.collabmatch-turnbanner-success {
    background: #ecfdf3;
    border-color: #86efac;
}
.collabmatch-turnbanner-muted {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.collabmatch-turnbanner-info {
    background: #eff6ff;
    border-color: #93c5fd;
}
.collabmatch-turnbanner-finished {
    background: #ecfdf3;
    border-color: #86efac;
}

.collabmatch-hero {
    border: 1px solid #d9e2f2;
    background: linear-gradient(180deg, #f7faff 0%, #eef4ff 100%);
    border-radius: 14px;
    padding: 1.2rem;
    margin-bottom: 1rem;
}
.collabmatch-hero h2 {
    margin: 0 0 0.35rem 0;
    font-size: 2rem;
}
.collabmatch-subtitle {
    font-size: 1.05rem;
    color: #344054;
    margin-bottom: 0;
}

.collabmatch-banner {
    border-radius: 14px;
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
    border: 1px solid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.collabmatch-banner h3 {
    margin: 0 0 0.4rem 0;
    font-size: 1.3rem;
}
.collabmatch-banner p {
    margin: 0 0 0.7rem 0;
    font-size: 1rem;
}
.collabmatch-banner-urgent {
    background: #fff7ed;
    border-color: #fdba74;
}
.collabmatch-banner-success {
    background: #ecfdf3;
    border-color: #86efac;
}
.collabmatch-banner-info {
    background: #eff6ff;
    border-color: #93c5fd;
}
.collabmatch-banner-muted {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.collabmatch-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
.collabmatch-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #ffffff;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    margin-bottom: 1rem;
}
.collabmatch-card h3 {
    margin-top: 0;
    margin-bottom: 0.75rem;
    font-size: 1.35rem;
}
.collabmatch-card ul {
    margin-bottom: 0.2rem;
}

.collabmatch-scoreline {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.4rem;
    padding: 0.4rem 0;
    border-bottom: 1px solid #f0f2f5;
}
.collabmatch-scoreline:last-child {
    border-bottom: none;
}

.collabmatch-pillrow {
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
    margin: 0.4rem 0 1rem 0;
}
.collabmatch-pill {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: 0.35rem 0.8rem;
    font-size: 0.95rem;
}
.collabmatch-status-active {
    background: #ecfdf3;
    border-color: #b7ebc6;
}
.collabmatch-status-finished {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.collabmatch-status-invited {
    background: #fff7ed;
    border-color: #fdba74;
}

.collabmatch-movebox {
    border-left: 4px solid #7c3aed;
}

.collabmatch-form-card {
    border: 2px solid #a7f3d0;
    border-radius: 16px;
    background: linear-gradient(180deg, #f8fffb 0%, #eefcf4 100%);
    padding: 1.1rem 1.1rem 1rem 1.1rem;
    margin-top: 0;
    margin-bottom: 1rem;
    box-shadow: 0 4px 14px rgba(16, 24, 40, 0.06);
}

.collabmatch-form-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.8rem;
}

.collabmatch-form-card h3 {
    margin: 0;
    font-size: 1.5rem;
    line-height: 1.15;
}

.collabmatch-action-badge {
    display: inline-block;
    padding: 0.24rem 0.65rem;
    border-radius: 999px;
    background: #166534;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.collabmatch-form-intro {
    margin: 0 0 1rem 0;
    color: #344054;
    font-size: 1rem;
}

.collabmatch-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
    min-width: 0;
}

.collabmatch-form-grid > * {
    min-width: 0;
}

.collabmatch-form-field {
    min-width: 0;
}

.collabmatch-form-field label {
    font-weight: 700;
    font-size: 1.08rem;
    display: block;
    margin-bottom: 0.45rem;
    color: #111827;
}

.collabmatch-form-grid select {
    width: 100%;
    min-height: 3rem;
    font-size: 1.06rem;
    font-weight: 600;
    border-radius: 10px;
}

.collabmatch-selection-hint {
    margin-top: 0.35rem;
    font-size: 0.92rem;
    color: #667085;
}

.collabmatch-picker-button {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    min-height: 3.1rem;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    column-gap: 0.75rem;
    padding: 0.75rem 0.9rem;
    border: 1px solid #98a2b3;
    border-radius: 10px;
    background: #ffffff;
    color: #101828;
    font-size: 1.06rem;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
    box-sizing: border-box;
    overflow: hidden;
}

.collabmatch-picker-button:hover,
.collabmatch-picker-button:focus {
    border-color: #475467;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.10);
    outline: none;
}

.collabmatch-picker-button-text {
    min-width: 0;
    max-width: 100%;
    width: 100%;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.collabmatch-picker-button-icon {
    color: #344054;
    font-size: 0.9rem;
}

.collabmatch-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 1050;
    padding: 1rem;
}

.collabmatch-modal-panel {
    width: min(760px, 100%);
    max-height: calc(100vh - 2rem);
    overflow: auto;
    margin: 2rem auto;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22);
    border: 1px solid #d0d5dd;
    padding: 1rem;
}

.collabmatch-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.collabmatch-modal-header h4 {
    margin: 0;
    font-size: 1.25rem;
}

.collabmatch-modal-close {
    border: none;
    background: transparent;
    font-size: 1.75rem;
    line-height: 1;
    padding: 0.1rem 0.35rem;
    cursor: pointer;
    color: #344054;
}

.collabmatch-modal-intro {
    margin: 0 0 0.85rem 0;
    color: #475467;
}

.collabmatch-modal-option-list {
    display: grid;
    gap: 0.65rem;
}

.collabmatch-modal-option {
    width: 100%;
    text-align: left;
    border: 1px solid #d0d5dd;
    border-radius: 12px;
    background: #ffffff;
    padding: 0.8rem 0.9rem;
    cursor: pointer;
}

.collabmatch-modal-option:hover,
.collabmatch-modal-option:focus {
    border-color: #3b82f6;
    background: #eff6ff;
    outline: none;
}

.collabmatch-modal-option-text {
    display: block;
    white-space: normal;
    line-height: 1.4;
    color: #101828;
    font-size: 1rem;
}

.collabmatch-submit {
    margin-top: 1rem;
}

.collabmatch-footer-note {
    font-size: 0.95rem;
    color: #475467;
    margin-top: 0.9rem;
}
.collabmatch-final {
    margin-top: 0.8rem;
}
.collabmatch-winner {
    background: linear-gradient(90deg, #ecfdf3, #d1fae5);
    border: 1px solid #86efac;
    border-radius: 10px;
    padding: 0.8rem;
    font-size: 1.1rem;
}

.collabmatch-lobby-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
}
.collabmatch-lobby-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f2f5;
}
.collabmatch-lobby-item:last-child {
    border-bottom: none;
}
.collabmatch-user-meta {
    color: #667085;
    font-size: 0.95rem;
}
.collabmatch-pending-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
}
.collabmatch-pending-list li {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f2f5;
}
.collabmatch-pending-list li:last-child {
    border-bottom: none;
}
.collabmatch-banner-actions {
    margin-top: 0.8rem;
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .collabmatch-grid,
    .collabmatch-lobby-item {
        grid-template-columns: 1fr;
    }
}';

/*
 * -------------------------------------------------------------
 * Handle explicit join link to a specific invited session
 * -------------------------------------------------------------
 */
if ($gameid > 0 && $join) {
    $requestedgame = $DB->get_record(
        'collabmatch_game',
        ['id' => $gameid, 'collabmatchid' => $collabmatch->id],
        '*',
        IGNORE_MISSING
    );

    if ($requestedgame && (int)$requestedgame->playerb === (int)$currentuserid) {
        if ($requestedgame->status === 'invited') {
            $requestedgame->status = 'active';
            $requestedgame->timemodified = time();
            $DB->update_record('collabmatch_game', $requestedgame);

            // Update grades for the invited learner now that the attempt is active.
            collabmatch_update_grades($collabmatch, $requestedgame->playerb);
        }

        redirect(new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]));
    }
}

/*
 * -------------------------------------------------------------
 * Handle invitation creation
 * -------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('inviteplayer', '', PARAM_TEXT) !== '') {
    require_sesskey();

    $activegame = $DB->get_records_select(
        'collabmatch_game',
        'collabmatchid = ? AND (playera = ? OR playerb = ?) AND status IN (?, ?, ?)',
        [$collabmatch->id, $currentuserid, $currentuserid, 'active', 'invited', 'waiting'],
        'id DESC',
        '*',
        0,
        1
    );

    if (!$activegame) {
        $inviteeuserid = required_param('inviteeuserid', PARAM_INT);

        if ($inviteeuserid > 0 && $inviteeuserid !== $currentuserid) {
            $invitee = core_user::get_user($inviteeuserid, '*', MUST_EXIST);

            $newsession = new stdClass();
            $newsession->collabmatchid = $collabmatch->id;
            $newsession->playera = $currentuserid;
            $newsession->playerb = $inviteeuserid;
            $newsession->currentturn = $currentuserid;
            $newsession->status = 'invited';
            $newsession->timecreated = time();
            $newsession->timemodified = time();
            $newsession->lastmove = '';
            $newsession->lastplayer = 0;
            $newsession->lastmovetime = 0;

            $newsession->id = $DB->insert_record('collabmatch_game', $newsession);

            collabmatch_send_invitation($currentuser, $invitee, $cm, $newsession, $collabmatch);

            redirect(
                new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]),
                'Invitation sent to ' . fullname($invitee) . '.',
                1
            );
        }
    } else {
        redirect(
            new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]),
            'Finish or leave your current game before inviting someone else.',
            1
        );
    }
}

/*
 * -------------------------------------------------------------
 * Pending invitations for this user
 * -------------------------------------------------------------
 */
$pendinginvites = $DB->get_records(
    'collabmatch_game',
    ['collabmatchid' => $collabmatch->id, 'playerb' => $currentuserid, 'status' => 'invited'],
    'id DESC'
);

/*
 * -------------------------------------------------------------
 * Current session selection
 * -------------------------------------------------------------
 */
$currentgame = false;
$latestfinishedgame = false;

$usergames = $DB->get_records_select(
    'collabmatch_game',
    'collabmatchid = ? AND (playera = ? OR playerb = ?)',
    [$collabmatch->id, $currentuserid, $currentuserid],
    'id DESC'
);

if ($usergames) {
    foreach ($usergames as $candidate) {
        if (in_array($candidate->status, ['active', 'waiting'], true)) {
            $currentgame = $candidate;
            break;
        }

        if (!$latestfinishedgame && $candidate->status === 'finished') {
            $latestfinishedgame = $candidate;
        }
    }
}

if (!$currentgame && !$newgame && $latestfinishedgame) {
    $currentgame = $latestfinishedgame;
}

/*
 * -------------------------------------------------------------
 * AJAX for lobby mode
 * -------------------------------------------------------------
 */
if ($ajax && !$currentgame) {
    $latestgameforpoll = $DB->get_records_select(
        'collabmatch_game',
        'collabmatchid = ? AND (playera = ? OR playerb = ?)',
        [$collabmatch->id, $currentuserid, $currentuserid],
        'id DESC',
        '*',
        0,
        1
    );

    $latestgameforpoll = $latestgameforpoll ? reset($latestgameforpoll) : false;

    $payload = [
        'mode' => 'lobby',
        'pendinginvitecount' => count($pendinginvites),
        'latestgameid' => $latestgameforpoll ? (int)$latestgameforpoll->id : 0,
        'latestgamestatus' => $latestgameforpoll ? (string)$latestgameforpoll->status : '',
        'latesttimemodified' => $latestgameforpoll ? (int)$latestgameforpoll->timemodified : 0,
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

/*
 * -------------------------------------------------------------
 * Lobby mode
 * -------------------------------------------------------------
 */
if (!$currentgame) {
    // Recently active in the last 180 seconds.
    $cutoff = time() - 180;

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

    // Exclude users already tied up in an active/invited/waiting Collabmatch game for this activity.
    $busygames = $DB->get_records_select(
        'collabmatch_game',
        'collabmatchid = ? AND status IN (?, ?, ?)',
        [$collabmatch->id, 'active', 'invited', 'waiting'],
        '',
        'playera, playerb'
    );

    $busyuserids = [];
    foreach ($busygames as $busygame) {
        if (!empty($busygame->playera)) {
            $busyuserids[(int)$busygame->playera] = true;
        }
        if (!empty($busygame->playerb)) {
            $busyuserids[(int)$busygame->playerb] = true;
        }
    }

    $availableusers = [];
    foreach ($onlineusers as $onlineuser) {
        if (!isset($busyuserids[(int)$onlineuser->id])) {
            $availableusers[] = $onlineuser;
        }
    }

    echo $OUTPUT->header();
    echo html_writer::tag('style', $styles);
    echo html_writer::start_div('collabmatch-shell');

    echo html_writer::tag('h1', format_string($collabmatch->name), ['class' => 'collabmatch-page-title']);

    echo html_writer::start_div('collabmatch-hero');
    echo html_writer::tag('h2', 'Invite someone to play');
    echo html_writer::tag(
        'p',
        'Invite someone who has been active in this course in the last few minutes to start a new Collaborative Match session.',
        ['class' => 'collabmatch-subtitle']
    );
    echo html_writer::end_div();

    if ($pendinginvites) {
        echo html_writer::start_div('collabmatch-banner collabmatch-banner-urgent');
        echo html_writer::tag('h3', 'You have a game waiting');
        echo html_writer::tag('p', 'Another learner has invited you. Click Join game to enter the session immediately.');

        echo html_writer::start_tag('ul', ['class' => 'collabmatch-pending-list']);

        foreach ($pendinginvites as $invitegame) {
            $inviter = core_user::get_user($invitegame->playera, '*', IGNORE_MISSING);
            $joinurl = new moodle_url('/mod/collabmatch/view.php', [
                'id' => $cm->id,
                'gameid' => $invitegame->id,
                'join' => 1,
            ]);

            echo html_writer::start_tag('li');
            echo html_writer::tag('div', $inviter ? fullname($inviter) . ' has invited you to play.' : 'Another learner has invited you to play.');
            echo html_writer::div(
                html_writer::link($joinurl, 'Join game', ['class' => 'btn btn-primary']),
                'collabmatch-banner-actions'
            );
            echo html_writer::end_tag('li');
        }

        echo html_writer::end_tag('ul');
        echo html_writer::end_div();
    }

    echo html_writer::start_div('collabmatch-card');
    echo html_writer::tag('h3', 'People recently active in this course');

    if ($availableusers) {
        echo html_writer::start_tag('ul', ['class' => 'collabmatch-lobby-list']);

        foreach ($availableusers as $onlineuser) {
            $inviteformurl = new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]);

            echo html_writer::start_tag('li', ['class' => 'collabmatch-lobby-item']);
            echo html_writer::start_div();
            echo html_writer::tag('div', fullname($onlineuser));
            echo html_writer::tag('div', 'Active in this course within the last few minutes', ['class' => 'collabmatch-user-meta']);
            echo html_writer::end_div();

            echo html_writer::start_tag('form', [
                'method' => 'post',
                'action' => $inviteformurl->out(false),
            ]);

            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
            ]);

            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'inviteeuserid',
                'value' => $onlineuser->id,
            ]);

            echo html_writer::empty_tag('input', [
                'type' => 'submit',
                'name' => 'inviteplayer',
                'value' => 'Invite',
                'class' => 'btn btn-secondary',
            ]);

            echo html_writer::end_tag('form');
            echo html_writer::end_tag('li');
        }

        echo html_writer::end_tag('ul');
    } else {
        echo html_writer::tag('p', 'No available learners have been active in this course in the last few minutes.', ['class' => 'collabmatch-user-meta']);
    }

    echo html_writer::end_div();
    echo html_writer::tag('p', 'When someone accepts an invitation by opening the activity, a new shared game session begins.', ['class' => 'collabmatch-footer-note']);
    echo html_writer::end_div();

    $lobbypollurl = new moodle_url('/mod/collabmatch/view.php', [
        'id' => $cm->id,
        'ajax' => 1,
        'newgame' => $newgame ? 1 : 0,
    ]);

    $latestgameforpoll = $DB->get_records_select(
        'collabmatch_game',
        'collabmatchid = ? AND (playera = ? OR playerb = ?)',
        [$collabmatch->id, $currentuserid, $currentuserid],
        'id DESC',
        '*',
        0,
        1
    );

    $latestgameforpoll = $latestgameforpoll ? reset($latestgameforpoll) : false;

    $initiallobbystate = [
        'mode' => 'lobby',
        'pendinginvitecount' => count($pendinginvites),
        'latestgameid' => $latestgameforpoll ? (int)$latestgameforpoll->id : 0,
        'latestgamestatus' => $latestgameforpoll ? (string)$latestgameforpoll->status : '',
        'latesttimemodified' => $latestgameforpoll ? (int)$latestgameforpoll->timemodified : 0,
    ];

    $lobbyjs = "
    (function() {
        var pollUrl = " . json_encode($lobbypollurl->out(false)) . ";
        var currentState = " . json_encode($initiallobbystate) . ";

        function statesDiffer(a, b) {
            return (
                parseInt(a.pendinginvitecount) !== parseInt(b.pendinginvitecount) ||
                parseInt(a.latestgameid) !== parseInt(b.latestgameid) ||
                String(a.latestgamestatus) !== String(b.latestgamestatus) ||
                parseInt(a.latesttimemodified) !== parseInt(b.latesttimemodified)
            );
        }

        function pollServer() {
            fetch(pollUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Lobby polling HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(function(serverState) {
                if (statesDiffer(currentState, serverState)) {
                    window.location.reload();
                }
            })
            .catch(function(error) {
                console.log('collabmatch lobby polling error', error);
            });
        }

        window.setInterval(pollServer, 3000);
    })();
    ";

    $PAGE->requires->js_init_code($lobbyjs);

    echo $OUTPUT->footer();
    exit;
}

/*
 * -------------------------------------------------------------
 * From here onward, we are inside one current game session
 * -------------------------------------------------------------
 */
$game = $currentgame;

$allmoves = $DB->get_records('collabmatch_move', ['gameid' => $game->id], 'id ASC');

$lastmove = false;
if ($allmoves) {
    $temp = array_values($allmoves);
    $lastmove = end($temp);
}

$useditems = [];
if ($allmoves) {
    foreach ($allmoves as $move) {
        if (!empty($move->choice) && strpos($move->choice, '->') !== false) {
            $parts = explode('->', $move->choice, 2);
            $useditem = trim($parts[0]);
            if ($useditem !== '') {
                $useditems[$useditem] = true;
            }
        }
    }
}

$remainingpairs = [];
foreach ($pairs as $item => $correctzone) {
    if (!isset($useditems[$item])) {
        $remainingpairs[$item] = $correctzone;
    }
}

if ($game->status === 'active' && count($remainingpairs) === 0) {
    $game->status = 'finished';
    $game->timemodified = time();
    $DB->update_record('collabmatch_game', $game);
    $game = $DB->get_record('collabmatch_game', ['id' => $game->id], '*', MUST_EXIST);

    // Push final grades for both players now that the game is finished.
    if (!empty($game->playera)) {
        collabmatch_update_grades($collabmatch, $game->playera);
    }
    if (!empty($game->playerb)) {
        collabmatch_update_grades($collabmatch, $game->playerb);
    }
}

if ($ajax) {
    $payload = [
        'gameid' => (int)$game->id,
        'status' => (string)$game->status,
        'playera' => (int)$game->playera,
        'playerb' => (int)$game->playerb,
        'currentturn' => (int)$game->currentturn,
        'timemodified' => (int)$game->timemodified,
        'lastmove' => (string)$game->lastmove,
        'lastplayer' => (int)$game->lastplayer,
        'lastmovetime' => (int)$game->lastmovetime,
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('submitmove', '', PARAM_TEXT) !== '') {
    require_sesskey();

    $item = required_param('item', PARAM_TEXT);
    $zone = required_param('zone', PARAM_TEXT);

    $item = trim($item);
    $zone = trim($zone);

    if (
        $game->status === 'active' &&
        (int)$game->currentturn === (int)$currentuserid &&
        $item !== '' &&
        $zone !== '' &&
        isset($pairs[$item]) &&
        isset($remainingpairs[$item])
    ) {
        $correct = 0;
        if ($pairs[$item] === $zone) {
            $correct = 1;
        }

        $move = new stdClass();
        $move->gameid = $game->id;
        $move->userid = $currentuserid;
        $move->choice = $item . ' -> ' . $zone;
        $move->correct = $correct;
        $move->timecreated = time();

        $DB->insert_record('collabmatch_move', $move);

        $game->lastmove = $move->choice;
        $game->lastplayer = $currentuserid;
        $game->lastmovetime = $move->timecreated;

        if ((int)$currentuserid === (int)$game->playera && !empty($game->playerb)) {
            $game->currentturn = $game->playerb;
        } else if ((int)$currentuserid === (int)$game->playerb) {
            $game->currentturn = $game->playera;
        }

        $game->timemodified = time();
        $DB->update_record('collabmatch_game', $game);
    }

    redirect(new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]));
}

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$sql = "
    SELECT userid, COUNT(id) AS score
    FROM {collabmatch_move}
    WHERE gameid = ? AND correct = 1
    GROUP BY userid
";

$scores = $DB->get_records_sql($sql, [$game->id]);

$playerascore = isset($scores[$game->playera]) ? $scores[$game->playera]->score : 0;
$playerbscore = isset($scores[$game->playerb]) ? $scores[$game->playerb]->score : 0;

$playeraname = 'Unknown user';
$playerauser = core_user::get_user($game->playera);
if ($playerauser) {
    $playeraname = fullname($playerauser);
}

$playerbname = 'Not yet assigned';
if (!empty($game->playerb)) {
    $playerbuser = core_user::get_user($game->playerb);
    if ($playerbuser) {
        $playerbname = fullname($playerbuser);
    }
}

$currentturnname = 'Not yet applicable';
if (!empty($game->currentturn)) {
    $turnuser = core_user::get_user($game->currentturn);
    if ($turnuser) {
        $currentturnname = fullname($turnuser);
    }
}

$lastmovetext = 'No move submitted yet';
$lastplayertext = 'Nobody yet';
$lastcorrecttext = '';

if ($lastmove) {
    $lastmovetext = $lastmove->choice;
    $lastplayeruser = core_user::get_user($lastmove->userid);
    if ($lastplayeruser) {
        $lastplayertext = fullname($lastplayeruser);
    }
    $lastcorrecttext = $lastmove->correct ? 'Correct' : 'Incorrect';
}

$finalresultmessage = '';
if ($game->status === 'finished') {
    if ($playerascore > $playerbscore) {
        $finalresultmessage = 'Winner: ' . $playeraname;
    } else if ($playerbscore > $playerascore) {
        $finalresultmessage = 'Winner: ' . $playerbname;
    } else {
        $finalresultmessage = 'Draw';
    }
}

echo $OUTPUT->header();
echo html_writer::tag('style', $styles);
echo html_writer::start_div('collabmatch-shell');

echo html_writer::tag('h1', 'Make the best match', ['class' => 'collabmatch-page-title']);

/*
 * -------------------------------------------------------------
 * Loud communication layer
 * -------------------------------------------------------------
 */
if ($pendinginvites) {
    echo html_writer::start_div('collabmatch-banner collabmatch-banner-urgent');
    echo html_writer::tag('h3', 'You have another game waiting');
    echo html_writer::tag('p', 'An invitation is waiting for you right now. Join it from here.');

    echo html_writer::start_tag('ul', ['class' => 'collabmatch-pending-list']);
    foreach ($pendinginvites as $invitegame) {
        $inviter = core_user::get_user($invitegame->playera, '*', IGNORE_MISSING);
        $joinurl = new moodle_url('/mod/collabmatch/view.php', [
            'id' => $cm->id,
            'gameid' => $invitegame->id,
            'join' => 1,
        ]);

        echo html_writer::start_tag('li');
        echo html_writer::tag('div', $inviter ? fullname($inviter) . ' has invited you to play.' : 'Another learner has invited you to play.');
        echo html_writer::div(
            html_writer::link($joinurl, 'Join game', ['class' => 'btn btn-primary']),
            'collabmatch-banner-actions'
        );
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

if ($game->status === 'active') {
    if ((int)$game->currentturn === (int)$currentuserid) {
        if (count($remainingpairs) > 0) {
            echo html_writer::start_div('collabmatch-turnbanner collabmatch-turnbanner-success');
            echo html_writer::tag('h2', 'It is your turn');
            echo html_writer::tag('p', 'Choose the best answer for one of the remaining questions.');
            echo html_writer::end_div();
        } else {
            echo html_writer::start_div('collabmatch-turnbanner collabmatch-turnbanner-info');
            echo html_writer::tag('h2', 'No questions remain');
            echo html_writer::tag('p', 'The activity will finish when the page refreshes.');
            echo html_writer::end_div();
        }
    } else {
        echo html_writer::start_div('collabmatch-turnbanner collabmatch-turnbanner-muted');
        echo html_writer::tag('h2', 'Waiting for the other learner');
        echo html_writer::tag('p', 'It is currently ' . s($currentturnname) . '\'s turn.');
        echo html_writer::end_div();
    }
}

if ($game->status === 'finished') {
    echo html_writer::start_div('collabmatch-turnbanner collabmatch-turnbanner-finished');
    echo html_writer::tag('h2', 'This game is finished');
    if ($finalresultmessage !== '') {
        echo html_writer::tag('p', $finalresultmessage);
    }
    echo html_writer::end_div();
}

/*
 * -------------------------------------------------------------
 * Primary action area
 * -------------------------------------------------------------
 */
if (
    $game->status === 'active' &&
    !empty($game->playera) &&
    !empty($game->playerb) &&
    (int)$game->currentturn === (int)$currentuserid &&
    count($remainingpairs) > 0
) {
    echo html_writer::start_div('collabmatch-form-card');
    echo html_writer::start_div('collabmatch-form-card-header');
    echo html_writer::tag('h3', 'Your turn');
    echo html_writer::tag('span', 'Action required', ['class' => 'collabmatch-action-badge']);
    echo html_writer::end_div();

    echo html_writer::tag('p', 'Choose one remaining question, then choose its best answer. Tap each button to open a compact chooser.', ['class' => 'collabmatch-form-intro']);

    $formaction = new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id]);

    $questionoptions = array_keys($remainingpairs);
    $defaultitem = !empty($questionoptions) ? reset($questionoptions) : '';
    $defaultzone = !empty($zones) ? reset($zones) : '';

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $formaction->out(false),
        'id' => 'collabmatch-move-form',
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'item',
        'id' => 'item',
        'value' => $defaultitem,
    ]);

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'zone',
        'id' => 'zone',
        'value' => $defaultzone,
    ]);

    echo html_writer::start_div('collabmatch-form-grid');

    echo html_writer::start_div('collabmatch-form-field');
    echo html_writer::tag('label', 'Choose question', ['for' => 'collabmatch-open-question-modal']);
    echo html_writer::start_tag('button', [
        'type' => 'button',
        'id' => 'collabmatch-open-question-modal',
        'class' => 'collabmatch-picker-button',
        'data-modal-target' => 'collabmatch-question-modal',
        'aria-haspopup' => 'dialog',
        'aria-controls' => 'collabmatch-question-modal',
    ]);
    echo html_writer::tag('span', s($defaultitem), ['id' => 'collabmatch-item-button-text', 'class' => 'collabmatch-picker-button-text']);
    echo html_writer::tag('span', '&#9662;', ['class' => 'collabmatch-picker-button-icon']);
    echo html_writer::end_tag('button');
    echo html_writer::tag('div', 'Open the chooser to read and select the full question text.', [
        'class' => 'collabmatch-selection-hint',
    ]);
    echo html_writer::end_div();

    echo html_writer::start_div('collabmatch-form-field');
    echo html_writer::tag('label', 'Choose answer', ['for' => 'collabmatch-open-answer-modal']);
    echo html_writer::start_tag('button', [
        'type' => 'button',
        'id' => 'collabmatch-open-answer-modal',
        'class' => 'collabmatch-picker-button',
        'data-modal-target' => 'collabmatch-answer-modal',
        'aria-haspopup' => 'dialog',
        'aria-controls' => 'collabmatch-answer-modal',
    ]);
    echo html_writer::tag('span', s($defaultzone), ['id' => 'collabmatch-zone-button-text', 'class' => 'collabmatch-picker-button-text']);
    echo html_writer::tag('span', '&#9662;', ['class' => 'collabmatch-picker-button-icon']);
    echo html_writer::end_tag('button');
    echo html_writer::tag('div', 'Open the chooser to read and select the full answer text.', [
        'class' => 'collabmatch-selection-hint',
    ]);
    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::start_div('collabmatch-modal-backdrop', [
        'id' => 'collabmatch-question-modal',
        'hidden' => 'hidden',
        'aria-hidden' => 'true',
    ]);
    echo html_writer::start_div('collabmatch-modal-panel', [
        'role' => 'dialog',
        'aria-modal' => 'true',
        'aria-labelledby' => 'collabmatch-question-modal-title',
    ]);
    echo html_writer::start_div('collabmatch-modal-header');
    echo html_writer::tag('h4', 'Choose question', ['id' => 'collabmatch-question-modal-title']);
    echo html_writer::tag('button', '&times;', [
        'type' => 'button',
        'class' => 'collabmatch-modal-close',
        'data-close-modal' => 'collabmatch-question-modal',
        'aria-label' => 'Close question chooser',
    ]);
    echo html_writer::end_div();
    echo html_writer::tag('p', 'Select the question you want to play this turn.', ['class' => 'collabmatch-modal-intro']);
    echo html_writer::start_tag('div', ['class' => 'collabmatch-modal-option-list']);
    foreach ($questionoptions as $item) {
        echo html_writer::start_tag('button', [
            'type' => 'button',
            'class' => 'collabmatch-modal-option',
            'data-picker-target' => 'item',
            'data-picker-label-target' => 'collabmatch-item-button-text',
            'data-picker-value' => $item,
            'data-close-modal' => 'collabmatch-question-modal',
        ]);
        echo html_writer::tag('span', s($item), ['class' => 'collabmatch-modal-option-text']);
        echo html_writer::end_tag('button');
    }
    echo html_writer::end_tag('div');
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('collabmatch-modal-backdrop', [
        'id' => 'collabmatch-answer-modal',
        'hidden' => 'hidden',
        'aria-hidden' => 'true',
    ]);
    echo html_writer::start_div('collabmatch-modal-panel', [
        'role' => 'dialog',
        'aria-modal' => 'true',
        'aria-labelledby' => 'collabmatch-answer-modal-title',
    ]);
    echo html_writer::start_div('collabmatch-modal-header');
    echo html_writer::tag('h4', 'Choose answer', ['id' => 'collabmatch-answer-modal-title']);
    echo html_writer::tag('button', '&times;', [
        'type' => 'button',
        'class' => 'collabmatch-modal-close',
        'data-close-modal' => 'collabmatch-answer-modal',
        'aria-label' => 'Close answer chooser',
    ]);
    echo html_writer::end_div();
    echo html_writer::tag('p', 'Select the answer that best matches your chosen question.', ['class' => 'collabmatch-modal-intro']);
    echo html_writer::start_tag('div', ['class' => 'collabmatch-modal-option-list']);
    foreach ($zones as $zone) {
        echo html_writer::start_tag('button', [
            'type' => 'button',
            'class' => 'collabmatch-modal-option',
            'data-picker-target' => 'zone',
            'data-picker-label-target' => 'collabmatch-zone-button-text',
            'data-picker-value' => $zone,
            'data-close-modal' => 'collabmatch-answer-modal',
        ]);
        echo html_writer::tag('span', s($zone), ['class' => 'collabmatch-modal-option-text']);
        echo html_writer::end_tag('button');
    }
    echo html_writer::end_tag('div');
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'name' => 'submitmove',
        'value' => 'Submit move',
        'class' => 'btn btn-primary collabmatch-submit',
    ]);

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

/*
 * -------------------------------------------------------------
 * Simple title and subtitle
 * -------------------------------------------------------------
 */
echo html_writer::start_div('collabmatch-hero');
echo html_writer::tag('h2', 'Make the best match');
echo html_writer::tag('p', 'A turn-based collaborative matching activity. Each move is shared, scored, and updated live for both learners.', ['class' => 'collabmatch-subtitle']);
echo html_writer::end_div();

/*
 * -------------------------------------------------------------
 * Score and recent move
 * -------------------------------------------------------------
 */
echo html_writer::start_div('collabmatch-grid');

echo html_writer::start_div('collabmatch-card');
echo html_writer::tag('h3', 'Score');
echo html_writer::start_div('collabmatch-scoreline');
echo html_writer::tag('div', s($playeraname));
echo html_writer::tag('div', s($playerascore));
echo html_writer::end_div();
echo html_writer::start_div('collabmatch-scoreline');
echo html_writer::tag('div', s($playerbname));
echo html_writer::tag('div', s($playerbscore));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('collabmatch-card collabmatch-movebox');
echo html_writer::tag('h3', 'Last shared move');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', 'Choice: ' . s($lastmovetext));
echo html_writer::tag('li', 'Chosen by: ' . s($lastplayertext));
if ($lastcorrecttext !== '') {
    echo html_writer::tag('li', 'Result: ' . s($lastcorrecttext));
}
echo html_writer::end_tag('ul');
echo html_writer::end_div();

echo html_writer::end_div();

/*
 * -------------------------------------------------------------
 * End-of-game actions
 * -------------------------------------------------------------
 */
if ($game->status === 'finished') {
    if ($finalresultmessage !== '') {
        echo html_writer::div(html_writer::tag('strong', $finalresultmessage), 'collabmatch-final collabmatch-winner');
    }

    $newgameurl = new moodle_url('/mod/collabmatch/view.php', ['id' => $cm->id, 'newgame' => 1]);
    echo html_writer::div(
        html_writer::link($newgameurl, 'Start new game', ['class' => 'btn btn-secondary']),
        'collabmatch-final'
    );
}

/*
 * -------------------------------------------------------------
 * Less important system information
 * -------------------------------------------------------------
 */
$statusclass = 'collabmatch-status-active';
if ($game->status === 'finished') {
    $statusclass = 'collabmatch-status-finished';
} else if ($game->status === 'invited') {
    $statusclass = 'collabmatch-status-invited';
}

echo html_writer::start_div('collabmatch-pillrow');
echo html_writer::tag('span', 'Game ID: ' . s($game->id), ['class' => 'collabmatch-pill']);
echo html_writer::tag('span', 'Remaining items: ' . count($remainingpairs), ['class' => 'collabmatch-pill']);
echo html_writer::tag('span', 'Status: ' . s($game->status), ['class' => 'collabmatch-pill ' . $statusclass]);
echo html_writer::end_div();

echo html_writer::start_div('collabmatch-card');
echo html_writer::tag('h3', 'Shared game state');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', 'Player A: ' . s($playeraname));
echo html_writer::tag('li', 'Player B: ' . s($playerbname));
echo html_writer::tag('li', 'Current turn: ' . html_writer::tag('strong', s($currentturnname)), ['style' => 'color:#2563eb;']);
echo html_writer::end_tag('ul');
echo html_writer::end_div();

echo html_writer::tag('p', 'This page checks for updates automatically every few seconds.', ['class' => 'collabmatch-footer-note']);

echo html_writer::end_div();

$pollurl = new moodle_url('/mod/collabmatch/view.php', [
    'id' => $cm->id,
    'ajax' => 1,
]);

$initialstate = [
    'gameid' => (int)$game->id,
    'status' => (string)$game->status,
    'playera' => (int)$game->playera,
    'playerb' => (int)$game->playerb,
    'currentturn' => (int)$game->currentturn,
    'timemodified' => (int)$game->timemodified,
    'lastmove' => (string)$game->lastmove,
    'lastplayer' => (int)$game->lastplayer,
    'lastmovetime' => (int)$game->lastmovetime,
];

$js = "
(function() {
    var pollUrl = " . json_encode($pollurl->out(false)) . ";
    var currentState = " . json_encode($initialstate) . ";

    function statesDiffer(a, b) {
        return (
            parseInt(a.gameid) !== parseInt(b.gameid) ||
            String(a.status) !== String(b.status) ||
            parseInt(a.playera) !== parseInt(b.playera) ||
            parseInt(a.playerb) !== parseInt(b.playerb) ||
            parseInt(a.currentturn) !== parseInt(b.currentturn) ||
            parseInt(a.timemodified) !== parseInt(b.timemodified) ||
            String(a.lastmove) !== String(b.lastmove) ||
            parseInt(a.lastplayer) !== parseInt(b.lastplayer) ||
            parseInt(a.lastmovetime) !== parseInt(b.lastmovetime)
        );
    }

    function pollServer() {
        fetch(pollUrl, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Polling HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(function(serverState) {
            if (statesDiffer(currentState, serverState)) {
                window.location.reload();
            }
        })
        .catch(function(error) {
            console.log('collabmatch polling error', error);
        });
    }

    window.setInterval(pollServer, 3000);
})();
";

$modaljs = "
(function() {
    var activeModal = null;
    var lastTrigger = null;

    function openModal(modalId, trigger) {
        var modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        activeModal = modal;
        lastTrigger = trigger || null;
        document.body.style.overflow = 'hidden';

        var firstOption = modal.querySelector('.collabmatch-modal-option');
        if (firstOption) {
            firstOption.focus();
        }
    }

    function closeModal(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        if (activeModal === modal) {
            activeModal = null;
            document.body.style.overflow = '';
            if (lastTrigger) {
                lastTrigger.focus();
            }
        }
    }

    document.querySelectorAll('[data-modal-target]').forEach(function(button) {
        button.addEventListener('click', function() {
            openModal(button.getAttribute('data-modal-target'), button);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(button) {
        button.addEventListener('click', function() {
            var modalId = button.getAttribute('data-close-modal');
            closeModal(modalId);
        });
    });

    document.querySelectorAll('.collabmatch-modal-backdrop').forEach(function(modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    document.querySelectorAll('.collabmatch-modal-option').forEach(function(option) {
        option.addEventListener('click', function() {
            var inputId = option.getAttribute('data-picker-target');
            var labelId = option.getAttribute('data-picker-label-target');
            var value = option.getAttribute('data-picker-value') || '';
            var input = document.getElementById(inputId);
            var label = document.getElementById(labelId);

            if (input) {
                input.value = value;
            }
            if (label) {
                label.textContent = value;
            }

            var modalId = option.getAttribute('data-close-modal');
            if (modalId) {
                closeModal(modalId);
            }
        });
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && activeModal) {
            closeModal(activeModal.id);
        }
    });

})();
";

$PAGE->requires->js_init_code($js);
$PAGE->requires->js_init_code($modaljs);

echo $OUTPUT->footer();
