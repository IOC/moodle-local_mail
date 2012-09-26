<?php

require_once('../../config.php');
require_once('locallib.php');

$type      = required_param('t', PARAM_ALPHA);
$messageid = optional_param('m', 0, PARAM_INT);
$courseid  = optional_param('c', 0, PARAM_INT);
$labelid   = optional_param('l', 0, PARAM_INT);
$delete    = optional_param('delete', false, PARAM_ALPHA);
$forward   = optional_param('forward', false, PARAM_BOOL);
$offset    = optional_param_array('offset', array(), PARAM_INT);
$reply     = optional_param('reply', false, PARAM_BOOL);
$replyall  = optional_param('replyall', false, PARAM_BOOL);
$starred   = optional_param('starred', false, PARAM_INT);
$msgs      = optional_param_array('msgs', array(), PARAM_INT);
$read      = optional_param('read', false, PARAM_ALPHA);
$unread    = optional_param('unread', false, PARAM_ALPHA);
$perpage   = optional_param('perpage', false, PARAM_INT);

$url = new moodle_url('/local/mail/view.php', array('t' => $type));
$type == 'course' and $url->param('c', $courseid);
$type == 'label' and $url->param('l', $labelid);

if ($messageid) {
    // Fetch message

    $message = local_mail_message::fetch($messageid);
    if (!$message or !$message->viewable($USER->id)) {
        print_error('local_mail', 'invalidmessage');
    }

    if (!$course = $DB->get_record('course', array('id' => $message->course()->id))) {
        print_error('invalidcourse', 'error');
    }

    local_mail_setup_page($course, new moodle_url($url, array('m' => $messageid)));
    navigation_node::override_active_url($url);

    $message->set_unread($USER->id, false);

    // Remove message

    if ($delete) {
        require_sesskey();
        if ($message->editable($USER->id)) {
            $message->discard();
        } elseif ($message->viewable($USER->id)) {
            $message->set_deleted($USER->id, !$message->deleted($USER->id));
        }
        redirect($url);
    }

    if ($starred) {
        require_sesskey();
        if ($message->id() === $starred) {
            $message->set_starred($USER->id, !$message->starred($USER->id));
        }
        $params = array('m' => $message->id());
        redirect($url);
    }

    // Reply message

    if ($reply || $replyall) {
        require_sesskey();
        $newreply = $message->reply($USER->id, $replyall);
        $url = new moodle_url('/local/mail/compose.php', array('m' => $newreply->id()));
        redirect($url);
    }

    // Forward message

    if ($forward) {
        require_sesskey();
        $newmessage = $message->forward($USER->id);
        $url = new moodle_url('/local/mail/compose.php', array('m' => $newmessage->id()));
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

} else {

    // Set up messages

    if (empty($offset)) {
        $offset = 0;
    } else {
        $offset = key($offset);
    }
    $mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);
    $totalcount = local_mail_message::count_index($USER->id, $type, $courseid);
    $messages = local_mail_message::fetch_index($USER->id, $type, $courseid, $offset, $mailpagesize);

    // Display page

    $courseid = $courseid ?: $SITE->id;

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
