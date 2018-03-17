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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/mail/locallib.php');


function local_mail_extend_navigation($root) {
    global $CFG, $COURSE, $PAGE, $SESSION, $SITE, $USER;

    if (!get_config('local_mail', 'version')) {
        return;
    }

    // User profile.

    if (empty($CFG->messaging) and
        $PAGE->url->compare(new moodle_url('/user/view.php'), URL_MATCH_BASE)) {
        $userid = optional_param('id', false, PARAM_INT);
        if (local_mail_valid_recipient($userid)) {
            $vars = array('course' => $COURSE->id, 'recipient' => $userid);
            $PAGE->requires->string_for_js('sendmessage', 'local_mail');
            $PAGE->requires->js_init_code('M.local_mail = ' . json_encode($vars));
            $PAGE->requires->js('/local/mail/user.js');
        }
    }

    // Users list.

    if (empty($CFG->messaging) and
        $PAGE->url->compare(new moodle_url('/user/index.php'), URL_MATCH_BASE)) {
        $userid = optional_param('id', false, PARAM_INT);
        $vars = array('course' => $COURSE->id);
        $PAGE->requires->string_for_js('choosedots', 'moodle');
        $PAGE->requires->strings_for_js(array(
                'bulkmessage',
                'to',
                'cc',
                'bcc',
                ), 'local_mail');
        $PAGE->requires->js_init_code('M.local_mail = ' . json_encode($vars));
        $PAGE->requires->js('/local/mail/users.js');
    }

    // Block completion_progress.

    if ($PAGE->url->compare(new moodle_url('/blocks/completion_progress/overview.php'), URL_MATCH_BASE)) {
        $userid = optional_param('id', false, PARAM_INT);
        $vars = array('course' => $COURSE->id);
        $PAGE->requires->string_for_js('choosedots', 'moodle');
        $PAGE->requires->strings_for_js(array(
                'bulkmessage',
                'to',
                'cc',
                'bcc',
                ), 'local_mail');
        $PAGE->requires->js_init_code('M.local_mail = ' . json_encode($vars));
        $PAGE->requires->js('/local/mail/users.js');
    }

    // Add "My mail" navigation if enabled.

    if (!get_config('local_mail', 'legacynav')) {
        return;
    }

    $courses = local_mail_get_my_courses();

    $count = local_mail_message::count_menu($USER->id);

    $text = get_string('mymail', 'local_mail');
    if (!empty($count->inbox)) {
        $text .= ' (' . $count->inbox . ')';
    }
    $node = navigation_node::create($text, null, navigation_node::TYPE_ROOTNODE);
    if (!empty($count->inbox)) {
        $node->add_class('local_mail_new_messages');
    }
    $child = $root->add_node($node, 'mycourses');
    $child->add_class('mail_root');

    // Compose.

    $text = get_string('compose', 'local_mail');
    $url = new moodle_url('/local/mail/compose.php');
    $urlrecipients = new moodle_url('/local/mail/recipients.php');

    if ($PAGE->url->compare($url, URL_MATCH_BASE) or
        $PAGE->url->compare($urlrecipients, URL_MATCH_BASE)) {
        $url->param('m', $PAGE->url->param('m'));
    } else {
        $url = new moodle_url('/local/mail/create.php');
        if ($COURSE->id != $SITE->id) {
            $url->param('c', $COURSE->id);
            $url->param('sesskey', sesskey());
        }
    }

    $node->add(s($text), $url);

    // Inbox.

    $text = get_string('inbox', 'local_mail');
    if (!empty($count->inbox)) {
        $text .= ' (' . $count->inbox . ')';
    }
    $url = new moodle_url('/local/mail/view.php', array('t' => 'inbox'));
    $child = $node->add(s($text), $url);
    $child->add_class('mail_inbox');

    // Starred.

    $text = get_string('starredmail', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'starred'));
    $node->add(s($text), $url);

    // Drafts.

    $text = get_string('drafts', 'local_mail');
    if (!empty($count->drafts)) {
        $text .= ' (' . $count->drafts . ')';
    }
    $url = new moodle_url('/local/mail/view.php', array('t' => 'drafts'));
    $child = $node->add(s($text), $url);
    $child->add_class('mail_drafts');

    // Sent.

    $text = get_string('sentmail', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'sent'));
    $node->add(s($text), $url);

    // Courses.

    if ($courses) {
        $text = get_string('courses', 'local_mail');
        $nodecourses = $node->add($text, null, navigation_node::TYPE_CONTAINER);
        foreach ($courses as $course) {
            $text = $course->shortname;
            if (!empty($count->courses[$course->id])) {
                $text .= ' (' . $count->courses[$course->id] . ')';
            }
            $params = array('t' => 'course', 'c' => $course->id);
            $url = new moodle_url('/local/mail/view.php', $params);
            $child = $nodecourses->add(s($text), $url);
            $child->hidden = !$course->visible;
            $child->add_class('mail_course_'.$course->id);
        }
    }

    // Labels.

    $labels = local_mail_label::fetch_user($USER->id);
    if ($labels) {
        $text = get_string('labels', 'local_mail');
        $nodelabels = $node->add($text, null, navigation_node::TYPE_CONTAINER);
        foreach ($labels as $label) {
            $text = $label->name();
            if (!empty($count->labels[$label->id()])) {
                $text .= ' (' . $count->labels[$label->id()] . ')';
            }
            $params = array('t' => 'label', 'l' => $label->id());
            $url = new moodle_url('/local/mail/view.php', $params);
            $child = $nodelabels->add(s($text), $url);
            $child->add_class('mail_label_'.$label->id());
        }
    }

    // Trash.

    $text = get_string('trash', 'local_mail');
    $url = new moodle_url('/local/mail/view.php', array('t' => 'trash'));
    $node->add(s($text), $url);

    // Preferences.

    $text = get_string('preferences');
    $url = new moodle_url('/local/mail/preferences.php');
    $node->add(s($text), $url);
}

function local_mail_pluginfile($course, $cm, $context, $filearea, $args,
                               $forcedownload, array $options=array()) {
    global $SITE, $USER;

    require_login($SITE, false);

    // Check message.

    $messageid = (int) array_shift($args);
    $message = local_mail_message::fetch($messageid);
    if ($filearea != 'message' or !$message or !$message->viewable($USER->id, true)) {
        return false;
    }

    // Fetch file info.

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_mail/$filearea/$messageid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true, $options);
}

/**
 * Renders the navigation bar link.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function local_mail_render_navbar_output(\renderer_base $renderer) {
    global $CFG, $COURSE, $PAGE, $USER;

    if (!isloggedin() or isguestuser() or user_not_fully_set_up($USER) or
            get_user_preferences('auth_forcepasswordchange') or
            ($CFG->sitepolicy and !$USER->policyagreed and !is_siteadmin())) {
        return '';
    }

    $composeurl = new moodle_url('/local/mail/compose.php');
    if ($PAGE->url->compare($composeurl, URL_MATCH_BASE)) {
        $composeurl->param('m', $PAGE->url->param('m'));
    } else {
        $composeurl = new moodle_url('/local/mail/create.php');
        if ($COURSE->id != SITEID) {
            $composeurl->param('c', $COURSE->id);
            $composeurl->param('sesskey', sesskey());
        }
    }

    $preferencesurl = new moodle_url('/local/mail/preferences.php');
    $viewurl = new moodle_url('/local/mail/view.php');

    $activetype = null;
    $activecourseid = null;
    $activelabelid = null;
    if ($PAGE->url->compare($viewurl, URL_MATCH_BASE)) {
        $activetype = $PAGE->url->param('t');
        if ($activetype == 'course') {
            $activecourseid = $PAGE->url->param('c');
        } else if ($activetype == 'label') {
            $activelabelid = $PAGE->url->param('l');
        }
    }

    $count = local_mail_message::count_menu($USER->id);

    $context = [
        'activetype' => $activetype,
        'activecourseid' => $activecourseid,
        'activelabelid' => $activelabelid,
        'composeurl' => $composeurl->out(),
        'preferencesurl' => $preferencesurl->out(),
        'viewurl' => $viewurl->out(),
        'count' => isset($count->inbox) ? $count->inbox : 0,
        'items' => [
            [
                'url' => new moodle_url($viewurl, ['t' => 'inbox']),
                'icon' => 'inbox',
                'text' => get_string('inbox', 'local_mail'),
                'unread' => isset($count->inbox) ? $count->inbox : 0,
                'active' => ($activetype == 'inbox'),
            ],
            [
                'url' => new moodle_url($viewurl, ['t' => 'starred']),
                'icon' => 'starred',
                'text' => get_string('starred', 'local_mail'),
                'active' => ($activetype == 'starred'),
            ],
            [
                'url' => new moodle_url($viewurl, ['t' => 'drafts']),
                'icon' => 'drafts',
                'text' => get_string('drafts', 'local_mail'),
                'drafts' => isset($count->drafts) ? $count->drafts : 0,
                'active' => ($activetype == 'drafts'),
            ],
            [
                'url' => new moodle_url($viewurl, ['t' => 'sent']),
                'icon' => 'sent',
                'text' => get_string('sentmail', 'local_mail'),
                'active' => ($activetype == 'sent'),
            ],
            [
                'url' => new moodle_url($viewurl, ['t' => 'trash']),
                'icon' => 'trash',
                'text' => get_string('trash', 'local_mail'),
                'active' => ($activetype == 'trash'),
            ],
        ],
    ];

    foreach (local_mail_label::fetch_user($USER->id) as $label) {
        $context['items'][] = [
            'url' => new moodle_url($viewurl, ['t' => 'label', 'l' => $label->id()]),
            'icon' => 'label',
            'text' => $label->name(),
            'unread' => isset($count->labels[$label->id()]) ? $count->labels[$label->id()] : 0,
            'active' => ($activelabelid == $label->id()),
        ];
    }

    foreach (local_mail_get_my_courses() as $course) {
        $context['items'][] = [
            'url' => new moodle_url($viewurl, ['t' => 'course', 'c' => $course->id]),
            'icon' => 'course',
            'text' => $course->shortname,
            'title' => $course->fullname,
            'unread' => isset($count->courses[$course->id]) ? $count->courses[$course->id] : 0,
            'dimmed' => !$course->visible,
            'active' => ($activecourseid == $course->id),
        ];
    }

    return $renderer->render_from_template('local_mail/navbar_popover', $context);
}

/**
 * Get icon mapping for font-awesome.
 */
function local_mail_get_fontawesome_icon_map() {
    return [
        'local_mail:compose' => 'fa-pencil-square-o',
        'local_mail:course' => 'fa-university',
        'local_mail:drafts' => 'fa-file',
        'local_mail:icon' => 'fa-envelope',
        'local_mail:inbox' => 'fa-inbox',
        'local_mail:label' => 'fa-tag',
        'local_mail:sent' => 'fa-paper-plane',
        'local_mail:starred' => 'fa-star',
        'local_mail:trash' => 'fa-trash',
    ];
}
