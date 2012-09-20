<?php

require_once('../../config.php');
require_once('locallib.php');
require_once('compose_form.php');

$messageid = required_param('m', PARAM_INT);
$remove = optional_param_array('remove', false, PARAM_INT);

// Fetch message

$message = local_mail_message::fetch($messageid);
if (!$message or !$message->editable($USER->id)) {
    print_error('local_mail', 'invalidmessage');
}

// Fetch references

$refs = $message->references();
if (!empty($refs)) {
    $references = local_mail_message::fetch_many($refs);
}

// Set up page

$url = new moodle_url('/local/mail/compose.php');
$url->param('m', $message->id());
local_mail_setup_page($message->course(), $url);

// Remove recipients

if ($remove) {
    require_sesskey();
    $message->remove_recipient(key($remove));
}

// Set up form

$data = array();
$customdata = array();
$customdata['message'] = $message;
$mform = new mail_compose_form($url, $customdata);

$mform = new mail_compose_form($url, $customdata);

$format = $message->format() >= 0 ? $message->format() : editors_get_preferred_format();

$data['course'] = $message->course()->id;
$data['subject'] = $message->subject();
$data['content']['format'] = $format;
$data['content']['text'] = $message->content();
$mform->set_data($data);

// Process form

if ($data = $mform->get_data()) {

    // Discard message
    if (!empty($data->discard)) {
        $message->discard();
        $url = new moodle_url('/local/mail/view_course.php');
        $url->param('c', $message->course()->id);
        redirect($url);
    }

    $message->save(trim($data->subject), $data->content['text'], $data->content['format']);

    // Select recipients
    if (!empty($data->recipients)) {
        $params = array('m' => $message->id());
        $url = new moodle_url('/local/mail/recipients.php', $params);
        redirect($url);
    }

    // Save message
    if (!empty($data->save)) {
        $params = array('type' => 'drafts');
        $url = new moodle_url('/local/mail/view_drafts.php');
        redirect($url);
    }

    // Send message
    if (!empty($data->send)) {
        $message->send();
        $url = new moodle_url('/local/mail/view_course.php');
        $url->param('c', $message->course()->id);
        local_mail_send_notifications($message);
        redirect($url);
    }
}

// Display page

echo $OUTPUT->header();
$mform->display();
$mailoutput = $PAGE->get_renderer('local_mail');
if (!empty($refs)) {
    echo $OUTPUT->container_start('mail_reply');
    echo html_writer::tag('h2', get_string('references', 'local_mail'));
    foreach ($references as $ref) {
        echo $mailoutput->mail($ref, true);
    }
    echo $OUTPUT->container_end();
}
echo $OUTPUT->footer();
