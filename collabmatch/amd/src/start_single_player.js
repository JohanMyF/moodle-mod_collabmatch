define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    return {

        init: function(cmid) {
            var buttons = document.querySelectorAll('[data-start-single-player]');

            if (!buttons.length) {
                return;
            }

            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    button.disabled = true;

                    Ajax.call([{
                        methodname: 'mod_collabmatch_start_single_player',
                        args: {
                            cmid: cmid
                        }
                    }])[0]
                    .done(function(response) {
                        if (response && response.success) {
                            window.location.reload();
                            return;
                        }

                        button.disabled = false;
                        Notification.alert(
                            'CollabMatch',
                            'The single-player game could not be started.',
                            'OK'
                        );
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