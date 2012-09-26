<?php

require_once($CFG->libdir . '/filelib.php');

function xmldb_local_mail_uninstall() {
    global $DB;

    $fs = get_file_storage();

    $conditions = array('contextlevel' => CONTEXT_COURSE);
    $records = $DB->get_records('context', $conditions, '', 'id');

    foreach ($records as $record) {
        $fs->delete_area_files($record->id, 'local_mail');
    }

    return true;
}
