<?php

require_once($CFG->libdir . '/formslib.php');

class local_mail_create_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'];

        // Header

        $label = get_string('compose', 'local_mail');
        $mform->addElement('header', 'general', $label);

        // Course

        $label = get_string('course');
        $options = array(SITEID => '');
        foreach ($courses as $course) {
            $options[$course->id] = $course->fullname;
        }
        $mform->addElement('select', 'c', $label, $options);

        // Button

        $label = get_string('continue', 'local_mail');
        $mform->addElement('submit', 'continue', $label);
    }

    function validation($data, $files) {
        global $SITE;

        $errors = array();

        if ($data['c'] == $SITE->id) {
            $errors['course'] = get_string('erroremptycourse', 'local_mail');
        }

        return $errors;
    }
}
