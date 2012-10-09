<?php

require_once($CFG->libdir . '/formslib.php');

class mail_label_form extends moodleform {

    function definition() {
        global $PAGE;

        $mform =& $this->_form;
        $colors = $this->_customdata['colors'];
        $offset = $this->_customdata['offset'];
        $labelid = $this->_customdata['l'];

        $mform->addElement('hidden', 'l', $labelid);

        $mform->addElement('hidden', 'offset', $offset);

        $mform->addElement('hidden', 'editlbl', $this->_customdata['editlbl']);

        // List labels

        $mform->addElement('header', 'editlabel', get_string('editlabel', 'local_mail'));
        $mform->addElement('text', 'labelname', get_string('labelname', 'local_mail'));
        $mform->addElement('select', 'labelcolor', get_string('labelcolor', 'local_mail'), $colors, array('class' => 'mail_label_colors'));

        // Buttons
        
        $this->add_action_buttons();
    }
}
