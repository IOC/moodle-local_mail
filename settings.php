<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    require_once($CFG->dirroot . '/local/mail/locallib.php');
    $settings = new admin_settingpage('local_mail', get_string('pluginname', 'local_mail'));

    $strmaxfiles = get_string('maxattachments', 'local_mail');
    $strmaxbytes = get_string('maxattachmentsize', 'local_mail');

    $settings->add(new admin_setting_configtext('local_mail/maxfiles', $strmaxfiles, '',
                                                LOCAL_MAIL_MAXFILES, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $sizes = get_max_upload_sizes($CFG->maxbytes);
        $settings->add(new admin_setting_configselect('local_mail/maxbytes', $strmaxbytes, '',
                                                      LOCAL_MAIL_MAXBYTES, $sizes));
    }

    $ADMIN->add('localplugins', $settings);
}
