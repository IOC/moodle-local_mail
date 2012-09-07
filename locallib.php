<?php

require_once('label.class.php');
require_once('message.class.php');

define('MAIL_PAGESIZE', 20);

function local_mail_view($type) {
    global $DB, $PAGE, $OUTPUT, $USER, $SITE;

    $itemid = optional_param('message', 0, PARAM_INT);
    $courseid = optional_param('id', 0, PARAM_INT);
    $delete = optional_param('delete', false, PARAM_ALPHA);
    $forward = optional_param('forward', false, PARAM_BOOL);
    $offset = optional_param('offset', 0, PARAM_INT);
    $reply = optional_param('reply', false, PARAM_BOOL);
    $replyall = optional_param('replyall', false, PARAM_BOOL);
    $starred = optional_param('starred', false, PARAM_INT);

    // Set up page

    $id = ($type == 'id' ? $courseid : $SITE->id);
    $url = new moodle_url("/local/mail/view_$type.php");
    if ($type == 'course' or $type == 'label') {
        $url->param('id', $courseid);
    }

    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourse', 'error');
    }

    local_mail_setup_page($course, $url);

    // Set up message
    if ($itemid) {
        // Fetch message

        $message = local_mail_message::fetch($itemid);
        if (!$message or !$message->viewable($USER->id)) {
            print_error('local_mail', 'invalidmessage');
        }

        $message->set_unread($USER->id, false);

        // Remove message

        if ($delete) {
            require_sesskey();
            $msg = optional_param('msg', false, PARAM_INT);
            if ($message->id() === $msg){
                if ($message->editable($USER->id)) {
                    $message->discard();
                } elseif ($message->viewable($USER->id)) {
                    $message->set_deleted($USER->id, !$message->deleted($USER->id));
                }
            }
            $url = new moodle_url("/local/mail/view_$type.php");
            redirect($url);
        }

        if ($starred) {
            if ($message->id() === $starred){
                $message->set_starred($USER->id, !$message->starred($USER->id));
            }
            $params = array('message' => $message->id());
            $url = new moodle_url("/local/mail/view_$type.php", $params);
            redirect($url);
        }

        // Reply message

        if ($reply || $replyall) {
            $newreply = $message->reply($USER->id, $replyall);
            $params = array('id' => $newreply->id());
            $url = new moodle_url('/local/mail/compose.php', $params);
            redirect($url);
        }

        // Forward message

        if ($forward) {
            $newmessage = $message->forward($USER->id);
            $params = array('id' => $newmessage->id());
            $url = new moodle_url('/local/mail/compose.php', $params);
            redirect($url);
        }

        // Display page
        $offset = 0;
        $totalcount = 1;
        if ($type){
            $messages = $message->fetch_index($USER->id, $type);
            $position = 0;
            foreach ($messages as $id => $m) {
                if ($id === $message->id()){
                    break;
                }
                $position += 1;
            }
            $offset = $position;
            $totalcount = count($messages);
        }

        $url->param('message', $message->id());
        echo $OUTPUT->header();
        echo html_writer::start_tag('form', array('method' => 'post', 'action' => $url));
        $mailoutput = $PAGE->get_renderer('local_mail');
        echo $mailoutput->toolbar($type);
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
        $totalcount = local_mail_message::count_index($USER->id, $type, $courseid);
        $messages = local_mail_message::fetch_index($USER->id, $type, $courseid, $offset, MAIL_PAGESIZE);
        // Remove
        if ($delete) {
            require_sesskey();
            $msg = optional_param_array('msg', array(), PARAM_INT);
            foreach ($messages as $message) {
                if (in_array($message->id(), $msg)){
                    if ($message->editable($USER->id)) {
                        $message->discard();
                    } elseif ($message->viewable($USER->id)) {
                        $message->set_deleted($USER->id, !$message->deleted($USER->id));
                    }
                    $totalcount -= 1;
                }
            }
            if ($offset > $totalcount-1) {
                $url->offset = min(0, $offset-MAIL_PAGESIZE);
            } else {
                $url->offset = $offset;
            }
            redirect($url);
        }

        // Starred
        if ($starred) {
            require_sesskey();
            $message = local_mail_message::fetch($starred);
            $message->set_starred($USER->id, !$message->starred($USER->id));
            redirect($url);
        }

        // Display page

        echo $OUTPUT->header();
        $mailoutput = $PAGE->get_renderer('local_mail');
        echo $mailoutput->view(array(
            'type' => $type,
            'itemid' => $courseid,
            'userid' => $USER->id,
            'messages' => $messages,
            'totalcount' => $totalcount,
            'offset' => $offset,
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
