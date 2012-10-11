<?php

defined('MOODLE_INTERNAL') || die();

class local_mail_label {

    private $id;
    private $userid;
    private $name;
    private $color;

    function __construct($record) {
        $this->id = (int) $record->id;
        $this->userid = (int) $record->userid;
        $this->name = $record->name;
        $this->color = $record->color;
    }

    static function create($userid, $name, $color='') {
        global $DB;

        assert($userid > 0);
        assert(strlen($name) > 0);
        assert(!$color or in_array($color, self::valid_colors()));

        $record = new stdClass;
        $record->userid = $userid;
        $record->name = $name;
        $record->color = $color;

        $record->id = $DB->insert_record('local_mail_labels', $record);

        return new self($record);
    }

    static function fetch($id) {
        global $DB;

        $record = $DB->get_record('local_mail_labels', array('id' => $id));

        if ($record) {
            return new self($record);
        }
    }

    static function fetch_many($ids) {
        global $DB;

        $records = $DB->get_records_list('local_mail_labels', 'id', $ids);
        return self::many($records);
    }

    static function fetch_user($userid) {
        global $DB;

        $records = $DB->get_records('local_mail_labels', array('userid' => $userid), 'name');
        return self::many($records);
    }

    static function valid_colors() {
        return array('red', 'orange', 'yellow', 'green', 'blue', 'purple', 'black');
    }

    function color() {
        return $this->color;
    }

    function delete() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('local_mail_labels', array('id' => $this->id));
        $DB->delete_records('local_mail_message_labels', array('labelid' => $this->id));

        $conditions = array('userid' => $this->userid, 'type' => 'label', 'item' => $this->id);
        $DB->delete_records('local_mail_index', $conditions);

        $transaction->allow_commit();
    }

    function id() {
        return $this->id;
    }

    function name() {
        return $this->name;
    }

     function save($name, $color) {
        global $DB;

        assert(in_array($color, self::valid_colors()));
        assert(strlen($name) > 0);

        $record = new stdClass;
        $record->id = $this->id;
        $record->name = $this->name = $name;
        $record->color = $this->color = $color;

        $DB->update_record('local_mail_labels', $record);
    }

    function userid() {
        return $this->userid;
    }

    private static function many($records) {
        $labels = array();
        foreach ($records as $record) {
            $labels[(int) $record->id] = new self($record);
        }
        return $labels;
    }
}
