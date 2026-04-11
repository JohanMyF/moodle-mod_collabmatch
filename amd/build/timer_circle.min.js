define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        init: function(timerId, cmid) {
            var timer = document.getElementById(timerId);
            var expired = false;
            var seconds = 0;
            var intervalId = null;

            if (!timer) {
                return;
            }

            seconds = parseInt(timer.getAttribute('data-seconds'), 10);
            if (!seconds || seconds < 1) {
                return;
            }

            function render(value) {
                timer.textContent = value;
            }

            function stopTimer() {
                if (intervalId) {
                    window.clearInterval(intervalId);
                    intervalId = null;
                }
            }

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
                        return;
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
                    seconds--;
                    render(seconds);
                }

                if (seconds <= 0) {
                    expireTurn();
                }
            }, 1000);
        }
    };
});