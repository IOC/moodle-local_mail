<?php

// Local mail plugin for Moodle
// Copyright © 2012,2013 Institut Obert de Catalunya
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// Ths program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

require_once($CFG->libdir . '/filelib.php');
require_once('label.class.php');
require_once('message.class.php');
require_once($CFG->dirroot.'/group/lib.php');

define('MAIL_PAGESIZE', 10);
define('LOCAL_MAIL_MAXFILES', 5);
define('LOCAL_MAIL_MAXBYTES', 1048576);

function local_mail_attachments($message) {
    $context = context_course::instance($message->course()->id);
    $fs = get_file_storage();
    return $fs->get_area_files($context->id, 'local_mail', 'message',
                               $message->id(), 'filename', false);
}

function local_mail_format_content($message) {
    $context = context_course::instance($message->course()->id);
    $content = file_rewrite_pluginfile_urls($message->content(), 'pluginfile.php', $context->id,
                                            'local_mail', 'message', $message->id());
    return format_text($content, $message->format());
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
            $urlcompose->param('m', $url->param('m'));
            $PAGE->navbar->add($text, $urlcompose);
        }
    }
}

function local_mail_send_notifications($message) {
    global $SITE;

    $plaindata = new stdClass;
    $htmldata = new stdClass;

    // Send the mail now!
    foreach ($message->recipients() as $userto) {

        $plaindata->user = fullname($message->sender());
        $plaindata->subject = $message->subject();

        $htmldata->user = fullname($message->sender());
        $htmldata->subject = $message->subject();
        $url = new moodle_url('/local/mail/view.php', array('t' => 'inbox', 'm' => $message->id()));
        $htmldata->url = $url->out(false);

        $eventdata = new stdClass();
        $eventdata->component         = 'local_mail';
        $eventdata->name              = 'mail';
        $eventdata->userfrom          = $message->sender();
        $eventdata->userto            = $userto;
        $eventdata->subject           = get_string('notificationsubject', 'local_mail', $SITE->shortname);
        $eventdata->fullmessage       = get_string('notificationbody', 'local_mail', $plaindata);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = get_string('notificationbodyhtml', 'local_mail', $htmldata);
        $eventdata->notification      = 1;

        $smallmessagestrings = new stdClass();
        $smallmessagestrings->user = fullname($message->sender());
        $smallmessagestrings->message = $message->subject();
        $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'local_mail', $smallmessagestrings);

        $url = new moodle_url('/local/mail/view.php', array('t' => 'inbox', 'm' => $message->id()));
        $eventdata->contexturl = $url->out(false);
        $eventdata->contexturlname = $message->subject();

        $mailresult = message_send($eventdata);
        if (!$mailresult) {
            mtrace("Error: local/mail/locallib.php local_mail_send_mail(): Could not send out mail for id {$message->id()} to user {$message->sender()->id}".
                    " ($userto->email) .. not trying again.");
            add_to_log($message->course()->id, 'local_mail', 'mail error', "view_inbox.php?m={$message->id()}",
                    substr(format_string($message->subject(),true),0,30), 0, $message->sender()->id);
        }
    }
}

function local_mail_js_labels() {
    global $USER;

    $labels = local_mail_label::fetch_user($USER->id);
    $js = 'M.local_mail = {mail_labels: {';
    $cont = 0;
    $total = count($labels);
    foreach ($labels as $label) {
        $js .= '"'.$label->id().'":{"id": "' . $label->id() . '", "name": "' . s($label->name()) . '", "color": "' . $label->color() . '"}';
        $cont++;
        if ($cont < $total) {
            $js .= ',';
        }
    }
    $js .=  '}};';
    return $js;
}

function local_mail_get_my_courses() {
    static $courses = null;

    if ($courses === null) {
        $courses = enrol_get_my_courses();
    }
    return $courses;
}

function local_mail_valid_recipient($recipient) {
    global $COURSE, $USER;

    if (!$recipient or $recipient == $USER->id) {
        return false;
    }

    $context = context_course::instance($COURSE->id);

    if (!is_enrolled($context, $recipient)) {
        return false;
    }

    if ($COURSE->groupmode == SEPARATEGROUPS and
        !has_capability('moodle/site:accessallgroups', $context)) {
        $ugroups = groups_get_all_groups($COURSE->id, $USER->id,
                                         $COURSE->defaultgroupingid, 'g.id');
        $rgroups = groups_get_all_groups($COURSE->id, $recipient,
                                         $COURSE->defaultgroupingid, 'g.id');
        if (!array_intersect(array_keys($ugroups), array_keys($rgroups))) {
            return false;
        }
    }

    return true;
}
