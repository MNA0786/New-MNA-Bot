<?php
// ==================== CONFIGURATION ====================
// Environment detection
$environment = getenv('ENVIRONMENT') ?: 'production';

// Error Reporting - Only in Development
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
    // Bot Configuration
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '8315381064:AAGk0FGVGmB8j5SjpBvW3rD3_kQHe_hyOWU',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: '@EntertainmentTadkaBot',
    
    // API Credentials
    'API_ID' => getenv('API_ID') ?: '21944581',
    'API_HASH' => getenv('API_HASH') ?: '7b1c174a5cd3466e25a976c39a791737',
    
    // Admin IDs
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    
    // Public Channels
    'PUBLIC_CHANNELS' => [
        ['id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395', 'username' => getenv('PUBLIC_CHANNEL_1_USERNAME') ?: '@EntertainmentTadka786'],
        ['id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1003614546520', 'username' => getenv('PUBLIC_CHANNEL_2_USERNAME') ?: '@Entertainment_Tadka_Serial_786'],
        ['id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002831605258', 'username' => getenv('PUBLIC_CHANNEL_3_USERNAME') ?: '@threater_print_movies'],
        ['id' => getenv('PUBLIC_CHANNEL_4_ID') ?: '-1002964109368', 'username' => getenv('PUBLIC_CHANNEL_4_USERNAME') ?: '@ETBackup']
    ],
    
    // Private Channels
    'PRIVATE_CHANNELS' => [
        ['id' => getenv('PRIVATE_CHANNEL_1_ID') ?: '-1003251791991', 'username' => getenv('PRIVATE_CHANNEL_1_USERNAME') ?: 'Private Channel 1'],
        ['id' => getenv('PRIVATE_CHANNEL_2_ID') ?: '-1002337293281', 'username' => getenv('PRIVATE_CHANNEL_2_USERNAME') ?: 'Private Channel 2']
    ],
    
    // Request Group
    'REQUEST_GROUP' => [
        'id' => getenv('REQUEST_GROUP_ID') ?: '-1003083386043',
        'username' => getenv('REQUEST_GROUP_USERNAME') ?: '@EntertainmentTadka7860'
    ],
    
    // File Paths
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'REQUESTS_FILE' => 'requests.json',
    'BACKUP_DIR' => 'backups/',
    'CACHE_DIR' => 'cache/',
    'USER_SETTINGS_FILE' => 'user_settings.json',
    'AUTO_DELETE_FILE' => 'auto_delete.json',
    'HISTORY_FILE' => 'request_history.json',
    'FAVORITES_FILE' => 'favorites.json',
    'DOWNLOAD_TRACKING_FILE' => 'download_tracking.json',
    'SERIES_DATA_FILE' => 'series_data.json',
    
    // Settings
    'CACHE_EXPIRY' => 300,
    'ITEMS_PER_PAGE' => 5,
    'CSV_BUFFER_SIZE' => 50,
    'MAX_REQUESTS_PER_DAY' => 3,
    'DUPLICATE_CHECK_HOURS' => 24,
    'REQUEST_SYSTEM_ENABLED' => true,
    
    // Security Settings
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60
];

if (empty($ENV_CONFIG['BOT_TOKEN'])) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
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
define('AUTO_DELETE_FILE', $ENV_CONFIG['AUTO_DELETE_FILE']);
define('HISTORY_FILE', $ENV_CONFIG['HISTORY_FILE']);
define('FAVORITES_FILE', $ENV_CONFIG['FAVORITES_FILE']);
define('DOWNLOAD_TRACKING_FILE', $ENV_CONFIG['DOWNLOAD_TRACKING_FILE']);
define('SERIES_DATA_FILE', $ENV_CONFIG['SERIES_DATA_FILE']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);
define('RATE_LIMIT_REQUESTS', $ENV_CONFIG['RATE_LIMIT_REQUESTS']);
define('RATE_LIMIT_WINDOW', $ENV_CONFIG['RATE_LIMIT_WINDOW']);

// Channel constants
define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('SERIAL_CHANNEL', '@Entertainment_Tadka_Serial_786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('BACKUP_CHANNEL', '@ETBackup');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');

// ==================== SECURITY FUNCTIONS ====================
function validateInput($input, $type = 'text') {
    if (is_array($input)) {
        return array_map('validateInput', $input);
    }
    
    $input = trim($input);
    
    switch($type) {
        case 'movie_name':
            if (strlen($input) < 2 || strlen($input) > 200) {
                return false;
            }
            if (!preg_match('/^[\p{L}\p{N}\s\-\.\,\&\+\'\"\(\)\!\:\;\?]{2,200}$/u', $input)) {
                return false;
            }
            return $input;
            
        case 'user_id':
            return preg_match('/^\d+$/', $input) ? intval($input) : false;
            
        case 'command':
            return preg_match('/^\/[a-zA-Z0-9_]+$/', $input) ? $input : false;
            
        case 'telegram_id':
            return preg_match('/^\-?\d+$/', $input) ? $input : false;
            
        case 'filename':
            $input = basename($input);
            $allowed_files = ['movies.csv', 'users.json', 'bot_stats.json', 'requests.json', 'user_settings.json', 'auto_delete.json', 'request_history.json', 'favorites.json', 'download_tracking.json', 'series_data.json'];
            return in_array($input, $allowed_files) ? $input : false;
            
        default:
            return $input;
    }
}

function secureFileOperation($filename, $operation = 'read') {
    $filename = validateInput($filename, 'filename');
    if (!$filename) {
        return false;
    }
    
    if ($operation === 'write') {
        if (!is_writable($filename)) {
            @chmod($filename, 0644);
        }
    }
    
    return $filename;
}

// ==================== RATE LIMITING ====================
class RateLimiter {
    private static $limits = [];
    
    public static function check($key, $limit = 30, $window = 60) {
        $now = time();
        $window_start = $now - $window;
        
        if (!isset(self::$limits[$key])) {
            self::$limits[$key] = [];
        }
        
        self::$limits[$key] = array_filter(self::$limits[$key], function($time) use ($window_start) {
            return $time > $window_start;
        });
        
        if (count(self::$limits[$key]) >= $limit) {
            log_error("Rate limit exceeded for key: $key", 'WARNING');
            return false;
        }
        
        self::$limits[$key][] = $now;
        return true;
    }
}

// ==================== AUTO-DELETE MANAGER ====================
class AutoDeleteManager {
    private static $instance = null;
    private $db_file = AUTO_DELETE_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initialize();
    }
    
    private function initialize() {
        if (!file_exists($this->db_file)) {
            $default = [
                'messages' => [],
                'stats' => [
                    'total_deleted' => 0,
                    'last_cleanup' => date('Y-m-d H:i:s')
                ]
            ];
            file_put_contents($this->db_file, json_encode($default, JSON_PRETTY_PRINT));
            chmod($this->db_file, 0666);
        }
    }
    
    private function loadData() {
        $data = json_decode(file_get_contents($this->db_file), true);
        return $data ?: ['messages' => [], 'stats' => ['total_deleted' => 0, 'last_cleanup' => date('Y-m-d H:i:s')]];
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
        log_error("Message registered for auto-delete", 'INFO', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'delete_in' => $timer_seconds . ' seconds'
        ]);
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
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $warning,
            'reply_to_message_id' => $message_id,
            'parse_mode' => 'HTML'
        ]);
    }
    
    private function deleteMessage($chat_id, $message_id) {
        return apiRequest('deleteMessage', [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ]);
    }
    
    public function getStats() {
        $data = $this->loadData();
        $now = time();
        
        $pending = array_filter($data['messages'], function($msg) use ($now) {
            return $msg['delete_at'] > $now;
        });
        
        return [
            'total_deleted' => $data['stats']['total_deleted'],
            'pending_count' => count($pending),
            'last_cleanup' => $data['stats']['last_cleanup']
        ];
    }
}

// ==================== SETTINGS MANAGER ====================
class SettingsManager {
    private static $instance = null;
    private $settings_file = USER_SETTINGS_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initialize();
    }
    
    private function initialize() {
        if (!file_exists($this->settings_file)) {
            $default = ['users' => []];
            file_put_contents($this->settings_file, json_encode($default, JSON_PRETTY_PRINT));
            chmod($this->settings_file, 0666);
        }
    }
    
    private function loadData() {
        $data = json_decode(file_get_contents($this->settings_file), true);
        return $data ?: ['users' => []];
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
        
        if (!isset($data['users'][$user_id])) {
            return $defaults;
        }
        
        return array_merge($defaults, $data['users'][$user_id]);
    }
    
    public function updateSettings($user_id, $key, $value) {
        $data = $this->loadData();
        
        if (!isset($data['users'][$user_id])) {
            $data['users'][$user_id] = [];
        }
        
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
function detectUserLanguage($text) {
    $hindi_pattern = '/[\x{0900}-\x{097F}]/u';
    if (preg_match($hindi_pattern, $text)) return 'hi';
    
    $hinglish_words = ['hai', 'hain', 'ka', 'ki', 'mein', 'se', 'ko', 'aur', 'kya'];
    $words = explode(' ', strtolower($text));
    $hinglish_count = count(array_intersect($words, $hinglish_words));
    
    return $hinglish_count >= 2 ? 'hi' : 'en';
}

function getUserLanguage($user_id) {
    if (file_exists(USERS_FILE)) {
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (isset($users_data['users'][$user_id]['language'])) {
            return $users_data['users'][$user_id]['language'];
        }
    }
    return 'en';
}

function setUserLanguage($user_id, $lang) {
    if (!file_exists(USERS_FILE)) return;
    $users_data = json_decode(file_get_contents(USERS_FILE), true) ?: ['users' => []];
    $users_data['users'][$user_id]['language'] = $lang;
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

// ==================== DOWNLOAD TRACKER ====================
class DownloadTracker {
    private static $instance = null;
    private $tracking_file = DOWNLOAD_TRACKING_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initialize();
    }
    
    private function initialize() {
        if (!file_exists($this->tracking_file)) {
            $data = [
                'movies' => [],
                'users' => [],
                'daily' => [],
                'total_downloads' => 0
            ];
            file_put_contents($this->tracking_file, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->tracking_file), true) ?: [
            'movies' => [], 'users' => [], 'daily' => [], 'total_downloads' => 0
        ];
    }
    
    private function saveData($data) {
        return file_put_contents($this->tracking_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function trackDownload($user_id, $movie_name, $quality = null, $is_series = false) {
        $data = $this->loadData();
        $today = date('Y-m-d');
        
        if (!isset($data['movies'][$movie_name])) {
            $data['movies'][$movie_name] = [
                'count' => 0,
                'users' => [],
                'last_download' => $today,
                'qualities' => [],
                'is_series' => $is_series
            ];
        }
        $data['movies'][$movie_name]['count']++;
        $data['movies'][$movie_name]['last_download'] = $today;
        $data['movies'][$movie_name]['users'][$user_id] = time();
        if ($quality && !in_array($quality, $data['movies'][$movie_name]['qualities'])) {
            $data['movies'][$movie_name]['qualities'][] = $quality;
        }
        
        if (!isset($data['users'][$user_id])) {
            $data['users'][$user_id] = [
                'total' => 0,
                'movies' => [],
                'last_active' => $today
            ];
        }
        $data['users'][$user_id]['total']++;
        $data['users'][$user_id]['movies'][$movie_name] = time();
        $data['users'][$user_id]['last_active'] = $today;
        
        if (!isset($data['daily'][$today])) $data['daily'][$today] = 0;
        $data['daily'][$today]++;
        $data['total_downloads']++;
        
        $this->saveData($data);
    }
    
    public function getPopularMovies($limit = 10, $period = 'all') {
        $data = $this->loadData();
        $movies = $data['movies'];
        
        if ($period == 'week') {
            $week_ago = strtotime('-7 days');
            $movies = array_filter($movies, function($m) use ($week_ago) {
                return strtotime($m['last_download']) > $week_ago;
            });
        } elseif ($period == 'month') {
            $month_ago = strtotime('-30 days');
            $movies = array_filter($movies, function($m) use ($month_ago) {
                return strtotime($m['last_download']) > $month_ago;
            });
        }
        
        uasort($movies, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_slice($movies, 0, $limit, true);
    }
}

// ==================== RECOMMENDATION ENGINE ====================
class RecommendationEngine {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function findSimilar($movie_name, $limit = 5) {
        global $csvManager;
        $all = $csvManager->getCachedData();
        $movie_lower = strtolower($movie_name);
        $keywords = $this->extractKeywords($movie_name);
        $scores = [];
        $seen = [];
        
        foreach ($all as $item) {
            $name = $item['movie_name'];
            if (strtolower($name) == $movie_lower || isset($seen[$name])) continue;
            
            $score = 0;
            if (stripos($name, $keywords['base']) !== false) $score += 50;
            if ($item['year'] != 'Unknown' && $keywords['year'] && $item['year'] == $keywords['year']) $score += 30;
            if ($keywords['quality'] && $item['quality'] == $keywords['quality']) $score += 20;
            if ($keywords['language'] && $item['language'] == $keywords['language']) $score += 20;
            
            similar_text(strtolower($name), $movie_lower, $similarity);
            $score += $similarity;
            
            if ($score > 30) {
                $scores[$name] = $score;
                $seen[$name] = true;
            }
        }
        
        arsort($scores);
        return array_slice(array_keys($scores), 0, $limit);
    }
    
    private function extractKeywords($movie_name) {
        $keywords = [
            'base' => $movie_name,
            'year' => null,
            'quality' => null,
            'language' => null,
            'is_series' => false
        ];
        
        preg_match('/\b(19|20)\d{2}\b/', $movie_name, $matches);
        if (!empty($matches)) {
            $keywords['year'] = $matches[0];
            $keywords['base'] = str_replace($matches[0], '', $keywords['base']);
        }
        
        $qualities = ['4K', '1080p', '720p', '480p'];
        foreach ($qualities as $q) {
            if (stripos($movie_name, $q) !== false) {
                $keywords['quality'] = $q;
                $keywords['base'] = str_ireplace($q, '', $keywords['base']);
                break;
            }
        }
        
        $languages = ['Hindi', 'English', 'Tamil', 'Telugu'];
        foreach ($languages as $lang) {
            if (stripos($movie_name, $lang) !== false) {
                $keywords['language'] = $lang;
                $keywords['base'] = str_ireplace($lang, '', $keywords['base']);
                break;
            }
        }
        
        $keywords['base'] = trim($keywords['base']);
        $keywords['is_series'] = is_series($movie_name);
        return $keywords;
    }
    
    public function getPersonalizedRecommendations($user_id, $limit = 5) {
        global $csvManager;
        $history = RequestHistory::getInstance()->getUserHistory($user_id, 20);
        
        if (empty($history)) {
            return $this->getPopularMovies($limit);
        }
        
        $preferences = ['languages' => [], 'qualities' => [], 'series' => false];
        foreach ($history as $req) {
            if ($req['status'] == 'approved') {
                $keywords = $this->extractKeywords($req['movie_name']);
                if ($keywords['language']) $preferences['languages'][$keywords['language']] = true;
                if ($keywords['quality']) $preferences['qualities'][$keywords['quality']] = true;
                if ($keywords['is_series']) $preferences['series'] = true;
            }
        }
        
        $all = $csvManager->getCachedData();
        $scores = [];
        $seen = [];
        
        foreach ($all as $item) {
            $name = $item['movie_name'];
            if (isset($seen[$name])) continue;
            
            $score = 0;
            if (isset($preferences['languages'][$item['language']])) $score += 30;
            if (isset($preferences['qualities'][$item['quality']])) $score += 20;
            if ($preferences['series'] && $item['is_series']) $score += 40;
            $score += min(20, count($item['items'] ?? []) * 2);
            
            if ($score > 20) {
                $scores[$name] = $score;
                $seen[$name] = true;
            }
        }
        
        arsort($scores);
        return array_slice(array_keys($scores), 0, $limit);
    }
    
    public function getPopularMovies($limit = 5) {
        global $csvManager;
        $all = $csvManager->getCachedData();
        $popularity = [];
        foreach ($all as $item) {
            $name = $item['movie_name'];
            $popularity[$name] = ($popularity[$name] ?? 0) + 1;
        }
        arsort($popularity);
        return array_slice(array_keys($popularity), 0, $limit);
    }
}

// ==================== FAVORITES MANAGER ====================
class FavoritesManager {
    private static $instance = null;
    private $favorites_file = FAVORITES_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists($this->favorites_file)) {
            file_put_contents($this->favorites_file, json_encode(['favorites' => []], JSON_PRETTY_PRINT));
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->favorites_file), true) ?: ['favorites' => []];
    }
    
    private function saveData($data) {
        return file_put_contents($this->favorites_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function addFavorite($user_id, $movie_name) {
        $data = $this->loadData();
        if (!isset($data['favorites'][$user_id])) $data['favorites'][$user_id] = [];
        if (!in_array($movie_name, $data['favorites'][$user_id])) {
            $data['favorites'][$user_id][] = $movie_name;
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function removeFavorite($user_id, $movie_name) {
        $data = $this->loadData();
        if (isset($data['favorites'][$user_id])) {
            $data['favorites'][$user_id] = array_values(array_filter($data['favorites'][$user_id], function($m) use ($movie_name) {
                return $m != $movie_name;
            }));
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function getFavorites($user_id) {
        $data = $this->loadData();
        return $data['favorites'][$user_id] ?? [];
    }
}

// ==================== REQUEST HISTORY ====================
class RequestHistory {
    private static $instance = null;
    private $history_file = HISTORY_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists($this->history_file)) {
            file_put_contents($this->history_file, json_encode(['history' => []], JSON_PRETTY_PRINT));
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->history_file), true) ?: ['history' => []];
    }
    
    private function saveData($data) {
        return file_put_contents($this->history_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function addToHistory($user_id, $request) {
        $data = $this->loadData();
        $entry = [
            'id' => $request['id'],
            'user_id' => $user_id,
            'movie_name' => $request['movie_name'],
            'status' => $request['status'],
            'created_at' => $request['created_at'],
            'updated_at' => $request['updated_at']
        ];
        
        if (!isset($data['history'][$user_id])) $data['history'][$user_id] = [];
        array_unshift($data['history'][$user_id], $entry);
        $data['history'][$user_id] = array_slice($data['history'][$user_id], 0, 50);
        $this->saveData($data);
    }
    
    public function getUserHistory($user_id, $limit = 20) {
        $data = $this->loadData();
        return array_slice($data['history'][$user_id] ?? [], 0, $limit);
    }
}

// ==================== SERIES MANAGER ====================
class SeriesManager {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getSeasons($series_name) {
        global $csvManager;
        $all = $csvManager->getCachedData();
        $seasons = [];
        
        foreach ($all as $item) {
            if ($item['is_series'] && stripos($item['movie_name'], $series_name) !== false && $item['season']) {
                $seasons[$item['season']] = [
                    'season' => $item['season'],
                    'episodes' => [],
                    'total_episodes' => 0,
                    'qualities' => [],
                    'languages' => []
                ];
            }
        }
        
        foreach (array_keys($seasons) as $season) {
            foreach ($all as $item) {
                if ($item['is_series'] && stripos($item['movie_name'], $series_name) !== false && 
                    $item['season'] == $season && $item['episode']) {
                    $seasons[$season]['episodes'][$item['episode']] = true;
                    $seasons[$season]['total_episodes']++;
                    $seasons[$season]['qualities'][$item['quality']] = true;
                    $seasons[$season]['languages'][$item['language']] = true;
                }
            }
            $seasons[$season]['episodes'] = array_keys($seasons[$season]['episodes']);
            sort($seasons[$season]['episodes']);
        }
        
        ksort($seasons);
        return $seasons;
    }
    
    public function getEpisodes($series_name, $season) {
        global $csvManager;
        $all = $csvManager->getCachedData();
        $episodes = [];
        
        foreach ($all as $item) {
            if ($item['is_series'] && stripos($item['movie_name'], $series_name) !== false && 
                $item['season'] == $season && $item['episode']) {
                
                $ep = $item['episode'];
                if (!isset($episodes[$ep])) {
                    $episodes[$ep] = [
                        'episode' => $ep,
                        'count' => 0,
                        'qualities' => [],
                        'languages' => [],
                        'items' => []
                    ];
                }
                $episodes[$ep]['count']++;
                $episodes[$ep]['qualities'][$item['quality']] = true;
                $episodes[$ep]['languages'][$item['language']] = true;
                $episodes[$ep]['items'][] = $item;
            }
        }
        
        ksort($episodes);
        return $episodes;
    }
}

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = [], $is_multipart = false) {
    RateLimiter::check('telegram_api', RATE_LIMIT_REQUESTS, RATE_LIMIT_WINDOW);
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    log_error("API Request: $method", 'DEBUG', $params);
    
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
        if ($res === false) {
            log_error("CURL ERROR: " . curl_error($ch), 'ERROR');
        }
        curl_close($ch);
        return $res;
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            log_error("apiRequest failed for method $method: " . ($error['message'] ?? 'Unknown error'), 'ERROR');
        }
        return $result;
    }
}

function sendChatAction($chat_id, $action = 'typing') {
    return apiRequest('sendChatAction', [
        'chat_id' => $chat_id,
        'action' => $action
    ]);
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    log_error("Sending message to $chat_id", 'INFO', ['text_length' => strlen($text)]);
    
    return apiRequest('sendMessage', $data);
}

function sendMessageWithSpoiler($chat_id, $text, $reply_markup = null, $spoiler = false) {
    if ($spoiler) {
        $text = "|| " . $text . " ||";
    }
    return sendMessage($chat_id, $text, $reply_markup, 'HTML');
}

function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text
    ];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    log_error("Editing message $message_id for $chat_id", 'INFO');
    
    return apiRequest('editMessageText', $data);
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = [
        'callback_query_id' => $callback_query_id,
        'show_alert' => $show_alert
    ];
    if ($text) $data['text'] = validateInput($text, 'text');
    return apiRequest('answerCallbackQuery', $data);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => validateInput($from_chat_id, 'telegram_id'),
        'message_id' => intval($message_id)
    ]);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => validateInput($from_chat_id, 'telegram_id'),
        'message_id' => intval($message_id)
    ]);
}

// ==================== CHANNEL FUNCTIONS ====================
function getChannelType($channel_id) {
    global $ENV_CONFIG;
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $c) {
        if ($c['id'] == $channel_id) return 'public';
    }
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $c) {
        if ($c['id'] == $channel_id) return 'private';
    }
    return 'unknown';
}

function getChannelUsername($channel_id) {
    global $ENV_CONFIG;
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $c) {
        if ($c['id'] == $channel_id) return $c['username'];
    }
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $c) {
        if ($c['id'] == $channel_id) return $c['username'] ?: 'Private Channel';
    }
    return 'Unknown';
}

// ==================== EXTRACTION FUNCTIONS ====================
function extract_movie_name($text) {
    $text = preg_replace('/^(?:Watch|Download|Free|Movie|Film|Full|HD|4K|1080p)\s+/i', '', $text);
    
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s*(?:\(?\d{4}\)?)?\s*(?:4K|1080p|720p|480p|HD)?\s*(?:Hindi|English|Tamil|Telugu)?/u', $text, $matches);
    if (!empty($matches[1])) return trim($matches[1]);
    
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s*(?:\{?\d{4}\}?)?\s*(?:\[?4K|1080p|720p|480p|HD\]?)?/u', $text, $matches);
    if (!empty($matches[1])) return trim($matches[1]);
    
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s+S\d{2}/u', $text, $matches);
    if (!empty($matches[1])) return trim($matches[1]);
    
    return substr(trim($text), 0, 100);
}

function extract_quality($text) {
    $qualities = [
        '4K' => ['4K', '2160p', 'UHD'],
        '1080p' => ['1080p', 'Full HD', 'FHD'],
        '720p' => ['720p', 'HD'],
        '480p' => ['480p', 'SD']
    ];
    
    foreach ($qualities as $quality => $patterns) {
        foreach ($patterns as $pattern) {
            if (stripos($text, $pattern) !== false) return $quality;
        }
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
    $languages = ['Hindi', 'English', 'Tamil', 'Telugu', 'Malayalam', 'Kannada', 'Bengali', 'Punjabi'];
    foreach ($languages as $lang) {
        if (stripos($text, $lang) !== false) return $lang;
    }
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
    $patterns = ['/S\d{2}/i', '/Season \d+/i', '/Episodes? \d+/i', '/Complete Series/i', '/Web Series/i', '/TV Series/i'];
    foreach ($patterns as $p) {
        if (preg_match($p, $text)) return true;
    }
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
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeFiles();
        register_shutdown_function([$this, 'flushBuffer']);
    }
    
    private function initializeFiles() {
        if (!file_exists(BACKUP_DIR)) mkdir(BACKUP_DIR, 0777, true);
        if (!file_exists(CACHE_DIR)) mkdir(CACHE_DIR, 0777, true);
        
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id,quality,language,size,year,is_series,season,episode,added_at\n";
            file_put_contents(CSV_FILE, $header);
            chmod(CSV_FILE, 0666);
        }
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
            if (!empty($filters['season']) && $item['season'] != $filters['season']) continue;
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
                $key = $item['is_series'] ? $item['movie_name'] . '_' . ($item['season'] ?? '') : $item['movie_name'];
                if (!isset($results[$key])) {
                    $results[$key] = [
                        'score' => $score,
                        'count' => 0,
                        'qualities' => [],
                        'languages' => [],
                        'items' => [],
                        'is_series' => $item['is_series'],
                        'seasons' => []
                    ];
                }
                $results[$key]['count']++;
                $results[$key]['qualities'][$item['quality']] = true;
                $results[$key]['languages'][$item['language']] = true;
                if ($item['is_series'] && $item['season']) $results[$key]['seasons'][$item['season']] = true;
                $results[$key]['items'][] = $item;
            }
        }
        
        uasort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($results, 0, 10);
    }
    
    public function getStats() {
        $data = $this->getCachedData();
        $stats = [
            'total_movies' => count($data),
            'total_series' => 0,
            'channels' => [],
            'qualities' => [],
            'languages' => [],
            'last_updated' => date('Y-m-d H:i:s', $this->cache_timestamp)
        ];
        
        foreach ($data as $item) {
            if ($item['is_series']) $stats['total_series']++;
            $stats['channels'][$item['channel_id']] = ($stats['channels'][$item['channel_id']] ?? 0) + 1;
            $stats['qualities'][$item['quality']] = ($stats['qualities'][$item['quality']] ?? 0) + 1;
            $stats['languages'][$item['language']] = ($stats['languages'][$item['language']] ?? 0) + 1;
        }
        
        return $stats;
    }
    
    public function getTrending($limit = 10) {
        $data = $this->getCachedData();
        usort($data, function($a, $b) {
            return strtotime($b['added_at']) - strtotime($a['added_at']);
        });
        return array_slice($data, 0, $limit);
    }
}

// ==================== REQUEST SYSTEM ====================
class RequestSystem {
    private static $instance = null;
    private $db_file = REQUESTS_FILE;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        if (!file_exists($this->db_file)) {
            $default_data = [
                'requests' => [],
                'last_request_id' => 0,
                'user_stats' => [],
                'system_stats' => [
                    'total_requests' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'pending' => 0
                ]
            ];
            file_put_contents($this->db_file, json_encode($default_data, JSON_PRETTY_PRINT));
            chmod($this->db_file, 0666);
            log_error("Requests database created", 'INFO');
        }
    }
    
    private function loadData() {
        $data = json_decode(file_get_contents($this->db_file), true);
        if (!$data) {
            $data = [
                'requests' => [],
                'last_request_id' => 0,
                'user_stats' => [],
                'system_stats' => [
                    'total_requests' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'pending' => 0
                ]
            ];
        }
        return $data;
    }
    
    private function saveData($data) {
        $result = file_put_contents($this->db_file, json_encode($data, JSON_PRETTY_PRINT));
        if ($result === false) {
            log_error("Failed to save requests database", 'ERROR');
            return false;
        }
        return true;
    }
    
    public function submitRequest($user_id, $movie_name, $user_name = '') {
        $movie_name = validateInput($movie_name, 'movie_name');
        $user_id = validateInput($user_id, 'user_id');
        
        if (!$movie_name || !$user_id || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        $data = $this->loadData();
        $request_id = ++$data['last_request_id'];
        
        $request = [
            'id' => $request_id,
            'user_id' => $user_id,
            'user_name' => validateInput($user_name),
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
            $data['user_stats'][$user_id] = [
                'total_requests' => 0,
                'approved' => 0,
                'rejected' => 0,
                'pending' => 0
            ];
        }
        $data['user_stats'][$user_id]['total_requests']++;
        $data['user_stats'][$user_id]['pending']++;
        
        $this->saveData($data);
        
        RequestHistory::getInstance()->addToHistory($user_id, $request);
        
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
        RequestHistory::getInstance()->addToHistory($user_id, $request);
        
        return ['success' => true, 'request' => $request];
    }
    
    public function rejectRequest($request_id, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized'];
        
        $data = $this->loadData();
        if (!isset($data['requests'][$request_id])) return ['success' => false, 'message' => 'Not found'];
        
        $request = &$data['requests'][$request_id];
        if ($request['status'] != 'pending') return ['success' => false, 'message' => 'Already processed'];
        
        $reason = validateInput($reason);
        
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
        RequestHistory::getInstance()->addToHistory($user_id, $request);
        
        return ['success' => true, 'request' => $request];
    }
    
    public function getPendingRequests($limit = 50) {
        $data = $this->loadData();
        $pending = array_filter($data['requests'], function($r) {
            return $r['status'] == 'pending';
        });
        usort($pending, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        return array_slice($pending, 0, $limit);
    }
    
    public function getUserRequests($user_id, $limit = 10) {
        $data = $this->loadData();
        $user_requests = array_filter($data['requests'], function($r) use ($user_id) {
            return $r['user_id'] == $user_id;
        });
        usort($user_requests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        return array_slice($user_requests, 0, $limit);
    }
    
    public function getRequest($request_id) {
        $data = $this->loadData();
        return $data['requests'][$request_id] ?? null;
    }
    
    public function getStats() {
        $data = $this->loadData();
        return $data['system_stats'];
    }
    
    public function checkAutoApprove($movie_name) {
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $approved = [];
        
        foreach ($data['requests'] as $id => &$request) {
            if ($request['status'] == 'pending' && stripos($movie_lower, strtolower($request['movie_name'])) !== false) {
                $request['status'] = 'approved';
                $request['approved_at'] = date('Y-m-d H:i:s');
                $request['approved_by'] = 'system';
                $data['system_stats']['approved']++;
                $data['system_stats']['pending']--;
                $approved[] = $id;
                RequestHistory::getInstance()->addToHistory($request['user_id'], $request);
            }
        }
        
        if (!empty($approved)) $this->saveData($data);
        return $approved;
    }
}

// ==================== DELIVERY FUNCTIONS ====================
function deliver_item_to_chat($chat_id, $item, $user_id = null) {
    $channel_id = $item['channel_id'];
    $message_id = $item['message_id'];
    $channel_type = getChannelType($channel_id);
    
    sendChatAction($chat_id, 'typing');
    
    $result = false;
    if ($channel_type === 'public') {
        $result = forwardMessage($chat_id, $channel_id, $message_id);
    } elseif ($channel_type === 'private') {
        $result = copyMessage($chat_id, $channel_id, $message_id);
    }
    
    if ($result && $user_id) {
        DownloadTracker::getInstance()->trackDownload($user_id, $item['movie_name'], $item['quality'], $item['is_series']);
    }
    
    return $result !== false;
}

function send_all_versions($chat_id, $items, $user_id = null) {
    $sent = 0;
    foreach ($items as $item) {
        if (deliver_item_to_chat($chat_id, $item, $user_id)) {
            $sent++;
            usleep(300000);
        }
    }
    sendMessage($chat_id, "✅ Sent $sent files", null, 'HTML');
}

function deliver_movie($chat_id, $movie_name, $user_id = null, $filter_quality = null, $filter_lang = null) {
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
    
    $grouped = [];
    foreach ($items as $item) {
        $key = $item['quality'] . '_' . $item['language'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'quality' => $item['quality'],
                'language' => $item['language'],
                'items' => []
            ];
        }
        $grouped[$key]['items'][] = $item;
    }
    
    if (count($grouped) > 1) {
        $keyboard = ['inline_keyboard' => []];
        foreach ($grouped as $g) {
            $keyboard['inline_keyboard'][] = [[
                'text' => "🎥 {$g['quality']} | 🗣️ {$g['language']} (" . count($g['items']) . ")",
                'callback_data' => 'send_' . base64_encode($movie_name) . '_' . $g['quality'] . '_' . $g['language']
            ]];
        }
        $keyboard['inline_keyboard'][] = [
            ['text' => '📤 Send All (' . count($items) . ')', 'callback_data' => 'sendall_' . base64_encode($movie_name)]
        ];
        
        sendMessage($chat_id, "🎬 <b>$movie_name</b>\n\nMultiple versions found:", $keyboard, 'HTML');
    } else {
        send_all_versions($chat_id, $items, $user_id);
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
        show_search_suggestions($chat_id, $q, $user_id);
        return;
    }
    
    $total_items = array_sum(array_column($results, 'count'));
    
    $text = "🔍 <b>Found " . count($results) . " results for '$q'</b> ($total_items files)\n\n";
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
        $keyboard['inline_keyboard'][] = [
            ['text' => "$i. " . substr($name, 0, 30), 'callback_data' => "movie_$encoded"]
        ];
        $i++;
        if ($i > 5) break;
    }
    
    $filter_row = [];
    $filter_row[] = ['text' => '🎥 Quality', 'callback_data' => 'menu_quality_' . base64_encode($q)];
    $filter_row[] = ['text' => '🗣️ Language', 'callback_data' => 'menu_language_' . base64_encode($q)];
    $filter_row[] = ['text' => '📺 Series', 'callback_data' => 'filter_series_' . base64_encode($q)];
    
    $keyboard['inline_keyboard'][] = $filter_row;
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
    update_stats('total_searches', 1);
}

function show_search_suggestions($chat_id, $query, $user_id = null) {
    global $csvManager;
    
    $all = $csvManager->getCachedData();
    $suggestions = [];
    $query_lower = strtolower($query);
    
    foreach ($all as $item) {
        $name_lower = strtolower($item['movie_name']);
        
        if (strpos($name_lower, $query_lower) !== false) {
            $suggestions[$item['movie_name']] = 100;
        } else {
            similar_text($name_lower, $query_lower, $similarity);
            if ($similarity > 50) $suggestions[$item['movie_name']] = $similarity;
        }
    }
    
    arsort($suggestions);
    $suggestions = array_slice(array_keys($suggestions), 0, 5);
    
    if (empty($suggestions)) {
        $popular = array_slice($all, 0, 5);
        foreach ($popular as $item) {
            $suggestions[] = $item['movie_name'];
        }
        $suggestions = array_unique($suggestions);
    }
    
    $text = "😔 <b>No results for '$query'</b>\n\n💡 Suggestions:\n";
    $keyboard = ['inline_keyboard' => []];
    foreach ($suggestions as $s) {
        $text .= "• $s\n";
        $keyboard['inline_keyboard'][] = [['text' => "🔍 $s", 'callback_data' => 'search_' . base64_encode($s)]];
    }
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function show_quality_filter_menu($chat_id, $msg_id, $query, $user_id = null) {
    $qualities = ['4K', '1080p', '720p', '480p'];
    $keyboard = ['inline_keyboard' => []];
    $row = [];
    
    foreach ($qualities as $quality) {
        $row[] = ['text' => "🎥 $quality", 'callback_data' => 'filter_quality_' . $quality . '_' . base64_encode($query)];
        if (count($row) == 2) {
            $keyboard['inline_keyboard'][] = $row;
            $row = [];
        }
    }
    if (!empty($row)) $keyboard['inline_keyboard'][] = $row;
    
    $keyboard['inline_keyboard'][] = [['text' => '🔙 Back', 'callback_data' => 'search_' . base64_encode($query)]];
    
    editMessageText($chat_id, $msg_id, "🎥 <b>SELECT QUALITY</b>\n\nChoose quality for: $query", $keyboard, 'HTML');
}

function show_language_filter_menu($chat_id, $msg_id, $query, $user_id = null) {
    $languages = ['Hindi', 'English', 'Tamil', 'Telugu'];
    $keyboard = ['inline_keyboard' => []];
    $row = [];
    
    foreach ($languages as $lang) {
        $row[] = ['text' => "🗣️ $lang", 'callback_data' => 'filter_lang_' . $lang . '_' . base64_encode($query)];
        if (count($row) == 2) {
            $keyboard['inline_keyboard'][] = $row;
            $row = [];
        }
    }
    if (!empty($row)) $keyboard['inline_keyboard'][] = $row;
    
    $keyboard['inline_keyboard'][] = [['text' => '🔙 Back', 'callback_data' => 'search_' . base64_encode($query)]];
    
    editMessageText($chat_id, $msg_id, "🗣️ <b>SELECT LANGUAGE</b>\n\nChoose language for: $query", $keyboard, 'HTML');
}

// ==================== TOTAL UPLOADS ====================
function totalupload_controller($chat_id, $page = 1, $user_id = null) {
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
    
    $grouped = [];
    foreach ($page_movies as $movie) {
        $name = $movie['movie_name'];
        if (!isset($grouped[$name])) {
            $grouped[$name] = [
                'name' => $name,
                'count' => 0,
                'qualities' => [],
                'languages' => [],
                'items' => []
            ];
        }
        $grouped[$name]['count']++;
        $grouped[$name]['qualities'][$movie['quality']] = true;
        $grouped[$name]['languages'][$movie['language']] = true;
        $grouped[$name]['items'][] = $movie;
    }
    
    foreach ($grouped as $group) {
        $first_item = $group['items'][0];
        deliver_item_to_chat($chat_id, $first_item, $user_id);
        usleep(500000);
    }
    
    $text = "📊 <b>TOTAL UPLOADS</b>\n\n";
    $text .= "📁 Page: {$page}/{$total_pages}\n";
    $text .= "🎬 Showing: " . count($grouped) . " movies\n";
    $text .= "📦 Total items: {$total}\n\n";
    
    $i = ($page - 1) * ITEMS_PER_PAGE + 1;
    foreach ($grouped as $group) {
        $qualities = implode('/', array_keys($group['qualities']));
        $languages = implode('/', array_keys($group['languages']));
        $text .= "{$i}. <b>{$group['name']}</b>\n   📥 {$group['count']} files | 🎥 {$qualities} | 🗣️ {$languages}\n\n";
        $i++;
    }
    
    $keyboard = ['inline_keyboard' => []];
    $nav_row = [];
    
    if ($page > 1) $nav_row[] = ['text' => '⏮️ Prev', 'callback_data' => 'tu_prev_' . ($page - 1)];
    $nav_row[] = ['text' => "📊 {$page}/{$total_pages}", 'callback_data' => 'page_info'];
    if ($page < $total_pages) $nav_row[] = ['text' => 'Next ⏭️', 'callback_data' => 'tu_next_' . ($page + 1)];
    
    $keyboard['inline_keyboard'][] = $nav_row;
    $keyboard['inline_keyboard'][] = [
        ['text' => '🏠 Home', 'callback_data' => 'back_home']
    ];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== STATS FUNCTIONS ====================
function update_stats($field, $increment = 1) {
    $stats = file_exists(STATS_FILE) ? json_decode(file_get_contents(STATS_FILE), true) : [];
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
}

function show_stats($chat_id, $user_id = null) {
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
    $text .= "✅ Approved: {$request_stats['approved']}\n";
    $text .= "❌ Rejected: {$request_stats['rejected']}\n";
    
    sendMessage($chat_id, $text, null, 'HTML');
}

function show_trending($chat_id, $user_id = null) {
    global $csvManager;
    
    $trending = $csvManager->getTrending(10);
    
    $text = "🔥 <b>TRENDING NOW</b>\n\n";
    $i = 1;
    foreach ($trending as $item) {
        $type = $item['is_series'] ? '📺' : '🎬';
        $text .= "$i. $type <b>{$item['movie_name']}</b>\n";
        $text .= "   {$item['quality']} | {$item['language']} | {$item['size']}\n\n";
        $i++;
    }
    
    sendMessage($chat_id, $text, null, 'HTML');
}

// ==================== USER FUNCTIONS ====================
function user_myrequests($chat_id, $user_id) {
    global $requestSystem;
    
    $requests = $requestSystem->getUserRequests($user_id);
    
    if (empty($requests)) {
        sendMessage($chat_id, "📭 No requests yet.\n\nUse /request MovieName to request.", null, 'HTML');
        return;
    }
    
    $text = "📋 <b>YOUR REQUESTS</b>\n\n";
    
    foreach ($requests as $req) {
        $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
        $text .= "$status_icon <b>#{$req['id']}:</b> {$req['movie_name']}\n";
        $text .= "   Status: " . ucfirst($req['status']) . "\n";
        $text .= "   Date: " . date('d M Y', strtotime($req['created_at'])) . "\n\n";
    }
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '📜 History', 'callback_data' => 'user_history']],
        [['text' => '⭐ Favorites', 'callback_data' => 'user_favorites']],
        [['text' => '🏠 Home', 'callback_data' => 'back_home']]
    ]];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function user_history($chat_id, $user_id) {
    $history = RequestHistory::getInstance()->getUserHistory($user_id);
    
    if (empty($history)) {
        sendMessage($chat_id, "📜 No request history found.", null, 'HTML');
        return;
    }
    
    $text = "📜 <b>REQUEST HISTORY</b>\n\n";
    
    foreach ($history as $req) {
        $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
        $date = date('d M Y', strtotime($req['created_at']));
        $text .= "$status_icon <b>{$req['movie_name']}</b> - #{$req['id']} ({$date})\n";
    }
    
    sendMessage($chat_id, $text, null, 'HTML');
}

function user_favorites($chat_id, $user_id) {
    $favorites = FavoritesManager::getInstance()->getFavorites($user_id);
    
    if (empty($favorites)) {
        sendMessage($chat_id, "⭐ No favorites yet. Search movies and add them!", null, 'HTML');
        return;
    }
    
    $text = "⭐ <b>FAVORITES</b>\n\n";
    $keyboard = ['inline_keyboard' => []];
    
    foreach ($favorites as $movie) {
        $text .= "• $movie\n";
        $keyboard['inline_keyboard'][] = [
            ['text' => "🔍 $movie", 'callback_data' => 'search_' . base64_encode($movie)],
            ['text' => '❌ Remove', 'callback_data' => 'remove_fav_' . base64_encode($movie)]
        ];
    }
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function show_recommendations($chat_id, $user_id) {
    $recommendations = RecommendationEngine::getInstance()->getPersonalizedRecommendations($user_id, 8);
    
    if (empty($recommendations)) {
        sendMessage($chat_id, "No recommendations yet. Try searching more movies!", null, 'HTML');
        return;
    }
    
    $text = "🎯 <b>RECOMMENDED FOR YOU</b>\n\n";
    $keyboard = ['inline_keyboard' => []];
    
    foreach ($recommendations as $rec) {
        $text .= "• $rec\n";
        $keyboard['inline_keyboard'][] = [['text' => "🔍 $rec", 'callback_data' => 'search_' . base64_encode($rec)]];
    }
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== ADMIN FUNCTIONS ====================
function admin_pending_list($chat_id, $user_id, $page = 1) {
    if (!in_array($user_id, ADMIN_IDS)) return;
    
    global $requestSystem;
    
    $all_pending = $requestSystem->getPendingRequests(50);
    $total = count($all_pending);
    $per_page = 10;
    $total_pages = ceil($total / $per_page);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * $per_page;
    $pending = array_slice($all_pending, $start, $per_page);
    
    $text = "⏳ <b>PENDING REQUESTS (Page $page/$total_pages)</b>\n\n";
    
    $keyboard = ['inline_keyboard' => []];
    
    foreach ($pending as $req) {
        $text .= "🔸 <b>#{$req['id']}:</b> {$req['movie_name']}\n";
        $text .= "   👤 User: " . ($req['user_name'] ?: $req['user_id']) . "\n";
        $text .= "   📅 " . date('d M H:i', strtotime($req['created_at'])) . "\n\n";
        
        $keyboard['inline_keyboard'][] = [
            ['text' => "✅ Approve #{$req['id']}", 'callback_data' => "admin_approve_{$req['id']}"],
            ['text' => "❌ Reject #{$req['id']}", 'callback_data' => "admin_reject_{$req['id']}"]
        ];
    }
    
    $nav = [];
    if ($page > 1) $nav[] = ['text' => '◀️ Prev', 'callback_data' => 'admin_pending_' . ($page - 1)];
    $nav[] = ['text' => "📊 $page/$total_pages", 'callback_data' => 'page_info'];
    if ($page < $total_pages) $nav[] = ['text' => 'Next ▶️', 'callback_data' => 'admin_pending_' . ($page + 1)];
    
    $keyboard['inline_keyboard'][] = $nav;
    $keyboard['inline_keyboard'][] = [['text' => '🔙 Back', 'callback_data' => 'admin_back']];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function admin_panel($chat_id, $user_id) {
    if (!in_array($user_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Unauthorized", null, 'HTML');
        return;
    }
    
    global $requestSystem;
    
    $pending = $requestSystem->getPendingRequests(5);
    $stats = $requestSystem->getStats();
    
    $text = "👑 <b>ADMIN PANEL</b>\n\n";
    $text .= "📊 System Stats:\n";
    $text .= "• Total: {$stats['total_requests']}\n";
    $text .= "• Pending: {$stats['pending']}\n";
    $text .= "• Approved: {$stats['approved']}\n";
    $text .= "• Rejected: {$stats['rejected']}\n\n";
    
    if (!empty($pending)) {
        $text .= "⏳ Recent Pending:\n";
        foreach ($pending as $req) {
            $text .= "• #{$req['id']}: {$req['movie_name']}\n";
        }
    }
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '⏳ View Pending', 'callback_data' => 'admin_pending']],
        [['text' => '📊 Stats', 'callback_data' => 'admin_stats']],
        [['text' => '🔙 Back', 'callback_data' => 'back_home']]
    ]];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== IMPORT OLD ====================
function cmd_import_old($chat_id, $user_id, $parts = []) {
    if (!in_array($user_id, ADMIN_IDS)) {
        sendMessage($chat_id, "❌ Unauthorized", null, 'HTML');
        return;
    }
    
    $dry_run = in_array('dry_run', $parts);
    $format = 'csv';
    foreach ($parts as $part) {
        if (strpos($part, 'format=') === 0) $format = str_replace('format=', '', $part);
    }
    
    $text = "📥 <b>BULK IMPORT MOVIES</b>\n\n";
    $text .= "Send a <b>{$format}</b> file.\n\n";
    
    if ($format === 'csv') {
        $text .= "<b>CSV Format:</b>\n";
        $text .= "<code>movie_name,message_id,channel_id,quality,language,size</code>\n";
        $text .= "Example:\n";
        $text .= "<code>KGF Chapter 2,12345,-100123456789,1080p,Hindi,2.5 GB</code>\n\n";
    } else {
        $text .= "<b>JSON Format:</b>\n";
        $text .= "<code>[{\"name\":\"KGF 2\",\"msg_id\":12345,\"channel\":\"-100123456789\",\"quality\":\"1080p\",\"lang\":\"Hindi\",\"size\":\"2.5 GB\"}]</code>\n\n";
    }
    
    $text .= "<b>Options:</b>\n";
    $text .= "• /import_old dry_run - Check without importing\n";
    
    sendMessage($chat_id, $text, null, 'HTML');
}

// ==================== MAINTENANCE CHECK ====================
if (MAINTENANCE_MODE) {
    $update = json_decode(file_get_contents('php://input'), true);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, "🛠️ Bot Under Maintenance", null, 'HTML');
    }
    exit;
}

// ==================== INITIALIZE MANAGERS ====================
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
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Setup</h2>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Deleted</h2>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

if (isset($_GET['test'])) {
    $stats = $csvManager->getStats();
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot - Test Page</h1>";
    echo "<p>Status: ✅ Running</p>";
    echo "<p>Movies: {$stats['total_movies']}</p>";
    echo "<p>Series: {$stats['total_series']}</p>";
    echo "<p>Users: " . count($users_data['users'] ?? []) . "</p>";
    exit;
}

// ==================== GET UPDATE ====================
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    http_response_code(200);
    echo "OK";
    exit;
}

log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);

RateLimiter::check($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 'telegram_update', 30, 60);

// ==================== PROCESS CHANNEL POSTS ====================
if (isset($update['channel_post'])) {
    $message = $update['channel_post'];
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    
    log_error("Channel post received", 'INFO', [
        'channel_id' => $chat_id,
        'message_id' => $message_id
    ]);
    
    $all_channels = array_merge(
        array_column($ENV_CONFIG['PUBLIC_CHANNELS'], 'id'),
        array_column($ENV_CONFIG['PRIVATE_CHANNELS'], 'id')
    );
    
    if (in_array($chat_id, $all_channels)) {
        $text = $message['caption'] ?? $message['text'] ?? $message['document']['file_name'] ?? 'Media - ' . date('d-m-Y H:i');
        
        if (!empty(trim($text))) {
            $movie_name = extract_movie_name($text);
            $quality = extract_quality($text);
            $language = extract_language($text);
            $size = extract_size($text);
            $year = extract_year($text);
            $is_series = is_series($text);
            $season = extract_season($text);
            $episode = extract_episode($text);
            
            $csvManager->bufferedAppend($movie_name, $message_id, $chat_id, [
                'quality' => $quality,
                'language' => $language,
                'size' => $size,
                'year' => $year,
                'is_series' => $is_series,
                'season' => $season,
                'episode' => $episode
            ]);
            
            log_error("✅ AUTO-SAVED: $movie_name", 'INFO', [
                'quality' => $quality,
                'language' => $language,
                'size' => $size
            ]);
            
            $auto_approved = $requestSystem->checkAutoApprove($movie_name);
            if (!empty($auto_approved)) {
                log_error("✅ Auto-approved requests: " . implode(',', $auto_approved), 'INFO');
            }
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
    $chat_type = $message['chat']['type'] ?? 'private';
    
    log_error("Message from $user_id", 'INFO', ['text' => substr($text, 0, 50)]);
    
    // Update user data
    $users_data = json_decode(file_get_contents(USERS_FILE), true) ?: ['users' => []];
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'first_name' => $message['from']['first_name'] ?? '',
            'last_name' => $message['from']['last_name'] ?? '',
            'username' => $message['from']['username'] ?? '',
            'joined' => date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s'),
            'language' => detectUserLanguage($text)
        ];
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        update_stats('total_users', 1);
        log_error("New user registered", 'INFO', ['user_id' => $user_id]);
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
            $welcome = "🎬 <b>Entertainment Tadka Bot</b>\n\nSearch any movie by typing its name.\n\nCommands:\n/settings - Settings\n/request - Request movie\n/myrequests - Your requests\n/history - History\n/favorites - Favorites\n/recommend - Recommendations\n/trending - Trending\n/stats - Bot stats\n/totaluploads - Browse all";
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '⚙️ Settings', 'callback_data' => 'menu_settings'], ['text' => '📊 Stats', 'callback_data' => 'show_stats']],
                    [['text' => '🔥 Trending', 'callback_data' => 'show_trending'], ['text' => '📝 My Requests', 'callback_data' => 'my_requests']],
                    [['text' => '⭐ Favorites', 'callback_data' => 'user_favorites'], ['text' => '🎯 Recommendations', 'callback_data' => 'show_recommendations']]
                ]
            ];
            sendMessageWithSpoiler($chat_id, $welcome, $keyboard, $spoiler_mode);
        }
        
        elseif ($command == '/settings') {
            cmd_settings($chat_id, $user_id);
        }
        
        elseif ($command == '/settimer') {
            cmd_timer($chat_id, $user_id);
        }
        
        elseif ($command == '/request') {
            if (!isset($parts[1])) {
                sendMessageWithSpoiler($chat_id, "📝 Use: /request Movie Name\nExample: /request KGF Chapter 2", null, $spoiler_mode);
            } else {
                $movie = implode(' ', array_slice($parts, 1));
                $user_name = $message['from']['first_name'] ?? '';
                $result = $requestSystem->submitRequest($user_id, $movie, $user_name);
                
                sendMessageWithSpoiler($chat_id, $result['message'], null, $spoiler_mode);
                
                if ($result['success']) {
                    foreach (ADMIN_IDS as $admin) {
                        sendMessage($admin, "📝 New request #{$result['request_id']}: $movie", null, 'HTML');
                    }
                }
            }
        }
        
        elseif ($command == '/myrequests') {
            user_myrequests($chat_id, $user_id);
        }
        
        elseif ($command == '/history') {
            user_history($chat_id, $user_id);
        }
        
        elseif ($command == '/favorites') {
            user_favorites($chat_id, $user_id);
        }
        
        elseif ($command == '/recommend') {
            show_recommendations($chat_id, $user_id);
        }
        
        elseif ($command == '/trending') {
            show_trending($chat_id, $user_id);
        }
        
        elseif ($command == '/stats') {
            show_stats($chat_id, $user_id);
        }
        
        elseif ($command == '/totaluploads' || $command == '/total') {
            $page = isset($parts[1]) && is_numeric($parts[1]) ? intval($parts[1]) : 1;
            totalupload_controller($chat_id, $page, $user_id);
        }
        
        elseif ($command == '/pending' && in_array($user_id, ADMIN_IDS)) {
            admin_pending_list($chat_id, $user_id);
        }
        
        elseif ($command == '/admin' && in_array($user_id, ADMIN_IDS)) {
            admin_panel($chat_id, $user_id);
        }
        
        elseif ($command == '/import_old' && in_array($user_id, ADMIN_IDS)) {
            cmd_import_old($chat_id, $user_id, array_slice($parts, 1));
        }
        
        elseif ($command == '/clearcache' && in_array($user_id, ADMIN_IDS)) {
            $csvManager->clearCache();
            sendMessage($chat_id, "✅ Cache cleared!", null, 'HTML');
        }
        
        elseif ($command == '/help') {
            $help = "🤖 <b>Commands</b>\n\n" .
                    "/start - Home\n/settings - Settings\n/request - Request movie\n/myrequests - Your requests\n" .
                    "/history - Request history\n/favorites - Favorites\n/recommend - Recommendations\n/trending - Trending\n" .
                    "/stats - Bot stats\n/totaluploads - Browse all\n" .
                    (in_array($user_id, ADMIN_IDS) ? "\n👑 Admin:\n/pending\n/admin\n/import_old\n/clearcache" : "");
            sendMessageWithSpoiler($chat_id, $help, null, $spoiler_mode);
        }
        
        else {
            sendMessageWithSpoiler($chat_id, "❌ Unknown command. Type /help", null, $spoiler_mode);
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
                [['text' => '⚙️ Settings', 'callback_data' => 'menu_settings'], ['text' => '📊 Stats', 'callback_data' => 'show_stats']],
                [['text' => '🔥 Trending', 'callback_data' => 'show_trending'], ['text' => '📝 My Requests', 'callback_data' => 'my_requests']],
                [['text' => '⭐ Favorites', 'callback_data' => 'user_favorites'], ['text' => '🎯 Recommendations', 'callback_data' => 'show_recommendations']]
            ]
        ];
        editMessageText($chat_id, $msg_id, $welcome, $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    
    // Show stats
    elseif ($data == 'show_stats') {
        show_stats($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Show trending
    elseif ($data == 'show_trending') {
        show_trending($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // My requests
    elseif ($data == 'my_requests') {
        user_myrequests($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // User history
    elseif ($data == 'user_history') {
        user_history($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // User favorites
    elseif ($data == 'user_favorites') {
        user_favorites($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Show recommendations
    elseif ($data == 'show_recommendations') {
        show_recommendations($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Remove favorite
    elseif (strpos($data, 'remove_fav_') === 0) {
        $movie = base64_decode(str_replace('remove_fav_', '', $data));
        FavoritesManager::getInstance()->removeFavorite($user_id, $movie);
        user_favorites($chat_id, $user_id);
        answerCallbackQuery($query['id'], "❌ Removed from favorites");
    }
    
    // Movie selection
    elseif (strpos($data, 'movie_') === 0) {
        $movie_name = base64_decode(str_replace('movie_', '', $data));
        
        // Add to favorites option
        $keyboard = ['inline_keyboard' => [
            [['text' => '⭐ Add to Favorites', 'callback_data' => 'add_fav_' . base64_encode($movie_name)]],
            [['text' => '🔍 Search Again', 'callback_data' => 'search_' . base64_encode($movie_name)]]
        ]];
        sendMessage($chat_id, "🎬 <b>$movie_name</b>\n\nChoose option:", $keyboard, 'HTML');
        
        deliver_movie($chat_id, $movie_name, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Add favorite
    elseif (strpos($data, 'add_fav_') === 0) {
        $movie = base64_decode(str_replace('add_fav_', '', $data));
        FavoritesManager::getInstance()->addFavorite($user_id, $movie);
        answerCallbackQuery($query['id'], "⭐ Added to favorites!");
    }
    
    // Send specific version
    elseif (strpos($data, 'send_') === 0) {
        $parts = explode('_', $data, 4);
        $movie = base64_decode($parts[1]);
        $quality = $parts[2] ?? null;
        $lang = $parts[3] ?? null;
        deliver_movie($chat_id, $movie, $user_id, $quality, $lang);
        answerCallbackQuery($query['id']);
    }
    
    // Send all
    elseif (strpos($data, 'sendall_') === 0) {
        $movie = base64_decode(str_replace('sendall_', '', $data));
        $all = $csvManager->getCachedData();
        $items = array_filter($all, function($i) use ($movie) {
            return strpos(strtolower($i['movie_name']), strtolower($movie)) !== false;
        });
        send_all_versions($chat_id, $items, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Quality menu
    elseif (strpos($data, 'menu_quality_') === 0) {
        $query = base64_decode(str_replace('menu_quality_', '', $data));
        show_quality_filter_menu($chat_id, $msg_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Language menu
    elseif (strpos($data, 'menu_language_') === 0) {
        $query = base64_decode(str_replace('menu_language_', '', $data));
        show_language_filter_menu($chat_id, $msg_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Filter quality
    elseif (strpos($data, 'filter_quality_') === 0) {
        $parts = explode('_', $data, 4);
        $quality = $parts[2];
        $query = base64_decode($parts[3] ?? '');
        advanced_search($chat_id, $query, $user_id, ['quality' => $quality]);
        answerCallbackQuery($query['id']);
    }
    
    // Filter language
    elseif (strpos($data, 'filter_lang_') === 0) {
        $parts = explode('_', $data, 4);
        $lang = $parts[2];
        $query = base64_decode($parts[3] ?? '');
        advanced_search($chat_id, $query, $user_id, ['language' => $lang]);
        answerCallbackQuery($query['id']);
    }
    
    // Filter series
    elseif (strpos($data, 'filter_series_') === 0) {
        $query = base64_decode(str_replace('filter_series_', '', $data));
        advanced_search($chat_id, $query, $user_id, ['is_series' => true]);
        answerCallbackQuery($query['id']);
    }
    
    // Search from suggestion
    elseif (strpos($data, 'search_') === 0) {
        $query = base64_decode(str_replace('search_', '', $data));
        advanced_search($chat_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Admin pending
    elseif ($data == 'admin_pending' && in_array($user_id, ADMIN_IDS)) {
        admin_pending_list($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    elseif (strpos($data, 'admin_pending_') === 0 && in_array($user_id, ADMIN_IDS)) {
        $page = intval(str_replace('admin_pending_', '', $data));
        admin_pending_list($chat_id, $user_id, $page);
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
    
    // Admin back
    elseif ($data == 'admin_back' && in_array($user_id, ADMIN_IDS)) {
        admin_panel($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Admin stats
    elseif ($data == 'admin_stats' && in_array($user_id, ADMIN_IDS)) {
        show_stats($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    // Total uploads navigation
    elseif (strpos($data, 'tu_prev_') === 0) {
        $page = intval(str_replace('tu_prev_', '', $data));
        totalupload_controller($chat_id, $page, $user_id);
        answerCallbackQuery($query['id']);
    }
    
    elseif (strpos($data, 'tu_next_') === 0) {
        $page = intval(str_replace('tu_next_', '', $data));
        totalupload_controller($chat_id, $page, $user_id);
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