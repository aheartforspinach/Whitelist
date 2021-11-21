<?php
define('IN_MYBB', 1);
require_once 'global.php';
require_once 'inc/datahandlers/whitelist.php';

// user is visiting the site and plugin isn't installed
if (!$db->field_exists('whitelist', 'users')) {
    redirect('index.php');
}

add_breadcrumb('Whitelist', 'whitelist.php');
global $db, $templates, $mybb, $lang;
$lang->load('whitelist');
$whitelistHandler = new whitelistHandler();

$thisMonth = date('m.Y', time());

// return if the user is guest and they can't see the whitelist
if (intval($mybb->settings['whitelist_guest']) === 0 && $mybb->user['uid'] === 0) {
    error_no_permission();
}

// settings
$showOtherUsers = intval($mybb->settings['whitelist_showUser']) === 1 ? false : true;

// return if settings aren't filled correctly
if (intval($mybb->settings['whitelist_player']) < 0) {
    error($lang->whitelist_error_message);
}

// set all characters on stay
if ($mybb->get_input('setAllCharactersOnStay') == 1) {
    $whitelistHandler->setCharactersOnStay();
}

// change status of character
$characters = $whitelistHandler->getCharacters();
foreach ($characters as $uid => $character) {
    if ($mybb->user['uid'] == 0) continue;
    if (!isset($_POST['uid'. $uid])) continue;

    $status = $mybb->get_input('status'. $uid, Mybb::INPUT_INT);
    // check if the user is allowed to change
    if (!in_array($mybb->user['uid'], array_keys($characters))) continue;
    $db->update_query('users', array('whitelist' => $status), 'uid = '. $uid);

    // also change in characters array
    $characters[$uid]['stayOrGo'] = $status;
}

// generate form for all own characters
foreach ($characters as $uid => $character) {
    $checkedGo = $character['stayOrGo'] === 0 ? 'checked' : '';
    $checkedStay = $character['stayOrGo'] === 1 ? 'checked' : '';

    if (!$whitelistHandler->canStatusOfCharacterCanBeChange($uid, $character['usergroup'])) 
    {
        $checkedGo .= ' disabled';
        $checkedStay .= ' disabled';
    }

    $user = get_user($uid);
    $userlink = build_profile_link(format_name($character['username'], $user['usergroup'], $user['displaygroup']), $uid);
    eval("\$charactersForm .= \"" . $templates->get("whitelist_characters") . "\";");
}

// show whitelist
$users = $whitelistHandler->getAllUsers();
$stay = $go = $away = $ice = '';
foreach ($users as $uid => $user) {
    // continue if it isn't own character and users shouldn't see other users
    if (!$showOtherUsers && !in_array($uid, $characters)) {
        continue;
    }

    $username = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $uid);
    if ($user['stayOrGo'] == 1) {
        eval("\$stay .= \"" . $templates->get("whitelist_user") . "\";");
    } elseif ($user['ice'] == 'Ja') {
        eval("\$onIce .= \"" . $templates->get("whitelist_user") . "\";");
    } elseif ($user['away'] == 1) {
        if ($user['as_uid'] == 0) {
            $username = build_profile_link($user['playerName'], $user['uid']);
            eval("\$away .= \"" . $templates->get("whitelist_user") . "\";");
        }
    } elseif ($user['stayOrGo'] == 0) {
        eval("\$go .= \"" . $templates->get("whitelist_user") . "\";");
    }
}

if ($mybb->user['uid'] != 0) {
    eval("\$form .= \"" . $templates->get("whitelist_form") . "\";");
}

$iceTh = $iceTd = '';
if (intval($mybb->settings['whitelist_ice']) !== -1) {
    eval("\$iceTh = \"" . $templates->get("whitelist_ice_th") . "\";"); 
    eval("\$iceTd = \"" . $templates->get("whitelist_ice_td") . "\";");
} 

eval("\$page = \"" . $templates->get("whitelist") . "\";");
output_page($page);