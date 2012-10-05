<?php

require_once($CFG->libdir . '/formslib.php');

class mail_labels_form extends moodleform {

    function definition() {
        global $PAGE;

        $mform =& $this->_form;
        $colors = $this->_customdata['colors'];
        $labelids = $this->_customdata['labelids'];
        $type = $this->_customdata['t'];
        $offset = $this->_customdata['offset'];

        $mform->addElement('hidden', 't', $type);

        $mform->addElement('hidden', 'offset', $offset);

        if ($this->_customdata['t'] == 'course') {
            $mform->addElement('hidden', 'c', $this->_customdata['c']);
        }
        
        if (isset($this->_customdata['msgs'])) {
            $msgs = $this->_customdata['msgs'];
            foreach ($msgs as $key => $msg) {
                $mform->addElement('hidden', 'msgs['.$key.']', $msg);
            }
        }

        $mform->addElement('hidden', 'assignlbl', $this->_customdata['assignlbl']);
        if (isset($this->_customdata['m'])) {
            $mform->addElement('hidden', 'm', $this->_customdata['m']);
        }

        // List labels

        $mform->addElement('header', 'listlabels', get_string('labels', 'local_mail'));
        $cont = 0;
        if ($labelids) {
            foreach($labelids as $id) {
                $html = html_writer::tag('span', $this->_customdata['labelname'.$id], array('class' => 'mail_label '.'mail_label_'.$this->_customdata['color'.$id]));
                $mform->addElement('advcheckbox', 'labelid['.$id.']', '', $html);
                $mform->setDefault('labelid['.$id.']', 0);
            }
        } else {
            $mform->addElement('static', 'nolabels', get_string('nolabels', 'local_mail'));
        }
        $mform->addElement('header', 'newlabel', get_string('assigntonewlabel', 'local_mail'));

        //New label

        $mform->addElement('text', 'newlabelname', get_string('labelname', 'local_mail'));
        $mform->addElement('select', 'newlabelcolor', get_string('labelcolor', 'local_mail'), $colors, array('class' => 'mail_label_colors'));

        // Buttons
        
        $this->add_action_buttons();
    }
}
