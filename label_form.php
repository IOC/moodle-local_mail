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

class mail_label_form extends moodleform {

    public function definition() {
        global $PAGE;

        $mform =& $this->_form;
        $colors = $this->_customdata['colors'];
        $offset = $this->_customdata['offset'];
        $labelid = $this->_customdata['l'];

        $mform->addElement('hidden', 'l', $labelid);
        $mform->setType('l', PARAM_INT);

        $mform->addElement('hidden', 'offset', $offset);
        $mform->setType('offset', PARAM_INT);

        $mform->addElement('hidden', 'editlbl', $this->_customdata['editlbl']);
        $mform->setType('editlbl', PARAM_BOOL);

        // List labels.

        $mform->addElement('header', 'editlabel', get_string('editlabel', 'local_mail'));
        $mform->addElement('text', 'labelname', get_string('labelname', 'local_mail'));
        $mform->setType('labelname', PARAM_TEXT);
        $text = get_string('maximumchars', '', 100);
        $mform->addRule('labelname', $text, 'maxlength', 100, 'client');
        $mform->addElement('select', 'labelcolor', get_string('labelcolor', 'local_mail'),
                        $colors, array('class' => 'mail_label_colors'));

        // Buttons.

        $buttonarray = array();

        $label = get_string('savechanges');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', $label);

        $label = get_string('cancel');
        $buttonarray[] = $mform->createElement('submit', 'cancel', $label);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['cancel']) and !trim($data['labelname'])) {
            $errors['labelname'] = get_string('erroremptylabelname', 'local_mail');
        }
        return $errors;
    }
}
