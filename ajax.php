<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/mail/lib.php');

$action   = optional_param('action', false, PARAM_ALPHA);
$type     = optional_param('type', false, PARAM_ALPHA);
$msgs     = optional_param('msgs', '', PARAM_SEQUENCE);
$labelids = optional_param('labelids', '', PARAM_SEQUENCE);
$itemid   = optional_param('itemid', 0, PARAM_INT);
$offset   = optional_param('offset', 0, PARAM_INT);
$perpage  = optional_param('perpage', 0, PARAM_INT);
$sesskey  = optional_param('sesskey', null, PARAM_RAW);
$mailview = optional_param('mailview', false, PARAM_BOOL);


$courseid = ($type == 'course'?$itemid:$SITE->id);
require_login($courseid);

$valid_actions = array(
    'starred',
    'unstarred',
    'delete',
    'markasread',
    'markasunread',
    'prevpage',
    'nextpage',
    'perpage',
    'viewmail',
    'goback',
    'setlabels'
);

if ($action and in_array($action, $valid_actions) and !empty($USER->id)) {

    if(!confirm_sesskey($sesskey)) {
        echo json_encode(array('msgerror' => get_string('invalidsesskey', 'error')));
        die;
    }
    if (empty($msgs) and ($action != 'prevpage' and $action != 'nextpage'and $action != 'perpage')){
        echo json_encode(array('msgerror' => get_string('nomessageserror', 'local_mail')));
        die;
    }
    if ($action != 'prevpage' and $action != 'nextpage' and $action != 'perpage' and $action != 'goback') {
        if ($action == 'viewmail') {
            $message = local_mail_message::fetch($msgs);
            if (!$message or !$message->viewable($USER->id)) {
                echo json_encode(array('msgerror' => get_string('invalidmessage', 'local_mail')));
            }
        } else {
            $msgsids = explode(',', $msgs);
            $messages = local_mail_message::fetch_many($msgsids);
        }
    }
    $params = array();
    $mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);
    if ($action === 'starred') {
        $func = 'setstarred';
        array_push($params, $messages);
        array_push($params, true);
    } elseif ($action === 'unstarred') {
        $func = 'setstarred';
        array_push($params, $messages);
        array_push($params, false);
    } elseif ($action === 'markasread') {
        $func = 'setread';
        array_push($params, $messages);
        array_push($params, true);
    }elseif ($action === 'markasunread') {
        $func = 'setread';
        array_push($params, $messages);
        array_push($params, false);
        if ($mailview) {
            if ($type != 'course' and $type != 'label') {
                $itemid = 0;
            }
            array_push($params, array(
                                    'itemid' => $itemid,
                                    'type' => $type,
                                    'offset' => $offset,
                                    'mailpagesize' => $mailpagesize
                                )
            );
        }
    }elseif ($action === 'delete') {
        $func = 'setdelete';
        array_push($params, $messages);
        array_push($params, ($type != 'trash'));
        if ($type != 'course' and $type != 'label') {
            $itemid = 0;
        }
        array_push($params, $itemid);
        array_push($params, $type);
        array_push($params, $offset);
        array_push($params, $mailpagesize);
    } elseif ($action === 'prevpage') {
        $func = 'setprevpage';
        array_push($params, $itemid);
        array_push($params, $type);
        array_push($params, $offset);
        array_push($params, $mailpagesize);
    } elseif ($action === 'nextpage') {
        $func = 'setnextpage';
        array_push($params, $itemid);
        array_push($params, $type);
        array_push($params, $offset);
        array_push($params, $mailpagesize);
    }  elseif ($action === 'perpage') {
        $func = 'setperpage';
        array_push($params, $itemid);
        array_push($params, $type);
        array_push($params, $offset);
        array_push($params, $perpage);
    } elseif ($action === 'viewmail') {
        $func = 'getmail';
        array_push($params, $message);
        array_push($params, $type);
        array_push($params, false); //reply
        array_push($params, $offset);
        array_push($params, $itemid);
    } elseif ($action === 'goback') {
        $func = 'setgoback';
        if ($type != 'course' and $type != 'label') {
            $itemid = 0;
        }
        array_push($params, $itemid);
        array_push($params, $type);
        array_push($params, $offset);
        array_push($params, $mailpagesize);
    } elseif ($action === 'setlabels') {
        $func = 'setlabels';
        array_push($params, $messages);
        array_push($params, explode(',', $labelids));
        array_push($params, $mailview);
        array_push($params, array(
                                    'itemid' => $itemid,
                                    'type' => $type,
                                    'offset' => $offset,
                                    'mailpagesize' => $mailpagesize
                                ));
    }
    echo json_encode(call_user_func_array($func, $params));
} else {
    echo json_encode(array('msgerror' => 'Invalid data'));
}

function setstarred ($messages, $bool) {
    global $USER;

    foreach ($messages as $message) {
        if ($message->viewable($USER->id)) {
            $message->set_starred($USER->id, $bool);
        }
    }
    return array(
        'info' => '',
        'html' => ''
    );
}

function setread ($messages, $bool, $mailview = false) {
    global $USER;

    $html = '';

    foreach ($messages as $message) {
        if ($message->viewable($USER->id)) {
            $message->set_unread($USER->id, !$bool);
        }
    }

    if ($mailview) {
        $totalcount = local_mail_message::count_index($USER->id, $mailview['type'], $mailview['itemid']);
        $html = print_messages($mailview['itemid'], $mailview['type'], $mailview['offset'], $mailview['mailpagesize'], $totalcount);
    }
    return array(
        'info' => get_info(),
        'html' => $html
    );
}

function setdelete ($messages, $bool, $itemid, $type, $offset, $mailpagesize) {
    global $PAGE, $USER;

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    foreach ($messages as $message) {
        if ($message->editable($USER->id)) {
            $message->discard();
        } elseif ($message->viewable($USER->id)) {
            $message->set_deleted($USER->id, $bool);
        }
        $totalcount -= 1;
    }
    if ($offset > $totalcount-1) {
        $offset = min(0, $offset-$mailpagesize);
    }
    return array(
        'info' => get_info(),
        'html' => print_messages($itemid, $type, $offset, $mailpagesize, $totalcount)
    );
}

function setprevpage($itemid, $type, $offset, $mailpagesize){
    global $USER;

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    $offset = max(0, $offset - $mailpagesize);
    return array(
        'info' => '',
        'html' => print_messages($itemid, $type, $offset, $mailpagesize, $totalcount)
    );
}

function setnextpage($itemid, $type, $offset, $mailpagesize){
    global $USER;

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    $offset = $offset + $mailpagesize;
    return array(
        'info' => '',
        'html' => print_messages($itemid, $type, $offset, $mailpagesize, $totalcount)
    );
}

function setgoback($itemid, $type, $offset, $mailpagesize){
    global $USER;

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    return array(
        'info' => '',
        'html' => print_messages($itemid, $type, $offset, $mailpagesize, $totalcount)
    );
}

function setlabels($messages, $labelids, $mailview, $data)
{
    global $USER;

    $rethtml = false;
    $content = '';

    $labels = local_mail_label::fetch_user($USER->id);
    foreach ($messages as $message) {
        if ($message->viewable($USER->id) and !$message->deleted($USER->id)) {
            foreach ($labels as $label) {
                if (in_array($label->id(), $labelids)) {
                    $message->add_label($label);
                } else {
                    if ($data['type'] == 'label' and $label->id() == $data['itemid']) {
                        $rethtml = true;
                    }
                    $message->remove_label($label);
                }
            }
        }
    }
    if (!$mailview && $rethtml) {
        $totalcount = local_mail_message::count_index($USER->id, $data['type'], $data['itemid']);
        $content = print_messages($data['itemid'], $data['type'], $data['offset'], $data['mailpagesize'], $totalcount);
    }
    return array(
        'info' => get_info(),
        'html' => $content
    );
}

function setperpage($itemid, $type, $offset, $mailpagesize){
    global $USER;

    $totalcount = local_mail_message::count_index($USER->id, $type, $itemid);
    if (in_array($mailpagesize, array (5, 10, 20, 50, 100))) {
        set_user_preference('local_mail_mailsperpage', $mailpagesize);
        return array(
            'info' => '',
            'html' => print_messages($itemid, $type, $offset, $mailpagesize, $totalcount)
        );
    }
    return array(
        'info' => '',
        'html' => ''
    );
}

function print_messages($itemid, $type, $offset, $mailpagesize, $totalcount) {
    global $PAGE, $USER;

    $url = new moodle_url('/local/mail/view.php', array('t' => $type));
    $PAGE->set_url($url);
    $mailoutput = $PAGE->get_renderer('local_mail');
    $messages = local_mail_message::fetch_index($USER->id, $type, $itemid, $offset, $mailpagesize);
    $content = $mailoutput->view(array(
        'type' => $type,
        'itemid' => $itemid,
        'userid' => $USER->id,
        'messages' => $messages,
        'totalcount' => $totalcount,
        'offset' => $offset,
        'ajax' => true
    ));
    return preg_replace('/^<div>|<\/div>$/', '', $content);
}

function getmail($message, $type, $reply, $offset, $labelid) {
    global $PAGE, $OUTPUT, $USER;

    $url = new moodle_url('/local/mail/view.php', array('t' => $type));
    $url->param('m', $message->id());
    $PAGE->set_url($url);

    $message->set_unread($USER->id, false);
    $mailoutput = $PAGE->get_renderer('local_mail');
    $content = $mailoutput->toolbar('view', false, null, ($type === 'trash'));
    $content .= $OUTPUT->container_start('mail_view');

    $content .= $OUTPUT->container_start('mail_subject');
    $title = s($message->subject());
    $content .= $mailoutput->label_message($message, $type, $labelid, true);
    $content .= $OUTPUT->heading($title, 3, '');
    if ($type !== 'trash') {
        $content .= $mailoutput->starred($message, $USER->id, $type, 0, true);
    }
    $content .= $OUTPUT->container_end();

    $content .= $mailoutput->mail($message, $reply, $offset);

    $content .= $OUTPUT->container_end();

    $content .= html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ));

    $content .= html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'type',
        'value' => $type,
    ));

    if ($type == 'course') {
        $content .= html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'itemid',
            'value' => $message->course()->id,
        ));
    } elseif ($type == 'label') {
        $content .= html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'itemid',
            'value' => $labelid,
        ));
    }

    $content .= html_writer::end_tag('form');
    $refs = $message->references();
    if (!empty($refs)) {
        $content .= $mailoutput->references(local_mail_message::fetch_many($refs));
    }
    $content = preg_replace('/^<div>|<\/div>$/', '', $content);
    return array(
        'info' => '',
        'html' => $content
    );
}

function get_info() {
    global $USER;

	$count = local_mail_message::count_menu($USER->id);


    $text = get_string('mymail', 'local_mail');
    if (empty($count->inbox)) {
        $count->root = $text;
    } else {
        $count->root = $text . ' (' . $count->inbox . ')';
    }

    $text = get_string('inbox', 'local_mail');
    if (empty($count->inbox)) {
        $count->inbox = $text;
    } else {
        $count->inbox = $text . ' (' . $count->inbox . ')';
    }

    $text = get_string('drafts', 'local_mail');
    if(empty($count->drafts)) {
        $count->drafts = $text;
    }else{
        $count->drafts = $text . ' (' . $count->drafts . ')';
    }

    $labels = local_mail_label::fetch_user($USER->id);
    if ($labels) {
        foreach ($labels as $label) {
            $text = $label->name();
            if (empty($count->labels[$label->id()])) {
                $count->labels[$label->id()] = $text;
            }else{
                $count->labels[$label->id()] = $text . ' (' . $count->labels[$label->id()] . ')';
            }
        }
    }

    if (!$courses = enrol_get_my_courses()) {
        return;
    }

    foreach ($courses as $course) {
        $text = $course->shortname;
        if (empty($count->courses[$course->id])) {
            $count->courses[$course->id] = $text;
        } else {
            $count->courses[$course->id] = $text . ' ('. $count->courses[$course->id].')';
        }
    }

    return $count;
}