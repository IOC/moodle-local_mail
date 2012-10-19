<?php

require_once($CFG->dirroot . '/local/mail/locallib.php');

function local_mail_course_deleted($course) {
    $context = context_course::instance($course->id);
    $fs->delete_area_files($course->context->id, 'local_mail');
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
    if (!empty($count->inbox)) {
        $text .= ' (' . $count->inbox . ')';
    }
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
    $url = new moodle_url('/local/mail/view.php', array('t' => 'inbox'));
    $alink = new action_link($url, html_writer::tag('span', s($text), array('id' => 'mail_inbox')));
    $node->add('', $alink);

    // Starred

    $text = get_string('starredmail', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'starred'));
    $node->add(s($text), $url);

    // Drafts

    $text = get_string('drafts', 'local_mail');
    if (!empty($count->drafts)) {
        $text .= ' (' . $count->drafts . ')';
    }
    $url = new moodle_url('/local/mail/view.php', array('t' => 'drafts'));
    $alink = new action_link($url, html_writer::tag('span', s($text), array('id' => 'mail_drafts')));
    $node->add('', $alink);

    // Sent

    $text = get_string('sentmail', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'sent'));
    $node->add(s($text), $url);

    // Courses

    $text = get_string('courses', 'local_mail');
    $nodecourses = $node->add($text, null, navigation_node::TYPE_CONTAINER);
    foreach ($courses as $course) {
        $text = $course->shortname;
        if (!empty($count->courses[$course->id])) {
            $text .= ' (' . $count->courses[$course->id] . ')';
        }
        $params = array('t' => 'course', 'c' => $course->id);
        $url = new moodle_url('/local/mail/view.php', $params);
        $alink = new action_link($url, html_writer::tag('span', s($text), array('id' => 'mail_course_'.$course->id)));
        $nodecourses->add('', $alink);
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
            $params = array('t' => 'label', 'l' => $label->id());
            $url = new moodle_url('/local/mail/view.php', $params);
            $alink = new action_link($url, html_writer::tag('span', s($text), array('id' => 'mail_label_'.$label->id())));
            $nodelabels->add('', $alink);
        }
    }

    // Trash

    $text = get_string('trash', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'trash'));
    $node->add(s($text), $url);
}

function local_mail_pluginfile($course, $cm, $context, $filearea, $args,
                               $forcedownload, array $options=array()) {
    global $USER;

    // Check course

    require_login($course, true);
    if ($context->contextlevel != CONTEXT_COURSE or $context->instanceid != $course->id) {
        return false;
    }

    // Check message

    $messageid = (int) array_shift($args);
    $message = local_mail_message::fetch($messageid);
    if ($filearea != 'message' or !$message or !$message->viewable($USER->id, true)) {
        return false;
    }

    // Fetch file info

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_mail/$filearea/$messageid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true, $options);
}
