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


    $settings->add(new admin_setting_heading('mailupdater', get_string('mailupdater', 'local_mail'), get_string('mailupdater', 'local_mail')));

    $settings->add(new admin_setting_configselect('local_mail/cronenabled', new lang_string('cronenabled', 'local_mail'), '',
                                                      0, array(0 => get_string('no'), 1 => get_string('yes'))));

    $settings->add(new admin_setting_configselect('local_mail/cronstart', new lang_string('cronstart', 'local_mail'), '',
                                                   0, range(0, 23)));
    $settings->add(new admin_setting_configselect('local_mail/cronstop', new lang_string('cronstop', 'local_mail'), '',
                                                   24, array_combine(range(1, 24), range(1, 24))));
    $settings->add(new admin_setting_configduration('local_mail/cronduration', new lang_string('cronduration', 'local_mail'), '', 1800, 60));

    $ADMIN->add('localplugins', $settings);
}
