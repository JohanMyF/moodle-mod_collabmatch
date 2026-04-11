define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    return {

        init: function(cmid) {
            var currentState = null;
            var pollingStarted = false;

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

            function statesDiffer(a, b) {
                if (!a || !b) {
                    return false;
                }

                return (
                    a.gameid !== b.gameid ||
                    a.status !== b.status ||
                    a.currentturn !== b.currentturn ||
                    a.timemodified !== b.timemodified
                );
            }

            function poll() {
                Ajax.call([{
                    methodname: 'mod_collabmatch_get_state',
                    args: {
                        cmid: cmid
                    }
                }])[0]
                .then(function(response) {
                    var serverState = normaliseState(response);

                    // First successful poll establishes the baseline.
                    if (!pollingStarted) {
                        currentState = serverState;
                        pollingStarted = true;
                        return;
                    }

                    if (statesDiffer(currentState, serverState)) {
                        window.location.reload();
                        return;
                    }

                    currentState = serverState;
                })
                .catch(function(error) {
                    Notification.exception(error);
                });
            }

            poll();
            window.setInterval(poll, 3000);
        }
    };
});