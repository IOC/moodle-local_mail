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

require_once('../../config.php');
require_once('locallib.php');
require_once('create_form.php');

$courseid = optional_param('c', $SITE->id, PARAM_INT);
$recipient = optional_param('r', false, PARAM_INT);
$recipients = optional_param('rs', '', PARAM_SEQUENCE);
$role = optional_param('local_mail_role', 0, PARAM_INT);

// Setup page.

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'error');
}
$url = new moodle_url('/local/mail/create.php');
local_mail_setup_page($course, $url);
$context = context_course::instance($course->id);

// Create message.

if ($course->id != $SITE->id and has_capability('local/mail:usemail', $context)) {
    require_sesskey();
    $message = local_mail_message::create($USER->id, $course->id);
    if ($recipients) {
        local_mail_add_recipients($message, explode(',', $recipients), $role);
    } else if (local_mail_valid_recipient($recipient)) {
        $message->add_recipient('to', $recipient);
    }
    $params = array('m' => $message->id());
    $url = new moodle_url('/local/mail/compose.php', $params);
    redirect($url);
}

// Setup form.

$courses = local_mail_get_my_courses();
$customdata = array('courses' => $courses);
$mform = new local_mail_create_form($url, $customdata);
$mform->get_data();

// Display page.

echo $OUTPUT->header();
if ($courses) {
    $mform->display();
} else {
    echo $OUTPUT->notification(get_string('cannotcompose', 'local_mail'));
}
echo $OUTPUT->footer();
