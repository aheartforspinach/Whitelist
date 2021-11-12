<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

global $plugins;

class whitelistHandler
{
    private $characters = [];

    public function __construct($uid)
    {
        global $db;
        $uid = $db->escape_string($uid);
        $query = $db->simple_select('users', 'uid, username, usergroup, whitelist, hasSeenWhitelist', 'uid = '. $uid . ' or as_uid = '. $uid);

        while ($row = $db->fetch_array($query)) {
            $this->characters[$row['uid']] = array(
                'username' => $row['username'],
                'usergroup' => $row['usergroup'],
                'stayOrGo' => intval($row['whitelist']),
                'hasSeenWhitelist' => intval($row['hasSeenWhitelist'])
            );
        }
    }

    private function isOlderThanXMonth($dateToTest) {
        global $mybb;
        $postInterval = intval($mybb->settings['whitelist_post']);
        $startDay = intval($mybb->settings['whitelist_dayBegin']);

        $monthInterval = strtotime('-' . $postInterval . ' month');
        $firstXMonthAgo = $startDay . '.' . date('m.Y', $monthInterval);
        $UnixFirstXMonthAgo = strtotime($firstXMonthAgo);
        return $dateToTest > $UnixFirstXMonthAgo;
    }

    public function getCharacters(): array
    {
        return $this->characters;
    }

    public function hideBanner() 
    {
        global $db;
        $db->update_query('users', array('hasSeenWhitelist' => 1), 'find_in_set("uid", "'. implode(',', array_keys($this->characters)) .'")');
    }

    /**
     *
     * @return array with all 
     * [
     *      'hideWhitelist' => bool,
            'reactToWhitelist' => bool
     * ]
     * 
    */
    public function getReactionWhitelist(): array
    {
        $shouldHideWhitelist = false;
        $hasReactToWhitelist = false;
        foreach($this->characters as $character) {
            if ($character['hasSeenWhitelist'] === 1) 
                $shouldHideWhitelist = true;

            if ($character['stayOrGo'] === 1)
                $hasReactToWhitelist = true;
        }

        return [
            'hideWhitelist' => $shouldHideWhitelist,
            'reactToWhitelist' => $hasReactToWhitelist
        ];
    }

    public function setCharactersOnStay() {
        global $db;
        foreach ($this->characters as $uid => $character) {
            $db->update_query('users', ['whitelist' => 1], 'uid = '. $uid);
            $this->characters[$uid]['stayOrGo'] = 1;
        }
    }

    /**
     *
     * @return array with character uids
     * 
    */
    public function getAllowedCharacters(): array
    {
        global $db;

        $allowedCharacters = [];

        $query = $db->simple_select(
            'ipt_scenes ips join '.  TABLE_PREFIX .'threads t on ips.tid = t.tid', 
            'uid, dateline',
            'find_in_set("uid", "'. implode(',', array_keys($this->characters)) .'")'
        );
        while ($row = $db->fetch_array($query)) {
            if (!$this->isOlderThanXMonth($row['dateline']) && !in_array($row['uid'], $allowedCharacters))
                $allowedCharacters[] = (int)$row['uid'];
        }

        return $allowedCharacters;
    }

    /**
     *
     * @return array with character uids
     * 
    */
    public function getAllUsers(): array
    {
        global $db, $mybb;

        $fidPlayer = intval($mybb->settings['whitelist_player']);
        // collect invisble accounts
        $invisibleAccounts = '';
        $accounts = explode(', ', $db->escape_string($mybb->settings['whitelist_teamaccs']));
        foreach ($accounts as $account) {
            if ($account != -1) {
                $invisibleAccounts .= 'XOR uid = ' . $account . ' ';
            }
        }

        // ice db fields
        $fidIce = $fidIceDB = '';
        if (intval($mybb->settings['whitelist_ice']) !== -1) {
            $fidIceDB = 'fid' . intval($mybb->settings['whitelist_ice']) . ',';
            $fidIce = intval($mybb->settings['whitelist_ice']);
        }

        $query = $db->simple_select(
            'users u join '.  TABLE_PREFIX .'userfields uf on uid = ufid', 
            'uid, username, usergroup, displaygroup, whitelist, away, as_uid, fid'. $fidPlayer,
            'not find_in_set("usergroup", "'.$mybb->settings['whitelist_hiddenGroups'].'")'. $invisibleAccounts,
            array('order_by' => 'username')
        );

        $users = [];
        while ($row = $db->fetch_array($query)) {
            $users[$row['uid']] = [
                'username' => $row['username'],
                'usergroup' => $row['usergroup'],
                'displaygroup' => $row['displaygroup'],
                'stayOrGo' => $row['whitelist'],
                'away' => $row['away'],
                'as_uid' => $row['as_uid'],
                'playerName' => $row['fid'. $fidPlayer]
            ];
        }

        return $users;
    }
}
