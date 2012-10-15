YUI(M.yui.loader).use('node', function(Y) {

    var mail_buttons = ['assignlbl', 'read', 'unread', 'delete'];

	var mail_toggle_menu = (function(){
		Y.one('.mail_optselect').toggleClass('mail_hidden');
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
        Y.each(mail_buttons, (function(value){
            mail_enable_button(value, bool);
        }));
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

    //Background selection
    Y.on('click', function(e) {
    	var node = this.ancestor('.mail_item');
    	node.toggleClass('mail_selected');
        mail_main_checkbox(node.hasClass('mail_selected'));
    }, 'input.mail_checkbox');

    //Select all/none
    Y.on('click', function(e) {
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
    Y.on('click', function(e) {
   		mail_toggle_menu();
    }, '.mail_checkbox_all');

    //Menu select all
    Y.on('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_all();
    }, '.mail_menu_option_all');

    //Menu select none
    Y.on('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_none();
    }, '.mail_menu_option_none');

    //Menu select read
    Y.on('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_read();
    }, '.mail_menu_option_read');

    //Menu select unread
    Y.on('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_unread();
    }, '.mail_menu_option_unread');

    //Menu select starred
    Y.on('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_starred();
    }, '.mail_menu_option_starred');

    //Menu select nostarred
    Y.on('click', function(e) {
    	e.preventDefault();
    	mail_toggle_menu();
   		mail_select_nostarred();
    }, '.mail_menu_option_unstarred');

    Y.on('click', function(e) {
        mail_check_selected();
    }, '.mail_optselect');


    //Show all hidden elements
    Y.one('span.mail_checkbox_all').removeClass('mail_hidden');
    mail_enable_all_buttons(false);
});