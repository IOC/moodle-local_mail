<?php

require_once('label.class.php');
require_once('message.class.php');

define('MAIL_PAGESIZE', 10);

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
    global $CFG;

    // Send the mail now!
    foreach ($message->recipients() as $userto) {

        $eventdata = new stdClass();
        $eventdata->component         = 'local_mail';
        $eventdata->name              = 'mail';
        $eventdata->userfrom          = $message->sender();
        $eventdata->userto            = $userto;
        $eventdata->subject           = $message->subject();
        $eventdata->fullmessage       = $message->content();
        $eventdata->fullmessageformat = $message->format();
        $eventdata->fullmessagehtml   = format_text($message->content(), $message->format());
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
