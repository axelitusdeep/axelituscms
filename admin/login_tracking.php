<?php
class LoginTracker {
    private $loginsFile;
    
    public function __construct() {
        $this->loginsFile = DATA_DIR . '/private/logins.xml';
        $this->ensureLoginsFileExists();
    }
    
    private function ensureLoginsFileExists() {
        $privateDir = DATA_DIR . '/private';
        if (!is_dir($privateDir)) {
            mkdir($privateDir, 0755, true);
        }
        
        if (!file_exists($this->loginsFile)) {
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;
            $root = $xml->createElement('logins');
            $xml->appendChild($root);
            $xml->save($this->loginsFile);
            chmod($this->loginsFile, 0600);
        }
    }
    
    private function getClientInfo() {
        return [
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
            'platform' => $this->getPlatform(),
            'browser' => $this->getBrowser()
        ];
    }

    public function getClientIP() {
        $ip = 'Unknown';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    private function getPlatform() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/windows/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'macOS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) return 'iOS';
        
        return 'Unknown';
    }
    
    private function getBrowser() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/edg/i', $ua)) return 'Edge';
        if (preg_match('/chrome/i', $ua)) return 'Chrome';
        if (preg_match('/firefox/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) return 'Safari';
        if (preg_match('/opera|opr/i', $ua)) return 'Opera';
        
        return 'Unknown';
    }
    
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    public function recordLogin($username) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        
        if (!$xml->load($this->loginsFile)) {
            return false;
        }
        
        $root = $xml->documentElement;
        $clientInfo = $this->getClientInfo();
        $sessionToken = $this->generateSessionToken();
        
        $login = $xml->createElement('login');
        $login->setAttribute('id', uniqid('login_', true));
        $login->setAttribute('session_token', $sessionToken);
        
        $usernameNode = $xml->createElement('username');
        $usernameNode->appendChild($xml->createTextNode($username));
        $login->appendChild($usernameNode);
        
        $ipNode = $xml->createElement('ip');
        $ipNode->appendChild($xml->createTextNode($clientInfo['ip']));
        $login->appendChild($ipNode);
        
        $uaNode = $xml->createElement('user_agent');
        $uaNode->appendChild($xml->createCDATASection($clientInfo['user_agent']));
        $login->appendChild($uaNode);
        
        $langNode = $xml->createElement('language');
        $langNode->appendChild($xml->createTextNode($clientInfo['language']));
        $login->appendChild($langNode);
        
        $platformNode = $xml->createElement('platform');
        $platformNode->appendChild($xml->createTextNode($clientInfo['platform']));
        $login->appendChild($platformNode);
        
        $browserNode = $xml->createElement('browser');
        $browserNode->appendChild($xml->createTextNode($clientInfo['browser']));
        $login->appendChild($browserNode);
        
        $loginTimeNode = $xml->createElement('login_time');
        $loginTimeNode->appendChild($xml->createTextNode(date('Y-m-d H:i:s')));
        $login->appendChild($loginTimeNode);
        
        $lastActivityNode = $xml->createElement('last_activity');
        $lastActivityNode->appendChild($xml->createTextNode(date('Y-m-d H:i:s')));
        $login->appendChild($lastActivityNode);
        
        $statusNode = $xml->createElement('status');
        $statusNode->appendChild($xml->createTextNode('active'));
        $login->appendChild($statusNode);
        
        $root->appendChild($login);
        
        if ($xml->save($this->loginsFile)) {
            $_SESSION['login_token'] = $sessionToken;
            return true;
        }
        
        return false;
    }
    
    public function updateActivity() {
        if (!isset($_SESSION['login_token'])) {
            return false;
        }
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        
        if (!$xml->load($this->loginsFile)) {
            return false;
        }
        
        $xpath = new DOMXPath($xml);
        $sessionToken = $_SESSION['login_token'];
        
        $nodes = $xpath->query("//login[@session_token='$sessionToken']");
        
        if ($nodes->length > 0) {
            $loginNode = $nodes->item(0);
            
            $lastActivityNodes = $xpath->query('last_activity', $loginNode);
            if ($lastActivityNodes->length > 0) {
                $lastActivityNodes->item(0)->nodeValue = date('Y-m-d H:i:s');
                return $xml->save($this->loginsFile);
            }
        }
        
        return false;
    }
    
    public function getActiveLogins() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        
        if (!$xml->load($this->loginsFile)) {
            return [];
        }
        
        $xpath = new DOMXPath($xml);
        $logins = [];
        
        $nodes = $xpath->query("//login[status='active']");
        
        foreach ($nodes as $node) {
            $loginId = $node->getAttribute('id');
            $sessionToken = $node->getAttribute('session_token');
            
            $logins[] = [
                'id' => $loginId,
                'session_token' => $sessionToken,
                'username' => $xpath->query('username', $node)->item(0)->nodeValue,
                'ip' => $xpath->query('ip', $node)->item(0)->nodeValue,
                'user_agent' => $xpath->query('user_agent', $node)->item(0)->nodeValue,
                'language' => $xpath->query('language', $node)->item(0)->nodeValue,
                'platform' => $xpath->query('platform', $node)->item(0)->nodeValue,
                'browser' => $xpath->query('browser', $node)->item(0)->nodeValue,
                'login_time' => $xpath->query('login_time', $node)->item(0)->nodeValue,
                'last_activity' => $xpath->query('last_activity', $node)->item(0)->nodeValue,
                'is_current' => ($sessionToken === ($_SESSION['login_token'] ?? ''))
            ];
        }
        
        usort($logins, function($a, $b) {
            return strtotime($b['login_time']) - strtotime($a['login_time']);
        });
        
        return $logins;
    }
    
    public function logoutSession($loginId) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        
        if (!$xml->load($this->loginsFile)) {
            return false;
        }
        
        $xpath = new DOMXPath($xml);
        $nodes = $xpath->query("//login[@id='$loginId']");
        
        if ($nodes->length > 0) {
            $loginNode = $nodes->item(0);
            
            $statusNodes = $xpath->query('status', $loginNode);
            if ($statusNodes->length > 0) {
                $statusNodes->item(0)->nodeValue = 'logged_out';
            }
            
            $logoutTimeNode = $xml->createElement('logout_time');
            $logoutTimeNode->appendChild($xml->createTextNode(date('Y-m-d H:i:s')));
            $loginNode->appendChild($logoutTimeNode);
            
            return $xml->save($this->loginsFile);
        }
        
        return false;
    }
    
    public function banIP($ip) {
        $bannedFile = DATA_DIR . '/private/banned_ips.json';
        
        $banned = [];
        if (file_exists($bannedFile)) {
            $banned = json_decode(file_get_contents($bannedFile), true) ?: [];
        }
        
        if (!in_array($ip, $banned)) {
            $banned[] = $ip;
            file_put_contents($bannedFile, json_encode($banned, JSON_PRETTY_PRINT));
            chmod($bannedFile, 0600);

            $this->logoutByIP($ip);
            
            return true;
        }
        
        return false;
    }
    
    public function unbanIP($ip) {
        $bannedFile = DATA_DIR . '/private/banned_ips.json';
        
        if (file_exists($bannedFile)) {
            $banned = json_decode(file_get_contents($bannedFile), true) ?: [];
            $banned = array_filter($banned, function($bannedIp) use ($ip) {
                return $bannedIp !== $ip;
            });
            
            file_put_contents($bannedFile, json_encode(array_values($banned), JSON_PRETTY_PRINT));
            return true;
        }
        
        return false;
    }
    
    public function isIPBanned($ip) {
        $bannedFile = DATA_DIR . '/private/banned_ips.json';
        
        if (file_exists($bannedFile)) {
            $banned = json_decode(file_get_contents($bannedFile), true) ?: [];
            return in_array($ip, $banned);
        }
        
        return false;
    }
    
    public function getBannedIPs() {
        $bannedFile = DATA_DIR . '/private/banned_ips.json';
        
        if (file_exists($bannedFile)) {
            return json_decode(file_get_contents($bannedFile), true) ?: [];
        }
        
        return [];
    }
    
    private function logoutByIP($ip) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        
        if (!$xml->load($this->loginsFile)) {
            return false;
        }
        
        $xpath = new DOMXPath($xml);
        $nodes = $xpath->query("//login[ip='$ip' and status='active']");
        
        foreach ($nodes as $loginNode) {
            $statusNodes = $xpath->query('status', $loginNode);
            if ($statusNodes->length > 0) {
                $statusNodes->item(0)->nodeValue = 'banned';
            }
            
            $logoutTimeNode = $xml->createElement('logout_time');
            $logoutTimeNode->appendChild($xml->createTextNode(date('Y-m-d H:i:s')));
            $loginNode->appendChild($logoutTimeNode);
        }
        
        return $xml->save($this->loginsFile);
    }
    
    public function cleanOldSessions($daysOld = 30) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        
        if (!$xml->load($this->loginsFile)) {
            return false;
        }
        
        $xpath = new DOMXPath($xml);
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$daysOld days"));
        
        $nodes = $xpath->query("//login[status!='active']");
        $removed = 0;
        
        foreach ($nodes as $node) {
            $lastActivity = $xpath->query('last_activity', $node)->item(0)->nodeValue;
            
            if ($lastActivity < $cutoffDate) {
                $node->parentNode->removeChild($node);
                $removed++;
            }
        }
        
        if ($removed > 0) {
            return $xml->save($this->loginsFile);
        }
        
        return true;
    }
}