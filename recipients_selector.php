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

require_once("{$CFG->dirroot}/user/selector/lib.php");
require_once("{$CFG->dirroot}/local/mail/lib.php");

class mail_recipients_selector extends groups_user_selector_base {

    public function find_users($search) {
        global $DB, $USER;

        $mailmaxusers = (isset($CFG->maxusersperpage) ? $CFG->maxusersperpage : $this->maxusersperpage);

        $context = context_course::instance($this->courseid);

        $mailsamerole = has_capability('local/mail:mailsamerole', $context);
        $userroleids = local_mail_get_user_roleids($USER->id, $context);

        list($wherecondition, $params) = $this->search_sql($search, 'u');
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($context, '', $this->groupid, true);
        list($parentsql, $parentparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true), SQL_PARAMS_NAMED);

        $params = array_merge($params, $enrolledparams, $parentparams);
        $params['courseid'] = $this->courseid;

        if (!$mailsamerole) {
            list($relctxsql, $reldctxparams) = $DB->get_in_or_equal($context->get_parent_context_ids(true),
                                                                    SQL_PARAMS_NAMED, 'relctx');
            list($samerolesql, $sameroleparams) = $DB->get_in_or_equal($userroleids, SQL_PARAMS_NAMED,
                                                                       'samerole' , false);
            $wherecondition .= " AND u.id IN (SELECT userid FROM {role_assignments}" .
                               " WHERE roleid $samerolesql AND contextid $relctxsql)";
            $params = array_merge($params, $sameroleparams, $reldctxparams);
        }

        $fields = 'SELECT r.id AS roleid, ' .
            'r.shortname AS roleshortname, ' .
            'r.name AS rolename, ' .
            'u.id AS userid, ' .
            $this->required_fields_sql('u');

        $countfields = 'SELECT COUNT(1)';

        $sql = ' FROM {user} u JOIN (' . $enrolledsql . ') e ON e.id = u.id' .
            ' LEFT JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid ' .
            $parentsql . ')' .
            ' LEFT JOIN {role} r ON r.id = ra.roleid' .
            ' WHERE ' . $wherecondition;

        $order = ' ORDER BY r.sortorder, u.lastname ASC, u.firstname ASC';

        if (!$this->is_validating()) {
            $count = $DB->count_records_sql($countfields . $sql, $params);
            if ($count > $mailmaxusers) {
                return $this->too_many_results($search, $count);
            }
        }

        $rs = $DB->get_recordset_sql($fields . $sql . $order, $params);
        $roles = groups_calculate_role_people($rs, $context);

        return $this->convert_array_format($roles, $search);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/mail/recipients_selector.php';
        return $options;
    }
}
