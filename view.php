<?php

require_once('../../config.php');
require_once('locallib.php');

$messageid = required_param('id', PARAM_INT);
$delete = optional_param('delete', false, PARAM_ALPHA);
$reply = optional_param('reply', false, PARAM_BOOL);
$replyall = optional_param('replyall', false, PARAM_BOOL);
$forward = optional_param('forward', false, PARAM_BOOL);
$type = optional_param('type', false, PARAM_ALPHA);
$starred = optional_param('starred', false, PARAM_ALPHA);

// Fetch message

$message = local_mail_message::fetch($messageid);
if (!$message or !$message->viewable($USER->id)) {
    print_error('local_mail', 'invalidmessage');
}

// Set up page

$params = array('id' => $message->id());
$url = new moodle_url('/local/mail/view.php', $params);
local_mail_setup_page($message->course(), $url);

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
    $url = new moodle_url('/local/mail/view_inbox.php');
    redirect($url);
}

if ($starred) {
    $msg = optional_param('msg', false, PARAM_INT);
    if ($message->id() === $msg){
        $message->set_starred($USER->id, !$message->starred($USER->id));
    }
    $params = array(
            'id' => $message->id(),
            'type' => $type);
    $url = new moodle_url('/local/mail/view.php', $params);
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

echo $OUTPUT->header();
echo html_writer::start_tag('form', array('method' => 'post', 'action' => $url));
$mailoutput = $PAGE->get_renderer('local_mail');
echo $mailoutput->toolbar('view');
echo $OUTPUT->container_start('mail_view');

echo $OUTPUT->container_start('mail_subject');
$title = s($message->subject());
echo $OUTPUT->heading($title, 3, '');
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
