<?php

require_once($CFG->libdir . '/formslib.php');

class mail_compose_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        $message = $this->_customdata['message'];

        // Header

        $label = get_string('compose', 'local_mail');
        $mform->addElement('header', 'general', $label);

        // Course

        $label = get_string('course');
        $text = $message->course()->fullname;
        $mform->addElement('static', 'coursefullname', $label, $text);

        // Recipients

        if ($message and $message->recipients('to')) {
            $text = $this->format_recipients($message->recipients('to'));
            $label = get_string('to', 'local_mail');
            $mform->addElement('static', 'to', $label, $text);
        }

        if ($message and $message->recipients('cc')) {
            $text = $this->format_recipients($message->recipients('cc'));
            $label = get_string('cc', 'local_mail');
            $mform->addElement('static', 'cc', $label, $text);
        }

        if ($message and $message->recipients('bcc')) {
            $text = $this->format_recipients($message->recipients('bcc'));
            $label = get_string('bcc', 'local_mail');
            $mform->addElement('static', 'bcc', $label, $text);
        }

        $label = get_string('addrecipients', 'local_mail');
        $mform->addElement('submit', 'recipients', $label);

        // Subject

        $label = get_string('subject', 'local_mail');
        $mform->addElement('text', 'subject', $label, 'size="48"');
        $text = get_string('maximumchars', '', 255);
        $mform->addRule('subject', $text, 'maxlength', 255, 'client');

        // Content

        $label = get_string('message', 'local_mail');
        $mform->addElement('editor', 'content', $label);
        $mform->setType('content', PARAM_RAW);

        // Buttons

        $buttonarray = array();

        $label = get_string('send', 'local_mail');
        $buttonarray[] = $mform->createElement('submit', 'send', $label);

        $label = get_string('save', 'local_mail');
        $buttonarray[] = $mform->createElement('submit', 'save', $label);

        $label = get_string('discard', 'local_mail');
        $buttonarray[] = $mform->createElement('submit', 'discard', $label);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function validation($data, $files) {
        $message = $this->_customdata['message'];

        $errors = array();

        // Course selected
        if (isset($data['course']) and $data['course'] == SITEID) {
            $errors['course'] = get_string('erroremptycourse', 'local_mail');
        }

        // Empty subject?
        if (!empty($data['send']) and !trim($data['subject'])) {
            $errors['subject'] = get_string('erroremptysubject', 'local_mail');
        }

        // At least one "to" recipient
        if (!empty($data['send']) and (!$message or !$message->recipients('to'))) {
            $errors['recipients'] = get_string('erroremptyrecipients', 'local_mail');
        }

        return $errors;
    }

    private function format_recipients($users) {
        global $OUTPUT;

        $message = $this->_customdata['message'];

        $content = '';
        
        foreach ($users as $user) { 
            $content .= html_writer::start_tag('div', array('class' => 'mail_recipient'));
            $options = array('courseid' => $message->course(),
                             'link' => false, 'alttext' => false);
            $content .= $OUTPUT->user_picture($user, $options);
            $content .= html_writer::tag('span', s(fullname($user)));
            $attributes = array('type' => 'image',
                                'name' => "remove[{$user->id}]",
                                'src' => $OUTPUT->pix_url('t/delete'),
                                'alt' => get_string('remove'));
            $content .= html_writer::tag('input', '', $attributes);
            $content .= html_writer::end_tag('div');
        }

        return $content;
    }
}
