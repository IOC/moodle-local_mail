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

require_once('../../config.php');
require_once('locallib.php');
require_once('recipients_selector.php');

$messageid = required_param('m', PARAM_INT);

// Fetch message.

$message = local_mail_message::fetch($messageid);
if (!$message or !$message->editable($USER->id)) {
    print_error('invalidmessage', 'local_mail');
}

// Set up page.

$params = array('m' => $messageid);
$url = new moodle_url('/local/mail/recipients.php', $params);
$activeurl = new moodle_url('/local/mail/compose.php', $params);
local_mail_setup_page($message->course(), $url);
navigation_node::override_active_url($activeurl);

// Check group.

$groupid = groups_get_course_group($COURSE, true);

if (!$groupid and $COURSE->groupmode == SEPARATEGROUPS and
    !has_capability('moodle/site:accessallgroups', $PAGE->context)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('notingroup', 'local_mail'));
    echo $OUTPUT->continue_button($activeurl);
    echo $OUTPUT->footer();
    exit;
}

// Set up selector.

$options = array('courseid' => $COURSE->id, 'groupid' => $groupid);
$participants = new mail_recipients_selector('recipients', $options);
$participants->exclude(array_keys($message->recipients()));
$participants->exclude(array($message->sender()->id));

// Process data.

if ($data = data_submitted()) {
    require_sesskey();

    // Cancel.
    if (!empty($data->cancel)) {
        $url = new moodle_url('/local/mail/compose.php', array('m' => $messageid));
        redirect($url);
    }

    // Add.
    $userids = array_keys($participants->get_selected_users());
    if (!empty($data->addto)) {
        foreach ($userids as $userid) {
            $message->add_recipient('to', $userid);
        }
    } else if (!empty($data->addcc)) {
        foreach ($userids as $userid) {
            $message->add_recipient('cc', $userid);
        }
    } else if (!empty($data->addbcc)) {
        foreach ($userids as $userid) {
            $message->add_recipient('bcc', $userid);
        }
    }

    $url = new moodle_url('/local/mail/compose.php', array('m' => $messageid));
    redirect($url);
}

// Display page.

echo $OUTPUT->header();

echo $OUTPUT->container_start('mail-recipients');

echo $OUTPUT->heading(get_string('addrecipients', 'local_mail'), 2);

groups_print_course_menu($COURSE, $PAGE->url);

echo html_writer::start_tag('form', array('method' => 'post', 'action' => $url));

$participants->display();

echo $OUTPUT->container_start('buttons');

$label = get_string('addto', 'local_mail');
$attributes = array('type' => 'submit', 'name' => 'addto', 'value' => $label);
echo html_writer::empty_tag('input', $attributes);

$label = get_string('addcc', 'local_mail');
$attributes = array('type' => 'submit', 'name' => 'addcc', 'value' => $label);
echo html_writer::empty_tag('input', $attributes);

$label = get_string('addbcc', 'local_mail');
$attributes = array('type' => 'submit', 'name' => 'addbcc', 'value' => $label);
echo html_writer::empty_tag('input', $attributes);

$label = get_string('cancel');
$attributes = array('type' => 'submit', 'name' => 'cancel', 'value' => $label);
echo html_writer::empty_tag('input', $attributes);

echo html_writer::empty_tag('input', array(
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
));

echo $OUTPUT->container_end();

echo html_writer::end_tag('form');

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
