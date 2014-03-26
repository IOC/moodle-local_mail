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
 * @copyright  Manuel Cagigas <sedras@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/clilib.php');

global $DB;

// Cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false, 'timelimit' => false),
                                               array('h' => 'help', 't' => 'timelimit'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}


if ($options['help']) {
    $help =
        "Local mail upgrade helper CLI tool.
Will upgrade all remaining mails if no options are specified.

Options:
-t, --timelimit=<n>     Process mails for n number of seconds, then exit. A mail
                        currently in progress will not be interrupted.
-h, --help              Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/mail/cli/cliupgrade.php -t=1000
";

    echo $help;
    die;
}

// Setup the stop time.
if ($options['timelimit']) {
    $stoptime = time() + $options['timelimit'];
} else {
    $stoptime = false;
}

$countrecords = $DB->count_records('local_mail_messages');

$limitfrom = 0;
$limitnum = 1000;

$inserts = 0;
$fs = get_file_storage();
$count = 0;
$starttime = time();

mtrace('Mail updater: processing ...');

while ((!$stoptime || (time() < $stoptime)) && $count < $countrecords) {

    $recordset = $DB->get_recordset('local_mail_messages', array(), '',     '*', $limitfrom, $limitnum);

    $transaction = $DB->start_delegated_transaction();

    foreach ($recordset as $record) {

        if (!$DB->get_records('local_mail_index', array('messageid' => $record->id, 'type' => 'attachment'))) {

            $indexrecord = new stdClass;
            $userid = $DB->get_field('local_mail_message_users', 'userid', array('messageid' => $record->id, 'role' => 'from'));
            $indexrecord->userid = $userid;
            $indexrecord->type = 'attachment';
            $indexrecord->time = $record->time;
            $indexrecord->messageid = $record->id;
            $unread = $DB->get_field('local_mail_message_users', 'unread', array('messageid' => $record->id, 'role' => 'from'));
            $indexrecord->unread = $unread;

            $context = context_course::instance($record->courseid);

            if ($fs->is_area_empty($context->id, 'local_mail', 'message', $record->id, 'filename', false)) {
                $indexrecord->item = 0;
            } else {
                $indexrecord->item = 1;
            }
            $DB->insert_record('local_mail_index', $indexrecord);

            $inserts++;

        }

    }

    $recordset->close();
    $transaction->allow_commit();
    $count += 1000;
    $limitfrom += $limitnum;
}

mtrace('Mail updater: Done. Processed '.$inserts.' mails in '.(time() - $starttime).' seconds');
