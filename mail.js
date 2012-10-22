YUI(M.yui.loader).use('io-base', 'node', 'json-parse', function(Y) {

    var init = function(){
        Y.one('span.mail_checkbox_all').removeClass('mail_hidden');
        Y.one('span.mail_more_actions').removeClass('mail_hidden');
        mail_hide_noscript_buttons();
        mail_enable_all_buttons(false);
        if (Y.one("input[name=type]").get('value') == 'trash') {
           mail_remove_action('.mail_menu_action_markasstarred');
           mail_remove_action('.mail_menu_action_markasunstarred');
        }
        mail_hide_actions();
    };

    var mail_hide_actions = function() {
        Y.all('.mail_menu_actions li').each(function(node){
            node.hide();
        });
    };

    var mail_update_menu_actions = function() {
        mail_hide_actions();
        if (Y.all('.mail_selected.mail_unread').size()) {
            Y.one('.mail_menu_action_markasread').ancestor('li').show();
        }
        if (Y.all('.mail_selected.mail_unread').size() < Y.all('.mail_selected').size()) {
            Y.one('.mail_menu_action_markasunread').ancestor('li').show();
        }
        if (Y.all('.mail_selected span.mail_starred').size()) {
            Y.one('.mail_menu_action_markasunstarred').ancestor('li').show();
        }
        if (Y.all('.mail_selected span.mail_unstarred').size()) {
            Y.one('.mail_menu_action_markasstarred').ancestor('li').show();
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
        Y.all('.mail_toolbar .mail_noscript_button').addClass('mail_hidden');
    };

    var mail_toggle_menu_actions = function(){
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
        var trash = (Y.one("input[name=type]").get('value') == 'trash');
        if (checkbox.get('checked')) {
            //Read or unread
            if (node.hasClass('mail_unread')) {
                menu.one('a.mail_menu_action_markasread').ancestor('li').show();
            } else {
                menu.one('a.mail_menu_action_markasunread').ancestor('li').show();
            }
            //Starred or unstarred
            if (!trash && node.one('.mail_flags span').hasClass('mail_starred')) {
                menu.one('a.mail_menu_action_markasunstarred').ancestor('li').show();
            } else {
                if (!trash) {
                    menu.one('a.mail_menu_action_markasstarred').ancestor('li').show();
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
                if (!trash && node.one('.mail_flags a span').hasClass('mail_starred')) {
                    var nodes = node.siblings(function(obj){
                        return obj.hasClass('.mail_selected') && obj.one('.mail_flags a span.mail_starred');
                    });
                    if (!nodes.size()) {
                        menu.one('a.mail_menu_action_markasunstarred').ancestor('li').hide();
                    }
                } else {
                    var nodes = node.siblings(function(obj){
                        return obj.hasClass('.mail_selected') &&  obj.one('.mail_flags a span.mail_unstarred');
                    });
                    if (!trash && !nodes.size()) {
                        menu.one('a.mail_menu_action_markasstarred').ancestor('li').hide();
                    }
                }
            }
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
            Y.one('.mail_more_actions').addClass('mail_button_disabled');
            Y.one('.mail_actselect').addClass('mail_hidden');
            Y.one('.mail_checkbox_all input[name=selectall]').set('checked','');
        }
        if(!Y.all('.mail_item > input').size()) {
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

    var mail_select_unread = function(){
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
            }
            if (obj.info) {
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

        if (action == 'togglestarred') {
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
            //console.log(nodes);
            nodes.each(function (node) {
                ancestor = node.ancestor('.mail_item');
                if (action == 'starred') {
                    if(child = ancestor.one('.mail_unstarred')) {
                        child.replaceClass('mail_unstarred', 'mail_starred');
                        child.ancestor('a').set('title', M.util.get_string('starred','local_mail'));
                    }
                } else if (action == 'unstarred') {
                    if(child = ancestor.one('.mail_starred')) {
                        child.replaceClass('mail_starred', 'mail_unstarred');
                        child.ancestor('a').set('title', M.util.get_string('unstarred','local_mail'));
                    }
                } else if (action == 'markasread') {
                    ancestor.removeClass('mail_unread');
                } else if (action == 'markasunread') {
                    ancestor.addClass('mail_unread');
                } else if (action == 'delete') {
                    ancestor.addClass('mail_hidden');
                }
            });
        }
        //Ajax call
        var cfg =  {
            method: 'POST',
            data: {
                sesskey: Y.one('input[name="sesskey"]').get('value'),
                msgs: ids,
                type: Y.one('input[name="type"]').get('value'),
                offset: Y.one('input[name="offset"]').get('value'),
                itemid: Y.one('input[name="itemid"]').get('value'),
                action: action
            },
            on: {
                success:handleSuccess,
                failure:handleFailure
            }
        };
        if (Y.one('input[name="courseid"]')) {
            cfg.data.courseid = Y.one('input[name="courseid"]').get('value');
        }
        if (action == 'perpage') {
            cfg.data.perpage = node.get('innerText');
        }
        var request = Y.io(M.cfg.wwwroot + '/local/mail/ajax.php', cfg);
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
            mail_update_menu_actions();
        } else {
            mail_select_none();
        }
        mail_check_selected();
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

    //Show all hidden elements
    init();
});