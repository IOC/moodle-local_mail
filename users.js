YUI(M.yui.loader).use('node', function(Y) {
    Y.on('domready', function() {
        var node = Y.one('#participantsform');
        if (node) {
            var html = '<div class="local_mail_sendmessage">'
                + '<form action="' + M.cfg.wwwroot + '/local/mail/create.php" method="post" id="local_mail_form_send">'
                + '<input type="hidden" name="c" value="' + M.local_mail.course +'" />'
                + '<input type="hidden" name="rs" value="" />'
                + '<input type="hidden" name="sesskey" value="' + M.cfg.sesskey +'" />'
                + '<label for="local_mail_role">' + M.util.get_string('bulkmessage', 'local_mail') + '</label>'
                + '<select name="local_mail_role">'
                + '<option value="">' + M.util.get_string('choosedots', 'moodle') + '</option>'
                + '<option value="0">' + M.util.get_string('to', 'local_mail') + '</option>'
                + '<option value="1">' + M.util.get_string('cc', 'local_mail') + '</option>'
                + '<option value="2">' + M.util.get_string('bcc', 'local_mail') + '</option>'
                + '</select>'
                + '</form>'
                + '</div>';
            var form = Y.Node.create(html);
            node.append(form);
        }
    });

    Y.one("#page-content").delegate('change', function(e) {
        var objects = Y.all('.usercheckbox');
        var users = [];
        var num = [];
        Y.each(objects, function(obj, index) {
            if (obj.get('checked')) {
                num = /^[^\d]+(\d+)$/.exec(obj.get('name'));
                if (num) {
                    users.push(num[1]);
                }
            }
        });
        Y.one('#local_mail_form_send input[name="rs"]').set('value', users.join());
        Y.one('#local_mail_form_send').submit();
    }, '#local_mail_form_send select[name="local_mail_role"]');
});
