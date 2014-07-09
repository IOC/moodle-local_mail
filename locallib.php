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

require_once($CFG->libdir . '/filelib.php');
require_once('label.class.php');
require_once('message.class.php');
require_once($CFG->dirroot.'/group/lib.php');

define('MAIL_PAGESIZE', 10);
define('LOCAL_MAIL_MAXFILES', 5);
define('LOCAL_MAIL_MAXBYTES', 1048576);

function local_mail_attachments($message) {
    $context = context_course::instance($message->course()->id);
    $fs = get_file_storage();
    return $fs->get_area_files($context->id, 'local_mail', 'message',
                               $message->id(), 'filename', false);
}

function local_mail_format_content($message) {
    $context = context_course::instance($message->course()->id);
    $content = file_rewrite_pluginfile_urls($message->content(), 'pluginfile.php', $context->id,
                                            'local_mail', 'message', $message->id());
    return format_text($content, $message->format());
}

function local_mail_setup_page($course, $url) {
    global $DB, $PAGE;

    require_login($course->id, false);

    $PAGE->set_url($url);
    $title = get_string('mymail', 'local_mail');
    $PAGE->set_title($course->shortname . ': ' . $title);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_heading($course->fullname);
    $PAGE->requires->css('/local/mail/styles.css');

    if ($course->id != SITEID) {
        $PAGE->navbar->add(get_string('mymail', 'local_mail'));
        $urlcompose = new moodle_url('/local/mail/compose.php');
        $urlrecipients = new moodle_url('/local/mail/recipients.php');
        if ($url->compare($urlcompose, URL_MATCH_BASE) or
            $url->compare($urlrecipients, URL_MATCH_BASE)) {
            $text = get_string('compose', 'local_mail');
            $urlcompose->param('m', $url->param('m'));
            $PAGE->navbar->add($text, $urlcompose);
        }
    }
}

function local_mail_send_notifications($message) {
    global $SITE;

    $plaindata = new stdClass;
    $htmldata = new stdClass;

    // Send the mail now!
    foreach ($message->recipients() as $userto) {

        $plaindata->user = fullname($message->sender());
        $plaindata->subject = $message->subject();

        $htmldata->user = fullname($message->sender());
        $htmldata->subject = $message->subject();
        $url = new moodle_url('/local/mail/view.php', array('t' => 'inbox', 'm' => $message->id()));
        $htmldata->url = $url->out(false);

        $eventdata = new stdClass();
        $eventdata->component         = 'local_mail';
        $eventdata->name              = 'mail';
        $eventdata->userfrom          = $message->sender();
        $eventdata->userto            = $userto;
        $eventdata->subject           = get_string('notificationsubject', 'local_mail', $SITE->shortname);
        $eventdata->fullmessage       = get_string('notificationbody', 'local_mail', $plaindata);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = get_string('notificationbodyhtml', 'local_mail', $htmldata);
        $eventdata->notification      = 1;

        $smallmessagestrings = new stdClass();
        $smallmessagestrings->user = fullname($message->sender());
        $smallmessagestrings->message = $message->subject();
        $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'local_mail', $smallmessagestrings);

        $url = new moodle_url('/local/mail/view.php', array('t' => 'inbox', 'm' => $message->id()));
        $eventdata->contexturl = $url->out(false);
        $eventdata->contexturlname = $message->subject();

        $mailresult = message_send($eventdata);
        if (!$mailresult) {
            mtrace("Error: local/mail/locallib.php local_mail_send_mail(): Could not send out mail for id {$message->id()} to user {$message->sender()->id}".
                    " ($userto->email) .. not trying again.");
            add_to_log($message->course()->id, 'local_mail', 'mail error', "view_inbox.php?m={$message->id()}",
                    substr(format_string($message->subject(), true), 0, 30), 0, $message->sender()->id);
        }
    }
}

function local_mail_js_labels() {
    global $USER;

    $labels = local_mail_label::fetch_user($USER->id);
    $js = 'M.local_mail = {mail_labels: {';
    $cont = 0;
    $total = count($labels);
    foreach ($labels as $label) {
        $js .= '"'.$label->id().'":{"id": "' . $label->id() . '", "name": "' . s($label->name()) . '", "color": "' . $label->color() . '"}';
        $cont++;
        if ($cont < $total) {
            $js .= ',';
        }
    }
    $js .= '}};';
    return $js;
}

function local_mail_get_my_courses() {
    static $courses = null;

    if ($courses === null) {
        $courses = enrol_get_my_courses();

        foreach ($courses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            if (!has_capability('local/mail:usemail', $context)) {
                unset($courses[$course->id]);
            }
        }
    }

    return $courses;
}

function local_mail_valid_recipient($recipient) {
    global $COURSE, $USER;

    if (!$recipient or $recipient == $USER->id) {
        return false;
    }

    $context = context_course::instance($COURSE->id);

    if (!is_enrolled($context, $recipient)) {
        return false;
    }

    if ($COURSE->groupmode == SEPARATEGROUPS and
        !has_capability('moodle/site:accessallgroups', $context)) {
        $ugroups = groups_get_all_groups($COURSE->id, $USER->id,
                                         $COURSE->defaultgroupingid, 'g.id');
        $rgroups = groups_get_all_groups($COURSE->id, $recipient,
                                         $COURSE->defaultgroupingid, 'g.id');
        if (!array_intersect(array_keys($ugroups), array_keys($rgroups))) {
            return false;
        }
    }

    return true;
}

function local_mail_add_recipients($message, $recipients, $role) {
    global $DB;

    $context = context_course::instance($message->course()->id);
    $groupid = 0;
    $severalseparategroups = false;
    $roles = array('to', 'cc', 'bcc');
    $role = ($role >= 0 and $role < 3) ? $role : 0;

    if ($message->course()->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
        $groups = groups_get_user_groups($message->course()->id, $message->sender()->id);
        if (count($groups[0]) == 0) {
            return;
        } else if (count($groups[0]) == 1) {// Only one group
            $groupid = $groups[0][0];
        } else {
            $severalseparategroups = true;// Several groups
        }
    }

    // Make sure recipients ids are integers
    $recipients = clean_param_array($recipients, PARAM_INT);

    $participants = array();
    list($select, $from, $where, $sort, $params) = local_mail_getsqlrecipients($message->course()->id, '', $groupid, 0, implode(',', $recipients));
    $rs = $DB->get_recordset_sql("$select $from $where $sort", $params);

    foreach ($rs as $rec) {
        if (!array_key_exists($rec->id, $participants)) {// Avoid duplicated users
            if ($severalseparategroups) {
                $valid = false;
                foreach ($groups[0] as $group) {
                    $valid = $valid || groups_is_member($group, $rec->id);
                }
                if (!$valid) {
                    continue;
                }
            }
            $message->add_recipient($roles[$role], $rec->id);
            $participants[$rec->id] = true;
        }
    }

    $rs->close();
}

function local_mail_getsqlrecipients($courseid, $search, $groupid, $roleid, $recipients = false) {
    global $CFG, $USER, $DB;

    $context = context_course::instance($courseid);

    list($esql, $params) = get_enrolled_sql($context, null, $groupid, true);
    $joins = array("FROM {user} u");
    $wheres = array();

    $mainuserfields = user_picture::fields('u', array('username', 'email', 'city', 'country', 'lang', 'timezone', 'maildisplay'));

    $extrasql = get_extra_user_fields_sql($context, 'u', '', array(
            'id', 'firstname', 'lastname'));
    $select = "SELECT $mainuserfields$extrasql";
    $joins[] = "JOIN ($esql) e ON e.id = u.id";

    // Performance hacks - we preload user contexts together with accounts.
    $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_USER;
    $select .= $ccselect;
    $joins[] = $ccjoin;

    // We want to query both the current context and parent contexts.
    list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');

    if ($roleid) {
        $wheres[] = "u.id IN (SELECT userid FROM {role_assignments} WHERE roleid = :roleid AND contextid $relatedctxsql)";
        $params = array_merge($params, array('roleid' => $roleid), $relatedctxparams);
    }

    $from = implode("\n", $joins);

    if (!empty($search)) {
        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $wheres[] = "(". $DB->sql_like($fullname, ':search1', false, false) .") ";
        $params['search1'] = "%$search%";
    }

    $from = implode("\n", $joins);

    $wheres[] = 'u.id <> :guestid AND u.deleted = 0 AND u.confirmed = 1 AND u.id <> :userid';
    $params['userid'] = $USER->id;
    $params['guestid'] = $CFG->siteguest;

    if ($recipients) {
        $wheres[] = 'u.id IN ('.preg_replace('/^,|,$/', '', $recipients).')';
    }

    $where = "WHERE " . implode(" AND ", $wheres);

    $sort = 'ORDER BY u.lastname ASC, u.firstname ASC';

    return array($select, $from, $where, $sort, $params);
}
