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
 * Handles sending player invitations in CollabMatch.
 *
 * @module     mod_collabmatch/invite_sender
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    'use strict';

    /**
     * Initialise invitation handling.
     *
     * @param {number} cmid
     */
    function init(cmid) {
        var strings = {
            title: 'CollabMatch',
            invaliduser: 'Invalid user selected.',
            sending: 'Sending...',
            invitationnotsent: 'Invitation could not be sent.',
            ok: 'OK'
        };

        Str.get_strings([
            {key: 'pluginname', component: 'mod_collabmatch'},
            {key: 'jsinvaliduserselected', component: 'mod_collabmatch'},
            {key: 'jssending', component: 'mod_collabmatch'},
            {key: 'jsinvitationnotsent', component: 'mod_collabmatch'},
            {key: 'ok', component: 'moodle'}
        ]).then(function(results) {
            strings.title = results[0];
            strings.invaliduser = results[1];
            strings.sending = results[2];
            strings.invitationnotsent = results[3];
            strings.ok = results[4];
        }).catch(function() {
            // Keep safe fallback strings.
        });

        document.addEventListener('click', function(e) {
            var button = e.target.closest('[data-invite-userid]');

            if (!button) {
                return;
            }

            e.preventDefault();

            var inviteeuserid = parseInt(button.getAttribute('data-invite-userid'), 10);

            if (!inviteeuserid) {
                Notification.alert(strings.title, strings.invaliduser, strings.ok);
                return;
            }

            if (button.disabled) {
                return;
            }

            button.disabled = true;
            var originalText = button.textContent;
            button.textContent = strings.sending;

            Ajax.call([{
                methodname: 'mod_collabmatch_invite_player',
                args: {
                    cmid: cmid,
                    inviteeuserid: inviteeuserid
                }
            }])[0]
                .then(function(response) {
                    if (response && response.success) {
                        window.location.reload();
                        return;
                    }

                    button.disabled = false;
                    button.textContent = originalText;
                    Notification.alert(strings.title, strings.invitationnotsent, strings.ok);
                })
                .catch(function(error) {
                    button.disabled = false;
                    button.textContent = originalText;
                    Notification.exception(error);
                });
        });
    }

    return {
        init: init
    };
});