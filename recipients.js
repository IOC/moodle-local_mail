YUI(M.yui.loader).use('io-base', 'node', 'json-parse', 'panel', 'datatable-base', 'dd-plugin', 'node-event-simulate', function(Y) {

    var mail_recipients_panel;
    var timeout;
    var mail_recipients;
    var mail_existing_recipients = [];

    var init = function () {
        mail_update_compose_url();
        mail_create_recipients_panel();
        mail_reset_recipients();
        mail_show_recipipients_button_ajax();
    };

    var mail_update_compose_url = function() {
        if (history.pushState) {
            var node = Y.one('input[name="m"]');
            if (node) {
                history.pushState({}, document.title, M.cfg.wwwroot + '/local/mail/compose.php?m=' + node.get('value'));
            }
        }
    };

    var mail_show_recipipients_button_ajax = function () {
        var ajaxbutton = Y.one('input[id$="id_recipients_ajax"]') || Y.one('button[id$="id_recipients_ajax"]');
        var noajaxbutton = Y.one('input[id$="id_recipients"]');

        if (noajaxbutton) {
            noajaxbutton.hide();
        }
        if (ajaxbutton) {
            ajaxbutton.removeClass('mail_hidden').ancestor('div').ancestor('div').removeClass('mail_hidden');
        }
    };

    var mail_create_recipients_panel = function () {
        var title = M.util.get_string('addrecipients', 'local_mail');
        var bodywidth = Y.one('body').get('offsetWidth');
        var width = (bodywidth > 500 ? 500 : bodywidth - 10);

        if (Y.one('#local_mail_recipients_form')) {
            mail_recipients_panel = new Y.Panel({
                srcNode      : '#local_mail_recipients_form',
                headerContent: title,
                width        : width,
                autoScroll   : true,
                zIndex       : 3000,
                centered     : true,
                modal        : true,
                visible      : false,
                render       : true,
                plugins      : [Y.Plugin.Drag]
            });
            mail_recipients_panel.plug(Y.Plugin.Drag,{handles:['.yui3-widget-hd']});
            mail_recipients_panel.addButton({
                value  : M.util.get_string('applychanges', 'local_mail'),
                section: Y.WidgetStdMod.FOOTER,
                action : function (e) {
                    e.preventDefault();
                    mail_recipients_panel.hide();
                    if (mail_changes_recipients()) {
                        mail_doaction('updaterecipients');
                    }
                }
            });
        } else {
            mail_recipients_panel = null;
        }
    };

    // Success call.
    var handleSuccess = function (transactionid, response, args) {
        var obj = Y.JSON.parse(response.responseText);
        var node;

        clearInterval(timeout);
        if(obj.redirect) {
            Y.one('#id_recipientshidden').simulate('click');
            return;
        }
        if (obj.msgerror) {
            alert(obj.msgerror);
        } else {
            node = Y.one('.mail_recipients_loading');
            if (node) {
                node.hide();
            }
            mail_enable_all_recipients_buttons(obj.html && /mail_form_recipient/.test(obj.html));
            if (obj.html) {
                Y.one('#local_mail_recipients_list').setContent(obj.html);
                mail_update_recipients_state();
            } else {
                node = Y.one('#local_mail_recipients_list');
                if (node) {
                    node.setContent(M.util.get_string('emptyrecipients','local_mail'));
                }
            }
            if (obj.info) {
                mail_existing_recipients = obj.info.slice(0);
            }
        }
    };

    // Failure call.
    var handleFailure = function (transactionid, response, args) {
        clearInterval(timeout);
        console.log(response);
    };

    // Update screen data and async call.
    var mail_doaction = function(action){
        var search = '';
        var cfg;
        var recipients = '';
        var roleids = '';
        var node;

        node = Y.one('.mail_recipients_loading');
        if (node) {
            node.show();
        }

        node = Y.one('input[name="recipients_search"]');
        if (node) {
            search = node.get('value');
            search = search.replace(/^\s+|\s+$/g, '');
            node.set('value', search);
        }

        if (action == 'updaterecipients') {
            if (mail_existing_recipients) {
                Y.Array.each(mail_existing_recipients, function(recipient, index) {
                    if (Y.Array.indexOf(mail_recipients[3], recipient) != -1) {
                        recipients += mail_recipients[3][index] + ',';
                        roleids += '3,';
                    }
                });
            }
            for (var i = 0; i < mail_recipients.length; i++) {
                for (var j = 0; j < mail_recipients[i].length; j++) {
                    recipients += mail_recipients[i][j] + ',';
                    roleids += i + ',';
                }
            }
            recipients = recipients.replace(/,$/, '');
            roleids = roleids.replace(/,$/, '');
        } else {
            action = 'getrecipients';
        }

        // Ajax call.
        cfg = {
            method: 'POST',
            data: {
                msgs:    Y.one('input[name="m"]').get('value'),
                sesskey: Y.one('input[name="sesskey"]').get('value'),
                search:  search,
                groupid: getgroupid(),
                roleid:  getroleid(),
                action:  action
            },
            on: {
                success:handleSuccess,
                failure:handleFailure
            }
        };

        if (action == 'updaterecipients') {
            cfg.data.recipients = recipients;
            cfg.data.roleids = roleids;
        }

        Y.io(M.cfg.wwwroot + '/local/mail/ajax.php', cfg);
    };

    var wait = function() {
        clearInterval(timeout);
        timeout = setTimeout(function(){mail_doaction('search');}, 1000);
    };

    var getgroupid = function() {
        var select = Y.one('#local_mail_recipients_groups');
        return (select ? select.get('options').item(select.get('selectedIndex')).get('value') : 0);
    };

    var getroleid = function() {
        var select = Y.one('#local_mail_recipients_roles');
        return (select ? select.get('options').item(select.get('selectedIndex')).get('value') : 0);
    };

    var mail_recipient_action = function(node) {
        var name = node.get('name');
        var role = /^\w+/.exec(name)[0];
        var userid = /\[(\d+)\]/.exec(name)[1];
        var noderolestring;
        node.set('disabled', 'disabled');
        if (node.get('type') === 'image') {
            node.addClass('mail_novisible');
            node.siblings('input').set('disabled', '');
            node.siblings('input').removeClass('mail_hidden');
            node.ancestor('.mail_form_recipient').removeClass('mail_recipient_selected');
            noderolestring = node.ancestor('.mail_recipient_actions').siblings('.mail_form_recipient_role');
            noderolestring.set('innerHTML', '');
            noderolestring.addClass('mail_hidden');
            mail_remove_recipient(userid, [0,1,2]);
        } else {
            mail_hide_recipients_button('to', userid);
            mail_hide_recipients_button('cc', userid);
            mail_hide_recipients_button('bcc', userid);
            node.ancestor('.mail_form_recipient').addClass('mail_recipient_selected');
            noderolestring = node.ancestor('.mail_recipient_actions').siblings('.mail_form_recipient_role');
            noderolestring.set('innerHTML', M.util.get_string('shortadd' + role, 'local_mail') + ':');
            noderolestring.removeClass('mail_hidden');
            node.siblings('input[type="button"]').set('disabled', 'disabled');
            node.siblings('input[type="button"]').addClass('mail_hidden');
            node.siblings('.mail_recipient_actions input[type="image"]').removeClass('mail_novisible');
            node.siblings('.mail_recipient_actions input[type="image"]').set('disabled', '');
            mail_add_recipient(userid, role);
        }
    };

    var mail_select_all_recipient_action = function(node) {
        var role = /^[^_]*/.exec(node.get('name'));
        var nodes = Y.all(".mail_recipient_actions input[name^=" + role[0] + "]");
        if (nodes) {
            nodes.each(function (node) {
                mail_recipient_action(node);
            });
        }
    };

    var mail_add_recipient = function(userid, role) {
        if (role == 'to') {
            mail_recipients[0].push(userid);
            mail_remove_recipient(userid, [1,2,3]);
        } else if (role == 'cc') {
            mail_recipients[1].push(userid);
            mail_remove_recipient(userid, [0,2,3]);
        } else if (role == 'bcc') {
            mail_recipients[2].push(userid);
            mail_remove_recipient(userid, [0,1,3]);
        }
    };

    var mail_remove_recipient = function(userid, roles) {
        var pos;
        Y.Array.each(roles, function(role) {
            pos = Y.Array.indexOf(mail_recipients[role], userid);
            if (pos != -1) {
                mail_recipients[role].splice(pos, 1);
            } else {
                if (Y.Array.indexOf(mail_existing_recipients, userid) != -1 && Y.Array.indexOf(mail_recipients[3], userid) == -1) {
                    mail_recipients[3].push(userid);
                }
            }
        });
    };

    var mail_changes_recipients = function() {
        return (mail_recipients[0].length > 0 ||
            mail_recipients[1].length > 0 ||
            mail_recipients[2].length > 0 ||
            mail_recipients[3].length > 0
            );
    };

    var mail_reset_recipients = function() {
        mail_recipients = [
            [],// To.
            [],// Cc.
            [],// Bcc.
            []// Remove.
        ];
    };

    var mail_update_recipients_state = function () {
        var node;
        Y.Array.each(mail_recipients[0], function (recipient) {
            mail_hide_recipients_button('to', recipient);
            mail_hide_recipients_button('cc', recipient);
            mail_hide_recipients_button('bcc', recipient);
            node = Y.one('span.mail_form_recipient_role[data-role-recipient="' + recipient + '"]');
            if (node) {
                node.set('innerHTML', M.util.get_string('shortaddto', 'local_mail') + ':');
                node.removeClass('mail_hidden');
                node.ancestor('.mail_form_recipient').addClass('mail_recipient_selected');
            }
            node = Y.one('.mail_recipient_actions input[name="remove[' + recipient + ']"]');
            if (node) {
                node.set('disabled', '');
                node.removeClass('mail_novisible');
            }
        });
        Y.Array.each(mail_recipients[1], function (recipient) {
            mail_hide_recipients_button('cc', recipient);
            mail_hide_recipients_button('to', recipient);
            mail_hide_recipients_button('bcc', recipient);
            node = Y.one('span.mail_form_recipient_role[data-role-recipient="' + recipient + '"]');
            if (node) {
                node.set('innerHTML', M.util.get_string('shortaddcc', 'local_mail') + ':');
                node.removeClass('mail_hidden');
                node.ancestor('.mail_form_recipient').addClass('mail_recipient_selected');
            }
            node = Y.one('.mail_recipient_actions input[name="remove[' + recipient + ']"]');
            if (node) {
                node.set('disabled', '');
                node.removeClass('mail_novisible');
            }
        });
        Y.Array.each(mail_recipients[2], function (recipient) {
            mail_hide_recipients_button('bcc', recipient);
            mail_hide_recipients_button('to', recipient);
            mail_hide_recipients_button('cc', recipient);
            node = Y.one('span.mail_form_recipient_role[data-role-recipient="' + recipient + '"]');
            if (node) {
                node.set('innerHTML', M.util.get_string('shortaddbcc', 'local_mail') + ':');
                node.removeClass('mail_hidden');
                node.ancestor('.mail_form_recipient').addClass('mail_recipient_selected');
            }
            node = Y.one('.mail_recipient_actions input[name="remove[' + recipient + ']"]');
            if (node) {
                node.set('disabled', '');
                node.removeClass('mail_novisible');
            }
        });
        Y.Array.each(mail_recipients[3], function (recipient) {
            mail_show_recipients_button('to', recipient);
            mail_show_recipients_button('cc', recipient);
            mail_show_recipients_button('bcc', recipient);
            node = Y.one('.mail_recipient_actions input[name="remove[' + recipient + ']"]');
            if (node) {
                node.addClass('mail_novisible');
                node.set('disabled', 'disabled');
                node.ancestor('.mail_form_recipient').removeClass('mail_recipient_selected');
            }
            node = Y.one('span.mail_form_recipient_role[data-role-recipient="' + recipient + '"]');
            if (node) {
                node.set('innerHTML', '');
                node.addClass('mail_hidden');
            }
        });
    };

    var mail_show_recipients_button = function (role, recipient) {
        var node = Y.one('.mail_recipient_actions input[name="' + role + '[' + recipient + ']"]');
        if (node) {
            node.set('disabled', '');
            node.removeClass('mail_hidden');
        }
    };

    var mail_hide_recipients_button = function (role, recipient) {
        var node = Y.one('.mail_recipient_actions input[name="' + role + '[' + recipient + ']"]');
        if (node) {
            node.set('disabled', 'disabled');
            node.addClass('mail_hidden');
        }
    };

    var mail_enable_all_recipients_buttons = function (enable) {
        var nodes = Y.all('.mail_all_recipients_actions input');
        if (nodes) {
            nodes.each(function(node) {
                if (enable) {
                    node.set('disabled', '');
                } else {
                    node.set('disabled', 'disabled');
                }
            });
        }
    };

    // Click label on recipients button.
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_reset_recipients();
        mail_doaction('');
        if (mail_recipients_panel) {
            mail_recipients_panel.show();
            Y.one('#local_mail_recipients_form').removeClass('mail_hidden');
        } else {
            alert(M.util.get_string('notingroup', 'local_mail'));
        }
    }, '#id_recipients_ajax');

    // Change on group select.
    Y.one("#region-main").delegate('change', function(e) {
        e.preventDefault();
        mail_doaction('setgroup');
    }, '#local_mail_recipients_groups');

    // Change on role select.
    Y.one("#region-main").delegate('change', function(e) {
        e.preventDefault();
        mail_doaction('setrole');
    }, '#local_mail_recipients_roles');

    // Keyup on search input.
    Y.one("#region-main").delegate('keyup', function(e) {
        wait();
    }, 'input[name="recipients_search"]');

    // Keydown on search input.
    Y.one("#region-main").delegate('keydown', function(e) {
        clearInterval(timeout);
    }, 'input[name="recipients_search"]');

    // Click on recipient action.
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_recipient_action(this);
    }, '.mail_recipient_actions input');

    // Click on select all recipients action.
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_select_all_recipient_action(this);
    }, '.mail_all_recipients_actions input');

    init();
});
