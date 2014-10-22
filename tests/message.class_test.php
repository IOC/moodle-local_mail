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
require_once($CFG->dirroot.'/local/mail/tests/testcase.class.php');
require_once($CFG->dirroot.'/local/mail/message.class.php');
require_once($CFG->dirroot.'/local/mail/label.class.php');

class local_mail_message_test extends local_mail_testcase {

    /* 1xx -> courses
       2xx -> users
       3xx -> formats
       5xx -> messages */

    private $course1, $course2, $user1, $user2, $user3;

    static public function assertMessage(local_mail_message $message) {
        global $DB;

        $course = $message->course();

        $record = $DB->get_record('local_mail_messages', array('id' => $message->id()));
        self::assertNotEquals(false, $record);
        self::assertEquals($course->id, $record->courseid);
        self::assertEquals($message->subject(), $record->subject);
        self::assertEquals($message->content(), $record->content);
        self::assertEquals($message->format(), $record->format);
        self::assertEquals($message->draft(), (bool) $record->draft);
        self::assertEquals($message->time(), $record->time);

        foreach ($message->references() as $reference) {
            self::assertRecords('message_refs', array(
                'messageid' => $message->id(),
                'reference' => $reference->id(),
            ));
        }

        $roleusers = array(
            'from' => array($message->sender()),
            'to' => $message->recipients('to'),
            'cc' => $message->recipients('cc'),
            'bcc' => $message->recipients('bcc'),
        );

        foreach ($roleusers as $role => $users) {
            foreach ($users as $user) {
                self::assertRecords('message_users', array(
                    'messageid' => $message->id(),
                    'userid' => $user->id,
                    'role' => $role,
                    'unread' => (int) $message->unread($user->id),
                    'starred' => (int) $message->starred($user->id),
                    'deleted' => (int) $message->deleted($user->id),
                ));
            }
        }

        foreach ($message->labels() as $label) {
            $conditions = array('messageid' => $message->id(), 'labelid' => $label->id());
            self::assertRecords('message_labels', $conditions);
        }
    }

    public function setUp() {
        global $DB;

        parent::setUp();

        $course = array(
            array('id',  'shortname', 'fullname', 'groupmode'),
            array('101', 'C1',        'Course 1', '0'),
            array('102', 'C2',        'Course 2', '0'),
        );
        $user = array(
            array(
                'id', 'username', 'firstname', 'lastname', 'email',
                'picture', 'imagealt', 'maildisplay'),
            array(
                201, 'user1', 'User1', 'Name', 'user1@ex.org',
                1, 'User 1', 1),
            array(
                202, 'user2', 'User2', 'Name', 'user2@ex.org',
                1, 'User 2', 1),
            array(
                203, 'user3', 'User3', 'Name', 'user3@ex.org',
                1, 'User 3', 1),
        );

        $this->loadRecords('course', $course);
        $this->loadRecords('user', $user);

        $this->course1 = (object) array_combine($course[0], $course[1]);
        $this->course2 = (object) array_combine($course[0], $course[2]);

        // When adding users, we need them to have all the fields that Moodle gets for the user.
        $fields = user_picture::fields('', array('username', 'maildisplay'));
        $this->user1 = $DB->get_record('user', array('id' => $user[1][0]), $fields, MUST_EXIST);
        $this->user2 = $DB->get_record('user', array('id' => $user[2][0]), $fields, MUST_EXIST);
        $this->user3 = $DB->get_record('user', array('id' => $user[3][0]), $fields, MUST_EXIST);

        // User need to be logged in to be able to have the local/mail:usemail, so we make ourselves
        // the admin to ensure we behave as a logged in user rather than a guest.
        $this->setAdminUser();
    }

    public function test_add_label() {
        $label1 = local_mail_label::create(201, 'name1');
        $label2 = local_mail_label::create(202, 'name2');
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->send();

        $message->add_label($label1);
        $message->add_label($label2);
        $message->add_label($label2);

        $this->assertCount(2, $message->labels());
        $this->assertContains($label1, $message->labels());
        $this->assertContains($label2, $message->labels());
        $this->assertTrue($message->has_label($label1));
        $this->assertTrue($message->has_label($label2));
        $this->assertCount(1, $message->labels(201));
        $this->assertContains($label1, $message->labels(201));
        $this->assertCount(1, $message->labels(202));
        $this->assertContains($label2, $message->labels(202));
        $this->assertMessage($message);
        $this->assertIndex(201, 'label', $label1->id(), $message->time(), $message->id(), false);
        $this->assertIndex(202, 'label', $label2->id(), $message->time(), $message->id(), true);
    }

    public function test_add_recipient() {
        $message = local_mail_message::create(201, 101);

        $message->add_recipient('to', 202);
        $message->add_recipient('cc', 203);

        $this->assertTrue($message->has_recipient(202));
        $this->assertTrue($message->has_recipient(203));
        $this->assertCount(2, $message->recipients());
        $this->assertCount(1, $message->recipients('to'));
        $this->assertCount(1, $message->recipients('cc'));
        $this->assertCount(0, $message->recipients('bcc'));
        $this->assertContains($this->user2, $message->recipients());
        $this->assertContains($this->user3, $message->recipients());
        $this->assertContains($this->user2, $message->recipients('to'));
        $this->assertContains($this->user3, $message->recipients('cc'));
        $this->assertMessage($message);
    }

    public function test_count_index() {
        $message1 = local_mail_message::create(201, 101);
        $message1->add_recipient('to', 202);
        $message1->send();
        $message2 = local_mail_message::create(201, 102);
        $message2->add_recipient('to', 202);
        $message2->send();
        $other = local_mail_message::create(202, 101);

        $result = local_mail_message::count_index(202, 'inbox');

        $this->assertEquals(2, $result);
    }

    public function test_create() {
        $result = local_mail_message::create(201, 101, 1234567890);

        $this->assertNotEquals(false, $result->id());
        $this->assertEquals($this->course1, $result->course());
        $this->assertEquals('', $result->subject());
        $this->assertEquals('', $result->content());
        $this->assertEquals(-1, $result->format());
        $this->assertTrue($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals(array(), $result->references());
        $this->assertEquals($this->user1, $result->sender());
        $this->assertCount(0, $result->recipients());
        $this->assertCount(0, $result->labels());
        $this->assertMessage($result);
        $this->assertIndex(201, 'drafts', 0, 1234567890, $result->id(), false);
        $this->assertIndex(201, 'course', 101, 1234567890, $result->id(), false);
    }

    public function test_delete_course() {
        $label = local_mail_label::create(201, 'name');
        $message1 = local_mail_message::create(201, 101);
        $message1->add_recipient('to', 202);
        $message1->add_label($label);
        $message1->send();
        $message2 = $message1->reply(202);
        $other = local_mail_message::create(201, 102);
        $other->add_recipient('to', 202);
        $other->add_label($label);
        $other->send();
        $other->reply(202);

        local_mail_message::delete_course(101);

        $this->assertNotRecords('messages', array('courseid' => 101));
        $this->assertNotRecords('message_refs', array('messageid' => $message2->id()));
        $this->assertNotRecords('message_users', array('messageid' => $message1->id()));
        $this->assertNotRecords('message_users', array('messageid' => $message2->id()));
        $this->assertNotRecords('message_labels', array('messageid' => $message1->id()));
        $this->assertNotRecords('message_labels', array('messageid' => $message2->id()));
        $this->assertRecords('messages');
        $this->assertRecords('message_users');
        $this->assertRecords('message_refs');
        $this->assertRecords('message_labels');
        $this->assertNotIndex(201, 'course', 101, $message1->id());
        $this->assertNotIndex(202, 'course', 101, $message2->id());
    }

    public function test_discard() {
        $label = local_mail_label::create(202, 'name');
        $reference = local_mail_message::create(201, 101);
        $reference->add_recipient('to', 202);
        $reference->send();
        $message = $reference->reply(202);
        $message->add_label($label);
        $other = $reference->forward(202);
        $other->add_label($label);

        $message->discard();

        $this->assertNotRecords('messages', array('id' => $message->id()));
        $this->assertNotRecords('message_users', array('messageid' => $message->id()));
        $this->assertNotRecords('message_refs', array('messageid' => $message->id()));
        $this->assertNotRecords('message_labels', array('messageid' => $message->id()));
        $this->assertRecords('messages');
        $this->assertRecords('message_users');
        $this->assertRecords('message_refs');
        $this->assertRecords('message_labels');
        $this->assertNotIndex(201, 'drafts', 0, $message->id());
        $this->assertNotIndex(201, 'course', 101, $message->id());
    }

    public function test_editable() {
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);

        $this->assertTrue($message->editable(201));
        $this->assertFalse($message->editable(202));
        $this->assertFalse($message->editable(203));

        $message->send();

        $this->assertFalse($message->editable(201));
        $this->assertFalse($message->editable(202));
        $this->assertFalse($message->editable(203));
    }

    public function test_fetch() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(201, 'label2');
        $label3 = local_mail_label::create(202, 'label3');
        $this->loadRecords('local_mail_messages', array(
            array('id', 'courseid', 'subject',  'content', 'format', 'draft', 'time'),
            array( 501,  101,       'subject1', 'content1', 301,      0,       1234567890 ),
            array( 502,  101,       'subject2', 'content2', 301,      1,       1234567891 ),
            array( 503,  101,       'subject3', 'content3', 301,      0,       1234567892 ),
            array( 504,  101,       'subject4', 'content4', 301,      0,       1234567893 ),
        ));
        $this->loadRecords('local_mail_message_refs', array(
            array('messageid', 'reference'),
            array( 501,         504 ),
            array( 501,         503 ),
            array( 502,         501 ),
        ));
        $this->loadRecords('local_mail_message_users', array(
             array('messageid', 'userid', 'role', 'unread', 'starred',  'deleted'),
             array( 501,         201,     'from',  0,        0,          1 ),
             array( 501,         202,     'to',    0,        1,          0 ),
             array( 501,         203,     'cc',    1,        0,          0 ),
             array( 502,         201,     'from',  0,        0,          0 ),
             array( 503,         201,     'from',  0,        0,          0 ),
             array( 503,         202,     'to',    0,        0,          0 ),
             array( 504,         202,     'from',  0,        0,          0 ),
             array( 504,         201,     'to',    0,        0,          0 ),
        ));
        $this->loadRecords('local_mail_message_labels', array(
            array('messageid', 'labelid'),
            array( 501,         $label1->id() ),
            array( 501,         $label2->id() ),
            array( 502,         $label3->id() ),
        ));

        $result = local_mail_message::fetch(501);

        $this->assertInstanceOf('local_mail_message', $result);
        $this->assertEquals(501, $result->id());
        $this->assertEquals($this->course1, $result->course());
        $this->assertEquals('subject1', $result->subject());
        $this->assertEquals('content1', $result->content());
        $this->assertEquals(301, $result->format());
        $this->assertFalse($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $references = array(local_mail_message::fetch(504), local_mail_message::fetch(503));
        $this->assertEquals($references, $result->references());
        $this->assertEquals($this->user1, $result->sender());
        $this->assertFalse($result->unread(201));
        $this->assertFalse($result->starred(201));
        $this->assertTrue($result->deleted(201));
        $this->assertCount(1, $result->recipients('to'));
        $this->assertCount(1, $result->recipients('cc'));
        $this->assertCount(0, $result->recipients('bcc'));
        $this->assertContains($this->user2, $result->recipients('to'));
        $this->assertContains($this->user3, $result->recipients('cc'));
        $this->assertFalse($result->unread(202));
        $this->assertTrue($result->starred(202));
        $this->assertFalse($result->deleted(202));
        $this->assertTrue($result->unread(203));
        $this->assertFalse($result->starred(203));
        $this->assertFalse($result->deleted(203));
        $this->assertCount(2, $result->labels());
        $this->assertContains($label1, $result->labels());
        $this->assertContains($label2, $result->labels());

        $this->assertFalse(local_mail_message::fetch(505));
    }

    public function test_fetch_index() {
        $message1 = local_mail_message::create(201, 101);
        $message1->save('subject1', 'content1', 301);
        $message1->add_recipient('to', 202);
        $message1->send(12345567890);
        $message2 = local_mail_message::create(201, 102);
        $message2->save('subject2', 'content2', 302);
        $message2->add_recipient('to', 202);
        $message2->send(12345567891);
        $message3 = local_mail_message::create(201, 102);
        $message3->save('subject3', 'content3', 302);
        $message3->add_recipient('to', 202);
        $message3->send(12345567891);
        $other = local_mail_message::create(202, 101);
        $other->save('subject', 'content', 0);

        $result = local_mail_message::fetch_index(202, 'inbox');

        $this->assertEquals(array($message3, $message2, $message1), $result);
    }

    public function test_fetch_many() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(202, 'label2');
        $message1 = local_mail_message::create(201, 101);
        $message1->save('subject1', 'content1', 301);
        $message1->add_recipient('to', 202);
        $message2 = local_mail_message::create(201, 101);
        $message2->save('subject2', 'content2', 302);
        $message2->add_recipient('to', 202);
        $message2->add_recipient('cc', 203);
        $message2->send();
        $message2->add_label($label1);
        $message2->add_label($label2);

        $result = local_mail_message::fetch_many(array($message1->id(), $message2->id()));

        $this->assertEquals(array($message1, $message2), $result);
    }

    public function test_fetch_menu() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(201, 'label2');
        $message1 = local_mail_message::create(201, 101);
        $message2 = local_mail_message::create(202, 101);
        $message2->add_recipient('to', 201);
        $message2->send();
        $message2->set_unread(201, false);
        $message2->add_label($label1);
        $message2->add_label($label2);
        $message3 = local_mail_message::create(201, 102);
        $message3->add_recipient('to', 202);
        $message3->send();
        $message4 = local_mail_message::create(202, 101);
        $message4->add_recipient('to', 201);
        $message4->send();
        $message4->add_label($label1);
        $message4->set_starred(201, true);

        $result = local_mail_message::count_menu(201);

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result->inbox);
        $this->assertEquals(1, $result->drafts);
        $this->assertEquals(array(101 => 1), $result->courses);
        $this->assertEquals(array($label1->id() => 1), $result->labels);
    }

    public function test_forward() {
        $label = local_mail_label::create(202, 'label');
        $message = local_mail_message::create(201, 101);
        $message->save('subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->send();
        $message->add_label($label);

        $result = $message->forward(202, 1234567890);

        $this->assertInstanceOf('local_mail_message', $result);
        $this->assertNotEquals(false, $result->id());
        $this->assertEquals($this->course1, $result->course());
        $this->assertEquals('FW: subject', $result->subject());
        $this->assertEquals('', $result->content());
        $this->assertEquals(-1, $result->format());
        $this->assertTrue($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals(array($message), $result->references());
        $this->assertEquals($this->user2, $result->sender());
        $this->assertCount(0, $result->recipients());
        $this->assertCount(1, $result->labels());
        $this->assertContains($label, $result->labels());
        $this->assertMessage($result);
        $this->assertIndex(202, 'drafts', 0, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'course', 101, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'label', $label->id(), 1234567890, $result->id(), false);
    }

    public function test_remove_label() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(202, 'label2');
        $label3 = local_mail_label::create(202, 'label3');
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->send();
        $message->add_label($label1);
        $message->add_label($label2);
        $message->add_label($label3);

        $message->remove_label($label1);
        $message->remove_label($label2);
        $message->remove_label($label2);

        $this->assertCount(1, $message->labels());
        $this->assertNotContains($label1, $message->labels());
        $this->assertNotContains($label2, $message->labels());
        $this->assertFalse($message->has_label($label1));
        $this->assertFalse($message->has_label($label2));
        $this->assertCount(0, $message->labels(201));
        $this->assertCount(1, $message->labels(202));
        $this->assertNotContains($label2, $message->labels(202));
        $this->assertMessage($message);
        $this->assertNotIndex(201, 'label', $label1->id(), $message->id());
        $this->assertNotIndex(202, 'label', $label2->id(), $message->time(), $message->id(), true);
        $this->assertIndex(202, 'label', $label3->id(), $message->time(), $message->id(), true);
    }

    public function test_remove_recipient() {
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->add_recipient('cc', 203);

        $message->remove_recipient(202);

        $this->assertFalse($message->has_recipient(202));
        $this->assertTrue($message->has_recipient(203));
        $this->assertCount(1, $message->recipients());
        $this->assertCount(0, $message->recipients('to'));
        $this->assertCount(1, $message->recipients('cc'));
        $this->assertCount(0, $message->recipients('bcc'));
        $this->assertContains($this->user3, $message->recipients());
        $this->assertContains($this->user3, $message->recipients('cc'));
        $this->assertMessage($message);
    }

    public function test_reply() {
        $label = local_mail_label::create(202, 'label');
        $message = local_mail_message::create(201, 101);
        $message->save('subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->add_recipient('to', 203);
        $message->send();
        $message->add_label($label);

        $result = $message->reply(202, false, 1234567890);

        $this->assertInstanceOf('local_mail_message', $result);
        $this->assertNotEquals(false, $result->id());
        $this->assertEquals($this->course1, $result->course());
        $this->assertEquals('RE: subject', $result->subject());
        $this->assertEquals('', $result->content());
        $this->assertEquals(-1, $result->format());
        $this->assertTrue($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals(array($message), $result->references());
        $this->assertEquals($this->user2, $result->sender());
        $this->assertCount(1, $result->recipients());
        $this->assertContains($this->user1, $result->recipients('to'));
        $this->assertCount(1, $result->labels());
        $this->assertContains($label, $result->labels());
        $this->assertMessage($result);
        $this->assertIndex(202, 'drafts', 0, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'course', 101, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'label', $label->id(), 1234567890, $result->id(), false);
    }

    public function test_reply_all() {
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->add_recipient('to', 203);
        $message->send();

        $result = $message->reply(202, true);

        $this->assertCount(2, $result->recipients());
        $this->assertContains($this->user1, $result->recipients('to'));
        $this->assertContains($this->user3, $result->recipients('cc'));
        $this->assertMessage($result);
    }

    public function test_reply_subject() {
        $message = local_mail_message::create(201, 101);
        $message->save('subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->send();

        $result = $message->reply(202);
        $this->assertEquals('RE: subject', $result->subject());

        $result->send();
        $result = $result->reply(201);
        $this->assertEquals('RE [2]: subject', $result->subject());

        $result->send();
        $result = $result->reply(202);
        $this->assertEquals('RE [3]: subject', $result->subject());
    }

    public function test_save() {
        $message = local_mail_message::create(201, 101);
        $message->save('subject', 'content', 301, 1234567890);

        $this->assertEquals($this->course1, $message->course());
        $this->assertEquals('subject', $message->subject());
        $this->assertEquals('content', $message->content());
        $this->assertEquals(301, $message->format());
        $this->assertTrue($message->draft());
        $this->assertEquals(1234567890, $message->time());
        $this->assertMessage($message);
        $this->assertIndex(201, 'drafts', 0, 1234567890, $message->id(), false);
        $this->assertIndex(201, 'course', 101, 1234567890, $message->id(), false);
    }

    public function test_search_index() {
        $message1 = local_mail_message::create(201, 101);
        $message1->add_recipient('to', 202);
        $message1->save('subject', 'content', 301, 1234567890);
        $message2 = local_mail_message::create(201, 101);
        $message2->add_recipient('to', 202);
        $message2->save('subject foo bar', 'content', 301, 1234567890);
        $message3 = local_mail_message::create(201, 101);
        $message3->save('subject', 'content <p>foo</p> <p>bar</p>', 301, 1234567891);
        $message4 = local_mail_message::create(201, 101);
        $message4->save('subject', 'content', 301, 1234567891);
        $message4->set_unread(201, true);
        $message5 = local_mail_message::create(202, 101);
        $message5->add_recipient('to', 201);
        $message5->save('subject5', 'content5', 301, 1234567890, true);

        // Subject and content
        $query = array('pattern' => ' foo  bar ');
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message3, $message2), $result);

        // Users
        $query = array('pattern' => fullname($this->user2));
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message2, $message1), $result);

        // Unread
        $query = array('unread' => true);
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message4), $result);

        // Date
        $query = array('time' => 1234567890);
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message2, $message1), $result);

        // Limit
        $query = array('limit' => 2);
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message4, $message3), $result);

        // Before
        $query = array('before' => $message2->id());
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message1), $result);

        // After
        $query = array('after' => $message1->id(), 'limit' => 2);
        $result = local_mail_message::search_index(201, 'course', 101, $query);
        $this->assertEquals(array($message3, $message2), $result);

        // Attach

        $query = array('attach' => true);
        $result = local_mail_message::search_index(202, 'course', 101, $query);
        $this->assertEquals(array($message5), $result);

        // From

        $query = array('searchfrom' => fullname($this->user2));
        $result = local_mail_message::search_index(202, 'course', 101, $query);
        $this->assertEquals(array($message5), $result);

        // To

        $query = array('searchto' => fullname($this->user1));
        $result = local_mail_message::search_index(202, 'course', 101, $query);
        $this->assertEquals(array($message5), $result);

    }

    public function test_send() {
        $label = local_mail_label::create(201, 'label');
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->add_label($label);

        $message->send(1234567890);

        $this->assertFalse($message->draft());
        $this->assertEquals(1234567890, $message->time());
        $this->assertContains($label, $message->labels());
        $this->assertMessage($message);
        $this->assertNotIndex(201, 'drafts', 0, $message->id());
        $this->assertIndex(201, 'sent', 0, 1234567890, $message->id(), false);
        $this->assertIndex(201, 'course', 101, 1234567890, $message->id(), false);
        $this->assertIndex(202, 'inbox', 0, 1234567890, $message->id(), true);
        $this->assertIndex(202, 'course', 101, 1234567890, $message->id(), true);
    }

    public function test_send_with_reference() {
        $label = local_mail_label::create(201, 'label');
        $reference = local_mail_message::create(201, 101);
        $reference->add_recipient('to', 202);
        $reference->send();
        $reference->add_label($label);
        $message = $reference->reply(202);
        $message->add_recipient('cc', 203);

        $message->send();

        $this->assertContains($label, $message->labels());
        $this->assertMessage($message);
        $this->assertIndex(201, 'label', $label->id(), $message->time(), $message->id(), true);
    }

    public function test_set_deleted() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(202, 'label2');
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->send();
        $message->add_label($label1);
        $message->add_label($label2);
        $message->set_starred(201, true);

        $message->set_deleted(201, true);
        $message->set_deleted(202, true);

        $this->assertTrue($message->deleted(201));
        $this->assertTrue($message->deleted(202));
        $this->assertNotIndex(201, 'sent', 0, $message->id());
        $this->assertNotIndex(201, 'starred', 0, $message->id());
        $this->assertNotIndex(201, 'course', $message->course()->id, $message->id());
        $this->assertNotIndex(201, 'label', $label1->id(), $message->id());
        $this->assertNotIndex(202, 'inbox', 0, $message->id());
        $this->assertNotIndex(202, 'course', $message->course()->id, $message->id());
        $this->assertNotIndex(202, 'label', $label2->id(), $message->id());
        $this->assertIndex(201, 'trash', 0, $message->time(), $message->id(), false);
        $this->assertIndex(202, 'trash', 0, $message->time(), $message->id(), true);
        $this->assertMessage($message);

        $message->set_deleted(201, false);
        $message->set_deleted(202, false);

        $this->assertFalse($message->deleted(201));
        $this->assertFalse($message->deleted(202));
        $this->assertIndex(201, 'sent', 0, $message->time(), $message->id(), false);
        $this->assertIndex(201, 'starred', 0, $message->time(), $message->id(), false);
        $this->assertIndex(201, 'course', $message->course()->id, $message->time(), $message->id(), false);
        $this->assertIndex(201, 'label', $label1->id(), $message->time(), $message->id(), false);
        $this->assertIndex(202, 'inbox', 0, $message->time(), $message->id(), true);
        $this->assertIndex(202, 'course', $message->course()->id, $message->time(), $message->id(), true);
        $this->assertIndex(202, 'label', $label2->id(), $message->time(), $message->id(), true);
        $this->assertNotIndex(201, 'trash', 0, $message->id());
        $this->assertNotIndex(202, 'trash', 0, $message->id());
        $this->assertMessage($message);
    }

    public function test_set_deleted_draft() {
        $message = local_mail_message::create(201, 101);

        $message->set_deleted(201, true);

        $this->assertTrue($message->deleted(201));
        $this->assertNotIndex(201, 'drafts', 0, $message->id());
        $this->assertNotIndex(201, 'course', $message->course()->id, $message->id());
        $this->assertIndex(201, 'trash', 0, $message->time(), $message->id(), false);
        $this->assertMessage($message);

        $message->set_deleted(201, false);

        $this->assertIndex(201, 'drafts', 0, $message->time(), $message->id(), false);
        $this->assertIndex(201, 'course', $message->course()->id, $message->time(), $message->id(), false);
        $this->assertNotIndex(201, 'trash', 0, $message->id());
        $this->assertMessage($message);
    }

    public function test_set_starred() {
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);
        $message->send();

        $message->set_starred(201, true);
        $message->set_starred(202, true);

        $this->assertTrue($message->starred(201));
        $this->assertTrue($message->starred(202));
        $this->assertIndex(201, 'starred', 0, $message->time(), $message->id(), false);
        $this->assertIndex(202, 'starred', 0, $message->time(), $message->id(), true);
        $this->assertMessage($message);

        $message->set_starred(201, false);
        $message->set_starred(202, false);

        $this->assertFalse($message->starred(201));
        $this->assertFalse($message->starred(202));
        $this->assertNotIndex(201, 'starred', 0, $message->id());
        $this->assertNotIndex(202, 'starred', 0, $message->id());
        $this->assertMessage($message);
    }

    public function test_set_unread() {
        $label = local_mail_label::create(201, 'label');
        $message = local_mail_message::create(201, 101);
        $message->add_label($label);
        $message->set_starred(201, true);

        $message->set_unread(201, true);

        $this->assertTrue($message->unread(201));
        $this->assertIndex(201, 'drafts', 0, $message->time(), $message->id(), true);
        $this->assertIndex(201, 'starred', 0, $message->time(), $message->id(), true);
        $this->assertIndex(201, 'course', $message->course()->id, $message->time(), $message->id(), true);
        $this->assertMessage($message);

        $message->set_unread(201, false);

        $this->assertFalse($message->unread(201));
        $this->assertIndex(201, 'drafts', 0, $message->time(), $message->id(), false);
        $this->assertIndex(201, 'starred', 0, $message->time(), $message->id(), false);
        $this->assertIndex(201, 'course', $message->course()->id, $message->time(), $message->id(), false);
        $this->assertMessage($message);
    }

    public function test_viewable() {
        $message = local_mail_message::create(201, 101);
        $message->add_recipient('to', 202);

        $this->assertTrue($message->viewable(201));
        $this->assertFalse($message->viewable(202));
        $this->assertFalse($message->viewable(203));

        $message->send();

        $this->assertTrue($message->viewable(201));
        $this->assertTrue($message->viewable(202));
        $this->assertFalse($message->viewable(203));

        $forwarded = $message->forward(202);
        $forwarded->add_recipient('to', 203);
        $forwarded->send();

        $this->assertFalse($message->viewable(203));
        $this->assertTrue($message->viewable(203, true));
    }

    public function test_fetch_index_attachment() {
        $message = local_mail_message::create(201, 101);
        $message->save('subject1', 'content1', 301, false, true);
        $message->add_recipient('to', 202);
        $message->send(12345567890);

        $result = local_mail_message::fetch_index(201, 'attachment', true);
        $this->assertEquals(array($message), $result);
    }

    public function test_fetch_index_attachment_deleted() {
        $message = local_mail_message::create(201, 101);
        $message->save('subject1', 'content1', 301 , false);
        $message->add_recipient('to', 202);
        $this->assertTrue($message->draft());

        $result = local_mail_message::fetch_index(201, 'attachment', false);
        $this->assertEquals(array($message), $result);

    }
}
