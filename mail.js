YUI(M.yui.loader, {lang: M.local_mail_lang}).use('io-base', 'node', 'json-parse', 'panel', 'datatable-base', 'dd-plugin', 'moodle-form-dateselector', 'datatype-date', 'calendar-base', function(Y) {

    var mail_message_view = false;
    var mail_checkbox_labels_default = {};
    var mail_view_type = '';
    var mail_edit_label_panel;
    var mail_new_label_panel;
    var mail_undo_function = '';
    var mail_undo_ids = '';
    var mail_search_selected = '';
    var mail_searchfrom_selected = '';
    var mail_searchto_selected = '';
    var mail_unread_selected = false;
    var mail_attach_selected = false;
    var mail_date_selected = '';
    var mail_doing_search = false;
    var mail_after_message_search = false;
    var mail_before_message_search = false;
    var mail_perpageid = 0;

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
        mail_define_label_handlers();
    };

    var mail_define_label_handlers = function () {
        if (Y.one('#local_mail_form_new_label')) {
            //Click on new label color div
            Y.one('#local_mail_form_new_label').delegate('click', function(e) {
                e.stopPropagation();
                mail_label_set_selected(this, 'new');
            }, '.mail_label_color');
        }

        if (Y.one('#local_mail_form_edit_label')) {
            //Click on edit label color div
            Y.one('#local_mail_form_edit_label').delegate('click', function(e) {
                e.stopPropagation();
                mail_label_set_selected(this, 'edit');
            }, '.mail_label_color');
        }
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
        var menu = Y.one('.mail_optselect');
        var position = button.getXY();
        if (!button.hasClass('mail_button_disabled')) {
            position[1] += button.get('clientHeight') + 2;
            menu.toggleClass('mail_hidden');
            menu.setXY(position);
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

    var mail_hide_menu_search = function() {
        var menu = Y.one('#mail_menu_search');
        if (menu) {
            menu.addClass('mail_hidden');
        }
        if (M.form.dateselector.panel) {
            M.form.dateselector.panel.hide();
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

    var mail_toggle_menu_search = function() {
        var button = Y.one('#mail_search');
        var menu = Y.one('#mail_menu_search');
        var advsearch = Y.one('#mail_adv_search');
        var position = button.getXY();
        var date;
        position[1] += button.get('clientHeight') + 2;
        menu.toggleClass('mail_hidden');
        menu.setXY(position);
        if (!menu.hasClass('mail_hidden')) {
            Y.one('#textsearch').focus();
            if (!advsearch.hasClass('mail_hidden')) {
                mail_position_datepicker();
            }
            if (mail_doing_search) {
                Y.one('#buttoncancelsearch').removeClass('mail_hidden');
            } else {
                Y.one('#buttoncancelsearch').addClass('mail_hidden');
            }
        } else {
            M.form.dateselector.panel.hide();
        }
    };

    var mail_toggle_adv_search = function() {
        var menu = Y.one('#mail_adv_search');
        var status = Y.one('#mail_adv_status');
        menu.toggleClass('mail_hidden');
        if (menu.hasClass('mail_hidden')) {
            M.form.dateselector.panel.hide();
            status.set('src', M.util.image_url('t/collapsed', 'moodle'));
            status.set('alt', 'collapsed');
        } else {
            mail_position_datepicker();
            status.set('src', M.util.image_url('t/expanded', 'moodle'));
            status.set('alt' ,'expanded');
        }
    };

    var mail_do_search = function() {
        mail_doing_search = true;
        mail_perpageid = 0;
        mail_search_selected = Y.one('#textsearch').get('value');
        mail_searchfrom_selected = Y.one('#textsearchfrom').get('value');
        mail_searchto_selected = Y.one('#textsearchto').get('value');
        mail_unread_selected = Y.one('#searchunread').get('checked');
        mail_attach_selected = Y.one('#searchattach').get('checked');
        mail_select_none();
        mail_check_selected();
        Y.all('.mail_paging input').set('disabled', 'disabled');
        mail_show_loading_image();
        mail_doaction('search');
        mail_hide_menu_search();
    };

    var mail_show_loading_image = function() {
        Y.one('.mail_list').addClass('mail_hidden');
        Y.one('.mail_search_loading').removeClass('mail_hidden');
    };

    var mail_update_form_search = function() {
        Y.one('#textsearch').set('value', mail_search_selected);
        Y.one('#textsearchfrom').set('value', mail_searchfrom_selected);
        Y.one('#textsearchto').set('value', mail_searchto_selected);
        if (mail_unread_selected) {
            Y.one('#searchunread').set('checked', 'checked');
        }
        if (mail_attach_selected) {
            Y.one('#searchattach').set('checked', 'checked');
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
        var labels = Y.all('.mail_menu_labels .mail_adv_checkbox');

        if (!mail_message_view) {
            var total = mail_get_checkboxs_checked().size();
            Y.each(labels, function(label, index) {
                classname = label.getAttribute('class');
                num = /mail_label_value_(\d+)/.exec(classname);
                if (num) {
                    labelid = num[1];
                    if (mail_checkbox_labels_default[labelid] == total) {
                        isdefault = isdefault && label.hasClass('mail_checkbox1');
                    } else if(mail_checkbox_labels_default[labelid] > 0) {
                        isdefault = isdefault && label.hasClass('mail_checkbox2');
                    } else {
                        isdefault = isdefault && label.hasClass('mail_checkbox0');
                    }
                }
            });
        } else {
            Y.each(labels, function(label, index) {
                classname = label.getAttribute('class');
                num = /mail_label_value_(\d+)/.exec(classname);
                if (num) {
                    labelid = num[1];
                    if (mail_checkbox_labels_default[labelid] == 1) {
                        isdefault = isdefault && label.hasClass('mail_checkbox1');
                    } else {
                        isdefault = isdefault && label.hasClass('mail_checkbox0');
                    }
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
            if (Y.Array.indexOf(lblstoadd, index) != -1) {
                if (index != labelid) {
                    elem = grouplabels.one('.mail_label_'+index);
                    if (!elem) {
                        elem = Y.Node.create('<span class="mail_label mail_label_'+M.local_mail.mail_labels[index].color+' mail_label_'+index+'">'+M.local_mail.mail_labels[index].name+'</span>');
                        grouplabels.append(elem);
                    }
                }
            } else if (Y.Array.indexOf(lblstoremain, index) == -1) {
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
        if (Y.one('#mail_search')) {
            mail_enable_button(Y.one('#mail_search'), true);
        }
        if (Y.one('#buttonsearch')) {
            mail_enable_button(Y.one('#buttonsearch'), true);
        }
        if (Y.one('#buttoncancelsearch')) {
            mail_enable_button(Y.one('#buttoncancelsearch'), true);
        }
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
        } else {
            mail_set_checkbox(node, 0);
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
        var node;

        if (obj.msgerror) {
            alert(obj.msgerror);
        } else {
            if (obj.html) {
                Y.one('#local_mail_main_form').setContent(obj.html);
                init();
                mail_update_url();
            }
            if (obj.search) {
                mail_perpageid = obj.search.perpageid;
                mail_doing_search = true;
                Y.one('#mail_search').addClass('mail_button_searching');
                Y.one('.mail_paging input[name="prevpage"]').set('disabled', 'disabled');
                Y.one('.mail_paging input[name="nextpage"]').set('disabled', 'disabled');
                Y.one('.mail_paging > span').addClass('mail_hidden');
                mail_search_selected = obj.search.query;
                mail_searchfrom_selected = obj.search.searchfrom;
                mail_searchto_selected = obj.search.searchto;
                mail_unread_selected = obj.search.unread;
                mail_attach_selected = obj.search.attach;
                mail_date_selected = obj.search.date;
                mail_update_form_search();
                if (obj.search.prev) {
                    Y.one('.mail_paging input[name="prevpage"]').set('disabled', '');
                }
                if (obj.search.next) {
                    Y.one('.mail_paging input[name="nextpage"]').set('disabled', '');
                }
                if (!mail_message_view) {
                    mail_before_message_search = obj.search.idbefore;
                    mail_after_message_search = obj.search.idafter;
                }
            }
            if (obj.info) {
                if (obj.info.root) {
                    node = Y.one('.mail_root span');
                    node.setContent(obj.info.root);
                    if(obj.info.root.match(/\(\d+\)/)) {
                        Y.one('.mail_root').addClass('local_mail_new_messages');
                    } else {
                        Y.one('.mail_root').removeClass('local_mail_new_messages');
                    }
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
            //Undo last action
            if (obj.undo && mail_undo_function != 'undo') {
                var msg = M.util.get_string('undo'+mail_undo_function, 'local_mail', obj.undo.split(',').length);
                if (mail_undo_function == 'delete') {
                    mail_undo_function = 'restore';
                } else if (mail_undo_function == 'restore') {
                    mail_undo_function = 'delete';
                }
                mail_notification_message(msg);
                mail_undo_ids = obj.undo;
            } else {
                mail_undo_function = '';
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
    var mail_doaction = function(action, node) {
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
            } else if (action == 'delete' || action == 'restore') {
                mail_undo_function = action;
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
            } else if (action == 'delete') {
                mail_undo_function = action;
                ids = mail_get_checkboxs_values();
            } else if (action == 'restore') {
                mail_undo_function = action;
                ids = mail_get_checkboxs_values();
            } else if (action == 'discard') {
                ids = mail_get_checkboxs_values();
            } else if (action == 'undo') {
                nodes.empty();
                action = mail_undo_function;
                mail_undo_function = 'undo';
                ids = mail_undo_ids;
            } else if (action == 'togglestarred') {
                obj = node.ancestor('.mail_item').one('.mail_adv_checkbox');
                nodes = Y.all(obj);
                if (node.one('span').hasClass('mail_starred')) {
                    action = 'unstarred';
                } else {
                    action = 'starred';
                }
                ids = mail_get_checkbox_value(obj);
            } else if(action == 'perpage' || action == 'search'){
                nodes.empty();
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
                    } else if(action == 'delete' || action == 'restore' || action == 'discard') {
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
            cfg.data.perpage = (node.get('innerText')?node.get('innerText'):node.get('textContent'));
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
            } else if (cfg.data.labelname.length > 100) {
                alert(M.util.get_string('maximumchars', 'moodle', 100));
                mail_label_edit();
                return false;
            }
            cfg.data.labelcolor = obj.get('value');
        }
        if (action == 'newlabel') {
            obj = Y.one('#local_mail_new_label_color');
            cfg.data.labelname = Y.one('#local_mail_new_label_name').get('value');
            if (!cfg.data.labelname) {
                alert(M.util.get_string('erroremptylabelname', 'local_mail'));
                mail_label_new();
                return false;
            } else if (cfg.data.labelname.length > 100) {
                alert(M.util.get_string('maximumchars', 'moodle', 100));
                mail_label_new();
                return false;
            }
            cfg.data.labelcolor = obj.get('value');
        }
        if (action == 'nextpage' || action == 'prevpage' ) {
            obj = Y.one('#mail_loading_small');
            var btn = Y.one('.mail_paging input[name="'+action+'"]');
            var position = btn.getXY();
            obj.removeClass('mail_hidden');
            position[0] += (btn.get('offsetWidth')/2) - (obj.one('img').get('offsetWidth')/2);
            position[1] = btn.getXY()[1] + (obj.one('img').get('offsetHeight')/2);
            obj.setXY(position);
        }
        if (mail_doing_search) {
            //Go back when searching keeps current page
            if (action == 'goback') {
                if (mail_before_message_search) {
                    cfg.data.before = mail_before_message_search;
                } else if (mail_after_message_search) {
                    cfg.data.after = mail_after_message_search;
                }
            }
            cfg.data.search =  mail_search_selected;
            cfg.data.searchfrom = mail_searchfrom_selected;
            cfg.data.searchto = mail_searchto_selected;
            cfg.data.unread = (mail_unread_selected?'1':'');
            cfg.data.attach = (mail_attach_selected?'1':'');
            cfg.data.time = mail_date_selected;
            cfg.data.perpageid = mail_perpageid;
            if (action == 'prevpage') {
                obj = Y.one('.mail_list .mail_item .mail_adv_checkbox');
                if (obj) {
                    cfg.data.after = mail_get_checkbox_value(obj);
                }
                cfg.data.action = 'search';
            } else if (action == 'nextpage') {
                obj = Y.all('.mail_item:last-child .mail_adv_checkbox');
                if (obj) {
                    cfg.data.before = mail_get_checkbox_value(obj.shift());
                    cfg.data.perpageid = cfg.data.before;
                }
                cfg.data.action = 'search';
            }
            cfg.data.searching = true;
        }
        if (mail_undo_function == 'undo') {
            cfg.data.undo = true;
        }
        request = Y.io(M.cfg.wwwroot + '/local/mail/ajax.php', cfg);
    };

    var mail_label_confirm_delete = function(e) {
        var labelid;
        var message;
        var labelname = '';
        labelid = Y.one('input[name="itemid"]').get('value');
        labelname = M.local_mail.mail_labels[labelid].name;
        if (labelname.length > 25) {
            labelname = labelname.substring(0, 25) + '...';
        }
        message = M.util.get_string('labeldeleteconfirm', 'local_mail', labelname);
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
        Y.all('.mail_label_color').removeClass('mail_label_color_selected');
        if (!labelcolor) {
            Y.one('.mail_label_color.mail_label_nocolor').addClass('mail_label_color_selected');
            labelcolor = '';
        } else {
            Y.one('.mail_label_color.mail_label_' + labelcolor).addClass('mail_label_color_selected');
        }
        Y.one('#local_mail_edit_label_color').set('value', labelcolor);
        mail_edit_label_panel.show();
        Y.one('#local_mail_form_edit_label').removeClass('mail_hidden');
        Y.one('#local_mail_edit_label_name').focus();
    };

    var mail_label_set_selected = function(obj, action) {
        Y.all('.mail_label_color').removeClass('mail_label_color_selected');
        obj.addClass('mail_label_color_selected');
        Y.one('#local_mail_' + action + '_label_color').set('value', obj.getAttribute('data-color'));
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

    var mail_position_datepicker = function() {
        var menu = Y.one('#mail_menu_search');
        var datepicker = Y.one('#dateselector-calendar-panel');
        var search = Y.one('.mail_search_datepicker');
        var position = menu.getXY();
        position[0] += (menu.get('offsetWidth')/2) - (datepicker.get('offsetWidth')/2);
        position[1] = search.getXY()[1] - datepicker.get('offsetHeight');
        datepicker.setXY(position);
    };

    var mail_get_selected_date = function(cell, date) {
        mail_date_selected = cell.date.getFullYear() + ',' + cell.date.getMonth() + ',' + cell.date.getDate();
        mail_set_selected_date(mail_date_selected);
        M.form.dateselector.panel.hide();
    };

    var mail_set_selected_date = function(date) {
        if (date) {
            var elems = date.split(',');
            date = Y.Date.format(new Date(elems[0], elems[1], elems[2]), {format:"%x"})
        } else {
            date = Y.Date.format(new Date(), {format:"%x"})
        }
        Y.one('#searchdate').set('text', date);
    };

    var mail_notification_message = function(message) {
        if (message) {
            Y.one('#mail_notification').addClass('mail_enabled').removeClass('mail_novisible');
            Y.one('#mail_notification_message').setContent(message);
            Y.one('#mail_notification_undo').removeClass('mail_novisible');
        } else {
            Y.one('#mail_notification').removeClass('mail_enabled').addClass('mail_novisible');
            Y.one('#mail_notification_message').setContent('');
            Y.one('#mail_notification_undo').addClass('mail_novisible');
        }
    };

    var mail_reset_date_selected = function() {
        date = new Date();
        mail_date_selected = date.getFullYear() + ',' + date.getMonth() + ',' + date.getDate();
        M.form.dateselector.calendar.deselectDates(date);
    };

    /*** Event listeners***/

    //Background selection
    Y.one("#region-main").delegate('click', function(e) {
        var ancestor = this.ancestor('.mail_item');
        mail_toggle_checkbox(this);
        ancestor.toggleClass('mail_selected');
        mail_main_checkbox();
        mail_customize_menu_actions(this);
    }, '.mail_list .mail_adv_checkbox');

    //Select all/none
    Y.one("#region-main").delegate('click', function(e) {
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
    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_menu();
        mail_hide_menu_actions();
        mail_hide_menu_labels();
        mail_hide_menu_search();
    }, '.mail_checkbox_all');

    //Checkbox hides other menus
    Y.one("#region-main").delegate('click', function(e) {
        mail_hide_menu_options();
        mail_hide_menu_labels();
    }, '.mail_checkbox_all > .mail_adv_checkbox');

    //Toggle menu actions
    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_menu_actions();
        mail_hide_menu_options();
        mail_hide_menu_labels();
        mail_hide_menu_search();
    }, '.mail_more_actions');

    //Toggle menu actions
    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
        mail_label_default_values();
        mail_customize_menu_label();
        mail_toggle_menu_labels();
        mail_hide_menu_options();
        mail_hide_menu_actions();
        mail_hide_menu_search();
    }, '.mail_assignlbl');

    //Menu select all
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_all();
        mail_update_menu_actions();
    }, '.mail_menu_option_all');

    //Menu select none
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_none();
    }, '.mail_menu_option_none');

    //Menu select read
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_read();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_read');

    //Menu select unread
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_unread();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_unread');

    //Menu select starred
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_starred();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_starred');

    //Menu select unstarred
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_toggle_menu();
        mail_select_unstarred();
        mail_main_checkbox();
        mail_update_menu_actions();
    }, '.mail_menu_option_unstarred');

    Y.one("#region-main").delegate('click', function(e) {
        mail_check_selected();
    }, '.mail_optselect');

    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
    }, '.mail_labelselect');

    //Menu action starred
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('starred');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasstarred');

    //Menu action unstarred
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('unstarred');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasunstarred');

    //Menu action markasread
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('markasread');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasread');

    //Menu action markasunread
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('markasunread');
        mail_update_menu_actions();
    }, '.mail_menu_action_markasunread');

    //Menu action editlabel
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_label_edit();
    }, '.mail_menu_action_editlabel');

    //Menu action removelabel
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_label_confirm_delete(e);
    }, '.mail_menu_action_removelabel');

    //Starred and unstarred
    Y.one('#region-main').delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('togglestarred', this);
        mail_update_menu_actions();
    }, '.mail_flags a');

    //Delete button
    Y.one("#region-main").delegate('click', function(e) {
        if (!this.hasClass('mail_button_disabled')) {
            mail_doaction('delete');
        }
    }, '#mail_delete');

    //Discard button
    Y.one("#region-main").delegate('click', function(e) {
        if (!this.hasClass('mail_button_disabled')) {
            mail_doaction('discard');
        }
    }, '#mail_discard');

    //Restore button
    Y.one("#region-main").delegate('click', function(e) {
        if (!this.hasClass('mail_button_disabled')) {
            mail_doaction('restore');
        }
    }, '#mail_restore');

    //Prev page button
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('prevpage');
    }, 'input[name="prevpage"]');

    //Prev page button
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('nextpage');
    }, 'input[name="nextpage"]');

    //Go back button
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('goback');
    }, '.mail_goback');

    //Mail per page
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('perpage', this);
    }, 'div.mail_perpage a');

    //Hide all menus
    Y.on('click', function(e) {
        mail_hide_menu_options();
        mail_hide_menu_actions();
        mail_hide_menu_labels();
        mail_hide_menu_search();
    }, 'body');

    //Show message
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('viewmail', this);
    }, 'a.mail_link');

    //Click apply changes on labels
    Y.one("#region-main").delegate('click', function(e) {
        mail_hide_menu_labels();
        mail_doaction('assignlabels');
    }, '.mail_menu_label_apply');

    //Click new label
    Y.one("#region-main").delegate('click', function(e) {
        mail_hide_menu_labels();
        mail_label_new();
    }, '.mail_menu_label_newlabel');

    //Click label on menu labels
    Y.one("#region-main").delegate('click', function(e) {
        mail_menu_label_selection(this);
        mail_customize_menu_label();
    }, '.mail_menu_labels li');

    //Click notification bar undo
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        var ancestor = Y.one('#mail_notification');
        if (ancestor.hasClass('mail_enabled')) {
            ancestor.removeClass('mail_enabled').addClass('mail_novisible');
            mail_doaction('undo');
        }
    }, '#mail_notification_undo');

    //Click cancel search button
    Y.one("#region-main").delegate('click', function(e) {
        e.preventDefault();
        mail_doing_search = false;
        mail_hide_menu_search();
        mail_doaction('goback');
        mail_before_message_search = false;
        mail_after_message_search = false;
        mail_reset_date_selected();
    }, '#buttoncancelsearch');


    //Click search button
    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
        var date;
        mail_hide_menu_options();
        mail_hide_menu_actions();
        mail_hide_menu_labels();
        mail_toggle_menu_search();
        if (!mail_date_selected) {
            mail_reset_date_selected();
        }
        mail_set_selected_date(mail_date_selected);
    }, '#mail_search');

    //Click menu search
    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
        M.form.dateselector.panel.hide();
    }, '#mail_menu_search');

    //Click adv search
    Y.one("#region-main").delegate('click', function(e) {
        e.stopPropagation();
        mail_toggle_adv_search();
    }, '#mail_toggle_adv_search');

    //Click date search
    Y.one("#local_mail_main_form").delegate('click', function(e) {
        e.stopPropagation();
        if(Y.one('#dateselector-calendar-panel').hasClass('yui3-overlay-hidden')) {
            M.form.dateselector.panel.show();
        } else {
            M.form.dateselector.panel.hide();
        }
    }, '.mail_search_date');

    Y.on('contentready', function() {
        if (M.form.dateselector.calendar) {
            M.form.dateselector.calendar.on('dateClick', mail_get_selected_date);
            M.form.dateselector.calendar.set('maximumDate', new Date());
            M.form.dateselector.panel.set('zIndex', 1);
            Y.one('#dateselector-calendar-panel').setStyle('border', 0);
            M.form.dateselector.calendar.render();
        }
    }, '#dateselector-calendar-panel');

    //Click on button search
    Y.one("#region-main").delegate('keydown', function(e) {
        e.stopPropagation();
        if (e.keyCode == 13) {
            this.focus();
            mail_do_search();
        }
    }, '#textsearch, #textsearchfrom, #textsearchto');

    //Click on button search
    Y.one("#region-main").delegate('click', function(e) {
        mail_do_search();
    }, '#buttonsearch');

    //Initialize
    init();
    mail_update_url();
});
