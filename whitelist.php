<?php
define("IN_MYBB", 1);
require_once "global.php";

add_breadcrumb("Whitelist", "whitelist.php");
global $db, $templates, $mybb;
$email = $db->escape_string($mybb->user['email']);

$username = "";
$checkedStay = "";
$checkedGo = "";
$day = date("j", time());
$thisMonth = date("m.Y", time());
$allowedUsers = array();

if ($mybb->settings['whitelist_guest'] == "0" && $mybb->user['uid'] == 0) {
    error_no_permission();
}

//Einstellungen holen
if ($mybb->settings['whitelist_ice'] == "-1") {
    $fidIceDB = "";
} else {
    $fidIceDB = "fid" . intval($mybb->settings['whitelist_ice']) . ",";
    $fidIce = intval($mybb->settings['whitelist_ice']);
}
$fidWhitelist = intval($mybb->settings['whitelist_fid']);
$fidPlayer = intval($mybb->settings['whitelist_player']);
$fidsInplay = $mybb->settings['whitelist_inplay'];
$fidArchive = $mybb->settings['whitelist_archive'];
$dayEcho = intval($mybb->settings['whitelist_echo']);
$month = intval($mybb->settings['whitelist_post']);
$dayBegin = intval($mybb->settings['whitelist_dayBegin']);
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
$uids = getAllUids();

if ($fidPlayer == -1 || $fidArchive == -1 || $fidInplay == -1) {
    die("Fülle bitte zuerst die Einstellungen im AdminCP aus. Es muss mind. die FID vom Spielernamenprofilfeld, die ID der Inplaykategorie und die ID der 
    Archivkategorie angebenen werden.");
}

//User, die sich streichen dürfen
if ($month != -1) {
    $lastIPPosts = $db->query("SELECT uid, dateline
    FROM " . TABLE_PREFIX . "posts p JOIN " . TABLE_PREFIX . "forums f ON f.fid = p.fid
    WHERE find_in_set(f.fid, '". $fidsInplay ."') or find_in_set(". $fidArchive .", parentlist)"); 
    while ($lastIPPost = $db->fetch_array($lastIPPosts)) {
        if (!isOlderThanOneMonth($lastIPPost['dateline']) && !in_array($lastIPPost['uid'], $allowedUsers))
            array_push($allowedUsers, $lastIPPost['uid']);
    }
}

//Post-Request auslesen
$countCharacters = 0;
$allowedUserIDs = array();
$allowedIDsGenerated = false;
while (isset($_POST["uid" . $countCharacters])) {
    	// Beim aller ersten Durchlauf der Schleife werden die berechtigten UserAccounts geladen
	if(!$allowedIDsGenerated)
	{
		$allowedIDsGenerated = true;
        // $allowedCharacters = $db->query("SELECT uid FROM " . TABLE_PREFIX . "users WHERE `email` = '" . $email . "'");
        $allowedCharacters = $db->query("SELECT uid FROM " . TABLE_PREFIX . "users WHERE find_in_set(uid, '". $uids. "')");
		while ($allowedCharacter = $db->fetch_array($allowedCharacters)) {
			$allowedUserIDs[] = (int)$allowedCharacter['uid'];
		}
	}

	// Übermittelte Daten verarbeiten
    if (isset($_POST["status" . $countCharacters])) {
        $status = $db->escape_string($_POST["status" . $countCharacters]);
        $uid = intval($_POST["uid" . $countCharacters]);

        // Berechtigte UserID? Nur dann das DB Feld aktualisieren
        if ( in_array($uid, $allowedUserIDs) ){
	        $db->query("UPDATE " . TABLE_PREFIX . "userfields SET fid" . $fidWhitelist . "  = '$status' WHERE ufid = $uid ");
		}}
    $countCharacters++;
}

$countCharacters = 0;
//Eigene Charaktere
$ownCharacters = $db->query("SELECT username,usergroup,displaygroup,uid, fid" . $fidWhitelist . "
FROM " . TABLE_PREFIX . "users u LEFT JOIN " . TABLE_PREFIX . "userfields uf ON(u.uid=uf.ufid)
WHERE find_in_set(uid, '". $uids. "')
ORDER BY username");
while ($ownCharacter = $db->fetch_array($ownCharacters)) {
    if ($ownCharacter['fid' . $fidWhitelist . ''] == "Bleibt") {
        $checkedStay = "checked";
        $checkedGo = "";
    } elseif ($ownCharacter['fid' . $fidWhitelist . ''] == "Geht" || $ownCharacter['fid' . $fidWhitelist . ''] == NULL) {
        $checkedGo = "checked";
        $checkedStay = "";
    }

    if (($month != -1 && !in_array($ownCharacter['uid'], $allowedUsers)) || $day > ($dayEcho + $dayBegin) || $ownCharacter['usergroup'] == $applicant) {
        $checkedGo .= " disabled";
        $checkedStay .= " disabled";
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
    global $month, $dayBegin;
    $oneMonthAgo = strtotime("-" . $month . " month");
    $firstOneMonthAgo = $dayBegin . '.' . date("m.Y", $oneMonthAgo);
    $UnixFirstOneMonthAgo = strtotime($firstOneMonthAgo);
    if ($dateToTest > $UnixFirstOneMonthAgo) {
        return false;
    } else {
        return true;
    }
}
if ($mybb->settings['whitelist_ice'] == "-1") { // Ice ist nicht aktiviert
    eval("\$page = \"" . $templates->get("whitelist") . "\";");
} else {
    eval("\$page = \"" . $templates->get("whitelistIce") . "\";");
}
output_page($page);

function getAllUids() {
    global $mybb, $db;
    if ($mybb->user['as_uid'] != 0) {
        $mainUid = $mybb->user['as_uid'];
    } else {
        $mainUid = $mybb->user['uid'];
    }

    $returnString = $mainUid;      
    $query = $db->simple_select('users', 'uid', 'as_uid = ' . $mainUid);
    while ($result = $db->fetch_array($query)) {
        $returnString .= ','. $result['uid']; 
    }
    return $returnString;
}