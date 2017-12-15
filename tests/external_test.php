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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/local/mail/message.class.php');

class local_mail_external_test extends advanced_testcase {

    private $course1;
    private $course2;
    private $course3;
    private $user1;
    private $user2;
    private $user3;

    public function setUp() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $this->course3 = $generator->create_course(['shortname' => 'C3', 'fullname' => 'Course 3']);
        $this->course2 = $generator->create_course(['shortname' => 'C2', 'fullname' => 'Course 2']);
        $this->course1 = $generator->create_course(['shortname' => 'C1', 'fullname' => 'Course 1']);
        $this->user1 = $generator->create_user(['username' => 'u1', 'lastbane' => 'Afeninas']);
        $this->user2 = $generator->create_user(['username' => 'u2', 'lastname' => 'Buristaki']);
        $this->user3 = $generator->create_user(['username' => 'u3', 'lastname' => 'Combitti']);
        $this->user4 = $generator->create_user(['username' => 'u4', 'lastname' => 'Dupsal']);
        $this->user5 = $generator->create_user(['username' => 'u5', 'lastname' => 'Emferz']);
        $generator->enrol_user($this->user1->id, $this->course1->id);
        $generator->enrol_user($this->user1->id, $this->course2->id);
        $generator->enrol_user($this->user1->id, $this->course3->id);
        $generator->enrol_user($this->user2->id, $this->course1->id);
        $generator->enrol_user($this->user2->id, $this->course2->id);
        $generator->enrol_user($this->user2->id, $this->course3->id);
        $generator->enrol_user($this->user3->id, $this->course1->id);
        $generator->enrol_user($this->user3->id, $this->course2->id);
        $generator->enrol_user($this->user3->id, $this->course3->id);
    }

    public function test_get_unread_count() {
        $this->setUser($this->user3->id);

        $message1 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message1->add_recipient('to', $this->user3->id);
        $message1->send();

        $message2 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message2->add_recipient('to', $this->user3->id);
        $message2->send();
        $message2->set_unread($this->user3->id, false);

        $message3 = local_mail_message::create($this->user2->id, $this->course1->id);
        $message3->add_recipient('to', $this->user3->id);
        $message3->send();

        $message4 = local_mail_message::create($this->user1->id, $this->course2->id);
        $message4->add_recipient('to', $this->user3->id);
        $message4->send();

        $result = local_mail_external::get_unread_count();

        external_api::validate_parameters(local_mail_external::get_unread_count_returns(), $result);

        $this->assertEquals(3, $result);
    }

    public function test_get_menu() {
        course_change_visibility($this->course2->id, false);
        $this->setUser($this->user3->id);
        // Assign teacher role so it can view hidden courses.
        $roleid = key(get_archetype_roles('teacher'));
        role_assign($roleid, $this->user3->id, context_system::instance());

        $label1 = local_mail_label::create($this->user3->id, 'Label 1');
        $label2 = local_mail_label::create($this->user3->id, 'Label 2');

        $message1 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message1->add_recipient('to', $this->user3->id);
        $message1->send();

        $message2 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message2->add_recipient('to', $this->user3->id);
        $message2->send();
        $message2->set_unread($this->user3->id, false);

        $message3 = local_mail_message::create($this->user2->id, $this->course1->id);
        $message3->add_recipient('to', $this->user3->id);
        $message3->send();
        $message3->add_label($label1);

        $message4 = local_mail_message::create($this->user1->id, $this->course2->id);
        $message4->add_recipient('to', $this->user3->id);
        $message4->send();
        $message4->add_label($label1);

        $message5 = local_mail_message::create($this->user3->id, $this->course1->id);

        $message5 = local_mail_message::create($this->user3->id, $this->course2->id);

        $result = local_mail_external::get_menu();

        external_api::validate_parameters(local_mail_external::get_menu_returns(), $result);

        $this->assertEquals(3, $result['unread']);
        $this->assertEquals(2, $result['drafts']);
        $courses = [[
            'id' => $this->course1->id,
            'shortname' => $this->course1->shortname,
            'fullname' => $this->course1->fullname,
            'unread' => 2,
            'visible' => true,
        ], [
            'id' => $this->course3->id,
            'shortname' => $this->course3->shortname,
            'fullname' => $this->course3->fullname,
            'unread' => 0,
            'visible' => true,
        ], [
            'id' => $this->course2->id,
            'shortname' => $this->course2->shortname,
            'fullname' => $this->course2->fullname,
            'unread' => 1,
            'visible' => false,
        ]];
        $this->assertEquals($courses, $result['courses']);
        $labels = [[
            'id' => $label1->id(),
            'name' => $label1->name(),
            'color' => $label1->color(),
            'unread' => 2,
        ], [
            'id' => $label2->id(),
            'name' => $label2->name(),
            'color' => $label2->color(),
            'unread' => 0,
        ]];
        $this->assertEquals($labels, $result['labels']);
    }

    public function test_get_index() {
        $this->setUser($this->user3->id);

        $label1 = local_mail_label::create($this->user3->id, 'Label 1', 'red');
        $label2 = local_mail_label::create($this->user3->id, 'Label 2', 'blue');

        $message1 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message1->add_recipient('to', $this->user3->id);
        $message1->send(1470000001);

        $message2 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message2->save('Subject 2', 'Content 2', FORMAT_HTML, 3);
        $message2->add_recipient('to', $this->user3->id);
        $message2->add_recipient('cc', $this->user4->id);
        $message2->add_recipient('bcc', $this->user5->id);
        $message2->send(1470000002);

        $message3 = local_mail_message::create($this->user2->id, $this->course2->id);
        $message3->save('Subject 3', 'Content 3', FORMAT_HTML, 0);
        $message3->add_recipient('to', $this->user3->id);
        $message3->add_recipient('to', $this->user4->id);
        $message3->send(1470000003);
        $message3->set_unread($this->user3->id, false);
        $message3->set_starred($this->user3->id, true);
        $message3->add_label($label1);

        $message4 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message4->add_recipient('to', $this->user3->id);
        $message4->send(1470000004);
        $message4->add_label($label1);

        $message5 = local_mail_message::create($this->user3->id, $this->course2->id);
        $message5->save('Subject 3', 'Content 3', FORMAT_HTML, 2, 1470000005);

        // Mesages in the inbox of user 3.

        $result = local_mail_external::get_index('inbox', 0, 1, 2);
        external_api::validate_parameters(local_mail_external::get_index_returns(), $result);
        $this->assertEquals($this->index_response(4, [$message3, $message2]), $result);

        // Messages in the course 2 of user 3.

        $result = local_mail_external::get_index('course', $this->course2->id, 0, 0);
        external_api::validate_parameters(local_mail_external::get_index_returns(), $result);
        $this->assertEquals($this->index_response(2, [$message5, $message3]), $result);
    }

    public function test_search_index() {
        $this->setUser($this->user3->id);

        $label1 = local_mail_label::create($this->user3->id, 'Label 1', 'red');
        $label2 = local_mail_label::create($this->user3->id, 'Label 2', 'blue');

        $message1 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message1->save('Subject 1', 'Content 1', FORMAT_HTML, 0);
        $message1->add_recipient('to', $this->user3->id);
        $message1->send(1470000001);
        $message1->set_unread($this->user3->id, false);
        $message1->set_starred($this->user3->id, true);

        $message2 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message2->save('Subject 2 (Test)', 'Content 2', FORMAT_HTML, 3);
        $message2->add_recipient('to', $this->user3->id);
        $message2->add_recipient('cc', $this->user4->id);
        $message2->add_recipient('bcc', $this->user5->id);
        $message2->send(1470000002);
        $message2->set_unread($this->user3->id, false);

        $message3 = local_mail_message::create($this->user2->id, $this->course2->id);
        $message3->save('Subject 3', 'Content 3 (Test)', FORMAT_HTML, 0);
        $message3->add_recipient('to', $this->user3->id);
        $message3->add_recipient('to', $this->user4->id);
        $message3->send(1470000003);
        $message3->set_unread($this->user3->id, false);
        $message3->set_starred($this->user3->id, true);
        $message3->add_label($label1);

        $message4 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message4->save('Subject 4', 'Content 4', FORMAT_HTML, 0);
        $message4->add_recipient('to', $this->user3->id);
        $message4->send(1470000004);
        $message4->add_label($label1);

        $message5 = local_mail_message::create($this->user2->id, $this->course1->id);
        $message5->save('Subject 5', 'Content 5', FORMAT_HTML, 1);
        $message5->add_recipient('to', $this->user3->id);
        $message5->send(1470000004);
        $message5->set_unread($this->user3->id, false);

        $message6 = local_mail_message::create($this->user1->id, $this->course1->id);
        $message6->save('Subject 6', 'Content 6 (Test)', FORMAT_HTML, 0);
        $message6->add_recipient('to', $this->user3->id);
        $message6->add_recipient('bcc', $this->user4->id);
        $message6->send(1470000005);
        $message6->add_label($label1);

        $message7 = local_mail_message::create($this->user1->id, $this->course2->id);
        $message7->save('Subject 7', 'Content 7', FORMAT_HTML, 0);
        $message7->add_recipient('to', $this->user2->id);
        $message7->add_recipient('bcc', $this->user3->id);
        $message7->send(1470000006);

        $message8 = local_mail_message::create($this->user3->id, $this->course2->id);
        $message8->save('Subject 8', 'Content 8', FORMAT_HTML, 2, 1470000007);

        $message9 = local_mail_message::create($this->user1->id, $this->course2->id);
        $message9->save('Subject 9', 'Content 9', FORMAT_HTML, 0);
        $message9->add_recipient('to', $this->user3->id);
        $message9->send(1470000007);
        $message9->set_deleted($this->user3->id, true);

        $message10 = local_mail_message::create($this->user3->id, $this->course2->id);
        $message10->save('Subject 10', 'Content 10', FORMAT_HTML, 0);
        $message10->add_recipient('to', $this->user1->id);
        $message10->send(1470000008);

        // All messages in the inbox.
        $result = local_mail_external::search_index('inbox', null, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message7, $message6, $message5, $message4, $message3, $message2, $message1]);
        $this->assertEquals($expected, $result);

        // Some messages in the inbox.
        $result = local_mail_external::search_index('inbox', null, ['limit' => 3]);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message7, $message6, $message5]);
        $this->assertEquals($expected, $result);

        // Some messages in the inbox, older than the message 5.
        $result = local_mail_external::search_index('inbox', null, ['beforeid' => $message5->id(), 'limit' => 3]);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message4, $message3, $message2]);
        $this->assertEquals($expected, $result);

        // Some messages in the inbox, newer than the message 4.
        $result = local_mail_external::search_index('inbox', null, ['afterid' => $message4->id(), 'limit' => 2]);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message6, $message5]);
        $this->assertEquals($expected, $result);

        // Unread messages in the inbox.
        $result = local_mail_external::search_index('inbox', null, ['unread' => true]);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message7, $message6, $message4]);
        $this->assertEquals($expected, $result);

        // Messages with attachments in the inbox.
        $result = local_mail_external::search_index('inbox', null, ['attachments' => true]);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message5, $message2]);
        $this->assertEquals($expected, $result);

        // Messages older than a timestamp in the inbox.
        $result = local_mail_external::search_index('inbox', null, ['time' => 1470000003]);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message3, $message2, $message1]);
        $this->assertEquals($expected, $result);

        // Messages in the inbox that contain "test".
        $result = local_mail_external::search_index('inbox', null, ['content' => 'test']);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message6, $message3, $message2]);
        $this->assertEquals($expected, $result);

        // Messages in the inbox send by "Buristaki".
        $result = local_mail_external::search_index('inbox', null, ['sender' => 'Buristaki']);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message5, $message3]);
        $this->assertEquals($expected, $result);

        // Messages sin the inbox send to "Dupsal".
        $result = local_mail_external::search_index('inbox', null, ['recipients' => 'Dupsal']);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(7, [$message3, $message2]);
        $this->assertEquals($expected, $result);

        // Starred messages.
        $result = local_mail_external::search_index('starred', null, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(2, [$message3, $message1]);
        $this->assertEquals($expected, $result);

        // Drafts.
        $result = local_mail_external::search_index('drafts', null, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(1, [$message8]);
        $this->assertEquals($expected, $result);

        // Sent messages.
        $result = local_mail_external::search_index('sent', null, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(1, [$message10]);
        $this->assertEquals($expected, $result);

        // Messages in trash.
        $result = local_mail_external::search_index('trash', null, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(1, [$message9]);
        $this->assertEquals($expected, $result);

        // Messages in course 2.
        $result = local_mail_external::search_index('course', $this->course2->id, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(4, [$message10, $message8, $message7, $message3]);
        $this->assertEquals($expected, $result);

        // Messages in course 3.
        $result = local_mail_external::search_index('course', $this->course3->id, []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(0, []);
        $this->assertEquals($expected, $result);

        // Messages in label 1.
        $result = local_mail_external::search_index('label', $label1->id(), []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(3, [$message6, $message4, $message3]);
        $this->assertEquals($expected, $result);

        // Messages in label 2.
        $result = local_mail_external::search_index('label', $label2->id(), []);
        external_api::validate_parameters(local_mail_external::search_index_returns(), $result);
        $expected = $this->index_response(0, []);
        $this->assertEquals($expected, $result);
    }

    public function test_get_message() {
        $this->setUser($this->user1->id);

        $label1 = local_mail_label::create($this->user1->id, 'Label 1', 'red');
        $label2 = local_mail_label::create($this->user1->id, 'Label 2', 'blue');
        $label3 = local_mail_label::create($this->user2->id, 'Label 3', 'green');

        // Message from the user with various recipients, attachments and labels.

        $message = local_mail_message::create($this->user1->id, $this->course1->id);
        $message->save('Subject 1', 'Content 1', FORMAT_HTML, 3);
        $message->add_recipient('to', $this->user2->id);
        $message->add_recipient('cc', $this->user3->id);
        $message->add_recipient('bcc', $this->user4->id);
        $this->create_attachment($message, 'file1.txt', 'First file');
        $this->create_attachment($message, 'file2.png', 'Second file');
        $this->create_attachment($message, 'file3.pdf', 'Third file');
        $message->send();
        $message->set_starred($this->user1->id, true);
        $message->add_label($label1);
        $message->add_label($label2);
        $message->add_label($label3);

        $result = local_mail_external::get_message($message->id());

        external_api::validate_parameters(local_mail_external::get_message_returns(), $result);

        list($expectedcontent, $expectedformat) = $this->format_text($message);
        $this->assertEquals([
            'id' => $message->id(),
            'subject' => $message->subject(),
            'content' => $expectedcontent,
            'format' => $expectedformat,
            'draft' => false,
            'time' => $message->time(),
            'unread' => false,
            'starred' => true,
            'course' => [
                'id' => $this->course1->id,
                'shortname' => $this->course1->shortname,
            ],
            'sender' => [
                'id' => $this->user1->id,
                'fullname' => fullname($this->user1),
                'pictureurl' => $this->picture_url($this->user1),
            ],
            'recipients' => [[
                'type' => 'to',
                'id' => $this->user2->id,
                'fullname' => fullname($this->user2),
                'pictureurl' => $this->picture_url($this->user2),
            ], [
                'type' => 'cc',
                'id' => $this->user3->id,
                'fullname' => fullname($this->user3),
                'pictureurl' => $this->picture_url($this->user3),
            ], [
                'type' => 'bcc',
                'id' => $this->user4->id,
                'fullname' => fullname($this->user4),
                'pictureurl' => $this->picture_url($this->user4),
            ]],
            'attachments' => [[
                'filename' => 'file1.txt',
                'filesize' => 10,
                'mimetype' => 'text/plain',
                'fileurl' => $this->attachment_url($message, 'file1.txt'),
            ], [
                'filename' => 'file2.png',
                'filesize' => 11,
                'mimetype' => 'image/png',
                'fileurl' => $this->attachment_url($message, 'file2.png'),
            ], [
                'filename' => 'file3.pdf',
                'filesize' => 10,
                'mimetype' => 'application/pdf',
                'fileurl' => $this->attachment_url($message, 'file3.pdf'),
            ]],
            'references' => [],
            'labels' => [[
                'id' => $label1->id(),
                'name' => $label1->name(),
                'color' => $label1->color(),
            ], [
                'id' => $label2->id(),
                'name' => $label2->name(),
                'color' => $label2->color(),
            ]],
        ], $result);

        // Message to the user with a BCC recipient that is hidden from the user.

        $message = local_mail_message::create($this->user2->id, $this->course1->id);
        $message->save('Subject 2', 'Content 2', FORMAT_MOODLE, 0);
        $message->add_recipient('to', $this->user1->id);
        $message->add_recipient('bcc', $this->user3->id);
        $message->send();

        $result = local_mail_external::get_message($message->id());

        external_api::validate_parameters(local_mail_external::get_message_returns(), $result);

        list($expectedcontent, $expectedformat) = $this->format_text($message);
        $this->assertEquals([
            'id' => $message->id(),
            'subject' => $message->subject(),
            'content' => $expectedcontent,
            'format' => $expectedformat,
            'draft' => false,
            'time' => $message->time(),
            'unread' => true,
            'starred' => false,
            'course' => [
                'id' => $this->course1->id,
                'shortname' => $this->course1->shortname,
            ],
            'sender' => [
                'id' => $this->user2->id,
                'fullname' => fullname($this->user2),
                'pictureurl' => $this->picture_url($this->user2),
            ],
            'recipients' => [[
                'type' => 'to',
                'id' => $this->user1->id,
                'fullname' => fullname($this->user1),
                'pictureurl' => $this->picture_url($this->user1),
            ]],
            'attachments' => [],
            'references' => [],
            'labels' => [],
        ], $result);

        // Draft from the user.

        $message = local_mail_message::create($this->user1->id, $this->course1->id);
        $message->save('Subject 3', 'Content 3', FORMAT_HTML, 0);
        $message->add_recipient('to', $this->user2->id);

        $result = local_mail_external::get_message($message->id());

        external_api::validate_parameters(local_mail_external::get_message_returns(), $result);

        list($expectedcontent, $expectedformat) = $this->format_text($message);
        $this->assertEquals([
            'id' => $message->id(),
            'subject' => $message->subject(),
            'content' => $expectedcontent,
            'format' => $expectedformat,
            'draft' => true,
            'time' => $message->time(),
            'unread' => false,
            'starred' => false,
            'course' => [
                'id' => $this->course1->id,
                'shortname' => $this->course1->shortname,
            ],
            'sender' => [
                'id' => $this->user1->id,
                'fullname' => fullname($this->user1),
                'pictureurl' => $this->picture_url($this->user1),
            ],
            'recipients' => [[
                'type' => 'to',
                'id' => $this->user2->id,
                'fullname' => fullname($this->user2),
                'pictureurl' => $this->picture_url($this->user2),
            ]],
            'attachments' => [],
            'references' => [],
            'labels' => [],
        ], $result);

        // Message with references.

        $message1 = local_mail_message::create($this->user2->id, $this->course1->id);
        $message1->save('Subject 4', 'Content 4', FORMAT_HTML, 3);
        $message1->add_recipient('to', $this->user3->id);
        $message1->send(make_timestamp(2016, 12, 30, 14, 25, 59));

        $message2 = $message1->forward($this->user3->id);
        $message2->add_recipient('to', $this->user4->id);
        $message2->send(make_timestamp(2016, 12, 31, 9, 55, 17));

        $message3 = $message2->reply($this->user4->id);
        $this->create_attachment($message3, 'file4.txt', 'Fourth file');
        $this->create_attachment($message3, 'file5.png', 'Fifth file');
        $message3->send(make_timestamp(2016, 12, 31, 14, 23, 55));

        $message4 = $message3->forward($this->user3->id);
        $message4->add_recipient('to', $this->user1->id);
        $message4->send(make_timestamp(2017, 1, 2, 20, 33, 41));

        $result = local_mail_external::get_message($message4->id());

        external_api::validate_parameters(local_mail_external::get_message_returns(), $result);

        list($expectedcontent1, $expectedformat1) = $this->format_text($message1);
        list($expectedcontent2, $expectedformat2) = $this->format_text($message2);
        list($expectedcontent3, $expectedformat3) = $this->format_text($message3);
        list($expectedcontent4, $expectedformat4) = $this->format_text($message4);
        $this->assertEquals([
            'id' => $message4->id(),
            'subject' => $message4->subject(),
            'content' => $expectedcontent4,
            'format' => $expectedformat4,
            'draft' => false,
            'time' => $message4->time(),
            'unread' => true,
            'starred' => false,
            'course' => [
                'id' => $this->course1->id,
                'shortname' => $this->course1->shortname,
            ],
            'sender' => [
                'id' => $this->user3->id,
                'fullname' => fullname($this->user3),
                'pictureurl' => $this->picture_url($this->user3),
            ],
            'recipients' => [[
                'type' => 'to',
                'id' => $this->user1->id,
                'fullname' => fullname($this->user1),
                'pictureurl' => $this->picture_url($this->user1),
            ]],
            'attachments' => [],
            'references' => [[
                'id' => $message3->id(),
                'subject' => $message3->subject(),
                'content' => $expectedcontent3,
                'format' => $expectedformat3,
                'time' => $message3->time(),
                'sender' => [
                    'id' => $this->user4->id,
                    'fullname' => fullname($this->user4),
                    'pictureurl' => $this->picture_url($this->user4),
                ],
                'attachments' => [[
                    'filename' => 'file4.txt',
                    'filesize' => 11,
                    'mimetype' => 'text/plain',
                    'fileurl' => $this->attachment_url($message3, 'file4.txt'),
                ], [
                    'filename' => 'file5.png',
                    'filesize' => 10,
                    'mimetype' => 'image/png',
                    'fileurl' => $this->attachment_url($message3, 'file5.png'),
                ]],
            ], [
                'id' => $message2->id(),
                'subject' => $message2->subject(),
                'content' => $expectedcontent2,
                'format' => $expectedformat2,
                'time' => $message2->time(),
                'sender' => [
                    'id' => $this->user3->id,
                    'fullname' => fullname($this->user3),
                    'pictureurl' => $this->picture_url($this->user3),
                ],
                'attachments' => [],
            ], [
                'id' => $message1->id(),
                'subject' => $message1->subject(),
                'content' => $expectedcontent1,
                'format' => $expectedformat1,
                'time' => $message1->time(),
                'sender' => [
                    'id' => $this->user2->id,
                    'fullname' => fullname($this->user2),
                    'pictureurl' => $this->picture_url($this->user2),
                ],
                'attachments' => [],
            ]],
            'labels' => [],
        ], $result);

        // Draft to the user (no permission).

        $message = local_mail_message::create($this->user2->id, $this->course1->id);
        $message->save('Subject 4', 'Content 4', FORMAT_HTML, 0);
        $message->add_recipient('to', $this->user1->id);

        try {
            $exception = null;
            local_mail_external::get_message($message->id());
        } catch (moodle_exception $exception) {
            $this->assertTrue(true);
        } finally {
            $this->assertEquals(new moodle_exception('invalidmessage', 'local_mail'), $exception);
        }

        // Invalid message.

        try {
            $exception = null;
            local_mail_external::get_message(-1);
        } catch (moodle_exception $exception) {
            $this->assertTrue(true);
        } finally {
            $this->assertEquals(new moodle_exception('invalidmessage', 'local_mail'), $exception);
        }
    }

    public function test_set_unread() {
        $this->setUser($this->user1->id);

        // Message from the user.

        $message = local_mail_message::create($this->user1->id, $this->course1->id);
        $message->save('Subject 1', 'Content 1', FORMAT_HTML);
        $message->add_recipient('to', $this->user2->id);
        $message->send();

        $result = local_mail_external::set_unread($message->id(), true);
        $this->assertNull($result);
        $message = local_mail_message::fetch($message->id());
        $this->assertTrue($message->unread($this->user1->id));

        $result = local_mail_external::set_unread($message->id(), false);
        $this->assertNull($result);
        $message = local_mail_message::fetch($message->id());
        $this->assertFalse($message->unread($this->user1->id));

        // Message sent to the user.

        $message = local_mail_message::create($this->user2->id, $this->course1->id);
        $message->save('Subject 2', 'Content 2', FORMAT_HTML);
        $message->add_recipient('to', $this->user1->id);
        $message->send();

        $result = local_mail_external::set_unread($message->id(), '0');
        $this->assertNull($result);
        $message = local_mail_message::fetch($message->id());
        $this->assertFalse($message->unread($this->user1->id));

        $result = local_mail_external::set_unread($message->id(), '1');
        $this->assertNull($result);
        $message = local_mail_message::fetch($message->id());
        $this->assertTrue($message->unread($this->user1->id));

        // Draft to the user (no permission).

        $message = local_mail_message::create($this->user2->id, $this->course1->id);
        $message->save('Subject 2', 'Content 2', FORMAT_HTML);
        $message->add_recipient('to', $this->user1->id);

        try {
            $exception = null;
            local_mail_external::set_unread($message->id(), '0');
        } catch (moodle_exception $exception) {
            $this->assertTrue(true);
        } finally {
            $this->assertEquals(new moodle_exception('invalidmessage', 'local_mail'), $exception);
        }

        // Invalid message.

        try {
            $exception = null;
            local_mail_external::set_unread(-1, '1');
        } catch (moodle_exception $exception) {
            $this->assertTrue(true);
        } finally {
            $this->assertEquals(new moodle_exception('invalidmessage', 'local_mail'), $exception);
        }
    }

    private function attachment_url($message, $filename) {
        $context = context_course::instance($message->course()->id);
        $url = moodle_url::make_webservice_pluginfile_url($context->id, 'local_mail', 'message', $message->id(), '/', $filename);
        return $url->out(false);
    }

    private function create_attachment($message, $filename, $content) {
        $fs = get_file_storage();
        $record = [
            'contextid' => context_course::instance($message->course()->id)->id,
            'component' => 'local_mail',
            'filearea' => 'message',
            'itemid' => $message->id(),
            'filepath' => '/',
            'filename' => $filename,
        ];
        $fs->create_file_from_string($record, $content);
    }

    private function format_text($message) {
        $context = context_course::instance($message->course()->id);
        return external_format_text($message->content(), $message->format(), $context->id, 'local_mail', 'message', $message->id());
    }

    private function picture_url($user) {
        global $PAGE;
        $userpicture = new user_picture($user);
        $userpicture->size = 1;
        return $userpicture->get_url($PAGE)->out(false);
    }

    private function index_response($totalcount, array $messages) {
        global $USER;

        $result = [
            'totalcount' => $totalcount,
            'messages' => [],
        ];

        foreach ($messages as $message) {
            $sender = [
                'id' => $message->sender()->id,
                'fullname' => fullname($message->sender()),
                'pictureurl' => $this->picture_url($message->sender()),
            ];
            $recipients = [];
            foreach (['to', 'cc'] as $type) {
                foreach ($message->recipients($type) as $user) {
                    $userpicture = new user_picture($user);
                    $userpicture->size = 1;
                    $recipients[] = [
                        'type' => $type,
                        'id' => $user->id,
                        'fullname' => fullname($user),
                        'pictureurl' => $this->picture_url($user),
                    ];
                }
            }
            $labels = [];
            foreach ($message->labels($USER->id) as $label) {
                $labels[] = [
                    'id' => $label->id(),
                    'name' => $label->name(),
                    'color' => $label->color(),
                ];
            }
            $result['messages'][] = [
                'id' => $message->id(),
                'subject' => $message->subject(),
                'attachments' => $message->attachments(true),
                'draft' => $message->draft(),
                'time' => $message->time(),
                'unread' => $message->unread($USER->id),
                'starred' => $message->starred($USER->id),
                'course' => [
                    'id' => $message->course()->id,
                    'shortname' => $message->course()->shortname,
                ],
                'sender' => $sender,
                'recipients' => $recipients,
                'labels' => $labels,
            ];
        }

        return $result;
    }
}
