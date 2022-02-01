<?php

function task_whitelist($task){
    global $db;

    $db->update_query('users', ['hasSeenWhitelist' => 0, 'whitelist' => 0]);

    add_task_log($task, 'Die Whitelist wurde erfolgreich zurückgesetzt');
}