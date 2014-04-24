<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local-mail
 * @copyright  Albert Gasset <albert.gasset@gmail.com>
 * @copyright  Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/mail/locallib.php');

function local_mail_cron() {
    $settings = get_config('local_mail');

    if (empty($settings->cronenabled)) {
        return;
    }
    mtrace('mailupdater: local_mail_cron() started at '. date('H:i:s'));

    if (local_mail_update_process($settings)) {
        set_config('cronenabled', false, 'local_mail');
    }

    mtrace('mailupdater: local_mail_cron() finished at ' . date('H:i:s'));
}

function local_mail_update_process($settings) {
    global $DB;

    $hour = (int) date('H');
    if ($hour < $settings->cronstart || $hour >= $settings->cronstop) {
        mtrace('mailupdater: not between starthour and stophour, so doing nothing (hour = ' .
                $hour . ').');
        return false;
    }

    // Setup the stop time.
    if ($settings->cronduration) {
        $stoptime = time() + $settings->cronduration;
    } else {
        $stoptime = false;
    }

    $countrecords = $DB->count_records('local_mail_messages');

    $limitfrom = 0;
    $limitnum = 1000;
    $countrecords;

    $inserts = 0;
    $fs = get_file_storage();
    $count = 0;
    $starttime = time();
    while ((!$stoptime || (time() < $stoptime)) && $count < $countrecords) {

        $recordset = $DB->get_recordset('local_mail_messages', array(), '',     '*', $limitfrom, $limitnum);
        try {
            $transaction = $DB->start_delegated_transaction();
            foreach ($recordset as $record) {
                if (!$DB->get_records('local_mail_index', array('messageid' => $record->id, 'type' => 'attachment'))) {
                    $indexrecord = new stdClass;
                    $userid = $DB->get_field('local_mail_message_users', 'userid', array('messageid' => $record->id, 'role' => 'from'));
                    $indexrecord->userid = $userid;
                    $indexrecord->type = 'attachment';
                    $indexrecord->time = $record->time;
                    $indexrecord->messageid = $record->id;
                    $unread = $DB->get_field('local_mail_message_users', 'unread', array('messageid' => $record->id, 'role' => 'from'));
                    $indexrecord->unread = $unread;

                    $context = context_course::instance($record->courseid);

                    if ($fs->is_area_empty($context->id, 'local_mail', 'message', $record->id, 'filename', false)) {
                        $indexrecord->item = 0;
                    } else {
                        $indexrecord->item = 1;
                    }
                    $DB->insert_record('local_mail_index', $indexrecord);
                    $inserts++;
                }
            }
            $recordset->close();
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        $count += 1000;
        $limitfrom += $limitnum;
    }

    return true;
}

function local_mail_course_deleted($course) {
    $fs = get_file_storage();
    $fs->delete_area_files($course->context->id, 'local_mail');
    local_mail_message::delete_course($course->id);
}

function local_mail_extends_navigation($root) {
    global $CFG, $COURSE, $PAGE, $SESSION, $SITE, $USER;

    if (!get_config('local_mail', 'version')) {
        return;
    }

    $courses = local_mail_get_my_courses();

    $count = local_mail_message::count_menu($USER->id);

    // My mail

    $text = get_string('mymail', 'local_mail');
    if (!empty($count->inbox)) {
        $text .= ' (' . $count->inbox . ')';
    }
    $node = navigation_node::create($text, null, navigation_node::TYPE_ROOTNODE);
    if (!empty($count->inbox)) {
        $node->add_class('local_mail_new_messages');
    }
    $child = $root->add_node($node, 'mycourses');
    $child->add_class('mail_root');

    // Compose

    $text = get_string('compose', 'local_mail');
    $url = new moodle_url('/local/mail/compose.php');
    $urlrecipients = new moodle_url('/local/mail/recipients.php');

    if ($PAGE->url->compare($url, URL_MATCH_BASE) or
        $PAGE->url->compare($urlrecipients, URL_MATCH_BASE)) {
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
    $child = $node->add(s($text), $url);
    $child->add_class('mail_inbox');

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
    $child = $node->add(s($text), $url);
    $child->add_class('mail_drafts');

    // Sent

    $text = get_string('sentmail', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'sent'));
    $node->add(s($text), $url);

    // Courses

    if ($courses) {
        $text = get_string('courses', 'local_mail');
        $nodecourses = $node->add($text, null, navigation_node::TYPE_CONTAINER);
        foreach ($courses as $course) {
            $text = $course->shortname;
            if (!empty($count->courses[$course->id])) {
                $text .= ' (' . $count->courses[$course->id] . ')';
            }
            $params = array('t' => 'course', 'c' => $course->id);
            $url = new moodle_url('/local/mail/view.php', $params);
            $child = $nodecourses->add(s($text), $url);
            $child->hidden = !$course->visible;
            $child->add_class('mail_course_'.$course->id);
        }
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
            $child = $nodelabels->add(s($text), $url);
            $child->add_class('mail_label_'.$label->id());
        }
    }

    // Trash

    $text = get_string('trash', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'trash'));
    $node->add(s($text), $url);

    // User profile

    if (empty($CFG->messaging) and
        $PAGE->url->compare(new moodle_url('/user/view.php'), URL_MATCH_BASE)) {
        $userid = optional_param('id', false, PARAM_INT);
        if (local_mail_valid_recipient($userid)) {
            $vars = array('course' => $COURSE->id, 'recipient' => $userid);
            $PAGE->requires->string_for_js('sendmessage', 'local_mail');
            $PAGE->requires->js_init_code('M.local_mail = ' . json_encode($vars));
            $PAGE->requires->js('/local/mail/user.js');
        }
    }

    // Users list

    if (empty($CFG->messaging) and
        $PAGE->url->compare(new moodle_url('/user/index.php'), URL_MATCH_BASE)) {
        $userid = optional_param('id', false, PARAM_INT);
        $vars = array('course' => $COURSE->id);
        $PAGE->requires->string_for_js('choosedots', 'moodle');
        $PAGE->requires->strings_for_js(array(
                'bulkmessage',
                'to',
                'cc',
                'bcc',
                ), 'local_mail');
        $PAGE->requires->js_init_code('M.local_mail = ' . json_encode($vars));
        $PAGE->requires->js('/local/mail/users.js');
    }
}

function local_mail_pluginfile($course, $cm, $context, $filearea, $args,
                               $forcedownload, array $options=array()) {
    global $SITE, $USER;

    require_login($SITE, false);

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
