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
require_once($CFG->dirroot.'/local/mail/label.class.php');

class local_mail_label_test extends local_mail_testcase {

    /* 1xx -> users
       4xx -> labels
       5xx -> maessages */

    static public function assertLabel(local_mail_label $label) {
        self::assertRecords('labels', array(
            'id' => $label->id(),
            'userid' => $label->userid(),
            'name' => $label->name(),
            'color' => $label->color(),
        ));
    }

    public function test_create() {
        $label = local_mail_label::create(201, 'name', 'red');

        $this->assertNotEquals(false, $label->id());
        $this->assertEquals(201, $label->userid());
        $this->assertEquals('name', $label->name());
        $this->assertEquals('red', $label->color());
        $this->assertLabel($label);
    }

    public function test_delete() {
        $label = local_mail_label::create(201, 'label', 'red');
        $other = local_mail_label::create(201, 'other', 'green');
        $this->loadRecords('local_mail_message_labels', array(
            array('messageid', 'labelid'),
            array( 501,         $label->id()),
            array( 502,         $label->id()),
            array( 501,         $other->id()),
        ));
        $this->loadRecords('local_mail_index', array(
            array('userid', 'type',  'item',        'time', 'messageid', 'unread'),
            array( 201,     'label',  $label->id(),  1,      501,         0 ),
            array( 201,     'label',  $label->id(),  2,      501,         0 ),
            array( 201,     'label',  $other->id(),  3,      501,         0 ),
        ));

        $label->delete();

        $this->assertNotRecords('labels', array('id' => $label->id()));
        $this->assertNotRecords('message_labels', array('labelid' => $label->id()));
        $this->assertRecords('labels');
        $this->assertRecords('message_labels');
        $this->assertNotIndex(201, 'label', $label->id(), 501);
        $this->assertNotIndex(201, 'label', $label->id(), 501);
        $this->assertIndex(201, 'label', $other->id(), 3, 501, 0);
    }

    public function test_fetch() {
        $this->loadRecords('local_mail_labels', array(
            array('id', 'userid', 'name',   'color'),
            array( 401,  201,     'label1', 'red'),
            array( 402,  201,     'label2', ''),
        ));

        $result = local_mail_label::fetch(401);

        $this->assertInstanceOf('local_mail_label', $result);
        $this->assertEquals(401, $result->id());
        $this->assertEquals(201, $result->userid());
        $this->assertEquals('label1', $result->name());
        $this->assertEquals('red', $result->color());
    }

    public function test_fetch_user() {
        $label1 = local_mail_label::create(201, 'label1', 'red');
        $label2 = local_mail_label::create(201, 'label2', 'green');
        $label3 = local_mail_label::create(202, 'label3', 'blue');

        $result = local_mail_label::fetch_user(201);

        $this->assertCount(2, $result);
        $this->assertContains($label1, $result);
        $this->assertContains($label2, $result);
    }

    public function test_save() {
        $label = local_mail_label::create(201, 'name', 'red');

        $label->save('changed', 'green');

        $this->assertEquals('changed', $label->name());
        $this->assertEquals('green', $label->color());
        $this->assertLabel($label);
    }
}
