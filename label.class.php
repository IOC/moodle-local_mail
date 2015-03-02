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

defined('MOODLE_INTERNAL') || die();

class local_mail_label {

    private $id;
    private $userid;
    private $name;
    private $color;

    static public function create($userid, $name, $color='') {
        global $DB;

        assert($userid > 0);
        assert(strlen($name) > 0);
        assert(!$color or in_array($color, self::valid_colors()));

        $record = new stdClass;
        $record->userid = $userid;
        $record->name = $name;
        $record->color = $color;

        $record->id = $DB->insert_record('local_mail_labels', $record);

        return self::from_record($record);
    }

    static public function fetch($id) {
        global $DB;

        $record = $DB->get_record('local_mail_labels', array('id' => $id));

        if ($record) {
            return self::from_record($record);
        }

        return false;
    }

    static public function fetch_user($userid) {
        global $DB;

        $result = array();

        $records = $DB->get_records('local_mail_labels', array('userid' => $userid), 'name');
        foreach ($records as $record) {
            $result[] = self::from_record($record);
        }

        return $result;
    }

    static public function from_record($record) {
        $label = new self;
        $label->id = (int) $record->id;
        $label->userid = (int) $record->userid;
        $label->name = $record->name;
        $label->color = $record->color;
        return $label;
    }

    static public function valid_colors() {
        return array('lightred', 'lightorange', 'lightyellow', 'lightgreen', 'lightblue', 'lightpurple', 'lightgray',
                     'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray',
                     'darkred', 'darkorange', 'darkyellow', 'darkgreen', 'darkblue', 'darkpurple', 'black');
    }

    public function color() {
        return $this->color;
    }

    public function delete() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('local_mail_labels', array('id' => $this->id));
        $DB->delete_records('local_mail_message_labels', array('labelid' => $this->id));

        $conditions = array('userid' => $this->userid, 'type' => 'label', 'item' => $this->id);
        $DB->delete_records('local_mail_index', $conditions);

        $transaction->allow_commit();
    }

    public function id() {
        return $this->id;
    }

    public function name() {
        return $this->name;
    }

    public function save($name, $color) {
        global $DB;

        assert(!$color or in_array($color, self::valid_colors()));
        assert(strlen($name) > 0);

        $record = new stdClass;
        $record->id = $this->id;
        $record->name = $this->name = $name;
        $record->color = $this->color = $color;

        $DB->update_record('local_mail_labels', $record);
    }

    public function userid() {
        return $this->userid;
    }

    private function __construct() {
    }
}
