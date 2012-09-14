<?php

require_once('label.class.php');
require_once('message.class.php');

define('MAIL_PAGESIZE', 20);

function local_mail_view($type) {
    global $DB, $PAGE, $OUTPUT, $USER;

    $itemid = optional_param('id', 0, PARAM_INT);
    $offset = optional_param('offset', 0, PARAM_INT);

    // Set up page

    $courseid = ($type == 'course' ? $itemid : SITEID);
    $url = new moodle_url("/local/mail/view_$type.php");
    if ($type == 'course' or $type == 'label') {
        $url->param('id', $itemid);
    }
 
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourse', 'error');
    }
   
    local_mail_setup_page($course, $url);
 
    // Set up messages

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    $messages = local_mail_message::fetch_index($USER->id, $type, $itemid, $offset, MAIL_PAGESIZE);

    // Display page

    echo $OUTPUT->header();
    $mailoutput = $PAGE->get_renderer('local_mail');
    echo $mailoutput->view(array(
        'type' => $type,
        'itemid' => $itemid,
        'userid' => $USER->id,
        'messages' => $messages,
        'totalcount' => $totalcount,
        'offset' => $offset,
    ));
    echo $OUTPUT->footer();
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
