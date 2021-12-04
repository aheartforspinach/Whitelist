<?php
// automatische Whitelist by aheartforspinach

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function whitelist_info()
{
    global $db;
    $option = '';
    if ($db->field_exists('whitelist', 'users'))
        $option = '<div style="float: right;"><a href="index.php?module=config&action=change&search=whitelist">Einstellungen</a></div>';


    return array(
        "name"            => "Whitelist",
        "description"    => "Erstellt automatisch jeden Monat eine Whitelist". $option,
        "author"        => "aheartforspinach",
        "authorsite"    => "https://github.com/aheartforspinach",
        "version"        => "2.0.2",
        "compatibility" => "18*"
    );
}

function whitelist_install()
{
    global $db;

    // database
    $db->add_column('users', 'whitelist', 'tinyint not null default 0');
    $db->add_column('users', 'hasSeenWhitelist', 'tinyint not null default 0');

    // tasks
    $date = new DateTime('01.' . date("m.Y", strtotime('+1 month')));
    $date->setTime(1, 0, 0);
    $whitelistTask = array(
        'title' => 'Whitelist Reset',
        'description' => 'Automatically resets all fields from the whitelist plugin',
        'file' => 'whitelist',
        'minute' => 0,
        'hour' => 0,
        'day' => 1,
        'month' => '*',
        'weekday' => '*',
        'nextrun' => $date->getTimestamp(),
        'logging' => 1,
        'locked' => 0
    );
    $db->insert_query('tasks', $whitelistTask);

    // css
    $css = array(
		'name' => 'whitelist.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" =>	'.whitelist-form-heading-container {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.whitelist-form-heading-container .button {
    height: min-content;
    align-self: center;
    margin-left: 15px;	
}

.whitelist-form-heading-container form {
    align-self: center;
    margin-left: 20px;
}

.whitelist-form-characters-container {
    display: grid;
    grid-gap: 15px;
    grid-template-columns: repeat(5, 1fr);
}

.whitelist-banner-close {
    font-size: 14px;
    margin-top: -2px;
    float: right;
}',
		'cachefile' => '',
		'lastmodified' => time()
	);

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

	$sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}

    // settings 
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
            'value' => 1, 
            'disporder' => 1
        ),
        'whitelist_hiddenGroups' => array(
            'title' => 'Auflistung von Gruppen',
            'description' => 'Welche Gruppen sollen sich nicht zurückmelden können? Wenn nein: nichts auswählen',
            'optionscode' => 'groupselect',
            'value' => '',
            'disporder' => 2
        ),
        'whitelist_showUser' => array(
            'title' => 'User verstecken',
            'description' => 'Sollen User nur ihre eigenen Charaktere auf der BL sehen? Falls nein, sehen User alle Charaktere und nicht nur ihre',
            'optionscode' => 'yesno',
            'value' => 0,
            'disporder' => 3
        ),
        'whitelist_teamaccs' => array(
            'title' => 'Teamaccount',
            'description' => 'Gib hier mit Komma getrennt die UIDs von den Accounts an, die NICHT gelistet werden sollen. Falls alle gelistet werden sollen, gib -1 ein',
            'optionscode' => 'text',
            'value' => '998, 999',
            'disporder' => 4
        ),
        'whitelist_ice' => array(
            'title' => 'Auf Eis Profilfeld',
            'description' => 'Gib hier die ID von deinem Profilfeld ein, ob der Charakter auf Eis ist. -1 bedeutet, dass du dieses Profilfeld nicht nutzt',
            'optionscode' => 'numeric',
            'value' => -1,
            'disporder' => 5
        ),
        'whitelist_player' => array(
            'title' => 'Spieler Profilfeld',
            'description' => 'Gib hier die ID von deinem Profilfeld ein, wo man den Spielernamen einträgt',
            'optionscode' => 'text',
            'value' => '-1',
            'disporder' => 6
        ),
        'whitelist_echo' => array(
            'title' => 'Rückmeldezeitraum',
            'description' => 'Bis zu welchen Tag darf man sich zurückmelden? (Hinweis: bis zu diesem Tag wird auch der Hinweis auf dem Index angezeigt)',
            'optionscode' => 'numeric',
            'value' => 7,
            'disporder' => 7
        ),
        'whitelist_dayBegin' => array(
            'title' => 'Veröffentlichungsdatum',
            'description' => 'An welchem Tag soll die Whitelist veröffentlicht werden?',
            'optionscode' => 'numeric',
            'value' => 1,
            'disporder' => 8
        ),
        'whitelist_post' => array(
            'title' => 'Mindestpostzahl',
            'description' => 'Falls in den letzten x Monaten ein Post erfolgt haben muss, trage hier z.B. eine 2 ein, wenn man in den letzten zwei Monaten mind. einen Post geschrieben haben musst. -1 falls so etwas nicht gewünscht ist',
            'optionscode' => 'numeric',
            'value' => 1,
            'disporder' => 9
        ),
    );

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    // templates
    addTemplates();

    rebuild_settings();
}

function whitelist_is_installed()
{
    global $db;
    return $db->field_exists('whitelist', 'users');
}

function whitelist_uninstall()
{
    global $db;
    $db->delete_query('settings', "name like 'whitelist_%'");
    $db->delete_query('settinggroups', "name = 'whitelist'");
    $db->delete_query("templates", "title like 'whitelist%'");
    $db->delete_query("templategroups", 'prefix = "whitelist"');

    if ($db->field_exists('hasSeenWhitelist', 'users'))
        $db->drop_column('users', 'hasSeenWhitelist');

    if ($db->field_exists('whitelist', 'users'))
        $db->drop_column('users', 'whitelist');

    $db->delete_query('tasks', 'file = "whitelist"');

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'whitelist.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}

    rebuild_settings();
}

function whitelist_activate()
{
    global $db, $mybb;
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$awaitingusers}') . "#i", '{$awaitingusers} {$header_whitelist}');
}

function whitelist_deactivate()
{
    global $db, $mybb;
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$header_whitelist}') . "#i", '', 0);
}

// banner
$plugins->add_hook('global_intermediate', 'whitelist_alert');
function whitelist_alert()
{
    global $mybb, $templates, $header_whitelist, $lang;
    require_once 'inc/datahandlers/whitelist.php';

    if (!whitelist_is_installed()) return;

    $lang->load('whitelist');
    $whitelistHandler = new whitelistHandler();
    $dayBegin = intval($mybb->settings['whitelist_dayBegin']);
    $alertDays = intval($mybb->settings['whitelist_echo']);


    // hide banner 
    if ($_POST['seen'] == 1) {
        $whitelistHandler->hideBanner();
    }

    $reactionWhitelist = $whitelistHandler->getReactionWhitelist();
    $hideWhitelist = $reactionWhitelist['hideWhitelist'];
    $echo = $reactionWhitelist['reactToWhitelist'] ? '' : $lang->whitelist_hasnt_react_to_whitelist;

    if (date("j", time()) <= ($alertDays + $dayBegin) && $alertDays != -1 && $mybb->user['uid'] != 0 && !$hideWhitelist) {
        eval("\$header_whitelist .= \"" . $templates->get("whitelist_header") . "\";");
    }
}

// update
$plugins->add_hook('misc_start', 'whitelist_misc_start');
function whitelist_misc_start()
{
    global $mybb, $db;
    
    if ($mybb->get_input('action') != 'whitelist-update') {
        return;
    }

    if ($db->field_exists('whitelist', 'users')) {
        error('Plugin wurde bereits geupdatet');
        return;
    }

    // remove unnecessary settings
    $db->delete_query('settings', "name in ('whitelist_applicant', 'whitelist_fid', 'whitelist_inplay', 'whitelist_archive')");

    // add new setting
    $gid = $db->fetch_field($db->simple_select('settinggroups', 'gid', 'name ="whitelist"'), 'gid');
    $db->insert_query('settings', [
        'gid' => $gid,
        'name' => 'whitelist_hiddenGroups',
        'title' => 'Auflistung von Gruppen',
        'description' => 'Welche Gruppen sollen sich nicht zurückmelden können? Wenn nein: nichts auswählen',
        'optionscode' => 'groupselect',
        'value' => '',
        'disporder' => 2
    ]);

    // add new db column
    $db->add_column('users', 'whitelist', 'tinyint not null default 0');

    // add new templates and css
    addTemplates();

    rebuild_settings();
}

function addTemplates() {
    global $db;

    $templategroup = array(
        "prefix" => "whitelist",
        "title" => $db->escape_string("Whitelist"),
    );

    $db->insert_query("templategroups", $templategroup);

    $insert_array = array(
        'title'        => 'whitelist',
        'template'    => $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{$mybb->settings[\'bbname\']} - Whitelist</title>
    {$headerinclude}
</head>

<body>
    {$header}
    <div class="panel" id="panel">
        <div id="panel">{$menu}</div>
        <h1>{$lang->whitelist_heading}{$thisMonth}</h1>

        <blockquote>{$lang->whitelist_explanation}</blockquote>

        {$form}

        <table style="width:95%; margin:auto;">
            <tr>
                <th width="25%" class="thead">
                    {$lang->whitelist_stay}
                </th>
                <th width="25%" class="thead">
                    {$lang->whitelist_go}
                </th>
                <th width="25%" class="thead">
                    {$lang->whitelist_away}
                </th>
                {$iceTh}
            </tr>
            <tr>
                <td valign="top">{$stay}</td>
                <td valign="top">{$go}</td>
                <td valign="top">{$away}</td>
                {$iceTd}
            </tr>
        </table>

    </div>
    {$footer}
</body>

</html>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'whitelist_characters',
        'template'    => $db->escape_string('<div>{$userlink}<br>
    <input type="hidden" name="uid{$uid}" value="{$uid}">
    <input type="radio" name="status{$uid}" id="stay{$uid}" value="1" {$checkedStay}> <label for="stay{$uid}">{$lang->whitelist_stay_action}</label><br>
    <input type="radio" name="status{$uid}" id="go{$uid}" value="0" {$checkedGo}> <label for="go{$uid}">{$lang->whitelist_go_action}</label><br>
</div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'whitelist_form',
        'template'    => $db->escape_string('<div style="width:95%; margin:auto;">
    <div class="whitelist-form-heading-container">
        <h3>{$lang->whitelist_own_characters}</h3>
        <form action="whitelist.php" method="post">
            <input type="hidden" name="setAllCharactersOnStay" value="1" />
            <input type="submit" value="{$lang->whitelist_submit_all_characters}" class="button" />
        </form>
    </div>
        
    <form action="whitelist.php" method="post">
        <div class="whitelist-form-characters-container">{$charactersForm}</div>
        <br>
        <div style="text-align:center;">
            <input type="submit" value="{$lang->whitelist_submit}" class="button" />
        </div>
    </form>
</div>
<br>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'whitelist_ice_td',
        'template'    => $db->escape_string('<td valign="top">{$ice}</td>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'whitelist_ice_th',
        'template'    => $db->escape_string('<th width="25%" class="thead">
    {$lang->whitelist_ice}
</th>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'whitelist_user',
        'template'    => $db->escape_string('{$username}</br>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'whitelist_header',
        'template'    => $db->escape_string('<div class="pm_alert">
    {$lang->whitelist_banner} {$echo}
    <span id="whitelist-close" class="whitelist-banner-close" style="cursor: pointer;" onclick="hideWhitelistBanner()">✕</span>
</div>

<script>
    function hideWhitelistBanner() {
        let formData = new FormData();
        formData.append(\'seen\', \'1\');
        fetch(\'whitelist.php\', {
            method: \'POST\',
            body: formData
        });
        document.querySelector(\'#whitelist-close\').closest(\'.pm_alert\').style.display = \'none\';
    }
</script>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
}