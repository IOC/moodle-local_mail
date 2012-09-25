<?php 

defined('MOODLE_INTERNAL') || die();

require_once('label.class.php');

class local_mail_message {

    private $id;
    private $course;
    private $subject;
    private $content;
    private $format;
    private $draft;
    private $time;
    private $refs = array();
    private $users = array();
    private $role = array();
    private $unread = array();
    private $starred = array();
    private $deleted = array();
    private $labels = array();

    static function count_index($userid, $type, $itemid=0) {
        global $DB;

        assert(in_array($type, array('inbox', 'drafts', 'sent', 'starred', 'course', 'label', 'trash')));

        $conditions = array('userid' => $userid, 'type' => $type, 'item'=> $itemid);
        return $DB->count_records('local_mail_index', $conditions);
    }

    static function count_index_unread($userid, $type, $itemid=0) {
        global $DB;

        assert(in_array($type, array('inbox', 'drafts', 'sent', 'starred', 'course', 'label', 'trash')));

        $conditions = array('userid' => $userid, 'type' => $type, 'item'=> $itemid, 'unread' => true);
        return $DB->count_records('local_mail_index', $conditions);
    }

    static function count_menu($userid) {
        global $DB;

        $result = new stdClass;
        $result->courses = array();
        $result->labels = array();

        $sql = 'SELECT id, type, item, unread, COUNT(*) AS count'
            . ' FROM {local_mail_index}'
            . ' WHERE userid = :userid'
            . ' GROUP BY type, item, unread';
        $records = $DB->get_records_sql($sql, array('userid' => $userid));

        foreach ($records as $record) {
            if ($record->type == 'inbox' and $record->unread) {
                $result->inbox = (int) $record->count;
            } else if ($record->type == 'drafts') {
                $result->drafts += (int) $record->count;
            } else if ($record->type == 'course' and $record->unread) {
                $result->courses[(int) $record->item] = (int) $record->count;
            } else if ($record->type == 'label' and $record->unread) {
                $result->labels[(int) $record->item] = (int) $record->count;
            }
        }

        return $result;
    }

    static function create($userid, $courseid, $time=false) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $course = self::fetch_course($courseid);
        $record = self::create_record($course, '', '', -1, $time);

        $user = self::fetch_user($userid);
        $user_record = self::create_user_record($record->id, 'from', $user);

        $message = new self($record, array(), array($user_record));
        $message->create_index($userid, 'drafts');
        $message->create_index($userid, 'course', $courseid);

        $transaction->allow_commit();

        return $message;
    }

    static function delete_course($courseid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $select = 'messageid IN (SELECT id FROM {local_mail_messages} WHERE courseid = :courseid)';
        $params = array('courseid' => $courseid);
        $DB->delete_records_select('local_mail_index', $select, $params);
        $DB->delete_records_select('local_mail_message_labels', $select, $params);
        $DB->delete_records_select('local_mail_message_users', $select, $params);
        $DB->delete_records_select('local_mail_message_refs', $select, $params);
        $DB->delete_records('local_mail_messages', $params);
        $transaction->allow_commit();
    }

    static function fetch($id) {
        $messages = self::fetch_many(array($id));
        return reset($messages);
    }

    static function fetch_index($userid, $type, $item=0, $limitfrom=0, $limitnum=0) {
        global $DB;

        assert(in_array($type, array('inbox', 'drafts', 'sent', 'starred', 'course', 'label', 'trash')));

        $conditions = array('userid' => $userid, 'type' => $type, 'item'=> $item);
        $records = $DB->get_records('local_mail_index', $conditions, 'time DESC',
                                    'id, messageid', $limitfrom, $limitnum);
        $ids = array_map(function($r) { return $r->messageid; }, $records);

        return self::fetch_many($ids);
    }

    static function fetch_many(array $ids) {
        global $DB;

        $messages = array();

        if (!$ids) {
            return $messages;
        }

        $sql = 'SELECT m.id, m.courseid, m.subject, m.content, m.format,'
            . ' m.draft, m.time, c.shortname, c.fullname'
            . ' FROM {local_mail_messages} m'
            . ' JOIN {course} c ON c.id = m.courseid'
            . ' WHERE m.id  IN (' . implode(',', $ids) . ')';
        $records = $DB->get_records_sql($sql);

        $sql = 'SELECT mr.id AS recordid, mr.messageid, mr.reference'
            . ' FROM {local_mail_message_refs} mr'
            . ' WHERE mr.messageid IN (' . implode(',', $ids) . ')'
            . ' ORDER BY mr.id ASC';
        $ref_records = $DB->get_records_sql($sql);

        $sql = 'SELECT mu.id AS recordid, mu.messageid, mu.userid, mu.role,'
            . ' mu.unread, mu.starred, mu.deleted,'
            . ' u.username, u.firstname, u.lastname, u.email, u.picture, u.imagealt, u.maildisplay'
            . ' FROM {local_mail_message_users} mu'
            . ' JOIN {user} u ON u.id = mu.userid'
            . ' WHERE mu.messageid  IN (' . implode(',', $ids) . ')';
        $user_records = $DB->get_records_sql($sql);

        $sql = 'SELECT ml.id AS recordid, ml.messageid, l.id, l.userid, l.name, l.color'
            . ' FROM {local_mail_message_labels} ml'
            . ' JOIN {local_mail_labels} l ON l.id = ml.labelid'
            . ' WHERE ml.messageid  IN (' . implode(',', $ids) . ')';
        $label_records = $DB->get_records_sql($sql);

        foreach ($ids as $id) {
            if (isset($records[$id])) {
                $messages[$id] = new self($records[$id], $ref_records,
                                          $user_records, $label_records);
            }
        }

        return $messages;
    }

    function add_label(local_mail_label $label) {
        global $DB;

        assert($this->has_user($label->userid()));
        assert(!$this->draft or $this->role[$label->userid()] == 'from');
        assert(!$this->deleted($label->userid()));

        if (!isset($this->labels[$label->id()])) {
            $transaction = $DB->start_delegated_transaction();
            $record = new stdClass;
            $record->messageid = $this->id;
            $record->labelid = $label->id();
            $DB->insert_record('local_mail_message_labels', $record);
            $this->create_index($label->userid(), 'label', $label->id());
            $transaction->allow_commit();
            $this->labels[$label->id()] = $label;
        }
    }

    function add_recipient($role, $userid) {
        global $DB;

        assert($this->draft);
        assert(!$this->has_recipient($userid));
        assert(in_array($role, array('to', 'cc', 'bcc')));

        $user = self::fetch_user($userid);
        $this->users[$userid] = $user;
        $this->role[$userid] = $role;
        $this->unread[$userid] = true;
        $this->starred[$userid] = false;
        $this->deleted[$userid] = false;

        self::create_user_record($this->id, $role, $user);
    }

    function content() {
        return $this->content;
    }

    function course() {
        return $this->course;
    }

    function deleted($userid) {
        assert($this->has_user($userid));
        return $this->deleted[$userid];
    }

    function discard() {
        global $DB;

        assert($this->draft);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_mail_messages', array('id' => $this->id));
        $DB->delete_records('local_mail_message_refs', array('messageid' => $this->id));
        $DB->delete_records('local_mail_message_users', array('messageid' => $this->id));
        $DB->delete_records('local_mail_message_labels', array('messageid' => $this->id));
        $DB->delete_records('local_mail_index', array('messageid' => $this->id));
        $transaction->allow_commit();
    }


    function draft() {
        return $this->draft;
    }

    function editable($userid) {
        return $this->draft and $this->has_user($userid) and $this->role[$userid] == 'from';
    }

    function format() {
        return $this->format;
    }

    function forward($userid, $time=false) {
        global $DB;

        assert(!$this->draft);
        assert($this->has_user($userid));

        $transaction = $DB->start_delegated_transaction();

        $subject = 'FW: ' . $this->subject;
        $record = self::create_record($this->course, $subject, '', -1, $time);
        $user_record = self::create_user_record($record->id, 'from', $this->users[$userid]);

        $references = array_merge(array($this->id), $this->references());
        $ref_records = self::create_ref_records($record->id, $references);

        $message = new self($record, $ref_records, array($user_record));

        $message->create_index($userid, 'drafts');
        $message->create_index($userid, 'course', $this->course->id);
        
        foreach ($this->labels($userid) as $label) {
            $message->add_label($label);
        }
        
        $transaction->allow_commit();

        return $message;
    }

    function has_label(local_mail_label $label) {
        return isset($this->labels[$label->id()]);
    }

    function has_recipient($userid) {
        return $this->has_user($userid) and $this->role[$userid] != 'from';
    }

    function id() {
        return $this->id;
    }

    function labels($userid=false) {
        assert($userid === false or $this->has_user($userid));
        
        if ($userid) {
            return array_filter($this->labels, function($label) use ($userid) {
                return $label->userid() == $userid;
            });
        } else {
            return $this->labels;
        }
    }

    function recipients() {
        $roles = func_get_args();
        return array_filter($this->users, function ($user) use ($roles) {
            $role = $this->role[$user->id];
            return $role != 'from' and (!$roles or in_array($role, $roles));
        });
    }

    function references() {
        return $this->refs;
    }

    function remove_label(local_mail_label $label) {
        global $DB;
        assert($this->has_user($label->userid()));
        assert(!$this->draft or $this->role[$label->userid()] == 'from');
        assert(!$this->deleted($label->userid()));

        if (isset($this->labels[$label->id()])) {
            $transaction = $DB->start_delegated_transaction();
            $conditions = array('messageid' => $this->id, 'labelid' => $label->id());
            $DB->delete_records('local_mail_message_labels', $conditions);
            $this->delete_index($label->userid(), 'label', $label->id());
            $transaction->allow_commit();
            unset($this->labels[$label->id()]);
        }
    }

    function remove_recipient($userid) {
        global $DB;

        assert($this->draft);
        assert($this->has_recipient($userid));

        $DB->delete_records('local_mail_message_users', array(
            'messageid' => $this->id,
            'userid' => $userid,
        ));

        unset($this->users[$userid]);
        unset($this->role[$userid]);
        unset($this->unread[$userid]);
        unset($this->starred[$userid]);
        unset($this->deleted[$userid]);
    }

    function reply($userid, $all=false, $time=false) {
        global $DB;

        assert(!$this->draft and $this->has_recipient($userid));
        assert(!$all or in_array($this->role[$userid], array('to', 'cc')));

        $transaction = $DB->start_delegated_transaction();

        $subject = 'RE: ' . $this->subject;
        $record = self::create_record($this->course, $subject, '', -1, $time);

        $references = array_merge(array($this->id), $this->references());
        $ref_records = self::create_ref_records($record->id, $references);

        $user_records = array(
            self::create_user_record($record->id, 'from', $this->users[$userid]),
            self::create_user_record($record->id, 'to', $this->sender()),
        );

        if ($all) {
            foreach ($this->recipients('to', 'cc') as $user) {
                if ($user->id != $userid) {
                    $user_records[] = self::create_user_record($record->id, 'cc', $user);
                }
            }
        }

        $message = new self($record, $ref_records, $user_records);

        $message->create_index($userid, 'drafts');
        $message->create_index($userid, 'course', $this->course->id);
        
        foreach ($this->labels($userid) as $label) {
            $message->add_label($label);
        }

        $transaction->allow_commit();

        return $message;
    }

    function save($subject, $content, $format, $time=false) {
        global $DB;

        assert($this->draft);

        $record = new stdClass;
        $record->id = $this->id;
        $record->subject = $this->subject = $subject;
        $record->content = $this->content = $content;
        $record->format = $this->format = $format;
        $record->time = $this->time = $time ?: time();

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('local_mail_messages', $record);
        $DB->set_field('local_mail_index', 'time', $this->time, array(
            'messageid' => $this->id,
        )); 
        $transaction->allow_commit();
    }

    function send($time=false) {
        global $DB;

        assert($this->draft and count($this->recipients()) > 0);

        $transaction = $DB->start_delegated_transaction();

        $record = new stdClass;
        $record->id = $this->id;
        $record->draft = $this->draft = false;
        $record->time = $this->time = $time ?: time();
        $DB->update_record('local_mail_messages', $record);

        $DB->set_field('local_mail_index', 'time', $this->time, array(
            'messageid' => $this->id,
        )); 

        $DB->set_field('local_mail_index', 'type', 'sent', array(
            'messageid' => $this->id,
            'userid' => $this->sender()->id,
            'type' => 'drafts',
        ));

        foreach ($this->recipients() as $user) {
            $this->create_index($user->id, 'inbox');
            $this->create_index($user->id, 'course', $this->course->id);
        }

        if ($references = $this->references()) {
            $message = self::fetch($references[0]);
            foreach ($this->recipients() as $users) {
                foreach ($message->labels($user->id) as $label) {
                    $this->add_label($label);
                }
            }
        }

        $transaction->allow_commit();
    }

    function sender() {
        $userid = array_search('from', $this->role);
        return $this->users[$userid];
    }

    function set_deleted($userid, $value) {
        global $DB;

        assert($this->has_user($userid));
        assert(!$this->draft);

        if ($this->deleted[$userid] == (bool) $value) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();

        $conditions = array('messageid' => $this->id, 'userid' => $userid);
        $DB->set_field('local_mail_message_users', 'deleted', (bool) $value, $conditions);

        if ($value) {
            $this->delete_index($userid);
            $this->create_index($userid, 'trash');
        } else {
            $this->delete_index($userid, 'trash');
            if ($this->role[$userid] == 'from') {
                $this->create_index($userid, 'sent');
            } else {
                $this->create_index($userid, 'inbox');
            }
            if ($this->starred($userid)) {
                $this->create_index($userid, 'starred');
            }
            $this->create_index($userid, 'course', $this->course->id);
            foreach ($this->labels($userid) as $label) {
                $this->create_index($userid, 'label', $label->id());
            }
        }

        $transaction->allow_commit();

        $this->deleted[$userid] = (bool) $value;
    }

    function set_starred($userid, $value) {
        global $DB;

        assert($this->has_user($userid));
        assert(!$this->draft or $this->role[$userid] == 'from');
        assert(!$this->deleted($userid));

        if ($this->starred[$userid] == (bool) $value) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();

        $conditions = array('messageid' => $this->id, 'userid' => $userid);
        $DB->set_field('local_mail_message_users', 'starred', (bool) $value, $conditions);

        if ($value) {
            $this->create_index($userid, 'starred');
        } else {
            $this->delete_index($userid, 'starred');
        }

        $transaction->allow_commit();

        $this->starred[$userid] = (bool) $value;
    }

    function set_unread($userid, $value) {
        global $DB;

        assert($this->has_user($userid));
        assert(!$this->draft or $this->role[$userid] == 'from');

        if ($this->unread[$userid] == (bool) $value) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();
        $conditions = array('messageid' => $this->id, 'userid' => $userid);
        $DB->set_field('local_mail_message_users', 'unread', (bool) $value, $conditions);
        $DB->set_field('local_mail_index', 'unread', (bool) $value, $conditions);
        $transaction->allow_commit();

        $this->unread[$userid] = (bool) $value;
    }

    function starred($userid) {
        assert($this->has_user($userid));
        return $this->starred[$userid];
    }

    function subject() {
        return $this->subject;
    }

    function time() {
        return $this->time;
    }

    function unread($userid) {
        assert($this->has_user($userid));
        return $this->unread[$userid];
    }

    function viewable($userid) {
        return $this->has_user($userid) and (!$this->draft or $this->role[$userid] == 'from');
    }

    private function __construct($record, $ref_records, $user_records, $label_records=array()) {
        $this->id = (int) $record->id;
        $this->course = (object) array(
            'id' => $record->courseid,
            'shortname' => $record->shortname,
            'fullname' => $record->fullname,
        );
        $this->subject = $record->subject;
        $this->content = $record->content;
        $this->format = (int) $record->format;
        $this->draft = (bool) $record->draft;
        $this->time = (int) $record->time;

        foreach ($ref_records as $r) {
            if ($r->messageid == $record->id) {
                $this->refs[] = $r->reference;
            }
        }

        foreach ($user_records as $r) {
            if ($r->messageid == $record->id) {
                $this->role[$r->userid] = $r->role;
                $this->unread[$r->userid] = (bool) $r->unread;
                $this->starred[$r->userid] = (bool) $r->starred;
                $this->deleted[$r->userid] = (bool) $r->deleted;
                $this->users[$r->userid] = (object) array(
                    'id' => $r->userid,
                    'username' => $r->username,
                    'firstname' => $r->firstname,
                    'lastname' => $r->lastname,
                    'email' => $r->email,
                    'picture' => $r->picture,
                    'imagealt' => $r->imagealt,
                    'maildisplay' => $r->maildisplay,
                );
            }
        }

        foreach ($label_records as $r) {
            if ($r->messageid == $record->id) {
                $this->labels[$r->id] = new local_mail_label($r);
            }
        }
    }

    private static function create_record($course, $subject, $content, $format, $time) {
        global $DB;

        $record = new stdClass;
        $record->courseid = $course->id;
        $record->subject = $subject;
        $record->content = $content;
        $record->format = $format;
        $record->draft = true;
        $record->time = $time ?: time();

        $record->id = $DB->insert_record('local_mail_messages', $record);

        $record->shortname = $course->shortname;
        $record->fullname = $course->fullname;

        return $record;
    }

    private static function create_ref_records($messageid, array $references) {
        global $DB;

        $records = array();

        foreach ($references as $reference) {
            $record = new stdClass;
            $record->messageid = $messageid;
            $record->reference = $reference;
            $DB->insert_record('local_mail_message_refs', $record);
            $records[] = $record;
        }

        return $records;
    }

    private static function create_user_record($messageid, $role, $user) {
        global $DB;

        $record = new stdClass;
        $record->messageid = $messageid;
        $record->userid = $user->id;
        $record->role = $role;
        $record->unread = ($role != 'from');
        $record->starred = 0;
        $record->deleted = 0;

        $DB->insert_record('local_mail_message_users', $record);

        $record->username = $user->username;
        $record->firstname = $user->firstname;
        $record->lastname = $user->lastname;
        $record->email = $user->email;
        $record->picture = $user->picture;
        $record->imagealt = $user->imagealt;
        $record->maildisplay = $user->maildisplay;

        return $record;
    }

    private static function fetch_course($courseid) {
        global $DB;
        $conditions = array('id' => $courseid);
        $fields = 'id, shortname, fullname';
        return $DB->get_record('course', $conditions, $fields, MUST_EXIST);
    }

    private static function fetch_user($userid) {
        global $DB;
        $conditions = array('id' => $userid);
        $fields = 'id, username, firstname, lastname, email, picture, imagealt, maildisplay';
        return $DB->get_record('user', $conditions, $fields, MUST_EXIST);
    }

    private function create_index($userid, $type, $itemid=0) {
        global $DB;

        $record = new stdClass;
        $record->userid = $userid;
        $record->type = $type;
        $record->item = $itemid;
        $record->time = $this->time;
        $record->messageid = $this->id;
        $record->unread = $this->unread[$userid];

        $DB->insert_record('local_mail_index', $record);
    }

    private function delete_index($userid, $type=false, $itemid=0) {
        global $DB;

        $conditions = array();
        $conditions['messageid'] = $this->id;
        $conditions['userid'] = $userid;
        if ($type) {
            $conditions['type'] = $type;
            $conditions['item'] = $itemid;
            $conditions['time'] = $this->time;
        }
        $DB->delete_records('local_mail_index', $conditions);
    }

    private function has_user($userid) {
        return isset($this->users[$userid]);
    }
}
