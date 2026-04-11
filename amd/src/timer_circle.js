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
 * Handles the turn timer for CollabMatch.
 *
 * @module     mod_collabmatch/timer_circle
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    /**
     * Initialise the turn timer.
     *
     * @param {string} timerId
     * @param {number} cmid
     */
    function init(timerId, cmid) {
        var timer = document.getElementById(timerId);
        var expired = false;
        var seconds = 0;
        var intervalId = null;

        if (!timer) {
            return;
        }

        seconds = parseInt(timer.getAttribute('data-seconds'), 10);
        if (isNaN(seconds) || seconds < 1) {
            return;
        }

        /**
         * Render the remaining time.
         *
         * @param {number} value
         */
        function render(value) {
            timer.textContent = value;
        }

        /**
         * Stop the timer interval.
         */
        function stopTimer() {
            if (intervalId !== null) {
                window.clearInterval(intervalId);
                intervalId = null;
            }
        }

        /**
         * Expire the current turn once time runs out.
         */
        function expireTurn() {
            if (expired) {
                return;
            }

            expired = true;
            stopTimer();

            Ajax.call([{
                methodname: 'mod_collabmatch_expire_turn',
                args: {
                    cmid: cmid
                }
            }])[0]
                .then(function(response) {
                    if (response && response.success) {
                        window.location.reload();
                    }

                    // Quiet failure: another browser may already have advanced the turn.
                })
                .catch(function(error) {
                    var message = '';

                    if (error && error.message) {
                        message = String(error.message).toLowerCase();
                    }

                    // Quietly ignore stale timer calls.
                    if (message.indexOf('notyourturn') !== -1 || message.indexOf('nogame') !== -1) {
                        return;
                    }

                    Notification.exception(error);
                });
        }

        render(seconds);

        intervalId = window.setInterval(function() {
            if (expired) {
                stopTimer();
                return;
            }

            if (seconds > 0) {
                seconds -= 1;
                render(seconds);
            }

            if (seconds <= 0) {
                expireTurn();
            }
        }, 1000);
    }

    return {
        init: init
    };
});