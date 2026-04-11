define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    return {

        init: function(cmid, formid) {
            var form = document.getElementById(formid);

            if (!form) {
                return;
            }

            var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                var itemField = form.querySelector('[name="item"]');
                var zoneField = form.querySelector('[name="zone"]');

                var item = itemField ? String(itemField.value || '').trim() : '';
                var zone = zoneField ? String(zoneField.value || '').trim() : '';

                if (!item || !zone) {
                    Notification.alert(
                        'CollabMatch',
                        'Please choose both an item and a zone.',
                        'OK'
                    );
                    return;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                }

                Ajax.call([{
                    methodname: 'mod_collabmatch_submit_move',
                    args: {
                        cmid: cmid,
                        item: item,
                        zone: zone
                    }
                }])[0]
                .then(function(response) {
                    if (response && response.success) {
                        window.location.reload();
                        return;
                    }

                    if (submitButton) {
                        submitButton.disabled = false;
                    }

                    Notification.alert(
                        'CollabMatch',
                        'The move could not be submitted.',
                        'OK'
                    );
                })
                .catch(function(error) {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    Notification.exception(error);
                });
            });
        }
    };
});