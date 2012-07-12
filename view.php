<?php

require_once('../../config.php');
require_once('locallib.php');

$messageid = required_param('id', PARAM_INT);

// Fetch message

$message = local_mail_message::fetch($messageid);
if (!$message or !$message->viewable($USER->id)) {
    print_error('local_mail', 'invalidmessage');
}

// Set up page

$params = array('id' => $message->id());
$url = new moodle_url('/local/mail/view.php', $params);
local_mail_setup_page($message->course(), $url);

// Display page

echo $OUTPUT->header();
echo $OUTPUT->container_start();

echo $OUTPUT->container_start('mail_subject');
$title = s($message->subject());
echo $OUTPUT->heading($title, 3);
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('mail_from');
echo $OUTPUT->user_picture($message->sender());
echo html_writer::tag('span', fullname($message->sender()));
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('mail_content');
echo format_text($message->content(), $message->format());
echo $OUTPUT->container_end();

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
