YUI(M.yui.loader).use('io-base', 'node', 'json-parse', function(Y) {

    var init = function(){
        Y.one('span.mail_checkbox_all').removeClass('mail_hidden');
        Y.one('span.mail_more_actions').removeClass('mail_hidden');
        mail_hide_noscript_buttons();
        mail_enable_all_buttons(false);
    };

    var mail_toggle_menu = (function(){
        var button = Y.one('.mail_checkbox_all');
        if (!button.hasClass('mail_button_disabled')) {
		  Y.one('.mail_optselect').toggleClass('mail_hidden');
        }
	});

    var mail_hide_menu_options = (function(){
        Y.one('.mail_optselect').addClass('mail_hidden');
    });

    var mail_hide_menu_actions = (function(){
        Y.one('.mail_actselect').addClass('mail_hidden');
    });

    var mail_hide_noscript_buttons = (function(){
        Y.all('.mail_toolbar .mail_noscript_button').addClass('mail_hidden');
    });

    var mail_toggle_menu_actions = (function(){
        var button = Y.one('.mail_more_actions');
        var menu = Y.one('.mail_actselect');
        var position = button.getXY();
        if (!button.hasClass('mail_button_disabled')) {
            position[1] += button.get('clientHeight') + 3;
            menu.toggleClass('mail_hidden');
            menu.setXY(position);
        }
    });

    var mail_check_selected = (function(){
        mail_enable_all_buttons(Y.all('.mail_selected').size());
    });

    var mail_enable_button = (function(name, bool) {
        bool = (typeof bool !== 'undefined' ? bool : false);
        if (bool) {
            Y.one('.mail_toolbar input[name='+name+']').set('disabled','');
        } else {
            Y.one('.mail_toolbar input[name='+name+']').set('disabled','disabled');
        }
    });

    var mail_enable_all_buttons = (function(bool) {
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
    });

    var mail_main_checkbox = (function(bool){
        if (bool){
            Y.one('.mail_checkbox_all > input').set('checked', 'checked');
        } else {
            if(!Y.all('.mail_selected').size()) {
                Y.one('.mail_checkbox_all > input').set('checked', '');
            }
        }
        mail_check_selected();
    });

	var mail_select_all = (function(){
		var checkbox = Y.one('.mail_checkbox_all > input');
		checkbox.set('checked', 'checked');
    	var nodes = Y.all('.mail_checkbox');
    	nodes.each(function(node) {
   			node.set('checked', 'checked');
    		node.ancestor('.mail_item').addClass('mail_selected');
    	});
	});

	var mail_select_none = (function(){
		var checkbox = Y.one('.mail_checkbox_all > input');
		checkbox.set('checked', '');
    	var nodes = Y.all('.mail_checkbox');
    	nodes.each(function(node) {
   			node.set('checked', '');
    		node.ancestor('.mail_item').removeClass('mail_selected');
    	});
	});

	var mail_select_read = (function(){
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
	});

	var mail_select_unread = (function(){
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
	});

	var mail_select_starred = (function(){
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
	});

	var mail_select_nostarred = (function(){
    	var nodes = Y.all('.mail_item > input');
    	var ancestor;
    	if (nodes) {
	    	nodes.each(function(node) {
	    		ancestor = node.ancestor('.mail_item');
	    		if (ancestor.one('.mail_nostarred')){
	    			node.set('checked', 'checked');
	    			ancestor.addClass('mail_selected');
	    		} else {
	    			node.set('checked', '');
	    			ancestor.removeClass('mail_selected');
	    		}
	    	});
	    }
	});

    //Success call
    var handleSuccess = function (transactionid, response, arguments) {
        var data = Y.JSON.parse(response.responseText);
        if (data && data !== 'error') {
            Y.one('div.region-content form').setContent(data);
            init();
        }
        //console.log(response);
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
            nodes.empty();
            nodes.push(obj);
            if (node.one('span').hasClass('mail_starred')) {
                action = 'nostarred'
            } else {
                action = 'starred'
            }
            ids = obj.get('value');
        } else if(action == 'perpage'){
            nodes.empty();
            action = 'perpage';
        }else {
            ids = Y.all('input[name="msgs[]"]:checked').get('value').join();
        }
        if (nodes.size()) {
            nodes.each(function (node) {
                ancestor = node.ancestor('.mail_item');
                if (action == 'starred') {
                    if(child = ancestor.one('.mail_nostarred')) {
                        child.replaceClass('mail_nostarred', 'mail_starred');
                        child.ancestor('a').set('title', M.util.get_string('starred','local_mail'));
                    }
                } else if (action == 'nostarred') {
                    if(child = ancestor.one('.mail_starred')) {
                        child.replaceClass('mail_starred', 'mail_nostarred');
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
    }, '.mail_menu_option_read');

    //Menu select unread
    Y.one("div.region-content").delegate('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_unread();
    }, '.mail_menu_option_unread');

    //Menu select starred
    Y.one("div.region-content").delegate('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_starred();
    }, '.mail_menu_option_starred');

    //Menu select nostarred
    Y.one("div.region-content").delegate('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_nostarred();
    }, '.mail_menu_option_unstarred');

    Y.one("div.region-content").delegate('click', function(e) {
        mail_check_selected();
    }, '.mail_optselect');

    //Menu action starred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('starred');
    }, '.mail_menu_action_markasstarred');

    //Menu action unstarred
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('nostarred');
    }, '.mail_menu_action_markasunstarred');

    //Menu action markasread
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('markasread');
    }, '.mail_menu_action_markasread');

    //Menu action markasunread
    Y.one("div.region-content").delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('markasunread');
    }, '.mail_menu_action_markasunread');

    //Starred and nostarred
    Y.one('div.region-content').delegate('click', function(e) {
        e.preventDefault();
        mail_doaction('togglestarred', this);
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