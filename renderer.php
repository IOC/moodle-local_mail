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

class local_mail_renderer extends plugin_renderer_base {

    public function date($message) {
        $offset = get_user_timezone_offset();
        $time = ($offset < 13) ? $message->time() + $offset : $message->time();
        $now = ($offset < 13) ? time() + $offset : time();
        $daysago = floor($now / 86400) - floor($time / 86400);
        $yearsago = (int) date('Y', $now) - (int) date('Y', $time);

        if ($daysago == 0) {
            $content = userdate($time, get_string('strftimetime'));
        } else if ($yearsago == 0) {
            $content = userdate($time, get_string('strftimedateshort'));
        } else {
            $content = userdate($time, get_string('strftimedate'));
        }

        return html_writer::tag('span', s($content), array('class' => 'mail_date'));
    }

    public function attachment($message) {
        if ($message->has_attachment()) {
            return html_writer::tag('span' , '' , array('class' => 'mail_attached'));
        }
    }

    public function label($label) {
        $html = '';
        if ($label) {
            $classes = 'mail_label mail_label_'. $label->color() . ' mail_label_' . $label->id();
            $html .= html_writer::start_tag('span', array('class' => 'mail_toolbar_label'));
            $html .= html_writer::tag('span', s($label->name()), array('class' => $classes));
            $html .= html_writer::end_tag('span');
        }
        return $html;
    }

    public function label_course($course) {
        return html_writer::tag('span', s($course->shortname),
                                array('class' => 'mail_label mail_course'));
    }

    public function label_message($message, $type, $labelid, $mailview = false) {
        global $USER;

        $output = html_writer::start_tag('span', array('class' => 'mail_group_labels'));
        $labels = $message->labels($USER->id);
        foreach ($labels as $label) {
            if ($type === 'label' and $label->id() === $labelid) {
                continue;
            }
            $text = html_writer::tag('span', s($label->name()),
                array('class' => 'mail_label mail_label_'. $label->color() . ' mail_label_' . $label->id()));
            if ($mailview) {
                $linkparams = array('title' => get_string('showlabelmessages', 'local_mail', s($label->name())));
                $output .= html_writer::link(new moodle_url('/local/mail/view.php', array('t' => 'label', 'l' => $label->id())), $text, $linkparams);
            } else {
                $output .= $text;
            }
        }
        $output .= html_writer::end_tag('span');
        return $output;
    }

    public function label_draft() {
        $name = get_string('draft', 'local_mail');
        return html_writer::tag('span', s($name), array('class' => 'mail_label mail_draft'));
    }

    public function messagelist($messages, $userid, $type, $itemid, $offset) {

        $output = $this->output->container_start('mail_list');

        foreach ($messages as $message) {
            $unreadclass = '';
            $attributes = array(
                    'type' => 'checkbox',
                    'name' => 'msgs[]',
                    'value' => $message->id(),
                    'class' => 'mail_checkbox'
            );
            $checkbox = html_writer::start_tag('noscript');
            $checkbox .= html_writer::empty_tag('input', $attributes);
            $checkbox .= html_writer::end_tag('noscript');
            $checkbox .= html_writer::tag('span', '', array('class' => 'mail_adv_checkbox mail_checkbox0 mail_checkbox_value_' . $message->id()));
            $flags = '';
            if ($type !== 'trash') {
                $flags = $this->starred($message, $userid, $type, $offset);
            }
            $content = ($this->users($message, $userid, $type, $itemid) .
                        $this->summary($message, $userid, $type, $itemid) .
                        $this->attachment($message) .
                        $this->date($message));
            $context = context_course::instance($message->course()->id);
            $draftmessage = ($message->editable($userid) and
                    (array_key_exists($message->course()->id, local_mail_get_my_courses()) or has_capability('moodle/course:view', $context)));
            if ($draftmessage) {
                $url = new moodle_url('/local/mail/compose.php', array('m' => $message->id()));
            } else {
                $params = array('t' => $type, 'm' => $message->id(), 'offset' => $offset);
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

    public function paging($offset, $count, $totalcount) {
        if ($count == 1) {
            $a = array('index' => $offset + 1, 'total' => $totalcount);
            $str = get_string('pagingsingle', 'local_mail', $a);
        } else if ($totalcount > 0) {
            $a = array('first' => $offset + 1, 'last' => $offset + $count, 'total' => $totalcount);
            $str = get_string('pagingmultiple', 'local_mail', $a);
        } else {
            $str = '';
        }
        $str = html_writer::tag('span', $str);

        $prev = '<input value="' . $this->output->larrow() . '" type="submit" name="prevpage" title="'
            . get_string('previous') . '" class="mail_button singlebutton"';
        if (!$offset) {
            $prev .= ' disabled="disabled"';
        }
        $prev .= ' />';

        $next = '<input value="'. $this->output->rarrow() .'" type="submit" name="nextpage" title="'
            . get_string('next') . '" class="mail_button singlebutton"';
        if ($offset === false or ($offset + $count) == $totalcount) {
            $next .= ' disabled="disabled"';
        }
        $next .= ' />';
        return $this->output->container($str . ' ' . $prev . $next, 'mail_paging');
    }

    public function summary($message, $userid, $type, $itemid) {
        global $DB;

        $content = '';

        if ($type != 'drafts' and $message->draft()) {
            $content .= $this->label_draft();
        }

        if ($type != 'course' or $itemid != $message->course()->id) {
            $content .= $this->label_course($message->course());
        }

        $content .= $this->label_message($message, $type, $itemid);

        if ($message->subject()) {
            $content .= s($message->subject());
        } else {
            $content .= get_string('nosubject', 'local_mail');
        }
        return html_writer::tag('span', $content, array('class' => 'mail_summary'));
    }

    public function delete($trash) {
        $type = ($trash ? 'restore' : 'delete');
        $label = ($trash ? get_string('restore', 'local_mail') : get_string('delete'));
        $attributes = array(
            'type' => 'submit',
            'name' => 'delete',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        $output = html_writer::start_tag('noscript');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('noscript');
        $output .= html_writer::start_tag('span', array('id' => 'mail_'. $type, 'class' => 'singlebutton mail_button mail_button_disabled mail_hidden'));
        $output .= html_writer::tag('span', $label);
        $output .= html_writer::end_tag('span');
        return $output;
    }

    public function reply($enabled = true) {
        $label = get_string('reply', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'reply',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        if (!$enabled) {
            $attributes['disabled'] = 'disabled';
        }
        return html_writer::empty_tag('input', $attributes);
    }

    public function starred($message, $userid, $type, $offset = 0, $view = false) {
        $params = array(
                'starred' => $message->id(),
                'sesskey' => sesskey()
        );
        $url = new moodle_url($this->page->url, $params);
        $url->param('offset', $offset);
        $output = html_writer::start_tag('span', array('class' => 'mail_flags'));
        if ($view) {
            $url->param('m', $message->id());
            $url->remove_params(array('offset'));
        }
        if ($message->starred($userid)) {
            $linkparams = array('title' => get_string('starred', 'local_mail'));
            $output .= html_writer::link($url, html_writer::tag('span', '', array('class' => 'mail_starred')), $linkparams);
        } else {
            $linkparams = array('title' => get_string('unstarred', 'local_mail'));
            $output .= html_writer::link($url, html_writer::tag('span', '', array('class' => 'mail_unstarred')), $linkparams);
        }
        $output .= html_writer::end_tag('span');
        return $output;
    }

    public function forward($enabled = true) {
        $label = get_string('forward', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'forward',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        if (!$enabled) {
            $attributes['disabled'] = 'disabled';
        }
        return html_writer::empty_tag('input', $attributes);
    }

    public function replyall($enabled = false) {
        $label = get_string('replyall', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'replyall',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        if (!$enabled) {
            $attributes = array_merge($attributes, array('disabled' => 'disabled'));
        }
        return html_writer::empty_tag('input', $attributes);
    }

    public function labels($type) {
        global $USER;

        $items = array();
        $label = get_string('setlabels', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'assignlbl',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        $output = html_writer::start_tag('noscript');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('noscript');
        $output .= html_writer::start_tag('span', array('class' => 'singlebutton mail_button mail_assignlbl mail_button_disabled mail_hidden'));
        $output .= html_writer::tag('span', $label);
        $url = $this->output->pix_url('t/expanded', 'moodle');
        $output .= html_writer::empty_tag('img', array('src' => $url, 'alt' => 'expanded'));
        $output .= html_writer::end_tag('span');
        // List labels and options
        $labels = local_mail_label::fetch_user($USER->id);
        $output .= html_writer::start_tag('div', array('class' => 'mail_hidden mail_labelselect'));
        foreach ($labels as $key => $label) {
            $content = html_writer::tag('span', '', array('class' => 'mail_adv_checkbox mail_checkbox0 mail_label_value_'.$label->id()));
            $content .= html_writer::tag('span', $label->name(), array('class' => 'mail_label_name'));
            $items[$key] = $content;
        }
        if (!empty($labels)) {
            $items[] = html_writer::tag('span', '', array('class' => 'mail_menu_label_separator'));
        }
        $items[] = html_writer::link('#', get_string('newlabel', 'local_mail'), array('class' => 'mail_menu_label_newlabel'));
        if (!empty($labels)) {
            $items[] = html_writer::link('#', get_string('applychanges', 'local_mail'), array('class' => 'mail_menu_label_apply'));
        }
        $output .= html_writer::alist($items, array('class' => 'mail_menu_labels'));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function read() {
        $label = get_string('markasread', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'read',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        $output = html_writer::start_tag('noscript');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('noscript');
        return $output;
    }

    public function unread($type) {
        $label = get_string('markasunread', 'local_mail');
        $class = 'mail_button singlebutton';
        $attributes = array(
            'type' => 'submit',
            'name' => 'unread',
            'value' => $label,
            'class' => $class
        );
        $output = html_writer::start_tag('noscript');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('noscript');
        return $output;
    }

    public function selectall() {
        $output = html_writer::start_tag('span', array('class' => 'mail_hidden mail_button mail_checkbox_all'));
        $output .= html_writer::tag('span', '', array('class' => 'mail_selectall mail_adv_checkbox mail_checkbox0'));
        $url = $this->output->pix_url('t/expanded', 'moodle');
        $output .= html_writer::empty_tag('img', array('src' => $url, 'alt' => 'expand'));
        $output .= html_writer::end_tag('span');
        // Menu options
        $output .= html_writer::start_tag('div', array('class' => 'mail_hidden mail_optselect'));
        $items = array(
            'all' => get_string('all', 'local_mail'),
            'none' => get_string('none', 'local_mail'),
            'read' => get_string('read', 'local_mail'),
            'unread' => get_string('unread', 'local_mail'),
            'starred' => get_string('starred', 'local_mail'),
            'unstarred' => get_string('unstarred', 'local_mail')
        );
        foreach ($items as $key => $item) {
            $items[$key] = html_writer::link('#', $item, array('class' => 'mail_menu_option_' . $key));
        }
        $output .= html_writer::alist($items, array('class' => 'mail_menu_options'));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function goback() {
        $label = $this->output->larrow();
        $output = html_writer::start_tag('noscript');
        $output .= '<input type="submit" name="goback" value="'. $label .'" class="mail_button singlebutton" />';
        $output .= html_writer::end_tag('noscript');
        $output .= html_writer::tag('span', $label, array('class' => 'mail_hidden singlebutton mail_button mail_goback'));
        return $output;
    }

    public function moreactions() {
        $output = html_writer::start_tag('span', array('class' => 'mail_hidden singlebutton mail_button mail_more_actions'));
        $output .= html_writer::tag('span', get_string('moreactions', 'local_mail'));
        $url = $this->output->pix_url('t/expanded', 'moodle');
        $output .= html_writer::empty_tag('img', array('src' => $url, 'alt' => 'expanded'));
        $output .= html_writer::end_tag('span');
        // Menu options
        $output .= html_writer::start_tag('div', array('class' => 'mail_hidden mail_actselect'));
        $items = array(
            'markasread' => get_string('markasread', 'local_mail'),
            'markasunread' => get_string('markasunread', 'local_mail'),
            'markasstarred' => get_string('markasstarred', 'local_mail'),
            'markasunstarred' => get_string('markasunstarred', 'local_mail'),
            'separator' => '',
            'editlabel' => get_string('editlabel', 'local_mail'),
            'removelabel' => get_string('removelabel', 'local_mail')
        );
        foreach ($items as $key => $item) {
            $items[$key] = html_writer::link('#', $item, array('class' => 'mail_menu_action_' . $key));
        }
        $output .= html_writer::alist($items, array('class' => 'mail_menu_actions'));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function optlabels() {
        $label = get_string('editlabel', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'editlbl',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        $output = html_writer::start_tag('noscript');
        $output .= html_writer::tag('span', '', array('class' => 'mail_toolbar_sep'));
        $output .= html_writer::empty_tag('input', $attributes);
        $label = get_string('removelabel', 'local_mail');
        $attributes = array(
            'type' => 'submit',
            'name' => 'removelbl',
            'value' => $label,
            'class' => 'mail_button singlebutton'
        );
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('noscript');
        return $output;
    }


    public function search() {
        $output = html_writer::start_tag('span', array('id' => 'mail_search', 'class' => 'mail_hidden mail_search_button singlebutton mail_button'));
        $output .= html_writer::tag('span', get_string('search', 'local_mail'));
        $url = $this->output->pix_url('t/expanded', 'moodle');
        $output .= html_writer::empty_tag('img', array('src' => $url, 'alt' => 'expanded'));
        $output .= html_writer::end_tag('span');
        $attributes = array(
            'type' => 'text',
            'id' => 'textsearch',
            'name' => 'textsearch',
            'maxlength' => 45
        );
        $output .= html_writer::start_tag('div', array('id' => 'mail_menu_search', 'class' => 'mail_hidden mail_menu_search'));
        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('div');
        $url = $this->output->pix_url('t/collapsed', 'moodle');
        $output .= html_writer::start_tag('div', array('id' => 'mail_toggle_adv_search', 'class' => 'mail_adv_search'));
        $output .= html_writer::empty_tag('img', array('id' => 'mail_adv_status', 'src' => $url, 'alt' => 'collapsed'));
        $output .= html_writer::tag('span', get_string('advsearch', 'local_mail'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('id' => 'mail_adv_search', 'class' => 'mail_hidden'));
        $attributes = array(
            'type' => 'text',
            'id' => 'textsearchfrom',
            'name' => 'textsearchfrom',
            'maxlength' => 45
        );
        $text = get_string('from', 'local_mail');
        $output .= html_writer::label($text, 'textsearchfrom');
        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('div');
        $attributes = array(
            'type' => 'text',
            'id' => 'textsearchto',
            'name' => 'textsearchto',
            'maxlength' => 45
        );
        $text = get_string('to', 'local_mail');
        $output .= html_writer::label($text, 'textsearchto');
        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('div');
        $attributes = array(
            'type' => 'checkbox',
            'id' => 'searchunread',
            'name' => 'searchunread'
        );
        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', $attributes);
        $text = get_string('searchbyunread', 'local_mail');
        $output .= html_writer::label($text, 'searchunread');
        $output .= html_writer::end_tag('div');
        $attributesattach = array(
            'type' => 'checkbox',
            'id' => 'searchattach',
            'name' => 'searchattach'
        );
        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', $attributesattach);
        $text = get_string('searchbyattach', 'local_mail');
        $output .= html_writer::label($text, 'searchattach');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'mail_search_datepicker'));
        $text = get_string('filterbydate', 'local_mail');
        $output .= html_writer::label($text, null);
        $output .= html_writer::start_tag('span', array('class' => 'mail_search_date'));
        $output .= html_writer::tag('span', '', array('id' => 'searchdate', ''));
        $url = $this->output->pix_url('i/calendar', 'core');
        $output .= html_writer::empty_tag('img', array('id' => 'mail_toggle_datepicker', 'class' => 'mail_toggle_datepicker', 'src' => $url, 'alt' => 'calendar'));
        $output .= html_writer::end_tag('span');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $attributes = array(
            'type' => 'button',
            'id' => 'buttoncancelsearch',
            'name' => 'buttoncancelsearch',
            'value' => get_string('cancel', 'local_mail'),
            'class' => 'mail_button_cancel_search mail_button mail_hidden'
        );
        $output .= html_writer::empty_tag('input', $attributes);
        $attributes = array(
            'type' => 'button',
            'id' => 'buttonsearch',
            'name' => 'buttonsearch',
            'value' => get_string('search', 'local_mail'),
            'class' => 'mail_button_search mail_button'
        );
        $output .= html_writer::empty_tag('input', $attributes);
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function editlabelform() {
        $content = html_writer::start_tag('div', array('id' => 'local_mail_form_edit_label', 'class' => 'local_mail_form mail_hidden'));
        $content .= html_writer::start_tag('div', array('class' => 'label_form'));
        $content .= html_writer::start_tag('div', array('class' => 'label_name'));
        $text = get_string('labelname', 'local_mail');
        $content .= html_writer::label($text, 'local_mail_edit_label_name');
        $content .= html_writer::empty_tag('input', array(
                'type' => 'text',
                'id' => 'local_mail_edit_label_name',
                'name' => 'local_mail_edit_label_name',
                'value' => '',
        ));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::start_tag('div', array('class' => 'label_color'));
        $colors = local_mail_label::valid_colors();
        foreach ($colors as $color) {
            $options[$color] = $color;
        }
        $text = get_string('labelcolor', 'local_mail');
        $content .= html_writer::label($text, 'local_mail_edit_label_color');
        $text = get_string('nocolor', 'local_mail');
        $content .= html_writer::select($options, 'local_mail_edit_label_color', '', array('' => $text),
            array('id' => 'local_mail_edit_label_color', 'class' => 'mail_label_colors'));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');
        return $content;
    }

    public function newlabelform() {
        $content = html_writer::start_tag('div', array('id' => 'local_mail_form_new_label', 'class' => 'local_mail_form mail_hidden'));
        $content .= html_writer::start_tag('div', array('class' => 'label_form'));
        $content .= html_writer::start_tag('div', array('class' => 'label_name'));
        $text = get_string('labelname', 'local_mail');
        $content .= html_writer::label($text, 'local_mail_new_label_name');
        $content .= html_writer::empty_tag('input', array(
                'type' => 'text',
                'id' => 'local_mail_new_label_name',
                'name' => 'local_mail_new_label_name',
                'value' => '',
        ));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::start_tag('div', array('class' => 'label_color'));
        $colors = local_mail_label::valid_colors();
        foreach ($colors as $color) {
            $options[$color] = $color;
        }
        $text = get_string('labelcolor', 'local_mail');
        $content .= html_writer::label($text, 'local_mail_new_label_color');
        $text = get_string('nocolor', 'local_mail');
        $content .= html_writer::select($options, 'local_mail_new_label_color', '', array('' => $text),
            array('id' => 'local_mail_new_label_color', 'class' => 'mail_label_colors'));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');
        return $content;
    }

    public function recipientsform($courseid, $userid) {
        global $COURSE, $DB;

        $options = array();

        $owngroups = groups_get_user_groups($courseid, $userid);
        $content = html_writer::start_tag('div', array('id' => 'local_mail_recipients_form', 'class' => 'local_mail_form mail_hidden'));

        if ($COURSE->groupmode == SEPARATEGROUPS and empty($owngroups[0])) {
            return '';
        }
        $content .= html_writer::start_tag('div', array('class' => 'mail_recipients_toolbar'));

        // Roles
        $context = context_course::instance($courseid);
        $roles = get_roles_used_in_context($context);
        foreach ($roles as $key => $role) {
            $count = $DB->count_records_select('role_assignments', "contextid = :contextid AND roleid = :roleid AND userid <> :userid",
                array('contextid' => $context->id, 'roleid' => $role->id, 'userid' => $userid));
            if ($count) {
                $options[$key] = ($role->name ? $role->name : $role->shortname);
            }
        }
        $text = get_string('role', 'moodle');
        $content .= html_writer::start_tag('span', array('class' => 'roleselector'));
        $content .= html_writer::label($text, 'local_mail_roles');
        $text = get_string('all', 'local_mail');
        $content .= html_writer::select($options, 'local_mail_roles', '', array('' => $text), array('id' => 'local_mail_recipients_roles', 'class' => ''));
        $content .= html_writer::end_tag('span');
        // Groups
        $groups = groups_get_all_groups($courseid);
        if ($COURSE->groupmode == NOGROUPS or ($COURSE->groupmode == VISIBLEGROUPS and empty($groups))) {
            $content .= html_writer::tag('span', get_string('allparticipants', 'moodle'), array('class' => 'groupselector groupname'));
        } else {
            if ($COURSE->groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $context)) {
                unset($options);
                foreach ($groups as $key => $group) {
                    $options[$key] = $group->name;
                }
                $text = get_string('group', 'moodle');
                $content .= html_writer::start_tag('span', array('class' => 'groupselector'));
                $content .= html_writer::label($text, 'local_mail_recipients_groups');
                $text = get_string('allparticipants', 'moodle');
                $content .= html_writer::select($options, 'local_mail_recipients_groups', '', array('' => $text), array('id' => 'local_mail_recipients_groups', 'class' => ''));
                $content .= html_writer::end_tag('span');
            } else if (count($owngroups[0]) == 1) {// SEPARATEGROUPS and user in only one group
                $text = get_string('group', 'moodle');
                $content .= html_writer::start_tag('span', array('class' => 'groupselector'));
                $content .= html_writer::label("$text: ", null);
                $content .= html_writer::tag('span', groups_get_group_name($owngroups[0][0]), array('class' => 'groupname'));
                $content .= html_writer::end_tag('span');
            } else if (count($owngroups[0]) > 1) {// SEPARATEGROUPS and user in several groups
                unset($options);
                foreach ($owngroups[0] as $key => $group) {
                    $options[$group] = groups_get_group_name($group);
                }
                $text = get_string('group', 'moodle');
                $content .= html_writer::start_tag('span', array('class' => 'groupselector'));
                $content .= html_writer::label($text, 'local_mail_recipients_groups');
                $text = get_string('allparticipants', 'moodle');
                $content .= html_writer::select($options, 'local_mail_recipients_groups', '',
                    array(key($options) => current($options)), array('id' => 'local_mail_recipients_groups', 'class' => ''));
                $content .= html_writer::end_tag('span');
            }
        }
        $content .= html_writer::tag('div', '', array('class' => 'mail_separator'));
        // Search
        $content .= html_writer::start_tag('div', array('class' => 'mail_recipients_search'));
        $attributes = array(
                'type'  => 'text',
                'name'  => 'recipients_search',
                'value' => '',
                'maxlength' => '100',
                'class' => 'mail_search'
        );
        $text = get_string('search', 'local_mail');
        $content .= html_writer::label($text, 'recipients_search');
        $content .= html_writer::empty_tag('input', $attributes);
        // Select all recipients
        $content .= html_writer::start_tag('span', array('class' => 'mail_all_recipients_actions'));
        $attributes = array(
           'type' => 'button',
           'name' => "to_all",
           'value' => get_string('to', 'local_mail')
        );
        $content .= html_writer::empty_tag('input', $attributes);
        $attributes = array(
           'type' => 'button',
           'name' => "cc_all",
           'value' => get_string('cc', 'local_mail')
        );
        $content .= html_writer::empty_tag('input', $attributes);
        $attributes = array(
           'type' => 'button',
           'name' => "bcc_all",
           'value' => get_string('bcc', 'local_mail')
        );
        $content .= html_writer::empty_tag('input', $attributes);
        $attributes = array('type' => 'image',
                                'name' => "remove_all",
                                'src' => $this->output->pix_url('t/delete'),
                                'alt' => get_string('remove'));
        $content .= html_writer::empty_tag('input', $attributes);
        $content .= html_writer::end_tag('span');
        $content .= html_writer::end_tag('div');

        $content .= html_writer::end_tag('div');
        $content .= html_writer::tag('div', '', array('id' => 'local_mail_recipients_list', 'class' => 'mail_form_recipients'));
        $content .= html_writer::start_tag('div', array('class' => 'mail_recipients_loading'));
        $content .= $this->output->pix_icon('i/loading', get_string('actions'), 'moodle', array('class' => 'loading_icon'));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');
        return $content;
    }

    public function recipientslist($participants) {
        $content = '';
        if ($participants === false) {
            return get_string('toomanyrecipients', 'local_mail');
        } else if (empty($participants)) {
            return '';
        }
        foreach ($participants as $key => $participant) {
            $selected = ($participant->role == 'to' or $participant->role == 'cc' or $participant->role == 'bcc');
            if ($selected) {
                $rolestring = get_string('shortadd'.$participant->role, 'local_mail').':';
                $hidden = '';
                $recipselected = ' mail_recipient_selected';
            } else {
                $rolestring = '';
                $hidden = ' mail_hidden';
                $recipselected = '';
            }
            $content .= html_writer::start_tag('div', array('class' => 'mail_form_recipient' . $recipselected));
            $content .= html_writer::tag('span', $rolestring, array('class' => 'mail_form_recipient_role' . $hidden, 'data-role-recipient' => $participant->id));
            $content .= $this->output->user_picture($participant, array('link' => false, 'alttext' => false));
            $content .= html_writer::tag('span', fullname($participant), array('class' => 'mail_form_recipient_name'));
            $content .= html_writer::start_tag('span', array('class' => 'mail_recipient_actions'));
            $attributes = array(
               'type' => 'button',
               'name' => "to[{$participant->id}]",
               'value' => get_string('to', 'local_mail')
            );
            if ($selected) {
                $attributes['disabled'] = 'disabled';
                $attributes['class'] = 'mail_hidden';
            }
            $content .= html_writer::empty_tag('input', $attributes);
            $attributes = array(
               'type' => 'button',
               'name' => "cc[{$participant->id}]",
               'value' => get_string('shortaddcc', 'local_mail')
            );
            if ($selected) {
                $attributes['disabled'] = 'disabled';
                $attributes['class'] = 'mail_hidden';
            }
            $content .= html_writer::empty_tag('input', $attributes);
            $attributes = array(
               'type' => 'button',
               'name' => "bcc[{$participant->id}]",
               'value' => get_string('shortaddbcc', 'local_mail')
            );
            if ($selected) {
                $attributes['disabled'] = 'disabled';
                $attributes['class'] = 'mail_hidden';
            }
            $content .= html_writer::empty_tag('input', $attributes);
            $attributes = array('type' => 'image',
                            'name' => "remove[{$participant->id}]",
                            'src' => $this->output->pix_url('t/delete'),
                            'alt' => get_string('remove'));
            if (!$selected) {
                $attributes['class'] = 'mail_novisible';
                $attributes['disabled'] = 'disabled';
            }
            $content .= html_writer::empty_tag('input', $attributes);
            $content .= html_writer::end_tag('span');
            $content .= html_writer::end_tag('div');
        }
        $content .= html_writer::end_tag('div');
        return $content;
    }

    public function references($references, $reply = false) {
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

    public function mail($message, $reply = false, $offset = 0) {
        global $CFG, $USER;

        $totalusers = 0;
        $output = '';

        if (!$reply) {
            $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'm',
                    'value' => $message->id(),
            ));

            $output .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'offset',
                    'value' => $offset,
            ));
        }

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
        $output .= $this->newlabelform();
        if (!$reply) {
            if ($message->sender()->id !== $USER->id) {
                $output .= $this->toolbar('reply', $message->course()->id, array('replyall' => ($totalusers > 1)));
            } else {
                $output .= $this->toolbar('forward', $message->course()->id);
            }
        }
        $output .= $this->output->container_end();
        return $output;
    }

    public function toolbar($type, $courseid = 0, $params = null) {

        $replyall = isset($params['replyall']) ? $params['replyall'] : false;
        $paging = isset($params['paging']) ? $params['paging'] : null;
        $trash = isset($params['trash']) ? $params['trash'] : false;
        $labelid = isset($params['labelid']) ? $params['labelid'] : false;

        $toolbardown = false;
        if ($type === 'reply') {
            $viewcourse = array_key_exists($courseid, local_mail_get_my_courses());
            $output = $this->reply($viewcourse);
            // all recipients
            $output .= $this->replyall(($viewcourse and $replyall));
            $output .= $this->forward($viewcourse);
            $toolbardown = true;
        } else if ($type === 'forward') {
            $viewcourse = array_key_exists($courseid, local_mail_get_my_courses());
            $output = $this->forward($viewcourse);
            $toolbardown = true;
        } else {
            $selectall = $this->selectall();
            $delete = $this->delete($trash);
            $labels = $extended = $goback = $search = $selectedlbl = '';
            if (!$trash and $type !== 'trash') {
                $labels = $this->labels($type);
            }
            $read = $unread = '';
            if ($type !== 'drafts') {
                $unread = $this->unread($type);
            }
            $pagingbar = '';
            if ($type !== 'view') {
                if ($type !== 'drafts') {
                    $read = $this->read();
                }
                $pagingbar = $this->paging($paging['offset'],
                                        $paging['count'],
                                        $paging['totalcount']);
                $search = $this->search();
            } else {
                $goback = $this->goback($paging);
            }
            if ($type === 'label') {
                $extended = $this->optlabels();
                $selectedlbl = $this->label(local_mail_label::fetch($labelid));
            }
            $more = $this->moreactions();
            $clearer = $this->output->container('', 'clearer');
            $left = html_writer::tag('div',
                $goback . $selectall . $labels . $read . $unread . $delete . $extended . $more . $search . $selectedlbl,
                array('class' => 'mail_buttons'));
            $output = $left . $pagingbar . $clearer;
        }
        return $this->output->container($output, ($toolbardown ? 'mail_toolbar_down' : 'mail_toolbar'));
    }

    public function notification_bar() {
        $output = html_writer::start_tag('div', array('id' => 'mail_notification', 'class' => 'mail_novisible mail_notification'));
        $output .= html_writer::tag('span', '', array('id' => 'mail_notification_message'));
        $output .= html_writer::link('#', get_string('undo', 'local_mail'), array('id' => 'mail_notification_undo', 'class' => 'mail_novisible'));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function users($message, $userid, $type, $itemid) {
        global $DB;
        if ($userid == $message->sender()->id) {
            if ($users = array_merge($message->recipients('to'), $message->recipients('cc'), $message->recipients('bcc'))) {
                $content = implode(', ', array_map('fullname', $users));
            } else {
                $content = get_string('norecipient', 'local_mail');
            }
        } else {
            $content = fullname($message->sender());
        }
        return html_writer::tag('span', s($content), array('class' => 'mail_users'));
    }

    public function perpage($offset, $mailpagesize) {
        $nums = array('5', '10', '20', '50', '100');
        $cont = count($nums) - 1;
        $sesskey = sesskey();
        $perpage = html_writer::start_tag('span');
        $params = array(
                'perpage' => '',
                'offset' => $offset,
                'sesskey' => $sesskey
        );
        foreach ($nums as $num) {
            $params['perpage'] = $num;
            if ($mailpagesize == $num) {
                $perpage .= html_writer::start_tag('strong');
            }
            $url = new moodle_url($this->page->url, $params);
            $perpage .= html_writer::link($url, $num);
            if ($mailpagesize == $num) {
                $perpage .= html_writer::end_tag('strong');
            }
            if ($cont) {
                $perpage .= ' | ';
            }
            $cont -= 1;
        }
        $perpage .= html_writer::end_tag('span');
        return get_string('perpage', 'local_mail', $perpage);
    }

    public function view($params) {
        global $USER;

        $content = '';

        $type = $params['type'];
        $itemid = !empty($params['itemid']) ? $params['itemid'] : 0;
        $userid = $params['userid'];
        $messages = $params['messages'];
        $count = count($messages);
        $offset = $params['offset'];
        $totalcount = $params['totalcount'];
        $ajax = !empty($params['ajax']);
        $mailpagesize = get_user_preferences('local_mail_mailsperpage', MAIL_PAGESIZE, $USER->id);

        if (!$ajax) {
            $url = new moodle_url($this->page->url);
            $content .= html_writer::start_tag('form', array('method' => 'post', 'action' => $url, 'id' => 'local_mail_main_form'));
        }
        $paging = array(
            'offset' => $offset,
            'count' => $count,
            'totalcount' => $totalcount,
            'pagesize' => $mailpagesize,
        );
        if (!$messages) {
            $paging['offset'] = false;
        }

        $content .= $this->toolbar($type, 0, array('paging' => $paging, 'trash' => ($type === 'trash'), 'labelid' => $itemid));
        $content .= $this->notification_bar();
        if ($messages) {
            $content .= $this->messagelist($messages, $userid, $type, $itemid, $offset);
        } else {
            $content .= $this->output->container_start('mail_list');
            $string = get_string('nomessagestoview', 'local_mail');
            $initurl = new moodle_url('/local/mail/view.php');
            $initurl->param('t' , $type);
            if ($type === 'label') {
                $initurl->param('l', $itemid);
            }
            $link = html_writer::link($initurl, get_string('showrecentmessages', 'local_mail'));
            $content .= html_writer::tag('div', $string.' '.$link, array('class' => 'mail_item'));
            $content .= $this->output->container_end();
        }
        $content .= html_writer::start_tag('div', array('class' => 'mail_hidden mail_search_loading'));
        $content .= $this->output->pix_icon('i/loading', get_string('actions'), 'moodle', array('class' => 'loading_icon'));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::start_tag('div');
        $content .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'type',
                'value' => $type,
        ));
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
        $content .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'itemid',
                'value' => $itemid,
        ));
        $content .= $this->editlabelform();
        $content .= $this->newlabelform();
        $content .= html_writer::end_tag('div');
        $content .= html_writer::start_tag('div', array('class' => 'mail_perpage'));
        $content .= $this->perpage($offset, $mailpagesize);
        $content .= html_writer::end_tag('div');
        if (!$ajax) {
            $content .= html_writer::end_tag('form');
        }

        return $this->output->container($content);
    }
}
