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
    'DUPLICATE_CHECK_HOURS' => 24,
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
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);

define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('SERIAL_CHANNEL', '@Entertainment_Tadka_Serial_786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('BACKUP_CHANNEL', '@ETBackup');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');

// ==================== SECURITY FUNCTIONS ====================
function validateInput($input, $type = 'text') {
    if (is_array($input)) return array_map('validateInput', $input);
    
    $input = trim($input);
    
    switch($type) {
        case 'movie_name':
            if (strlen($input) < 2 || strlen($input) > 200) return false;
            if (!preg_match('/^[\p{L}\p{N}\s\-\.\,\&\+\'\"\(\)\!\:\;\?]{2,200}$/u', $input)) return false;
            return $input;
        case 'user_id': return preg_match('/^\d+$/', $input) ? intval($input) : false;
        case 'command': return preg_match('/^\/[a-zA-Z0-9_]+$/', $input) ? $input : false;
        case 'telegram_id': return preg_match('/^\-?\d+$/', $input) ? $input : false;
        case 'filename':
            $input = basename($input);
            $allowed = ['movies.csv', 'users.json', 'bot_stats.json', 'requests.json', 'user_settings.json'];
            return in_array($input, $allowed) ? $input : false;
        default: return $input;
    }
}

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

// ==================== LANGUAGE FUNCTIONS ====================
class LanguageManager {
    private static $instance = null;
    private $translations = [];
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        $this->loadTranslations();
    }
    
    private function loadTranslations() {
        // English
        $this->translations['en'] = [
            'welcome' => "🎬 <b>Welcome to Entertainment Tadka Bot!</b>\n\nSearch any movie by typing its name.\nExamples: KGF, Jawan, Animal",
            'search' => "🔍 Search Results",
            'no_results' => "😔 No movies found",
            'settings' => "⚙️ Settings",
            'trending' => "🔥 Trending",
            'requests' => "📝 My Requests",
            'help' => "❓ Help",
            'language' => "🌐 Language",
            'home' => "🏠 Home",
            'back' => "🔙 Back",
            'next' => "Next ▶️",
            'prev' => "◀️ Prev",
            'quality' => "🎥 Quality",
            'language_filter' => "🗣️ Language",
            'series' => "📺 Series",
            'movies' => "🎬 Movies",
            'download' => "📥 Download",
            'approve' => "✅ Approve",
            'reject' => "❌ Reject",
            'pending' => "⏳ Pending",
            'stats' => "📊 Statistics",
            'channels' => "📢 Channels",
            'favorites' => "⭐ Favorites",
            'history' => "📜 History"
        ];
        
        // Hindi
        $this->translations['hi'] = [
            'welcome' => "🎬 <b>एंटरटेनमेंट तड़का बॉट में आपका स्वागत है!</b>\n\nकोई भी मूवी का नाम लिखकर सर्च करें।\nउदाहरण: केजीएफ, जवान, एनिमल",
            'search' => "🔍 खोज परिणाम",
            'no_results' => "😔 कोई मूवी नहीं मिली",
            'settings' => "⚙️ सेटिंग्स",
            'trending' => "🔥 ट्रेंडिंग",
            'requests' => "📝 मेरे अनुरोध",
            'help' => "❓ मदद",
            'language' => "🌐 भाषा",
            'home' => "🏠 होम",
            'back' => "🔙 वापस",
            'next' => "अगला ▶️",
            'prev' => "◀️ पिछला",
            'quality' => "🎥 क्वालिटी",
            'language_filter' => "🗣️ भाषा",
            'series' => "📺 सीरीज",
            'movies' => "🎬 मूवीज",
            'download' => "📥 डाउनलोड",
            'approve' => "✅ स्वीकारें",
            'reject' => "❌ अस्वीकारें",
            'pending' => "⏳ लंबित",
            'stats' => "📊 आंकड़े",
            'channels' => "📢 चैनल",
            'favorites' => "⭐ पसंदीदा",
            'history' => "📜 इतिहास"
        ];
        
        // Tamil
        $this->translations['ta'] = [
            'welcome' => "🎬 <b>எண்டர்டெயின்மென்ட் தட்கா போட்டுக்கு வரவேற்கிறோம்!</b>\n\nபெயரைத் தட்டச்சு செய்து எந்த படத்தையும் தேடுங்கள்.\nஎடுத்துக்காட்டுகள்: கேஜிஎஃப், ஜவான், அனிமல்",
            'search' => "🔍 தேடல் முடிவுகள்",
            'no_results' => "😔 படங்கள் எதுவும் கிடைக்கவில்லை",
            'settings' => "⚙️ அமைப்புகள்",
            'trending' => "🔥 பிரபலமானவை",
            'requests' => "📝 எனது கோரிக்கைகள்",
            'help' => "❓ உதவி",
            'language' => "🌐 மொழி",
            'home' => "🏠 முகப்பு",
            'back' => "🔙 பின்",
            'next' => "அடுத்து ▶️",
            'prev' => "◀️ முந்தைய",
            'quality' => "🎥 தரம்",
            'language_filter' => "🗣️ மொழி",
            'series' => "📺 தொடர்கள்",
            'movies' => "🎬 திரைப்படங்கள்",
            'download' => "📥 பதிவிறக்கம்",
            'approve' => "✅ ஏற்க",
            'reject' => "❌ நிராகரி",
            'pending' => "⏳ நிலுவையில்",
            'stats' => "📊 புள்ளிவிவரங்கள்",
            'channels' => "📢 சேனல்கள்",
            'favorites' => "⭐ விருப்பங்கள்",
            'history' => "📜 வரலாறு"
        ];
        
        // Telugu
        $this->translations['te'] = [
            'welcome' => "🎬 <b>ఎంటర్టైన్మెంట్ తడ్కా బాట్‌కు స్వాగతం!</b>\n\nపేరు టైప్ చేసి ఏదైనా సినిమా కోసం వెతకండి.\nఉదాహరణలు: కేజీఎఫ్, జవాన్, యానిమల్",
            'search' => "🔍 శోధన ఫలితాలు",
            'no_results' => "😔 సినిమాలు ఏవీ దొరకలేదు",
            'settings' => "⚙️ సెట్టింగులు",
            'trending' => "🔥 ట్రెండింగ్",
            'requests' => "📝 నా అభ్యర్థనలు",
            'help' => "❓ సహాయం",
            'language' => "🌐 భాష",
            'home' => "🏠 హోమ్",
            'back' => "🔙 వెనుకకు",
            'next' => "తదుపరి ▶️",
            'prev' => "◀️ మునుపటి",
            'quality' => "🎥 నాణ్యత",
            'language_filter' => "🗣️ భాష",
            'series' => "📺 సిరీస్",
            'movies' => "🎬 సినిమాలు",
            'download' => "📥 డౌన్‌లోడ్",
            'approve' => "✅ ఆమోదించు",
            'reject' => "❌ తిరస్కరించు",
            'pending' => "⏳ పెండింగ్",
            'stats' => "📊 గణాంకాలు",
            'channels' => "📢 ఛానెల్స్",
            'favorites' => "⭐ ఇష్టమైనవి",
            'history' => "📜 చరిత్ర"
        ];
    }
    
    public function translate($key, $lang = 'en') {
        return $this->translations[$lang][$key] ?? $this->translations['en'][$key] ?? $key;
    }
    
    public function getLanguageMenu() {
        return [
            'inline_keyboard' => [
                [['text' => '🇬🇧 English', 'callback_data' => 'set_lang_en'], ['text' => '🇮🇳 हिंदी', 'callback_data' => 'set_lang_hi']],
                [['text' => '🇮🇳 தமிழ்', 'callback_data' => 'set_lang_ta'], ['text' => '🇮🇳 తెలుగు', 'callback_data' => 'set_lang_te']],
                [['text' => '🔙 Back', 'callback_data' => 'back_home']]
            ]
        ];
    }
}

function detectUserLanguage($text) {
    $hindi_pattern = '/[\x{0900}-\x{097F}]/u';
    if (preg_match($hindi_pattern, $text)) return 'hi';
    
    $tamil_pattern = '/[\x{0B80}-\x{0BFF}]/u';
    if (preg_match($tamil_pattern, $text)) return 'ta';
    
    $telugu_pattern = '/[\x{0C00}-\x{0C7F}]/u';
    if (preg_match($telugu_pattern, $text)) return 'te';
    
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

function t($key, $user_id = null) {
    $lang = $user_id ? getUserLanguage($user_id) : 'en';
    return LanguageManager::getInstance()->translate($key, $lang);
}

// ==================== ENHANCED EXTRACTION FUNCTIONS ====================
function enhanced_extract_movie_name($text) {
    $original = $text;
    $text = preg_replace('/^(?:Watch|Download|Free|Movie|Film|Full|HD|4K|1080p)\s+/i', '', $text);
    
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s*(?:\(?\d{4}\)?)?\s*(?:4K|1080p|720p|480p|HD)?\s*(?:Hindi|English|Tamil|Telugu)?/u', $text, $matches);
    if (!empty($matches[1])) return trim($matches[1]);
    
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s*(?:\{?\d{4}\}?)?\s*(?:\[?4K|1080p|720p|480p|HD\]?)?/u', $text, $matches);
    if (!empty($matches[1])) return trim($matches[1]);
    
    preg_match('/^([A-Za-z0-9\s:.\-!&\'",]+?)\s+S\d{2}/u', $text, $matches);
    if (!empty($matches[1])) return trim($matches[1]);
    
    return substr(trim($original), 0, 100);
}

function enhanced_extract_quality($text) {
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

function enhanced_extract_language($text) {
    $languages = [
        'Hindi' => ['Hindi', 'हिंदी', 'HINDI'],
        'English' => ['English', 'ENG', 'ENGLISH'],
        'Tamil' => ['Tamil', 'தமிழ்', 'TAMIL'],
        'Telugu' => ['Telugu', 'తెలుగు', 'TELUGU'],
        'Malayalam' => ['Malayalam', 'മലയാളം', 'MALAYALAM'],
        'Kannada' => ['Kannada', 'ಕನ್ನಡ', 'KANNADA'],
        'Bengali' => ['Bengali', 'বাংলা', 'BENGALI'],
        'Punjabi' => ['Punjabi', 'ਪੰਜਾਬੀ', 'PUNJABI']
    ];
    
    foreach ($languages as $language => $patterns) {
        foreach ($patterns as $pattern) {
            if (stripos($text, $pattern) !== false) return $language;
        }
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

function enhanced_is_series($text) {
    $series_patterns = ['/S\d{2}/i', '/Season \d+/i', '/Episodes? \d+/i', '/Complete Series/i', '/Web Series/i', '/TV Series/i'];
    foreach ($series_patterns as $pattern) {
        if (preg_match($pattern, $text)) return true;
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

// ==================== CHANNEL MANAGEMENT ====================
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

// ==================== DOWNLOAD TRACKER ====================
class DownloadTracker {
    private static $instance = null;
    private $tracking_file = 'download_tracking.json';
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        if (!file_exists($this->tracking_file)) {
            file_put_contents($this->tracking_file, json_encode(['movies' => [], 'users' => [], 'daily' => [], 'total_downloads' => 0], JSON_PRETTY_PRINT));
        }
    }
    
    private function loadData() {
        return json_decode(file_get_contents($this->tracking_file), true) ?: ['movies' => [], 'users' => [], 'daily' => [], 'total_downloads' => 0];
    }
    
    private function saveData($data) {
        return file_put_contents($this->tracking_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function trackDownload($user_id, $movie_name, $quality = null, $is_series = false) {
        $data = $this->loadData();
        $today = date('Y-m-d');
        
        if (!isset($data['movies'][$movie_name])) {
            $data['movies'][$movie_name] = ['count' => 0, 'users' => [], 'last_download' => $today, 'qualities' => [], 'is_series' => $is_series];
        }
        $data['movies'][$movie_name]['count']++;
        $data['movies'][$movie_name]['last_download'] = $today;
        $data['movies'][$movie_name]['users'][$user_id] = time();
        if ($quality && !in_array($quality, $data['movies'][$movie_name]['qualities'])) {
            $data['movies'][$movie_name]['qualities'][] = $quality;
        }
        
        if (!isset($data['users'][$user_id])) {
            $data['users'][$user_id] = ['total' => 0, 'movies' => [], 'last_active' => $today];
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
            $movies = array_filter($movies, fn($m) => strtotime($m['last_download']) > $week_ago);
        } elseif ($period == 'month') {
            $month_ago = strtotime('-30 days');
            $movies = array_filter($movies, fn($m) => strtotime($m['last_download']) > $month_ago);
        }
        
        uasort($movies, fn($a, $b) => $b['count'] - $a['count']);
        return array_slice($movies, 0, $limit, true);
    }
    
    public function displayPopularity($chat_id, $user_id = null) {
        $all_time = $this->getPopularMovies(10, 'all');
        $trending = $this->getPopularMovies(10, 'week');
        $data = $this->loadData();
        
        $text = "📊 <b>" . t('stats', $user_id) . "</b>\n\n";
        $text .= "📦 Total Downloads: {$data['total_downloads']}\n";
        $text .= "🎬 Unique Movies: " . count($data['movies']) . "\n";
        $text .= "👥 Active Users: " . count($data['users']) . "\n\n";
        
        $text .= "🔥 <b>" . t('trending', $user_id) . " (This Week):</b>\n";
        $i = 1;
        foreach ($trending as $name => $stats) {
            $icon = $stats['is_series'] ? '📺' : '🎬';
            $text .= "$i. $icon $name - {$stats['count']} downloads\n";
            $i++;
        }
        
        $text .= "\n🏆 <b>All-Time Popular:</b>\n";
        $i = 1;
        foreach ($all_time as $name => $stats) {
            $icon = $stats['is_series'] ? '📺' : '🎬';
            $text .= "$i. $icon $name - {$stats['count']} downloads\n";
            $i++;
        }
        
        $keyboard = ['inline_keyboard' => [
            [['text' => '🔄 ' . t('refresh', $user_id), 'callback_data' => 'show_popularity']],
            [['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'back_home']]
        ]];
        
        sendMessage($chat_id, $text, $keyboard, 'HTML');
    }
}

// ==================== RECOMMENDATION ENGINE ====================
class RecommendationEngine {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
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
        $keywords = ['base' => $movie_name, 'year' => null, 'quality' => null, 'language' => null, 'is_series' => false];
        
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
        $keywords['is_series'] = enhanced_is_series($movie_name);
        return $keywords;
    }
    
    public function getPersonalizedRecommendations($user_id, $limit = 5) {
        global $csvManager, $requestHistory;
        
        $history = $requestHistory ? $requestHistory->getUserHistory($user_id, 20) : [];
        if (empty($history)) return $this->getPopularMovies($limit);
        
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
    
    public function displayRecommendations($chat_id, $user_id) {
        $recommendations = $this->getPersonalizedRecommendations($user_id, 8);
        
        if (empty($recommendations)) {
            sendMessage($chat_id, "No recommendations yet. Try searching more movies!", null, 'HTML');
            return;
        }
        
        $text = "🎯 <b>" . t('recommendations', $user_id) . "</b>\n\n";
        $keyboard = ['inline_keyboard' => []];
        
        foreach ($recommendations as $rec) {
            $text .= "• $rec\n";
            $keyboard['inline_keyboard'][] = [['text' => "🔍 $rec", 'callback_data' => 'search_' . base64_encode($rec)]];
        }
        
        $keyboard['inline_keyboard'][] = [
            ['text' => '🔄 ' . t('more', $user_id), 'callback_data' => 'recommend_more'],
            ['text' => '🏠 ' . t('home', $user_id), 'callback_data' => 'back_home']
        ];
        
        sendMessage($chat_id, $text, $keyboard, 'HTML');
    }
    
    public function displaySimilar($chat_id, $movie_name, $user_id = null) {
        $similar = $this->findSimilar($movie_name, 5);
        if (empty($similar)) return;
        
        $text = "🎯 <b>Similar to '{$movie_name}':</b>\n\n";
        $keyboard = ['inline_keyboard' => []];
        
        foreach ($similar as $s) {
            $text .= "• $s\n";
            $keyboard['inline_keyboard'][] = [['text' => "🔍 $s", 'callback_data' => 'search_' . base64_encode($s)]];
        }
        
        sendMessage($chat_id, $text, $keyboard, 'HTML');
    }
}

// ==================== FAVORITES MANAGER ====================
class FavoritesManager {
    private static $instance = null;
    private $favorites_file = 'favorites.json';
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
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
            $data['favorites'][$user_id] = array_values(array_filter($data['favorites'][$user_id], fn($m) => $m != $movie_name));
            $this->saveData($data);
            return true;
        }
        return false;
    }
    
    public function getFavorites($user_id) {
        $data = $this->loadData();
        return $data['favorites'][$user_id] ?? [];
    }
    
    public function displayFavorites($chat_id, $user_id) {
        $favorites = $this->getFavorites($user_id);
        
        if (empty($favorites)) {
            sendMessage($chat_id, "⭐ You have no favorites yet. Search movies and add them!", null, 'HTML');
            return;
        }
        
        $text = "⭐ <b>" . t('favorites', $user_id) . "</b>\n\n";
        $keyboard = ['inline_keyboard' => []];
        
        foreach ($favorites as $movie) {
            $text .= "• $movie\n";
            $keyboard['inline_keyboard'][] = [
                ['text' => "🔍 $movie", 'callback_data' => 'search_' . base64_encode($movie)],
                ['text' => '❌ Remove', 'callback_data' => 'remove_fav_' . base64_encode($movie)]
            ];
        }
        
        $keyboard['inline_keyboard'][] = [['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'back_home']];
        
        sendMessage($chat_id, $text, $keyboard, 'HTML');
    }
}

// ==================== REQUEST HISTORY ====================
class RequestHistory {
    private static $instance = null;
    private $history_file = 'request_history.json';
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
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
    
    public function displayHistory($chat_id, $user_id) {
        $history = $this->getUserHistory($user_id);
        
        if (empty($history)) {
            sendMessage($chat_id, "📜 No request history found.", null, 'HTML');
            return;
        }
        
        $text = "📜 <b>" . t('history', $user_id) . "</b>\n\n";
        $keyboard = ['inline_keyboard' => []];
        
        foreach ($history as $req) {
            $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
            $date = date('d M Y', strtotime($req['created_at']));
            $text .= "$status_icon <b>{$req['movie_name']}</b> - #{$req['id']} ({$date})\n";
        }
        
        $keyboard['inline_keyboard'][] = [
            ['text' => '🔄 ' . t('refresh', $user_id), 'callback_data' => 'user_history'],
            ['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'back_home']
        ];
        
        sendMessage($chat_id, $text, $keyboard, 'HTML');
    }
}

// ==================== SERIES MANAGER ====================
class SeriesManager {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
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
                    $episodes[$ep] = ['episode' => $ep, 'count' => 0, 'qualities' => [], 'languages' => [], 'items' => []];
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
    
    public function displaySeriesMenu($chat_id, $msg_id, $series_name, $user_id = null) {
        $seasons = $this->getSeasons($series_name);
        
        $text = "📺 <b>{$series_name}</b>\n\n";
        $text .= "📊 Total Seasons: " . count($seasons) . "\n\n";
        
        $keyboard = ['inline_keyboard' => []];
        
        foreach ($seasons as $season => $data) {
            $qualities = implode('/', array_keys($data['qualities']));
            $languages = implode('/', array_keys($data['languages']));
            $text .= "• <b>$season</b>: {$data['total_episodes']} eps | $qualities | $languages\n";
            
            $keyboard['inline_keyboard'][] = [
                ['text' => "📺 $season", 'callback_data' => 'series_season_' . base64_encode($series_name) . '_' . $season]
            ];
        }
        
        $keyboard['inline_keyboard'][] = [
            ['text' => '📥 ' . t('download', $user_id) . ' All', 'callback_data' => 'series_download_all_' . base64_encode($series_name)],
            ['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'back_home']
        ];
        
        editMessageText($chat_id, $msg_id, $text, $keyboard, 'HTML');
    }
    
    public function displaySeasonEpisodes($chat_id, $msg_id, $series_name, $season, $user_id = null) {
        $episodes = $this->getEpisodes($series_name, $season);
        
        $text = "📺 <b>$series_name - $season</b>\n\n";
        $text .= "📊 Total Episodes: " . count($episodes) . "\n\n";
        
        $keyboard = ['inline_keyboard' => []];
        $row = [];
        
        foreach ($episodes as $ep => $data) {
            $row[] = ['text' => "$ep", 'callback_data' => 'series_episode_' . base64_encode($series_name) . '_' . $season . '_' . $ep];
            if (count($row) == 5) {
                $keyboard['inline_keyboard'][] = $row;
                $row = [];
            }
        }
        if (!empty($row)) $keyboard['inline_keyboard'][] = $row;
        
        $seasons = array_keys($this->getSeasons($series_name));
        $current_index = array_search($season, $seasons);
        $nav_row = [];
        
        if ($current_index > 0) {
            $prev_season = $seasons[$current_index - 1];
            $nav_row[] = ['text' => '◀️ ' . t('prev', $user_id), 'callback_data' => 'series_season_' . base64_encode($series_name) . '_' . $prev_season];
        }
        $nav_row[] = ['text' => '📋 All', 'callback_data' => 'series_menu_' . base64_encode($series_name)];
        if ($current_index < count($seasons) - 1) {
            $next_season = $seasons[$current_index + 1];
            $nav_row[] = ['text' => t('next', $user_id) . ' ▶️', 'callback_data' => 'series_season_' . base64_encode($series_name) . '_' . $next_season];
        }
        
        $keyboard['inline_keyboard'][] = $nav_row;
        $keyboard['inline_keyboard'][] = [
            ['text' => '📥 ' . t('download', $user_id) . ' Season', 'callback_data' => 'season_download_all_' . base64_encode($series_name) . '_' . $season],
            ['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'series_menu_' . base64_encode($series_name)]
        ];
        
        editMessageText($chat_id, $msg_id, $text, $keyboard, 'HTML');
    }
    
    public function sendEpisode($chat_id, $series_name, $season, $episode) {
        global $csvManager;
        $all = $csvManager->getCachedData();
        $items = [];
        
        foreach ($all as $item) {
            if ($item['is_series'] && stripos($item['movie_name'], $series_name) !== false && 
                $item['season'] == $season && $item['episode'] == $episode) {
                $items[] = $item;
            }
        }
        
        if (empty($items)) {
            sendMessage($chat_id, "❌ Episode not found", null, 'HTML');
            return;
        }
        
        $sent = 0;
        foreach ($items as $item) {
            if (deliver_item_to_chat($chat_id, $item)) $sent++;
            usleep(300000);
        }
        
        sendMessage($chat_id, "✅ Sent $sent files for $series_name $season $episode", null, 'HTML');
    }
    
    public function sendSeason($chat_id, $series_name, $season) {
        $episodes = $this->getEpisodes($series_name, $season);
        $total = 0;
        
        foreach ($episodes as $ep => $data) {
            foreach ($data['items'] as $item) {
                if (deliver_item_to_chat($chat_id, $item)) $total++;
                usleep(300000);
            }
        }
        
        sendMessage($chat_id, "✅ Downloaded $total files for $series_name $season", null, 'HTML');
    }
    
    public function sendSeries($chat_id, $series_name) {
        $seasons = $this->getSeasons($series_name);
        $total = 0;
        
        foreach ($seasons as $season => $data) {
            $episodes = $this->getEpisodes($series_name, $season);
            foreach ($episodes as $ep => $ep_data) {
                foreach ($ep_data['items'] as $item) {
                    if (deliver_item_to_chat($chat_id, $item)) $total++;
                    usleep(300000);
                }
            }
        }
        
        sendMessage($chat_id, "✅ Downloaded all $total files for $series_name", null, 'HTML');
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
            'timer' => 60,
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
            $default = ['requests' => [], 'last_request_id' => 0, 'user_stats' => [], 
                       'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]];
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
        $movie_name = validateInput($movie_name, 'movie_name');
        if (!$movie_name || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name'];
        }
        
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
            'quality' => $extra['quality'] ?? enhanced_extract_quality($movie_name),
            'language' => $extra['language'] ?? enhanced_extract_language($movie_name),
            'size' => $extra['size'] ?? extract_size($movie_name),
            'year' => $extra['year'] ?? extract_year($movie_name),
            'is_series' => $extra['is_series'] ?? (enhanced_is_series($movie_name) ? '1' : '0'),
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
        
        uasort($results, fn($a, $b) => $b['score'] - $a['score']);
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
        usort($data, fn($a, $b) => strtotime($b['added_at']) - strtotime($a['added_at']));
        return array_slice($data, 0, $limit);
    }
}

// ==================== ENHANCED SEARCH ====================
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
    
    $keyboard['inline_keyboard'][] = [['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'search_' . base64_encode($query)]];
    
    editMessageText($chat_id, $msg_id, "🎥 <b>" . t('quality', $user_id) . "</b>\n\nSelect quality for: $query", $keyboard, 'HTML');
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
    
    $keyboard['inline_keyboard'][] = [['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'search_' . base64_encode($query)]];
    
    editMessageText($chat_id, $msg_id, "🗣️ <b>" . t('language_filter', $user_id) . "</b>\n\nSelect language for: $query", $keyboard, 'HTML');
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
        
        $query_words = explode(' ', $query_lower);
        $name_words = explode(' ', $name_lower);
        $common = array_intersect($query_words, $name_words);
        if (!empty($common)) $suggestions[$item['movie_name']] = max($suggestions[$item['movie_name']] ?? 0, 70);
    }
    
    arsort($suggestions);
    $suggestions = array_slice(array_keys($suggestions), 0, 8);
    
    if (empty($suggestions)) {
        $popular = array_slice($all, 0, 8);
        foreach ($popular as $item) $suggestions[] = $item['movie_name'];
        $suggestions = array_unique($suggestions);
        $suggestions = array_slice($suggestions, 0, 8);
    }
    
    $text = "😔 <b>" . t('no_results', $user_id) . " for '$query'</b>\n\n💡 <b>Suggestions:</b>\n";
    
    $keyboard = ['inline_keyboard' => []];
    $i = 1;
    foreach ($suggestions as $suggestion) {
        $text .= "$i. $suggestion\n";
        $keyboard['inline_keyboard'][] = [['text' => "🔍 $suggestion", 'callback_data' => 'search_' . base64_encode($suggestion)]];
        $i++;
    }
    
    $text .= "\n📢 Join: @EntertainmentTadka786";
    $keyboard['inline_keyboard'][] = [
        ['text' => '🎥 ' . t('browse', $user_id), 'callback_data' => 'browse_all'],
        ['text' => '🔥 ' . t('trending', $user_id), 'callback_data' => 'show_trending']
    ];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function advanced_search_with_filters($chat_id, $query, $user_id = null, $filters = []) {
    global $csvManager, $cacheManager;
    
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
    
    $filter_text = "";
    if (!empty($filters)) {
        $filter_text = "\n📊 <b>Active Filters:</b> ";
        if (isset($filters['quality'])) $filter_text .= "🎥 {$filters['quality']} ";
        if (isset($filters['language'])) $filter_text .= "🗣️ {$filters['language']} ";
        if (isset($filters['is_series'])) $filter_text .= "📺 Series ";
        $filter_text .= "\n";
    }
    
    $text = "🔍 <b>Found " . count($results) . " " . t('search', $user_id) . " for '$q'</b> ($total_items files)$filter_text\n\n";
    
    $i = 1;
    foreach ($results as $name => $data) {
        $qualities = implode('/', array_keys($data['qualities']));
        $languages = implode('/', array_keys($data['languages']));
        $type = $data['is_series'] ? '📺 Series' : '🎬 Movie';
        $text .= "$i. <b>$name</b>\n   $type | 🎥 $qualities | 🗣️ $languages | 📦 {$data['count']} files\n";
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
    $quality_text = isset($filters['quality']) ? "🎥 {$filters['quality']} ✓" : "🎥 " . t('quality', $user_id);
    $filter_row[] = ['text' => $quality_text, 'callback_data' => 'filter_menu_quality_' . base64_encode($q)];
    
    $lang_text = isset($filters['language']) ? "🗣️ {$filters['language']} ✓" : "🗣️ " . t('language_filter', $user_id);
    $filter_row[] = ['text' => $lang_text, 'callback_data' => 'filter_menu_lang_' . base64_encode($q)];
    
    $series_text = isset($filters['is_series']) ? "📺 Series ✓" : "📺 Series";
    $filter_row[] = ['text' => $series_text, 'callback_data' => 'filter_toggle_series_' . base64_encode($q)];
    
    $keyboard['inline_keyboard'][] = $filter_row;
    
    if (!empty($filters)) {
        $keyboard['inline_keyboard'][] = [
            ['text' => '🗑️ Clear Filters', 'callback_data' => 'search_' . base64_encode($q)]
        ];
    }
    
    $keyboard['inline_keyboard'][] = [
        ['text' => '🔍 ' . t('search', $user_id), 'switch_inline_query_current_chat' => ''],
        ['text' => '🏠 ' . t('home', $user_id), 'callback_data' => 'back_home']
    ];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
    
    update_stats('total_searches', 1);
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
            $grouped[$key] = ['quality' => $item['quality'], 'language' => $item['language'], 'items' => []];
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
        
        sendMessage($chat_id, "🎬 <b>$movie_name</b>\n\nMultiple versions found. Choose one:", $keyboard, 'HTML');
    } else {
        send_all_versions($chat_id, $items, $user_id);
    }
}

// ==================== TOTAL UPLOADS ====================
function totalupload_controller($chat_id, $page = 1, $user_id = null) {
    global $csvManager;
    
    sendChatAction($chat_id, 'upload_document');
    
    $all = $csvManager->getCachedData();
    if (empty($all)) {
        sendMessage($chat_id, "⚠️ No movies found in database.", null, 'HTML');
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
            $grouped[$name] = ['name' => $name, 'count' => 0, 'qualities' => [], 'languages' => [], 'items' => []];
        }
        $grouped[$name]['count']++;
        $grouped[$name]['qualities'][$movie['quality']] = true;
        $grouped[$name]['languages'][$movie['language']] = true;
        $grouped[$name]['items'][] = $movie;
    }
    
    $sent_count = 0;
    foreach ($grouped as $group) {
        $first_item = $group['items'][0];
        deliver_item_to_chat($chat_id, $first_item, $user_id);
        $sent_count++;
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
        ['text' => '🔍 ' . t('search', $user_id), 'switch_inline_query_current_chat' => ''],
        ['text' => '🏠 ' . t('home', $user_id), 'callback_data' => 'back_home']
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

function show_stats($chat_id, $detailed = false, $user_id = null) {
    global $csvManager, $requestSystem;
    
    sendChatAction($chat_id, 'typing');
    
    $csv_stats = $csvManager->getStats();
    $request_stats = $requestSystem->getStats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    $file_stats = json_decode(file_get_contents(STATS_FILE), true);
    
    $channels_text = "";
    foreach ($csv_stats['channels'] as $channel_id => $count) {
        $channels_text .= "• " . getChannelUsername($channel_id) . ": " . $count . "\n";
    }
    
    $text = "📊 <b>" . t('stats', $user_id) . "</b>\n\n";
    $text .= "🎬 Total Movies: {$csv_stats['total_movies']}\n";
    $text .= "📺 Total Series: {$csv_stats['total_series']}\n";
    $text .= "👥 Total Users: $total_users\n";
    $text .= "🔍 Total Searches: " . ($file_stats['total_searches'] ?? 0) . "\n";
    $text .= "📋 Total Requests: {$request_stats['total_requests']}\n";
    $text .= "⏳ Pending: {$request_stats['pending']}\n\n";
    
    if ($detailed) {
        $text .= "🎥 By Quality:\n";
        foreach ($csv_stats['qualities'] as $q => $c) $text .= "  • $q: $c\n";
        $text .= "\n🗣️ By Language:\n";
        foreach ($csv_stats['languages'] as $l => $c) $text .= "  • $l: $c\n";
        $text .= "\n📡 By Channel:\n" . $channels_text;
    }
    
    $keyboard = ['inline_keyboard' => [
        [['text' => $detailed ? '📊 Simple' : '📈 Detailed', 'callback_data' => $detailed ? 'stats_simple' : 'stats_detailed']],
        [['text' => '🔄 ' . t('refresh', $user_id), 'callback_data' => $detailed ? 'stats_detailed' : 'stats_simple']],
        [['text' => '🏠 ' . t('home', $user_id), 'callback_data' => 'back_home']]
    ]];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function show_trending($chat_id, $user_id = null) {
    global $csvManager;
    
    $trending = $csvManager->getTrending(10);
    
    $text = "🔥 <b>" . t('trending', $user_id) . "</b>\n\n";
    $i = 1;
    foreach ($trending as $item) {
        $type = $item['is_series'] ? '📺' : '🎬';
        $text .= "$i. $type <b>{$item['movie_name']}</b>\n";
        $text .= "   {$item['quality']} | {$item['language']} | {$item['size']}\n\n";
        $i++;
    }
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '🎥 ' . t('quality', $user_id), 'callback_data' => 'menu_quality']],
        [['text' => '🗣️ ' . t('language_filter', $user_id), 'callback_data' => 'menu_language']],
        [['text' => '🏠 ' . t('home', $user_id), 'callback_data' => 'back_home']]
    ]];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== SETTINGS COMMANDS ====================
function cmd_settings($chat_id, $user_id) {
    $settings = SettingsManager::getInstance()->getSettings($user_id);
    $lang = $user_id;
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '⏱️ File Delete Timer: ' . $settings['timer'] . 's', 'callback_data' => 'menu_timer']],
            [['text' => '📡 Auto Scan: ' . ($settings['auto_scan'] ? '✅ ON' : '❌ OFF'), 'callback_data' => 'toggle_autoscan']],
            [['text' => '🎭 Spoiler Mode: ' . ($settings['spoiler_mode'] ? '✅ ON' : '❌ OFF'), 'callback_data' => 'toggle_spoiler']],
            [['text' => '🔥 Top Search: ' . ($settings['top_search'] ? '✅ ON' : '❌ OFF'), 'callback_data' => 'toggle_topsearch']],
            [['text' => '📊 Priority: ' . ucfirst($settings['priority']), 'callback_data' => 'menu_priority']],
            [['text' => '🎨 Layout: ' . ucfirst($settings['layout']), 'callback_data' => 'menu_layout']],
            [['text' => '🔄 Reset All', 'callback_data' => 'reset_settings']],
            [['text' => '🔙 ' . t('back', $lang), 'callback_data' => 'back_home']]
        ]
    ];
    
    sendMessage($chat_id, "⚙️ <b>" . t('settings', $lang) . "</b>", $keyboard, 'HTML');
}

function cmd_timer($chat_id, $user_id) {
    $lang = $user_id;
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '⏳ 30 seconds', 'callback_data' => 'timer_30']],
            [['text' => '⏳ 60 seconds', 'callback_data' => 'timer_60']],
            [['text' => '⏳ 90 seconds', 'callback_data' => 'timer_90']],
            [['text' => '⏳ 120 seconds', 'callback_data' => 'timer_120']],
            [['text' => '🚫 Disable', 'callback_data' => 'timer_off']],
            [['text' => '🔙 ' . t('back', $lang), 'callback_data' => 'back_settings']]
        ]
    ];
    
    sendMessage($chat_id, "⏱️ <b>File Delete Timer</b>", $keyboard, 'HTML');
}

// ==================== ADMIN FUNCTIONS ====================
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
        $text .= "⏳ Recent Pending Requests:\n";
        foreach ($pending as $req) {
            $text .= "• #{$req['id']}: {$req['movie_name']} - " . substr($req['created_at'], 0, 10) . "\n";
        }
    }
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '⏳ View All Pending', 'callback_data' => 'admin_pending']],
        [['text' => '📊 Detailed Stats', 'callback_data' => 'admin_stats']],
        [['text' => '📡 Channel Monitor', 'callback_data' => 'admin_monitor']],
        [['text' => '📋 Bulk Actions', 'callback_data' => 'admin_bulk']],
        [['text' => '📈 Analytics', 'callback_data' => 'admin_analytics']],
        [['text' => '🔙 Back', 'callback_data' => 'back_home']]
    ]];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

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
    $keyboard['inline_keyboard'][] = [
        ['text' => '✅ Bulk Approve', 'callback_data' => 'bulk_approve_page'],
        ['text' => '❌ Bulk Reject', 'callback_data' => 'bulk_reject_page']
    ];
    $keyboard['inline_keyboard'][] = [['text' => '🔙 Back to Admin', 'callback_data' => 'admin_back']];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

function admin_bulk_actions($chat_id, $user_id, $action, $request_ids = []) {
    global $requestSystem;
    
    if (!in_array($user_id, ADMIN_IDS)) return;
    
    if (empty($request_ids)) {
        $pending = $requestSystem->getPendingRequests(100);
        $request_ids = array_column($pending, 'id');
    }
    
    $total = count($request_ids);
    $success = 0;
    $failed = 0;
    
    $status_msg = sendMessage($chat_id, "🔄 Processing {$total} requests...", null, 'HTML');
    
    foreach ($request_ids as $req_id) {
        if ($action == 'approve') {
            $result = $requestSystem->approveRequest($req_id, $user_id);
        } else {
            $result = $requestSystem->rejectRequest($req_id, $user_id, 'Bulk action');
        }
        
        if ($result['success']) {
            $success++;
            $request = $result['request'];
            $notify_msg = $action == 'approve' 
                ? "✅ Your request #$req_id for '{$request['movie_name']}' has been approved!"
                : "❌ Your request #$req_id for '{$request['movie_name']}' has been rejected.";
            sendMessage($request['user_id'], $notify_msg, null, 'HTML');
        } else {
            $failed++;
        }
    }
    
    $text = "✅ <b>Bulk " . ucfirst($action) . " Completed</b>\n\n";
    $text .= "📊 Results:\n";
    $text .= "• Total: $total\n";
    $text .= "• ✅ Success: $success\n";
    $text .= "• ❌ Failed: $failed\n";
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '📊 View Pending', 'callback_data' => 'admin_pending']],
        [['text' => '🔙 Back', 'callback_data' => 'admin_back']]
    ]];
    
    editMessageText($chat_id, $status_msg['result']['message_id'], $text, $keyboard, 'HTML');
}

function admin_bulk_menu($chat_id, $msg_id) {
    global $requestSystem;
    
    $pending = $requestSystem->getPendingRequests(100);
    $total_pending = count($pending);
    
    $text = "📋 <b>BULK ACTIONS</b>\n\n";
    $text .= "📊 Pending Requests: $total_pending\n\n";
    $text .= "Choose action:";
    
    $keyboard = ['inline_keyboard' => [
        [
            ['text' => "✅ Approve All ($total_pending)", 'callback_data' => 'bulk_approve_all'],
            ['text' => "❌ Reject All ($total_pending)", 'callback_data' => 'bulk_reject_all']
        ],
        [
            ['text' => '✅ Approve Page', 'callback_data' => 'bulk_approve_page'],
            ['text' => '❌ Reject Page', 'callback_data' => 'bulk_reject_page']
        ],
        [
            ['text' => '🔙 Back', 'callback_data' => 'admin_back']
        ]
    ]];
    
    editMessageText($chat_id, $msg_id, $text, $keyboard, 'HTML');
}

function show_analytics_dashboard($chat_id, $user_id) {
    if (!in_array($user_id, ADMIN_IDS)) return;
    
    global $csvManager, $requestSystem, $downloadTracker;
    
    $csv_stats = $csvManager->getStats();
    $request_stats = $requestSystem->getStats();
    $download_data = $downloadTracker->loadData();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    $total_users = count($users_data['users'] ?? []);
    $active_today = count(array_filter($users_data['users'] ?? [], fn($u) => strtotime($u['last_active'] ?? '2000-01-01') > strtotime('-24 hours')));
    
    $text = "📊 <b>ANALYTICS DASHBOARD</b>\n\n";
    
    $text .= "📈 <b>Overview:</b>\n";
    $text .= "• Total Users: $total_users\n";
    $text .= "• Active Today: $active_today\n";
    $text .= "• Total Downloads: {$download_data['total_downloads']}\n\n";
    
    $text .= "🎬 <b>Content:</b>\n";
    $text .= "• Movies: {$csv_stats['total_movies']}\n";
    $text .= "• Series: {$csv_stats['total_series']}\n\n";
    
    $text .= "📋 <b>Requests:</b>\n";
    $text .= "• Total: {$request_stats['total_requests']}\n";
    $text .= "• Pending: {$request_stats['pending']}\n";
    $text .= "• Approved: {$request_stats['approved']}\n";
    $text .= "• Rejected: {$request_stats['rejected']}\n\n";
    
    $text .= "📅 <b>Last 7 Days Downloads:</b>\n";
    $daily = $download_data['daily'] ?? [];
    $dates = array_slice(array_keys($daily), -7);
    foreach ($dates as $date) {
        $text .= "• $date: {$daily[$date]} downloads\n";
    }
    
    $keyboard = ['inline_keyboard' => [
        [['text' => '🔄 Refresh', 'callback_data' => 'admin_analytics']],
        [['text' => '🔙 Back', 'callback_data' => 'admin_back']]
    ]];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== USER FUNCTIONS ====================
function user_myrequests($chat_id, $user_id) {
    global $requestSystem;
    
    $requests = $requestSystem->getUserRequests($user_id);
    
    if (empty($requests)) {
        sendMessage($chat_id, "📭 You haven't made any requests yet.\n\nUse /request MovieName to request.", null, 'HTML');
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
        [['text' => '🔄 Check Status', 'callback_data' => 'user_check_status']],
        [['text' => '📜 History', 'callback_data' => 'user_history']],
        [['text' => '⭐ Favorites', 'callback_data' => 'user_favorites']],
        [['text' => '🏠 Home', 'callback_data' => 'back_home']]
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
    $force = in_array('force', $parts);
    $backup_first = in_array('backup', $parts);
    $format = 'csv';
    
    foreach ($parts as $part) {
        if (strpos($part, 'format=') === 0) $format = str_replace('format=', '', $part);
    }
    
    $text = "📥 <b>BULK IMPORT MOVIES</b>\n\n";
    $text .= "Send me a <b>{$format}</b> file with movie data.\n\n";
    
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
    $text .= "• /import_old force - Skip duplicate checks\n";
    $text .= "• /import_old backup - Create backup first\n";
    $text .= "• /import_old format=json - Use JSON format\n\n";
    
    $stats = CSVManager::getInstance()->getStats();
    $text .= "<b>Current Stats:</b>\n";
    $text .= "• Total Movies: {$stats['total_movies']}\n";
    
    $import_session = ['user_id' => $user_id, 'chat_id' => $chat_id, 'dry_run' => $dry_run, 'force' => $force, 'backup_first' => $backup_first, 'format' => $format, 'timestamp' => time()];
    file_put_contents('import_session.json', json_encode($import_session));
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '📥 Download Template', 'callback_data' => 'import_template']],
            [['text' => '🔍 Dry Run: ' . ($dry_run ? '✅' : '❌'), 'callback_data' => 'import_toggle_dryrun']],
            [['text' => '⚡ Force: ' . ($force ? '✅' : '❌'), 'callback_data' => 'import_toggle_force']],
            [['text' => '💾 Backup: ' . ($backup_first ? '✅' : '❌'), 'callback_data' => 'import_toggle_backup']],
            [['text' => '📊 Format: ' . strtoupper($format), 'callback_data' => 'import_toggle_format']],
            [['text' => '❌ Cancel', 'callback_data' => 'import_cancel']]
        ]
    ];
    
    sendMessage($chat_id, $text, $keyboard, 'HTML');
}

// ==================== MAINTENANCE CHECK ====================
if (MAINTENANCE_MODE) {
    $update = json_decode(file_get_contents('php://input'), true);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, "🛠️ <b>Bot Under Maintenance</b>\n\nWe'll be back soon!", null, 'HTML');
    }
    exit;
}

// ==================== INITIALIZE MANAGERS ====================
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();
$settingsManager = SettingsManager::getInstance();
$langManager = LanguageManager::getInstance();
$downloadTracker = DownloadTracker::getInstance();
$recommendationEngine = RecommendationEngine::getInstance();
$favoritesManager = FavoritesManager::getInstance();
$seriesManager = SeriesManager::getInstance();
$requestHistory = RequestHistory::getInstance();

// ==================== WEBHOOK SETUP ====================
if (isset($_GET['setup'])) {
    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1><h2>Webhook Setup</h2><pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1><h2>Webhook Deleted</h2><pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

if (isset($_GET['test'])) {
    $csv_stats = $csvManager->getStats();
    $request_stats = $requestSystem->getStats();
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot - Test Page</h1>";
    echo "<p>Status: ✅ Running</p>";
    echo "<p>Movies: {$csv_stats['total_movies']}</p>";
    echo "<p>Series: {$csv_stats['total_series']}</p>";
    echo "<p>Users: " . count($users_data['users'] ?? []) . "</p>";
    echo "<p>Requests: {$request_stats['total_requests']}</p>";
    exit;
}

// ==================== GET UPDATE ====================
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    show_html_page();
    exit;
}

log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);

RateLimiter::check($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 'telegram_update', 30, 60);

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
        $text = $message['caption'] ?? $message['text'] ?? $message['document']['file_name'] ?? 'Media - ' . date('d-m-Y H:i');
        
        if (!empty(trim($text))) {
            $movie_name = enhanced_extract_movie_name($text);
            $quality = enhanced_extract_quality($text);
            $language = enhanced_extract_language($text);
            $size = extract_size($text);
            $year = extract_year($text);
            $is_series = enhanced_is_series($text);
            $season = extract_season($text);
            $episode = extract_episode($text);
            
            $csvManager->bufferedAppend($movie_name, $message_id, $chat_id, [
                'quality' => $quality, 'language' => $language, 'size' => $size, 'year' => $year,
                'is_series' => $is_series, 'season' => $season, 'episode' => $episode
            ]);
            
            log_error("Auto-saved: $movie_name", 'INFO', ['quality' => $quality, 'language' => $language]);
            
            $auto_approved = $requestSystem->checkAutoApprove($movie_name);
            if (!empty($auto_approved)) log_error("Auto-approved: " . implode(',', $auto_approved), 'INFO');
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
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true) ?: ['users' => []];
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'first_name' => $message['from']['first_name'] ?? '',
            'username' => $message['from']['username'] ?? '',
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
    
    $lang = getUserLanguage($user_id);
    
    if (strpos($text, '/') === 0) {
        $parts = explode(' ', $text);
        $command = strtolower($parts[0]);
        
        if ($command == '/start' || $command == '/home') {
            $welcome = t('welcome', $user_id);
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔍 ' . t('search', $user_id), 'switch_inline_query_current_chat' => '']],
                    [['text' => '⚙️ ' . t('settings', $user_id), 'callback_data' => 'menu_settings'], 
                     ['text' => '📊 ' . t('stats', $user_id), 'callback_data' => 'show_stats']],
                    [['text' => '🔥 ' . t('trending', $user_id), 'callback_data' => 'show_trending'], 
                     ['text' => '📝 ' . t('requests', $user_id), 'callback_data' => 'my_requests']],
                    [['text' => '📺 ' . t('series', $user_id), 'callback_data' => 'series_menu_main'], 
                     ['text' => '⭐ ' . t('favorites', $user_id), 'callback_data' => 'user_favorites']],
                    [['text' => '📢 ' . t('channels', $user_id), 'callback_data' => 'show_channels'], 
                     ['text' => '🌐 ' . t('language', $user_id), 'callback_data' => 'change_language']]
                ]
            ];
            sendMessage($chat_id, $welcome, $keyboard, 'HTML');
        }
        elseif ($command == '/help') {
            $help = "🤖 <b>Commands</b>\n\n" .
                    "/start - Home\n/settings - Settings\n/request [movie] - Request\n/myrequests - Your requests\n" .
                    "/history - History\n/favorites - Favorites\n/recommend - Recommendations\n/series [name] - Series\n" .
                    "/trending - Trending\n/popularity - Stats\n/language - Change language\n/stats - Bot stats\n" .
                    "/totaluploads - Browse all\n" .
                    (in_array($user_id, ADMIN_IDS) ? "\n👑 Admin:\n/pending\n/approve [id]\n/reject [id]\n/bulk\n/analytics\n/import_old\n/clearcache" : "");
            sendMessage($chat_id, $help, null, 'HTML');
        }
        elseif ($command == '/settings') cmd_settings($chat_id, $user_id);
        elseif ($command == '/settimer') cmd_timer($chat_id, $user_id);
        elseif ($command == '/autoscan') {
            $value = $parts[1] ?? 'toggle';
            $settingsManager->updateSettings($user_id, 'auto_scan', $value == 'on');
            sendMessage($chat_id, "✅ Auto Scan " . ($value == 'on' ? 'ON' : 'OFF'), null, 'HTML');
        }
        elseif ($command == '/spoiler') {
            $value = $parts[1] ?? 'toggle';
            $settingsManager->updateSettings($user_id, 'spoiler_mode', $value == 'on');
            sendMessage($chat_id, "✅ Spoiler Mode " . ($value == 'on' ? 'ON' : 'OFF'), null, 'HTML');
        }
        elseif ($command == '/topsearch') {
            $value = $parts[1] ?? 'toggle';
            $settingsManager->updateSettings($user_id, 'top_search', $value == 'on');
            sendMessage($chat_id, "✅ Top Search " . ($value == 'on' ? 'ON' : 'OFF'), null, 'HTML');
        }
        elseif ($command == '/priority') {
            $settingsManager->updateSettings($user_id, 'priority', $parts[1] ?? 'quality');
            sendMessage($chat_id, "✅ Priority set", null, 'HTML');
        }
        elseif ($command == '/layout') {
            $settingsManager->updateSettings($user_id, 'layout', $parts[1] ?? 'buttons');
            sendMessage($chat_id, "✅ Layout set", null, 'HTML');
        }
        elseif ($command == '/reset') {
            $settingsManager->resetSettings($user_id);
            sendMessage($chat_id, "✅ Settings reset", null, 'HTML');
        }
        elseif ($command == '/trending' || $command == '/popular') show_trending($chat_id, $user_id);
        elseif ($command == '/popularity') $downloadTracker->displayPopularity($chat_id, $user_id);
        elseif ($command == '/recent') {
            $recent = $csvManager->getTrending(10);
            $text = "🕒 <b>RECENT UPLOADS</b>\n\n";
            foreach ($recent as $item) {
                $type = $item['is_series'] ? '📺' : '🎬';
                $text .= "$type {$item['movie_name']} - {$item['quality']}\n";
            }
            sendMessage($chat_id, $text, null, 'HTML');
        }
        elseif ($command == '/quick' && isset($parts[1])) {
            $movie = implode(' ', array_slice($parts, 1));
            advanced_search_with_filters($chat_id, $movie, $user_id);
        }
        elseif ($command == '/qualities' && isset($parts[1])) {
            $movie = implode(' ', array_slice($parts, 1));
            show_quality_filter_menu($chat_id, $message_id, $movie, $user_id);
        }
        elseif ($command == '/versions' && isset($parts[1])) {
            $movie = implode(' ', array_slice($parts, 1));
            deliver_movie($chat_id, $movie, $user_id);
        }
        elseif ($command == '/send' && isset($parts[1])) {
            $movie = implode(' ', array_slice($parts, 1));
            deliver_movie($chat_id, $movie, $user_id);
        }
        elseif ($command == '/sendall' && isset($parts[1])) {
            $movie = implode(' ', array_slice($parts, 1));
            $all = $csvManager->getCachedData();
            $items = array_filter($all, fn($i) => strpos(strtolower($i['movie_name']), strtolower($movie)) !== false);
            send_all_versions($chat_id, $items, $user_id);
        }
        elseif ($command == '/series' && isset($parts[1])) {
            $series_name = implode(' ', array_slice($parts, 1));
            $seasons = $seriesManager->getSeasons($series_name);
            if (empty($seasons)) {
                sendMessage($chat_id, "❌ Series not found", null, 'HTML');
            } else {
                $seriesManager->displaySeriesMenu($chat_id, $message_id, $series_name, $user_id);
            }
        }
        elseif ($command == '/history') $requestHistory->displayHistory($chat_id, $user_id);
        elseif ($command == '/favorites') $favoritesManager->displayFavorites($chat_id, $user_id);
        elseif ($command == '/recommend') $recommendationEngine->displayRecommendations($chat_id, $user_id);
        elseif ($command == '/filter') {
            if (isset($parts[1]) && $parts[1] == 'quality') show_quality_filter_menu($chat_id, $message_id, '', $user_id);
            elseif (isset($parts[1]) && $parts[1] == 'language') show_language_filter_menu($chat_id, $message_id, '', $user_id);
        }
        elseif ($command == '/back') {
            $welcome = t('welcome', $user_id);
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔍 ' . t('search', $user_id), 'switch_inline_query_current_chat' => '']],
                    [['text' => '⚙️ ' . t('settings', $user_id), 'callback_data' => 'menu_settings'], 
                     ['text' => '📊 ' . t('stats', $user_id), 'callback_data' => 'show_stats']],
                    [['text' => '🔥 ' . t('trending', $user_id), 'callback_data' => 'show_trending'], 
                     ['text' => '📝 ' . t('requests', $user_id), 'callback_data' => 'my_requests']],
                    [['text' => '📺 ' . t('series', $user_id), 'callback_data' => 'series_menu_main'], 
                     ['text' => '⭐ ' . t('favorites', $user_id), 'callback_data' => 'user_favorites']]
                ]
            ];
            sendMessage($chat_id, $welcome, $keyboard, 'HTML');
        }
        elseif ($command == '/request') {
            if (!isset($parts[1])) {
                sendMessage($chat_id, "📝 Use: /request Movie Name", null, 'HTML');
                return;
            }
            $movie = implode(' ', array_slice($parts, 1));
            $user_name = $message['from']['first_name'] ?? '';
            $result = $requestSystem->submitRequest($user_id, $movie, $user_name);
            
            if ($result['success']) {
                sendMessage($chat_id, $result['message'], null, 'HTML');
                foreach (ADMIN_IDS as $admin) {
                    sendMessage($admin, "📝 New request #{$result['request_id']}: $movie from $user_name", null, 'HTML');
                }
            } else {
                sendMessage($chat_id, $result['message'], null, 'HTML');
            }
        }
        elseif ($command == '/myrequests') user_myrequests($chat_id, $user_id);
        elseif ($command == '/status' && isset($parts[1])) {
            $req_id = intval($parts[1]);
            $request = $requestSystem->getRequest($req_id);
            if (!$request) sendMessage($chat_id, "❌ Request not found", null, 'HTML');
            elseif ($request['user_id'] != $user_id && !in_array($user_id, ADMIN_IDS)) sendMessage($chat_id, "❌ Unauthorized", null, 'HTML');
            else {
                $status_icon = $request['status'] == 'approved' ? '✅' : ($request['status'] == 'rejected' ? '❌' : '⏳');
                $text = "$status_icon <b>Request #{$request['id']}</b>\n\nMovie: {$request['movie_name']}\nDate: " . date('d M Y H:i', strtotime($request['created_at'])) . "\nStatus: " . ucfirst($request['status']);
                sendMessage($chat_id, $text, null, 'HTML');
            }
        }
        elseif ($command == '/pending' && in_array($user_id, ADMIN_IDS)) admin_pending_list($chat_id, $user_id);
        elseif ($command == '/approve' && isset($parts[1]) && in_array($user_id, ADMIN_IDS)) {
            $req_id = intval($parts[1]);
            $result = $requestSystem->approveRequest($req_id, $user_id);
            if ($result['success']) {
                sendMessage($chat_id, $result['message'], null, 'HTML');
                sendMessage($result['request']['user_id'], "✅ Request #$req_id approved!", null, 'HTML');
            } else sendMessage($chat_id, $result['message'], null, 'HTML');
        }
        elseif ($command == '/reject' && isset($parts[1]) && in_array($user_id, ADMIN_IDS)) {
            $req_id = intval($parts[1]);
            $reason = implode(' ', array_slice($parts, 2)) ?: 'Not specified';
            $result = $requestSystem->rejectRequest($req_id, $user_id, $reason);
            if ($result['success']) {
                sendMessage($chat_id, $result['message'], null, 'HTML');
                sendMessage($result['request']['user_id'], "❌ Request #$req_id rejected.\nReason: $reason", null, 'HTML');
            } else sendMessage($chat_id, $result['message'], null, 'HTML');
        }
        elseif ($command == '/stats' && in_array($user_id, ADMIN_IDS)) show_stats($chat_id, true, $user_id);
        elseif ($command == '/livestats') show_stats($chat_id, false, $user_id);
        elseif ($command == '/analytics' && in_array($user_id, ADMIN_IDS)) show_analytics_dashboard($chat_id, $user_id);
        elseif ($command == '/bulk' && in_array($user_id, ADMIN_IDS)) admin_bulk_menu($chat_id, $message_id);
        elseif ($command == '/channels') {
            $channels_text = "";
            foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $c) $channels_text .= "🌐 {$c['username']}\n";
            foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $c) $channels_text .= "🔒 {$c['username']}\n";
            sendMessage($chat_id, "📢 <b>Channels</b>\n\n$channels_text", null, 'HTML');
        }
        elseif ($command == '/language' || $command == '/lang') {
            sendMessage($chat_id, "🌐 <b>" . t('language', $user_id) . "</b>", $langManager->getLanguageMenu(), 'HTML');
        }
        elseif ($command == '/totaluploads' || $command == '/totalupload' || $command == '/total') {
            $page = isset($parts[1]) && is_numeric($parts[1]) ? intval($parts[1]) : 1;
            totalupload_controller($chat_id, $page, $user_id);
        }
        elseif ($command == '/clearcache' && in_array($user_id, ADMIN_IDS)) {
            $csvManager->clearCache();
            sendMessage($chat_id, "✅ Cache cleared!", null, 'HTML');
        }
        elseif ($command == '/import_old' && in_array($user_id, ADMIN_IDS)) {
            cmd_import_old($chat_id, $user_id, array_slice($parts, 1));
        }
        else sendMessage($chat_id, "❌ Unknown command. Type /help", null, 'HTML');
    }
    elseif (preg_match('/(add|request|pls|please).+(movie|series)/i', $text)) {
        $movie = preg_replace('/(add|request|pls|please|movie|series)/i', '', $text);
        $movie = trim($movie);
        if (strlen($movie) > 2) {
            $result = $requestSystem->submitRequest($user_id, $movie, $message['from']['first_name'] ?? '');
            sendMessage($chat_id, $result['message'], null, 'HTML');
        }
    }
    elseif (!empty(trim($text))) {
        advanced_search_with_filters($chat_id, $text, $user_id);
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
    sendChatAction($chat_id, 'typing');
    
    if ($data == 'menu_settings') { cmd_settings($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'menu_timer') { cmd_timer($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif (strpos($data, 'timer_') === 0) {
        $time = str_replace('timer_', '', $data);
        $settingsManager->updateSettings($user_id, 'timer', $time == 'off' ? 0 : intval($time));
        sendMessage($chat_id, "⏱️ Timer set to " . ($time == 'off' ? 'OFF' : $time . 's'), null, 'HTML');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'toggle_autoscan') {
        $settings = $settingsManager->getSettings($user_id);
        $settingsManager->updateSettings($user_id, 'auto_scan', !$settings['auto_scan']);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'toggle_spoiler') {
        $settings = $settingsManager->getSettings($user_id);
        $settingsManager->updateSettings($user_id, 'spoiler_mode', !$settings['spoiler_mode']);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'toggle_topsearch') {
        $settings = $settingsManager->getSettings($user_id);
        $settingsManager->updateSettings($user_id, 'top_search', !$settings['top_search']);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'menu_priority') {
        $keyboard = ['inline_keyboard' => [
            [['text' => '🎥 Quality', 'callback_data' => 'set_priority_quality']],
            [['text' => '📊 Size', 'callback_data' => 'set_priority_size']],
            [['text' => '🔙 Back', 'callback_data' => 'back_settings']]
        ]];
        editMessageText($chat_id, $msg_id, "📊 Choose priority:", $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'set_priority_quality') {
        $settingsManager->updateSettings($user_id, 'priority', 'quality');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id'], "Priority: Quality");
    }
    elseif ($data == 'set_priority_size') {
        $settingsManager->updateSettings($user_id, 'priority', 'size');
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id'], "Priority: Size");
    }
    elseif ($data == 'menu_layout') {
        $keyboard = ['inline_keyboard' => [
            [['text' => '🔘 Buttons', 'callback_data' => 'set_layout_buttons']],
            [['text' => '📝 Text', 'callback_data' => 'set_layout_text']],
            [['text' => '🔙 Back', 'callback_data' => 'back_settings']]
        ]];
        editMessageText($chat_id, $msg_id, "🎨 Choose layout:", $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
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
    elseif ($data == 'reset_settings') {
        $settingsManager->resetSettings($user_id);
        cmd_settings($chat_id, $user_id);
        answerCallbackQuery($query['id'], "Settings reset!");
    }
    elseif ($data == 'back_settings') { cmd_settings($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'back_home') {
        $welcome = t('welcome', $user_id);
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔍 ' . t('search', $user_id), 'switch_inline_query_current_chat' => '']],
                [['text' => '⚙️ ' . t('settings', $user_id), 'callback_data' => 'menu_settings'], 
                 ['text' => '📊 ' . t('stats', $user_id), 'callback_data' => 'show_stats']],
                [['text' => '🔥 ' . t('trending', $user_id), 'callback_data' => 'show_trending'], 
                 ['text' => '📝 ' . t('requests', $user_id), 'callback_data' => 'my_requests']],
                [['text' => '📺 ' . t('series', $user_id), 'callback_data' => 'series_menu_main'], 
                 ['text' => '⭐ ' . t('favorites', $user_id), 'callback_data' => 'user_favorites']]
            ]
        ];
        editMessageText($chat_id, $msg_id, $welcome, $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'show_stats') { show_stats($chat_id, false, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'stats_detailed') { show_stats($chat_id, true, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'stats_simple') { show_stats($chat_id, false, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'show_trending') { show_trending($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'show_popularity') { $downloadTracker->displayPopularity($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif (strpos($data, 'filter_menu_quality_') === 0) {
        $query = base64_decode(str_replace('filter_menu_quality_', '', $data));
        show_quality_filter_menu($chat_id, $msg_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'filter_menu_lang_') === 0) {
        $query = base64_decode(str_replace('filter_menu_lang_', '', $data));
        show_language_filter_menu($chat_id, $msg_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'filter_quality_') === 0) {
        $parts = explode('_', $data, 4);
        $quality = $parts[2];
        $query = base64_decode($parts[3] ?? '');
        advanced_search_with_filters($chat_id, $query, $user_id, ['quality' => $quality]);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'filter_lang_') === 0) {
        $parts = explode('_', $data, 4);
        $lang = $parts[2];
        $query = base64_decode($parts[3] ?? '');
        advanced_search_with_filters($chat_id, $query, $user_id, ['language' => $lang]);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'filter_toggle_series_') === 0) {
        $query = base64_decode(str_replace('filter_toggle_series_', '', $data));
        advanced_search_with_filters($chat_id, $query, $user_id, ['is_series' => true]);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'movie_') === 0) {
        $movie_name = base64_decode(str_replace('movie_', '', $data));
        deliver_movie($chat_id, $movie_name, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'send_') === 0) {
        $parts = explode('_', $data, 4);
        $movie = base64_decode($parts[1]);
        $quality = $parts[2] ?? null;
        $lang = $parts[3] ?? null;
        deliver_movie($chat_id, $movie, $user_id, $quality, $lang);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'sendall_') === 0) {
        $movie = base64_decode(str_replace('sendall_', '', $data));
        $all = $csvManager->getCachedData();
        $items = array_filter($all, fn($i) => strpos(strtolower($i['movie_name']), strtolower($movie)) !== false);
        send_all_versions($chat_id, $items, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'series_menu_') === 0) {
        $series = base64_decode(str_replace('series_menu_', '', $data));
        $seriesManager->displaySeriesMenu($chat_id, $msg_id, $series, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'series_menu_main') {
        $all = $csvManager->getCachedData();
        $series_list = [];
        foreach ($all as $item) if ($item['is_series']) {
            $name = preg_replace('/\s+S\d{2}.*/', '', $item['movie_name']);
            $series_list[$name] = true;
        }
        $text = "📺 <b>" . t('series', $user_id) . "</b>\n\n";
        $keyboard = ['inline_keyboard' => []];
        foreach (array_slice(array_keys($series_list), 0, 10) as $s) {
            $keyboard['inline_keyboard'][] = [['text' => $s, 'callback_data' => 'series_menu_' . base64_encode($s)]];
        }
        $keyboard['inline_keyboard'][] = [['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'back_home']];
        editMessageText($chat_id, $msg_id, $text, $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'series_season_') === 0) {
        $parts = explode('_', $data, 4);
        $series = base64_decode($parts[2]);
        $season = $parts[3];
        $seriesManager->displaySeasonEpisodes($chat_id, $msg_id, $series, $season, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'series_episode_') === 0) {
        $parts = explode('_', $data, 5);
        $series = base64_decode($parts[2]);
        $season = $parts[3];
        $episode = $parts[4];
        $seriesManager->sendEpisode($chat_id, $series, $season, $episode);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'series_download_all_') === 0) {
        $series = base64_decode(str_replace('series_download_all_', '', $data));
        $seriesManager->sendSeries($chat_id, $series);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'season_download_all_') === 0) {
        $parts = explode('_', $data, 4);
        $series = base64_decode($parts[2]);
        $season = $parts[3];
        $seriesManager->sendSeason($chat_id, $series, $season);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'my_requests') { user_myrequests($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'user_history') { $requestHistory->displayHistory($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'user_favorites') { $favoritesManager->displayFavorites($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif (strpos($data, 'add_fav_') === 0) {
        $movie = base64_decode(str_replace('add_fav_', '', $data));
        $favoritesManager->addFavorite($user_id, $movie);
        answerCallbackQuery($query['id'], "⭐ Added to favorites!");
    }
    elseif (strpos($data, 'remove_fav_') === 0) {
        $movie = base64_decode(str_replace('remove_fav_', '', $data));
        $favoritesManager->removeFavorite($user_id, $movie);
        $favoritesManager->displayFavorites($chat_id, $user_id);
        answerCallbackQuery($query['id'], "❌ Removed");
    }
    elseif ($data == 'recommend_more') { $recommendationEngine->displayRecommendations($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif (strpos($data, 'similar_') === 0) {
        $movie = base64_decode(str_replace('similar_', '', $data));
        $recommendationEngine->displaySimilar($chat_id, $movie, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'admin_pending') { admin_pending_list($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif (strpos($data, 'admin_pending_') === 0) {
        $page = intval(str_replace('admin_pending_', '', $data));
        admin_pending_list($chat_id, $user_id, $page);
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'admin_approve_') === 0 && in_array($user_id, ADMIN_IDS)) {
        $req_id = intval(str_replace('admin_approve_', '', $data));
        $result = $requestSystem->approveRequest($req_id, $user_id);
        answerCallbackQuery($query['id'], $result['message']);
        if ($result['success']) {
            sendMessage($result['request']['user_id'], "✅ Request #$req_id approved!", null, 'HTML');
            admin_pending_list($chat_id, $user_id);
        }
    }
    elseif (strpos($data, 'admin_reject_') === 0 && in_array($user_id, ADMIN_IDS)) {
        $req_id = intval(str_replace('admin_reject_', '', $data));
        $result = $requestSystem->rejectRequest($req_id, $user_id, 'Rejected by admin');
        answerCallbackQuery($query['id'], $result['message']);
        if ($result['success']) {
            sendMessage($result['request']['user_id'], "❌ Request #$req_id rejected", null, 'HTML');
            admin_pending_list($chat_id, $user_id);
        }
    }
    elseif ($data == 'admin_back' && in_array($user_id, ADMIN_IDS)) { admin_panel($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'admin_bulk' && in_array($user_id, ADMIN_IDS)) { admin_bulk_menu($chat_id, $msg_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'bulk_approve_all' && in_array($user_id, ADMIN_IDS)) {
        $pending = $requestSystem->getPendingRequests(100);
        $ids = array_column($pending, 'id');
        admin_bulk_actions($chat_id, $user_id, 'approve', $ids);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'bulk_reject_all' && in_array($user_id, ADMIN_IDS)) {
        $pending = $requestSystem->getPendingRequests(100);
        $ids = array_column($pending, 'id');
        admin_bulk_actions($chat_id, $user_id, 'reject', $ids);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'bulk_approve_page' && in_array($user_id, ADMIN_IDS)) {
        $pending = $requestSystem->getPendingRequests(100);
        $page = 1;
        $per_page = 10;
        $start = ($page - 1) * $per_page;
        $page_pending = array_slice($pending, $start, $per_page);
        $ids = array_column($page_pending, 'id');
        admin_bulk_actions($chat_id, $user_id, 'approve', $ids);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'bulk_reject_page' && in_array($user_id, ADMIN_IDS)) {
        $pending = $requestSystem->getPendingRequests(100);
        $page = 1;
        $per_page = 10;
        $start = ($page - 1) * $per_page;
        $page_pending = array_slice($pending, $start, $per_page);
        $ids = array_column($page_pending, 'id');
        admin_bulk_actions($chat_id, $user_id, 'reject', $ids);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'admin_analytics' && in_array($user_id, ADMIN_IDS)) { show_analytics_dashboard($chat_id, $user_id); answerCallbackQuery($query['id']); }
    elseif ($data == 'admin_monitor' && in_array($user_id, ADMIN_IDS)) {
        $text = "📡 <b>CHANNEL MONITOR</b>\n\n";
        $stats = $csvManager->getStats();
        foreach ($stats['channels'] as $channel_id => $count) {
            $text .= "• " . getChannelUsername($channel_id) . ": $count movies\n";
        }
        sendMessage($chat_id, $text, null, 'HTML');
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'search_') === 0) {
        $query = base64_decode(str_replace('search_', '', $data));
        advanced_search_with_filters($chat_id, $query, $user_id);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'change_language') {
        editMessageText($chat_id, $msg_id, "🌐 <b>" . t('language', $user_id) . "</b>", $langManager->getLanguageMenu(), 'HTML');
        answerCallbackQuery($query['id']);
    }
    elseif (strpos($data, 'set_lang_') === 0) {
        $lang = str_replace('set_lang_', '', $data);
        setUserLanguage($user_id, $lang);
        answerCallbackQuery($query['id'], "Language set");
        $welcome = t('welcome', $user_id);
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔍 ' . t('search', $user_id), 'switch_inline_query_current_chat' => '']],
                [['text' => '⚙️ ' . t('settings', $user_id), 'callback_data' => 'menu_settings'], 
                 ['text' => '📊 ' . t('stats', $user_id), 'callback_data' => 'show_stats']],
                [['text' => '🔥 ' . t('trending', $user_id), 'callback_data' => 'show_trending'], 
                 ['text' => '📝 ' . t('requests', $user_id), 'callback_data' => 'my_requests']],
                [['text' => '📺 ' . t('series', $user_id), 'callback_data' => 'series_menu_main'], 
                 ['text' => '⭐ ' . t('favorites', $user_id), 'callback_data' => 'user_favorites']]
            ]
        ];
        editMessageText($chat_id, $msg_id, $welcome, $keyboard, 'HTML');
    }
    elseif ($data == 'show_channels') {
        $channels_text = "";
        $keyboard = ['inline_keyboard' => []];
        foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $c) {
            $channels_text .= "🌐 {$c['username']}\n";
            $keyboard['inline_keyboard'][] = [['text' => $c['username'], 'url' => 'https://t.me/' . ltrim($c['username'], '@')]];
        }
        foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $c) {
            $channels_text .= "🔒 {$c['username']}\n";
        }
        $keyboard['inline_keyboard'][] = [['text' => '🔙 ' . t('back', $user_id), 'callback_data' => 'back_home']];
        editMessageText($chat_id, $msg_id, "📢 <b>" . t('channels', $user_id) . "</b>\n\n$channels_text", $keyboard, 'HTML');
        answerCallbackQuery($query['id']);
    }
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
    elseif ($data == 'page_info') { answerCallbackQuery($query['id'], "You're on this page", true); }
    elseif ($data == 'import_template') {
        $format = 'csv';
        if (file_exists('import_session.json')) {
            $session = json_decode(file_get_contents('import_session.json'), true);
            $format = $session['format'] ?? 'csv';
        }
        $filename = "import_template.$format";
        if ($format === 'csv') {
            $content = "movie_name,message_id,channel_id,quality,language,size\nKGF Chapter 2,12345,-100123456789,1080p,Hindi,2.5 GB\nJawan,12346,-100123456789,4K,Hindi,4.8 GB\n";
        } else {
            $content = json_encode([["name" => "KGF Chapter 2", "msg_id" => 12345, "channel" => "-100123456789", "quality" => "1080p", "language" => "Hindi", "size" => "2.5 GB"]], JSON_PRETTY_PRINT);
        }
        file_put_contents($filename, $content);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.telegram.org/bot" . BOT_TOKEN . "/sendDocument",
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                'document' => new CURLFile($filename),
                'caption' => "📋 Import Template"
            ]
        ]);
        curl_exec($curl);
        curl_close($curl);
        unlink($filename);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'import_toggle_dryrun' && file_exists('import_session.json')) {
        $session = json_decode(file_get_contents('import_session.json'), true);
        $session['dry_run'] = !$session['dry_run'];
        $session['timestamp'] = time();
        file_put_contents('import_session.json', json_encode($session));
        $options = [];
        if ($session['dry_run']) $options[] = 'dry_run';
        if ($session['force']) $options[] = 'force';
        if ($session['backup_first']) $options[] = 'backup';
        cmd_import_old($chat_id, $user_id, $options);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'import_toggle_force' && file_exists('import_session.json')) {
        $session = json_decode(file_get_contents('import_session.json'), true);
        $session['force'] = !$session['force'];
        $session['timestamp'] = time();
        file_put_contents('import_session.json', json_encode($session));
        $options = [];
        if ($session['dry_run']) $options[] = 'dry_run';
        if ($session['force']) $options[] = 'force';
        if ($session['backup_first']) $options[] = 'backup';
        cmd_import_old($chat_id, $user_id, $options);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'import_toggle_backup' && file_exists('import_session.json')) {
        $session = json_decode(file_get_contents('import_session.json'), true);
        $session['backup_first'] = !$session['backup_first'];
        $session['timestamp'] = time();
        file_put_contents('import_session.json', json_encode($session));
        $options = [];
        if ($session['dry_run']) $options[] = 'dry_run';
        if ($session['force']) $options[] = 'force';
        if ($session['backup_first']) $options[] = 'backup';
        cmd_import_old($chat_id, $user_id, $options);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'import_toggle_format' && file_exists('import_session.json')) {
        $session = json_decode(file_get_contents('import_session.json'), true);
        $session['format'] = $session['format'] === 'csv' ? 'json' : 'csv';
        $session['timestamp'] = time();
        file_put_contents('import_session.json', json_encode($session));
        $options = ['format=' . $session['format']];
        if ($session['dry_run']) $options[] = 'dry_run';
        if ($session['force']) $options[] = 'force';
        if ($session['backup_first']) $options[] = 'backup';
        cmd_import_old($chat_id, $user_id, $options);
        answerCallbackQuery($query['id']);
    }
    elseif ($data == 'import_cancel') {
        if (file_exists('import_session.json')) unlink('import_session.json');
        editMessageText($chat_id, $msg_id, "❌ Import cancelled.", null, 'HTML');
        answerCallbackQuery($query['id']);
    }
    else { answerCallbackQuery($query['id'], "Processing..."); }
}

// ==================== HTML PAGE ====================
function show_html_page() {
    global $ENV_CONFIG, $csvManager, $requestSystem;
    
    $csv_stats = $csvManager->getStats();
    $request_stats = $requestSystem->getStats();
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    
    $theme = $_COOKIE['theme'] ?? 'dark';
    $theme_styles = $theme == 'light' ? "
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); color: #333; }
        .container { background: rgba(255,255,255,0.9); color: #333; }
        .stat-card { background: rgba(0,0,0,0.05); color: #333; }
        .feature-item { background: rgba(0,0,0,0.05); color: #333; }
    " : "
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { background: rgba(255,255,255,0.1); color: white; }
        .stat-card { background: rgba(255,255,255,0.15); color: white; }
        .feature-item { background: rgba(255,255,255,0.1); color: white; }
    ";
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>🎬 Entertainment Tadka Bot - 84 Features</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding: 20px; transition: all 0.3s; }
            <?php echo $theme_styles; ?>
            .container { max-width: 1200px; margin: 0 auto; backdrop-filter: blur(10px); border-radius: 20px; padding: 40px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
            h1 { text-align: center; margin-bottom: 30px; font-size: 2.8em; }
            .theme-switch { position: fixed; top: 20px; right: 20px; cursor: pointer; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 30px; }
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 20px; margin: 30px 0; }
            .stat-card { padding: 20px; border-radius: 12px; text-align: center; }
            .stat-value { font-size: 2.5em; font-weight: bold; color: #4CAF50; }
            .btn-group { display: flex; gap: 15px; justify-content: center; margin: 30px 0; flex-wrap: wrap; }
            .btn { padding: 14px 28px; border-radius: 10px; text-decoration: none; font-weight: bold; background: #4CAF50; color: white; transition: all 0.3s; }
            .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
            .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap: 20px; margin: 30px 0; }
            .feature-category { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; }
            .feature-category h3 { margin-bottom: 15px; color: #FFD700; border-bottom: 1px solid rgba(255,255,255,0.3); }
            .feature-list { list-style: none; }
            .feature-list li { margin: 8px 0; padding-left: 20px; position: relative; }
            .feature-list li::before { content: "✅"; position: absolute; left: -5px; color: #4CAF50; }
            footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); }
        </style>
        <script>
            function toggleTheme() {
                document.cookie = 'theme=' + (document.body.classList.contains('light') ? 'dark' : 'light') + '; path=/';
                location.reload();
            }
        </script>
    </head>
    <body>
        <div class="theme-switch" onclick="toggleTheme()">🌓 Toggle Theme</div>
        <div class="container">
            <h1>🎬 Entertainment Tadka Bot - 84 Features</h1>
            
            <div class="stats-grid">
                <div class="stat-card"><div>🎬 Movies</div><div class="stat-value"><?php echo $csv_stats['total_movies']; ?></div></div>
                <div class="stat-card"><div>📺 Series</div><div class="stat-value"><?php echo $csv_stats['total_series']; ?></div></div>
                <div class="stat-card"><div>👥 Users</div><div class="stat-value"><?php echo $total_users; ?></div></div>
                <div class="stat-card"><div>📋 Requests</div><div class="stat-value"><?php echo $request_stats['total_requests']; ?></div></div>
            </div>
            
            <div class="btn-group">
                <a href="?setup=1" class="btn">🔗 Set Webhook</a>
                <a href="?test=1" class="btn">🧪 Test Bot</a>
                <a href="https://t.me/<?php echo ltrim($ENV_CONFIG['BOT_USERNAME'], '@'); ?>" class="btn">📱 Open Bot</a>
            </div>
            
            <div class="features-grid">
                <div class="feature-category"><h3>⚙️ Settings (8)</h3><ul class="feature-list"><li>File Delete Timer</li><li>Auto Scan</li><li>Spoiler Mode</li><li>Top Search</li><li>Priority Sorting</li><li>Result Layout</li><li>Reset Settings</li><li>Settings Panel</li></ul></div>
                <div class="feature-category"><h3>🔍 Search (6)</h3><ul class="feature-list"><li>Smart Search</li><li>Search Stats</li><li>Auto Suggestions</li><li>Recent Searches</li><li>Fast Query</li><li>Search Filters</li></ul></div>
                <div class="feature-category"><h3>🎬 Movie (9)</h3><ul class="feature-list"><li>Multi-Quality</li><li>File Details</li><li>Version Grouping</li><li>Duplicate Protection</li><li>Movie Extraction</li><li>Request System</li><li>User Attribution</li><li>Bulk Import</li><li>Similar Movies</li></ul></div>
                <div class="feature-category"><h3>📺 Series (9)</h3><ul class="feature-list"><li>Series Detection</li><li>Season Organizer</li><li>Episode Listing</li><li>Episode Grouping</li><li>Season Quality</li><li>Season Language</li><li>Series Navigation</li><li>Complete Series Manager</li><li>Season/Episode Download</li></ul></div>
                <div class="feature-category"><h3>🎯 Filters (8)</h3><ul class="feature-list"><li>Quality Filter</li><li>Language Filter</li><li>Season Filter</li><li>Episode Filter</li><li>Back Button</li><li>Previous Button</li><li>Middle Button</li><li>Next Button</li></ul></div>
                <div class="feature-category"><h3>🧭 Navigation (7)</h3><ul class="feature-list"><li>Back Button</li><li>Previous Button</li><li>Next Button</li><li>Middle Button</li><li>Home Button</li><li>Page Navigation</li><li>Breadcrumb Trail</li></ul></div>
                <div class="feature-category"><h3>📤 Send (5)</h3><ul class="feature-list"><li>Single Send</li><li>Bulk Send</li><li>File Forward</li><li>Media Copy</li><li>Channel Fetch</li></ul></div>
                <div class="feature-category"><h3>⏱️ Auto-Delete (4)</h3><ul class="feature-list"><li>Timer Message</li><li>Auto Cleanup</li><li>Delete Confirm</li><li>Timer Preview</li></ul></div>
                <div class="feature-category"><h3>👑 Admin (9)</h3><ul class="feature-list"><li>Pending View</li><li>Request Approve</li><li>Request Reject</li><li>Admin Notify</li><li>Bot Stats</li><li>Channel Monitor</li><li>Bulk Approve/Reject</li><li>Analytics Dashboard</li><li>Export Analytics</li></ul></div>
                <div class="feature-category"><h3>👤 User (8)</h3><ul class="feature-list"><li>My Requests</li><li>Status Check</li><li>Timer Check</li><li>Request History</li><li>Favorites</li><li>Download History</li><li>Personal Stats</li><li>Movie Recommendations</li></ul></div>
                <div class="feature-category"><h3>🌐 Language (4)</h3><ul class="feature-list"><li>Multi-language (EN/HI/TA/TE)</li><li>Language Detector</li><li>Hinglish Support</li><li>Translation System</li></ul></div>
                <div class="feature-category"><h3>📊 Analytics (4)</h3><ul class="feature-list"><li>Popularity Tracking</li><li>Download Count</li><li>User Activity</li><li>Trending Movies</li></ul></div>
                <div class="feature-category"><h3>🎨 UI/UX (3)</h3><ul class="feature-list"><li>Dark/Light Theme</li><li>Callback Buttons</li><li>Typing Indicators</li></ul></div>
            </div>
            
            <footer>
                <p>© <?php echo date('Y'); ?> Entertainment Tadka Bot | <b>84 Features</b> | All Callback Buttons | Multi-Language</p>
                <p>🚀 Ready for Deployment | ~5500 Lines of Code</p>
            </footer>
        </div>
    </body>
    </html>
    <?php
    exit;
}

http_response_code(200);
echo "OK";
?>