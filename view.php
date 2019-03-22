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
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @author     Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('labels_form.php');
require_once('label_form.php');

$type         = required_param('t', PARAM_ALPHA);
$messageid    = optional_param('m', 0, PARAM_INT);
$courseid     = optional_param('c', 0, PARAM_INT);
$labelid      = optional_param('l', 0, PARAM_INT);
$delete       = optional_param('delete', false, PARAM_ALPHA);
$discard      = optional_param('discard', false, PARAM_ALPHA);
$hide         = optional_param('hide', false, PARAM_BOOL);
$emptytrash   = optional_param('emptytrash', false, PARAM_BOOL);
$confirmmsg   = optional_param('confirmmsg', false, PARAM_BOOL);
$forward      = optional_param('forward', false, PARAM_BOOL);
$offset       = optional_param('offset', 0, PARAM_INT);
$nextpage     = optional_param('nextpage', false, PARAM_BOOL);
$prevpage     = optional_param('prevpage', false, PARAM_BOOL);
$reply        = optional_param('reply', false, PARAM_BOOL);
$replyall     = optional_param('replyall', false, PARAM_BOOL);
$replysend    = optional_param('replysend', false, PARAM_BOOL);
$replysendall = optional_param('replysendall', false, PARAM_BOOL);
$starred      = optional_param('starred', false, PARAM_INT);
$msgs         = optional_param_array('msgs', array(), PARAM_INT);
$read         = optional_param('read', false, PARAM_ALPHA);
$unread       = optional_param('unread', false, PARAM_ALPHA);
$perpage      = optional_param('perpage', false, PARAM_INT);
$assignlbl    = optional_param('assignlbl', false, PARAM_BOOL);
$editlbl      = optional_param('editlbl', false, PARAM_BOOL);
$removelbl    = optional_param('removelbl', false, PARAM_BOOL);
$confirmlbl   = optional_param('confirmlbl', false, PARAM_BOOL);
$goback       = optional_param('goback', false, PARAM_BOOL);
$downloadall  = optional_param('downloadall', false, PARAM_BOOL);

$url = new moodle_url('/local/mail/view.php', array('t' => $type));
$offset = max(0, $offset);

if ($goback) {
    $messageid = 0;
}
if ($type == 'course') {
    $url->param('c', $courseid);
}
if ($type == 'label') {
    $url->param('l', $labelid);
}

if ($removelbl) {
    require_sesskey();
    $label = local_mail_label::fetch($labelid);
    if (!$label or $label->userid() != $USER->id) {
        print_error('invalidlabel', 'local_mail');
    }

    $courseid = $courseid ?: $SITE->id;

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourse', 'error');
    }

    // Check whether user can use mail in that course.
    if ($course->id != $SITE->id) {
        $context = context_course::instance($course->id);
        require_capability('local/mail:usemail', $context);
    }

    local_mail_setup_page($course, $url);

    if ($confirmlbl) {
        if ($label->userid() != $USER->id) {
            print_error('invalidlabel', 'local_mail');
        }
        $label->delete();
        $url->param('t', 'inbox');
        $url->remove_params(array('l'));
        redirect($url);
    } else {
        echo $OUTPUT->header();
        $cancel = clone $url;
        $url->param('confirmlbl', '1');
        $url->param('removelbl', '1');

        echo $OUTPUT->confirm(get_string('labeldeleteconfirm', 'local_mail', $label->name()), $url, $cancel);
        echo $OUTPUT->footer();
        die;
    }
} else if ($editlbl) {
    require_sesskey();
    $label = local_mail_label::fetch($labelid);
    if (!$label or $label->userid() != $USER->id) {
        print_error('invalidlabel', 'local_mail');
    }

    $courseid = $courseid ?: $SITE->id;

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourse', 'error');
    }

    // Check whether user can use mail in that course.
    if ($course->id != $SITE->id) {
        $context = context_course::instance($course->id);
        require_capability('local/mail:usemail', $context);
    }

    $url->param('offset', $offset);
    local_mail_setup_page($course, $url);

    // Set up form.
    $customdata = array();
    $colors = local_mail_label::valid_colors();
    $customdata["editlbl"] = $editlbl;
    $customdata["offset"] = $offset;
    $customdata["colors"] = array();
    $customdata["colors"][''] = get_string('nocolor', 'local_mail');
    foreach ($colors as $color) {
        $customdata["colors"][$color] = $color;
    }
    $customdata['l'] = $label->id();
    $customdata['labelname'] = $label->name();
    $customdata['labelcolor'] = $label->color();

    // Create form.
    $mform = new mail_label_form($url, $customdata);
    $mform->set_data($customdata);

    if ($data = $mform->get_data()) {
        if (isset($data->submitbutton)) {
            $data->labelname = trim(clean_param($data->labelname, PARAM_TEXT));
            $labels = local_mail_label::fetch_user($USER->id);
            $repeatedname = false;
            foreach ($labels as $lbl) {
                $repeatedname = $repeatedname || (($lbl->name() === $data->labelname) and ($lbl->id() != $labelid));
            }
            if (!$repeatedname and $data->labelname and (!$data->labelcolor or in_array($data->labelcolor, $colors))) {
                $label->save($data->labelname, $data->labelcolor);
            }
        }
        redirect($url);
    }

    // Display page.

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

} else if ($assignlbl) {
    $courseid = $courseid ?: $SITE->id;

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourse', 'error');
    }

    // Check whether user can use mail in that course.
    if ($course->id != $SITE->id) {
        $context = context_course::instance($course->id);
        require_capability('local/mail:usemail', $context);
    }

    $url->param('offset', $offset);
    local_mail_setup_page($course, $url);

    // Check whether there are messages to assign or not.
    if (!$messageid and empty($msgs)) {
        echo $OUTPUT->header();
        echo html_writer::tag('p', get_string('noselectedmessages', 'local_mail'), array('class' => 'box errorbox'));
        $continuebutton = new single_button($url, get_string('continue'));
        $continuebutton->class = 'continuebutton';
        echo $OUTPUT->render($continuebutton);
        echo $OUTPUT->footer();
        die;
    }

    // Set up form.
    $customdata = array();
    $customdata['assignlbl'] = $assignlbl;
    $customdata['t'] = $type;
    $customdata['c'] = $courseid;
    $customdata['offset'] = $offset;
    $customdata['colors'] = array();
    $colors = local_mail_label::valid_colors();
    if ($messageid) {
        $customdata['m'] = $messageid;
    } else {
        $customdata['msgs'] = $msgs;
    }
    $customdata['colors'][''] = get_string('nocolor', 'local_mail');
    foreach ($colors as $color) {
        $customdata['colors'][$color] = $color;
    }

    $labels = local_mail_label::fetch_user($USER->id);
    if ($messageid) {
        $message = local_mail_message::fetch($messageid);
        if ($message->deleted($USER->id)) {
            print_error('invalidmessage', 'local_mail');
        }
    } else {
        $messages = local_mail_message::fetch_many($msgs);
        foreach ($messages as $message) {
            if ($message->deleted($USER->id)) {
                print_error('invalidmessage', 'local_mail');
            }
        }
    }
    $customdata['labelids'] = array();
    if ($labels) {
        foreach ($labels as $label) {
            array_push($customdata['labelids'], $label->id());
            $customdata['labelname'.$label->id()] = $label->name();
            $customdata['color'.$label->id()] = $label->color();
            if (!isset($messages)) {
                $customdata['labelid['.$label->id().']'] = $message->has_label($label);
            } else {
                foreach ($messages as $message) {
                    if ($message->has_label($label)) {
                        $customdata['labelid['.$label->id().']'] = 1;
                    }
                }
            }
        }
    }

    // Create form.
    $mform = new mail_labels_form($url, $customdata);

    $mform->set_data($customdata);

    if ($data = $mform->get_data()) {
        if (isset($data->submitbutton)) {
            $newlabel = false;
            $data->newlabelname = trim(clean_param($data->newlabelname, PARAM_TEXT));
            $data->newlabelname = preg_replace('/\s+/', ' ', $data->newlabelname);
            $validcolor = (!$data->newlabelcolor or in_array($data->newlabelcolor, $colors));
            $repeatedname = false;
            foreach ($labels as $label) {
                $repeatedname = $repeatedname || ($label->name() === $data->newlabelname);
            }
            if (!$repeatedname and !empty($data->newlabelname) and $validcolor) {
                $newlabel = local_mail_label::create($USER->id, $data->newlabelname, $data->newlabelcolor);
                if (!isset($data->labelid)) {
                    $data->labelid = array();
                }
                $data->labelid[$newlabel->id()] = 1;
            }
            if ($messageid) {
                $message = local_mail_message::fetch($messageid);
                if (!$message or !$message->viewable($USER->id) or $message->deleted($USER->id)) {
                    print_error('nomessages', 'local_mail');
                }
                if (isset($data->labelid)) {
                    $data->labelid = clean_param_array($data->labelid, PARAM_INT);
                    $labels = local_mail_label::fetch_user($USER->id);
                    foreach ($labels as $label) {
                        if ($data->labelid[$label->id()]) {
                            $message->add_label($label);
                        } else {
                            $message->remove_label($label);
                        }
                    }
                }
            } else {
                if ($msgs) {
                    $messages = local_mail_message::fetch_many($msgs);
                    if (isset($data->labelid)) {
                        $data->labelid = clean_param_array($data->labelid, PARAM_INT);
                        $labels = local_mail_label::fetch_user($USER->id);
                    }
                    foreach ($messages as $message) {
                        if (!$message->viewable($USER->id) or $message->deleted($USER->id)) {
                            print_error('invalidmessage', 'local_mail');
                        }
                        if (isset($data->labelid)) {
                            foreach ($labels as $label) {
                                if ($data->labelid[$label->id()]) {
                                    $message->add_label($label);
                                } else {
                                    $message->remove_label($label);
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($messageid) {
            $url->param('m', $messageid);
        }
        redirect($url);
    }

    // Display page.

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

} else if ($messageid) {
    local_mail_setup_page($SITE, new moodle_url($url, array('m' => $messageid)));

    // Fetch message.

    $message = local_mail_message::fetch($messageid);
    if (!$message or !$message->viewable($USER->id)) {
        print_error('invalidmessage', 'local_mail');
    }

    navigation_node::override_active_url($url);

    $message->set_unread($USER->id, false);

    // Remove message.

    if ($delete) {
        require_sesskey();
        if ($message->viewable($USER->id)) {
            $message->set_deleted($USER->id, !$message->deleted($USER->id));
        }
        redirect($url);
    }

    if ($hide) {
        require_sesskey();
        if ($confirmmsg) {
            if ($message->viewable($USER->id) and $message->deleted($USER->id)) {
                $message->set_invisible($USER->id);
            }
            redirect($url);
        } else {
            echo $OUTPUT->header();
            $cancel = clone $url;
            $url->param('t', 'trash');
            $url->param('m', $message->id());
            $url->param('confirmmsg', '1');
            $url->param('hide', '1');

            echo $OUTPUT->confirm(get_string('messagedeleteconfirm', 'local_mail', $message->subject()), $url, $cancel);
            echo $OUTPUT->footer();
            die;
        }
    }

    if ($starred) {
        require_sesskey();
        if (!$message->deleted($USER->id) and $message->id() === $starred) {
            $message->set_starred($USER->id, !$message->starred($USER->id));
        }
        $url->param('m', $message->id());
        redirect($url);
    }

    // Reply message.

    if ($reply || $replyall) {
        require_sesskey();
        $newreply = $message->reply($USER->id, $replyall);
        $url = new moodle_url('/local/mail/compose.php', array('m' => $newreply->id()));
        redirect($url);
    }

    // Reply message from sent.

    if ($replysend || $replysendall) {
        require_sesskey();
        $newreply = $message->replysend($USER->id, $replysendall);
        $url = new moodle_url('/local/mail/compose.php', array('m' => $newreply->id()));
        redirect($url);
    }

    // Forward message.

    if ($forward) {
        require_sesskey();
        $newmessage = $message->forward($USER->id);
        $url = new moodle_url('/local/mail/compose.php', array('m' => $newmessage->id()));
        redirect($url);
    }

    // Unread.
    if ($unread) {
        require_sesskey();
        $message->set_unread($USER->id, true);
        redirect($url);
    }

    // Downloadall.
    if ($downloadall) {
        local_mail_downloadall($message);
    }

    $jslabels = local_mail_js_labels();
    $url->param('m', $message->id());
    // Display page.
    $PAGE->requires->data_for_js('M.local_mail_lang', current_language(), true);
    $PAGE->requires->js('/local/mail/mail.js');
    $PAGE->requires->strings_for_js(array(
        'starred',
        'unstarred',
        'editlabel',
        'newlabel',
        'erroremptylabelname',
        'undodelete',
        'undorestore',
        'search',
        'delete',
        'messagedeleteconfirm',
        'messagesdeleteconfirm',
        ), 'local_mail');
    $PAGE->requires->strings_for_js(array(
        'submit',
        'cancel',
        'maximumchars'
        ), 'moodle');
    form_init_date_js();
    $PAGE->requires->js_init_code($jslabels);

    echo $OUTPUT->header();
    $usesvg = $PAGE->theme->use_svg_icons();
    $params = array(
        'method' => 'post',
        'action' => $url,
        'id' => 'local_mail_main_form',
        'class' => ($usesvg ? 'mail_svg' : ''),
    );
    echo html_writer::start_tag('form', $params);
    $mailoutput = $PAGE->get_renderer('local_mail');
    echo $mailoutput->toolbar('view', $message->course()->id, array('trash' => ($type === 'trash')));
    echo $mailoutput->notification_bar();
    echo $OUTPUT->container_start('mail_view');

    echo $OUTPUT->container_start('mail_subject');
    $title = s($message->subject());
    echo $mailoutput->label_message($message, $type, $labelid, true);
    echo $OUTPUT->heading($title, 3, '');
    if ($type !== 'trash') {
        echo $mailoutput->starred($message, $USER->id, $type, 0, true);
    }
    echo $OUTPUT->container_end();

    echo $mailoutput->mail($message, false, $offset);

    echo $OUTPUT->container_end();

    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ));

    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'type',
        'value' => $type,
    ));

    if ($type == 'course') {
        echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'itemid',
            'value' => $message->course()->id,
        ));
    } else if ($type == 'label') {
        echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'itemid',
            'value' => $labelid,
        ));
    }
    echo html_writer::end_tag('div');

    $refs = $message->references();
    if (!empty($refs)) {
        echo $mailoutput->references($refs);
    }
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();

} else {
    $mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);

    if ($prevpage or $nextpage) {
        if ($prevpage) {
            $offset = max(0, $offset - $mailpagesize);
        } else if ($nextpage) {
            $offset = $offset + $mailpagesize;
        }
        $url->param('offset', $offset);
        redirect($url);
    }

    // Set up messages.

    $itemid = ($labelid?:$courseid);

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    $messages = local_mail_message::fetch_index($USER->id, $type, $itemid, $offset, $mailpagesize);


    // Display page.

    $courseid = $courseid ?: $SITE->id;

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourse', 'error');
    }

    // Check whether user can use mail in that course.
    if ($course->id != $SITE->id) {
        $context = context_course::instance($course->id);
        require_capability('local/mail:usemail', $context);
    }

    local_mail_setup_page($course, $url);
    $url->param('offset', $offset);

    // Remove.
    if ($delete) {
        require_sesskey();
        foreach ($messages as $message) {
            if (in_array($message->id(), $msgs)) {
                if ($message->viewable($USER->id)) {
                    $message->set_deleted($USER->id, !$message->deleted($USER->id));
                }
                $totalcount -= 1;
            }
        }
        if ($offset > $totalcount - 1) {
            $url->offset = max(0, $offset - $mailpagesize);
        } else {
            $url->offset = $offset;
        }
        redirect($url);
    }

    if ($hide) {
        require_sesskey();
        if ($confirmmsg) {
            foreach ($messages as $message) {
                if (in_array($message->id(), $msgs)) {
                    if ($message->viewable($USER->id) and $message->deleted($USER->id)) {
                        $message->set_invisible($USER->id);
                    }
                    $totalcount -= 1;
                }
            }
            if ($offset > $totalcount - 1) {
                $url->offset = max(0, $offset - $mailpagesize);
            } else {
                $url->offset = $offset;
            }
            redirect($url);
        } else {
            echo $OUTPUT->header();
            $cancel = clone $url;
            $url->param('confirmmsg', '1');
            $url->param('hide', '1');
            foreach ($msgs as $key => $value) {
                $url->param("msgs[$key]", $value);
            }

            echo $OUTPUT->confirm(get_string('messagesdeleteconfirm', 'local_mail', count($msgs)), $url, $cancel);
            echo $OUTPUT->footer();
            die;
        }
    }

    if ($emptytrash) {
        require_sesskey();
        if ($confirmmsg) {
            local_mail_message::empty_trash($USER->id);
            $url->offset = 0;
            redirect($url);
        } else {
            echo $OUTPUT->header();
            $cancel = clone $url;
            $url->param('confirmmsg', '1');
            $url->param('emptytrash', '1');

            echo $OUTPUT->confirm(get_string('emptytrashconfirm', 'local_mail'), $url, $cancel);
            echo $OUTPUT->footer();
            die;
        }
    }

    // Remove.
    if ($discard) {
        require_sesskey();
        foreach ($messages as $message) {
            if (in_array($message->id(), $msgs)) {
                if ($message->viewable($USER->id) and $message->draft()) {
                    $message->discard();
                }
                $totalcount -= 1;
            }
        }
        if ($offset > $totalcount - 1) {
            $url->offset = max(0, $offset - $mailpagesize);
        } else {
            $url->offset = $offset;
        }
        redirect($url);
    }

    // Starred.
    if ($starred) {
        require_sesskey();
        $message = local_mail_message::fetch($starred);
        if (!$message or !$message->viewable($USER->id) or $message->deleted($USER->id)) {
            print_error('invalidmessage', 'local_mail');
        }
        $message->set_starred($USER->id, !$message->starred($USER->id));
        redirect($url);
    }

    // Read or Unread.
    if ($read || $unread) {
        require_sesskey();
        foreach ($messages as $message) {
            if (in_array($message->id(), $msgs)) {
                $message->set_unread($USER->id, $unread);
            }
        }
        redirect($url);
    }

    // Perpage.
    if ($perpage) {
        require_sesskey();
        if (in_array($perpage, array (5, 10, 20, 50, 100))) {
            set_user_preference('local_mail_mailsperpage', $perpage);
        }
        redirect($url);
    }

    $jslabels = local_mail_js_labels();
    // Display page.
    $PAGE->requires->data_for_js('M.local_mail_lang', current_language(), true);
    $PAGE->requires->js('/local/mail/mail.js');
    $PAGE->requires->strings_for_js(array(
        'delete',
        'editlabel',
        'erroremptylabelname',
        'labeldeleteconfirm',
        'newlabel',
        'starred',
        'undodelete',
        'undorestore',
        'unstarred',
        'search',
        'messagedeleteconfirm',
        'messagesdeleteconfirm',
        'emptytrash',
        'emptytrashconfirm',
        ), 'local_mail');
     $PAGE->requires->strings_for_js(array(
        'submit',
        'cancel',
        'maximumchars'
        ), 'moodle');
    form_init_date_js();
    $PAGE->requires->js_init_code($jslabels);
    $mailoutput = $PAGE->get_renderer('local_mail');
    echo $OUTPUT->header();
    echo $mailoutput->view(array(
        'type' => $type,
        'labelid' => $labelid,
        'itemid' => $itemid,
        'courseid' => $courseid,
        'userid' => $USER->id,
        'messages' => $messages,
        'totalcount' => $totalcount,
        'offset' => $offset
    ));
    echo $OUTPUT->footer();
}
