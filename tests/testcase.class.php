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

global $CFG;

abstract class local_mail_testcase extends advanced_testcase {

    static public function assertContains($needle, $haystack, $message = '',
                                   $ignoreCase = false, $checkForObjectIdentity = false, $checkForNonObjectIdentity = false) {
        parent::assertContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity, $checkForNonObjectIdentity);
    }

    static public function assertIndex($userid, $type, $item, $time, $messageid, $unread) {
        self::assertRecords('index', array(
            'userid' => $userid,
            'type' => $type,
            'item' => $item,
            'time' => $time,
            'messageid' => $messageid,
            'unread' => $unread,
        ));
    }

    static public function assertNotContains($needle, $haystack, $message = '',
                                      $ignoreCase = false, $checkForObjectIdentity = false, $checkForNonObjectIdentity = false) {
        parent::assertNotContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity, $checkForNonObjectIdentity);
    }

    static public function assertNotIndex($userid, $type, $item, $message) {
        self::assertNotRecords('index', array(
            'userid' => $userid,
            'type' => $type,
            'item' => $item,
            'messageid' => $message,
        ));
    }

    static public function assertNotRecords($table, array $conditions = array()) {
        global $DB;
        self::assertFalse($DB->record_exists('local_mail_' . $table, $conditions));
    }

    static public function assertRecords($table, array $conditions = array()) {
        global $DB;
        self::assertTrue($DB->record_exists('local_mail_' . $table, $conditions));
    }

    static public function loadRecords($table, $rows) {
        global $DB;
        $columns = array_shift($rows);
        foreach ($rows as $row) {
            $record = (object) array_combine($columns, $row);
            if (empty($record->id)) {
                $DB->insert_record($table, $record);
            } else {
                $DB->import_record($table, $record);
            }
        }
    }

    public function setUp() {
        $this->resetAfterTest(false);
    }

    public function tearDown() {
        global $DB;
        $DB->delete_records_select('course', 'id > 100');
        $DB->delete_records_select('user', 'id > 200');
        $DB->delete_records('local_mail_labels');
        $DB->delete_records('local_mail_messages');
        $DB->delete_records('local_mail_message_refs');
        $DB->delete_records('local_mail_message_users');
        $DB->delete_records('local_mail_message_labels');
        $DB->delete_records('local_mail_index');
    }
}
