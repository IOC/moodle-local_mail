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
require_once('compose_form.php');
require_once('recipients_selector.php');

$messageid = required_param('m', PARAM_INT);
$remove = optional_param_array('remove', false, PARAM_INT);

// Fetch message.

$message = local_mail_message::fetch($messageid);
if (!$message or !$message->editable($USER->id)) {
    print_error('local_mail', 'invalidmessage');
}

// Fetch references.

$references = $message->references();

// Set up page.

$url = new moodle_url('/local/mail/compose.php');
$url->param('m', $message->id());
local_mail_setup_page($message->course(), $url);

// Remove recipients.

if ($remove) {
    require_sesskey();
    $message->remove_recipient(key($remove));
}

// Set up form.

$data = array();
$customdata = array();
$customdata['message'] = $message;
$customdata['context'] = $PAGE->context;

$mform = new mail_compose_form($url, $customdata);

$draftareaid = file_get_submitted_draft_itemid('message');
$content = file_prepare_draft_area($draftareaid, $PAGE->context->id,
                                   'local_mail', 'message', $message->id(),
                                   mail_compose_form::file_options(),
                                   $message->content());
$format = $message->format() >= 0 ? $message->format() : editors_get_preferred_format();

$data['course'] = $message->course()->id;
$data['subject'] = $message->subject();
$data['content']['format'] = $format;
$data['content']['text'] = $content;
$data['content']['itemid'] = $draftareaid;
$data['attachments'] = $draftareaid;
$mform->set_data($data);

// Process form.

if ($data = $mform->get_data()) {
    $fs = get_file_storage();

    // Discard message.
    if (!empty($data->discard)) {
        $fs->delete_area_files($PAGE->context->id, 'local_mail', 'message', $message->id());
        $message->discard();
        $params = array('t' => 'course', 'c' => $message->course()->id);
        $url = new moodle_url('/local/mail/view.php', $params);
        redirect($url);
    }

    $content = file_save_draft_area_files($data->content['itemid'], $PAGE->context->id,
                                          'local_mail', 'message', $message->id(),
                                          mail_compose_form::file_options(),
                                          $data->content['text']);

    $files = $fs->get_area_files($PAGE->context->id, 'local_mail', 'message', $message->id(), 'filename', false);

    $message->save(trim($data->subject), $content, $data->content['format'], count($files));

    // Select recipients.
    if (!empty($data->recipients)) {
        $url = new moodle_url('/local/mail/recipients.php', array('m' => $message->id()));
        redirect($url);
    }

    // Save message.
    if (!empty($data->save)) {
        $url = new moodle_url('/local/mail/view.php', array('t' => 'drafts'));
        redirect($url);
    }

    // Send message.
    if (!empty($data->send)) {
        $message->send();
        $params = array('t' => 'course', 'c' => $message->course()->id);
        $url = new moodle_url('/local/mail/view.php', $params);
        local_mail_send_notifications($message);
        redirect($url);
    }
}

// Display page.

echo $OUTPUT->header();
$mform->display();
$mailoutput = $PAGE->get_renderer('local_mail');

// Recipients form ajax.
echo $mailoutput->recipientsform($message->course()->id, $message->sender()->id);
$PAGE->requires->js('/local/mail/recipients.js');
$PAGE->requires->strings_for_js(array(
    'emptyrecipients',
    'shortaddto',
    'shortaddcc',
    'shortaddbcc',
    'addrecipients',
    'applychanges',
    'notingroup'
    ), 'local_mail');
if (!empty($references)) {
    echo $mailoutput->references($references, true);
}

echo $OUTPUT->footer();
