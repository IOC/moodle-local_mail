<?php

$handlers = array(
    'course_deleted' => array(
        'handlerfile' => '/local/mail/lib.php',
        'handlerfunction' => 'local_mail_course_deleted',
        'schedule' => 'instant',
    )
);
