<?php

function task_whitelist($task){
    global $db, $lang, $mybb;
    $fidWhitelist = intval($mybb->settings['whitelist_fid']);

    $updateUsers = array('hasSeenWhitelist' => 0);
    $db->update_query('users', $updateUsers);
    $updateUserfields = array('fid'. $fidWhitelist => 'Geht');
    $db->update_query('userfields', $updateUserfields);

    // Add an entry to the log
    add_task_log($task, 'Die Whitelist wurde erfolgreich zurückgesetzt');
}