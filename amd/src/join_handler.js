define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    return {

        init: function(cmid) {
            var buttons = document.querySelectorAll('[data-join-gameid]');

            if (!buttons.length) {
                return;
            }

            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    var gameid = parseInt(button.getAttribute('data-join-gameid'), 10);

                    if (!gameid) {
                        Notification.alert('CollabMatch', 'Invalid game selected.', 'OK');
                        return;
                    }

                    button.disabled = true;

                    Ajax.call([{
                        methodname: 'mod_collabmatch_join_game',
                        args: {
                            cmid: cmid,
                            gameid: gameid
                        }
                    }])[0]
                    .done(function(response) {
                        if (response && response.success) {
                            window.location.reload();
                            return;
                        }

                        button.disabled = false;
                        Notification.alert('CollabMatch', 'The game could not be joined.', 'OK');
                    })
                    .fail(function(error) {
                        button.disabled = false;
                        Notification.exception(error);
                    });
                });
            });
        }
    };
});