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
 * Renderer for the CollabMatch activity.
 *
 * @package    mod_collabmatch
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main renderer for CollabMatch.
 *
 * @package    mod_collabmatch
 */
class mod_collabmatch_renderer extends plugin_renderer_base {

    /**
     * Render the top help button and help panel.
     *
     * @param string $howitworkstext
     * @return string
     */
    public function render_help_panel(string $howitworkstext): string {
        $output = html_writer::start_div('collabmatch-topbar');
        $output .= html_writer::tag('button',
            get_string('howitworksbutton', 'mod_collabmatch'),
            [
                'type' => 'button',
                'class' => 'collabmatch-help-btn',
                'id' => 'collabmatch-help-btn',
                'aria-expanded' => 'false',
                'aria-controls' => 'collabmatch-how-panel',
            ]
        );
        $output .= html_writer::end_div();

        $output .= html_writer::tag(
            'div',
            html_writer::tag('p', s($howitworkstext)),
            ['class' => 'collabmatch-how-panel', 'id' => 'collabmatch-how-panel']
        );

        return $output;
    }

    /**
     * Render the turn banner.
     *
     * @param bool $myturn
     * @param bool $timerenabled
     * @param int $timeseconds
     * @return string
     */
    public function render_turn_banner(bool $myturn, bool $timerenabled, int $timeseconds): string {
        $bannertext = $myturn
            ? get_string('yourturnmessage', 'mod_collabmatch')
            : get_string('waitingmessage', 'mod_collabmatch');

        $heading = $myturn
            ? get_string('yourturn', 'mod_collabmatch')
            : get_string('pleasewait', 'mod_collabmatch');

        $timerhtml = '';
        if ($timerenabled) {
            $timerhtml = html_writer::tag(
                'div',
                (string)$timeseconds,
                [
                    'class' => 'collabmatch-timer',
                    'id' => 'collabmatch-timer',
                    'data-seconds' => $timeseconds,
                ]
            );
        }

        return html_writer::tag(
            'div',
            html_writer::tag(
                'div',
                html_writer::tag('h3', $heading) . html_writer::tag('p', $bannertext)
            ) . $timerhtml,
            ['class' => 'collabmatch-turn-banner']
        );
    }

    /**
     * Render the empty-state screen.
     *
     * @param array $availableusers
     * @return string
     */
    public function render_empty_state(array $availableusers): string {
        $output = html_writer::start_div('collabmatch-empty-layout');

        $solohtml = html_writer::tag('h3', get_string('playsolo', 'mod_collabmatch'));
        $solohtml .= html_writer::tag('p', get_string('playsolodesc', 'mod_collabmatch'));
        $solohtml .= html_writer::tag(
            'button',
            get_string('startsingleplayergame', 'mod_collabmatch'),
            [
                'type' => 'button',
                'class' => 'btn btn-primary collabmatch-full-btn',
                'data-start-single-player' => '1',
            ]
        );
        $output .= html_writer::tag('div', $solohtml, ['class' => 'collabmatch-card']);

        $invitehtml = html_writer::tag('h3', get_string('inviteanotherlearner', 'mod_collabmatch'));
        if ($availableusers) {
            $items = [];
            foreach ($availableusers as $onlineuser) {
                $invitebutton = html_writer::tag(
                    'button',
                    get_string('invite', 'mod_collabmatch'),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-secondary',
                        'data-invite-userid' => $onlineuser->id,
                    ]
                );
                $items[] = html_writer::tag(
                    'li',
                    html_writer::tag('span', s(fullname($onlineuser))) . $invitebutton,
                    ['class' => 'collabmatch-action-item']
                );
            }
            $invitehtml .= html_writer::tag('ul', implode('', $items), ['class' => 'collabmatch-action-list']);
        } else {
            $invitehtml .= html_writer::tag('p', get_string('noavailablelearners', 'mod_collabmatch'));
        }
        $output .= html_writer::tag('div', $invitehtml, ['class' => 'collabmatch-card']);

        $output .= html_writer::end_div();

        return $output;
    }
    /**
     * Render the finished-game summary panel.
     *
     * @param bool $issologame
     * @param string $youname
     * @param int $youscore
     * @param string $partnername
     * @param int $partnerscore
     * @param int $totalpairs
     * @return string
     */
    public function render_finished_state(
        bool $issologame,
        string $youname,
        int $youscore,
        string $partnername,
        int $partnerscore,
        int $totalpairs
    ): string {
        $content = '';

        if ($issologame) {
            $message = get_string('finishedstatesolo', 'mod_collabmatch', [
                'score' => $youscore,
                'total' => $totalpairs,
            ]);

            $content .= html_writer::tag('p', $message, ['class' => 'collabmatch-finished-message']);
        } else {
            if ($youscore > $partnerscore) {
                $resulttext = get_string('finishedstatewon', 'mod_collabmatch');
            } else if ($youscore < $partnerscore) {
                $resulttext = get_string('finishedstatelost', 'mod_collabmatch');
            } else {
                $resulttext = get_string('finishedstatedraw', 'mod_collabmatch');
            }

            $message = get_string('finishedstatemulti', 'mod_collabmatch', [
                'youname' => $youname,
                'youscore' => $youscore,
                'partnername' => $partnername,
                'partnerscore' => $partnerscore,
                'result' => $resulttext,
            ]);

            $content .= html_writer::tag('p', $message, ['class' => 'collabmatch-finished-message']);
        }

        return html_writer::tag('div', $content, ['class' => 'collabmatch-finished-panel']);
    }



    /**
     * Render the invited-state card.
     *
     * @param int $gameid
     * @return string
     */
    public function render_invited_state(int $gameid): string {
        $content = html_writer::tag('h3', get_string('pendinginvites', 'mod_collabmatch'));
        $content .= html_writer::tag(
            'button',
            get_string('joingame', 'mod_collabmatch'),
            [
                'type' => 'button',
                'class' => 'btn btn-primary',
                'data-join-gameid' => $gameid,
            ]
        );
        $content .= html_writer::tag('p', get_string('joingamedesc', 'mod_collabmatch'));

        return html_writer::tag('div', $content, ['class' => 'collabmatch-card']);
    }

    /**
     * Render the move form.
     *
     * @param moodle_url $actionurl
     * @param array $remainingpairs
     * @param array $zones
     * @param string $defaultitem
     * @param string $defaultzone
     * @return string
     */
    public function render_move_form(
        moodle_url $actionurl,
        array $remainingpairs,
        array $zones,
        string $defaultitem,
        string $defaultzone
    ): string {
        $content = html_writer::tag('h3', get_string('makethebestmatch', 'mod_collabmatch'));
        $content .= html_writer::tag('p', get_string('makethebestmatchdesc', 'mod_collabmatch'));

        $content .= html_writer::start_tag('form', [
            'id' => 'collabmatch-move-form',
            'method' => 'post',
            'action' => $actionurl->out(false),
            'class' => 'collabmatch-form-stack',
        ]);

        $content .= html_writer::start_div('collabmatch-field');
        $content .= html_writer::tag(
            'label',
            get_string('choosequestion', 'mod_collabmatch'),
            ['for' => 'collabmatch-item-select']
        );
        $content .= html_writer::select(
            array_combine(array_keys($remainingpairs), array_keys($remainingpairs)),
            'item',
            $defaultitem,
            false,
            ['id' => 'collabmatch-item-select', 'class' => 'custom-select collabmatch-select']
        );
        $content .= html_writer::tag('div', get_string('choosequestiondesc', 'mod_collabmatch'), [
            'class' => 'collabmatch-field-hint',
        ]);
        $content .= html_writer::end_div();

        $content .= html_writer::start_div('collabmatch-field');
        $content .= html_writer::tag(
            'label',
            get_string('chooseanswer', 'mod_collabmatch'),
            ['for' => 'collabmatch-zone-select']
        );
        $content .= html_writer::select(
            array_combine($zones, $zones),
            'zone',
            $defaultzone,
            false,
            ['id' => 'collabmatch-zone-select', 'class' => 'custom-select collabmatch-select']
        );
        $content .= html_writer::tag('div', get_string('chooseanswerdesc', 'mod_collabmatch'), [
            'class' => 'collabmatch-field-hint',
        ]);
        $content .= html_writer::end_div();

        $content .= html_writer::tag(
            'button',
            get_string('submitmove', 'mod_collabmatch'),
            ['type' => 'submit', 'class' => 'btn btn-primary collabmatch-submit-btn']
        );

        $content .= html_writer::end_tag('form');

        return html_writer::tag('div', $content, ['class' => 'collabmatch-card']);
    }

    /**
     * Render the game progress card.
     *
     * @param bool $issologame
     * @param string $youname
     * @param int $youscore
     * @param string $partnername
     * @param int $partnerscore
     * @param array $recentmoves
     * @param array $usersbyid
     * @return string
     */
    public function render_game_progress(
        bool $issologame,
        string $youname,
        int $youscore,
        string $partnername,
        int $partnerscore,
        array $recentmoves,
        array $usersbyid
    ): string {
        $content = html_writer::tag('h3', get_string('gameprogress', 'mod_collabmatch'));

        if ($issologame) {
            $content .= html_writer::start_div('collabmatch-score-strip collabmatch-score-strip-solo');
            $content .= html_writer::tag(
                'div',
                html_writer::tag('div', get_string('you', 'mod_collabmatch'), ['class' => 'collabmatch-score-label']) .
                html_writer::tag('div', s($youname), ['class' => 'collabmatch-score-name']) .
                html_writer::tag('div', (string)$youscore, ['class' => 'collabmatch-score-value']),
                ['class' => 'collabmatch-score-box']
            );
            $content .= html_writer::end_div();
        } else {
            $content .= html_writer::start_div('collabmatch-score-strip');
            $content .= html_writer::tag(
                'div',
                html_writer::tag('div', get_string('you', 'mod_collabmatch'), ['class' => 'collabmatch-score-label']) .
                html_writer::tag('div', s($youname), ['class' => 'collabmatch-score-name']) .
                html_writer::tag('div', (string)$youscore, ['class' => 'collabmatch-score-value']),
                ['class' => 'collabmatch-score-box']
            );
            $content .= html_writer::tag('div', get_string('versus', 'mod_collabmatch'), ['class' => 'collabmatch-score-versus']);
            $content .= html_writer::tag(
                'div',
                html_writer::tag('div', get_string('partner', 'mod_collabmatch'), ['class' => 'collabmatch-score-label']) .
                html_writer::tag('div', s($partnername), ['class' => 'collabmatch-score-name']) .
                html_writer::tag('div', (string)$partnerscore, ['class' => 'collabmatch-score-value']),
                ['class' => 'collabmatch-score-box collabmatch-score-box-partner']
            );
            $content .= html_writer::end_div();
        }

        $content .= html_writer::tag('h4', get_string('recentmoves', 'mod_collabmatch'), ['class' => 'collabmatch-subheading']);
        $content .= html_writer::start_div('collabmatch-move-list');

        if ($recentmoves) {
            foreach ($recentmoves as $move) {
                $movename = get_string('unknownuser');
                if (!empty($usersbyid[$move->userid])) {
                    $movename = fullname($usersbyid[$move->userid]);
                }

                $movetext = !empty($move->choice) ? $move->choice : get_string('nomovesyet', 'mod_collabmatch');
                $icon = !empty($move->correct) ? '✓' : '•';

                $content .= html_writer::tag(
                    'div',
                    html_writer::tag('div', $icon, ['class' => 'collabmatch-move-dot']) .
                    html_writer::tag('div', s($movename . ': ' . $movetext)),
                    ['class' => 'collabmatch-move-row']
                );
            }
        } else {
            $content .= html_writer::tag('div', get_string('nomovesyet', 'mod_collabmatch'));
        }

        $content .= html_writer::end_div();

        return html_writer::tag('div', $content, ['class' => 'collabmatch-card']);
    }
}
