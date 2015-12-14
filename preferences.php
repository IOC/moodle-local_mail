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
 * @copyright  Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/local/mail/locallib.php');
require_once($CFG->dirroot.'/local/mail/preferences_form.php');

$url = new moodle_url('/local/mail/preferences.php');
$viewurl = new moodle_url('/local/mail/view.php', array('t' => 'inbox'));

local_mail_setup_page($SITE, new moodle_url($url));
$title = get_string('preferences');

$prefs = new stdClass;
$prefs->markasread  = get_user_preferences('local_mail_markasread', 0);

$form = new local_mail_preferences_form($url);
$form->set_data($prefs);

if ($form->is_cancelled()) {
    redirect($viewurl);
} else if ($form->is_submitted() && $form->is_validated() && confirm_sesskey()) {
    $data = $form->get_data();

    if (!isset($data->markasread)) {
        $data->markasread = '0';
    }
    set_user_preference('local_mail_markasread', $data->markasread);

    redirect($viewurl, get_string('changessaved'), 1);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $OUTPUT->box_start('generalbox boxaligncenter');
$form->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();