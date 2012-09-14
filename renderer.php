<?php

class local_mail_renderer extends plugin_renderer_base {

    function date($message) {
        $offset = get_user_timezone_offset();
        $time = ($offset < 13) ? $message->time() + $offset : $message->time();
        $now = ($offset < 13) ? time() + $offset : time();
        $daysago = floor($now / 86400) - floor($time / 86400);
        $yearsago = (int) date('Y', $now) - (int) date('Y', $time);

        if ($daysago == 0) {
            $content = userdate($time, get_string('strftimetime'));
        } elseif ($daysago <= 6) {
            $content = userdate($time, get_string('strftimedaytime'));
        } elseif ($yearsago == 0) {
            $content = userdate($time, get_string('strftimedateshort'));
        } else {
            $content = userdate($time, get_string('strftimedate'));
        }

        return html_writer::tag('span', s($content), array('class' => 'mail_date'));
    }

    function label($label) {
        $classes = 'mail_label' . ($label->color() ? 'mail_' . $label->color() : '');
        return html_writer::tag('span', s($label->name()), array('class' => $classes));
    }

    function label_course($course) {
        return html_writer::tag('span', s($course->shortname),
                                array('class' => 'mail_label mail_course'));
    }

    function label_draft() {
        $name = get_string('draft', 'local_mail');
        return html_writer::tag('span', s($name), array('class' => 'mail_label mail_draft'));
    }

    function messagelist($messages, $userid, $type, $itemid) {
        global $OUTPUT;

        $output = $this->output->container_start('mail_list');

        foreach ($messages as $message) {
            $unreadclass = '';
            $attributes = array(
                    'type' => 'checkbox',
                    'name' => 'msg[]',
                    'value' => $message->id(),
                    'class' => 'mail_checkbox');
            $checkbox = html_writer::empty_tag('input', $attributes);
            /*
            $flags = html_writer::start_tag('span', array('class' => 'mail_flags'));
            $params = array(
                    'starred' => $message->id(),
                    'sesskey' => sesskey());
            $url = new moodle_url($this->page->url, $params);
            if ($message->starred($userid)) {
                $linkparams = array('title' => get_string('starred', 'local_mail'));
                $flags .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('starred','local_mail'))), $linkparams);
            } elseif ($type !== 'drafts') {
                $linkparams = array('title' => get_string('nostarred', 'local_mail'));
                $flags .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('nostarred','local_mail'))), $linkparams);
            }
            $flags .= html_writer::end_tag('span');
            */
            $flags = $this->starred($message, $userid, $type);
            $content = ($this->users($message, $userid, $type, $itemid) .
                        $this->summary($message, $userid, $type, $itemid) .
                        $this->date($message));
            $params = array('message' => $message->id());
            if ($message->editable($userid)) {
                $url = new moodle_url('/local/mail/compose.php', $params);
                $url->param('type', $type);
            } else {
                $url = new moodle_url("/local/mail/view_$type.php", $params);
            }
            if ($message->unread($userid)) {
                $unreadclass = 'mail_unread';
            }
            $output .= $this->output->container_start('mail_item ' . $unreadclass);
            $attributes = array('href' => $url, 'class' => 'mail_link');
            $output .= $checkbox . $flags . html_writer::tag('a', $content, $attributes);
            $output .= $this->output->container_end('mail_item');
        }

        $output .= $this->output->container_end();

        return $output;
    }

    function paging($offset, $count, $totalcount, $pagesize) {
        if ($count == 1) {
            $a = array('index' => $offset + 1, 'total' => $totalcount);
            $str = get_string('pagingsingle', 'local_mail', $a);
        } else {
            $a = array('first' => $offset + 1, 'last' => $offset + $count, 'total' => $totalcount);
            $str = get_string('pagingmultiple', 'local_mail', $a);
        }
        $prevoffset = max(0, $offset - $pagesize);
        $prevurl = new moodle_url($this->page->url, array('offset' => $prevoffset));
        $prevtitle = $this->output->larrow();
        $prev = html_writer::empty_tag('input', array(
            'value' => $prevtitle,
            'type' => 'submit',
            'name' => 'offset[' . $prevoffset . ']',
            'tooltip' => get_string('previous'),
            'class' => 'singlebutton',
            'disabled' => ($offset == 0)));
/*        $prev = $this->output->single_button($prevurl, $prevtitle, 'get', array(
            'tooltip' => get_string('previous'),
            'disabled' => ($offset == 0),
        ));*/

        $nextoffset = min($totalcount - 1, $offset + $pagesize);
        $nexturl = new moodle_url($this->page->url, array('offset' => $nextoffset));
        $nexttitle = $this->output->rarrow();
        $next = html_writer::empty_tag('input', array(
                'value' => $nexttitle,
                'type' => 'submit',
                'name' => 'offset[' . $nextoffset . ']',
                'tooltip' => get_string('next'),
                'class' => 'singlebutton',
                'disabled' => ($offset + $count == $totalcount)));
/*        $next = $this->output->single_button($nexturl, $nexttitle, 'get', array(
            'tooltip' => get_string('next'),
            'disabled' => ($offset + $count == $totalcount),
        ));*/

        return $this->output->container($str . ' ' . $prev . $next, 'mail_paging');
    }
    
    function summary($message, $userid, $type, $itemid) {
        global $DB;

        $content = '';

        if ($type != 'drafts' and $message->draft()) {
            $content .= $this->label_draft();
        }

        if ($type != 'course' or $itemid != $message->course()->id) {
            $content .= $this->label_course($message->course());
        }

        if ($message->subject()) {
            $content .= s($message->subject());
        } else {
            $content .= get_string('nosubject', 'local_mail');
        }
        return html_writer::tag('span', $content, array('class' => 'mail_summary'));
    }

    function delete($type) {
        $label = ($type === 'trash'?get_string('undelete', 'local_mail'):get_string('delete'));
        $attributes = array('type' => 'submit', 'name' => 'delete', 'value' => $label, 'class' => 'singlebutton');
        return html_writer::empty_tag('input', $attributes);
    }

    function reply() {
        $label = get_string('reply', 'local_mail');
        $attributes = array('type' => 'submit', 'name' => 'reply', 'value' => $label, 'class' => 'singlebutton');
        return html_writer::empty_tag('input', $attributes);
    }

    function starred($message, $userid, $type, $view = false) {
        global $OUTPUT;

        $params = array(
                'starred' => $message->id(),
                'sesskey' => sesskey());
        $url = new moodle_url($this->page->url, $params);
        $output = html_writer::start_tag('span', array('class' => 'mail_flags'));
        if ($view){
            $url->param('message', $message->id());
        }
        if ($message->starred($userid)) {
            $linkparams = array('title' => get_string('starred', 'local_mail'));
            $output .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('starred','local_mail'))), $linkparams);
        } elseif ($type !== 'drafts') {
            $linkparams = array('title' => get_string('nostarred', 'local_mail'));
            $output .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('nostarred','local_mail'))), $linkparams);
        }
        $output .= html_writer::end_tag('span');
        return $output;
    }

    function forward() {
        $label = get_string('forward', 'local_mail');
        $attributes = array('type' => 'submit', 'name' => 'forward', 'value' => $label, 'class' => 'singlebutton');
        return html_writer::empty_tag('input', $attributes);
    }

    function replyall($enabled = false) {
        $label = get_string('replyall', 'local_mail');
        $attributes = array('type' => 'submit', 'name' => 'replyall', 'value' => $label, 'class' => 'singlebutton');
        if (!$enabled){
            $attributes = array_merge($attributes, array('disabled' => 'disabled'));
        }
        return html_writer::empty_tag('input', $attributes);
    }

    function mail($message) {
        global $CFG, $OUTPUT, $USER;

        $totalusers = 0;

        $output = html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'msg',
                'value' => $message->id(),
        ));

        $output .= $OUTPUT->container_start('mail_header');
        $output .= $OUTPUT->container_start('left');
        $output .= $OUTPUT->user_picture($message->sender());
        $output .= $OUTPUT->container_end();
        $output .= $OUTPUT->container_start('mail_info');
        $output .= html_writer::link($CFG->wwwroot.'/user/view.php?id='.$message->sender()->id.'&course='.$message->course()->id, fullname($message->sender()), array('class' => 'user_from'));
        $output .= $this->date($message);
        $output .= $OUTPUT->container_start('mail_recipients');
        foreach (array('to', 'cc', 'bcc') as $role) {
            $recipients = $message->recipients($role);
            if (!empty($recipients)) {
                $output .= html_writer::start_tag('div');
                $output .= html_writer::tag('span', get_string($role, 'local_mail'), array('class' => 'mail_role'));
                $numusers = count($recipients);
                $totalusers += $numusers;
                $cont = 1;
                foreach ($recipients as $user) {
                    $output .= html_writer::link($CFG->wwwroot.'/user/view.php?id='.$user->id.'&course='.$message->course()->id, fullname($user));
                    if ($cont < $numusers) {
                        $output .= ', ';
                    }
                    $cont += 1;
                }
                $output .= ' ';
                $output .= html_writer::end_tag('div');
            }
        }
        $output .= $OUTPUT->container_end();
        $output .= $OUTPUT->container_end();
        $output .= $OUTPUT->container_end();

        $output .= $OUTPUT->container_start('mail_body');
        $output .= $OUTPUT->container_start('mail_content');
        $output .= format_text($message->content(), $message->format());
        $output .= $OUTPUT->container_end();
        if ($message->sender()->id !== $USER->id) {
            $output .= $this->toolbar('reply', ($totalusers > 1));
        } else {
            $output .= $this->toolbar('forward');
        }
        $output .= $OUTPUT->container_end();

        return $output;
    }

    function toolbar($type, $replyall = false, $paging = null) {
        if ($type === 'reply') {
            $output = $this->reply();
            //all recipients
            $output .= $this->replyall($replyall);
            $output .= $this->forward();
        } elseif ($type === 'forward') {
            $output = $this->forward();
        } else {
            $delete = $this->delete($type);
            $pagingbar = '';
            if ($type !== 'view') {
                $pagingbar = $this->paging($paging['offset'], $paging['count'], $paging['totalcount'], $paging['pagesize']);
            }
            $clearer = $this->output->container('', 'clearer');
            $output = $delete . $pagingbar . $clearer;
        }
        return $this->output->container($output, 'mail_toolbar');
    }

    function users($message, $userid, $type, $itemid) {
        global $DB;
        if ($userid == $message->sender()->id) {
            if ($users = $message->recipients('to')) {
                $content = implode(', ', array_map('fullname', $users));
            } else {
                $content = get_string('norecipient', 'local_mail');
            }
        } else {
            $content = fullname($message->sender());
        }        
        return html_writer::tag('span', s($content), array('class' => 'mail_users'));
    }

    function view($params) {
        $content = '';

        $type = $params['type'];
        $itemid = !empty($params['itemid']) ? $params['itemid'] : 0;
        $userid = $params['userid'];
        $messages = $params['messages'];
        $count = count($messages);
        $offset = $params['offset'];
        $totalcount = $params['totalcount'];

        if ($messages) {
            $url = new moodle_url($this->page->url);
            $content .= html_writer::start_tag('form', array('method' => 'post', 'action' => $url));
            $content .= $this->toolbar($type, false, array('offset' => $offset, 'count' => $count, 'totalcount' => $totalcount, 'pagesize' => MAIL_PAGESIZE));
            $content .= $this->messagelist($messages, $userid, $type, $itemid);
            $content .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'sesskey',
                    'value' => sesskey(),
            ));
            $content .= html_writer::end_tag('form');
        } else {
            $text = get_string('nomessages', 'local_mail');
            $content = html_writer::tag('p', s($text));
        }

        return $this->output->container($content);
    }
}
