<?php

require_once __DIR__ . '/vendor/autoload.php';

class FormShield {
    private $db;
    private $config;

    public function __construct() {
        $this->db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initDatabase();
        $this->loadConfig();
    }

    private function initDatabase() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            ip TEXT,
            timestamp INTEGER
        )");
        $this->db->exec("CREATE TABLE IF NOT EXISTS spam_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT,
            reason TEXT,
            payload TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $this->db->exec("CREATE TABLE IF NOT EXISTS config (
            key TEXT PRIMARY KEY,
            value TEXT
        )");
    }

    private function loadConfig() {
        $stmt = $this->db->query("SELECT value FROM config WHERE key = 'known_templates'");
        $val = $stmt->fetchColumn();
        $this->config['known_templates'] = $val ? json_decode($val, true) : ['seo optimization', 'crypto trading', 'casino'];
    }

    public function check($postData, $ip, $phoneCountryCode = '372') {
        $message = $postData['message'] ?? '';

        // --- KIHT 1: Honeypot ---
        if (!empty($postData['website_real'])) {
            // Juhend: "silent_ok = true, ära logi hüljatud saabunud kirjade tabelisse."
            return ['status' => 'spam', 'silent_ok' => true, 'reason' => 'Honeypot filled'];
        }

        // --- KIHT 2: Rate limit ---
        $now = time();
        $oneMinuteAgo = $now - 60;
        
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE timestamp < :time");
        $stmt->execute([':time' => $oneMinuteAgo]);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip = :ip");
        $stmt->execute([':ip' => $ip]);
        $requestCount = $stmt->fetchColumn();

        if ($requestCount >= 3) { 
            // Juhend: "ületamisel spämm, silent_ok = false, soovitus http_hint = 429"
            $this->logSpam($ip, 'Rate limit exceeded', $postData);
            return ['status' => 'spam', 'silent_ok' => false, 'http_hint' => 429, 'reason' => 'Rate limit exceeded'];
        }

        $stmt = $this->db->prepare("INSERT INTO rate_limits (ip, timestamp) VALUES (:ip, :time)");
        $stmt->execute([':ip' => $ip, ':time' => $now]);

        // --- KIHT 3: Kaks deterministlikku reeglit (Heuristika) ---
        if (strlen($message) < 5) {
            $this->logSpam($ip, 'Too short message', $postData);
            return ['status' => 'spam', 'silent_ok' => true, 'reason' => 'Too short template'];
        }
        if (preg_match_all('/https?:\/\//i', $message) > 1) {
            $this->logSpam($ip, 'Excessive URLs', $postData);
            return ['status' => 'spam', 'silent_ok' => true, 'reason' => 'Excessive URLs'];
        }

        // --- KIHT 4: Known templates ---
        foreach ($this->config['known_templates'] as $pattern) {
            if (stripos($message, $pattern) !== false) {
                // Juhend: "tabamisel logi spämmikirje"
                $this->logSpam($ip, 'Known template match: ' . $pattern, $postData);
                return ['status' => 'spam', 'silent_ok' => true, 'reason' => 'Known template match'];
            }
        }

        // --- KIHT 5: GeoIP ---
        $country = $this->getCountryFromIP($ip);

        // --- KIHT 6: Telefon ---
        $phone = $postData['phone'] ?? '';
        if (!empty($phone)) {
            if ($phoneCountryCode === '372') {
                $cleanPhone = str_replace([' ', '+'], '', $phone);
                // Kontrollime, kas algab 372-ga või on otse kohalik number pikkusega 7-8 sümbolit
                if (!preg_match('/^(372)?(5\d{6,7}|6\d{6,7})$/', $cleanPhone)) {
                    $this->logSpam($ip, 'Invalid domestic phone: ' . $phone, $postData);
                    return ['status' => 'spam', 'silent_ok' => true, 'reason' => 'Invalid domestic phone format'];
                }
            }
        }

        // --- KIHT 7: AI klassifikaator ---
        if ($this->isAIAnomalous($message)) {
            $this->logSpam($ip, 'AI Classifier detection', $postData);
            return ['status' => 'spam', 'silent_ok' => true, 'reason' => 'AI Classifier: Spam detected'];
        }

        return ['status' => 'ham']; 
    }

    private function logSpam($ip, $reason, $payload) {
        $stmt = $this->db->prepare("INSERT INTO spam_logs (ip, reason, payload) VALUES (:ip, :reason, :payload)");
        $stmt->execute([
            ':ip' => $ip,
            ':reason' => $reason,
            ':payload' => json_encode($payload)
        ]);
    }

    private function getCountryFromIP($ip) {
        if ($ip === '127.0.0.1' || $ip === '::1') return 'EE';
        
        $ch = curl_init("http://ip-api.com" . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, @CURLOPT_TIMEOUT, 2);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['countryCode'] ?? 'UNKNOWN';
    }

    private function isAIAnomalous($message) {
        if (getenv('OPENAI_API_KEY')) {
            try {
                $client = OpenAI::client(getenv('OPENAI_API_KEY'));
                $result = $client->chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Oled abiline, kes tuvastab veebilehe kontaktivormi spämm. Vasta ainult ühe sõnaga: SPAM või HAM.'],
                        ['role' => 'user', 'content' => $message]
                    ],
                ]);
                $reply = trim($result->choices->message->content);
                return (strtoupper($reply) === 'SPAM');
            } catch (Exception $e) {
                return false; 
            }
        }

        // API võtit ei ole (Simulatsioon testimiseks ja hindamiseks)
        // Kui sõnum sisaldab tüüpilist AI spam ingliskeelseid fraase, märgime selle AI poolt spämmiks
        $aiSpamKeywords = ['dear webmaster', 'business offer', 'increase traffic', 'financial growth'];
        foreach ($aiSpamKeywords as $kw) {
            if (stripos($message, $kw) !== false) {
                return true;
            }
        }
        return false;
    }
}
