define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    return {

        init: function(cmid) {

            document.addEventListener('click', function(e) {
                var button = e.target.closest('[data-invite-userid]');

                if (!button) {
                    return;
                }

                e.preventDefault();

                var inviteeuserid = parseInt(button.getAttribute('data-invite-userid'), 10);

                if (!inviteeuserid) {
                    Notification.alert(
                        'CollabMatch',
                        'Invalid user selected.',
                        'OK'
                    );
                    return;
                }

                if (button.disabled) {
                    return;
                }

                button.disabled = true;
                var originalText = button.textContent;
                button.textContent = 'Sending...';

                var request = {
                    methodname: 'mod_collabmatch_invite_player',
                    args: {
                        cmid: cmid,
                        inviteeuserid: inviteeuserid
                    }
                };

                var promise = Ajax.call([request])[0];

                promise.then(function(response) {
                    if (response && response.success) {
                        window.location.reload();
                        return;
                    }

                    button.disabled = false;
                    button.textContent = originalText;

                    Notification.alert(
                        'CollabMatch',
                        'Invitation could not be sent.',
                        'OK'
                    );
                }).catch(function(error) {
                    button.disabled = false;
                    button.textContent = originalText;
                    Notification.exception(error);
                });
            });
        }
    };
});