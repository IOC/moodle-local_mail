<?php

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
