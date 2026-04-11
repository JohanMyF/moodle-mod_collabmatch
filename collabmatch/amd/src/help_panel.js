define([], function() {
    return {
        init: function(buttonId, panelId, autocloseMs) {
            var button = document.getElementById(buttonId);
            var panel = document.getElementById(panelId);
            var hideTimer = null;
            var delay = parseInt(autocloseMs, 10);

            if (!button || !panel) {
                return;
            }

            if (!delay || delay < 0) {
                delay = 3000;
            }

            function closePanel() {
                panel.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
                if (hideTimer) {
                    window.clearTimeout(hideTimer);
                    hideTimer = null;
                }
            }

            function openPanel() {
                panel.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');

                if (hideTimer) {
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
    };
});