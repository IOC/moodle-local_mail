YUI(M.yui.loader).use('io-base', 'node', 'json-parse', function(Y) {

    var mail_message_view = false;

    var init = function(){
        var type = Y.one("input[name=type]").get('value');
        if (Y.one('input[name="m"]')) {
            mail_message_view = true;
        }
        Y.one('span.mail_checkbox_all').removeClass('mail_hidden');
        Y.one('span.mail_more_actions').removeClass('mail_hidden');
        mail_hide_noscript_buttons();
        mail_enable_all_buttons(mail_message_view);
        if (type == 'trash') {
            mail_remove_action('.mail_menu_action_markasstarred');
            mail_remove_action('.mail_menu_action_markasunstarred');
        } else if (type == 'label') {
            Y.one('input[name="editlbl"]').remove();
            Y.one('input[name="removelbl"]').remove();
            Y.one('.mail_toolbar_sep').remove();
        }
        mail_update_menu_actions();
    };

    var mail_hide_actions = function() {
        var type = Y.one("input[name=type]").get('value');
        Y.all('.mail_menu_actions li').each(function(node){
            node.hide();
        });
        if (type == 'label') {
            mail_show_label_actions(false);
        }
    };

    var mail_show_label_actions = function(separator) {
        if (separator) {
            Y.one('.mail_menu_action_separator').ancestor('li').show();
        }
        Y.one('.mail_menu_action_editlabel').ancestor('li').show();
        Y.one('.mail_menu_action_removelabel').ancestor('li').show();
    };

    var mail_update_menu_actions = function() {
        var type = Y.one("input[name=type]").get('value');
        var separator = false;

        mail_hide_actions();
        if (mail_message_view) {
            var type = Y.one("input[name=type]").get('value');
            if (type == 'trash') {
                Y.one('.mail_menu_action_markasunread').ancestor('li').show();
            } else {
                Y.one('.mail_menu_action_markasunread').ancestor('li').show();
                if (Y.one('.mail_flags span').hasClass('mail_starred')) {
                    Y.one('.mail_menu_action_markasunstarred').ancestor('li').show();
                } else {
                    Y.one('.mail_menu_action_markasstarred').ancestor('li').show();
                }
            }
        } else {
            if (Y.all('.mail_selected.mail_unread').size()) {
                Y.one('.mail_menu_action_markasread').ancestor('li').show();
                separator = true;
            }
            if (Y.all('.mail_selected.mail_unread').size() < Y.all('.mail_selected').size()) {
                Y.one('.mail_menu_action_markasunread').ancestor('li').show();
                separator = true;
            }
            if (Y.all('.mail_selected span.mail_starred').size()) {
                Y.one('.mail_menu_action_markasunstarred').ancestor('li').show();
                separator = true;
            }
            if (Y.all('.mail_selected span.mail_unstarred').size()) {
                Y.one('.mail_menu_action_markasstarred').ancestor('li').show();
                separator = true;
            }
        }
        if (type == 'label') {
            mail_show_label_actions(separator);
        }

    };

    var mail_toggle_menu = function(){
        var button = Y.one('.mail_checkbox_all');
        if (!button.hasClass('mail_button_disabled')) {
          Y.one('.mail_optselect').toggleClass('mail_hidden');
        }
    };

    var mail_hide_menu_options = function(){
        Y.one('.mail_optselect').addClass('mail_hidden');
    };

    var mail_hide_menu_actions = function(){
        Y.one('.mail_actselect').addClass('mail_hidden');
    };

    var mail_hide_noscript_buttons = function(){
        if (mail_message_view) {
            Y.one('.mail_checkbox_all').remove();
            Y.one('input[name="unread"]').remove();
        } else {
            Y.all('.mail_toolbar .mail_noscript_button').addClass('mail_hidden');
        }
    };

    var mail_toggle_menu_actions = function() {
        var button = Y.one('.mail_more_actions');
        var menu = Y.one('.mail_actselect');
        var position = button.getXY();
        if (!button.hasClass('mail_button_disabled')) {
            position[1] += button.get('clientHeight') + 3;
            menu.toggleClass('mail_hidden');
            menu.setXY(position);
        }
    };

    var mail_remove_action = function(action) {
        Y.one(action).ancestor('li').remove();
    };

    var mail_customize_menu_actions = function(node) {
        var menu = Y.one('.mail_menu_actions');
        var checkbox = node.one('input[type=checkbox]');
        var type = Y.one("input[name=type]").get('value');
        var separator = false;

        if (checkbox.get('checked')) {
            //Read or unread
            if (node.hasClass('mail_unread')) {
                menu.one('a.mail_menu_action_markasread').ancestor('li').show();
                separator = true;
            } else {
                menu.one('a.mail_menu_action_markasunread').ancestor('li').show();
                separator = true;
            }
            //Starred or unstarred
            if (type != 'trash' && node.one('.mail_flags span').hasClass('mail_starred')) {
                menu.one('a.mail_menu_action_markasunstarred').ancestor('li').show();
                separator = true;
            } else {
                if (type != 'trash') {
                    menu.one('a.mail_menu_action_markasstarred').ancestor('li').show();
                    separator = true;
                }
            }
        } else {
            if (!Y.all('.mail_list .mail_selected').size()) {
                mail_hide_actions();
            } else {
                //Read or unread
                if (node.hasClass('mail_unread')) {
                    if (!node.siblings('.mail_selected.mail_unread').size()) {
                        menu.one('a.mail_menu_action_markasread').ancestor('li').hide();
                    }
                } else {
                    if (node.siblings('.mail_selected.mail_unread').size() == node.siblings('.mail_selected').size()) {
                        menu.one('a.mail_menu_action_markasunread').ancestor('li').hide();
                    }
                }
                //Starred or unstarred
                if (type != 'trash' && node.one('.mail_flags a span').hasClass('mail_starred')) {
                    var nodes = node.siblings(function(obj) {
                        return obj.hasClass('mail_selected') && obj.one('.mail_flags a span.mail_starred');
                    });
                    if (!nodes.size()) {
                        menu.one('a.mail_menu_action_markasunstarred').ancestor('li').hide();
                    }
                } else {
                    var nodes = node.siblings(function(obj) {
                        return obj.hasClass('mail_selected') &&  obj.one('.mail_flags a span.mail_unstarred');
                    });
                    if (type != 'trash' && !nodes.size()) {
                        menu.one('a.mail_menu_action_markasstarred').ancestor('li').hide();
                    }
                }
            }
        }
        if (type == 'label') {
            mail_show_label_actions(separator);
        }
    };

    var mail_check_selected = function(){
        mail_enable_all_buttons(Y.all('.mail_selected').size());
    };

    var mail_enable_button = function(name, bool) {
        bool = (typeof bool !== 'undefined' ? bool : false);
        if (bool) {
            Y.one('.mail_toolbar input[name='+name+']').set('disabled','');
        } else {
            Y.one('.mail_toolbar input[name='+name+']').set('disabled','disabled');
        }
    };

    var mail_enable_all_buttons = function(bool) {
        var mail_buttons = Y.all('.mail_toolbar > input').get('name');

        Y.each(mail_buttons, (function(value){
            mail_enable_button(value, bool);
        }));
        if (bool) {
            Y.one('.mail_more_actions').removeClass('mail_button_disabled');
        } else {
            if (Y.one("input[name=type]").get('value') != 'label') {
                Y.one('.mail_more_actions').addClass('mail_button_disabled');
            }
            Y.one('.mail_actselect').addClass('mail_hidden');
            Y.one('.mail_checkbox_all input[name=selectall]').set('checked','');
        }
        if(!mail_message_view && !Y.all('.mail_item > input').size()) {
            Y.one('.mail_checkbox_all').addClass('mail_button_disabled');
            Y.one('.mail_checkbox_all input[name=selectall]').set('disabled','disabled').set('checked','');
        }
    };

    var mail_main_checkbox = function(bool){
        if (bool){
            Y.one('.mail_checkbox_all > input').set('checked', 'checked');
        } else {
            if(!Y.all('.mail_selected').size()) {
                Y.one('.mail_checkbox_all > input').set('checked', '');
            }
        }
        mail_check_selected();
    };

    var mail_select_all = function(){
        var checkbox = Y.one('.mail_checkbox_all > input');
        checkbox.set('checked', 'checked');
        var nodes = Y.all('.mail_checkbox');
        nodes.each(function(node) {
               node.set('checked', 'checked');
            node.ancestor('.mail_item').addClass('mail_selected');
        });
    };

    var mail_select_none = function(){
        var checkbox = Y.one('.mail_checkbox_all > input');
        checkbox.set('checked', '');
        var nodes = Y.all('.mail_checkbox');
        nodes.each(function(node) {
               node.set('checked', '');
            node.ancestor('.mail_item').removeClass('mail_selected');
        });
    };

    var mail_select_read = function(){
        var nodes = Y.all('.mail_item > input');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (!ancestor.hasClass('mail_unread')){
                    node.set('checked', 'checked');
                    ancestor.addClass('mail_selected');
                } else {
                    node.set('checked', '');
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    var mail_select_unread = function() {
        var nodes = Y.all('.mail_item > input');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (ancestor.hasClass('mail_unread')){
                    node.set('checked', 'checked');
                    ancestor.addClass('mail_selected');
                } else {
                    node.set('checked', '');
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    var mail_select_starred = function() {
        var nodes = Y.all('.mail_item > input');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (ancestor.one('.mail_starred')){
                    node.set('checked', 'checked');
                    ancestor.addClass('mail_selected');
                } else {
                    node.set('checked', '');
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    var mail_select_unstarred = function() {
        var nodes = Y.all('.mail_item > input');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (ancestor.one('.mail_unstarred')) {
                    node.set('checked', 'checked');
                    ancestor.addClass('mail_selected');
                } else {
                    node.set('checked', '');
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    //Success call
    var handleSuccess = function (transactionid, response, arguments) {
        var obj = Y.JSON.parse(response.responseText);
        var img;

        if (obj.msgerror) {
            alert(obj.msgerror);
        } else {
            if (obj.html) {
                Y.one('div.region-content form').setContent(obj.html);
                init();
                mail_update_url();
            }
            if (obj.info) {
                if (obj.info.root) {
                    Y.one('.mail_root span').setContent(obj.info.root);
                }
                if (obj.info.inbox) {
                    img = Y.one('.mail_inbox a img').get('outerHTML');
                    Y.one('.mail_inbox a').setContent(img+obj.info.inbox);
                }
                if (obj.info.drafts) {
                    img = Y.one('.mail_drafts a img').get('outerHTML');
                    Y.one('.mail_drafts a').setContent(img+obj.info.drafts);
                }
                if (obj.info.courses) {
                    Y.each(obj.info.courses, (function(value, index) {
                        img = Y.one('.mail_course_'+index+' a img').get('outerHTML');
                        Y.one('.mail_course_'+index+' a').setContent(img+value);
                    }));
                }
                if (obj.info.labels) {
                    Y.each(obj.info.labels, (function(value, index) {
                        img = Y.one('.mail_label_'+index+' a img').get('outerHTML');
                        Y.one('.mail_label_'+index+' a').setContent(img+value);
                    }));
                }
            }
        }
    };

    //Failure call
    var handleFailure = function (transactionid, response, arguments) {
        console.log(response);
    };

    //Update screen data and async call
    var mail_doaction = function(action, node){
        node = (typeof node !== 'undefined' ? node : null);
        var nodes = Y.all('.mail_item > input:checked');
        var obj;
        var child;
        var ancestor;
        var ids;
        var request;
        var mail_view;

        if(mail_message_view) {
            if(action == 'togglestarred') {
                obj = node.one('span');
                if (obj.hasClass('mail_starred')) {
                    action = 'unstarred'
                    obj.replaceClass('mail_starred', 'mail_unstarred');
                    node.set('title', M.util.get_string('unstarred','local_mail'));
                } else {
                    action = 'starred'
                    obj.replaceClass('mail_unstarred', 'mail_starred');
                    node.set('title', M.util.get_string('starred','local_mail'));
                }
            } else if (action == 'delete') {
                mail_message_view = false;
            } else if (action == 'starred') {
                node = Y.one('.mail_flags span');
                node.replaceClass('mail_unstarred', 'mail_starred');
                node.ancestor('a').set('title', M.util.get_string('starred','local_mail'));
            } else if (action == 'unstarred') {
                node = Y.one('.mail_flags span');
                node.replaceClass('mail_starred', 'mail_unstarred');
                node.ancestor('a').set('title', M.util.get_string('unstarred','local_mail'));
            } else if(action == 'markasunread') {
                mail_message_view = false;
            } else if(action == 'goback') {
                mail_message_view = false;
            }
            mail_view = true;
            ids = Y.one('input[name="m"]').get('value');
        } else {//List messages view
            if(action == 'viewmail') {
                nodes.empty();
                ids = /m=(\d+)/.exec(node.get('href'))[1];
            }else if (action == 'togglestarred') {
                obj = node.ancestor('.mail_item').one('input.mail_checkbox');
                nodes = Y.all(obj);
                if (node.one('span').hasClass('mail_starred')) {
                    action = 'unstarred'
                } else {
                    action = 'starred'
                }
                ids = obj.get('value');
            } else if(action == 'perpage'){
                nodes.empty();
                action = 'perpage';
            } else {
                ids = Y.all('input[name="msgs[]"]:checked').get('value').join();
            }
            if (nodes.size()) {
                nodes.each(function (node) {
                    ancestor = node.ancestor('.mail_item');
                    if (action == 'starred') {
                        if(child = ancestor.one('.mail_unstarred')) {
                            child.replaceClass('mail_unstarred', 'mail_starred');
                            child.ancestor('a').set('title', M.util.get_string('starred','local_mail'));
                        }
                    } else if(action == 'unstarred') {
                        if(child = ancestor.one('.mail_starred')) {
                            child.replaceClass('mail_starred', 'mail_unstarred');
                            child.ancestor('a').set('title', M.util.get_string('unstarred','local_mail'));
                        }
                    } else if(action == 'markasread') {
                        ancestor.removeClass('mail_unread');
                    } else if(action == 'markasunread') {
                        ancestor.addClass('mail_unread');
                    } else if(action == 'delete') {
                        ancestor.addClass('mail_hidden');
                    }
                });
            }
            mail_view = false;
        }
        //Ajax call
        var cfg =  {
            method: 'POST',
            data: {
                msgs: ids,
                sesskey: Y.one('input[name="sesskey"]').get('value'),
                type: Y.one('input[name="type"]').get('value'),
                offset: Y.one('input[name="offset"]').get('value'),
                action: action,
                mailview: mail_view
            },
            on: {
                success:handleSuccess,
                failure:handleFailure
            }
        };
        if (Y.one('input[name="m"]')) {
            cfg.data.itemid = Y.one('input[name="m"]').get('value');
        }
        if(Y.one('input[name="itemid"]')) {
            cfg.data.itemid = Y.one('input[name="itemid"]').get('value');
        }

        if (action == 'perpage') {
            cfg.data.perpage = node.get('innerText');
        }
        request = Y.io(M.cfg.wwwroot + '/local/mail/ajax.php', cfg);
    };

    var mail_label_post = function(action) {
        var params = new Array();
        params.push('offset='+Y.one('input[name="offset"]').get('value'));
        params.push('sesskey='+Y.one('input[name="sesskey"]').get('value'));
        params.push(action+'=1');
        var url = Y.one('.region-content form').get('action');
        document.location.href =  url+'&'+params.join('&');
    };

    var mail_update_url = function() {
        var params = new Array();
        var offset;
        var m;
        var type;

        if (history.pushState) {
            type = Y.one('input[name="type"]').get('value');
            params.push('t='+type);
            if (mail_message_view) {
                params.push('m='+Y.one('input[name=m]').get('value'));
            }
            if (type == 'course') {
                params.push('c='+Y.one('input[name="itemid"]').get('value'));
            } else {
                if (type == 'label') {
                    params.push('l='+Y.one('input[name="itemid"]').get('value'));
                }
            }
            offset = Y.one('input[name="offset"]').get('value');
            if (parseInt(offset, 10) > 0) {
                params.push('offset='+offset);
            }
            history.pushState({}, document.title, M.cfg.wwwroot + '/local/mail/view.php?' + params.join('&'));
        }
    };

    //Background selection
    Y.one("div.region-content").delegate('click', function(e) {
        var node = this.ancestor('.mail_item');
        node.toggleClass('mail_selected');
        mail_main_checkbox(node.hasClass('mail_selected'));
        mail_customize_menu_actions(node);
    }, 'input.mail_checkbox');

    //Select all/none
    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
        Y.one('.mail_optselect').addClass('mail_hidden');
        if (this.get('checked')) {
            mail_select_all();
        } else {
            mail_select_none();
        }
        mail_check_selected();
        mail_update_menu_actions();
    }, '.mail_checkbox_all > input');

    //Toggle menu select all/none
    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_menu();
        mail_hide_menu_actions();
    }, '.mail_checkbox_all');

    //Checkbox hides menu options
    Y.one("div.region-content").delegate('click', function(e) {
        mail_hide_menu_options();
    }, '.mail_checkbox_all > input[name=selectall]');

    //Toggle menu actions
    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_menu_actions();
        mail_hide_menu_options();
    }, '.mail_more_actions');

    //Menu select all
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_all();
        mail_update_menu_actions();
    }, '.mail_menu_option_all');

    //Menu select none
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_none();
    }, '.mail_menu_option_none');

    //Menu select read
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
          mail_select_read();
        mail_update_menu_actions();
    }, '.mail_menu_option_read');

    //Menu select unread
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_unread();
        mail_update_menu_actions();
    }, '.mail_menu_option_unread');

    //Menu select starred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_starred();
        mail_update_menu_actions();
    }, '.mail_menu_option_starred');

    //Menu select unstarred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_unstarred();
        mail_update_menu_actions();
    }, '.mail_menu_option_unstarred');

    Y.one("div.region-content").delegate('click', function(e) {
        mail_check_selected();
    }, '.mail_optselect');

    //Menu action starred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('starred');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasstarred');

    //Menu action unstarred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('unstarred');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasunstarred');

    //Menu action markasread
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('markasread');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasread');

    //Menu action markasunread
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('markasunread');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasunread');

    //Menu action editlabel
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_label_post('editlbl');
    }, '.mail_menu_action_editlabel');

    //Menu action removelabel
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_label_post('removelbl');
    }, '.mail_menu_action_removelabel');

    //Starred and unstarred
    Y.one('div.region-content').delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('togglestarred', this);
        mail_update_menu_actions();
    }, '.mail_flags a');

    //Delete button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('delete');
    }, 'input[name=delete]');

    //Prev page button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('prevpage');
    }, 'input[name=prevpage]');

    //Prev page button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('nextpage');
    }, 'input[name=nextpage]');

    //Go back button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('goback');
    }, 'input[name=goback]');

    //Mail per page
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('perpage', this);
    }, 'div.mail_perpage a');

    //Hide all menus
    Y.on('click', function(e) {
        mail_hide_menu_options();
        mail_hide_menu_actions();
    }, 'body');

    //Show message
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('viewmail', this);
    }, 'a.mail_link');

    //Show all hidden elements
    init();
    mail_update_url();
});