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
        $output = $this->output->container_start('mail_list');

        foreach ($messages as $message) {
            $content = ($this->users($message, $userid, $type, $itemid) .
                        $this->summary($message, $userid, $type, $itemid) .
                        $this->date($message));
            $params = array('id' => $message->id());
            if ($message->editable($userid)) {
                $url = new moodle_url('/local/mail/compose.php', $params);
            } else {
                $url = new moodle_url('/local/mail/view.php', $params);
            }
            $attributes = array('class' => 'mail_item', 'href' => $url);
            $output .= html_writer::tag('a', $content, $attributes);
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
        $prev = $this->output->single_button($prevurl, $prevtitle, 'get', array(
            'tooltip' => get_string('previous'),
            'disabled' => ($offset == 0),
        ));

        $nextoffset = min($totalcount - 1, $offset + $pagesize);
        $nexturl = new moodle_url($this->page->url, array('offset' => $nextoffset));
        $nexttitle = $this->output->rarrow();        
        $next = $this->output->single_button($nexturl, $nexttitle, 'get', array(
            'tooltip' => get_string('next'),
            'disabled' => ($offset + $count == $totalcount),
        ));

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

    function toolbar($offset, $count, $totalcount, $pagesize) {
        $paging = $this->paging($offset, $count, $totalcount, $pagesize);
        $clearer = $this->output->container('', 'clearer');
        return $this->output->container($paging . $clearer, 'mail_toolbar');
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
            $content .= $this->toolbar($offset, $count, $totalcount, MAIL_PAGESIZE);
            $content .= $this->messagelist($messages, $userid, $type, $itemid);
        } else {
            $text = get_string('nomessages', 'local_mail');
            $content = html_writer::tag('p', s($text));
        }

        return $this->output->container($content);
    }
}
