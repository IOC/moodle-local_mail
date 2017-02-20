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
 * @author     Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_mail_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2014030600) {

        // Define index type_messageid_item (not unique) to be added to local_mail_index.
        $table = new xmldb_table('local_mail_index');
        $index = new xmldb_index('type_messageid_item', XMLDB_INDEX_NOTUNIQUE, array('type', 'messageid', 'item'));

        // Conditionally launch add index type_messageid_item.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Mail savepoint reached.
        upgrade_plugin_savepoint(true, 2014030600, 'local', 'mail');
    }

    if ($oldversion < 2015121400) {

        // Clean obsolete local_mail_fullmessage preference.
        $DB->execute('DELETE FROM {user_preferences} WHERE name="local_mail_fullmessage"');

        upgrade_plugin_savepoint(true, 2015121400, 'local', 'mail');
    }

    if ($oldversion < 2016070100) {

        // Define field attachments to be added to local_mail_messages.
        $table = new xmldb_table('local_mail_messages');
        $field = new xmldb_field('attachments', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'format');

        // Conditionally launch add field attachments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mail savepoint reached.
        upgrade_plugin_savepoint(true, 2016070100, 'local', 'mail');
    }

    if ($oldversion < 2016070101) {

        // Update field attachments.
        $sql = 'SELECT f.itemid, COUNT(*) as numfiles
                FROM {files} f
                WHERE f.component = :component
                AND f.filearea = :filearea
                AND f.filename <> :filename
                GROUP BY f.itemid';
        $params = array(
            'component' => 'local_mail',
            'filearea' => 'message',
            'filename' => '.',
        );
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            $DB->set_field('local_mail_messages', 'attachments', $record->numfiles, array('id' => $record->itemid));
        }
        $rs->close();

        // Mail savepoint reached.
        upgrade_plugin_savepoint(true, 2016070101, 'local', 'mail');
    }

    if ($oldversion < 2016070102) {

        // Delete attachment records from local_mail_index, 1000 at a time.
        while (true) {
            $records = $DB->get_records('local_mail_index', array('type' => 'attachment'), '', 'id', 0, 1000);
            if (!$records) {
                break;
            }
            list($sqlid, $params) = $DB->get_in_or_equal(array_keys($records));
            $DB->delete_records_select('local_mail_index', "id $sqlid", $params);
        }

        // Mail savepoint reached.
        upgrade_plugin_savepoint(true, 2016070102, 'local', 'mail');
    }

    if ($oldversion < 2016070103) {

        // Clean obsolete settings.
        unset_config('cronenabled', 'local_mail');
        unset_config('cronstart', 'local_mail');
        unset_config('cronstop', 'local_mail');
        unset_config('cronduration', 'local_mail');

        upgrade_plugin_savepoint(true, 2016070103, 'local', 'mail');
    }

    if ($oldversion < 2016070104) {

        // Define index type_messageid_item (not unique) to be dropped form local_mail_index.
        $table = new xmldb_table('local_mail_index');
        $index = new xmldb_index('type_messageid_item', XMLDB_INDEX_NOTUNIQUE, array('type', 'messageid', 'item'));

        // Conditionally launch drop index type_messageid_item.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Mail savepoint reached.
        upgrade_plugin_savepoint(true, 2016070104, 'local', 'mail');
    }

    return true;
}
