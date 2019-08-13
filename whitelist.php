<?php
define("IN_MYBB", 1);
require_once "global.php";

add_breadcrumb("Whitelist", "whitelist.php");
global $db, $templates, $mybb;
$email = $mybb->user['email'];

$username = "";
$checkedStay = "";
$checkedGo = "";
$day = date("j", time());
$thisMonth = date("m.Y", time());
$allowedUsers = array();

//Einstellungen holen
if ($mybb->settings['whitelist_ice'] == "-1") {
    $fidIceDB = "";
} else {
    $fidIceDB = "fid" . intval($mybb->settings['whitelist_ice']) . ",";
    $fidIce = intval($mybb->settings['whitelist_ice']);
}
$fidWhitelist = intval($mybb->settings['whitelist_fid']);
$fidPlayer = intval($mybb->settings['whitelist_player']);
$fidInplay = intval($mybb->settings['whitelist_inplay']);
$fidArchive = intval($mybb->settings['whitelist_archive']);
$dayEcho = intval($mybb->settings['whitelist_echo']);
$month = intval($mybb->settings['whitelist_post']);
if ($mybb->settings['whitelist_applicant'] == "1") {
    $applicant = -1;
} else {
    $applicant = 2;
}
if ($mybb->settings['whitelist_showUser'] == "1") {
    $showOtherUsers = false;
} else {
    $showOtherUsers = true;
}

$invisibleAccounts = "";
$accounts = explode(", ", $db->escape_string($mybb->settings['whitelist_teamaccs']));
foreach ($accounts as $account) {
    if ($account != -1) {
        $invisibleAccounts .= "XOR uid = " . $account . " ";
    }
}

if ($fidPlayer == -1 || $fidArchive == -1 || $fidInplay == -1) {
    die("Fülle bitte zuerst die Einstellungen im AdminCP aus. Es muss mind. die FID vom Spielernamenprofilfeld, die ID der Inplaykategorie und die ID der 
    Archivkategorie angebenen werden.");
}

//User, die sich streichen dürfen
if ($month != -1) {
    $lastIPPosts = $db->query("SELECT uid, dateline, name
    FROM " . TABLE_PREFIX . "posts p JOIN " . TABLE_PREFIX . "forums f ON f.fid = p.fid
    WHERE f.parentlist LIKE '" . $fidInplay . ",%' OR f.parentlist LIKE '%" . $fidArchive . "%'"); //" . $fidArchive . "
    while ($lastIPPost = $db->fetch_array($lastIPPosts)) {
        if (!isOlderThanOneMonth($lastIPPost['dateline']))
            array_push($allowedUsers, $lastIPPost['uid']);
    }
}


//Post-Request auslesen
$countCharacters = 0;
while (isset($_POST["uid" . $countCharacters])) {
    if (isset($_POST["status" . $countCharacters])) {
        $status = $_POST["status" . $countCharacters];
        $uid = $_POST["uid" . $countCharacters];
        $db->query("UPDATE " . TABLE_PREFIX . "userfields SET fid" . $fidWhitelist . "  = '$status' WHERE ufid = $uid ");
    }
    $countCharacters++;
}

$countCharacters = 0;
//Eigene Charaktere
$ownCharacters = $db->query("SELECT username,usergroup,displaygroup,uid, fid" . $fidWhitelist . "
FROM " . TABLE_PREFIX . "users u LEFT JOIN " . TABLE_PREFIX . "userfields uf ON(u.uid=uf.ufid)
WHERE email = '" . $email . "' 
ORDER BY username");
while ($ownCharacter = $db->fetch_array($ownCharacters)) {
    if ($ownCharacter['fid' . $fidWhitelist . ''] == "Bleibt") {
        $checkedStay = "checked";
        $checkedGo = "";
    } elseif ($ownCharacter['fid' . $fidWhitelist . ''] == "Geht" || $ownCharacter['fid' . $fidWhitelist . ''] == NULL) {
        $checkedGo = "checked";
        $checkedStay = "";
    }
    if ($month != -1 || $ownCharacter['usergroup'] == $applicant) {
        if (!in_array($ownCharacter['uid'], $allowedUsers) || $day > $dayEcho || $ownCharacter['usergroup'] == $applicant) {
            $checkedGo .= " disabled";
            $checkedStay .= " disabled";
        }
    }

    $userlink = build_profile_link(format_name($ownCharacter['username'], $ownCharacter['usergroup'], $ownCharacter['displaygroup']), $ownCharacter['uid']);
    $userUid = $ownCharacter['uid'];
    eval("\$username .= \"" . $templates->get("whitelist") . "\";");
    eval("\$form .= \"" . $templates->get("whitelistCharacters") . "\";");
    $countCharacters++;
}

// Charaktere, die bleiben/gehen/abwesend sind
$users = $db->query("SELECT username,usergroup,displaygroup,uid, " . $fidIceDB . " fid" . $fidWhitelist . ",fid" . $fidPlayer . ", away,as_uid, email
FROM " . TABLE_PREFIX . "users u LEFT JOIN " . TABLE_PREFIX . "userfields uf ON(u.uid=uf.ufid)
WHERE NOT usergroup = " . $applicant . " " . $invisibleAccounts . "
ORDER BY username");
$stay = "";
$go = "";
$away = "";
$onIce = "";
while ($user = $db->fetch_array($users)) {
    if (!$showOtherUsers && $user['email'] == $email || $showOtherUsers) {
        $username = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
        if ($user['fid' . $fidIce . ''] == "Ja") {
            eval("\$onIce .= \"" . $templates->get("whitelistUser") . "\";");
        } elseif ($user['away'] == 1) {
            if ($user['as_uid'] == 0) {
                $username = build_profile_link($user['fid' . $fidPlayer . ''], $user['uid']);
                eval("\$away .= \"" . $templates->get("whitelistUser") . "\";");
            }
        } elseif ($user['fid' . $fidWhitelist . ''] == "Bleibt") {
            eval("\$stay .= \"" . $templates->get("whitelistUser") . "\";");
        } elseif ($user['fid' . $fidWhitelist . ''] == "Geht" || $user['fid' . $fidWhitelist . ''] == NULL) {
            eval("\$go .= \"" . $templates->get("whitelistUser") . "\";");
        }
    }
}

function isOlderThanOneMonth($dateToTest)
{
    global $month;
    $oneMonthAgo = strtotime("-" . $month . " month");
    $firstOneMonthAgo = "01." . date("m.Y", $oneMonthAgo);

    $UnixFirstOneMonthAgo = strtotime($firstOneMonthAgo);
    if ($dateToTest > $UnixFirstOneMonthAgo) {
        return false;
    } else {
        return true;
    }
}
if ($mybb->settings['whitelist_ice'] == "-1"){// Ice ist nicht aktiviert
    eval("\$page = \"" . $templates->get("whitelist") . "\";");
}else{
    eval("\$page = \"" . $templates->get("whitelistIce") . "\";");
}
output_page($page);
