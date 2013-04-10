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

require_once('../../config.php');
require_once('locallib.php');
require_once('create_form.php');

$courseid = optional_param('c', $SITE->id, PARAM_INT);

// Setup page

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'error');
}
$url = new moodle_url('/local/mail/create.php');
local_mail_setup_page($course, $url);

// Create message

if ($course->id != $SITE->id) {
    require_sesskey();
    $message = local_mail_message::create($USER->id, $course->id);
    $params = array('m' => $message->id());
    $url = new moodle_url('/local/mail/compose.php', $params);
    redirect($url);
}

// Setup form

$courses = local_mail_get_my_courses();
$customdata = array('courses' => $courses);
$mform = new local_mail_create_form($url, $customdata);
$mform->get_data();

// Display page

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
