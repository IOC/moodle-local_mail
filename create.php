<?php

require_once('../../config.php');
require_once('locallib.php');
require_once('create_form.php');

$courseid = optional_param('course', $SITE->id, PARAM_INT);

// Setup page

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'error');
}
$url = new moodle_url('/local/mail/create.php');
local_mail_setup_page($course, $url);

// Create message

if ($course->id != $SITE->id) {
    confirm_sesskey();
    $message = local_mail_message::create($USER->id, $course->id);
    $params = array('id' => $message->id());
    $url = new moodle_url('/local/mail/compose.php', $params);
    redirect($url);
}

// Setup form

$courses = enrol_get_my_courses(array('id', 'shortname, fullname'));
$customdata = array('courses' => $courses);
$mform = new local_mail_create_form($url, $customdata);
$mform->get_data();

// Display page

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
