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

if ($hassiteconfig) {
    require_once($CFG->dirroot . '/local/mail/locallib.php');
    $settings = new admin_settingpage('local_mail', get_string('pluginname', 'local_mail'));

    $strmaxfiles = get_string('maxattachments', 'local_mail');
    $strmaxbytes = get_string('maxattachmentsize', 'local_mail');

    $settings->add(new admin_setting_configtext('local_mail/maxfiles', $strmaxfiles, '',
                                                LOCAL_MAIL_MAXFILES, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $sizes = get_max_upload_sizes($CFG->maxbytes);
        $settings->add(new admin_setting_configselect('local_mail/maxbytes', $strmaxbytes, '',
                                                      LOCAL_MAIL_MAXBYTES, $sizes));
    }

    $ADMIN->add('localplugins', $settings);
}
