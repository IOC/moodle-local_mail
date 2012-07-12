<?php

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

    static function assertMessage(local_mail_message $message) {
        self::assertEquals($message, local_mail_message::fetch($message->id()));
    }

    static function assertNotIndex($userid, $type, $item, $message) {
        self::assertNotRecords('index', array(
            'userid' => $userid,
            'type' => $type,
            'item' => $item,
            'messageid' => $message,
        ));
    }

    function setUp() {
        parent::setUp();

        $course = array(
            array('id',  'shortname', 'fullname'),
            array('101', 'C1',        'Course 1'),
            array('102', 'C2',        'Course 2'),
        );
        $user = array(
            array('id', 'username', 'firstname', 'lastname', 'email',        'picture', 'imagealt'),
            array( 201, 'user1',     'User1',    'Name',     'user1@ex.org',  1,        'User 1' ),
            array( 202, 'user2',     'User2',    'Name',     'user2@ex.org',  1,        'User 2' ),
            array( 203, 'user3',     'User3',    'Name',     'user3@ex.org',  1,        'User 3' ),
        );

        $this->loadRecords('course', $course);
        $this->loadRecords('user', $user);

        $this->course1 = (object) array_combine($course[0], $course[1]);
        $this->course2 = (object) array_combine($course[0], $course[2]);
        $this->user1 = (object) array_combine($user[0], $user[1]);
        $this->user2 = (object) array_combine($user[0], $user[2]);
        $this->user3 = (object) array_combine($user[0], $user[3]);
    }

    function test_add_label() {
        $label1 = local_mail_label::create(201, 'name1');
        $label2 = local_mail_label::create(202, 'name2');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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

    function test_add_recipient() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);

        $message->add_recipient('to', 202);
        $message->add_recipient('cc', 203);
        
        $this->assertTrue($message->has_recipient(202));
        $this->assertTrue($message->has_recipient(203));
        $this->assertCount(2, $message->recipients());
        $this->assertCount(1, $message->recipients('to'));
        $this->assertCount(1, $message->recipients('cc'));
        $this->assertCount(0, $message->recipients('bcc'));
        $this->assertEquals($this->user2, $message->recipients()[202]);
        $this->assertEquals($this->user3, $message->recipients()[203]);
        $this->assertEquals($this->user2, $message->recipients('to')[202]);
        $this->assertEquals($this->user3, $message->recipients('cc')[203]);
        $this->assertMessage($message);
    }

    function test_count_index() {
        $message1 = local_mail_message::create(201, 101, 'subject1', 'content1', 0);
        $message1->add_recipient('to', 202);
        $message1->send();
        $message2 = local_mail_message::create(201, 102, 'subject2', 'content2', 0);
        $message2->add_recipient('to', 202);
        $message2->send();
        $other = local_mail_message::create(202, 101, 'subject', 'content', 0);

        $result = local_mail_message::count_index(202, 'inbox');

        $this->assertEquals(2, $result);
    }

    function test_count_index_unread() {
        $message1 = local_mail_message::create(201, 101, 'subject1', 'content1', 0);
        $message1->add_recipient('to', 202);
        $message1->send();
        $message1->set_unread(202, false);
        $message2 = local_mail_message::create(201, 102, 'subject2', 'content2', 0);
        $message2->add_recipient('to', 202);
        $message2->send();
        $message3 = local_mail_message::create(201, 102, 'subject2', 'content2', 0);
        $message3->add_recipient('to', 202);
        $message3->send();

        $result = local_mail_message::count_index_unread(202, 'inbox');

        $this->assertEquals(2, $result);
    }

    function test_create() {
        $result = local_mail_message::create(201, 101, 'subject', 'content', 301, 1234567890);

        $this->assertNotEquals(false, $result->id());
        $this->assertEquals($this->course1, $result->course());
        $this->assertEquals('subject', $result->subject());
        $this->assertEquals('content', $result->content());
        $this->assertEquals(301, $result->format());
        $this->assertEquals(0, $result->reference());
        $this->assertTrue($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals($this->user1, $result->sender());
        $this->assertCount(0, $result->recipients());
        $this->assertCount(0, $result->labels());
        $this->assertMessage($result);
        $this->assertIndex(201, 'drafts', 0, 1234567890, $result->id(), false);
        $this->assertIndex(201, 'course', 101, 1234567890, $result->id(), false);
    }

    function test_delete_course() {
        $label = local_mail_label::create(201, 'name');
        $message1 = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message1->add_recipient('to', 202);
        $message1->add_label($label);
        $message2 = local_mail_message::create(202, 101, 'subject', 'content', 301);
        $other = local_mail_message::create(201, 102, 'subject', 'content', 301);
        $other->add_label($label);

        local_mail_message::delete_course(101);

        $this->assertNotRecords('messages', array('courseid' => 101));
        $this->assertNotRecords('message_users', array('messageid' => $message1->id()));
        $this->assertNotRecords('message_users', array('messageid' => $message1->id()));
        $this->assertNotRecords('message_labels', array('messageid' => $message2->id()));
        $this->assertNotRecords('message_labels', array('messageid' => $message2->id()));
        $this->assertRecords('messages');
        $this->assertRecords('message_users');
        $this->assertRecords('message_labels');
        $this->assertNotIndex(201, 'course', 101, $message1->id());
        $this->assertNotIndex(202, 'course', 101, $message2->id());
    }

    function test_discard() {
        $label = local_mail_label::create(201, 'name');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->add_label($label);
        $other = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $other->add_label($label);

        $message->discard();

        $this->assertNotRecords('messages', array('id' => $message->id()));
        $this->assertNotRecords('message_users', array('messageid' => $message->id()));
        $this->assertNotRecords('message_labels', array('messageid' => $message->id()));
        $this->assertRecords('messages');
        $this->assertRecords('message_users');
        $this->assertRecords('message_labels');
        $this->assertNotIndex(201, 'drafts', 0, $message->id());
        $this->assertNotIndex(201, 'course', 101, $message->id());
    }

    function test_editable() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->add_recipient('to', 202);

        $this->assertTrue($message->editable(201));
        $this->assertFalse($message->editable(202));
        $this->assertFalse($message->editable(203));

        $message->send();

        $this->assertFalse($message->editable(201));
        $this->assertFalse($message->editable(202));
        $this->assertFalse($message->editable(203));
    }

    function test_fetch() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(201, 'label2');
        $label3 = local_mail_label::create(202, 'label3');
        $this->loadRecords('local_mail_messages', array(
            array('id', 'courseid', 'subject',  'content', 'format', 'reference', 'draft', 'time'),
            array( 501,  101,       'subject1', 'content1', 301,      503,         0,       1234567890 ),
            array( 502,  101,       'subject2', 'content2', 301,      501,         1,       1234567891 ),
        ));
        $this->loadRecords('local_mail_message_users', array(
             array('messageid', 'userid', 'role', 'unread', 'starred',  'deleted'),
             array( 501,         201,     'from',  0,        0,          1 ),
             array( 501,         202,     'to',    0,        1,          0 ),
             array( 501,         203,     'cc',    1,        0,          0 ),
             array( 502,         201,     'from',  0,        0,          0 ),
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
        $this->assertEquals(503, $result->reference());
        $this->assertFalse($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals($this->user1, $result->sender());
        $this->assertFalse($result->unread(201));
        $this->assertFalse($result->starred(201));
        $this->assertTrue($result->deleted(201));
        $this->assertCount(1, $result->recipients('to'));
        $this->assertCount(1, $result->recipients('cc'));
        $this->assertCount(0, $result->recipients('bcc'));
        $this->assertEquals($this->user2, $result->recipients('to')[202]);
        $this->assertEquals($this->user3, $result->recipients('cc')[203]);
        $this->assertFalse($result->unread(202));
        $this->assertTrue($result->starred(202));
        $this->assertFalse($result->deleted(202));
        $this->assertTrue($result->unread(203));
        $this->assertFalse($result->starred(203));
        $this->assertFalse($result->deleted(203));
        $this->assertCount(2, $result->labels());
        $this->assertContains($label1, $result->labels());
        $this->assertContains($label2, $result->labels());
    }

    function test_fetch_index() {
        $message1 = local_mail_message::create(201, 101, 'subject1', 'content1', 0);
        $message1->add_recipient('to', 202);
        $message1->send(12345567890);
        $message2 = local_mail_message::create(201, 102, 'subject2', 'content2', 0);
        $message2->add_recipient('to', 202);
        $message2->send(12345567891);
        $other = local_mail_message::create(202, 101, 'subject', 'content', 0);

        $result = local_mail_message::fetch_index(202, 'inbox');

        $this->assertCount(2, $result);
        $this->assertEquals(array_keys($result), array($message2->id(), $message1->id()));
        $this->assertEquals($result[$message1->id()], $message1);
        $this->assertEquals($result[$message2->id()], $message2);
    }

    function test_fetch_many() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(202, 'label2');
        $message1 = local_mail_message::create(201, 101, 'subject1', 'content1', 301);
        $message1->add_recipient('to', 202);
        $message2 = local_mail_message::create(201, 101, 'subject2', 'content2', 302);
        $message2->add_recipient('to', 202);
        $message2->add_recipient('cc', 203);
        $message2->send();
        $message2->add_label($label1);
        $message2->add_label($label2);
        $result = local_mail_message::fetch_many(array($message1->id(), $message2->id()));

        $this->assertCount(2, $result);
        $this->assertEquals(array_keys($result), array($message1->id(), $message2->id()));
        $this->assertEquals($result[$message1->id()], $message1);
        $this->assertEquals($result[$message2->id()], $message2);
    }

    function test_fetch_menu() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(201, 'label2');
        $message1 = local_mail_message::create(201, 101, 'subject1', 'content1', 301);
        $message2 = local_mail_message::create(202, 101, 'subject2', 'content2', 302);
        $message2->add_recipient('to', 201);
        $message2->send();
        $message2->set_unread(201, false);
        $message2->add_label($label1);
        $message2->add_label($label2);
        $message3 = local_mail_message::create(201, 102, 'subject3', 'content3', 303);
        $message3->add_recipient('to', 202);
        $message3->send();
        $message4 = local_mail_message::create(202, 101, 'subject4', 'content4', 304);
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

    function test_forward() {
        $label = local_mail_label::create(202, 'label');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->send();
        $message->add_label($label);

        $result = $message->forward(202, 1234567890);

        $this->assertInstanceOf('local_mail_message', $result);
        $this->assertNotEquals(false, $result->id());
        $this->assertEquals($this->course1, $result->course());
        $this->assertEquals('FW: subject', $result->subject());
        $this->assertEquals('', $result->content());
        $this->assertEquals(0, $result->format());
        $this->assertEquals($message->id(), $result->reference());
        $this->assertTrue($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals($this->user2, $result->sender());
        $this->assertCount(0, $result->recipients());
        $this->assertCount(1, $result->labels());
        $this->assertContains($label, $result->labels());
        $this->assertMessage($result);
        $this->assertIndex(202, 'drafts', 0, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'course', 101, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'label', $label->id(), 1234567890, $result->id(), false);
    }

    function test_remove_label() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(202, 'label2');
        $label3 = local_mail_label::create(202, 'label3');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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

    function test_remove_recipient() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->add_recipient('cc', 203);

        $message->remove_recipient(202);
        
        $this->assertFalse($message->has_recipient(202));
        $this->assertTrue($message->has_recipient(203));
        $this->assertCount(1, $message->recipients());
        $this->assertCount(0, $message->recipients('to'));
        $this->assertCount(1, $message->recipients('cc'));
        $this->assertCount(0, $message->recipients('bcc'));
        $this->assertEquals($this->user3, $message->recipients()[203]);
        $this->assertEquals($this->user3, $message->recipients('cc')[203]);
        $this->assertMessage($message);
    }

    function test_reply() {
        $label = local_mail_label::create(202, 'label');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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
        $this->assertEquals(0, $result->format());
        $this->assertEquals($message->id(), $result->reference());
        $this->assertTrue($result->draft());
        $this->assertEquals(1234567890, $result->time());
        $this->assertEquals($this->user2, $result->sender());
        $this->assertCount(1, $result->recipients());
        $this->assertEquals($this->user1, $result->recipients('to')[201]);
        $this->assertCount(1, $result->labels());
        $this->assertContains($label, $result->labels());
        $this->assertMessage($result);
        $this->assertIndex(202, 'drafts', 0, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'course', 101, 1234567890, $result->id(), false);
        $this->assertIndex(202, 'label', $label->id(), 1234567890, $result->id(), false);
    }

    function test_reply_all() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->add_recipient('to', 202);
        $message->add_recipient('to', 203);
        $message->send();

        $result = $message->reply(202, true);

        $this->assertCount(2, $result->recipients());
        $this->assertEquals($this->user1, $result->recipients('to')[201]);
        $this->assertEquals($this->user3, $result->recipients('cc')[203]);
        $this->assertMessage($result);
    }

    function test_save() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->save(102, 'changed subject', 'changed content', 302, 1234567890);

        $this->assertEquals($this->course2, $message->course());
        $this->assertEquals('changed subject', $message->subject());
        $this->assertEquals('changed content', $message->content());
        $this->assertEquals(302, $message->format());
        $this->assertTrue($message->draft());
        $this->assertEquals(1234567890, $message->time());
        $this->assertMessage($message);
        $this->assertIndex(201, 'drafts', 0, 1234567890, $message->id(), false);
        $this->assertIndex(201, 'course', 102, 1234567890, $message->id(), false);
    }

    function test_send() {
        $label = local_mail_label::create(201, 'label');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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

    function test_send_with_reference() {
        $label = local_mail_label::create(201, 'label');
        $reference = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $reference->add_recipient('to', 202);
        $reference->send();
        $reference->add_label($label);
        $message = $reference->reply(202);

        $message->send();

        $this->assertContains($label, $message->labels());
        $this->assertMessage($message);
        $this->assertIndex(201, 'label', $label->id(), $message->time(), $message->id(), true);
    }

    function test_set_deleted() {
        $label1 = local_mail_label::create(201, 'label1');
        $label2 = local_mail_label::create(202, 'label2');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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

    function test_set_starred() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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

    function test_set_unread() {
        $label = local_mail_label::create(201, 'label');
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
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

    function test_viewable() {
        $message = local_mail_message::create(201, 101, 'subject', 'content', 301);
        $message->add_recipient('to', 202);

        $this->assertTrue($message->viewable(201));
        $this->assertFalse($message->viewable(202));
        $this->assertFalse($message->viewable(203));

        $message->send();

        $this->assertTrue($message->viewable(201));
        $this->assertTrue($message->viewable(202));
        $this->assertFalse($message->viewable(203));
    }
}
