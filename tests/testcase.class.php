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

defined('MOODLE_INTERNAL') || die();

global $CFG;

abstract class local_mail_testcase extends advanced_testcase {

    static function assertContains($needle, $haystack, $message = '',
                                   $ignoreCase = false, $checkForObjectIdentity = false) {
        parent::assertContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity);
    }

    static function assertIndex($userid, $type, $item, $time, $messageid, $unread) {
        self::assertRecords('index', array(
            'userid' => $userid,
            'type' => $type,
            'item' => $item,
            'time' => $time,
            'messageid' => $messageid,
            'unread' => $unread,
        ));
    }

    static function assertNotContains($needle, $haystack, $message = '',
                                      $ignoreCase = false, $checkForObjectIdentity = false) {
        parent::assertNotContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity);
    }

    static function assertNotIndex($userid, $type, $item, $message) {
        self::assertNotRecords('index', array(
            'userid' => $userid,
            'type' => $type,
            'item' => $item,
            'messageid' => $message,
        ));
    }

    static function assertNotRecords($table, array $conditions = array()) {
        global $DB;
        self::assertFalse($DB->record_exists('local_mail_' . $table, $conditions));
    }

    static function assertRecords($table, array $conditions = array()) {
        global $DB;
        self::assertTrue($DB->record_exists('local_mail_' . $table, $conditions));
    }

    static function loadRecords($table, $rows) {
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

    function setUp() {
        $this->resetAfterTest(false);
    }

    function tearDown() {
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
