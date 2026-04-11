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
 * Help panel toggle behaviour for CollabMatch.
 *
 * @module     mod_collabmatch/help_panel
 * @copyright  2026 Johan Venter <johan@myfutureway.co.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    /**
     * Initialise the help panel toggle.
     *
     * @param {string} buttonId
     * @param {string} panelId
     * @param {number|string} autocloseMs
     */
    function init(buttonId, panelId, autocloseMs) {
        var button = document.getElementById(buttonId);
        var panel = document.getElementById(panelId);
        var hideTimer = null;
        var delay = parseInt(autocloseMs, 10);

        if (!button || !panel) {
            return;
        }

        if (isNaN(delay) || delay < 0) {
            delay = 3000;
        }

        /**
         * Close the panel and clear any pending timer.
         */
        function closePanel() {
            panel.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');

            if (hideTimer !== null) {
                window.clearTimeout(hideTimer);
                hideTimer = null;
            }
        }

        /**
         * Open the panel and start the auto-close timer.
         */
        function openPanel() {
            panel.classList.add('is-open');
            button.setAttribute('aria-expanded', 'true');

            if (hideTimer !== null) {
                window.clearTimeout(hideTimer);
            }

            hideTimer = window.setTimeout(function() {
                closePanel();
            }, delay);
        }

        button.addEventListener('click', function(e) {
            e.preventDefault();

            if (panel.classList.contains('is-open')) {
                closePanel();
            } else {
                openPanel();
            }
        });
    }

    return {
        init: init
    };
});