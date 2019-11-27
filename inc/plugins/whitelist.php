<?php
// automatische Whitelist by aheartforspinach

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function whitelist_info(){
	return array(
		"name"			=> "Whitelist",
		"description"	=> "erstellt automatisch am ersten jeden Monats eine Whitelist",
		"author"		=> "aheartforspinach",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=176",
		"version"		=> "1.0",
		"compatibility" => "18*"
	);
}

function whitelist_install(){
    global $db, $cache, $mybb;

    $db->write_query("ALTER TABLE ".TABLE_PREFIX."users ADD hasSeenWhitelist INT(1) NOT NULL DEFAULT '0';");

    $disporder = 0;
    $highestDisporder = $db->query("SELECT disporder
    FROM ".TABLE_PREFIX."profilefields");

    while ($highetsDisporder=$db->fetch_array($highestDisporder)){
        if($disporder < $highetsDisporder['disporder']){
            $disporder = $highetsDisporder['disporder'];
        }
    }

    //neue Profilfelder
    $newPfWhitelist = array(
        'name' => 'Whitelist',
        'description' => 'Man kann zwischen bleiben und gehen wählen',
        'type' => 'radio
Bleibt
Geht',
        'disporder' => $disporder+1,
        'viewableby' => '-1',
        'editableby' => '-1',
        'maxlength' => 0,
        'viewableby' => '3,4',
        'editableby' => '3,4',
    );

    $newFidNr = $db->insert_query("profilefields", $newPfWhitelist);
    $db->write_query("ALTER TABLE ".TABLE_PREFIX."userfields ADD fid" . $newFidNr . " TEXT DEFAULT NULL;");

    //Einstellungen 
	$setting_group = array(
      'name' => 'whitelist',
      'title' => 'Whitelist',
      'description' => 'Einstellungen für das Whitelist-Plugin',
      'isdefault' => 0
    );
    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'whitelist_guest' => array(
            'title' => 'Sichtbarkeit',
            'description' => 'Sollen Gäste die Whitelist sehen können?',
            'optionscode' => 'yesno',
            'value' => 1, // Default
            'disporder' => 1
        ),
        'whitelist_applicant' => array(
            'title' => 'Auflistung Bewerber',
            'description' => 'Sollen Bewerber sich auch zurückmelden können?',
            'optionscode' => 'yesno',
            'value' => 0, // Default
            'disporder' => 2
        ),
        'whitelist_showUser' => array(
            'title' => 'User verstecken',
            'description' => 'Sollen User nur ihre eigenen Charaktere auf der BL sehen? Falls nein, sehen User alle Charaktere und nicht nur ihre',
            'optionscode' => 'yesno',
            'value' => 0, // Default
            'disporder' => 3
        ),
        'whitelist_teamaccs' => array(
            'title' => 'Teamaccount',
            'description' => 'Gib hier mit Komma getrennt die UIDs von den Accounts an, die NICHT gelistet werden sollen. Falls alle gelistet werden sollen, gib -1 ein',
            'optionscode' => 'text',
            'value' => '998, 999', // Default
            'disporder' => 4
        ),
        'whitelist_fid' => array(
            'title' => 'Whitelist Profilfeld',
            'description' => 'Das Profilfeld für Whitelist würde automatisch angelegt. Falls die Zahl falsch sein sollte, kannst du sie hier ändern.',
            'optionscode' => 'text',
            'value' => $newFidNr, // Default
            'disporder' => 5
        ),
        'whitelist_ice' => array(
            'title' => 'Auf Eis Profilfeld',
            'description' => 'Gib hier die ID von deinem Profilfeld ein, ob der Charakter auf Eis ist. -1 bedeutet, dass du dieses Profilfeld nicht nutzt',
            'optionscode' => 'text',
            'value' => '-1', // Default
            'disporder' => 6
        ),
        'whitelist_player' => array(
            'title' => 'Spieler Profilfeld',
            'description' => 'Gib hier die ID von deinem Profilfeld ein, wo man den Spielernamen einträgt',
            'optionscode' => 'text',
            'value' => '-1', // Default
            'disporder' => 7
        ),
        'whitelist_inplay' => array(
            'title' => 'Inplaykategorie',
            'description' => 'Gib hier die ID von der Inplaykategorie ein.',
            'optionscode' => 'text',
            'value' => '-1', // Default
            'disporder' => 8
        ),
        'whitelist_archive' => array(
            'title' => 'Archivkategorie',
            'description' => 'Gib hier die ID von der Archivkategorie ein.',
            'optionscode' => 'text',
            'value' => '-1', // Default
            'disporder' => 9
        ),
        'whitelist_echo' => array(
            'title' => 'Rückmeldezeitraum',
            'description' => 'Bis zu welchen Tag darf man sich zurückmelden? (Hinweis: bis zu diesem Tag wird auch der Hinweis auf dem Index angezeigt)',
            'optionscode' => 'text',
            'value' => '7', // Default
            'disporder' => 10
        ),
        'whitelist_dayBegin' => array(
            'title' => 'Veröffentlichungsdatum',
            'description' => 'An welchem Tag soll die Whitelist veröffentlicht werden?',
            'optionscode' => 'text',
            'value' => '1', // Default
            'disporder' => 11
        ),
        'whitelist_post' => array(
            'title' => 'Mindestpostzahl',
            'description' => 'Falls in den letzten x Monaten ein Post erfolgt haben muss, trage hier z.B. eine 2 ein, wenn man in den letzten zwei Monaten mind. einen Post geschrieben haben musst. -1 falls so etwas nicht gewünscht ist',
            'optionscode' => 'text',
            'value' => '1', // Default
            'disporder' => 12
        ),
    );

    foreach($setting_array as $name => $setting){
        $setting['name'] = $name;
        $setting['gid'] = $gid;
  
        $db->insert_query('settings', $setting);
    }

    //Template whitelist bauen
    $insert_array = array(
        'title'		=> 'whitelist',
        'template'	=> $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">

        <head>
            <title>{$mybb->settings[\'bbname\']} - Whitelist</title>
            {$headerinclude}
        </head>
        
        <body>
            {$header}
            <div class="panel" id="panel">
                <div id="panel">$menu</div>
                <h1>Whitelist vom 01.{$thisMonth}</h1>
        
                <blockquote>Hier kann ein Text stehen zu euren speziellen Whitelist Regeln oder eine Anleitung</blockquote>
        
        
                <div style="width:95%; margin:auto;">
                    <h3>Eigene Charaktere</h3>
                    <form action="whitelist.php" method="post">
                        {$form}
                        <br>
                        <div style="text-align:center;">
                            <input type="submit" value="Bestätigen" class="buttonWhitelist button">
                        </div>
                    </form>
                </div>
        
        
                <br>
                <table style="width:95%; margin:auto;">
                    <tr>
                        <td width="25%" class="thead">
                            Bleibt
                        </td>
                        <td width="25%" class="thead">
                            Geht
                        </td>
						<td width="25%" class="thead">
                            Abwesend
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">{$stay}</td>
                        <td valign="top">{$go}</td>
						<td valign="top">{$away}</td>
                    </tr>
                </table>
        
            </div>
            {$footer}
        </body>
        
        </html>
        <style>
        .charakter{
            width:25%;
            float:left;
            margin-top: 20px;
        }
        
        .buttonWhitelist{
            margin: auto;
            margin-left: 350px;
            margin-right: 350px;    
            margin-top: 20px;    
        }
        </style>'),
        'sid'		=> '-1',
        'version'	=> '',
        'dateline'	=> TIME_NOW
      );
      $db->insert_query("templates", $insert_array);

    //Template whitelistIce bauen
    $insert_array = array(
        'title'		=> 'whitelistIce',
        'template'	=> $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">

        <head>
            <title>{$mybb->settings[\'bbname\']} - Whitelist</title>
            {$headerinclude}
        </head>
        
        <body>
            {$header}
            <div class="panel" id="panel">
                <div id="panel">$menu</div>
                <h1>Whitelist vom 01.{$thisMonth}</h1>
        
                <blockquote>Hier kann ein Text stehen zu euren speziellen Whitelist Regeln oder eine Anleitung</blockquote>
        
        
                <div style="width:95%; margin:auto;">
                    <h3>Eigene Charaktere</h3>
                    <form action="whitelist.php" method="post">
                        {$form}
                        <br>
                        <div style="text-align:center;">
                            <input type="submit" value="Bestätigen" class="buttonWhitelist button">
                        </div>
                    </form>
                </div>
        
        
                <br>
                <table style="width:95%; margin:auto;">
                    <tr>
                        <td width="25%" class="thead">
                            Bleibt
                        </td>
                        <td width="25%" class="thead">
                            Geht
                        </td>
						<td width="25%" class="thead">
                            Abwesend
                        </td>
                        <td width="25%" class="thead">
                            Auf Eis
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">{$stay}</td>
                        <td valign="top">{$go}</td>
						<td valign="top">{$away}</td>
                        <td valign="top">{$onIce}</td>
                    </tr>
                </table>
        
            </div>
            {$footer}
        </body>
        
        </html>
        <style>
        .charakter{
            width:25%;
            float:left;
            margin-top: 20px;
        }
        
        .buttonWhitelist{
            margin: auto;
            margin-left: 350px;
            margin-right: 350px;    
            margin-top: 20px;    
        }
        </style>'),
        'sid'		=> '-1',
        'version'	=> '',
        'dateline'	=> TIME_NOW
      );
      $db->insert_query("templates", $insert_array);

      //Template whitelistCharacters bauen
      $insert_array = array(
        'title'		=> 'whitelistCharacters',
        'template'	=> $db->escape_string('<div class="charakter">{$userlink}<br>
        <input type="hidden" name="uid{$countCharacters}" value="{$userUid}">
          <input type="radio" name="status{$countCharacters}" value="Bleibt" {$checkedStay}> Bleiben<br>
          <input type="radio" name="status{$countCharacters}" value="Geht" {$checkedGo}> Gehen<br>
        </div>'),
        'sid'		=> '-1',
        'version'	=> '',
        'dateline'	=> TIME_NOW
      );
      $db->insert_query("templates", $insert_array);

      //Template whitelistUser bauen
      $insert_array = array(
        'title'		=> 'whitelistUser',
        'template'	=> $db->escape_string('$username</br>'),
        'sid'		=> '-1',
        'version'	=> '',
        'dateline'	=> TIME_NOW
      );
      $db->insert_query("templates", $insert_array);

      //Template whitelistHeader bauen
      $insert_array = array(
        'title'		=> 'whitelistHeader',
        'template'	=> $db->escape_string('<div class="pm_alert">Die aktuelle <a href="/whitelist.php">Whitelist</a> ist draußen. {$noEcho} <a href="/whitelist.php?seen=1" title="Nicht mehr anzeigen"><span style="font-size: 14px;margin-top: -2px;float:right;">✕</span></a></div>'),
        'sid'		=> '-1',
        'version'	=> '',
        'dateline'	=> TIME_NOW
      );
      $db->insert_query("templates", $insert_array);

    rebuild_settings(); 
}

function whitelist_is_installed(){
  global $db;
	if($db->field_exists('hasSeenWhitelist', 'users')) {
			return true;
	}
	return false;
}

function whitelist_uninstall(){
    global $db;
$db->delete_query('settings', "name IN('whitelist_guest','whitelist_applicant', 'whitelist_showUser', 'whitelist_teamaccs','whitelist_post', 'whitelist_fid', 'whitelist_ice', 'whitelist_player', 'whitelist_inplay', 'whitelist_archive', 'whitelist_echo', 'whitelist_dayBegin')");
    $db->delete_query('settinggroups', "name = 'whitelist'");
    $db->delete_query("templates", "title IN('whitelist', 'whitelistIce', 'whitelistUser', 'whitelistCharacters', 'whitelistHeader')");
    $db->query("ALTER TABLE ".TABLE_PREFIX."users DROP hasSeenWhitelist");
    $fids = $db->query("SELECT fid
    FROM ".TABLE_PREFIX."profilefields 
    WHERE name = 'Whitelist'");
    $whitelistFid = "";
    while($fid=$db->fetch_array($fids)) {
        $whitelistFid = $fid['fid'];
    }
    $db->delete_query('profilefields', "name = 'Whitelist'");
    $whitelistFid = "fid" .  $whitelistFid;
    $db->query("ALTER TABLE ".TABLE_PREFIX."userfields DROP " . $whitelistFid . "");
    rebuild_settings();
}

function whitelist_activate(){
  global $db, $mybb;
  include MYBB_ROOT."/inc/adminfunctions_templates.php";
  find_replace_templatesets("header", "#".preg_quote('{$awaitingusers}')."#i", '{$awaitingusers} {$header_whitelist}');
}

function whitelist_deactivate(){
  global $db, $mybb;
  include MYBB_ROOT."/inc/adminfunctions_templates.php";
  find_replace_templatesets("header", "#".preg_quote('{$header_whitelist}')."#i", '', 0);
}

//Benachrichtung bei Whitelist
$plugins->add_hook('global_intermediate', 'whitelist_alert');
function whitelist_alert(){
    global $db, $mybb, $templates, $header_whitelist; 

    $dayBegin = intval($mybb->settings['whitelist_dayBegin']);
    $alertDays = intval($mybb->settings['whitelist_echo']); 
    $fidWhitelist = intval($mybb->settings['whitelist_fid']); 
    $email = $mybb->user['email'];
    
    if(date("j", time()) == $dayBegin && date("H:i:s", time()) == '00:00:00'){
        $db->query("UPDATE ".TABLE_PREFIX."users SET hasSeenWhitelist = 0");
        $db->query("UPDATE ".TABLE_PREFIX."userfields SET fid". $fidWhitelist ." = 'Geht'");
    }

    if($_GET['seen'] == 1){
        $db->query("UPDATE ".TABLE_PREFIX."users SET hasSeenWhitelist = 1 WHERE email = '" . $email . "'");
    }

    $charas = $db->query("SELECT hasSeenWhitelist, fid". $fidWhitelist ."
    FROM ".TABLE_PREFIX."users u LEFT JOIN ".TABLE_PREFIX."userfields uf ON(u.uid=uf.ufid)
    WHERE email = '" . $email . "' 
    ORDER BY username");
    $header_whitelist = "";
    $dontSee = false;
    $noEcho = "";
    while($chara=$db->fetch_array($charas)) {
        if($chara['hasSeenWhitelist'] == 1){
            $dontSee = true;
        }
        if($chara['fid'. $fidWhitelist .''] == 'Bleibt'){
            $noEcho = "";
        }else{
            $noEcho = "Du hast dich noch nicht zurückgemeldet.";
        }
    }
    if(date("j", time()) <= ($alertDays + $dayBegin) && $alertDays != -1 && $mybb->user['uid'] != 0 && !$dontSee){
        eval("\$header_whitelist .= \"".$templates->get("whitelistHeader")."\";");
    }
}

?>