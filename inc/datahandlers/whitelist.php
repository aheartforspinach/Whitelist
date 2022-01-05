<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

global $plugins;

class whitelistHandler
{
    private $characters = [];

    public function __construct()
    {
        global $db, $mybb;
        $mainUid = $mybb->user['as_uid'] == 0 ? $mybb->user['uid'] : $mybb->user['as_uid'];
        $query = $db->simple_select(
            'users', 
            'uid, username, usergroup, whitelist, hasSeenWhitelist', 
            '(uid = '. $mainUid . ' or as_uid = '. $mainUid . ') and not find_in_set(usergroup, "'.$mybb->settings['whitelist_hiddenGroups'].'")',
            array('order_by' => 'username')
        );

        while ($row = $db->fetch_array($query)) {
            $this->characters[$row['uid']] = array(
                'username' => $row['username'],
                'usergroup' => $row['usergroup'],
                'stayOrGo' => intval($row['whitelist']),
                'hasSeenWhitelist' => intval($row['hasSeenWhitelist'])
            );
        }
    }

    /**
     *
     * @return array with character uids
     * 
    */
    private function getAllowedCharacters(): array
    {
        global $db, $mybb;

        $postInterval = intval($mybb->settings['whitelist_post']);
        $startDay = intval($mybb->settings['whitelist_dayBegin']);
        $startDay = intval($mybb->settings['whitelist_dayBegin']);
        $monthInterval = strtotime('-' . $postInterval . ' month');
        $firstXMonthAgo = $startDay . '.' . date('m.Y', $monthInterval);
        $UnixFirstXMonthAgo = strtotime($firstXMonthAgo);
        $allowedCharacters = [];

        $query = $db->simple_select(
            'ipt_scenes ips join '.  TABLE_PREFIX .'posts p on ips.tid = p.tid join '.  TABLE_PREFIX .'ipt_scenes_partners ipp on ips.tid = ipp.tid', 
            'ipp.uid',
            'ipp.uid in ('. implode(',', array_keys($this->characters)) .') and dateline > '. $UnixFirstXMonthAgo,
            ['order_by' => 'dateline', 'order_dir' => 'desc']
        );
        while ($row = $db->fetch_array($query)) {
            if (!in_array($row['uid'], $allowedCharacters)) {
                $allowedCharacters[] = (int)$row['uid'];
            }
        }

        // add characters which get a recent wob
        $wobSetting = intval($mybb->settings['whitelist_wob']);
        $wobNoPost = intval($mybb->settings['whitelist_wobNoPost']);
        if ($wobSetting === 1) {
            $whitelistStartDate = date('Y-m', time()) .'-'. intval($mybb->settings['whitelist_dayBegin']);
            $date = new DateTime($whitelistStartDate);
            $date->modify('-'.$wobNoPost.' days');
            $query2 = $db->simple_select('users', 'uid', 'wobSince > "'.$date->format('Y-m-d').'"');
            while($row = $db->fetch_array($query2)) {
                if (!in_array($row['uid'], $allowedCharacters)) {
                    $allowedCharacters[] = (int)$row['uid'];
                }
            }
        }

        return $allowedCharacters;
    }

    public function getCharacters(): array
    {
        return $this->characters;
    }

    public function hideBanner() 
    {
        global $db;
        $db->update_query(
            'users', 
            array('hasSeenWhitelist' => 1), 
            'find_in_set(uid, "'. implode(',', array_keys($this->characters)) .'")'
        );
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
            // check if post is required
            if (!$this->canStatusOfCharacterCanBeChange($uid, $character['usergroup'])) {
                continue;
            }

            $db->update_query('users', ['whitelist' => 1], 'uid = '. $uid);
            $this->characters[$uid]['stayOrGo'] = 1;
        }
    }

     /**
     *
     * @return bool
     * 
    */
    public function canStatusOfCharacterCanBeChange($uid, $usergroup) {
        global $mybb;
        $showWhitelistUntil = intval($mybb->settings['whitelist_echo']) + intval($mybb->settings['whitelist_dayBegin']);
        $hiddenGroups = explode(',', $mybb->settings['whitelist_hiddenGroups']);
        $postIsRequired = intval($mybb->settings['whitelist_post']) === -1 ? false : true;
        $allowedCharacters = $this->getAllowedCharacters();

        if (
            ($postIsRequired && !in_array($uid, $allowedCharacters)) || 
            date('j', time()) > $showWhitelistUntil || 
            in_array($usergroup, $hiddenGroups)
            ) 
        {
            return false;
        }

        return true;
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
            $fidIceDB = ', fid' . intval($mybb->settings['whitelist_ice']);
            $fidIce = intval($mybb->settings['whitelist_ice']);
        }

        $query = $db->simple_select(
            'users u join '.  TABLE_PREFIX .'userfields uf on u.uid = uf.ufid', 
            'uid, username, usergroup, displaygroup, whitelist, away, as_uid, fid'. $fidPlayer . $fidIceDB,
            'not find_in_set(usergroup, "'.$mybb->settings['whitelist_hiddenGroups'].'") ' . $invisibleAccounts,
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
                'ice' => $row['fid'. $fidIce],
                'playerName' => $row['fid'. $fidPlayer]
            ];
        }

        return $users;
    }
}
