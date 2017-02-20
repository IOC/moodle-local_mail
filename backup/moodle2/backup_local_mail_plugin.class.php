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

defined('MOODLE_INTERNAL') || die();

class backup_local_mail_plugin extends backup_local_plugin {

    protected function define_course_plugin_structure() {
        if (!get_config('local_mail', 'enablebackup')) {
            return;
        }

        if (!$this->get_setting_value('users') or $this->get_setting_value('anonymize')) {
            return;
        }

        $plugin = $this->get_plugin_element(null);

        // Elements.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $messages = new backup_nested_element('messages');
        $elements = array('courseid', 'subject', 'content', 'format', 'attachments', 'draft', 'time');
        $message = new backup_nested_element('message', array('id'), $elements);
        $refs = new backup_nested_element('refs');
        $ref = new backup_nested_element('ref', array('id'), array('reference'));
        $users = new backup_nested_element('users');
        $user = new backup_nested_element('user', array('id'), array('userid', 'role', 'unread', 'starred', 'deleted'));
        $labels = new backup_nested_element('labels');
        $label = new backup_nested_element('label', array('id'), array('userid', 'name', 'color'));

        // Tree.
        $plugin->add_child($pluginwrapper);
        $pluginwrapper->add_child($messages);
        $messages->add_child($message);
        $message->add_child($refs);
        $refs->add_child($ref);
        $message->add_child($users);
        $users->add_child($user);
        $message->add_child($labels);
        $labels->add_child($label);

        // Sources.
        $message->set_source_table('local_mail_messages', array('courseid' => backup::VAR_COURSEID), 'id');
        $ref->set_source_table('local_mail_message_refs', array('messageid' => '../../id'));
        $user->set_source_table('local_mail_message_users', array('messageid' => '../../id'));
        $sql = 'SELECT ml.id, l.userid, l.name, l.color
                FROM {local_mail_message_labels} ml
                JOIN {local_mail_labels} l ON l.id = ml.labelid
                WHERE ml.messageid = ?';
        $label->set_source_sql($sql, array('messageid' => '../../id'));

        // ID annotations.
        $user->annotate_ids('user', 'userid');
        $label->annotate_ids('user', 'userid');

        // File annotations.
        $message->annotate_files('local_mail', 'message', 'id');

        return $plugin;
    }
}
