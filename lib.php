<?php

require_once($CFG->dirroot . '/local/mail/locallib.php');

function local_mail_course_deleted($course) {
    local_mail_message::delete_course($course->id);
}

function local_mail_extends_navigation($root) {
    global $COURSE, $PAGE, $SESSION, $SITE, $USER;

    if (!get_config('local_mail', 'version')) {
        return;
    }

    if (!$courses = enrol_get_my_courses()) {
        return;
    }

    $count = local_mail_message::count_menu($USER->id);

    // My mail

    $text = get_string('mymail', 'local_mail');
    $node = navigation_node::create($text, null, navigation_node::TYPE_ROOTNODE);
    $root->add_node($node, 'courses');

    // Compose

    $text = get_string('compose', 'local_mail');
    $url = new moodle_url('/local/mail/compose.php');
    $url_recipients = new moodle_url('/local/mail/recipients.php');

    if ($PAGE->url->compare($url, URL_MATCH_BASE) or
        $PAGE->url->compare($url_recipients, URL_MATCH_BASE)) {
        $url->param('m', $PAGE->url->param('m'));
    } else {
        $url = new moodle_url('/local/mail/create.php');
        if ($COURSE->id != $SITE->id) {
            $url->param('c', $COURSE->id);
            $url->param('sesskey', sesskey());
        }
    }

    $node->add(s($text), $url);

    // Inbox

    $text = get_string('inbox', 'local_mail');
    if (!empty($count->inbox)) {
        $text .= ' (' . $count->inbox . ')';
    }
    $url = new moodle_url('/local/mail/view_inbox.php');
    $node->add(s($text), $url);

    // Starred

    $text = get_string('starred', 'local_mail');
    $url = new moodle_url('/local/mail/view_starred.php');
    $node->add(s($text), $url);

    // Drafts

    $text = get_string('drafts', 'local_mail');
    if (!empty($count->drafts)) {
        $text .= ' (' . $count->drafts . ')';
    }
    $url = new moodle_url('/local/mail/view_drafts.php');
    $node->add(s($text), $url);

    // Sent

    $text = get_string('sent', 'local_mail');
    $url = new moodle_url('/local/mail/view_sent.php');
    $node->add(s($text), $url);

    // Courses

    $text = get_string('courses', 'local_mail');
    $nodecourses = $node->add($text, null, navigation_node::TYPE_CONTAINER);
    foreach ($courses as $course) {
        $text = $course->shortname;
        if (!empty($count->courses[$course->id])) {
            $text .= ' (' . $count->courses[$course->id] . ')';
        }
        $url = new moodle_url('/local/mail/view_course.php', array('c' => $course->id));
        $nodecourses->add(s($text), $url);
    }

    // Labels

    $labels = local_mail_label::fetch_user($USER->id);
    if ($labels) {
        $text = get_string('labels', 'local_mail');
        $nodelabels = $node->add($text, null, navigation_node::TYPE_CONTAINER);
        foreach ($labels as $label) {
            $text = $label->name();
            if (!empty($count->labels[$label->id()])) {
                $text .= ' (' . $count->labels[$label->id()] . ')';
            }
            $url = new moodle_url('/local/mail/view_label.php', array('l' => $label->id()));
            $nodelabels->add(s($text), $url);
        }
    }

    // Trash

    $text = get_string('trash', 'local_mail');
    $url = new moodle_url('/local/mail/view_trash.php');
    $node->add(s($text), $url);
}

