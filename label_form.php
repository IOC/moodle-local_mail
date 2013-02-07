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
        
        $buttonarray = array();

        $label = get_string('savechanges');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', $label);

        $label = get_string('cancel');
        $buttonarray[] = $mform->createElement('submit', 'cancel', $label);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['cancel']) and !trim($data['labelname'])) {
            $errors['labelname'] = get_string('erroremptylabelname', 'local_mail');
        }
        return $errors;
    }
}
