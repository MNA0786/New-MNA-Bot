<?php
// ==================== ERROR LOGGING SETUP ====================
function log_error($message, $type = 'ERROR', $context = []) {
    $log_entry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    // Write to error.log
    file_put_contents('error.log', $log_entry, FILE_APPEND);
    
    // Also log to PHP error log
    error_log($message);
    
    // If in development, also output
    if (getenv('ENVIRONMENT') === 'development') {
        echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
    }
}

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_error("PHP Error [$errno]: $errstr in $errfile on line $errline", 'PHP_ERROR');
    return false;
});

// Set exception handler
set_exception_handler(function($exception) {
    log_error("Uncaught Exception: " . $exception->getMessage(), 'EXCEPTION', [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

// Log script start
log_error("Bot script started", 'INFO', ['time' => date('Y-m-d H:i:s')]);

// ... REST OF YOUR EXISTING CODE FROM PREVIOUS RESPONSE ...
// Make sure to add log_error() calls in key functions:
// - In apiRequest() on failure
// - In CSV operations
// - In search functions
// - In delivery functions
?>
// Enable error reporting for debugging only
if (getenv('ENVIRONMENT') === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// ==================== ENVIRONMENT CONFIGURATION ====================
// All configuration from environment variables
$ENV_CONFIG = [
    // Bot Configuration
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot',
    'ADMIN_ID' => (int)(getenv('ADMIN_ID') ?: 1080317415),
    
    // Public Channels (Forward from will be visible)
    'PUBLIC_CHANNELS' => [
        [
            'id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395',
            'username' => getenv('PUBLIC_CHANNEL_1_USERNAME') ?: '@EntertainmentTadka786'
        ],
        [
            'id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1002831605258',
            'username' => getenv('PUBLIC_CHANNEL_2_USERNAME') ?: '@threater_print_movies'
        ],
        [
            'id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002964109368',
            'username' => getenv('PUBLIC_CHANNEL_3_USERNAME') ?: '@ETBackup'
        ]
    ],
    
    // Private Channels (Forward from will be hidden using copyMessage)
    'PRIVATE_CHANNELS' => [
        [
            'id' => getenv('PRIVATE_CHANNEL_1_ID') ?: '-1003251791991',
            'username' => getenv('PRIVATE_CHANNEL_1_USERNAME') ?: ''
        ],
        [
            'id' => getenv('PRIVATE_CHANNEL_2_ID') ?: '-1002337293281',
            'username' => getenv('PRIVATE_CHANNEL_2_USERNAME') ?: ''
        ],
        [
            'id' => getenv('PRIVATE_CHANNEL_3_ID') ?: '-1003614546520',
            'username' => getenv('PRIVATE_CHANNEL_3_USERNAME') ?: ''
        ]
    ],
    
    // Request Group (for reference only, not used for forwarding)
    'REQUEST_GROUP' => [
        'id' => getenv('REQUEST_GROUP_ID') ?: '-1003083386043',
        'username' => getenv('REQUEST_GROUP_USERNAME') ?: '@EntertainmentTadka7860'
    ],
    
    // App Configuration
    'API_ID' => getenv('API_ID') ?: 21944581,
    'API_HASH' => getenv('API_HASH') ?: '7b1c174a5cd3466e25a976c39a791737',
    
    // File Paths
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'BACKUP_DIR' => 'backups/',
    'CACHE_DIR' => 'cache/',
    
    // Settings
    'CACHE_EXPIRY' => 300, // 5 minutes
    'ITEMS_PER_PAGE' => 5,
    'CSV_BUFFER_SIZE' => 50
];

// Validate required configuration
if (empty($ENV_CONFIG['BOT_TOKEN'])) {
    http_response_code(500);
    die("❌ Bot Token not configured. Please set BOT_TOKEN environment variable.");
}

// Extract config to constants for backward compatibility
define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_ID', $ENV_CONFIG['ADMIN_ID']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);

// ==================== CSV MANAGER CLASS ====================
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
        // Create necessary directories
        if (!file_exists(BACKUP_DIR)) {
            @mkdir(BACKUP_DIR, 0777, true);
        }
        if (!file_exists(CACHE_DIR)) {
            @mkdir(CACHE_DIR, 0777, true);
        }
        
        // Initialize CSV with correct header if not exists
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id\n";
            file_put_contents(CSV_FILE, $header);
            @chmod(CSV_FILE, 0666);
        }
        
        // Initialize other files
        $files = [
            USERS_FILE => json_encode(['users' => [], 'total_requests' => 0, 'message_logs' => []], JSON_PRETTY_PRINT),
            STATS_FILE => json_encode([
                'total_movies' => 0, 
                'total_users' => 0, 
                'total_searches' => 0, 
                'last_updated' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT)
        ];
        
        foreach ($files as $file => $content) {
            if (!file_exists($file)) {
                file_put_contents($file, $content);
                @chmod($file, 0666);
            }
        }
    }
    
    // =================== BUFFERED WRITE ===================
    public function bufferedAppend($movie_name, $message_id, $channel_id) {
        if (empty(trim($movie_name))) return false;
        
        self::$buffer[] = [
            'movie_name' => trim($movie_name),
            'message_id' => $message_id,
            'channel_id' => $channel_id,
            'timestamp' => time()
        ];
        
        // Buffer full ho gaya toh flush karo
        if (count(self::$buffer) >= CSV_BUFFER_SIZE) {
            $this->flushBuffer();
        }
        
        // Invalidate cache
        $this->clearCache();
        
        return true;
    }
    
    public function flushBuffer() {
        if (empty(self::$buffer)) return true;
        
        // Exclusive lock for writing
        $fp = fopen(CSV_FILE, 'a');
        if (!$fp) return false;
        
        if (flock($fp, LOCK_EX)) {
            foreach (self::$buffer as $entry) {
                fputcsv($fp, [
                    $entry['movie_name'],
                    $entry['message_id'],
                    $entry['channel_id']
                ]);
            }
            fflush($fp);
            flock($fp, LOCK_UN);
        } else {
            error_log("Could not lock CSV file for writing");
            fclose($fp);
            return false;
        }
        
        fclose($fp);
        
        // Clear buffer
        self::$buffer = [];
        
        return true;
    }
    
    // =================== SAFE READ ===================
    public function readCSV() {
        $data = [];
        
        if (!file_exists(CSV_FILE)) {
            return $data;
        }
        
        // Shared lock for reading
        $fp = fopen(CSV_FILE, 'r');
        if (!$fp) return $data;
        
        if (flock($fp, LOCK_SH)) {
            $header = fgetcsv($fp);
            if ($header === false || $header[0] !== 'movie_name') {
                // Invalid header, rebuild
                flock($fp, LOCK_UN);
                fclose($fp);
                $this->rebuildCSV();
                return $this->readCSV();
            }
            
            while (($row = fgetcsv($fp)) !== FALSE) {
                if (count($row) >= 3 && !empty(trim($row[0]))) {
                    $data[] = [
                        'movie_name' => trim($row[0]),
                        'message_id' => isset($row[1]) ? intval(trim($row[1])) : 0,
                        'channel_id' => isset($row[2]) ? trim($row[2]) : ''
                    ];
                }
            }
            flock($fp, LOCK_UN);
        }
        
        fclose($fp);
        return $data;
    }
    
    private function rebuildCSV() {
        $backup = BACKUP_DIR . 'csv_backup_' . date('Y-m-d_H-i-s') . '.csv';
        if (file_exists(CSV_FILE)) {
            copy(CSV_FILE, $backup);
        }
        
        $data = [];
        if (file_exists(CSV_FILE)) {
            $lines = file(CSV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode(',', $line);
                if (count($parts) >= 3) {
                    $data[] = [
                        'movie_name' => trim($parts[0]),
                        'message_id' => intval(trim($parts[1])),
                        'channel_id' => trim($parts[2])
                    ];
                }
            }
        }
        
        $fp = fopen(CSV_FILE, 'w');
        fputcsv($fp, ['movie_name', 'message_id', 'channel_id']);
        foreach ($data as $row) {
            fputcsv($fp, [$row['movie_name'], $row['message_id'], $row['channel_id']]);
        }
        fclose($fp);
    }
    
    // =================== CACHING SYSTEM ===================
    public function getCachedData() {
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        
        // Memory cache check
        if ($this->cache_data !== null && (time() - $this->cache_timestamp) < CACHE_EXPIRY) {
            return $this->cache_data;
        }
        
        // File cache check
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_EXPIRY) {
            $cached = @unserialize(file_get_contents($cache_file));
            if ($cached !== false) {
                $this->cache_data = $cached;
                $this->cache_timestamp = filemtime($cache_file);
                return $this->cache_data;
            }
        }
        
        // Fresh load from CSV
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        
        // Save to file cache
        file_put_contents($cache_file, serialize($this->cache_data));
        
        return $this->cache_data;
    }
    
    public function clearCache() {
        $this->cache_data = null;
        $this->cache_timestamp = 0;
        
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }
    
    // =================== SEARCH FUNCTIONS ===================
    public function searchMovies($query) {
        $data = $this->getCachedData();
        $query_lower = strtolower(trim($query));
        $results = [];
        
        foreach ($data as $item) {
            $movie_lower = strtolower($item['movie_name']);
            $score = 0;
            
            if ($movie_lower === $query_lower) {
                $score = 100;
            } elseif (strpos($movie_lower, $query_lower) !== false) {
                $score = 80;
            } else {
                similar_text($movie_lower, $query_lower, $similarity);
                if ($similarity > 60) {
                    $score = $similarity;
                }
            }
            
            if ($score > 0) {
                if (!isset($results[$movie_lower])) {
                    $results[$movie_lower] = [
                        'score' => $score,
                        'count' => 0,
                        'items' => []
                    ];
                }
                $results[$movie_lower]['count']++;
                $results[$movie_lower]['items'][] = $item;
            }
        }
        
        // Sort by score
        uasort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($results, 0, 10);
    }
    
    // =================== STATISTICS ===================
    public function getStats() {
        $data = $this->getCachedData();
        $stats = [
            'total_movies' => count($data),
            'channels' => [],
            'last_updated' => date('Y-m-d H:i:s', $this->cache_timestamp)
        ];
        
        // Count by channel
        foreach ($data as $item) {
            $channel = $item['channel_id'];
            if (!isset($stats['channels'][$channel])) {
                $stats['channels'][$channel] = 0;
            }
            $stats['channels'][$channel]++;
        }
        
        return $stats;
    }
}

// ==================== GLOBAL CONFIGURATION ====================
global $ENV_CONFIG, $csvManager;
$csvManager = CSVManager::getInstance();

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = array(), $is_multipart = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        if ($res === false) {
            error_log("CURL ERROR: " . curl_error($ch));
        }
        curl_close($ch);
        return $res;
    } else {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
            )
        );
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            error_log("apiRequest failed for method $method");
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

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    return apiRequest('sendMessage', $data);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = [
        'callback_query_id' => $callback_query_id,
        'show_alert' => $show_alert
    ];
    if ($text) $data['text'] = $text;
    return apiRequest('answerCallbackQuery', $data);
}

// ==================== CHANNEL MANAGEMENT ====================
function getChannelType($channel_id) {
    global $ENV_CONFIG;
    
    // Check if public channel
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return 'public';
        }
    }
    
    // Check if private channel
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return 'private';
        }
    }
    
    return 'unknown';
}

function getChannelUsername($channel_id) {
    global $ENV_CONFIG;
    
    // Check public channels
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return $channel['username'];
        }
    }
    
    // Check private channels
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return $channel['username'] ?: 'Private Channel';
        }
    }
    
    return 'Unknown Channel';
}

// ==================== DELIVERY LOGIC ====================
function deliver_item_to_chat($chat_id, $item) {
    $channel_id = $item['channel_id'];
    $message_id = $item['message_id'];
    $channel_type = getChannelType($channel_id);
    
    // Show typing indicator
    sendChatAction($chat_id, 'typing');
    
    if ($channel_type === 'public') {
        // Public channel - use forwardMessage (shows source)
        $result = forwardMessage($chat_id, $channel_id, $message_id);
        return $result !== false;
    } elseif ($channel_type === 'private') {
        // Private channel - use copyMessage (hides source)
        $result = copyMessage($chat_id, $channel_id, $message_id);
        return $result !== false;
    }
    
    // Fallback - send as text
    $text = "🎬 " . htmlspecialchars($item['movie_name']) . "\n";
    $text .= "📁 Channel: " . getChannelUsername($channel_id) . "\n";
    $text .= "🔗 Message ID: " . $message_id;
    sendMessage($chat_id, $text, null, 'HTML');
    return false;
}

// ==================== SEARCH FUNCTION ====================
function advanced_search($chat_id, $query, $user_id = null) {
    global $csvManager;
    
    // Show typing indicator
    sendChatAction($chat_id, 'typing');
    
    $q = strtolower(trim($query));
    
    // 1. Minimum length check
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters for search");
        return;
    }
    
    // 2. INVALID KEYWORDS FILTER
    $invalid_keywords = [
        'vlc', 'audio', 'track', 'change', 'open', 'kar', 'me', 'hai',
        'how', 'what', 'problem', 'issue', 'help', 'solution', 'fix',
        'error', 'not working', 'download', 'play', 'video', 'sound',
        'subtitle', 'quality', 'hd', 'full', 'part', 'scene'
    ];
    
    $query_words = explode(' ', $q);
    $invalid_count = 0;
    foreach ($query_words as $word) {
        if (in_array($word, $invalid_keywords)) {
            $invalid_count++;
        }
    }
    
    if ($invalid_count > 0 && ($invalid_count / count($query_words)) > 0.5) {
        sendMessage($chat_id, "🎬 Please enter a valid movie name!\n\nExamples:\n• kgf\n• pushpa\n• avengers\n\n📢 Join: @EntertainmentTadka786", null, 'HTML');
        return;
    }
    
    // 3. Search movies
    $found = $csvManager->searchMovies($query);
    
    if (!empty($found)) {
        $total_items = 0;
        foreach ($found as $movie_data) {
            $total_items += $movie_data['count'];
        }
        
        $msg = "🔍 Found " . count($found) . " movies for '$query' ($total_items items):\n\n";
        $i = 1;
        foreach ($found as $movie_name => $movie_data) {
            $msg .= "$i. " . ucwords($movie_name) . " (" . $movie_data['count'] . " entries)\n";
            $i++;
            if ($i > 10) break;
        }
        
        sendMessage($chat_id, $msg);
        
        // Create inline keyboard with top 5 results
        $keyboard = ['inline_keyboard' => []];
        $count = 0;
        foreach ($found as $movie_name => $movie_data) {
            $keyboard['inline_keyboard'][] = [[
                'text' => "🎬 " . ucwords($movie_name) . " (" . $movie_data['count'] . ")",
                'callback_data' => 'movie_' . base64_encode($movie_name)
            ]];
            $count++;
            if ($count >= 5) break;
        }
        
        sendMessage($chat_id, "🚀 Select a movie to get all copies:", $keyboard);
        
        // Update user points if user_id provided
        if ($user_id) {
            update_user_points($user_id, 'found_movie');
        }
    } else {
        // Not found message
        $lang = detect_language($query);
        $messages = [
            'hindi' => "😔 Yeh movie abhi available nahi hai!\n\n📢 Join: @EntertainmentTadka786",
            'english' => "😔 This movie isn't available yet!\n\n📢 Join: @EntertainmentTadka786"
        ];
        sendMessage($chat_id, $messages[$lang]);
    }
    
    // Update statistics
    update_stats('total_searches', 1);
    if ($user_id) {
        update_user_points($user_id, 'search');
    }
}

// ==================== HELPER FUNCTIONS ====================
function detect_language($text) {
    $hindi_keywords = ['फिल्म', 'मूवी', 'डाउनलोड', 'हिंदी', 'की', 'में'];
    $english_keywords = ['movie', 'download', 'watch', 'print', 'film', 'the'];
    
    $h = 0;
    $e = 0;
    
    $text_lower = strtolower($text);
    foreach ($hindi_keywords as $k) {
        if (strpos($text, $k) !== false) $h++;
    }
    foreach ($english_keywords as $k) {
        if (stripos($text_lower, $k) !== false) $e++;
    }
    
    return $h > $e ? 'hindi' : 'english';
}

function update_stats($field, $increment = 1) {
    if (!file_exists(STATS_FILE)) return;
    
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    if (!$stats) $stats = [];
    
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
}

function update_user_points($user_id, $action) {
    if (!file_exists(USERS_FILE)) return;
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users_data) $users_data = ['users' => []];
    
    $points_map = ['search' => 1, 'found_movie' => 5, 'daily_login' => 10];
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'points' => 0,
            'last_activity' => date('Y-m-d H:i:s')
        ];
    }
    
    $users_data['users'][$user_id]['points'] += ($points_map[$action] ?? 0);
    $users_data['users'][$user_id]['last_activity'] = date('Y-m-d H:i:s');
    
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

// ==================== ADMIN COMMANDS ====================
function admin_stats($chat_id) {
    global $csvManager, $ENV_CONFIG;
    
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    
    $msg = "📊 Bot Statistics\n\n";
    $msg .= "🎬 Total Movies: " . $stats['total_movies'] . "\n";
    $msg .= "👥 Total Users: " . $total_users . "\n";
    
    // Get searches from stats file
    $file_stats = json_decode(file_get_contents(STATS_FILE), true);
    $msg .= "🔍 Total Searches: " . ($file_stats['total_searches'] ?? 0) . "\n";
    $msg .= "🕒 Last Updated: " . $stats['last_updated'] . "\n\n";
    
    $msg .= "📡 Channels Distribution:\n";
    foreach ($stats['channels'] as $channel_id => $count) {
        $channel_name = getChannelUsername($channel_id);
        $msg .= "• " . $channel_name . ": " . $count . " movies\n";
    }
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

function csv_stats_command($chat_id) {
    global $csvManager;
    
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $csv_size = file_exists(CSV_FILE) ? filesize(CSV_FILE) : 0;
    
    $msg = "📊 CSV Database Statistics\n\n";
    $msg .= "📁 File Size: " . round($csv_size / 1024, 2) . " KB\n";
    $msg .= "📄 Total Movies: " . $stats['total_movies'] . "\n";
    $msg .= "🕒 Last Cache Update: " . $stats['last_updated'] . "\n\n";
    
    $msg .= "📡 Movies by Channel:\n";
    foreach ($stats['channels'] as $channel_id => $count) {
        $channel_type = getChannelType($channel_id);
        $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
        $msg .= $type_icon . " " . getChannelUsername($channel_id) . ": " . $count . "\n";
    }
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

// ==================== PAGINATION & VIEW ====================
function totalupload_controller($chat_id, $page = 1) {
    global $csvManager;
    
    sendChatAction($chat_id, 'upload_document');
    
    $all = $csvManager->getCachedData();
    if (empty($all)) {
        sendMessage($chat_id, "⚠️ No movies found in database.");
        return;
    }
    
    $total = count($all);
    $total_pages = ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    $page_movies = array_slice($all, $start, ITEMS_PER_PAGE);
    
    // Forward movies with delay
    $i = 1;
    foreach ($page_movies as $movie) {
        sendChatAction($chat_id, 'upload_document');
        deliver_item_to_chat($chat_id, $movie);
        usleep(500000); // 0.5 second delay
        $i++;
    }
    
    // Send pagination message
    $title = "📊 Total Uploads\n";
    $title .= "• Page {$page}/{$total_pages}\n";
    $title .= "• Showing: " . count($page_movies) . " of {$total}\n\n";
    $title .= "➡️ Use buttons to navigate";
    
    $keyboard = ['inline_keyboard' => []];
    $row = [];
    if ($page > 1) {
        $row[] = ['text' => '⏮️ Previous', 'callback_data' => 'tu_prev_' . ($page - 1)];
    }
    if ($page < $total_pages) {
        $row[] = ['text' => '⏭️ Next', 'callback_data' => 'tu_next_' . ($page + 1)];
    }
    if (!empty($row)) {
        $keyboard['inline_keyboard'][] = $row;
    }
    $keyboard['inline_keyboard'][] = [
        ['text' => '🎬 View Current Page', 'callback_data' => 'tu_view_' . $page],
        ['text' => '🛑 Stop', 'callback_data' => 'tu_stop']
    ];
    
    sendMessage($chat_id, $title, $keyboard, 'HTML');
}

// ==================== MAIN UPDATE PROCESSING ====================
$update = json_decode(file_get_contents('php://input'), true);
if ($update) {
    // Load cache
    $csvManager->getCachedData();
    
    // Process channel posts
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        // Check if this is one of our configured channels
        $all_channels = array_merge(
            array_column($ENV_CONFIG['PUBLIC_CHANNELS'], 'id'),
            array_column($ENV_CONFIG['PRIVATE_CHANNELS'], 'id')
        );
        
        if (in_array($chat_id, $all_channels)) {
            $text = '';
            
            if (isset($message['caption'])) {
                $text = $message['caption'];
            } elseif (isset($message['text'])) {
                $text = $message['text'];
            } elseif (isset($message['document'])) {
                $text = $message['document']['file_name'];
            } else {
                $text = 'Media Upload - ' . date('d-m-Y H:i');
            }
            
            if (!empty(trim($text))) {
                $csvManager->bufferedAppend($text, $message_id, $chat_id);
            }
        }
    }
    
    // Process user messages
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
        // Update user data
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!$users_data) $users_data = ['users' => []];
        
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '',
                'last_name' => $message['from']['last_name'] ?? '',
                'username' => $message['from']['username'] ?? '',
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s'),
                'points' => 0
            ];
            $users_data['total_requests'] = ($users_data['total_requests'] ?? 0) + 1;
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            update_stats('total_users', 1);
        }
        
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        
        // Process commands
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
            if ($command == '/start') {
                sendChatAction($chat_id, 'typing');
                $welcome = "🎬 Welcome to Entertainment Tadka!\n\n";
                $welcome .= "📢 How to use this bot:\n";
                $welcome .= "• Simply type any movie name\n";
                $welcome .= "• Use English or Hindi\n";
                $welcome .= "• Partial names also work\n\n";
                $welcome .= "🔍 Examples:\n";
                $welcome .= "• kgf\n• pushpa\n• avengers\n• spider-man\n\n";
                $welcome .= "📢 Join: @EntertainmentTadka786\n";
                $welcome .= "🎭 Theater Prints: @threater_print_movies\n";
                $welcome .= "💾 Backup: @ETBackup";
                sendMessage($chat_id, $welcome, null, 'HTML');
                update_user_points($user_id, 'daily_login');
            }
            elseif ($command == '/checkdate') {
                sendChatAction($chat_id, 'typing');
                check_date($chat_id);
            }
            elseif ($command == '/totalupload' || $command == '/totaluploads') {
                totalupload_controller($chat_id, 1);
            }
            elseif ($command == '/testcsv') {
                sendChatAction($chat_id, 'typing');
                test_csv($chat_id);
            }
            elseif ($command == '/checkcsv') {
                sendChatAction($chat_id, 'typing');
                $show_all = (isset($parts[1]) && strtolower($parts[1]) == 'all');
                show_csv_data($chat_id, $show_all);
            }
            elseif ($command == '/csvstats') {
                csv_stats_command($chat_id);
            }
            elseif ($command == '/stats' && $user_id == ADMIN_ID) {
                admin_stats($chat_id);
            }
            elseif ($command == '/help') {
                sendChatAction($chat_id, 'typing');
                $help = "🤖 Entertainment Tadka Bot\n\n";
                $help .= "📢 Join our channels:\n";
                $help .= "• @EntertainmentTadka786\n";
                $help .= "• @threater_print_movies\n";
                $help .= "• @ETBackup\n\n";
                $help .= "📋 Available Commands:\n";
                $help .= "/start - Welcome message\n";
                $help .= "/checkdate - Date-wise stats\n";
                $help .= "/totalupload - Browse all movies\n";
                $help .= "/testcsv - View all movies\n";
                $help .= "/checkcsv - Check CSV data\n";
                $help .= "/csvstats - CSV statistics\n";
                $help .= "/help - This message\n";
                if ($user_id == ADMIN_ID) {
                    $help .= "/stats - Admin statistics\n";
                }
                sendMessage($chat_id, $help, null, 'HTML');
            }
        } 
        elseif (!empty(trim($text))) {
            advanced_search($chat_id, $text, $user_id);
        }
    }
    
    // Process callback queries
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $data = $query['data'];
        $user_id = $query['from']['id'];
        
        // Show typing indicator
        sendChatAction($chat_id, 'typing');
        
        if (strpos($data, 'movie_') === 0) {
            // Movie selection from search results
            $movie_name_encoded = str_replace('movie_', '', $data);
            $movie_name = base64_decode($movie_name_encoded);
            
            if ($movie_name) {
                $all_movies = $csvManager->getCachedData();
                $movie_items = [];
                
                foreach ($all_movies as $item) {
                    if (strtolower($item['movie_name']) === strtolower($movie_name)) {
                        $movie_items[] = $item;
                    }
                }
                
                if (!empty($movie_items)) {
                    $sent_count = 0;
                    foreach ($movie_items as $item) {
                        if (deliver_item_to_chat($chat_id, $item)) {
                            $sent_count++;
                            usleep(300000); // 0.3 second delay
                        }
                    }
                    
                    $channel_type = getChannelType($movie_items[0]['channel_id']);
                    $source_note = $channel_type === 'public' ? 
                        " (Forwarded from " . getChannelUsername($movie_items[0]['channel_id']) . ")" :
                        " (Source hidden)";
                    
                    sendMessage($chat_id, 
                        "✅ Sent $sent_count copies of '$movie_name'$source_note\n\n" .
                        "📢 Join: @EntertainmentTadka786"
                    );
                    answerCallbackQuery($query['id'], "🎬 $sent_count items sent!");
                } else {
                    answerCallbackQuery($query['id'], "❌ Movie not found", true);
                }
            }
        }
        elseif (strpos($data, 'tu_prev_') === 0) {
            $page = (int)str_replace('tu_prev_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'tu_next_') === 0) {
            $page = (int)str_replace('tu_next_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'tu_view_') === 0) {
            $page = (int)str_replace('tu_view_', '', $data);
            $all = $csvManager->getCachedData();
            $total = count($all);
            $start = ($page - 1) * ITEMS_PER_PAGE;
            $page_movies = array_slice($all, $start, ITEMS_PER_PAGE);
            
            $sent = 0;
            foreach ($page_movies as $movie) {
                if (deliver_item_to_chat($chat_id, $movie)) {
                    $sent++;
                    usleep(500000);
                }
            }
            
            answerCallbackQuery($query['id'], "✅ Re-sent $sent movies");
        }
        elseif ($data === 'tu_stop') {
            sendMessage($chat_id, "✅ Pagination stopped. Type /totalupload to start again.");
            answerCallbackQuery($query['id'], "Stopped");
        }
    }
    
    // Daily maintenance at 3 AM
    if (date('H:i') == '03:00') {
        $csvManager->flushBuffer();
        $csvManager->clearCache();
    }
}

// ==================== LEGACY FUNCTIONS (Keep for compatibility) ====================
function check_date($chat_id) {
    // Simplified version for now
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $msg = "📅 Bot Statistics\n\n";
    $msg .= "🎬 Total Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $msg .= "👥 Total Users: " . ($stats['total_users'] ?? 0) . "\n";
    $msg .= "🔍 Total Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $msg .= "🕒 Last Updated: " . ($stats['last_updated'] ?? 'N/A');
    sendMessage($chat_id, $msg, null, 'HTML');
}

function test_csv($chat_id) {
    global $csvManager;
    $data = $csvManager->getCachedData();
    
    if (empty($data)) {
        sendMessage($chat_id, "📊 CSV file is empty.");
        return;
    }
    
    $message = "📊 CSV Movie Database\n\n";
    $message .= "📁 Total Movies: " . count($data) . "\n";
    $message .= "🔍 Showing latest 10 entries\n\n";
    
    $recent = array_slice($data, -10);
    $i = 1;
    foreach ($recent as $movie) {
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . htmlspecialchars($movie['movie_name']) . "\n";
        $message .= "   📝 ID: " . $movie['message_id'] . "\n";
        $message .= "   📡 Channel: " . $channel_name . "\n\n";
        $i++;
    }
    
    sendMessage($chat_id, $message, null, 'HTML');
}

function show_csv_data($chat_id, $show_all = false) {
    global $csvManager;
    $data = $csvManager->getCachedData();
    
    if (empty($data)) {
        sendMessage($chat_id, "📊 CSV file is empty.");
        return;
    }
    
    $limit = $show_all ? count($data) : 10;
    $display_data = array_slice($data, -$limit);
    
    $message = "📊 CSV Movie Database\n\n";
    $message .= "📁 Total Movies: " . count($data) . "\n";
    if (!$show_all) {
        $message .= "🔍 Showing latest 10 entries\n";
        $message .= "📋 Use '/checkcsv all' for full list\n\n";
    } else {
        $message .= "📋 Full database listing\n\n";
    }
    
    $i = 1;
    foreach ($display_data as $movie) {
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . htmlspecialchars($movie['movie_name']) . "\n";
        $message .= "   📝 ID: " . $movie['message_id'] . "\n";
        $message .= "   📡 Channel: " . $channel_name . "\n\n";
        $i++;
        
        if (strlen($message) > 3000) {
            sendMessage($chat_id, $message, null, 'HTML');
            $message = "📊 Continuing...\n\n";
        }
    }
    
    $message .= "💾 File: " . CSV_FILE . "\n";
    $message .= "⏰ Last Updated: " . date('Y-m-d H:i:s', filemtime(CSV_FILE));
    
    sendMessage($chat_id, $message, null, 'HTML');
}

// ==================== WEBHOOK SETUP & TESTING ====================
if (php_sapi_name() === 'cli' || isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Setup</h2>";
    echo "<p>Result: " . htmlspecialchars($result) . "</p>";
    echo "<p>Webhook URL: " . htmlspecialchars($webhook_url) . "</p>";
    
    $bot_info = json_decode(apiRequest('getMe'), true);
    if ($bot_info && isset($bot_info['ok']) && $bot_info['ok']) {
        echo "<h2>Bot Info</h2>";
        echo "<p>Name: " . htmlspecialchars($bot_info['result']['first_name']) . "</p>";
        echo "<p>Username: @" . htmlspecialchars($bot_info['result']['username']) . "</p>";
    }
    
    echo "<h2>Channels Configured</h2>";
    echo "<h3>Public Channels:</h3>";
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        echo "<p>" . htmlspecialchars($channel['username']) . " (" . htmlspecialchars($channel['id']) . ")</p>";
    }
    
    echo "<h3>Private Channels:</h3>";
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        echo "<p>" . (htmlspecialchars($channel['username']) ?: 'Private') . " (" . htmlspecialchars($channel['id']) . ")</p>";
    }
    
    exit;
}

if (isset($_GET['test'])) {
    echo "<h1>🎬 Entertainment Tadka Bot - Test Page</h1>";
    echo "<p><strong>Status:</strong> ✅ Running</p>";
    echo "<p><strong>Bot:</strong> @" . $ENV_CONFIG['BOT_USERNAME'] . "</p>";
    
    $stats = $csvManager->getStats();
    echo "<p><strong>Total Movies:</strong> " . $stats['total_movies'] . "</p>";
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    echo "<p><strong>Total Users:</strong> " . count($users_data['users'] ?? []) . "</p>";
    
    echo "<h3>🚀 Quick Setup</h3>";
    echo "<p><a href='?setwebhook=1'>Set Webhook Now</a></p>";
    echo "<p><a href='?test_csv=1'>Test CSV Manager</a></p>";
    
    exit;
}

if (isset($_GET['test_csv'])) {
    echo "<h2>Testing CSV Manager</h2>";
    
    echo "<h3>1. Reading CSV...</h3>";
    $data = $csvManager->readCSV();
    echo "Total entries: " . count($data) . "<br>";
    
    echo "<h3>2. Cache Status...</h3>";
    $cached = $csvManager->getCachedData();
    echo "Cached entries: " . count($cached) . "<br>";
    
    echo "<h3>3. CSV Stats...</h3>";
    $stats = $csvManager->getStats();
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
    exit;
}

// Default response for direct access
if (!isset($update) || !$update) {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Entertainment Tadka Bot</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .status {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 5px solid #4CAF50;
        }
        .channels {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .channel-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .channel-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.25);
        }
        .channel-card.public {
            border-left: 5px solid #4CAF50;
        }
        .channel-card.private {
            border-left: 5px solid #FF9800;
        }
        .btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin: 10px 5px;
            transition: background 0.3s, transform 0.3s;
            font-weight: bold;
        }
        .btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .btn-blue {
            background: #2196F3;
        }
        .btn-blue:hover {
            background: #1976D2;
        }
        .features {
            margin-top: 30px;
        }
        .feature-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        .feature-item::before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Bot</h1>
        
        <div class="status">
            <strong>✅ Bot is Running</strong>
            <p>Telegram Bot for movie searches across multiple channels</p>
        </div>
        
        <div style="text-align: center; margin: 25px 0;">
            <a href="?setwebhook=1" class="btn">🔗 Set Webhook</a>
            <a href="?test=1" class="btn btn-blue">🧪 Test Bot</a>
            <a href="?test_csv=1" class="btn">📊 Test CSV</a>
        </div>
        
        <h3>📡 Configured Channels</h3>
        <div class="channels">
HTML;

    // Display public channels
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if (!empty($channel['username'])) {
            echo "<div class='channel-card public'>";
            echo "<strong>🌐 Public Channel</strong><br>";
            echo $channel['username'] . "<br>";
            echo "<small>" . $channel['id'] . "</small>";
            echo "</div>";
        }
    }
    
    // Display private channels
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        echo "<div class='channel-card private'>";
        echo "<strong>🔒 Private Channel</strong><br>";
        echo ($channel['username'] ?: 'Private Channel') . "<br>";
        echo "<small>" . $channel['id'] . "</small>";
        echo "</div>";
    }

    echo <<<HTML
        </div>
        
        <div class="features">
            <h3>✨ Features</h3>
            <div class="feature-item">Multi-channel support (Public & Private)</div>
            <div class="feature-item">Smart movie search with partial matching</div>
            <div class="feature-item">Typing indicators for better UX</div>
            <div class="feature-item">Public channels show source, private channels hide source</div>
            <div class="feature-item">CSV-based database with caching</div>
            <div class="feature-item">Admin statistics and monitoring</div>
            <div class="feature-item">Pagination for browsing all movies</div>
            <div class="feature-item">Automatic channel post tracking</div>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: rgba(255, 255, 255, 0.1); border-radius: 10px;">
            <strong>📝 Environment Variables Configured:</strong>
            <p>• BOT_TOKEN: " . (BOT_TOKEN ? '✅ Set' : '❌ Not Set') . "</p>
            <p>• Public Channels: " . count($ENV_CONFIG['PUBLIC_CHANNELS']) . "</p>
            <p>• Private Channels: " . count($ENV_CONFIG['PRIVATE_CHANNELS']) . "</p>
            <p>• CSV File: " . CSV_FILE . "</p>
        </div>
    </div>
</body>
</html>
HTML;
}
?>