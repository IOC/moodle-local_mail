YUI(M.yui.loader).use('io-base', 'node', 'json-parse', 'panel', 'datatable-base', 'dd-plugin', function(Y) {

    var mail_message_view = false;
    var mail_checkbox_labels_default = {};
    var mail_view_type = '';
    var mail_edit_label_panel;
    var mail_new_label_panel;

    var init = function(){
        mail_view_type = Y.one('input[name="type"]').get('value');
        if (Y.one('input[name="m"]')) {
            mail_message_view = true;
            Y.one('.mail_checkbox_all').remove();
        }
        mail_enable_all_buttons(mail_message_view);
        if (!mail_message_view) {
            mail_select_none();
        }
        if (mail_view_type == 'trash') {
            mail_remove_action('.mail_menu_action_markasstarred');
            mail_remove_action('.mail_menu_action_markasunstarred');
        }
        mail_update_menu_actions();
        mail_create_edit_label_panel();
        mail_create_new_label_panel();
    };

    var mail_create_edit_label_panel = function () {
        var title = M.util.get_string('editlabel', 'local_mail');
        var obj = (Y.one('.mail_list')?Y.one('.mail_list'):Y.one('.mail_view'));
        var position = obj.getXY();
        var width = 400;
        var posx = position[0]+(Y.one('body').get('offsetWidth')/2)-width;
        mail_edit_label_panel = new Y.Panel({
            srcNode      : '#local_mail_form_edit_label',
            headerContent: title,
            width        : width,
            zIndex       : 5,
            centered     : false,
            modal        : true,
            visible      : false,
            render       : true,
            xy           : [posx,position[1]],
            plugins      : [Y.Plugin.Drag]
        });
        mail_edit_label_panel.addButton({
            value  : M.util.get_string('submit', 'moodle'),
            section: Y.WidgetStdMod.FOOTER,
            action : function (e) {
                e.preventDefault();
                mail_edit_label_panel.hide();
                mail_doaction('setlabel');
            }
        });
        mail_edit_label_panel.addButton({
            value  : M.util.get_string('cancel', 'moodle'),
            section: Y.WidgetStdMod.FOOTER,
            action : function (e) {
                e.preventDefault();
                mail_edit_label_panel.hide();
            }
        });
    };

    var mail_create_new_label_panel = function () {
        var title = M.util.get_string('newlabel', 'local_mail');
        var obj = (Y.one('.mail_list')?Y.one('.mail_list'):Y.one('.mail_view'));
        var position = obj.getXY();
        var width = 400;
        var posx = position[0]+(Y.one('body').get('offsetWidth')/2)-width;
        mail_new_label_panel = new Y.Panel({
            srcNode      : '#local_mail_form_new_label',
            headerContent: title,
            width        : width,
            zIndex       : 5,
            centered     : false,
            modal        : true,
            visible      : false,
            render       : true,
            xy           : [posx,position[1]],
            plugins      : [Y.Plugin.Drag]
        });
        mail_new_label_panel.addButton({
            value  : M.util.get_string('submit', 'moodle'),
            section: Y.WidgetStdMod.FOOTER,
            action : function (e) {
                e.preventDefault();
                mail_new_label_panel.hide();
                mail_doaction('newlabel');
            }
        });
        mail_new_label_panel.addButton({
            value  : M.util.get_string('cancel', 'moodle'),
            section: Y.WidgetStdMod.FOOTER,
            action : function (e) {
                e.preventDefault();
                mail_new_label_panel.hide();
            }
        });
    };

    var mail_hide_actions = function() {
        Y.all('.mail_menu_actions li').each(function(node){
            node.hide();
        });
        mail_show_label_actions(false);
    };

    var mail_show_label_actions = function(separator) {
        if (mail_view_type == 'label' && !mail_message_view) {
            if (separator) {
                Y.one('.mail_menu_action_separator').ancestor('li').show();
            }
            Y.one('.mail_menu_action_editlabel').ancestor('li').show();
            Y.one('.mail_menu_action_removelabel').ancestor('li').show();
        }
    };

    var mail_update_menu_actions = function() {
        var separator = false;
        mail_hide_actions();
        if (mail_message_view) {
            if (mail_view_type == 'trash') {
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
        mail_show_label_actions(separator);
    };

    var mail_toggle_menu = function() {
        var button = Y.one('.mail_checkbox_all');
        if (!button.hasClass('mail_button_disabled')) {
          Y.one('.mail_optselect').toggleClass('mail_hidden');
        }
    };

    var mail_hide_menu_options = function() {
        Y.one('.mail_optselect').addClass('mail_hidden');
    };

    var mail_hide_menu_actions = function() {
        Y.one('.mail_actselect').addClass('mail_hidden');
    };

    var mail_hide_menu_labels = function() {
        if (mail_view_type != 'trash') {
            Y.one('.mail_labelselect').addClass('mail_hidden');
        }
    };

    var mail_toggle_menu_actions = function() {
        var button = Y.one('.mail_more_actions');
        var menu = Y.one('.mail_actselect');
        var position = button.getXY();
        if (!button.hasClass('mail_button_disabled')) {
            position[1] += button.get('clientHeight') + 2;
            menu.toggleClass('mail_hidden');
            menu.setXY(position);
        }
    };

    var mail_toggle_menu_labels = function() {
        var button = Y.one('.mail_assignlbl');
        var menu = Y.one('.mail_labelselect');
        var position = button.getXY();
        if (!button.hasClass('mail_button_disabled')) {
            position[1] += button.get('clientHeight') + 2;
            menu.toggleClass('mail_hidden');
            menu.setXY(position);
        }
    };

    var mail_remove_action = function(action) {
        Y.one(action).ancestor('li').remove();
    };

    var mail_customize_menu_actions = function(checkbox) {
        var menu = Y.one('.mail_menu_actions');
        var mailitem = checkbox.ancestor('.mail_item');
        var separator = false;
        var nodes;
        if (mail_is_checkbox_checked(checkbox)) {
            //Read or unread
            if (mailitem.hasClass('mail_unread')) {
                menu.one('a.mail_menu_action_markasread').ancestor('li').show();
                separator = true;
            } else {
                menu.one('a.mail_menu_action_markasunread').ancestor('li').show();
                separator = true;
            }
            //Starred or unstarred
            if (mail_view_type != 'trash' && mailitem.one('.mail_flags span').hasClass('mail_starred')) {
                menu.one('a.mail_menu_action_markasunstarred').ancestor('li').show();
                separator = true;
            } else {
                if (mail_view_type != 'trash') {
                    menu.one('a.mail_menu_action_markasstarred').ancestor('li').show();
                    separator = true;
                }
            }
        } else {
            if (!Y.all('.mail_list .mail_selected').size()) {
                mail_hide_actions();
            } else {
                //Read or unread
                if (mailitem.hasClass('mail_unread')) {
                    if (!mailitem.siblings('.mail_selected.mail_unread').size()) {
                        menu.one('a.mail_menu_action_markasread').ancestor('li').hide();
                    }
                } else {
                    if (mailitem.siblings('.mail_selected.mail_unread').size() == mailitem.siblings('.mail_selected').size()) {
                        menu.one('a.mail_menu_action_markasunread').ancestor('li').hide();
                    }
                }
                //Starred or unstarred
                if (mail_view_type != 'trash' && mailitem.one('.mail_flags a span').hasClass('mail_starred')) {
                    nodes = mailitem.siblings(function(obj) {
                        return obj.hasClass('mail_selected') && obj.one('.mail_flags a span.mail_starred');
                    });
                    if (!nodes.size()) {
                        menu.one('a.mail_menu_action_markasunstarred').ancestor('li').hide();
                    }
                } else {
                    nodes = mailitem.siblings(function(obj) {
                        return obj.hasClass('mail_selected') && obj.one('.mail_flags a span.mail_unstarred');
                    });
                    if (mail_view_type != 'trash' && !nodes.size()) {
                        menu.one('a.mail_menu_action_markasstarred').ancestor('li').hide();
                    }
                }
            }
        }
        mail_show_label_actions(separator);
    };

    var mail_label_default_values = function () {
        var grouplabels;
        if (Y.one('.mail_labelselect').hasClass('mail_hidden')) {
            Y.each(M.local_mail.mail_labels, function (label, index) {
                mail_checkbox_labels_default[index] = 0;
            });
            if (mail_message_view) {
                grouplabels = Y.all('.mail_group_labels span');
                if (grouplabels) {
                    mail_set_label_default_values(grouplabels);
                }
            } else {
                var nodes = mail_get_checkboxs_checked();
                Y.each(nodes, function (node, index) {
                    grouplabels = node.ancestor('.mail_item').all('.mail_group_labels span');
                    if (grouplabels) {
                        mail_set_label_default_values(grouplabels);
                    }
                });
            }
            mail_label_set_values();
        }
    };

    var mail_set_label_default_values = function (grouplabels) {
        var classnames = [];
        var num;
        Y.each(grouplabels, function (grouplabel, index) {
            classnames = grouplabel.getAttribute('class').split(' ');
            Y.each(classnames, function(classname){
                num = /mail_label_(\d+)/.exec(classname);
                if (num) {
                    mail_checkbox_labels_default[num[1]] += 1;
                }
            });
        });
        if (mail_view_type == 'label') {
            num = parseInt(Y.one('input[name="itemid"]').get('value'), 10);
            mail_checkbox_labels_default[num] += 1;
        }
    };

    var mail_menu_label_selection = function (node) {
        var checkbox = node.one('.mail_adv_checkbox');
        if (checkbox) {
            mail_toggle_checkbox(checkbox);
        }
    };

    var mail_customize_menu_label = function() {
        if (Y.all('.mail_menu_labels li').size() > 1) {
            if(mail_label_check_default_values()) {
                Y.one('.mail_menu_labels .mail_menu_label_newlabel').removeClass('mail_hidden');
                Y.one('.mail_menu_labels .mail_menu_label_apply').addClass('mail_hidden');
            } else {
                Y.one('.mail_menu_labels .mail_menu_label_newlabel').addClass('mail_hidden');
                Y.one('.mail_menu_labels .mail_menu_label_apply').removeClass('mail_hidden');
            }
        }
    };

    var mail_label_check_default_values = function () {
        var isdefault = true;
        var classname;
        var labelid;
        var num;

        if (!mail_message_view) {
            var labels = Y.all('.mail_menu_labels .mail_adv_checkbox');
            var total = mail_get_checkboxs_checked().size();

            Y.each(labels, function(label, index) {
                classname = label.getAttribute('class');
                num = /mail_label_value_(\d+)/.exec(classname);
                if (num) {
                    labelid = num[1];
                }
                if (mail_checkbox_labels_default[labelid] == total) {
                    isdefault = isdefault && label.hasClass('mail_checkbox1');
                } else if(mail_checkbox_labels_default[labelid] > 0) {
                    isdefault = isdefault && label.hasClass('mail_checkbox2');
                } else {
                    isdefault = isdefault && label.hasClass('mail_checkbox0');
                }
            });
        }
        return isdefault;
    };

    var mail_label_set_values = function () {
        var total = (mail_message_view?1:mail_get_checkboxs_checked().size());
        var state;

        Y.each(mail_checkbox_labels_default, function(value, index){
            if (value == total) {
                state = 1;
            } else if(value > 0) {
                state = 2;
            } else {
                state = 0;
            }
            mail_set_checkbox(Y.one('.mail_menu_labels .mail_label_value_'+index), state);
        });
    };

    var mail_get_label_value = function(checkbox){
        var value;
        classnames = checkbox.getAttribute('class').split(' ');
        Y.each(classnames, function(classname){
            num = /mail_label_value_(\d+)/.exec(classname);
            if (num) {
                value = num[1];
            }
        });
        return value;
    };

    var mail_get_labels_checked = function(){
        return Y.all('.mail_menu_labels .mail_checkbox1');
    };

    var mail_get_labels_thirdstate = function(){
        return Y.all('.mail_menu_labels .mail_checkbox2');
    };

    var mail_get_labels_values = function(thirdstate){
        var nodes = (thirdstate?mail_get_labels_thirdstate():mail_get_labels_checked());
        var values = [];
        Y.each(nodes, function (node, index) {
            values.push(mail_get_label_value(node));
        });
        return values.join();
    };

    var mail_assign_labels = function (node) {
        node = (typeof node !== 'undefined' ? node : false);
        var grouplabels;
        var elem;
        var labelid = 0;
        if (mail_message_view) {
            grouplabels = Y.one('.mail_group_labels');
        } else {
            grouplabels = node.ancestor('.mail_item').one('.mail_group_labels');
        }
        if (mail_view_type == 'label') {
            labelid = parseInt(Y.one('input[name="itemid"]').get('value'), 10);
        }
        var lblstoadd = mail_get_labels_values(false).split(',');
        var lblstoremain = mail_get_labels_values(true).split(',');

        Y.each(M.local_mail.mail_labels, function (value, index) {
            if (lblstoadd.indexOf(index) != -1) {
                if (index != labelid) {
                    elem = grouplabels.one('.mail_label_'+index);
                    if (!elem) {
                        elem = Y.Node.create('<span class="mail_label mail_label_'+M.local_mail.mail_labels[index].color+' mail_label_'+index+'">'+M.local_mail.mail_labels[index].name+'</span>');
                        grouplabels.append(elem);
                    }
                }
            } else if (lblstoremain.indexOf(index) == -1) {
                if (!mail_message_view && index == labelid) {
                    grouplabels.ancestor('.mail_item').remove();
                } else {
                    elem = grouplabels.one('.mail_label_'+index);
                    if (elem) {
                        elem.remove();
                    }
                }
            }
        });
    };

    var mail_check_selected = function() {
        mail_enable_all_buttons(Y.all('.mail_selected').size());
    };

    var mail_enable_button = function(button, bool) {
        bool = (typeof bool !== 'undefined' ? bool : false);
        if (bool) {
            button.removeClass('mail_button_disabled');
        } else if(!button.hasClass('mail_checkbox_all')){
            button.addClass('mail_button_disabled');
        }
    };

    var mail_enable_all_buttons = function(bool) {
        var mail_buttons = Y.all('.mail_toolbar .mail_buttons .mail_button');
        Y.each(mail_buttons, (function(button) {
            button.removeClass('mail_hidden');
            mail_enable_button(button, bool);
        }));
        if (mail_view_type == 'label') {
            mail_enable_button(Y.one('.mail_toolbar .mail_more_actions'), true);
        }
    };

    var mail_get_checkboxs_checked = function(){
        return Y.all('.mail_list .mail_checkbox1');
    };

    var mail_get_checkbox_value = function(checkbox){
        var value;
        classnames = checkbox.getAttribute('class').split(' ');
        Y.each(classnames, function(classname){
            num = /mail_checkbox_value_(\d+)/.exec(classname);
            if (num) {
                value = num[1];
            }
        });
        return value;
    };

    var mail_get_checkboxs_values = function(){
        var nodes = mail_get_checkboxs_checked();
        var values = [];
        Y.each(nodes, function (node, index) {
            values.push(mail_get_checkbox_value(node));
        });
        return values.join();
    };

    var mail_set_checkbox = function(node, value){
        if (value == 1) {
            node.removeClass('mail_checkbox0').removeClass('mail_checkbox2').addClass('mail_checkbox1');
        } else if (value == 2) {
            node.removeClass('mail_checkbox0').removeClass('mail_checkbox1').addClass('mail_checkbox2');
        } else {
            node.removeClass('mail_checkbox1').removeClass('mail_checkbox2').addClass('mail_checkbox0');
        }
    };

    var mail_toggle_checkbox = function(node){
        if (node.hasClass('mail_checkbox0')) {
            mail_set_checkbox(node, 1);
        } else if (node.hasClass('mail_checkbox1')) {
            mail_set_checkbox(node, 0);
        } else {
            mail_set_checkbox(node, 1);
        }
    };

    var mail_is_checkbox_checked = function(node){
        return node.hasClass('mail_checkbox1');
    };

    var mail_main_checkbox = function(){
        if(!Y.all('.mail_selected').size()) {
            mail_set_checkbox(Y.one('.mail_checkbox_all > .mail_adv_checkbox'), 0);
        } else if(Y.all('.mail_selected').size() == Y.all('.mail_item').size()) {
            mail_set_checkbox(Y.one('.mail_checkbox_all > .mail_adv_checkbox'), 1);
        } else {
            mail_set_checkbox(Y.one('.mail_checkbox_all > .mail_adv_checkbox'), 2);
        }
        mail_check_selected();
    };

    var mail_select_all = function(){
        var checkbox = Y.one('.mail_checkbox_all > .mail_adv_checkbox');
        mail_set_checkbox(checkbox, 1);
        var nodes = Y.all('.mail_list .mail_adv_checkbox');
        nodes.each(function(node) {
            mail_set_checkbox(node, 1);
            node.ancestor('.mail_item').addClass('mail_selected');
        });
    };

    var mail_select_none = function(){
        var checkbox = Y.one('.mail_checkbox_all > .mail_adv_checkbox');
        mail_set_checkbox(checkbox, 0);
        var nodes = Y.all('.mail_list .mail_adv_checkbox');
        nodes.each(function(node) {
            mail_set_checkbox(node, 0);
            node.ancestor('.mail_item').removeClass('mail_selected');
        });
    };

    var mail_select_read = function(){
        var nodes = Y.all('.mail_item > .mail_adv_checkbox');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (!ancestor.hasClass('mail_unread')){
                    mail_set_checkbox(node, 1);
                    ancestor.addClass('mail_selected');
                } else {
                    mail_set_checkbox(node, 0);
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    var mail_select_unread = function() {
        var nodes = Y.all('.mail_item > .mail_adv_checkbox');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (ancestor.hasClass('mail_unread')){
                    mail_set_checkbox(node, 1);
                    ancestor.addClass('mail_selected');
                } else {
                    mail_set_checkbox(node, 0);
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    var mail_select_starred = function() {
        var nodes = Y.all('.mail_item > .mail_adv_checkbox');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (ancestor.one('.mail_starred')) {
                    mail_set_checkbox(node, 1);
                    ancestor.addClass('mail_selected');
                } else {
                    mail_set_checkbox(node, 0);
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    var mail_select_unstarred = function() {
        var nodes = Y.all('.mail_item > .mail_adv_checkbox');
        var ancestor;
        if (nodes) {
            nodes.each(function(node) {
                ancestor = node.ancestor('.mail_item');
                if (ancestor.one('.mail_unstarred')) {
                    mail_set_checkbox(node, 1);
                    ancestor.addClass('mail_selected');
                } else {
                    mail_set_checkbox(node, 0);
                    ancestor.removeClass('mail_selected');
                }
            });
        }
    };

    //Success call
    var handleSuccess = function (transactionid, response, args) {
        var obj = Y.JSON.parse(response.responseText);
        var img;

        if (obj.msgerror) {
            alert(obj.msgerror);
        } else {
            if (obj.html) {
                Y.one('#local_mail_main_form').setContent(obj.html);
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
            if(obj.redirect) {
                document.location.href = obj.redirect;
            }
        }
    };

    //Failure call
    var handleFailure = function (transactionid, response, args) {
        console.log(response);
    };

    //Update screen data and async call
    var mail_doaction = function(action, node){
        node = (typeof node !== 'undefined' ? node : null);
        var nodes = mail_get_checkboxs_checked();
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
                    action = 'unstarred';
                    obj.replaceClass('mail_starred', 'mail_unstarred');
                    node.set('title', M.util.get_string('unstarred','local_mail'));
                } else {
                    action = 'starred';
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
            } else if(action == 'assignlabels') {
                mail_assign_labels();
            }
            mail_view = true;
            ids = Y.one('input[name="m"]').get('value');
        } else {//List messages view
            if(action == 'viewmail') {
                nodes.empty();
                var url = node.get('href');
                if (url.match(/compose\.php/)){
                    document.location.href = url;
                    return 0;
                } else {
                    ids = /m=(\d+)/.exec(node.get('href'))[1];
                }
            }else if (action == 'togglestarred') {
                obj = node.ancestor('.mail_item').one('.mail_adv_checkbox');
                nodes = Y.all(obj);
                if (node.one('span').hasClass('mail_starred')) {
                    action = 'unstarred';
                } else {
                    action = 'starred';
                }
                ids = mail_get_checkbox_value(obj);
            } else if(action == 'perpage'){
                nodes.empty();
                action = 'perpage';
            } else {
                ids = mail_get_checkboxs_values();
            }
            if (nodes.size()) {
                nodes.each(function (node) {
                    ancestor = node.ancestor('.mail_item');
                    if (action == 'starred') {
                        child = ancestor.one('.mail_unstarred');
                        if(child) {
                            child.replaceClass('mail_unstarred', 'mail_starred');
                            child.ancestor('a').set('title', M.util.get_string('starred','local_mail'));
                        }
                    } else if(action == 'unstarred') {
                        if (mail_view_type == 'starred') {
                            ancestor.remove();
                        } else {
                            child = ancestor.one('.mail_starred');
                            if(child) {
                                child.replaceClass('mail_starred', 'mail_unstarred');
                                child.ancestor('a').set('title', M.util.get_string('unstarred','local_mail'));
                            }
                        }
                    } else if(action == 'markasread') {
                        ancestor.removeClass('mail_unread');
                    } else if(action == 'markasunread') {
                        ancestor.addClass('mail_unread');
                    } else if(action == 'delete') {
                        ancestor.remove();
                    } else if(action == 'assignlabels') {
                        mail_assign_labels(node);
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
                type: mail_view_type,
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
            cfg.data.m = Y.one('input[name="m"]').get('value');
        }
        if(Y.one('input[name="itemid"]')) {
            cfg.data.itemid = Y.one('input[name="itemid"]').get('value');
        }
        if (action == 'perpage') {
            cfg.data.perpage = node.get('innerText');
        }
        if (action == 'assignlabels') {
            cfg.data.labelids = mail_get_labels_values(false);
            cfg.data.labeltsids = mail_get_labels_values(true);
        }
        if (action == 'setlabel') {
            obj = Y.one('#local_mail_edit_label_color');
            cfg.data.labelname = Y.one('#local_mail_edit_label_name').get('value');
            if (!cfg.data.labelname) {
                alert(M.util.get_string('erroremptylabelname', 'local_mail'));
                mail_label_edit();
                return false;
            }
            cfg.data.labelcolor = obj.get('options').item(obj.get('selectedIndex')).get('value');
        }
        if (action == 'newlabel') {
            obj = Y.one('#local_mail_new_label_color');
            cfg.data.labelname = Y.one('#local_mail_new_label_name').get('value');
            if (!cfg.data.labelname) {
                alert(M.util.get_string('erroremptylabelname', 'local_mail'));
                mail_label_new();
                return false;
            }
            cfg.data.labelcolor = obj.get('options').item(obj.get('selectedIndex')).get('value');
        }
        request = Y.io(M.cfg.wwwroot + '/local/mail/ajax.php', cfg);
    };

    var mail_label_confirm_delete = function(e) {
        var labelid;
        var message;
        labelid = Y.one('input[name="itemid"]').get('value');
        message = M.util.get_string('labeldeleteconfirm', 'local_mail', M.local_mail.mail_labels[labelid].name);
        M.util.show_confirm_dialog(e, {
                                        'callback' : mail_label_remove,
                                        'message' : message,
                                        'continuelabel': M.util.get_string('delete', 'local_mail')
                                    }
        );
    };

    var mail_label_remove = function() {
        var params = [];
        params.push('offset='+Y.one('input[name="offset"]').get('value'));
        params.push('sesskey='+Y.one('input[name="sesskey"]').get('value'));
        params.push('removelbl=1');
        params.push('confirmlbl=1');
        var url = Y.one('#local_mail_main_form').get('action');
        document.location.href = url+'&'+params.join('&');
    };

    var mail_label_new = function() {
        mail_new_label_panel.show();
        Y.one('#local_mail_form_new_label').removeClass('mail_hidden');
        Y.one('#local_mail_new_label_name').focus();
    };

    var mail_label_edit = function() {
        var labelid = Y.one('input[name="itemid"]').get('value');
        var labelname = M.local_mail.mail_labels[labelid].name;
        var labelcolor = M.local_mail.mail_labels[labelid].color;
        Y.one('#local_mail_edit_label_name').set('value', labelname);
        if (labelcolor != 'nocolor') {
            Y.one('#local_mail_edit_label_color option[value="'+labelcolor+'"]').set('selected', 'selected');
        }
        mail_edit_label_panel.show();
        Y.one('#local_mail_form_edit_label').removeClass('mail_hidden');
        Y.one('#local_mail_edit_label_name').focus();
    };

    var mail_update_url = function() {
        var params = [];
        var offset;
        var m;
        var type;

        if (history.pushState) {
            params.push('t='+mail_view_type);
            if (mail_message_view) {
                params.push('m='+Y.one('input[name="m"]').get('value'));
            }
            if (mail_view_type == 'course') {
                params.push('c='+Y.one('input[name="itemid"]').get('value'));
            } else {
                if (mail_view_type == 'label') {
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

    /*** Click events ***/

    //Background selection
    Y.one("div.region-content").delegate('click', function(e) {
        var ancestor = this.ancestor('.mail_item');
        mail_toggle_checkbox(this);
        ancestor.toggleClass('mail_selected');
        mail_main_checkbox();
        mail_customize_menu_actions(this);
    }, '.mail_list .mail_adv_checkbox');

    //Select all/none
    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_checkbox(this);
        mail_hide_menu_options();
        mail_hide_menu_labels();
        if (mail_is_checkbox_checked(this)) {
            mail_select_all();
        } else {
            mail_select_none();
        }
        mail_check_selected();
        mail_update_menu_actions();
    }, '.mail_checkbox_all > .mail_adv_checkbox');

    //Toggle menu select all/none
    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_menu();
        mail_hide_menu_actions();
        mail_hide_menu_labels();
    }, '.mail_checkbox_all');

    //Checkbox hides other menus
    Y.one("div.region-content").delegate('click', function(e) {
        mail_hide_menu_options();
        mail_hide_menu_labels();
    }, '.mail_checkbox_all > .mail_adv_checkbox');

    //Toggle menu actions
    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_menu_actions();
        mail_hide_menu_options();
        mail_hide_menu_labels();
    }, '.mail_more_actions');

    //Toggle menu actions
    Y.one("div.region-content").delegate('click', function(e) {
        //e.preventDefault();
        e.stopPropagation();
        mail_label_default_values();
        mail_customize_menu_label();
        mail_toggle_menu_labels();
        mail_hide_menu_options();
        mail_hide_menu_actions();
    }, '.mail_assignlbl');

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
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_read');

    //Menu select unread
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_unread();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_unread');

    //Menu select starred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_starred();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_starred');

    //Menu select unstarred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_unstarred();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_unstarred');

    Y.one("div.region-content").delegate('click', function(e) {
        mail_check_selected();
    }, '.mail_optselect');

    Y.one("div.region-content").delegate('click', function(e) {
        e.stopPropagation();
    }, '.mail_labelselect');

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
        mail_label_edit();
    }, '.mail_menu_action_editlabel');

    //Menu action removelabel
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_label_confirm_delete(e);
    }, '.mail_menu_action_removelabel');

    //Starred and unstarred
    Y.one('div.region-content').delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('togglestarred', this);
        mail_update_menu_actions();
    }, '.mail_flags a');

    //Delete button
    Y.one("div.region-content").delegate('click', function(e) {
        if (!this.hasClass('mail_button_disabled')) {
            mail_doaction('delete');
        }
    }, '.mail_delete');

    //Prev page button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('prevpage');
    }, 'input[name="prevpage"]');

    //Prev page button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('nextpage');
    }, 'input[name="nextpage"]');

    //Go back button
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('goback');
    }, '.mail_goback');

    //Mail per page
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('perpage', this);
    }, 'div.mail_perpage a');

    //Hide all menus
    Y.on('click', function(e) {
        mail_hide_menu_options();
        mail_hide_menu_actions();
        mail_hide_menu_labels();
    }, 'body');

    //Show message
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('viewmail', this);
    }, 'a.mail_link');

    //Click apply changes on labels
    Y.one("div.region-content").delegate('click', function(e) {
        mail_hide_menu_labels();
        mail_doaction('assignlabels');
    }, '.mail_menu_label_apply');

    //Click new label
    Y.one("div.region-content").delegate('click', function(e) {
        mail_hide_menu_labels();
        mail_label_new();
    }, '.mail_menu_label_newlabel');

    //Click label on menu labels
    Y.one("div.region-content").delegate('click', function(e) {
        mail_menu_label_selection(this);
        mail_customize_menu_label();
    }, '.mail_menu_labels li');

    //Initialize
    init();
    mail_update_url();
});