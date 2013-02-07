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
