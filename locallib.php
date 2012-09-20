<?php

require_once('label.class.php');
require_once('message.class.php');

define('MAIL_PAGESIZE', 10);

function local_mail_view($type) {
    global $DB, $PAGE, $OUTPUT, $USER, $SITE;

    $itemid   = optional_param('m', 0, PARAM_INT); //Message ID
    $courseid = optional_param('c', 0, PARAM_INT); //Course ID
    $labelid  = optional_param('l', 0, PARAM_INT); //Label ID
    $delete   = optional_param('delete', false, PARAM_ALPHA);
    $forward  = optional_param('forward', false, PARAM_BOOL);
    $offset   = optional_param_array('offset', array(), PARAM_INT);
    $reply    = optional_param('reply', false, PARAM_BOOL);
    $replyall = optional_param('replyall', false, PARAM_BOOL);
    $starred  = optional_param('starred', false, PARAM_INT);
    $msgs     = optional_param_array('msgs', array(), PARAM_INT);
    $read     = optional_param('read', false, PARAM_ALPHA);
    $unread   = optional_param('unread', false, PARAM_ALPHA);
    $perpage  = optional_param('perpage', false, PARAM_INT);

    // Set up message
    if ($itemid) {
        // Fetch message

        $message = local_mail_message::fetch($itemid);
        if (!$message or !$message->viewable($USER->id)) {
            print_error('local_mail', 'invalidmessage');
        }

        $url = new moodle_url("/local/mail/view_$type.php");
        if (!$course = $DB->get_record('course', array('id' => $message->course()->id))) {
            print_error('invalidcourse', 'error');
        }

        local_mail_setup_page($course, $url);

        $message->set_unread($USER->id, false);

        // Remove message

        if ($delete) {
            require_sesskey();
            if ($message->editable($USER->id)) {
                $message->discard();
            } elseif ($message->viewable($USER->id)) {
                $message->set_deleted($USER->id, !$message->deleted($USER->id));
            }
            $url = new moodle_url("/local/mail/view_$type.php");
            redirect($url);
        }

        if ($starred) {
            require_sesskey();
            if ($message->id() === $starred) {
                $message->set_starred($USER->id, !$message->starred($USER->id));
            }
            $params = array('m' => $message->id());
            $url = new moodle_url("/local/mail/view_$type.php", $params);
            redirect($url);
        }

        // Reply message

        if ($reply || $replyall) {
            require_sesskey();
            $newreply = $message->reply($USER->id, $replyall);
            $params = array('m' => $newreply->id());
            $url = new moodle_url('/local/mail/compose.php', $params);
            redirect($url);
        }

        // Forward message

        if ($forward) {
            require_sesskey();
            $newmessage = $message->forward($USER->id);
            $params = array('m' => $newmessage->id());
            $url = new moodle_url('/local/mail/compose.php', $params);
            redirect($url);
        }

        // Unread
        if ($unread) {
            require_sesskey();
            $message->set_unread($USER->id, true);
            redirect($url);
        }

        $url->param('message', $message->id());
        echo $OUTPUT->header();
        echo html_writer::start_tag('form', array('method' => 'post', 'action' => $url));
        $mailoutput = $PAGE->get_renderer('local_mail');
        echo $mailoutput->toolbar('view');
        echo $OUTPUT->container_start('mail_view');

        echo $OUTPUT->container_start('mail_subject');
        $title = s($message->subject());
        echo $OUTPUT->heading($title, 3, '');
        echo $mailoutput->starred($message, $USER->id, $type, true);
        echo $OUTPUT->container_end();

        echo $mailoutput->mail($message);

        echo $OUTPUT->container_end();

        echo html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
        ));

        echo html_writer::end_tag('form');
        echo $OUTPUT->footer();
    } else {  // Set up messages
        if (empty($offset)) {
            $offset = 0;
        } else {
            $offset = key($offset);
        }
        $mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);
        $totalcount = local_mail_message::count_index($USER->id, $type, $courseid);
        $messages = local_mail_message::fetch_index($USER->id, $type, $courseid, $offset, $mailpagesize);

        // Display page

        if (!$courseid){
            $courseid = $SITE->id;
        }
        $url = new moodle_url("/local/mail/view_$type.php");
        if ($type == 'course' or $type == 'label') {
            $url->param('c', $courseid);
        }

        if (!$course = $DB->get_record('course', array('id' => $courseid))) {
            print_error('invalidcourse', 'error');
        }

        local_mail_setup_page($course, $url);

        // Remove
        if ($delete) {
            require_sesskey();
            foreach ($messages as $message) {
                if (in_array($message->id(), $msgs)) {
                    if ($message->editable($USER->id)) {
                        $message->discard();
                    } elseif ($message->viewable($USER->id)) {
                        $message->set_deleted($USER->id, !$message->deleted($USER->id));
                    }
                    $totalcount -= 1;
                }
            }
            if ($offset > $totalcount-1) {
                $url->offset = min(0, $offset-$mailpagesize);
            } else {
                $url->offset = $offset;
            }
            redirect($url);
        }

        // Starred
        if ($starred) {
            require_sesskey();
            $message = local_mail_message::fetch($starred);
            if (!$message or !$message->viewable($USER->id)) {
                print_error('local_mail', 'invalidmessage');
            }
            $message->set_starred($USER->id, !$message->starred($USER->id));
            redirect($url);
        }

        // Read or Unread
        if ($read || $unread) {
            require_sesskey();
            foreach ($messages as $message) {
                if (in_array($message->id(), $msgs)) {
                    $message->set_unread($USER->id, $unread);
                }
            }
            redirect($url);
        }

        // Perpage
        if ($perpage) {
            require_sesskey();
            if (in_array($perpage, array (5, 10, 20, 50, 100))) {
                set_user_preference('local_mail_mailsperpage', $perpage);
            }
            redirect($url);
        }

        // Display page
        $mailoutput = $PAGE->get_renderer('local_mail');
        echo $OUTPUT->header();
        echo $mailoutput->view(array(
            'type' => $type,
            'itemid' => $courseid,
            'userid' => $USER->id,
            'messages' => $messages,
            'totalcount' => $totalcount,
            'offset' => $offset
        ));
        echo $OUTPUT->footer();
    }
}

function local_mail_setup_page($course, $url) {
    global $DB, $PAGE;

    require_login($course->id, false);

    $PAGE->set_url($url);
    $title = get_string('mymail', 'local_mail');
    $PAGE->set_title($course->shortname . ': ' . $title);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_heading($course->fullname);
    $PAGE->requires->css('/local/mail/styles.css');

    if ($course->id != SITEID) {
        $PAGE->navbar->add(get_string('mymail', 'local_mail'));
        $urlcompose = new moodle_url('/local/mail/compose.php');
        $urlrecipients = new moodle_url('/local/mail/recipients.php');
        if ($url->compare($urlcompose, URL_MATCH_BASE) or
            $url->compare($urlrecipients, URL_MATCH_BASE)) {
            $text = get_string('compose', 'local_mail');
            $urlcompose->param('id', $url->param('id'));
            $PAGE->navbar->add($text, $urlcompose);
        }
    }
}

function local_mail_send_notifications($message) {
    global $CFG;

    // Send the mail now!
    foreach ($message->recipients() as $userto) {

        $eventdata = new stdClass();
        $eventdata->component         = 'local_mail';
        $eventdata->name              = 'mail';
        $eventdata->userfrom          = $message->sender();
        $eventdata->userto            = $userto;
        $eventdata->subject           = $message->subject();
        $eventdata->fullmessage       = $message->content();
        $eventdata->fullmessageformat = $message->format();
        $eventdata->fullmessagehtml   = format_text($message->content(), $message->format());
        $eventdata->notification      = 1;

        $smallmessagestrings = new stdClass();
        $smallmessagestrings->user = fullname($message->sender());
        $smallmessagestrings->message = $message->subject();
        $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'local_mail', $smallmessagestrings);

        $eventdata->contexturl = $CFG->wwwroot . '/local/mail/view_inbox.php?m='.$message->id();
        $eventdata->contexturlname = $message->subject();

        $mailresult = message_send($eventdata);
        if (!$mailresult) {
            mtrace("Error: local/mail/locallib.php local_mail_send_mail(): Could not send out mail for id {$message->id()} to user {$message->sender()->id}".
                    " ($userto->email) .. not trying again.");
            add_to_log($message->course()->id, 'local_mail', 'mail error', "view_inbox.php?m={$message->id()}",
                    substr(format_string($message->subject(),true),0,30), 0, $message->sender()->id);
        }
    }
}
