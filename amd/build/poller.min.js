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
 * Polls the server for CollabMatch state changes and reloads the page when needed.
 *
 * @module     mod_collabmatch/poller
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    /**
     * Convert the server response into a normalised state object.
     *
     * @param {Object|null} state
     * @returns {Object|null}
     */
    function normaliseState(state) {
        if (!state) {
            return null;
        }

        return {
            gameid: parseInt(state.gameid || 0, 10),
            status: String(state.status || ''),
            currentturn: parseInt(state.currentturn || 0, 10),
            timemodified: parseInt(state.timemodified || 0, 10)
        };
    }

    /**
     * Determine whether two state objects differ meaningfully.
     *
     * @param {Object|null} a
     * @param {Object|null} b
     * @returns {boolean}
     */
    function statesDiffer(a, b) {
        if (a === null && b === null) {
            return false;
        }

        if (a === null || b === null) {
            return true;
        }

        return (
            a.gameid !== b.gameid ||
            a.status !== b.status ||
            a.currentturn !== b.currentturn ||
            a.timemodified !== b.timemodified
        );
    }

    /**
     * Initialise polling for server-side game state changes.
     *
     * @param {number} cmid
     */
    function init(cmid) {
        var currentState = null;
        var pollingStarted = false;
        var pollInProgress = false;

        /**
         * Poll the server once.
         */
        function poll() {
            if (pollInProgress) {
                return;
            }

            pollInProgress = true;

            Ajax.call([{
                methodname: 'mod_collabmatch_get_state',
                args: {
                    cmid: cmid
                }
            }])[0]
                .then(function(response) {
                    var serverState = normaliseState(response);

                    if (!pollingStarted) {
                        currentState = serverState;
                        pollingStarted = true;
                        pollInProgress = false;
                        return;
                    }

                    if (statesDiffer(currentState, serverState)) {
                        window.location.reload();
                        return;
                    }

                    currentState = serverState;
                    pollInProgress = false;
                })
                .catch(function(error) {
                    pollInProgress = false;
                    Notification.exception(error);
                });
        }

        poll();
        window.setInterval(poll, 3000);
    }

    return {
        init: init
    };
});