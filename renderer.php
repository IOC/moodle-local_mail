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

    function label_message($message) {
        $output = html_writer::start_tag('span', array('class' => 'mail_group_labels'));
        $labels = $message->labels();
        foreach ($labels as $label) {
            $output .= html_writer::tag('span', s($label->name()),
                array('class' => 'mail_label mail_label_'. $label->color()));
        }
        $output .= html_writer::end_tag('span');
        return $output;
    }

    function label_draft() {
        $name = get_string('draft', 'local_mail');
        return html_writer::tag('span', s($name), array('class' => 'mail_label mail_draft'));
    }

    function messagelist($messages, $userid, $type, $itemid, $offset) {
        $output = $this->output->container_start('mail_list');

        foreach ($messages as $message) {
            $unreadclass = '';
            $attributes = array(
                    'type' => 'checkbox',
                    'name' => 'msgs[]',
                    'value' => $message->id(),
                    'class' => 'mail_checkbox'
            );
            $checkbox = html_writer::empty_tag('input', $attributes);
            $flags = '';
            if ($type !== 'trash') {
                $flags = $this->starred($message, $userid, $type, $offset);
            }
            $content = ($this->users($message, $userid, $type, $itemid) .
                        $this->summary($message, $userid, $type, $itemid) .
                        $this->date($message));
            if ($message->editable($userid)) {
                $url = new moodle_url('/local/mail/compose.php', array('m' => $message->id()));
            } else {
                $params = array('t' => $type, 'm' => $message->id());
                $type == 'course' and $params['c'] = $itemid;
                $type == 'label' and $params['l'] = $itemid;
                $url = new moodle_url("/local/mail/view.php", $params);
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

    function paging($offset, $count, $totalcount) {
        if ($offset === false){
            $str = get_string('pagingempty', 'local_mail', $totalcount);
        } elseif ($count == 1) {
            $a = array('index' => $offset + 1, 'total' => $totalcount);
            $str = get_string('pagingsingle', 'local_mail', $a);
        } else {
            $a = array('first' => $offset + 1, 'last' => $offset + $count, 'total' => $totalcount);
            $str = get_string('pagingmultiple', 'local_mail', $a);
        }
        $prevtitle = $this->output->larrow();
        $params = array(
            'value' => $prevtitle,
            'type' => 'submit',
            'name' => 'prevpage',
            'tooltip' => get_string('previous'),
            'class' => 'singlebutton',
        );
        if (!$offset) {
            $params = array_merge($params, array('disabled' => 'disabled'));
        }
        $prev = html_writer::empty_tag('input', $params);

        $nexttitle = $this->output->rarrow();
        $params = array(
            'value' => $nexttitle,
            'type' => 'submit',
            'name' => 'nextpage',
            'tooltip' => get_string('next'),
            'class' => 'singlebutton',
        );
        if ($offset === false or ($offset + $count) == $totalcount) {
            $params = array_merge($params, array('disabled' => 'disabled'));
        }
        $next = html_writer::empty_tag('input', $params);
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

        $content .= $this->label_message($message);

        if ($message->subject()) {
            $content .= s($message->subject());
        } else {
            $content .= get_string('nosubject', 'local_mail');
        }
        return html_writer::tag('span', $content, array('class' => 'mail_summary'));
    }

    function delete($type) {
        $label = ($type === 'trash'?get_string('restore', 'local_mail'):get_string('delete'));
        $attributes = array(
            'type' => 'submit',
            'name' => 'delete',
            'value' => $label,
            'class' => 'singlebutton'
        );
        return html_writer::empty_tag('input', $attributes);
    }

    function reply() {
        $label = get_string('reply', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'reply',
            'value' => $label,
            'class' => 'singlebutton'
        );
        return html_writer::empty_tag('input', $attributes);
    }

    function starred($message, $userid, $type, $offset = 0, $view = false) {
        $params = array(
                'starred' => $message->id(),
                'sesskey' => sesskey()
        );
        $url = new moodle_url($this->page->url, $params);
        $url->param('offset', $offset);
        $output = html_writer::start_tag('span', array('class' => 'mail_flags'));
        if ($view){
            $url->param('m', $message->id());
            $url->remove_params(array('offset'));
        }
        if ($message->starred($userid)) {
            $linkparams = array('title' => get_string('starred', 'local_mail'));
            $output .= html_writer::link($url, html_writer::tag('span', '', array('class' => 'mail_starred')), $linkparams);
        } else {
            $linkparams = array('title' => get_string('nostarred', 'local_mail'));
            $output .= html_writer::link($url, html_writer::tag('span', '', array('class' => 'mail_nostarred')), $linkparams);
        }
        $output .= html_writer::end_tag('span');
        return $output;
    }

    function forward() {
        $label = get_string('forward', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'forward',
            'value' => $label,
            'class' => 'singlebutton'
        );
        return html_writer::empty_tag('input', $attributes);
    }

    function replyall($enabled = false) {
        $label = get_string('replyall', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'replyall',
            'value' => $label,
            'class' => 'singlebutton'
        );
        if (!$enabled){
            $attributes = array_merge($attributes, array('disabled' => 'disabled'));
        }
        return html_writer::empty_tag('input', $attributes);
    }

    function labels($type) {
        $label = get_string('setlabels', 'local_mail');
        $attributes = array('type' => 'hidden', 'name' => 'type', 'value' => $type);
        $output = html_writer::empty_tag('input', $attributes);
        $attributes = array(
            'type' => 'submit',
            'name' => 'assignlbl',
            'value' => $label,
            'class' => 'singlebutton'
        );
        $output .= html_writer::empty_tag('input', $attributes);
        return $output;
    }

    function read() {
        $label = get_string('read', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'read',
            'value' => $label,
            'class' => 'singlebutton'
        );
        return html_writer::empty_tag('input', $attributes);
    }

    function unread() {
        $label = get_string('unread', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'unread',
            'value' => $label,
            'class' => 'singlebutton'
        );
        return html_writer::empty_tag('input', $attributes);
    }

    function optlabels() {
        $label = get_string('editlabel', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'editlbl',
            'value' => $label,
            'class' => 'singlebutton'
        );
        $content = html_writer::tag('span', '', array('class' => 'mail_toolbar_sep'));
        $content .= html_writer::empty_tag('input', $attributes);
        $label = get_string('removelabel', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'removelbl',
            'value' => $label,
            'class' => 'singlebutton'
        );
        $content .= html_writer::empty_tag('input', $attributes);
        return $content;
    }

    function references($references, $reply = false) {
        $class = 'mail_references';
        $header = 'h3';
        if ($reply) {
            $class = 'mail_reply';
            $header = 'h2';
        }
        $output = $this->output->container_start($class);
        $output .= html_writer::tag($header, get_string('references', 'local_mail'));
        foreach ($references as $ref) {
            $output .= $this->mail($ref, true);
        }
        $output .= $this->output->container_end();
        return $output;
    }

    function mail($message, $reply = false) {
        global $CFG, $USER;

        $totalusers = 0;

        $output = html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'm',
                'value' => $message->id(),
        ));
        $output .= $this->output->container_start('mail_header');
        $output .= $this->output->container_start('left');
        $output .= $this->output->user_picture($message->sender());
        $output .= $this->output->container_end();
        $output .= $this->output->container_start('mail_info');
        $output .= html_writer::link(new moodle_url('/user/view.php',
                                            array(
                                                'id' => $message->sender()->id,
                                                'course' => $message->course()->id
                                            )),
                                    fullname($message->sender()),
                                    array('class' => 'user_from'));
        $output .= $this->date($message);
        if (!$reply) {
            $output .= $this->output->container_start('mail_recipients');
            foreach (array('to', 'cc', 'bcc') as $role) {
                $recipients = $message->recipients($role);
                if (!empty($recipients)) {
                    if ($role == 'bcc' and $message->sender()->id !== $USER->id) {
                        continue;
                    }
                    $output .= html_writer::start_tag('div');
                    $output .= html_writer::tag('span', get_string($role, 'local_mail'), array('class' => 'mail_role'));
                    $numusers = count($recipients);
                    $totalusers += $numusers;
                    $cont = 1;
                    foreach ($recipients as $user) {
                        $output .= html_writer::link(new moodle_url('/user/view.php',
                                            array(
                                                'id' => $user->id,
                                                'course' => $message->course()->id
                                            )),
                                            fullname($user));
                        if ($cont < $numusers) {
                            $output .= ', ';
                        }
                        $cont += 1;
                    }
                    $output .= ' ';
                    $output .= html_writer::end_tag('div');
                }
            }
            $output .= $this->output->container_end();
        } else {
            $output .= html_writer::tag('div', '', array('class' => 'mail_recipients'));
        }
        $output .= $this->output->container_end();
        $output .= $this->output->container_end();

        $output .= $this->output->container_start('mail_body');
        $output .= $this->output->container_start('mail_content');
        $output .= local_mail_format_content($message);
        $attachments = local_mail_attachments($message);
        if ($attachments) {
            $output .= $this->output->container_start('mail_attachments');
            if (count($attachments) > 1) {
                $text = get_string('attachnumber', 'local_mail', count($attachments));
                $output .= html_writer::tag('div', $text, array('class' => 'mail_attachment_text'));
            }
            foreach ($attachments as $attach) {
                $filename = $attach->get_filename();
                $filepath = $attach->get_filepath();
                $mimetype = $attach->get_mimetype();
                $iconimage = $this->output->pix_icon(file_file_icon($attach), get_mimetype_description($attach), 'moodle', array('class' => 'icon'));
                $path = '/'.$attach->get_contextid().'/local_mail/message/'.$attach->get_itemid().$filepath.$filename;
                $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, true);
                $output .= html_writer::start_tag('div', array('class' => 'mail_attachment'));
                $output .= html_writer::link($fullurl, $iconimage);
                $output .= html_writer::link($fullurl, s($filename));
                $output .= html_writer::tag('span', display_size($attach->get_filesize()), array('class' => 'mail_attachment_size'));
                $output .= html_writer::end_tag('div');
            }
            $output .= $this->output->container_end();
        }
        $output .= $this->output->container_end();
        if (!$reply) {
            if ($message->sender()->id !== $USER->id) {
                $output .= $this->toolbar('reply', ($totalusers > 1));
            } else {
                $output .= $this->toolbar('forward');
            }
        }
        $output .= $this->output->container_end();
        return $output;
    }

    function toolbar($type, $replyall = false, $paging = null, $trash = false) {
        if ($type === 'reply') {
            $output = $this->reply();
            //all recipients
            $output .= $this->replyall($replyall);
            $output .= $this->forward();
        } elseif ($type === 'forward') {
            $output = $this->forward();
        } else {
            $delete = $this->delete($type);
            $labels = $extended = '';
            if (!$trash and $type !== 'trash') {
                $labels = $this->labels($type);
            }
            $read = $unread = '';
            if ($type !== 'drafts') {
                $unread = $this->unread();
            }
            $pagingbar = '';
            if ($type !== 'view') {
                if ($type !== 'drafts') {
                    $read = $this->read();
                }
                $pagingbar = $this->paging($paging['offset'],
                                        $paging['count'],
                                        $paging['totalcount']);
            }
            if ($type === 'label') {
                $extended = $this->optlabels();
            }
            $clearer = $this->output->container('', 'clearer');
            $output = $labels . $read . $unread . $pagingbar . $delete . $extended . $clearer;
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
        global $USER;

        $content = '';

        $type = $params['type'];
        $itemid = !empty($params['itemid']) ? $params['itemid'] : 0;
        $userid = $params['userid'];
        $messages = $params['messages'];
        $count = count($messages);
        $offset = $params['offset'];
        $totalcount = $params['totalcount'];
        $mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);

        $url = new moodle_url($this->page->url);
        $content .= html_writer::start_tag('form', array('method' => 'post', 'action' => $url));
        $paging = array(
            'offset' => $offset,
            'count' => $count,
            'totalcount' => $totalcount,
            'pagesize' => $mailpagesize,
        );
        if (!$messages) {
            $paging['offset'] = false;
        }
        $content .= $this->toolbar($type, false, $paging);
        if ($messages) {
            $content .= $this->messagelist($messages, $userid, $type, $itemid, $offset);
        } else {
            $content .= $this->output->container_start('mail_list');
            $string = get_string('nomessagestoview', 'local_mail');
            $initurl = new moodle_url('/local/mail/view.php');
            $initurl->param('t' , $type);
            $link = html_writer::link($initurl, get_string('showrecentmessages', 'local_mail'));
            $content .= html_writer::tag('div', $string.' '.$link, array('class' => 'mail_item'));
            $content .= $this->output->container_end();
        }
        $content .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
        ));
        $content .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'offset',
                'value' => $offset,
        ));
        $content .= html_writer::start_tag('div', array('class' => 'mail_perpage'));
        $nums = array('5', '10', '20', '50', '100');
        $cont = count($nums) - 1;
        $perpage = '';
        foreach ($nums as $num) {
            $params = array(
                    'perpage' => $num,
                    'offset' => $offset,
                    'sesskey' => sesskey()
            );
            if ($mailpagesize == $num) {
                $perpage .= html_writer::start_tag('strong');
            }
            $url = new moodle_url($this->page->url, $params);
            $perpage .= html_writer::link($url, $num);
            if ($mailpagesize == $num) {
                $perpage .= html_writer::end_tag('strong');
            }
            if ($cont) {
                $perpage .= '|';
            }
            $cont -= 1;
        }
        $content .= get_string('perpage', 'local_mail', $perpage);
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('form');

        return $this->output->container($content);
    }
}
