<?php
// ==================== CONFIGURATION ====================
$environment = getenv('ENVIRONMENT') ?: 'production';

if ($environment === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// ==================== ERROR LOGGING ====================
function log_error($message, $type = 'ERROR', $context = []) {
    $log_entry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    @file_put_contents('error.log', $log_entry, FILE_APPEND);
    @chmod('error.log', 0666);
    @error_log($message);
    
    if (getenv('ENVIRONMENT') === 'development') {
        echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
    }
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_error("PHP Error [$errno]: $errstr in $errfile on line $errline", 'PHP_ERROR');
    return false;
});

set_exception_handler(function($exception) {
    log_error("Uncaught Exception: " . $exception->getMessage(), 'EXCEPTION', [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

log_error("Bot script started", 'INFO', [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'uri' => $_SERVER['REQUEST_URI'] ?? ''
]);

// ==================== ENVIRONMENT CONFIGURATION ====================
$ENV_CONFIG = [
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '8315381064:AAGk0FGVGmB8j5SjpBvW3rD3_kQHe_hyOWU',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: '@EntertainmentTadkaBot',
    'API_ID' => getenv('API_ID') ?: '21944581',
    'API_HASH' => getenv('API_HASH') ?: '7b1c174a5cd3466e25a976c39a791737',
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    
    'PUBLIC_CHANNELS' => [
        ['id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395', 'username' => getenv('PUBLIC_CHANNEL_1_USERNAME') ?: '@EntertainmentTadka786'],
        ['id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1003614546520', 'username' => getenv('PUBLIC_CHANNEL_2_USERNAME') ?: '@Entertainment_Tadka_Serial_786'],
        ['id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002831605258', 'username' => getenv('PUBLIC_CHANNEL_3_USERNAME') ?: '@threater_print_movies'],
        ['id' => getenv('PUBLIC_CHANNEL_4_ID') ?: '-1002964109368', 'username' => getenv('PUBLIC_CHANNEL_4_USERNAME') ?: '@ETBackup']
    ],
    
    'PRIVATE_CHANNELS' => [
        ['id' => getenv('PRIVATE_CHANNEL_1_ID') ?: '-1003251791991', 'username' => getenv('PRIVATE_CHANNEL_1_USERNAME') ?: 'Private Channel 1'],
        ['id' => getenv('PRIVATE_CHANNEL_2_ID') ?: '-1002337293281', 'username' => getenv('PRIVATE_CHANNEL_2_USERNAME') ?: 'Private Channel 2']
    ],
    
    'REQUEST_GROUP' => [
        'id' => getenv('REQUEST_GROUP_ID') ?: '-1003083386043',
        'username' => getenv('REQUEST_GROUP_USERNAME') ?: '@EntertainmentTadka7860'
    ],
    
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'REQUESTS_FILE' => 'requests.json',
    'BACKUP_DIR' => 'backups/',
    'CACHE_DIR' => 'cache/',
    'USER_SETTINGS_FILE' => 'user_settings.json',
    
    'CACHE_EXPIRY' => 300,
    'ITEMS_PER_PAGE' => 5,
    'CSV_BUFFER_SIZE' => 50,
    'MAX_REQUESTS_PER_DAY' => 3,
    'REQUEST_SYSTEM_ENABLED' => true,
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60
];

if (empty($ENV_CONFIG['BOT_TOKEN'])) {
    http_response_code(500);
    die("❌ Bot Token not configured.");
}

define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_IDS', $ENV_CONFIG['ADMIN_IDS']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUESTS_FILE', $ENV_CONFIG['REQUESTS_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('USER_SETTINGS_FILE', $ENV_CONFIG['USER_SETTINGS_FILE']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);

define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('SERIAL_CHANNEL', '@Entertainment_Tadka_Serial_786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('BACKUP_CHANNEL', '@ETBackup');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');

// ==================== RATE LIMITING ====================
class RateLimiter {
    private static $limits = [];
    
    public static function check($key, $limit = 30, $window = 60) {
        $now = time();
        $window_start = $now - $window;
        
        if (!isset(self::$limits[$key])) self::$limits[$key] = [];
        self::$limits[$key] = array_filter(self::$limits[$key], fn($t) => $t > $window_start);
        
        if (count(self::$limits[$key]) >= $limit) return false;
        
        self::$limits[$key][] = $now;
        return true;
    }
}

// ==================== AUTO-DELETE MANAGER ====================
class AutoDeleteManager {
    private static $instance = null;
    private $db_file = 'auto_delete.json';
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists($this->db_file)) {
            $default = ['messages' => [], 'stats' => ['total_deleted' => 0, 'last_cleanup' => date('Y-m-d H:i:s')]];
            file_put_contents($this->db_file, json_encode($default, JSON_PRETTY_PRINT));
            chmod($this->db_file, 0666);
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->db_file), true) ?: ['messages' => [], 'stats' => ['total_deleted' => 0, 'last_cleanup' => date('Y-m-d H:i:s')]];
    }
    
    private function saveData($data) {
        return file_put_contents($this->db_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function registerMessage($chat_id, $message_id, $user_id, $timer_seconds) {
        if ($timer_seconds <= 0) return false;
        
        $data = $this->loadData();
        $delete_time = time() + $timer_seconds;
        $warning_time = $delete_time - 30;
        
        $data['messages'][] = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'user_id' => $user_id,
            'registered_at' => time(),
            'delete_at' => $delete_time,
            'warning_sent' => false,
            'warning_time' => $warning_time,
            'status' => 'pending'
        ];
        
        $this->saveData($data);
        log_error("Message registered for auto-delete", 'INFO', ['chat_id' => $chat_id, 'message_id' => $message_id, 'delete_in' => $timer_seconds . ' seconds']);
        return true;
    }
    
    public function checkAndDelete() {
        $data = $this->loadData();
        $now = time();
        $deleted_count = 0;
        $remaining_messages = [];
        
        foreach ($data['messages'] as $msg) {
            if ($now >= $msg['delete_at']) {
                $this->deleteMessage($msg['chat_id'], $msg['message_id']);
                $deleted_count++;
                $data['stats']['total_deleted']++;
            } elseif ($now >= $msg['warning_time'] && !$msg['warning_sent']) {
                $this->sendWarning($msg['chat_id'], $msg['message_id'], $msg['delete_at'] - $now);
                $msg['warning_sent'] = true;
                $remaining_messages[] = $msg;
            } else {
                $remaining_messages[] = $msg;
            }
        }
        
        if ($deleted_count > 0) {
            $data['messages'] = $remaining_messages;
            $data['stats']['last_cleanup'] = date('Y-m-d H:i:s');
            $this->saveData($data);
        }
        
        return $deleted_count;
    }
    
    private function sendWarning($chat_id, $message_id, $seconds_left) {
        $minutes = ceil($seconds_left / 60);
        $warning = "⚠️ <b>Warning!</b> Yeh message {$minutes} minute mein automatically delete ho jayega!";
        apiRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $warning, 'reply_to_message_id' => $message_id, 'parse_mode' => 'HTML']);
    }
    
    private function deleteMessage($chat_id, $message_id) {
        return apiRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);
    }
}

// ==================== SETTINGS MANAGER ====================
class SettingsManager {
    private static $instance = null;
    private $settings_file = USER_SETTINGS_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists($this->settings_file)) {
            file_put_contents($this->settings_file, json_encode(['users' => []], JSON_PRETTY_PRINT));
            chmod($this->settings_file, 0666);
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->settings_file), true) ?: ['users' => []];
    }
    
    private function saveData($data) {
        return file_put_contents($this->settings_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function getSettings($user_id) {
        $data = $this->loadData();
        $defaults = [
            'auto_scan' => true,
            'spoiler_mode' => false,
            'top_search' => true,
            'priority' => 'quality',
            'layout' => 'buttons',
            'timer' => 0,
            'language' => 'en'
        ];
        return array_merge($defaults, $data['users'][$user_id] ?? []);
    }
    
    public function updateSettings($user_id, $key, $value) {
        $data = $this->loadData();
        if (!isset($data['users'][$user_id])) $data['users'][$user_id] = [];
        $data['users'][$user_id][$key] = $value;
        return $this->saveData($data);
    }
    
    public function resetSettings($user_id) {
        $data = $this->loadData();
        unset($data['users'][$user_id]);
        return $this->saveData($data);
    }
}

// ==================== LANGUAGE FUNCTIONS ====================
class LanguageManager {
    private static $instance = null;
    private $translations = [];
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        $this->translations['en'] = [
            'welcome' => "🎬 <b>Welcome to Entertainment Tadka Bot!</b>\n\nSearch any movie by typing its name.",
            'settings' => "⚙️ Settings",
            'back' => "🔙 Back",
            'home' => "🏠 Home"
        ];
        $this->translations['hi'] = [
            'welcome' => "🎬 <b>एंटरटेनमेंट तड़का बॉट में आपका स्वागत है!</b>\n\nकोई भी मूवी का नाम लिखकर सर्च करें।",
            'settings' => "⚙️ सेटिंग्स",
            'back' => "🔙 वापस",
            'home' => "🏠 होम"
        ];
    }
    
    public function translate($key, $lang = 'en') {
        return $this->translations[$lang][$key] ?? $this->translations['en'][$key] ?? $key;
    }
}

function detectUserLanguage($text) {
    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) return 'hi';
    return 'en';
}

function getUserLanguage($user_id) {
    if (file_exists(USERS_FILE)) {
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        return $users_data['users'][$user_id]['language'] ?? 'en';
    }
    return 'en';
}

function setUserLanguage($user_id, $lang) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true) ?: ['users' => []];
    $users_data['users'][$user_id]['language'] = $lang;
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

function t($key, $user_id = null) {
    $lang = $user_id ? getUserLanguage($user_id) : 'en';
    return LanguageManager::getInstance()->translate($key, $lang);
}

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = [], $is_multipart = false) {
    RateLimiter::check('telegram_api', 30, 60);
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 30
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ];
        return @file_get_contents($url, false, stream_context_create($options));
    }
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = 'HTML') {
    $data = ['chat_id' => $chat_id, 'text' => $text];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    return apiRequest('sendMessage', $data);
}

function sendMessageWithSpoiler($chat_id, $text, $reply_markup = null, $spoiler = false) {
    if ($spoiler) $text = "|| " . $text . " ||";
    return sendMessage($chat_id, $text, $reply_markup, 'HTML');
}

function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = 'HTML') {
    $data = ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $text];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    return apiRequest('editMessageText', $data);
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = ['callback_query_id' => $callback_query_id, 'show_alert' => $show_alert];
    if ($text) $data['text'] = $text;
    return apiRequest('answerCallbackQuery', $data);
}

function sendChatAction($chat_id, $action = 'typing') {
    return apiRequest('sendChatAction', ['chat_id' => $chat_id, 'action' => $action]);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => intval($message_id)
    ]);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => intval($message_id)
    ]);
}

// ==================== CHANNEL FUNCTIONS ====================
function getChannelType($channel_id) {
    global $ENV_CONFIG;
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $c) if ($c['id'] == $channel_id) return 'public';
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $c) if ($c['id'] == $channel_id) return 'private';
    return 'unknown';
}

function getChannelUsername($channel_id) {
    global $ENV_CONFIG;
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $c) if ($c['id'] == $channel_id) return $c['username'];
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $c) if ($c['id'] == $channel_id) return $c['username'] ?: 'Private Channel';
    return 'Unknown';
}

// ==================== EXTRACTION FUNCTIONS ====================
function extract_movie_name($text) {
    $text = preg_replace('/^(?:Watch|Download|Free|Movie|Film|Full|HD|4K|1080p)\s+/i', '', $text);
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s*(?:\(?\d{4}\)?)?\s*(?:4K|1080p|720p|480p|HD)?\s*(?:Hindi|English|Tamil|Telugu)?/u', $text, $matches);
    return trim($matches[1] ?? substr($text, 0, 50));
}

function extract_quality($text) {
    $qualities = ['4K' => ['4K','2160p','UHD'], '1080p' => ['1080p','Full HD','FHD'], '720p' => ['720p','HD'], '480p' => ['480p','SD']];
    foreach ($qualities as $quality => $patterns) {
        foreach ($patterns as $pattern) if (stripos($text, $pattern) !== false) return $quality;
    }
    preg_match('/[\[\(\{]?(1080p|720p|4K|480p|2160p|HD|FHD|UHD)[\]\)\}]?/i', $text, $matches);
    if (!empty($matches[1])) {
        $match = strtoupper($matches[1]);
        if ($match == 'UHD' || $match == '2160P') return '4K';
        if ($match == 'FHD') return '1080p';
        if ($match == 'HD') return '720p';
        return $matches[1];
    }
    return 'HD';
}

function extract_language($text) {
    $languages = ['Hindi','English','Tamil','Telugu','Malayalam','Kannada','Bengali','Punjabi'];
    foreach ($languages as $lang) if (stripos($text, $lang) !== false) return $lang;
    if (stripos($text, 'Dual') !== false) return 'Dual Audio';
    return 'Unknown';
}

function extract_size($text) {
    preg_match('/(\d+\.?\d*\s*(?:GB|MB|KB))/i', $text, $matches);
    return $matches[1] ?? 'Unknown';
}

function extract_year($text) {
    preg_match('/\b(19|20)\d{2}\b/', $text, $matches);
    return $matches[0] ?? 'Unknown';
}

function is_series($text) {
    $patterns = ['/S\d{2}/i', '/Season \d+/i', '/Episodes? \d+/i'];
    foreach ($patterns as $p) if (preg_match($p, $text)) return true;
    return false;
}

function extract_season($text) {
    preg_match('/S(\d{2})|Season (\d+)/i', $text, $matches);
    if (!empty($matches[1])) return 'S' . $matches[1];
    if (!empty($matches[2])) return 'S' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    return null;
}

function extract_episode($text) {
    preg_match('/E(\d{2})|Ep (\d+)|Episode (\d+)/i', $text, $matches);
    if (!empty($matches[1])) return 'E' . $matches[1];
    if (!empty($matches[2])) return 'E' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    if (!empty($matches[3])) return 'E' . str_pad($matches[3], 2, '0', STR_PAD_LEFT);
    return null;
}

// ==================== CSV MANAGER ====================
class CSVManager {
    private static $buffer = [];
    private static $instance = null;
    private $cache_data = null;
    private $cache_timestamp = 0;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists(BACKUP_DIR)) mkdir(BACKUP_DIR, 0777, true);
        if (!file_exists(CACHE_DIR)) mkdir(CACHE_DIR, 0777, true);
        
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id,quality,language,size,year,is_series,season,episode,added_at\n";
            file_put_contents(CSV_FILE, $header);
            chmod(CSV_FILE, 0666);
        }
        register_shutdown_function([$this, 'flushBuffer']);
    }
    
    private function acquireLock($file, $mode = LOCK_EX) {
        $fp = fopen($file, 'r+');
        return ($fp && flock($fp, $mode)) ? $fp : false;
    }
    
    private function releaseLock($fp) {
        if ($fp) { flock($fp, LOCK_UN); fclose($fp); }
    }
    
    public function bufferedAppend($movie_name, $message_id, $channel_id, $extra = []) {
        if (empty(trim($movie_name))) return false;
        
        self::$buffer[] = array_merge([
            'movie_name' => trim($movie_name),
            'message_id' => intval($message_id),
            'channel_id' => $channel_id,
            'quality' => $extra['quality'] ?? extract_quality($movie_name),
            'language' => $extra['language'] ?? extract_language($movie_name),
            'size' => $extra['size'] ?? extract_size($movie_name),
            'year' => $extra['year'] ?? extract_year($movie_name),
            'is_series' => $extra['is_series'] ?? (is_series($movie_name) ? '1' : '0'),
            'season' => $extra['season'] ?? extract_season($movie_name),
            'episode' => $extra['episode'] ?? extract_episode($movie_name),
            'added_at' => date('Y-m-d H:i:s')
        ]);
        
        if (count(self::$buffer) >= CSV_BUFFER_SIZE) $this->flushBuffer();
        $this->clearCache();
        return true;
    }
    
    public function flushBuffer() {
        if (empty(self::$buffer)) return true;
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_EX);
        if (!$fp) return false;
        
        foreach (self::$buffer as $entry) {
            fputcsv($fp, [
                $entry['movie_name'], $entry['message_id'], $entry['channel_id'],
                $entry['quality'], $entry['language'], $entry['size'], $entry['year'],
                $entry['is_series'], $entry['season'], $entry['episode'], $entry['added_at']
            ]);
        }
        
        $this->releaseLock($fp);
        self::$buffer = [];
        return true;
    }
    
    public function readCSV() {
        if (!file_exists(CSV_FILE)) return [];
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_SH);
        if (!$fp) return [];
        
        $data = [];
        $header = fgetcsv($fp);
        
        while (($row = fgetcsv($fp)) !== FALSE) {
            if (count($row) >= 3) {
                $data[] = [
                    'movie_name' => $row[0],
                    'message_id' => intval($row[1]),
                    'channel_id' => $row[2],
                    'quality' => $row[3] ?? 'Unknown',
                    'language' => $row[4] ?? 'Unknown',
                    'size' => $row[5] ?? 'Unknown',
                    'year' => $row[6] ?? 'Unknown',
                    'is_series' => ($row[7] ?? '0') == '1',
                    'season' => $row[8] ?? null,
                    'episode' => $row[9] ?? null,
                    'added_at' => $row[10] ?? date('Y-m-d H:i:s')
                ];
            }
        }
        
        $this->releaseLock($fp);
        return $data;
    }
    
    public function getCachedData() {
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        
        if ($this->cache_data !== null && (time() - $this->cache_timestamp) < CACHE_EXPIRY) {
            return $this->cache_data;
        }
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_EXPIRY) {
            $cached = @unserialize(file_get_contents($cache_file));
            if ($cached !== false) {
                $this->cache_data = $cached;
                $this->cache_timestamp = filemtime($cache_file);
                return $this->cache_data;
            }
        }
        
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        file_put_contents($cache_file, serialize($this->cache_data));
        
        return $this->cache_data;
    }
    
    public function clearCache() {
        $this->cache_data = null;
        $this->cache_timestamp = 0;
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        if (file_exists($cache_file)) unlink($cache_file);
    }
    
    public function searchMovies($query, $filters = []) {
        $query = strtolower(trim($query));
        if (strlen($query) < 2) return [];
        
        $data = $this->getCachedData();
        $results = [];
        
        foreach ($data as $item) {
            if (!empty($filters['quality']) && $item['quality'] != $filters['quality']) continue;
            if (!empty($filters['language']) && $item['language'] != $filters['language']) continue;
            if (isset($filters['is_series']) && $item['is_series'] != $filters['is_series']) continue;
            
            $movie_lower = strtolower($item['movie_name']);
            $score = 0;
            
            if ($movie_lower === $query) $score = 100;
            elseif (strpos($movie_lower, $query) !== false) $score = 80;
            else {
                similar_text($movie_lower, $query, $similarity);
                if ($similarity > 60) $score = $similarity;
            }
            
            if ($score > 0) {
                $key = $item['movie_name'];
                if (!isset($results[$key])) {
                    $results[$key] = ['score' => $score, 'count' => 0, 'qualities' => [], 'languages' => [], 'items' => [], 'is_series' => $item['is_series']];
                }
                $results[$key]['count']++;
                $results[$key]['qualities'][$item['quality']] = true;
                $results[$key]['languages'][$item['language']] = true;
                $results[$key]['items'][] = $item;
            }
        }
        
        uasort($results, fn($a, $b) => $b['score'] - $a['score']);
        return array_slice($results, 0, 10);
    }
    
    public function getStats() {
        $data = $this->getCachedData();
        $stats = ['total_movies' => count($data), 'total_series' => 0, 'channels' => [], 'qualities' => [], 'languages' => [], 'last_updated' => date('Y-m-d H:i:s', $this->cache_timestamp)];
        
        foreach ($data as $item) {
            if ($item['is_series']) $stats['total_series']++;
            $stats['channels'][$item['channel_id']] = ($stats['channels'][$item['channel_id']] ?? 0) + 1;
            $stats['qualities'][$item['quality']] = ($stats['qualities'][$item['quality']] ?? 0) + 1;
            $stats['languages'][$item['language']] = ($stats['languages'][$item['language']] ?? 0) + 1;
        }
        return $stats;
    }
}

// ==================== REQUEST SYSTEM ====================
class RequestSystem {
    private static $instance = null;
    private $db_file = REQUESTS_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists($this->db_file)) {
            $default = ['requests' => [], 'last_request_id' => 0, 'user_stats' => [], 'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]];
            file_put_contents($this->db_file, json_encode($default, JSON_PRETTY_PRINT));
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->db_file), true) ?: 
               ['requests' => [], 'last_request_id' => 0, 'user_stats' => [], 'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]];
    }
    
    private function saveData($data) {
        return file_put_contents($this->db_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function submitRequest($user_id, $movie_name, $user_name = '') {
        if (strlen($movie_name) < 2) return ['success' => false, 'message' => 'Please enter a valid movie name'];
        
        $data = $this->loadData();
        $request_id = ++$data['last_request_id'];
        
        $request = [
            'id' => $request_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'movie_name' => $movie_name,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'is_notified' => false
        ];
        
        $data['requests'][$request_id] = $request;
        $data['system_stats']['total_requests']++;
        $data['system_stats']['pending']++;
        
        if (!isset($data['user_stats'][$user_id])) {
            $data['user_stats'][$user_id] = ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];
        }
        $data['user_stats'][$user_id]['total_requests']++;
        $data['user_stats'][$user_id]['pending']++;
        
        $this->saveData($data);
        return ['success' => true, 'request_id' => $request_id, 'message' => "✅ Request #$request_id submitted"];
    }
    
    public function approveRequest($request_id, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized'];
        
        $data = $this->loadData();
        if (!isset($data['requests'][$request_id])) return ['success' => false, 'message' => 'Not found'];
        
        $request = &$data['requests'][$request_id];
        if ($request['status'] != 'pending') return ['success' => false, 'message' => 'Already processed'];
        
        $request['status'] = 'approved';
        $request['approved_at'] = date('Y-m-d H:i:s');
        $request['approved_by'] = $admin_id;
        
        $data['system_stats']['approved']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['approved']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        return ['success' => true, 'request' => $request];
    }
    
    public function rejectRequest($request_id, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized'];
        
        $data = $this->loadData();
        if (!isset($data['requests'][$request_id])) return ['success' => false, 'message' => 'Not found'];
        
        $request = &$data['requests'][$request_id];
        if ($request['status'] != 'pending') return ['success' => false, 'message' => 'Already processed'];
        
        $request['status'] = 'rejected';
        $request['rejected_at'] = date('Y-m-d H:i:s');
        $request['rejected_by'] = $admin_id;
        $request['reason'] = $reason;
        
        $data['system_stats']['rejected']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['rejected']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        return ['success' => true, 'request' => $request];
    }
    
    public function getPendingRequests($limit = 50) {
        $data = $this->loadData();
        $pending = array_filter($data['requests'], fn($r) => $r['status'] == 'pending');
        usort($pending, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));
        return array_slice($pending, 0, $limit);
    }
    
    public function getUserRequests($user_id, $limit = 10) {
        $data = $this->loadData();
        $user_requests = array_filter($data['requests'], fn($r) => $r['user_id'] == $user_id);
        usort($user_requests, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return array_slice($user_requests, 0, $limit);
    }
    
    public function getStats() {
        $data = $this->loadData();
        return $data['system_stats'];
    }
}

// ==================== DELIVERY FUNCTIONS ====================
function deliver_item_to_chat($chat_id, $item) {
    $channel_type = getChannelType($item['channel_id']);
    sendChatAction($chat_id, 'typing');
    
    if ($channel_type === 'public') {
        return forwardMessage($chat_id, $item['channel_id'], $item['message_id']);
    } else {
        return copyMessage($chat_id, $item['channel_id'], $item['message_id']);
    }
}

function send_all_versions($chat_id, $items) {
    $sent = 0;
    foreach ($items as $item) {
        if (deliver_item_to_chat($chat_id, $item)) $sent++;
        usleep(300000);
    }
    sendMessage($chat_id, "✅ Sent $sent files", null, 'HTML');
}

function deliver_movie($chat_id, $movie_name, $filter_quality = null, $filter_lang = null) {
    global $csvManager;
    
    $all = $csvManager->getCachedData();
    $items = [];
    
    foreach ($all as $item) {
        if (strpos(strtolower($item['movie_name']), strtolower($movie_name)) !== false) {
            if ($filter_quality && $item['quality'] != $filter_quality) continue;
            if ($filter_lang && $item['language'] != $filter_lang) continue;
            $items[] = $item;
        }
    }
    
    if (empty($items)) {
        sendMessage($chat_id, "❌ Movie not found!", null, 'HTML');
        return;
    }
    
    if (count($items) > 1) {
        $keyboard = ['inline_keyboard' => []];
        $grouped = [];
        foreach ($items as $item) {
            $key = $item['quality'] . '_' . $item['language'];
            if (!isset($grouped[$key])) $grouped[$key] = ['quality' => $item['quality'], 'language' => $item['language'], 'items' => []];
            $grouped[$key]['items'][] = $item;
        }
        foreach ($grouped as $g) {
            $keyboard['inline_keyboard'][] = [[
                'text' => "🎥 {$g['quality']} | 🗣️ {$g['language']} (" . count($g['items']) . ")",
                'callback_data' => 'send_' . base64_encode($movie_name) . '_' . $g['quality'] . '_' . $g['language']
            ]];
        }
        $keyboard['inline_keyboard'][] = [['text' => '📤 Send All (' . count($items) . ')', 'callback_data' => 'sendall_' . base64_encode($movie_name)]];
        sendMessage($chat_id, "🎬 <b>$movie_name</b>\n\nMultiple versions found:", $keyboard, 'HTML');
    } else {
        send_all_versions($chat_id, $items);
    }
}

// ==================== SETTINGS COMMANDS ====================
function cmd_settings($chat_id, $user_id) {
    $settings = SettingsManager::getInstance()->getSettings($user_id);
    $timer_text = $settings['timer'] > 0 ? $settings['timer'] . 's' : 'OFF';
    $spoiler_text = $settings['spoiler_mode'] ? '✅ ON' : '❌ OFF';
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '⏱️ File Delete Timer: ' . $timer_text, 'callback_data' => 'menu_timer']],
            [['text' => '🎭 Spoiler Mode: ' . $spoiler_text, 'callback_data' => 'toggle_spoiler']],
            [['text' => '📡 Auto Scan: ' . ($settings['auto_scan'] ? '✅ ON' : '❌ OFF'), 'callback_data' => 'toggle_autoscan']],
            [['text' => '🔥 Top Search: ' . ($settings['top_search'] ? '✅ ON' : '❌ OFF'), 'callback_data' => 'toggle_topsearch']],
            [['text' => '📊 Priority: ' . ucfirst($settings['priority']), 'callback_data' => 'menu_priority']],
            [['text' => '🎨 Layout: ' . ucfirst($settings['layout']), 'callback_data' => 'menu_layout']],
            [['text' => '🔄 Reset All', 'callback_data' => 'reset_settings']],
            [['text' => '🔙 Back', 'callback_data' => 'back_home']]
        ]
    ];
    
    sendMessage($chat_id, "⚙️ <b>SETTINGS PANEL</b>", $keyboard, 'HTML');
}

function cmd_timer($chat_id, $user_id) {
    $settings = SettingsManager::getInstance()->getSettings($user_id);
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '⏳ 30 seconds' . ($settings['timer'] == 30 ? ' ✓' : ''), 'callback_data' => 'timer_set_30']],
            [['text' => '⏳ 60 seconds' . ($settings['timer'] == 60 ? ' ✓' : ''), 'callback_data' => 'timer_set_60']],
            [['text' => '⏳ 90 seconds' . ($settings['timer'] == 90 ? ' ✓' : ''), 'callback_data' => 'timer_set_90']],
            [['text' => '⏳ 120 seconds' . ($settings['timer'] == 120 ? ' ✓' : ''), 'callback_data' => 'timer_set_120']],
            [['text' => '🚫 Disable' . ($settings['timer'] == 0 ? ' ✓' : ''), 'callback_data' => 'timer_set_0']],
            [['text' => '🔙 Back', 'callback_data' => 'back_settings']]
        ]
    ];
    
    sendMessage($chat_id, "⏱️ <b>FILE DELETE TIMER</b>", $keyboard, 'HTML');
}

// ==================== SEARCH FUNCTIONS ====================
function advanced_search($chat_id, $query, $user_id = null, $filters = []) {
    global $csvManager;
    
    sendChatAction($chat_id, 'typing');
    
    $q = trim($query);
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters", null, 'HTML');
        return;
    }
    
    $results = $csvManager->searchMovies($q, $filters);
    
    if (empty($results)) {
        sendMessage($chat_id, "😔 No results found for '$q'", null, 'HTML');
        return;
    }
    
    $text = "🔍 <b>Found " . count($results) . " results for '$q'</b>\n\n";
    $i = 1;
    foreach ($results as $name => $data) {
        $qualities = implode('/', array_keys($data['qualities']));
        $languages = implode('/', array_keys($data['languages']));
        $type = $data['is_series'] ? '📺 Series' : '🎬 Movie';
        $text .= "$i. <b>$name</b>\n   $type | 🎥 $qualities | 🗣️ $languages | 📦 {$data['count']} files\n\n";
        $i++;
    }
    
    $keyboard = ['inline_keyboard' => []];
    $i = 1;
    foreach ($results as $name => $data) {
        $encoded = base64_encode($name);
        $keyboard['inline_keyboard'][] = [['text' => "$i. " . substr($name, 0, 30), 'callback_data' => "movie_$encoded"]];
        $i++;
        if ($i > 5) break;
    }
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
    update_stats('total_searches', 1);
}

function show_search_suggestions($chat_id, $query) {
    global $csvManager;
    
    $all = $csvManager->getCachedData();
    $suggestions = [];
    
    foreach ($all as $item) {
        similar_text(strtolower($item['movie_name']), strtolower($query), $sim);
        if ($sim > 50) $suggestions[$item['movie_name']] = $sim;
    }
    
    arsort($suggestions);
    $suggestions = array_slice(array_keys($suggestions), 0, 5);
    
    $text = "😔 No results for '$query'\n\n💡 Suggestions:\n";
    $keyboard = ['inline_keyboard' => []];
    foreach ($suggestions as $s) {
        $text .= "• $s\n";
        $keyboard['inline_keyboard'][] = [['text' => "🔍 $s", 'callback_data' => 'search_' . base64_encode($s)]];
    }
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== TOTAL UPLOADS ====================
function totalupload_controller($chat_id, $page = 1) {
    global $csvManager;
    
    sendChatAction($chat_id, 'upload_document');
    
    $all = $csvManager->getCachedData();
    if (empty($all)) {
        sendMessage($chat_id, "⚠️ No movies found.", null, 'HTML');
        return;
    }
    
    $total = count($all);
    $total_pages = ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    $page_movies = array_slice($all, $start, ITEMS_PER_PAGE);
    
    foreach ($page_movies as $movie) {
        deliver_item_to_chat($chat_id, $movie);
        usleep(500000);
    }
    
    $text = "📊 Page {$page}/{$total_pages}\nTotal: {$total} items";
    
    $keyboard = ['inline_keyboard' => []];
    $nav = [];
    if ($page > 1) $nav[] = ['text' => '◀️ Prev', 'callback_data' => 'tu_prev_' . ($page - 1)];
    $nav[] = ['text' => "📊 {$page}/{$total_pages}", 'callback_data' => 'page_info'];
    if ($page < $total_pages) $nav[] = ['text' => 'Next ▶️', 'callback_data' => 'tu_next_' . ($page + 1)];
    
    $keyboard['inline_keyboard'][] = $nav;
    $keyboard['inline_keyboard'][] = [['text' => '🏠 Home', 'callback_data' => 'back_home']];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== STATS FUNCTIONS ====================
function update_stats($field, $increment = 1) {
    $stats = file_exists(STATS_FILE) ? json_decode(file_get_contents(STATS_FILE), true) : [];
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
}

function show_stats($chat_id) {
    global $csvManager, $requestSystem;
    
    $csv_stats = $csvManager->getStats();
    $request_stats = $requestSystem->getStats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    
    $text = "📊 <b>BOT STATISTICS</b>\n\n";
    $text .= "🎬 Movies: {$csv_stats['total_movies']}\n";
    $text .= "📺 Series: {$csv_stats['total_series']}\n";
    $text .= "👥 Users: $total_users\n";
    $text .= "📋 Requests: {$request_stats['total_requests']}\n";
    $text .= "⏳ Pending: {$request_stats['pending']}\n";
    
    sendMessage($chat_id, $text, null, 'HTML');
}

// ==================== ADMIN FUNCTIONS ====================
function admin_pending_list($chat_id, $user_id, $page = 1) {
    if (!in_array($user_id, ADMIN_IDS)) return;
    
    global $requestSystem;
    
    $pending = $requestSystem->getPendingRequests(10);
    $text = "⏳ <b>PENDING REQUESTS</b>\n\n";
    $keyboard = ['inline_keyboard' => []];
    
    foreach ($pending as $req) {
        $text .= "#{$req['id']}: {$req['movie_name']}\n";
        $keyboard['inline_keyboard'][] = [
            ['text' => "✅ Approve #{$req['id']}", 'callback_data' => "admin_approve_{$req['id']}"],
            ['text' => "❌ Reject #{$req['id']}", 'callback_data' => "admin_reject_{$req['id']}"]
        ];
    }
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== IMPORT OLD ====================
function cmd_import_old($chat_id, $user_id, $parts = []) {
    if (!in_array($user_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Unauthorized", null, 'HTML');
        return;
    }
    
    $text = "📥 <b>BULK IMPORT MOVIES</b>\n\n";
    $text .= "Send a CSV file with format:\n";
    $text .= "<code>movie_name,message_id,channel_id,quality,language,size</code>\n\n";
    $text .= "Example:\n";
    $text .= "<code>KGF Chapter 2,12345,-100123456789,1080p,Hindi,2.5 GB</code>\n";
    
    sendMessage($chat_id, $text, null, 'HTML');
}

// ==================== MAINTENANCE CHECK ====================
if (MAINTENANCE_MODE) {
    $update = json_decode(file_get_contents('php://input'), true);
    if (isset($update['message'])) {
        sendMessage($update['message']['chat']['id'], "🛠️ Bot Under Maintenance", null, 'HTML');
    }
    exit;
}

// ==================== INITIALIZE ====================
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();
$settingsManager = SettingsManager::getInstance();
$autoDelete = AutoDeleteManager::getInstance();

// Auto-delete cron
static $last_auto_delete = 0;
if (time() - $last_auto_delete >= 60) {
    $autoDelete->checkAndDelete();
    $last_auto_delete = time();
}

// ==================== WEBHOOK SETUP ====================
if (isset($_GET['setup'])) {
    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    die("<h1>Webhook Set</h1><pre>$result</pre>");
}

if (isset($_GET['test'])) {
    $stats = $csvManager->getStats();
    die("<h1>Bot Running</h1><p>Movies: {$stats['total_movies']}</p>");
}

// ==================== GET UPDATE ====================
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    http_response_code(200);
    echo "OK";
    exit;
}

log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);

// ==================== PROCESS CHANNEL POSTS ====================
if (isset($update['channel_post'])) {
    $message = $update['channel_post'];
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    
    $all_channels = array_merge(
        array_column($ENV_CONFIG['PUBLIC_CHANNELS'], 'id'),
        array_column($ENV_CONFIG['PRIVATE_CHANNELS'], 'id')
    );
    
    if (in_array($chat_id, $all_channels)) {
        $text = $message['caption'] ?? $message['text'] ?? $message['document']['file_name'] ?? '';
        
        if (!empty(trim($text))) {
            $movie_name = extract_movie_name($text);
            $quality = extract_quality($text);
            $language = extract_language($text);
            
            $csvManager->bufferedAppend($movie_name, $message_id, $chat_id, [
                'quality' => $quality, 'language' => $language
            ]);
            
            log_error("Auto-saved: $movie_name", 'INFO');
        }
    }
}

// ==================== PROCESS MESSAGES ====================
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $message_id = $message['message_id'];
    $text = $message['text'] ?? '';
    
    log_error("Message from $user_id", 'INFO', ['text' => substr($text, 0, 50)]);
    
    // Update user data
    $users_data = json_decode(file_get_contents(USERS_FILE), true) ?: ['users' => []];
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'first_name' => $message['from']['first_name'] ?? '',
            'joined' => date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s'),
            'language' => detectUserLanguage($text)
        ];
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        update_stats('total_users', 1);
    } else {
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
    }
    
    // Get user settings
    $settings = $settingsManager->getSettings($user_id);
    $spoiler_mode = $settings['spoiler_mode'];
    $timer = $settings['timer'];
    
    // Process commands
    if (strpos($text, '/') === 0) {
        $parts = explode(' ', $text);
        $command = strtolower($parts[0]);
        
        if ($command == '/start' || $command == '/home') {
            $welcome = "🎬 <b>Entertainment Tadka Bot</b>\n\nSearch any movie by typing its name.";
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '⚙️ Settings', 'callback_data' => 'menu_settings']],
                    [['text' => '📊 Stats', 'callback_data' => 'show_stats']],
                    [['text' => '📝 My Requests', 'callback_data' => 'my_requests']]
                ]
            ];
            sendMessageWithSpoiler($chat_id, $welcome, $keyboard, $spoiler_mode);
        }
        
        elseif ($command == '/settings') cmd_settings($chat_id, $user_id);
        elseif ($command == '/settimer') cmd_timer($chat_id, $user_id);
        
        elseif ($command == '/request') {
            if (!isset($parts[1])) {
                sendMessageWithSpoiler($chat_id, "📝 Use: /request Movie Name", null, $spoiler_mode);
            } else {
                $movie = implode(' ', array_slice($parts, 1));
                $result = $requestSystem->submitRequest($user_id, $movie, $message['from']['first_name'] ?? '');
                sendMessageWithSpoiler($chat_id, $result['message'], null, $spoiler_mode);
            }
        }
        
        elseif ($command == '/myrequests') {
            $requests = $requestSystem->getUserRequests($user_id);
            if (empty($requests)) {
                sendMessageWithSpoiler($chat_id, "📭 No requests yet.", null, $spoiler_mode);
            } else {
                $text = "📋 <b>Your Requests</b>\n\n";
                foreach ($requests as $req) {
                    $status = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                    $text .= "$status #{$req['id']}: {$req['movie_name']}\n";
                }
                sendMessageWithSpoiler($chat_id, $text, null, $spoiler_mode);
            }
        }
        
        elseif ($command == '/pending' && in_array($user_id, ADMIN_IDS)) {
            admin_pending_list($chat_id, $user_id);
        }
        
        elseif ($command == '/stats') show_stats($chat_id);
        
        elseif ($command == '/totaluploads' || $command == '/total') {
            $page = isset($parts[1]) && is_numeric($parts[1]) ? intval($parts[1]) : 1;
            totalupload_controller($chat_id, $page);
        }
        
        elseif ($command == '/import_old' && in_array($user_id, ADMIN_IDS)) {
            cmd_import_old($chat_id, $user_id, array_slice($parts, 1));
        }
        
        else {
            sendMessageWithSpoiler($chat_id, "❌ Unknown command. Type /start", null, $spoiler_mode);
        }
    }
    
    // Normal search
    elseif (!empty(trim($text))) {
        advanced_search($chat_id, $text, $user_id);
    }
    
    // Register for auto-delete
    if ($timer > 0 && isset($message_id)) {
        $autoDelete->registerMessage($chat_id, $message_id, $user_id, $timer);
    }
}

// ==================== PROCESS CALLBACK QUERIES ====================
if (isset($update['callback_query'])) {
    $query = $update['callback_query'];
    $message = $query['message'];
    $chat_id = $message['chat']['id'];
    $data = $query['data'];
    $user_id = $query['from']['id'];
    $msg_id = $message['message_id'];
    
    log_error("Callback: $data", 'INFO', ['user' => $user_id]);
    
    $settings = $settingsManager->getSettings($user_id);
    $spoiler_mode = $settings['spoiler_mode'];
    
    // Settings menu
    if ($data == 'menu_settings') {
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Timer menu
    elseif ($data == 'menu_timer') {
        cmd_timer($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Set timer
    elseif (strpos($data, 'timer_set_') === 0) {
        $seconds = intval(str_replace('timer_set_', '', $data));
        $settingsManager->updateSettings($user_id, 'timer', $seconds);
        
        $msg = $seconds > 0 ? "✅ Timer set to {$seconds}s" : "✅ Timer disabled";
        sendMessageWithSpoiler($chat_id, $msg, null, $spoiler_mode);
        
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Toggle spoiler
    elseif ($data == 'toggle_spoiler') {
        $new_value = !$settings['spoiler_mode'];
        $settingsManager->updateSettings($user_id, 'spoiler_mode', $new_value);
        
        sendMessageWithSpoiler($chat_id, "🎭 Spoiler Mode: " . ($new_value ? 'ON' : 'OFF'), null, $new_value);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Toggle auto scan
    elseif ($data == 'toggle_autoscan') {
        $new_value = !$settings['auto_scan'];
        $settingsManager->updateSettings($user_id, 'auto_scan', $new_value);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Toggle top search
    elseif ($data == 'toggle_topsearch') {
        $new_value = !$settings['top_search'];
        $settingsManager->updateSettings($user_id, 'top_search', $new_value);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Priority menu
    elseif ($data == 'menu_priority') {
        $keyboard = ['inline_keyboard' => [
            [['text' => '🎥 Quality', 'callback_data' => 'set_priority_quality']],
            [['text' => '📊 Size', 'callback_data' => 'set_priority_size']],
            [['text' => '🔙 Back', 'callback_data' => 'back_settings']]
        ]];
        editMessageText($chat_id, $msg_id, "📊 Choose priority:", $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    
    // Set priority
    elseif ($data == 'set_priority_quality') {
        $settingsManager->updateSettings($user_id, 'priority', 'quality');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    elseif ($data == 'set_priority_size') {
        $settingsManager->updateSettings($user_id, 'priority', 'size');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Layout menu
    elseif ($data == 'menu_layout') {
        $keyboard = ['inline_keyboard' => [
            [['text' => '🔘 Buttons', 'callback_data' => 'set_layout_buttons']],
            [['text' => '📝 Text', 'callback_data' => 'set_layout_text']],
            [['text' => '🔙 Back', 'callback_data' => 'back_settings']]
        ]];
        editMessageText($chat_id, $msg_id, "🎨 Choose layout:", $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    
    // Set layout
    elseif ($data == 'set_layout_buttons') {
        $settingsManager->updateSettings($user_id, 'layout', 'buttons');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    elseif ($data == 'set_layout_text') {
        $settingsManager->updateSettings($user_id, 'layout', 'text');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Reset settings
    elseif ($data == 'reset_settings') {
        $settingsManager->resetSettings($user_id);
        sendMessageWithSpoiler($chat_id, "🔄 Settings reset to defaults", null, $spoiler_mode);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Back to settings
    elseif ($data == 'back_settings') {
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Back to home
    elseif ($data == 'back_home') {
        $welcome = "🎬 <b>Entertainment Tadka Bot</b>\n\nSearch any movie by typing its name.";
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '⚙️ Settings', 'callback_data' => 'menu_settings']],
                [['text' => '📊 Stats', 'callback_data' => 'show_stats']],
                [['text' => '📝 My Requests', 'callback_data' => 'my_requests']]
            ]
        ];
        editMessageText($chat_id, $msg_id, $welcome, $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    
    // Show stats
    elseif ($data == 'show_stats') {
        show_stats($chat_id);
        answerCallbackQuery($query['id']);
    }
    
    // My requests
    elseif ($data == 'my_requests') {
        $requests = $requestSystem->getUserRequests($user_id);
        if (empty($requests)) {
            sendMessageWithSpoiler($chat_id, "📭 No requests yet.", null, $spoiler_mode);
        } else {
            $text = "📋 <b>Your Requests</b>\n\n";
            foreach ($requests as $req) {
                $status = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                $text .= "$status #{$req['id']}: {$req['movie_name']}\n";
            }
            sendMessageWithSpoiler($chat_id, $text, null, $spoiler_mode);
        }
        answerCallbackQuery($query['id']);
    }
    
    // Movie selection
    elseif (strpos($data, 'movie_') === 0) {
        $movie = base64_decode(str_replace('movie_', '', $data));
        deliver_movie($chat_id, $movie);
        answerCallbackQuery($query['id']);
    }
    
    // Send specific version
    elseif (strpos($data, 'send_') === 0) {
        $parts = explode('_', $data, 4);
        $movie = base64_decode($parts[1]);
        $quality = $parts[2] ?? null;
        $lang = $parts[3] ?? null;
        deliver_movie($chat_id, $movie, $quality, $lang);
        answerCallbackQuery($query['id']);
    }
    
    // Send all
    elseif (strpos($data, 'sendall_') === 0) {
        $movie = base64_decode(str_replace('sendall_', '', $data));
        $all = $csvManager->getCachedData();
        $items = array_filter($all, fn($i) => strpos(strtolower($i['movie_name']), strtolower($movie)) !== false);
        send_all_versions($chat_id, $items);
        answerCallbackQuery($query['id']);
    }
    
    // Search from suggestion
    elseif (strpos($data, 'search_') === 0) {
        $query = base64_decode(str_replace('search_', '', $data));
        advanced_search($chat_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Admin approve
    elseif (strpos($data, 'admin_approve_') === 0 && in_array($user_id, ADMIN_IDS)) {
        $req_id = intval(str_replace('admin_approve_', '', $data));
        $result = $requestSystem->approveRequest($req_id, $user_id);
        
        if ($result['success']) {
            sendMessage($result['request']['user_id'], "✅ Request #$req_id approved!", null, 'HTML');
            sendMessageWithSpoiler($chat_id, "✅ Request #$req_id approved", null, $spoiler_mode);
        }
        
        admin_pending_list($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Admin reject
    elseif (strpos($data, 'admin_reject_') === 0 && in_array($user_id, ADMIN_IDS)) {
        $req_id = intval(str_replace('admin_reject_', '', $data));
        $result = $requestSystem->rejectRequest($req_id, $user_id, 'Rejected by admin');
        
        if ($result['success']) {
            sendMessage($result['request']['user_id'], "❌ Request #$req_id rejected", null, 'HTML');
            sendMessageWithSpoiler($chat_id, "❌ Request #$req_id rejected", null, $spoiler_mode);
        }
        
        admin_pending_list($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Total uploads navigation
    elseif (strpos($data, 'tu_prev_') === 0) {
        $page = intval(str_replace('tu_prev_', '', $data));
        totalupload_controller($chat_id, $page);
        answerCallbackQuery($query['id']);
    }
    
    elseif (strpos($data, 'tu_next_') === 0) {
        $page = intval(str_replace('tu_next_', '', $data));
        totalupload_controller($chat_id, $page);
        answerCallbackQuery($query['id']);
    }
    
    // Page info
    elseif ($data == 'page_info') {
        answerCallbackQuery($query['id'], "You're on this page", true);
    }
    
    // Default
    else {
        answerCallbackQuery($query['id'], "Processing...");
    }
}

http_response_code(200);
echo "OK";
?>