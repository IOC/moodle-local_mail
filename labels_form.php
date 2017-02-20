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

class mail_labels_form extends moodleform {

    public function definition() {
        global $PAGE;

        $mform =& $this->_form;
        $colors = $this->_customdata['colors'];
        $labelids = $this->_customdata['labelids'];
        $type = $this->_customdata['t'];
        $offset = $this->_customdata['offset'];
        $courseid = $this->_customdata['c'];

        $mform->addElement('hidden', 't', $type);
        $mform->setType('t', PARAM_ALPHA);

        $mform->addElement('hidden', 'offset', $offset);
        $mform->setType('offset', PARAM_INT);

        $mform->addElement('hidden', 'c', $courseid);
        $mform->setType('c', PARAM_INT);

        if (isset($this->_customdata['msgs'])) {
            $msgs = $this->_customdata['msgs'];
            foreach ($msgs as $key => $msg) {
                $mform->addElement('hidden', 'msgs['.$key.']', $msg);
                $mform->setType('msgs['.$key.']', PARAM_INT);
            }
        }

        $mform->addElement('hidden', 'assignlbl', $this->_customdata['assignlbl']);
        $mform->setType('assignlbl', PARAM_INT);
        if (isset($this->_customdata['m'])) {
            $mform->addElement('hidden', 'm', $this->_customdata['m']);
            $mform->setType('m', PARAM_INT);
        }

        // List labels.

        $mform->addElement('header', 'listlabels', get_string('labels', 'local_mail'));
        if ($labelids) {
            foreach ($labelids as $id) {
                $html = html_writer::tag('span', $this->_customdata['labelname'.$id],
                                        array('class' => 'mail_label ' . 'mail_label_'.$this->_customdata['color' . $id]));
                $mform->addElement('advcheckbox', 'labelid['.$id.']', '', $html);
                $mform->setDefault('labelid['.$id.']', 0);
            }
        } else {
            $mform->addElement('static', 'nolabels', get_string('nolabels', 'local_mail'));
        }
        $mform->addElement('header', 'newlabel', get_string('assigntonewlabel', 'local_mail'));

        // New label.

        $mform->addElement('text', 'newlabelname', get_string('labelname', 'local_mail'));
        $mform->setType('newlabelname', PARAM_TEXT);
        $text = get_string('maximumchars', '', 100);
        $mform->addRule('newlabelname', $text, 'maxlength', 100, 'client');
        $mform->addElement('select', 'newlabelcolor', get_string('labelcolor', 'local_mail'), $colors,
                            array('class' => 'mail_label_colors'));

        // Buttons.

        $buttonarray = array();

        $label = get_string('savechanges');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', $label);

        $label = get_string('cancel');
        $buttonarray[] = $mform->createElement('submit', 'cancel', $label);

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
