<?php
// ==================== CONFIGURATION ====================
// Error Reporting - Only in Development
$isDevelopment = (getenv('ENVIRONMENT') === 'development');
if ($isDevelopment) {
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
    
    // Write to error.log
    @file_put_contents('error.log', $log_entry, FILE_APPEND);
    @chmod('error.log', 0666);
    
    // Also log to PHP error log
    @error_log($message);
    
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
log_error("Bot script started", 'INFO', [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'uri' => $_SERVER['REQUEST_URI'] ?? ''
]);

// ==================== ENVIRONMENT CONFIGURATION ====================
$ENV_CONFIG = [
    // Bot Configuration
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot',
    'ADMIN_ID' => (int)(getenv('ADMIN_ID') ?: 1080317415),
    
    // Public Channels
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
    
    // Private Channels
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
    
    // Request Group
    'REQUEST_GROUP_ID' => getenv('REQUEST_GROUP_ID') ?: '-1003083386043',
    'REQUEST_GROUP_USERNAME' => getenv('REQUEST_GROUP_USERNAME') ?: '@EntertainmentTadka7860',
    
    // File Paths
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'REQUEST_FILE' => 'requests.json',
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
    header('Content-Type: text/plain; charset=utf-8');
    die("❌ Bot Token not configured. Please set BOT_TOKEN environment variable.");
}

// Extract config to constants
define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_ID', $ENV_CONFIG['ADMIN_ID']);
define('REQUEST_GROUP_ID', $ENV_CONFIG['REQUEST_GROUP_ID']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUEST_FILE', $ENV_CONFIG['REQUEST_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);

// Channel constants for easy access
define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');
define('BACKUP_CHANNEL_USERNAME', '@ETBackup');

// Admin IDs array
$ADMIN_IDS = [ADMIN_ID];

// ==================== BASIC HELPERS ====================
function load_json($file) {
    if (!file_exists($file)) {
        if ($file === REQUEST_FILE) {
            // Initialize empty requests file
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
            @chmod($file, 0666);
            return [];
        }
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return $data ?: [];
}

function save_json($file, $data) {
    $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    if ($result === false) {
        log_error("Failed to save JSON file: $file", 'ERROR');
    }
}

// ==================== REQUEST DETECTION ====================
function is_request_message($text) {
    if (empty(trim($text))) return false;
    
    $keywords = ['request', 'req', 'pls add', 'please add', 'add movie', 'add', 'movie request'];
    $text = strtolower($text);
    
    // Check if it's a command
    if (strpos($text, '/') === 0) return false;
    
    foreach ($keywords as $k) {
        if (strpos($text, $k) !== false) return true;
    }
    
    // Check for patterns like "please add [movie]"
    if (preg_match('/(please|pls|can you|could you).*(add|upload|provide)/i', $text)) {
        return true;
    }
    
    return false;
}

function extract_movie_from_request($text) {
    // Remove common request phrases
    $patterns = [
        '/^(please|pls|kindly|can you|could you)\s+/i',
        '/\s+(please|pls|kindly)$/i',
        '/\b(add|upload|provide|get|find)\s+(me\s+)?(the\s+)?/i',
        '/\b(movie|film|picture|video)\s+(of\s+)?/i',
        '/\b(request|req|asking|wanted)\s+(for\s+)?/i'
    ];
    
    $movie = trim($text);
    foreach ($patterns as $pattern) {
        $movie = preg_replace($pattern, '', $movie);
    }
    
    return trim($movie);
}

// ==================== DUPLICATE + FLOOD CONTROL ====================
function is_duplicate_request($user_id, $movie) {
    $requests = load_json(REQUEST_FILE);
    if (empty($requests)) return false;
    
    $movie_lower = strtolower(trim($movie));
    $recent_time = time() - (30 * 24 * 3600); // 30 days
    
    foreach ($requests as $r) {
        if (
            isset($r['user_id']) && $r['user_id'] == $user_id &&
            isset($r['movie']) && strtolower(trim($r['movie'])) == $movie_lower &&
            isset($r['status']) && $r['status'] === 'pending' &&
            isset($r['time']) && $r['time'] >= $recent_time
        ) {
            return true;
        }
    }
    return false;
}

function request_flood_check($user_id) {
    $requests = load_json(REQUEST_FILE);
    if (empty($requests)) return false;
    
    $count = 0;
    $since = time() - 86400; // 24 hours
    
    foreach ($requests as $r) {
        if (
            isset($r['user_id']) && $r['user_id'] == $user_id &&
            isset($r['time']) && $r['time'] >= $since &&
            isset($r['status']) && $r['status'] === 'pending'
        ) {
            $count++;
        }
    }
    
    return $count >= 3; // Max 3 requests per day
}

// ==================== HANDLE REQUEST ====================
function handle_movie_request($chat_id, $user_id, $username, $movie) {
    if (empty(trim($movie))) {
        sendMessage($chat_id,
            "❌ <b>Invalid Request</b>\nPlease provide a movie name.",
            null, 'HTML');
        return;
    }
    
    if (strlen(trim($movie)) < 3) {
        sendMessage($chat_id,
            "❌ <b>Invalid Request</b>\nMovie name too short. Minimum 3 characters.",
            null, 'HTML');
        return;
    }
    
    if (request_flood_check($user_id)) {
        sendMessage($chat_id,
            "⛔ <b>Limit Reached</b>\nYou can make maximum 3 requests in 24 hours.\nPlease wait and try again tomorrow.",
            null, 'HTML');
        return;
    }
    
    if (is_duplicate_request($user_id, $movie)) {
        sendMessage($chat_id,
            "⚠️ <b>Duplicate Request</b>\nYou already requested this movie recently.\nIt's still pending review.",
            null, 'HTML');
        return;
    }
    
    $requests = load_json(REQUEST_FILE);
    $id = 'req_' . uniqid();
    
    $requests[$id] = [
        'id' => $id,
        'user_id' => $user_id,
        'username' => $username ?: ('user_' . $user_id),
        'movie' => trim($movie),
        'status' => 'pending',
        'time' => time(),
        'chat_id' => $chat_id
    ];
    
    save_json(REQUEST_FILE, $requests);
    
    sendMessage($chat_id,
        "📥 <b>Request Submitted Successfully!</b>\n\n" .
        "🎬 <b>Movie:</b> " . htmlspecialchars($movie) . "\n" .
        "⏳ <b>Status:</b> Pending review\n" .
        "👤 <b>Your ID:</b> $user_id\n\n" .
        "✅ You'll be notified when it's approved.\n" .
        "📋 Use /myrequests to check status.",
        null, 'HTML');
    
    log_error("Movie request submitted", 'INFO', [
        'user_id' => $user_id,
        'movie' => $movie,
        'request_id' => $id
    ]);
    
    notify_admin_request($id, $requests[$id]);
}

// ==================== ADMIN NOTIFICATION ====================
function notify_admin_request($req_id, $data) {
    global $ENV_CONFIG;
    
    $msg = "📥 <b>🎬 NEW MOVIE REQUEST</b>\n\n";
    $msg .= "🆔 <b>Request ID:</b> $req_id\n";
    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($data['movie']) . "\n";
    $msg .= "👤 <b>User:</b> @" . htmlspecialchars($data['username']) . "\n";
    $msg .= "🆔 <b>User ID:</b> " . $data['user_id'] . "\n";
    $msg .= "⏰ <b>Time:</b> " . date('d M Y, H:i', $data['time']) . "\n";
    $msg .= "📱 <b>Chat ID:</b> " . ($data['chat_id'] ?? 'N/A');
    
    $kb = [
        'inline_keyboard' => [
            [
                ['text' => '✅ Approve', 'callback_data' => "req_approve:$req_id"],
                ['text' => '❌ Reject', 'callback_data' => "req_reject:$req_id"]
            ],
            [
                ['text' => '🔍 Search in Database', 'callback_data' => "req_search:$req_id"],
                ['text' => '📝 View All Pending', 'callback_data' => 'req_view_pending']
            ]
        ]
    ];
    
    sendMessage(REQUEST_GROUP_ID, $msg, $kb, 'HTML');
    log_error("Admin notified of new request", 'INFO', ['request_id' => $req_id]);
}

// ==================== USER NOTIFICATION ====================
function notify_user_approved($user_id, $movie) {
    $msg = "✅ <b>🎉 REQUEST APPROVED!</b>\n\n";
    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie) . "\n";
    $msg .= "✅ <b>Status:</b> Added to database\n";
    $msg .= "⏰ <b>Time:</b> " . date('d M Y, H:i') . "\n\n";
    $msg .= "🔍 You can now search for it in the bot!\n";
    $msg .= "📢 Join: @EntertainmentTadka786";
    
    sendMessage($user_id, $msg, null, 'HTML');
}

function notify_user_rejected($user_id, $movie, $reason = '') {
    $msg = "❌ <b>REQUEST REJECTED</b>\n\n";
    $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($movie) . "\n";
    $msg .= "❌ <b>Status:</b> Not approved\n";
    
    if (!empty($reason)) {
        $msg .= "📝 <b>Reason:</b> $reason\n";
    } else {
        $msg .= "📝 <b>Reason:</b> Could not be added at this time\n";
    }
    
    $msg .= "\n💡 <b>Tip:</b> You can request another movie using /request";
    
    sendMessage($user_id, $msg, null, 'HTML');
}

// ==================== PAGINATION VIEW ====================
function send_pending_requests_page($chat_id, $page = 1, $limit = 5, $filter = '') {
    $requests = load_json(REQUEST_FILE);
    $pending = [];
    
    foreach ($requests as $id => $r) {
        if (!isset($r['status']) || $r['status'] !== 'pending') continue;
        
        if (!empty($filter)) {
            $movie_lower = strtolower($r['movie'] ?? '');
            $filter_lower = strtolower($filter);
            if (strpos($movie_lower, $filter_lower) === false) continue;
        }
        
        $pending[$id] = $r;
    }
    
    if (empty($pending)) {
        sendMessage($chat_id, "📭 <b>No pending requests found.</b>", null, 'HTML');
        return;
    }
    
    $total = count($pending);
    $total_pages = ceil($total / $limit);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * $limit;
    
    $slice = array_slice($pending, $start, $limit, true);
    
    $message = "📋 <b>Pending Requests</b> (Page $page/$total_pages)\n";
    $message .= "📊 <b>Total:</b> $total requests\n\n";
    
    if (!empty($filter)) {
        $message .= "🔍 <b>Filter:</b> \"$filter\"\n\n";
    }
    
    $i = $start + 1;
    foreach ($slice as $id => $r) {
        $time_ago = time() - $r['time'];
        $hours = floor($time_ago / 3600);
        
        $message .= "<b>$i.</b> 🎬 " . htmlspecialchars($r['movie']) . "\n";
        $message .= "   👤 @" . htmlspecialchars($r['username']) . " (ID: " . $r['user_id'] . ")\n";
        $message .= "   ⏰ " . ($hours > 0 ? "$hours hours ago" : "Recently") . "\n";
        $message .= "   🆔 $id\n\n";
        $i++;
    }
    
    $keyboard = ['inline_keyboard' => []];
    
    // Page navigation
    $nav_row = [];
    if ($page > 1) {
        $nav_row[] = ['text' => '⏮️ Previous', 'callback_data' => "req_page:" . ($page - 1) . ":$limit:$filter"];
    }
    if ($page < $total_pages) {
        $nav_row[] = ['text' => '⏭️ Next', 'callback_data' => "req_page:" . ($page + 1) . ":$limit:$filter"];
    }
    if (!empty($nav_row)) {
        $keyboard['inline_keyboard'][] = $nav_row;
    }
    
    // Bulk actions for the current page
    $keyboard['inline_keyboard'][] = [
        ['text' => '✅ Approve ALL on Page', 'callback_data' => "req_bulk_approve:$page:$limit:$filter"],
        ['text' => '❌ Reject ALL on Page', 'callback_data' => "req_bulk_reject:$page:$limit:$filter"]
    ];
    
    // Refresh button
    $keyboard['inline_keyboard'][] = [
        ['text' => '🔄 Refresh', 'callback_data' => "req_page:$page:$limit:$filter"],
        ['text' => '❌ Close', 'callback_data' => 'req_close']
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

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
            @chmod(BACKUP_DIR, 0777);
        }
        if (!file_exists(CACHE_DIR)) {
            @mkdir(CACHE_DIR, 0777, true);
            @chmod(CACHE_DIR, 0777);
        }
        
        // Initialize CSV with correct header if not exists
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id\n";
            @file_put_contents(CSV_FILE, $header);
            @chmod(CSV_FILE, 0666);
            log_error("CSV file created", 'INFO');
        }
        
        // Initialize users.json
        if (!file_exists(USERS_FILE)) {
            $users_data = [
                'users' => [],
                'total_requests' => 0,
                'message_logs' => []
            ];
            @file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            @chmod(USERS_FILE, 0666);
        }
        
        // Initialize bot_stats.json
        if (!file_exists(STATS_FILE)) {
            $stats_data = [
                'total_movies' => 0,
                'total_users' => 0,
                'total_searches' => 0,
                'total_requests' => 0,
                'approved_requests' => 0,
                'rejected_requests' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            @file_put_contents(STATS_FILE, json_encode($stats_data, JSON_PRETTY_PRINT));
            @chmod(STATS_FILE, 0666);
        }
        
        // Initialize requests.json
        if (!file_exists(REQUEST_FILE)) {
            @file_put_contents(REQUEST_FILE, json_encode([], JSON_PRETTY_PRINT));
            @chmod(REQUEST_FILE, 0666);
        }
    }
    
    public function bufferedAppend($movie_name, $message_id, $channel_id) {
        if (empty(trim($movie_name))) {
            log_error("Empty movie name provided", 'WARNING');
            return false;
        }
        
        self::$buffer[] = [
            'movie_name' => trim($movie_name),
            'message_id' => $message_id,
            'channel_id' => $channel_id,
            'timestamp' => time()
        ];
        
        log_error("Added to buffer: " . trim($movie_name), 'INFO', [
            'message_id' => $message_id,
            'channel_id' => $channel_id
        ]);
        
        // Buffer full ho gaya toh flush karo
        if (count(self::$buffer) >= CSV_BUFFER_SIZE) {
            $this->flushBuffer();
        }
        
        // Invalidate cache
        $this->clearCache();
        
        return true;
    }
    
    public function flushBuffer() {
        if (empty(self::$buffer)) {
            return true;
        }
        
        log_error("Flushing buffer with " . count(self::$buffer) . " items", 'INFO');
        
        // Exclusive lock for writing
        $fp = @fopen(CSV_FILE, 'a');
        if (!$fp) {
            log_error("Failed to open CSV file for writing", 'ERROR');
            return false;
        }
        
        if (flock($fp, LOCK_EX)) {
            foreach (self::$buffer as $entry) {
                $result = fputcsv($fp, [
                    $entry['movie_name'],
                    $entry['message_id'],
                    $entry['channel_id']
                ]);
                if ($result === false) {
                    log_error("Failed to write CSV row", 'ERROR', $entry);
                }
            }
            fflush($fp);
            flock($fp, LOCK_UN);
            log_error("Buffer flushed successfully", 'INFO');
        } else {
            log_error("Could not lock CSV file for writing", 'ERROR');
            fclose($fp);
            return false;
        }
        
        fclose($fp);
        
        // Clear buffer
        self::$buffer = [];
        
        return true;
    }
    
    public function readCSV() {
        $data = [];
        
        if (!file_exists(CSV_FILE)) {
            log_error("CSV file not found", 'ERROR');
            return $data;
        }
        
        // Shared lock for reading
        $fp = @fopen(CSV_FILE, 'r');
        if (!$fp) {
            log_error("Failed to open CSV file for reading", 'ERROR');
            return $data;
        }
        
        if (flock($fp, LOCK_SH)) {
            $header = fgetcsv($fp);
            if ($header === false || $header[0] !== 'movie_name') {
                // Invalid header, rebuild
                log_error("Invalid CSV header, rebuilding", 'WARNING');
                flock($fp, LOCK_UN);
                fclose($fp);
                $this->rebuildCSV();
                return $this->readCSV();
            }
            
            $row_count = 0;
            while (($row = fgetcsv($fp)) !== FALSE) {
                if (count($row) >= 3 && !empty(trim($row[0]))) {
                    $data[] = [
                        'movie_name' => trim($row[0]),
                        'message_id' => isset($row[1]) ? intval(trim($row[1])) : 0,
                        'channel_id' => isset($row[2]) ? trim($row[2]) : ''
                    ];
                    $row_count++;
                }
            }
            flock($fp, LOCK_UN);
            log_error("Read $row_count rows from CSV", 'INFO');
        } else {
            log_error("Could not lock CSV file for reading", 'ERROR');
        }
        
        fclose($fp);
        return $data;
    }
    
    private function rebuildCSV() {
        $backup = BACKUP_DIR . 'csv_backup_' . date('Y-m-d_H-i-s') . '.csv';
        if (file_exists(CSV_FILE)) {
            copy(CSV_FILE, $backup);
            log_error("CSV backed up to: $backup", 'INFO');
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
        @chmod(CSV_FILE, 0666);
        
        log_error("CSV rebuilt with " . count($data) . " rows", 'INFO');
    }
    
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
                log_error("Loaded from file cache", 'INFO');
                return $this->cache_data;
            }
        }
        
        // Fresh load from CSV
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        
        // Save to file cache
        @file_put_contents($cache_file, serialize($this->cache_data));
        @chmod($cache_file, 0666);
        
        log_error("Cache updated with " . count($this->cache_data) . " items", 'INFO');
        
        return $this->cache_data;
    }
    
    public function clearCache() {
        $this->cache_data = null;
        $this->cache_timestamp = 0;
        
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        if (file_exists($cache_file)) {
            @unlink($cache_file);
            log_error("Cache cleared", 'INFO');
        }
    }
    
    public function searchMovies($query) {
        $data = $this->getCachedData();
        $query_lower = strtolower(trim($query));
        $results = [];
        
        log_error("Searching for: $query", 'INFO', ['total_items' => count($data)]);
        
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
        
        log_error("Search results: " . count($results) . " matches", 'INFO');
        
        return array_slice($results, 0, 10);
    }
    
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

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = array(), $is_multipart = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    log_error("API Request: $method", 'DEBUG', $params);
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        if ($res === false) {
            log_error("CURL ERROR: " . curl_error($ch), 'ERROR');
        }
        curl_close($ch);
        return $res;
    } else {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 30,
                'ignore_errors' => true
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
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

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    log_error("Sending message to $chat_id", 'INFO', ['text_length' => strlen($text)]);
    
    return apiRequest('sendMessage', $data);
}

function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = null) {
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
    
    log_error("Delivering item to $chat_id", 'INFO', [
        'movie' => $item['movie_name'],
        'channel_id' => $channel_id,
        'message_id' => $message_id
    ]);
    
    // Show typing indicator
    sendChatAction($chat_id, 'typing');
    
    if ($channel_type === 'public') {
        // Public channel - use forwardMessage (shows source)
        $result = forwardMessage($chat_id, $channel_id, $message_id);
        if ($result !== false) {
            log_error("Forwarded message successfully", 'INFO');
            return true;
        } else {
            log_error("Failed to forward message", 'ERROR');
            return false;
        }
    } elseif ($channel_type === 'private') {
        // Private channel - use copyMessage (hides source)
        $result = copyMessage($chat_id, $channel_id, $message_id);
        if ($result !== false) {
            log_error("Copied message successfully", 'INFO');
            return true;
        } else {
            log_error("Failed to copy message", 'ERROR');
            return false;
        }
    }
    
    // Fallback - send as text
    $text = "🎬 " . htmlspecialchars($item['movie_name']) . "\n";
    $text .= "📁 Channel: " . getChannelUsername($channel_id) . "\n";
    $text .= "🔗 Message ID: " . $message_id;
    sendMessage($chat_id, $text, null, 'HTML');
    log_error("Used fallback text delivery", 'WARNING');
    return false;
}

// ==================== SEARCH FUNCTION ====================
function advanced_search($chat_id, $query, $user_id = null) {
    global $csvManager;
    
    // Show typing indicator
    sendChatAction($chat_id, 'typing');
    
    $q = strtolower(trim($query));
    
    log_error("Advanced search initiated by $user_id", 'INFO', ['query' => $query]);
    
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
        log_error("Invalid search query detected", 'WARNING', ['query' => $query]);
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
        
        log_error("Search successful, found " . count($found) . " movies", 'INFO');
    } else {
        // Not found message
        $lang = detect_language($query);
        $messages = [
            'hindi' => "😔 Yeh movie abhi available nahi hai!\n\n📢 Join: @EntertainmentTadka786",
            'english' => "😔 This movie isn't available yet!\n\n📢 Join: @EntertainmentTadka786"
        ];
        sendMessage($chat_id, $messages[$lang]);
        log_error("No results found for query", 'INFO', ['query' => $query]);
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
    if (!file_exists(STATS_FILE)) {
        log_error("Stats file not found", 'ERROR');
        return;
    }
    
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    if (!$stats) {
        $stats = [];
        log_error("Failed to decode stats file", 'ERROR');
    }
    
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    
    $result = file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
    if ($result === false) {
        log_error("Failed to write stats file", 'ERROR');
    }
}

function update_user_points($user_id, $action) {
    if (!file_exists(USERS_FILE)) {
        log_error("Users file not found", 'ERROR');
        return;
    }
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users_data) {
        $users_data = ['users' => []];
        log_error("Failed to decode users file", 'ERROR');
    }
    
    $points_map = ['search' => 1, 'found_movie' => 5, 'daily_login' => 10, 'request_submitted' => 3];
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'points' => 0,
            'last_activity' => date('Y-m-d H:i:s')
        ];
    }
    
    $users_data['users'][$user_id]['points'] += ($points_map[$action] ?? 0);
    $users_data['users'][$user_id]['last_activity'] = date('Y-m-d H:i:s');
    
    $result = file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
    if ($result === false) {
        log_error("Failed to write users file", 'ERROR');
    }
}

// ==================== ADMIN COMMANDS ====================
function admin_stats($chat_id) {
    global $csvManager, $ENV_CONFIG;
    
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    
    $file_stats = json_decode(file_get_contents(STATS_FILE), true);
    $requests = load_json(REQUEST_FILE);
    $pending_requests = 0;
    $approved_requests = 0;
    $rejected_requests = 0;
    
    foreach ($requests as $r) {
        if (isset($r['status'])) {
            if ($r['status'] === 'pending') $pending_requests++;
            elseif ($r['status'] === 'approved') $approved_requests++;
            elseif ($r['status'] === 'rejected') $rejected_requests++;
        }
    }
    
    $msg = "📊 <b>Bot Statistics</b>\n\n";
    $msg .= "🎬 <b>Total Movies:</b> " . $stats['total_movies'] . "\n";
    $msg .= "👥 <b>Total Users:</b> " . $total_users . "\n";
    $msg .= "🔍 <b>Total Searches:</b> " . ($file_stats['total_searches'] ?? 0) . "\n\n";
    
    $msg .= "📥 <b>Request Statistics:</b>\n";
    $msg .= "• 📭 Pending: " . $pending_requests . "\n";
    $msg .= "• ✅ Approved: " . $approved_requests . "\n";
    $msg .= "• ❌ Rejected: " . $rejected_requests . "\n";
    $msg .= "• 📊 Total: " . count($requests) . "\n\n";
    
    $msg .= "📡 <b>Channels Distribution:</b>\n";
    foreach ($stats['channels'] as $channel_id => $count) {
        $channel_name = getChannelUsername($channel_id);
        $msg .= "• " . $channel_name . ": " . $count . " movies\n";
    }
    
    sendMessage($chat_id, $msg, null, 'HTML');
    log_error("Admin stats sent to $chat_id", 'INFO');
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
    log_error("CSV stats sent to $chat_id", 'INFO');
}

// ==================== PAGINATION & VIEW ====================
function totalupload_controller($chat_id, $page = 1) {
    global $csvManager;
    
    sendChatAction($chat_id, 'upload_document');
    
    $all = $csvManager->getCachedData();
    if (empty($all)) {
        sendMessage($chat_id, "⚠️ No movies found in database.");
        log_error("No movies found for pagination", 'WARNING');
        return;
    }
    
    $total = count($all);
    $total_pages = ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    $page_movies = array_slice($all, $start, ITEMS_PER_PAGE);
    
    log_error("Pagination: page $page/$total_pages, showing " . count($page_movies) . " items", 'INFO');
    
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

// ==================== LEGACY FUNCTIONS ====================
function check_date($chat_id) {
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $msg = "📅 Bot Statistics\n\n";
    $msg .= "🎬 Total Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $msg .= "👥 Total Users: " . ($stats['total_users'] ?? 0) . "\n";
    $msg .= "🔍 Total Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $msg .= "🕒 Last Updated: " . ($stats['last_updated'] ?? 'N/A');
    sendMessage($chat_id, $msg, null, 'HTML');
    log_error("Check date command executed", 'INFO');
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
    log_error("Test CSV command executed", 'INFO');
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
    log_error("Show CSV data command executed", 'INFO');
}

// ==================== MAIN PROCESSING ====================
// Initialize CSV Manager
$csvManager = CSVManager::getInstance();

// Check for webhook setup
if (isset($_GET['setup'])) {
    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Setup</h2>";
    echo "<pre>Webhook Set: " . htmlspecialchars($result) . "</pre>";
    echo "<p>Webhook URL: " . htmlspecialchars($webhook_url) . "</p>";
    
    $bot_info = json_decode(apiRequest('getMe'), true);
    if ($bot_info && isset($bot_info['ok']) && $bot_info['ok']) {
        echo "<h2>Bot Info</h2>";
        echo "<p>Name: " . htmlspecialchars($bot_info['result']['first_name']) . "</p>";
        echo "<p>Username: @" . htmlspecialchars($bot_info['result']['username']) . "</p>";
    }
    exit;
}

// Check for webhook deletion
if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Deleted</h2>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

// Test page
if (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot - Test Page</h1>";
    echo "<p><strong>Status:</strong> ✅ Running</p>";
    echo "<p><strong>Bot:</strong> @" . $ENV_CONFIG['BOT_USERNAME'] . "</p>";
    echo "<p><strong>Environment:</strong> " . getenv('ENVIRONMENT') . "</p>";
    
    $stats = $csvManager->getStats();
    echo "<p><strong>Total Movies:</strong> " . $stats['total_movies'] . "</p>";
    
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    echo "<p><strong>Total Users:</strong> " . count($users_data['users'] ?? []) . "</p>";
    
    echo "<h3>🚀 Quick Setup</h3>";
    echo "<p><a href='?setup=1'>Set Webhook Now</a></p>";
    echo "<p><a href='?deletehook=1'>Delete Webhook</a></p>";
    echo "<p><a href='?test_csv=1'>Test CSV Manager</a></p>";
    
    exit;
}

// Test CSV
if (isset($_GET['test_csv'])) {
    header('Content-Type: text/html; charset=utf-8');
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

// ==================== TELEGRAM UPDATE PROCESSING ====================
// Get the incoming update from Telegram
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);
    
    // Process channel posts
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        log_error("Channel post received", 'INFO', [
            'channel_id' => $chat_id,
            'message_id' => $message_id
        ]);
        
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
                log_error("Added channel post to CSV", 'INFO', [
                    'movie_name' => $text,
                    'channel_id' => $chat_id
                ]);
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
        $username = $message['from']['username'] ?? ('user_' . $user_id);
        
        log_error("Message received", 'INFO', [
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'text' => substr($text, 0, 100)
        ]);
        
        // Update user data
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!$users_data) $users_data = ['users' => []];
        
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '',
                'last_name' => $message['from']['last_name'] ?? '',
                'username' => $username,
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s'),
                'points' => 0,
                'request_count' => 0
            ];
            $users_data['total_requests'] = ($users_data['total_requests'] ?? 0) + 1;
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            update_stats('total_users', 1);
            
            log_error("New user registered", 'INFO', [
                'user_id' => $user_id,
                'username' => $username
            ]);
        }
        
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        
        // Process commands
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            $args = array_slice($parts, 1);
            
            log_error("Command received", 'INFO', ['command' => $command]);
            
            if ($command == '/start') {
                $welcome = "🎬 <b>Welcome to Entertainment Tadka!</b>\n\n";
                
                $welcome .= "📢 <b>How to use this bot:</b>\n";
                $welcome .= "• Simply type any movie name\n";
                $welcome .= "• Use English or Hindi\n";
                $welcome .= "• Add 'theater' for theater prints\n";
                $welcome .= "• Partial names also work\n\n";
                
                $welcome .= "🔍 <b>Examples:</b>\n";
                $welcome .= "• Mandala Murders 2025\n";
                $welcome .= "• Lokah Chapter 1 Chandra 2025\n";
                $welcome .= "• Idli Kadai (2025)\n";
                $welcome .= "• IT - Welcome to Derry (2025) S01\n";
                $welcome .= "• hindi movie\n";
                $welcome .= "• kgf\n\n";
                
                $welcome .= "📢 <b>Our Channels:</b>\n";
                $welcome .= "🍿 Main: @EntertainmentTadka786\n";
                $welcome .= "🎭 Theater: @threater_print_movies\n";
                $welcome .= "📥 Requests: @EntertainmentTadka7860\n";
                $welcome .= "🔒 Backup: @ETBackup\n\n";
                
                $welcome .= "🎯 <b>Request Movies:</b>\n";
                $welcome .= "• Use /request movie_name\n";
                $welcome .= "• Example: /request Avengers Endgame\n\n";
                
                $welcome .= "💡 <b>Tip:</b> Use /help for all commands";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                            ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']
                        ],
                        [
                            ['text' => '📥 Request Movie', 'url' => 'https://t.me/EntertainmentTadka7860'],
                            ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']
                        ],
                        [
                            ['text' => '❓ Help', 'callback_data' => 'help_command'],
                            ['text' => '📊 Stats', 'callback_data' => 'show_stats']
                        ],
                        [
                            ['text' => '📥 Request a Movie', 'callback_data' => 'show_request_form']
                        ]
                    ]
                ];
                
                sendMessage($chat_id, $welcome, $keyboard, 'HTML');
                update_user_points($user_id, 'daily_login');
            }
            elseif ($command == '/help') {
                sendChatAction($chat_id, 'typing');
                $help = "🤖 <b>Entertainment Tadka Bot - Help</b>\n\n";
                
                $help .= "📋 <b>Available Commands:</b>\n";
                $help .= "/start - Welcome message with channel links\n";
                $help .= "/help - Show this help message\n";
                $help .= "/request - Request a new movie (max 3 per day)\n";
                $help .= "/myrequests - View your request status\n";
                $help .= "/checkdate - Show date-wise statistics\n";
                $help .= "/totalupload - Browse all movies with pagination\n";
                $help .= "/testcsv - View all movies in database\n";
                $help .= "/checkcsv - Check CSV data (add 'all' for full list)\n";
                $help .= "/csvstats - CSV statistics\n";
                
                if ($user_id == ADMIN_ID) {
                    $help .= "/stats - Admin statistics (Admin only)\n";
                    $help .= "/pendingrequests - View pending requests (Admin only)\n";
                }
                
                $help .= "\n🔍 <b>How to Search:</b>\n";
                $help .= "• Type any movie name (English/Hindi)\n";
                $help .= "• Partial names work too\n";
                $help .= "• Example: 'kgf', 'pushpa', 'hindi movie'\n\n";
                
                $help .= "🎬 <b>Channel Information:</b>\n";
                $help .= "🍿 Main: @EntertainmentTadka786\n";
                $help .= "🎭 Theater: @threater_print_movies\n";
                $help .= "📥 Requests: @EntertainmentTadka7860\n";
                $help .= "🔒 Backup: @ETBackup\n\n";
                
                $help .= "⚠️ <b>Note:</b> This bot works with webhook. If you face issues, contact admin.";
                
                sendMessage($chat_id, $help, null, 'HTML');
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
                $show_all = (isset($args[0]) && strtolower($args[0]) == 'all');
                show_csv_data($chat_id, $show_all);
            }
            elseif ($command == '/csvstats') {
                csv_stats_command($chat_id);
            }
            elseif ($command == '/stats' && $user_id == ADMIN_ID) {
                admin_stats($chat_id);
            }
            elseif ($command == '/request') {
                if (empty($args)) {
                    sendMessage($chat_id,
                        "📝 <b>How to Request a Movie:</b>\n\n" .
                        "Usage: <code>/request Movie Name</code>\n\n" .
                        "📋 <b>Examples:</b>\n" .
                        "<code>/request Avengers Endgame</code>\n" .
                        "<code>/request Jawan 2023</code>\n" .
                        "<code>/request Hindi movie 2024</code>\n\n" .
                        "⚠️ <b>Limits:</b> Max 3 requests per day\n" .
                        "✅ You'll be notified when approved.",
                        null, 'HTML');
                    break;
                }
                
                $movie = implode(' ', $args);
                handle_movie_request($chat_id, $user_id, $username, $movie);
                update_user_points($user_id, 'request_submitted');
            }
            elseif ($command == '/myrequests') {
                $requests = load_json(REQUEST_FILE);
                $user_requests = [];
                
                foreach ($requests as $r) {
                    if (isset($r['user_id']) && $r['user_id'] == $user_id) {
                        $user_requests[] = $r;
                    }
                }
                
                if (empty($user_requests)) {
                    sendMessage($chat_id,
                        "📭 <b>No Requests Found</b>\n\n" .
                        "You haven't made any movie requests yet.\n" .
                        "Use <code>/request Movie Name</code> to request a movie.",
                        null, 'HTML');
                    break;
                }
                
                $message = "📋 <b>Your Movie Requests</b>\n\n";
                $pending_count = 0;
                $approved_count = 0;
                $rejected_count = 0;
                
                foreach ($user_requests as $i => $r) {
                    $status_emoji = $r['status'] == 'pending' ? '⏳' : 
                                   ($r['status'] == 'approved' ? '✅' : '❌');
                    $time_ago = time() - $r['time'];
                    $hours = floor($time_ago / 3600);
                    
                    $message .= "<b>" . ($i + 1) . ".</b> $status_emoji <b>" . htmlspecialchars($r['movie']) . "</b>\n";
                    $message .= "   📊 <b>Status:</b> " . ucfirst($r['status']) . "\n";
                    $message .= "   ⏰ <b>Time:</b> " . ($hours > 0 ? "$hours hours ago" : "Recently") . "\n\n";
                    
                    if ($r['status'] == 'pending') $pending_count++;
                    elseif ($r['status'] == 'approved') $approved_count++;
                    elseif ($r['status'] == 'rejected') $rejected_count++;
                }
                
                $message .= "📊 <b>Summary:</b>\n";
                $message .= "• ⏳ Pending: $pending_count\n";
                $message .= "• ✅ Approved: $approved_count\n";
                $message .= "• ❌ Rejected: $rejected_count\n";
                $message .= "• 📊 Total: " . count($user_requests) . "\n\n";
                
                $message .= "💡 <b>Tip:</b> Use /request to make new requests";
                
                sendMessage($chat_id, $message, null, 'HTML');
            }
            elseif ($command == '/pendingrequests') {
                if (!in_array($user_id, $ADMIN_IDS)) {
                    sendMessage($chat_id, "⛔ Admin only command", null, 'HTML');
                    break;
                }
                
                $limit = 5;
                $filter = '';
                
                if (!empty($args)) {
                    if (is_numeric($args[0])) {
                        $limit = min(10, max(1, (int)$args[0]));
                    } else {
                        $filter = implode(' ', $args);
                    }
                }
                
                send_pending_requests_page($chat_id, 1, $limit, $filter);
            }
        } 
        elseif (!empty(trim($text))) {
            // Check if this is a request message
            if (is_request_message($text)) {
                $movie = extract_movie_from_request($text);
                if (!empty($movie)) {
                    handle_movie_request($chat_id, $user_id, $username, $movie);
                    update_user_points($user_id, 'request_submitted');
                } else {
                    sendMessage($chat_id,
                        "🤔 <b>Movie Request</b>\n\n" .
                        "I detected you might want to request a movie.\n" .
                        "Please use: <code>/request Movie Name</code>\n\n" .
                        "Example: <code>/request Avengers Endgame</code>",
                        null, 'HTML');
                }
            } else {
                advanced_search($chat_id, $text, $user_id);
            }
        }
    }
    
    // Process callback queries
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $data = $query['data'];
        $user_id = $query['from']['id'];
        
        log_error("Callback query received", 'INFO', [
            'callback_data' => $data,
            'user_id' => $user_id
        ]);
        
        // Show typing indicator
        sendChatAction($chat_id, 'typing');
        
        if (strpos($data, 'movie_') === 0) {
            // Movie selection from search results
            $movie_name_encoded = str_replace('movie_', '', $data);
            $movie_name = base64_decode($movie_name_encoded);
            
            log_error("Movie selected", 'INFO', ['movie_name' => $movie_name]);
            
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
                    
                    log_error("Movie delivery completed", 'INFO', [
                        'movie' => $movie_name,
                        'sent_count' => $sent_count
                    ]);
                } else {
                    answerCallbackQuery($query['id'], "❌ Movie not found", true);
                    log_error("Movie not found after selection", 'WARNING', ['movie' => $movie_name]);
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
        elseif ($data === 'help_command') {
            $help_text = "🤖 <b>Entertainment Tadka Bot - Help</b>\n\n";
            
            $help_text .= "📋 <b>Available Commands:</b>\n";
            $help_text .= "/start - Welcome message with channel links\n";
            $help_text .= "/help - Show this help message\n";
            $help_text .= "/request - Request a new movie (max 3 per day)\n";
            $help_text .= "/myrequests - View your request status\n";
            $help_text .= "/checkdate - Show date-wise statistics\n";
            $help_text .= "/totalupload - Browse all movies with pagination\n";
            $help_text .= "/testcsv - View all movies in database\n";
            $help_text .= "/checkcsv - Check CSV data (add 'all' for full list)\n";
            $help_text .= "/csvstats - CSV statistics\n";
            
            if ($user_id == ADMIN_ID) {
                $help_text .= "/stats - Admin statistics (Admin only)\n";
                $help_text .= "/pendingrequests - View pending requests (Admin only)\n";
            }
            
            $help_text .= "\n🔍 <b>How to Search:</b>\n";
            $help_text .= "• Type any movie name (English/Hindi)\n";
            $help_text .= "• Partial names work too\n";
            $help_text .= "• Example: 'kgf', 'pushpa', 'hindi movie'\n\n";
            
            $help_text .= "🎬 <b>Channel Information:</b>\n";
            $help_text .= "🍿 Main: @EntertainmentTadka786\n";
            $help_text .= "🎭 Theater: @threater_print_movies\n";
            $help_text .= "📥 Requests: @EntertainmentTadka7860\n";
            $help_text .= "🔒 Backup: @ETBackup\n\n";
            
            $help_text .= "⚠️ <b>Note:</b> This bot works with webhook. If you face issues, contact admin.";
            
            editMessageText($chat_id, $message['message_id'], $help_text, [
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start']
                    ]
                ]
            ], 'HTML');
            
            answerCallbackQuery($query['id'], "Help information loaded");
        }
        elseif ($data === 'back_to_start') {
            $welcome = "🎬 <b>Welcome to Entertainment Tadka!</b>\n\n";
            
            $welcome .= "📢 <b>How to use this bot:</b>\n";
            $welcome .= "• Simply type any movie name\n";
            $welcome .= "• Use English or Hindi\n";
            $welcome .= "• Add 'theater' for theater prints\n";
            $welcome .= "• Partial names also work\n\n";
            
            $welcome .= "🔍 <b>Examples:</b>\n";
            $welcome .= "• Mandala Murders 2025\n";
            $welcome .= "• Lokah Chapter 1 Chandra 2025\n";
            $welcome .= "• Idli Kadai (2025)\n";
            $welcome .= "• IT - Welcome to Derry (2025) S01\n";
            $welcome .= "• hindi movie\n";
            $welcome .= "• kgf\n\n";
            
            $welcome .= "📢 <b>Our Channels:</b>\n";
            $welcome .= "🍿 Main: @EntertainmentTadka786\n";
            $welcome .= "🎭 Theater: @threater_print_movies\n";
            $welcome .= "📥 Requests: @EntertainmentTadka7860\n";
            $welcome .= "🔒 Backup: @ETBackup\n\n";
            
            $welcome .= "🎯 <b>Request Movies:</b>\n";
            $welcome .= "• Use /request movie_name\n";
            $welcome .= "• Example: /request Avengers Endgame\n\n";
            
            $welcome .= "💡 <b>Tip:</b> Use /help for all commands";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                        ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']
                    ],
                    [
                        ['text' => '📥 Request Movie', 'url' => 'https://t.me/EntertainmentTadka7860'],
                        ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']
                    ],
                    [
                        ['text' => '❓ Help', 'callback_data' => 'help_command'],
                        ['text' => '📊 Stats', 'callback_data' => 'show_stats']
                    ],
                    [
                        ['text' => '📥 Request a Movie', 'callback_data' => 'show_request_form']
                    ]
                ]
            ];
            
            editMessageText($chat_id, $message['message_id'], $welcome, $keyboard, 'HTML');
            
            answerCallbackQuery($query['id'], "Welcome back!");
        }
        elseif ($data === 'show_stats') {
            $stats = $csvManager->getStats();
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $total_users = count($users_data['users'] ?? []);
            
            $stats_text = "📊 <b>Bot Statistics</b>\n\n";
            $stats_text .= "🎬 <b>Total Movies:</b> " . $stats['total_movies'] . "\n";
            $stats_text .= "👥 <b>Total Users:</b> " . $total_users . "\n";
            
            $file_stats = json_decode(file_get_contents(STATS_FILE), true);
            $stats_text .= "🔍 <b>Total Searches:</b> " . ($file_stats['total_searches'] ?? 0) . "\n";
            $stats_text .= "🕒 <b>Last Updated:</b> " . $stats['last_updated'] . "\n\n";
            
            $stats_text .= "📡 <b>Movies by Channel:</b>\n";
            foreach ($stats['channels'] as $channel_id => $count) {
                $channel_name = getChannelUsername($channel_id);
                $channel_type = getChannelType($channel_id);
                $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
                $stats_text .= $type_icon . " " . $channel_name . ": " . $count . " movies\n";
            }
            
            editMessageText($chat_id, $message['message_id'], $stats_text, [
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start'],
                        ['text' => '🔄 Refresh', 'callback_data' => 'show_stats']
                    ]
                ]
            ], 'HTML');
            
            answerCallbackQuery($query['id'], "Statistics updated");
        }
        elseif ($data === 'show_request_form') {
            $request_text = "📝 <b>Request a Movie</b>\n\n";
            $request_text .= "To request a movie, use:\n";
            $request_text .= "<code>/request Movie Name</code>\n\n";
            $request_text .= "📋 <b>Examples:</b>\n";
            $request_text .= "<code>/request Avengers Endgame</code>\n";
            $request_text .= "<code>/request Jawan 2023</code>\n";
            $request_text .= "<code>/request Hindi movie 2024</code>\n\n";
            $request_text .= "⚠️ <b>Limits:</b>\n";
            $request_text .= "• Max 3 requests per day\n";
            $request_text .= "• No duplicate requests within 30 days\n\n";
            $request_text .= "✅ You'll be notified when your request is approved!";
            
            editMessageText($chat_id, $message['message_id'], $request_text, [
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start'],
                        ['text' => '📥 Make Request', 'url' => 'https://t.me/EntertainmentTadka7860']
                    ]
                ]
            ], 'HTML');
            
            answerCallbackQuery($query['id'], "Request form shown");
        }
        elseif (strpos($data, 'req_') === 0) {
            // Handle request callbacks (admin only)
            if (!in_array($user_id, $ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "⛔ Admin only", true);
                exit;
            }
            
            $requests = load_json(REQUEST_FILE);
            
            if (strpos($data, 'req_approve:') === 0) {
                $req_id = str_replace('req_approve:', '', $data);
                
                if (!isset($requests[$req_id])) {
                    answerCallbackQuery($query['id'], "❌ Request not found", true);
                    exit;
                }
                
                $requests[$req_id]['status'] = 'approved';
                $requests[$req_id]['approved_by'] = $user_id;
                $requests[$req_id]['approved_time'] = time();
                
                save_json(REQUEST_FILE, $requests);
                
                // Notify user
                notify_user_approved($requests[$req_id]['user_id'], $requests[$req_id]['movie']);
                
                // Update admin message
                $msg = "✅ <b>REQUEST APPROVED</b>\n\n";
                $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($requests[$req_id]['movie']) . "\n";
                $msg .= "👤 <b>User:</b> @" . htmlspecialchars($requests[$req_id]['username']) . "\n";
                $msg .= "🆔 <b>Request ID:</b> $req_id\n";
                $msg .= "✅ <b>Approved by:</b> Admin\n";
                $msg .= "⏰ <b>Time:</b> " . date('d M Y, H:i');
                
                editMessageText($chat_id, $message['message_id'], $msg, null, 'HTML');
                
                answerCallbackQuery($query['id'], "✅ Request approved");
                
                log_error("Request approved", 'INFO', [
                    'request_id' => $req_id,
                    'movie' => $requests[$req_id]['movie'],
                    'admin_id' => $user_id
                ]);
                
                update_stats('approved_requests', 1);
            }
            elseif (strpos($data, 'req_reject:') === 0) {
                $req_id = str_replace('req_reject:', '', $data);
                
                if (!isset($requests[$req_id])) {
                    answerCallbackQuery($query['id'], "❌ Request not found", true);
                    exit;
                }
                
                $requests[$req_id]['status'] = 'rejected';
                $requests[$req_id]['rejected_by'] = $user_id;
                $requests[$req_id]['rejected_time'] = time();
                
                save_json(REQUEST_FILE, $requests);
                
                // Notify user
                notify_user_rejected($requests[$req_id]['user_id'], $requests[$req_id]['movie']);
                
                // Update admin message
                $msg = "❌ <b>REQUEST REJECTED</b>\n\n";
                $msg .= "🎬 <b>Movie:</b> " . htmlspecialchars($requests[$req_id]['movie']) . "\n";
                $msg .= "👤 <b>User:</b> @" . htmlspecialchars($requests[$req_id]['username']) . "\n";
                $msg .= "🆔 <b>Request ID:</b> $req_id\n";
                $msg .= "❌ <b>Rejected by:</b> Admin\n";
                $msg .= "⏰ <b>Time:</b> " . date('d M Y, H:i');
                
                editMessageText($chat_id, $message['message_id'], $msg, null, 'HTML');
                
                answerCallbackQuery($query['id'], "❌ Request rejected");
                
                log_error("Request rejected", 'INFO', [
                    'request_id' => $req_id,
                    'movie' => $requests[$req_id]['movie'],
                    'admin_id' => $user_id
                ]);
                
                update_stats('rejected_requests', 1);
            }
            elseif (strpos($data, 'req_search:') === 0) {
                $req_id = str_replace('req_search:', '', $data);
                
                if (!isset($requests[$req_id])) {
                    answerCallbackQuery($query['id'], "❌ Request not found", true);
                    exit;
                }
                
                $movie = $requests[$req_id]['movie'];
                
                // Search in database
                global $csvManager;
                $found = $csvManager->searchMovies($movie);
                
                if (!empty($found)) {
                    $total_items = 0;
                    foreach ($found as $movie_data) {
                        $total_items += $movie_data['count'];
                    }
                    
                    $msg = "🔍 <b>Search Results for:</b> " . htmlspecialchars($movie) . "\n\n";
                    $msg .= "📊 Found " . count($found) . " movies ($total_items items)\n\n";
                    
                    $i = 1;
                    foreach ($found as $movie_name => $movie_data) {
                        $msg .= "$i. " . ucwords($movie_name) . " (" . $movie_data['count'] . " entries)\n";
                        $i++;
                        if ($i > 5) break;
                    }
                    
                    $msg .= "\n✅ <b>Movie already exists in database!</b>";
                } else {
                    $msg = "🔍 <b>Search Results for:</b> " . htmlspecialchars($movie) . "\n\n";
                    $msg .= "❌ <b>Not found in database</b>\n";
                    $msg .= "This is a new request.";
                }
                
                editMessageText($chat_id, $message['message_id'], $msg, [
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Approve Anyway', 'callback_data' => "req_approve:$req_id"],
                            ['text' => '❌ Reject', 'callback_data' => "req_reject:$req_id"]
                        ],
                        [
                            ['text' => '🔙 Back to Request', 'callback_data' => 'req_view_pending']
                        ]
                    ]
                ], 'HTML');
                
                answerCallbackQuery($query['id'], "Search completed");
            }
            elseif ($data === 'req_view_pending') {
                send_pending_requests_page($chat_id, 1, 5);
                answerCallbackQuery($query['id'], "Loading pending requests");
            }
            elseif (strpos($data, 'req_page:') === 0) {
                [, $page, $limit, $filter] = explode(':', $data, 4);
                send_pending_requests_page($chat_id, $page, $limit, $filter);
                answerCallbackQuery($query['id'], "Page $page");
            }
            elseif ($data === 'req_close') {
                editMessageText($chat_id, $message['message_id'], "❌ Request view closed.", null, 'HTML');
                answerCallbackQuery($query['id'], "Closed");
            }
            elseif (strpos($data, 'req_bulk_approve:') === 0) {
                [, $page, $limit, $filter] = explode(':', $data, 4);
                $requests = load_json(REQUEST_FILE);
                
                $pending = [];
                foreach ($requests as $id => $r) {
                    if (!isset($r['status']) || $r['status'] !== 'pending') continue;
                    
                    if (!empty($filter)) {
                        $movie_lower = strtolower($r['movie'] ?? '');
                        $filter_lower = strtolower($filter);
                        if (strpos($movie_lower, $filter_lower) === false) continue;
                    }
                    
                    $pending[$id] = $r;
                }
                
                $slice = array_slice($pending, ($page - 1) * $limit, $limit, true);
                $approved_count = 0;
                
                foreach ($slice as $id => $r) {
                    $requests[$id]['status'] = 'approved';
                    $requests[$id]['approved_by'] = $user_id;
                    $requests[$id]['approved_time'] = time();
                    
                    // Notify user
                    notify_user_approved($r['user_id'], $r['movie']);
                    $approved_count++;
                    
                    update_stats('approved_requests', 1);
                }
                
                save_json(REQUEST_FILE, $requests);
                
                answerCallbackQuery($query['id'], "✅ $approved_count requests approved");
                send_pending_requests_page($chat_id, $page, $limit, $filter);
            }
            elseif (strpos($data, 'req_bulk_reject:') === 0) {
                [, $page, $limit, $filter] = explode(':', $data, 4);
                $requests = load_json(REQUEST_FILE);
                
                $pending = [];
                foreach ($requests as $id => $r) {
                    if (!isset($r['status']) || $r['status'] !== 'pending') continue;
                    
                    if (!empty($filter)) {
                        $movie_lower = strtolower($r['movie'] ?? '');
                        $filter_lower = strtolower($filter);
                        if (strpos($movie_lower, $filter_lower) === false) continue;
                    }
                    
                    $pending[$id] = $r;
                }
                
                $slice = array_slice($pending, ($page - 1) * $limit, $limit, true);
                $rejected_count = 0;
                
                foreach ($slice as $id => $r) {
                    $requests[$id]['status'] = 'rejected';
                    $requests[$id]['rejected_by'] = $user_id;
                    $requests[$id]['rejected_time'] = time();
                    
                    // Notify user
                    notify_user_rejected($r['user_id'], $r['movie']);
                    $rejected_count++;
                    
                    update_stats('rejected_requests', 1);
                }
                
                save_json(REQUEST_FILE, $requests);
                
                answerCallbackQuery($query['id'], "❌ $rejected_count requests rejected");
                send_pending_requests_page($chat_id, $page, $limit, $filter);
            }
        }
    }
    
    // Daily maintenance at 3 AM
    if (date('H:i') == '03:00') {
        $csvManager->flushBuffer();
        $csvManager->clearCache();
        log_error("Daily maintenance completed", 'INFO');
    }
    
    // Send HTTP 200 OK response
    http_response_code(200);
    echo "OK";
    exit;
}

// ==================== DEFAULT HTML PAGE ====================
// If no update and not a test request, show the info page
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Entertainment Tadka Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.8em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            color: #fff;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #4CAF50;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
            100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
        }
        
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
            min-width: 200px;
            text-align: center;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-warning {
            background: #FF9800;
            color: white;
        }
        
        .btn-warning:hover {
            background: #F57C00;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .channels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .channel-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 12px;
            transition: transform 0.3s ease;
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
        
        .feature-list {
            margin: 30px 0;
        }
        
        .feature-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: background 0.3s ease;
        }
        
        .feature-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .feature-item::before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
            font-size: 1.2em;
            margin-right: 15px;
            min-width: 30px;
            text-align: center;
        }
        
        .stats-panel {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .btn {
                width: 100%;
                min-width: auto;
            }
            
            .channels-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Bot</h1>
        
        <div class="status-card">
            <h2>✅ Bot is Running</h2>
            <p>Telegram Bot for movie searches across multiple channels | Hosted on Render.com</p>
            <p><strong>Now with Movie Request System! 🎉</strong></p>
        </div>
        
        <div class="btn-group">
            <a href="?setup=1" class="btn btn-primary">
                <span class="icon">🔗</span> Set Webhook
            </a>
            <a href="?test=1" class="btn btn-secondary">
                <span class="icon">🧪</span> Test Bot
            </a>
            <a href="?deletehook=1" class="btn btn-warning">
                <span class="icon">🗑️</span> Delete Webhook
            </a>
        </div>
        
        <div class="stats-panel">
            <h3>📊 Current Statistics</h3>
            <div class="stats-grid">
                <?php
                $csvManager = CSVManager::getInstance();
                $stats = $csvManager->getStats();
                $users_data = json_decode(@file_get_contents(USERS_FILE), true);
                $total_users = count($users_data['users'] ?? []);
                $requests = load_json(REQUEST_FILE);
                $pending_requests = 0;
                
                foreach ($requests as $r) {
                    if (isset($r['status']) && $r['status'] === 'pending') {
                        $pending_requests++;
                    }
                }
                ?>
                <div class="stat-item">
                    <div>🎬 Total Movies</div>
                    <div class="stat-value"><?php echo $stats['total_movies']; ?></div>
                </div>
                <div class="stat-item">
                    <div>👥 Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-item">
                    <div>📥 Pending Requests</div>
                    <div class="stat-value"><?php echo $pending_requests; ?></div>
                </div>
                <div class="stat-item">
                    <div>🕒 Uptime</div>
                    <div class="stat-value">100%</div>
                </div>
            </div>
        </div>
        
        <h3>📡 Configured Channels</h3>
        <div class="channels-grid">
            <?php
            // Display public channels
            foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
                if (!empty($channel['username'])) {
                    echo '<div class="channel-card public">';
                    echo '<div style="font-weight: bold; margin-bottom: 8px;">🌐 Public Channel</div>';
                    echo '<div style="font-size: 1.1em; margin-bottom: 5px;">' . htmlspecialchars($channel['username']) . '</div>';
                    echo '<div style="font-size: 0.9em; opacity: 0.8;">ID: ' . htmlspecialchars($channel['id']) . '</div>';
                    echo '</div>';
                }
            }
            
            // Display private channels
            foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
                echo '<div class="channel-card private">';
                echo '<div style="font-weight: bold; margin-bottom: 8px;">🔒 Private Channel</div>';
                echo '<div style="font-size: 1.1em; margin-bottom: 5px;">' . htmlspecialchars($channel['username'] ?: 'Private Channel') . '</div>';
                echo '<div style="font-size: 0.9em; opacity: 0.8;">ID: ' . htmlspecialchars($channel['id']) . '</div>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="feature-list">
            <h3>✨ Features</h3>
            <div class="feature-item">Multi-channel support (Public & Private channels)</div>
            <div class="feature-item">Smart movie search with partial matching</div>
            <div class="feature-item">Movie Request System with admin approval</div>
            <div class="feature-item">Request flood control (max 3 per day)</div>
            <div class="feature-item">Duplicate request detection</div>
            <div class="feature-item">Admin panel for request management</div>
            <div class="feature-item">Bulk approve/reject requests</div>
            <div class="feature-item">Automatic user notifications</div>
            <div class="feature-item">CSV-based database with intelligent caching</div>
            <div class="feature-item">Webhook support for Render.com hosting</div>
        </div>
        
        <div style="margin-top: 40px; padding: 25px; background: rgba(255, 255, 255, 0.15); border-radius: 15px;">
            <h3>🚀 Quick Start Guide</h3>
            <ol style="margin-left: 20px; margin-top: 15px;">
                <li style="margin-bottom: 10px;">Click "Set Webhook" to configure Telegram webhook</li>
                <li style="margin-bottom: 10px;">Test the bot using the "Test Bot" button</li>
                <li style="margin-bottom: 10px;">Start searching movies in Telegram bot</li>
                <li style="margin-bottom: 10px;">Use /request to submit movie requests</li>
                <li style="margin-bottom: 10px;">Admins can use /pendingrequests to manage requests</li>
            </ol>
        </div>
        
        <footer>
            <p>🎬 Entertainment Tadka Bot | Powered by PHP & Telegram Bot API | Hosted on Render.com</p>
            <p style="margin-top: 10px; font-size: 0.9em;">© <?php echo date('Y'); ?> - All rights reserved</p>
        </footer>
    </div>
</body>
</html>
<?php
// End of file
?>
