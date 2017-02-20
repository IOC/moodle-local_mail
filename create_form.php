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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class local_mail_create_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $courses = $this->_customdata['courses'];

        // Header.

        $label = get_string('compose', 'local_mail');
        $mform->addElement('header', 'general', $label);

        // Course.

        $label = get_string('course');
        $options = array(SITEID => '');
        foreach ($courses as $course) {
            $options[$course->id] = $course->fullname;
        }
        $mform->addElement('select', 'c', $label, $options);

        // Button.

        $label = get_string('continue', 'local_mail');
        $mform->addElement('submit', 'continue', $label);
    }

    public function validation($data, $files) {
        global $SITE;

        $errors = array();

        if ($data['c'] == $SITE->id) {
            $errors['course'] = get_string('erroremptycourse', 'local_mail');
        }

        return $errors;
    }
}
