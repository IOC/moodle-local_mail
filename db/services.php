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

$functions = array(
    'local_mail_get_unread_count' => array(
        'classname' => 'local_mail_external',
        'methodname' => 'get_unread_count',
        'classpath' => 'local/mail/externallib.php',
        'description' => 'Get the number of unread messages.',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),
    'local_mail_get_menu' => array(
        'classname' => 'local_mail_external',
        'methodname' => 'get_menu',
        'classpath' => 'local/mail/externallib.php',
        'description' => 'Get the list of courses and labels and the number of unread messages.',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),
    'local_mail_get_index' => array(
        'classname' => 'local_mail_external',
        'methodname' => 'get_index',
        'classpath' => 'local/mail/externallib.php',
        'description' => 'Get a list of messages from the index.',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),
    'local_mail_search_index' => array(
        'classname' => 'local_mail_external',
        'methodname' => 'search_index',
        'classpath' => 'local/mail/externallib.php',
        'description' => 'Search messages from the index.',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),
    'local_mail_get_message' => array(
        'classname' => 'local_mail_external',
        'methodname' => 'get_message',
        'classpath' => 'local/mail/externallib.php',
        'description' => 'Get the contents of a message.',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),
    'local_mail_set_unread' => array(
        'classname' => 'local_mail_external',
        'methodname' => 'set_unread',
        'classpath' => 'local/mail/externallib.php',
        'description' => 'Sets the unread status of a message.',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),
);
