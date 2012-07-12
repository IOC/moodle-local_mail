<?php

require_once('../../config.php');
require_once('locallib.php');
require_once('compose_form.php');

$messageid = optional_param('id', false, PARAM_INT);
$courseid = optional_param('course', SITEID, PARAM_INT);
$remove = optional_param_array('remove', false, PARAM_INT);

// Fetch message

if ($messageid) {
    $message = local_mail_message::fetch($messageid);
    if (!$message or !$message->editable($USER->id)) {
        print_error('local_mail', 'invalidmessage');
    }
    $course = $message->course();
    $courseid = $course->id;
} else {
    $message = false;
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourse', 'error');
    }
}

// Set up page

$url = new moodle_url('/local/mail/compose.php');
if ($message) {
    $url->param('id', $message->id());
} else {
    $url->param('course', $course->id);
}
local_mail_setup_page($course, $url);

// Remove recipients

if ($remove and $message) {
    require_sesskey();
    $message->remove_recipient(key($remove));
}

// Set up form

$data = array();
$customdata = array();
$customdata['courses'] = enrol_get_my_courses(array('id', 'shortname, fullname'));
$customdata['message'] = $message;
$mform = new mail_compose_form($url, $customdata);

if ($message) {
    $data['course'] = $message->course()->id;
    $data['subject'] = $message->subject();
    $data['content']['format'] = $message->format();
    $data['content']['text'] = $message->content();
} else {
    $data['content']['format'] = editors_get_preferred_format();
}

$mform->set_data($data);

// Process form

if ($data = $mform->get_data()) {

    if (!empty($data->course)) {
        $courseid = $data->course;
    }

    // Discard message
    if (!empty($data->discard)) {
        if ($message) {
            $message->discard();
        }
        if ($courseid) {
            $url = new moodle_url('/local/mail/view_course.php');
            $url->param('id', $courseid);
        } else {
            $url = new moodle_url('/local/mail/view_drafts.php');
        }
        redirect($url);
    }

    if ($message) {
        $message->save($courseid, trim($data->subject),
                       $data->content['text'], $data->content['format']);
    } else {
        $message = local_mail_message::create($USER->id, $courseid,
                                              trim($data->subject),
                                              $data->content['text'],
                                              $data->content['format']);
    }

    // Select recipients
    if (!empty($data->recipients)) {
        $params = array('id' => $message->id());
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
        $url->param('id', $courseid);
        redirect($url);
    }
}

// Display page

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
