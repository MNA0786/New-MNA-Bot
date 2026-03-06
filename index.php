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
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '8315381064:AAFwATwGTSzJf7YY5QkayO3T2bnp50f23eU',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: '@EntertainmentTadkaBot',
    'BOT_ID' => '8315381064',
    
    // Admin IDs (comma separated)
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    
    // API Credentials for monitoring
    'API_ID' => '21944581',
    'API_HASH' => '7b1c174a5cd3466e25a976c39a791737',
    
    // Public Channels
    'PUBLIC_CHANNELS' => [
        [
            'id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395',
            'username' => getenv('PUBLIC_CHANNEL_1_USERNAME') ?: '@EntertainmentTadka786'
        ],
        [
            'id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1003614546520',
            'username' => getenv('PUBLIC_CHANNEL_2_USERNAME') ?: '@Entertainment_Tadka_Serial_786'
        ],
        [
            'id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002831605258',
            'username' => getenv('PUBLIC_CHANNEL_3_USERNAME') ?: '@threater_print_movies'
        ],
        [
            'id' => getenv('PUBLIC_CHANNEL_4_ID') ?: '-1002964109368',
            'username' => getenv('PUBLIC_CHANNEL_4_USERNAME') ?: '@ETBackup'
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
        ]
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
    'INDEX_FILE' => 'cache/movie_index.json',
    'METRICS_FILE' => 'cache/metrics.json',
    
    // Settings
    'CACHE_EXPIRY' => 300,
    'ITEMS_PER_PAGE' => 5,
    'CSV_BUFFER_SIZE' => 50,
    
    // Request System Settings
    'MAX_REQUESTS_PER_DAY' => 3,
    'DUPLICATE_CHECK_HOURS' => 24,
    'REQUEST_SYSTEM_ENABLED' => true,
    'REPUTATION_BONUS_REQUESTS' => 2,
    'REPUTATION_THRESHOLD' => 100,
    
    // Security Settings
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60
];

if (empty($ENV_CONFIG['BOT_TOKEN']) || $ENV_CONFIG['BOT_TOKEN'] === 'YOUR_BOT_TOKEN_HERE') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die("❌ Bot Token not configured. Please set BOT_TOKEN environment variable.");
}

define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_IDS', $ENV_CONFIG['ADMIN_IDS']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUESTS_FILE', $ENV_CONFIG['REQUESTS_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('INDEX_FILE', $ENV_CONFIG['INDEX_FILE']);
define('METRICS_FILE', $ENV_CONFIG['METRICS_FILE']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);
define('RATE_LIMIT_REQUESTS', $ENV_CONFIG['RATE_LIMIT_REQUESTS']);
define('RATE_LIMIT_WINDOW', $ENV_CONFIG['RATE_LIMIT_WINDOW']);
define('REPUTATION_BONUS_REQUESTS', $ENV_CONFIG['REPUTATION_BONUS_REQUESTS']);
define('REPUTATION_THRESHOLD', $ENV_CONFIG['REPUTATION_THRESHOLD']);

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
            $allowed_files = ['movies.csv', 'users.json', 'bot_stats.json', 'requests.json', 'cache/movie_index.json', 'cache/metrics.json'];
            return in_array($input, $allowed_files) ? $input : false;
            
        default:
            return $input;
    }
}

function secureFilePath($path) {
    $real_path = realpath($path);
    if ($real_path === false) {
        return false;
    }
    
    $allowed_dir = realpath(__DIR__);
    if ($allowed_dir === false) {
        return false;
    }
    
    if (strpos($real_path, $allowed_dir) !== 0) {
        log_error("Security violation: Path traversal attempt on $path", 'CRITICAL');
        return false;
    }
    return $real_path;
}

function secureFileOperation($filename, $operation = 'read') {
    $filename = validateInput($filename, 'filename');
    if (!$filename) {
        return false;
    }
    
    $full_path = __DIR__ . '/' . $filename;
    if (secureFilePath($full_path) === false) {
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
    private static $ip_blocks = [];
    
    public static function check($key, $limit = 30, $window = 60) {
        $now = time();
        $window_start = $now - $window;
        
        // IP-based blocking for repeated attacks
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (isset(self::$ip_blocks[$ip]) && self::$ip_blocks[$ip] > $now) {
            log_error("Blocked IP $ip attempted access", 'SECURITY');
            return false;
        }
        
        if (!isset(self::$limits[$key])) {
            self::$limits[$key] = [];
        }
        
        self::$limits[$key] = array_filter(self::$limits[$key], 
            function($time) use ($window_start) {
                return $time > $window_start;
            });
        
        if (count(self::$limits[$key]) >= $limit) {
            // Block IP for 5 minutes after 3 violations
            $violations = self::getViolations($ip);
            if ($violations > 3) {
                self::$ip_blocks[$ip] = $now + 300; // 5 minutes block
                log_error("IP $ip blocked for 5 minutes due to excessive violations", 'SECURITY');
            }
            log_error("Rate limit exceeded for key: $key", 'WARNING');
            return false;
        }
        
        self::$limits[$key][] = $now;
        return true;
    }
    
    private static function getViolations($ip) {
        static $violations = [];
        $now = time();
        
        if (!isset($violations[$ip])) {
            $violations[$ip] = [];
        }
        
        $violations[$ip] = array_filter($violations[$ip], function($time) use ($now) {
            return $time > $now - 3600; // Last hour
        });
        
        return count($violations[$ip]);
    }
}

// ==================== MONITORING SYSTEM ====================
class BotMonitor {
    private static $instance = null;
    private $metrics_file;
    private $start_time;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->metrics_file = METRICS_FILE;
        $this->start_time = microtime(true);
        $this->initializeMetrics();
    }
    
    private function initializeMetrics() {
        if (!file_exists($this->metrics_file)) {
            $default_metrics = [
                'response_times' => [],
                'memory_usage' => [],
                'csv_size_history' => [],
                'active_users' => [],
                'popular_searches' => [],
                'error_count' => 0,
                'last_cleanup' => date('Y-m-d H:i:s'),
                'api_calls' => 0,
                'avg_response_time' => 0
            ];
            file_put_contents($this->metrics_file, json_encode($default_metrics, JSON_PRETTY_PRINT));
        }
    }
    
    public function trackPerformance() {
        $metrics = $this->loadMetrics();
        
        // Response time
        $response_time = microtime(true) - $this->start_time;
        $metrics['response_times'][] = round($response_time, 4);
        $metrics['response_times'] = array_slice($metrics['response_times'], -100); // Keep last 100
        
        // Memory usage
        $metrics['memory_usage'][] = memory_get_peak_usage(true);
        $metrics['memory_usage'] = array_slice($metrics['memory_usage'], -100);
        
        // CSV size
        if (file_exists(CSV_FILE)) {
            $metrics['csv_size_history'][] = filesize(CSV_FILE);
            $metrics['csv_size_history'] = array_slice($metrics['csv_size_history'], -24); // Last 24 readings
        }
        
        // Calculate averages
        if (!empty($metrics['response_times'])) {
            $metrics['avg_response_time'] = array_sum($metrics['response_times']) / count($metrics['response_times']);
        }
        
        // Auto-cache clear if slow response
        if ($metrics['avg_response_time'] > 2.0) {
            log_error("Slow response detected! Avg: " . $metrics['avg_response_time'] . "s", 'WARNING');
            global $csvManager;
            $csvManager->clearCache();
            $metrics['auto_cache_cleared'] = date('Y-m-d H:i:s');
        }
        
        $this->saveMetrics($metrics);
    }
    
    public function trackSearch($query, $found_count) {
        $metrics = $this->loadMetrics();
        
        $query_lower = strtolower(trim($query));
        if (!isset($metrics['popular_searches'][$query_lower])) {
            $metrics['popular_searches'][$query_lower] = [
                'count' => 0,
                'last_searched' => null,
                'found_count' => 0
            ];
        }
        
        $metrics['popular_searches'][$query_lower]['count']++;
        $metrics['popular_searches'][$query_lower]['last_searched'] = date('Y-m-d H:i:s');
        $metrics['popular_searches'][$query_lower]['found_count'] += $found_count;
        
        // Keep only top 50 popular searches
        uasort($metrics['popular_searches'], function($a, $b) {
            return $b['count'] - $a['count'];
        });
        $metrics['popular_searches'] = array_slice($metrics['popular_searches'], 0, 50, true);
        
        $this->saveMetrics($metrics);
    }
    
    public function trackUserActivity($user_id) {
        $metrics = $this->loadMetrics();
        
        $hour = date('Y-m-d H:00:00');
        if (!isset($metrics['active_users'][$hour])) {
            $metrics['active_users'][$hour] = [];
        }
        
        if (!in_array($user_id, $metrics['active_users'][$hour])) {
            $metrics['active_users'][$hour][] = $user_id;
        }
        
        // Keep only last 24 hours
        $metrics['active_users'] = array_slice($metrics['active_users'], -24, null, true);
        
        $this->saveMetrics($metrics);
    }
    
    public function trackApiCall() {
        $metrics = $this->loadMetrics();
        $metrics['api_calls'] = ($metrics['api_calls'] ?? 0) + 1;
        $this->saveMetrics($metrics);
    }
    
    public function trackError() {
        $metrics = $this->loadMetrics();
        $metrics['error_count'] = ($metrics['error_count'] ?? 0) + 1;
        $this->saveMetrics($metrics);
    }
    
    public function getMetrics() {
        return $this->loadMetrics();
    }
    
    public function getActiveUsersLastHour() {
        $metrics = $this->loadMetrics();
        $last_hour = date('Y-m-d H:00:00', strtotime('-1 hour'));
        return count($metrics['active_users'][$last_hour] ?? []);
    }
    
    private function loadMetrics() {
        $data = json_decode(file_get_contents($this->metrics_file), true);
        return $data ?: [];
    }
    
    private function saveMetrics($metrics) {
        file_put_contents($this->metrics_file, json_encode($metrics, JSON_PRETTY_PRINT));
    }
}

// ==================== ADMIN PANEL ====================
class AdminPanel {
    private static $instance = null;
    private $admin_ids = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->admin_ids = ADMIN_IDS;
    }
    
    public function isAdmin($user_id) {
        return in_array($user_id, $this->admin_ids);
    }
    
    public function getAdminMainMenu() {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Bot Stats', 'callback_data' => 'admin_stats'],
                    ['text' => '📋 Pending Requests', 'callback_data' => 'admin_pending']
                ],
                [
                    ['text' => '👥 Users', 'callback_data' => 'admin_users'],
                    ['text' => '🎬 Movies', 'callback_data' => 'admin_movies']
                ],
                [
                    ['text' => '📢 Broadcast', 'callback_data' => 'admin_broadcast'],
                    ['text' => '⚙️ Settings', 'callback_data' => 'admin_settings']
                ],
                [
                    ['text' => '📁 Backup', 'callback_data' => 'admin_backup'],
                    ['text' => '📊 Performance', 'callback_data' => 'admin_performance']
                ],
                [
                    ['text' => '🚪 Close', 'callback_data' => 'admin_close']
                ]
            ]
        ];
        
        $message = "🔐 <b>Admin Control Panel</b>\n\n";
        $message .= "Welcome to Admin Dashboard!\n";
        $message .= "Select an option below:\n\n";
        $message .= "• 📊 View bot statistics\n";
        $message .= "• 📋 Manage movie requests\n";
        $message .= "• 👥 View user list\n";
        $message .= "• 🎬 Browse movies\n";
        $message .= "• 📢 Send broadcast message\n";
        $message .= "• ⚙️ Configure settings\n";
        $message .= "• 📁 Backup database\n";
        $message .= "• 📊 Performance metrics";
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getStats($csvManager, $requestSystem, $monitor) {
        $csv_stats = $csvManager->getStats();
        $request_stats = $requestSystem->getStats();
        $metrics = $monitor->getMetrics();
        
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        $total_users = count($users_data['users'] ?? []);
        $active_last_hour = $monitor->getActiveUsersLastHour();
        
        $file_stats = json_decode(file_get_contents(STATS_FILE), true);
        
        $channels_text = "";
        // Fix: Properly access channels from $ENV_CONFIG
        global $ENV_CONFIG;
        foreach ($csv_stats['channels'] as $channel_id => $count) {
            $channel_name = getChannelUsername($channel_id);
            $channel_type = getChannelType($channel_id);
            $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
            $channels_text .= "$type_icon $channel_name: $count movies\n";
        }
        
        $message = "📊 <b>Bot Statistics</b>\n\n";
        $message .= "🎬 <b>Movies:</b> " . $csv_stats['total_movies'] . "\n";
        $message .= "👥 <b>Users:</b> $total_users\n";
        $message .= "👤 <b>Active (1h):</b> $active_last_hour\n";
        $message .= "🔍 <b>Searches:</b> " . ($file_stats['total_searches'] ?? 0) . "\n";
        $message .= "📡 <b>API Calls:</b> " . ($metrics['api_calls'] ?? 0) . "\n";
        $message .= "⚡ <b>Avg Response:</b> " . round(($metrics['avg_response_time'] ?? 0) * 1000, 2) . "ms\n\n";
        
        $message .= "📋 <b>Requests:</b>\n";
        $message .= "• Total: " . $request_stats['total_requests'] . "\n";
        $message .= "• Pending: " . $request_stats['pending'] . "\n";
        $message .= "• Approved: " . $request_stats['approved'] . "\n";
        $message .= "• Rejected: " . $request_stats['rejected'] . "\n\n";
        
        $message .= "📡 <b>Channels:</b>\n$channels_text\n";
        $message .= "🕒 <b>Last Updated:</b> " . $csv_stats['last_updated'];
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Refresh', 'callback_data' => 'admin_stats'],
                    ['text' => '🔙 Back', 'callback_data' => 'admin_main']
                ]
            ]
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getUsersList($page = 1) {
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        $users = $users_data['users'] ?? [];
        
        $total_users = count($users);
        $per_page = 10;
        $total_pages = ceil($total_users / $per_page);
        $page = max(1, min($page, $total_pages));
        $start = ($page - 1) * $per_page;
        
        $users_slice = array_slice($users, $start, $per_page, true);
        
        $message = "👥 <b>User List (Page $page/$total_pages)</b>\n\n";
        $message .= "Total Users: $total_users\n\n";
        
        foreach ($users_slice as $user_id => $user) {
            $name = $user['first_name'] ?? '';
            $name .= $user['last_name'] ? ' ' . $user['last_name'] : '';
            $username = $user['username'] ? '@' . $user['username'] : '';
            $points = $user['points'] ?? 0;
            $reputation = $user['reputation'] ?? 0;
            $last_active = isset($user['last_active']) ? date('d M', strtotime($user['last_active'])) : 'N/A';
            
            $message .= "🆔 <code>$user_id</code>\n";
            $message .= "👤 $name $username\n";
            $message .= "⭐ Points: $points | Rep: $reputation | Last: $last_active\n\n";
        }
        
        $keyboard = ['inline_keyboard' => []];
        $row = [];
        
        if ($page > 1) {
            $row[] = ['text' => '◀️ Prev', 'callback_data' => 'admin_users_' . ($page - 1)];
        }
        if ($page < $total_pages) {
            $row[] = ['text' => 'Next ▶️', 'callback_data' => 'admin_users_' . ($page + 1)];
        }
        if (!empty($row)) {
            $keyboard['inline_keyboard'][] = $row;
        }
        
        $keyboard['inline_keyboard'][] = [
            ['text' => '🔙 Main Menu', 'callback_data' => 'admin_main']
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getMoviesList($csvManager, $page = 1) {
        $movies = $csvManager->getCachedData();
        $total_movies = count($movies);
        $per_page = 10;
        $total_pages = ceil($total_movies / $per_page);
        $page = max(1, min($page, $total_pages));
        $start = ($page - 1) * $per_page;
        
        $movies_slice = array_slice($movies, $start, $per_page);
        
        $message = "🎬 <b>Movies List (Page $page/$total_pages)</b>\n\n";
        $message .= "Total Movies: $total_movies\n\n";
        
        $i = $start + 1;
        foreach ($movies_slice as $movie) {
            $channel_name = getChannelUsername($movie['channel_id']);
            $message .= "$i. " . htmlspecialchars($movie['movie_name']) . "\n";
            $message .= "   📝 ID: {$movie['message_id']} | 📡 $channel_name\n\n";
            $i++;
        }
        
        $keyboard = ['inline_keyboard' => []];
        $row = [];
        
        if ($page > 1) {
            $row[] = ['text' => '◀️ Prev', 'callback_data' => 'admin_movies_' . ($page - 1)];
        }
        if ($page < $total_pages) {
            $row[] = ['text' => 'Next ▶️', 'callback_data' => 'admin_movies_' . ($page + 1)];
        }
        if (!empty($row)) {
            $keyboard['inline_keyboard'][] = $row;
        }
        
        $keyboard['inline_keyboard'][] = [
            ['text' => '🔙 Main Menu', 'callback_data' => 'admin_main']
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getPerformanceMetrics($monitor) {
        $metrics = $monitor->getMetrics();
        
        $message = "📊 <b>Performance Metrics</b>\n\n";
        
        // Response times
        $message .= "⚡ <b>Response Times (last 100):</b>\n";
        if (!empty($metrics['response_times'])) {
            $avg = array_sum($metrics['response_times']) / count($metrics['response_times']);
            $max = max($metrics['response_times']);
            $min = min($metrics['response_times']);
            $message .= "• Avg: " . round($avg * 1000, 2) . "ms\n";
            $message .= "• Max: " . round($max * 1000, 2) . "ms\n";
            $message .= "• Min: " . round($min * 1000, 2) . "ms\n\n";
        } else {
            $message .= "• No data yet\n\n";
        }
        
        // Memory usage
        $message .= "💾 <b>Memory Usage:</b>\n";
        if (!empty($metrics['memory_usage'])) {
            $peak = max($metrics['memory_usage']);
            $message .= "• Peak: " . round($peak / 1024 / 1024, 2) . " MB\n\n";
        }
        
        // Popular searches
        $message .= "🔥 <b>Popular Searches (Top 5):</b>\n";
        if (!empty($metrics['popular_searches'])) {
            $i = 1;
            foreach (array_slice($metrics['popular_searches'], 0, 5, true) as $query => $data) {
                $message .= "$i. \"$query\" - {$data['count']} times";
                if ($data['found_count'] > 0) {
                    $message .= " (found: {$data['found_count']})";
                }
                $message .= "\n";
                $i++;
            }
        } else {
            $message .= "• No searches yet\n";
        }
        
        $message .= "\n📊 <b>Other Metrics:</b>\n";
        $message .= "• API Calls: " . ($metrics['api_calls'] ?? 0) . "\n";
        $message .= "• Errors: " . ($metrics['error_count'] ?? 0) . "\n";
        $message .= "• Auto Cache Clears: " . (isset($metrics['auto_cache_cleared']) ? 1 : 0) . "\n";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Refresh', 'callback_data' => 'admin_performance'],
                    ['text' => '🔙 Main Menu', 'callback_data' => 'admin_main']
                ]
            ]
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getPendingRequestsView($requestSystem, $page = 1) {
        $requests = $requestSystem->getPendingRequests(50); // Get up to 50
        $total_pending = count($requests);
        $per_page = 5;
        $total_pages = ceil($total_pending / $per_page);
        $page = max(1, min($page, $total_pages));
        $start = ($page - 1) * $per_page;
        
        $requests_slice = array_slice($requests, $start, $per_page);
        
        $stats = $requestSystem->getStats();
        
        $message = "📋 <b>Pending Requests (Page $page/$total_pages)</b>\n\n";
        $message .= "📊 <b>Stats:</b>\n";
        $message .= "• Total Pending: {$stats['pending']}\n";
        $message .= "• Showing: " . count($requests_slice) . "\n\n";
        
        $keyboard = ['inline_keyboard' => []];
        
        foreach ($requests_slice as $req) {
            $movie_name = htmlspecialchars($req['movie_name']);
            $user_name = htmlspecialchars($req['user_name'] ?: "User " . $req['user_id']);
            $date = date('d M H:i', strtotime($req['created_at']));
            
            $message .= "🔸 <b>#{$req['id']}:</b> $movie_name\n";
            $message .= "   👤 $user_name | 📅 $date\n\n";
            
            $keyboard['inline_keyboard'][] = [
                [
                    'text' => "✅ Approve #{$req['id']}",
                    'callback_data' => "admin_approve_{$req['id']}"
                ],
                [
                    'text' => "❌ Reject #{$req['id']}",
                    'callback_data' => "admin_reject_{$req['id']}"
                ]
            ];
        }
        
        // Pagination row
        $nav_row = [];
        if ($page > 1) {
            $nav_row[] = ['text' => '◀️ Prev', 'callback_data' => 'admin_pending_' . ($page - 1)];
        }
        if ($page < $total_pages) {
            $nav_row[] = ['text' => 'Next ▶️', 'callback_data' => 'admin_pending_' . ($page + 1)];
        }
        if (!empty($nav_row)) {
            $keyboard['inline_keyboard'][] = $nav_row;
        }
        
        // Bulk actions
        if (!empty($requests_slice)) {
            $request_ids = array_column($requests_slice, 'id');
            $ids_json = base64_encode(json_encode($request_ids));
            
            $keyboard['inline_keyboard'][] = [
                [
                    'text' => '✅ Bulk Approve Page',
                    'callback_data' => 'admin_bulk_approve_' . $ids_json
                ],
                [
                    'text' => '❌ Bulk Reject Page',
                    'callback_data' => 'admin_bulk_reject_' . $ids_json
                ]
            ];
        }
        
        $keyboard['inline_keyboard'][] = [
            ['text' => '🔙 Main Menu', 'callback_data' => 'admin_main']
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getSettingsMenu() {
        $message = "⚙️ <b>Admin Settings</b>\n\n";
        $message .= "Select setting to configure:\n\n";
        $message .= "• 🎬 Request System: " . (REQUEST_SYSTEM_ENABLED ? '✅ ON' : '❌ OFF') . "\n";
        $message .= "• 🔧 Maintenance: " . (MAINTENANCE_MODE ? '🔴 ON' : '🟢 OFF') . "\n";
        $message .= "• 📊 Items per page: " . ITEMS_PER_PAGE . "\n";
        $message .= "• 📝 Max requests/day: " . MAX_REQUESTS_PER_DAY . "\n";
        $message .= "• ⭐ Rep threshold: " . REPUTATION_THRESHOLD . "\n";
        $message .= "• ➕ Bonus requests: " . REPUTATION_BONUS_REQUESTS . "\n";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🎬 Toggle Request', 'callback_data' => 'admin_toggle_request'],
                    ['text' => '🔧 Toggle Maintenance', 'callback_data' => 'admin_toggle_maintenance']
                ],
                [
                    ['text' => '📊 Clear Cache', 'callback_data' => 'admin_clear_cache'],
                    ['text' => '📁 Backup Now', 'callback_data' => 'admin_backup_now']
                ],
                [
                    ['text' => '🔙 Main Menu', 'callback_data' => 'admin_main']
                ]
            ]
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function getBroadcastInstructions() {
        $message = "📢 <b>Broadcast Message</b>\n\n";
        $message .= "Please send the message you want to broadcast to all users.\n\n";
        $message .= "You can use:\n";
        $message .= "• HTML formatting\n";
        $message .= "• Emojis\n";
        $message .= "• Line breaks\n\n";
        $message .= "<b>Type your message below:</b>";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔙 Cancel', 'callback_data' => 'admin_main']
                ]
            ]
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
    
    public function confirmBroadcast($message_text, $user_count) {
        $preview = substr($message_text, 0, 200);
        if (strlen($message_text) > 200) {
            $preview .= '...';
        }
        
        $msg = "📢 <b>Broadcast Preview</b>\n\n";
        $msg .= "<b>Message:</b>\n$preview\n\n";
        $msg .= "<b>Total Users:</b> $user_count\n\n";
        $msg .= "Send this broadcast to all users?";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Yes, Send', 'callback_data' => 'admin_broadcast_send'],
                    ['text' => '❌ No, Cancel', 'callback_data' => 'admin_main']
                ]
            ]
        ];
        
        return ['text' => $msg, 'keyboard' => $keyboard];
    }
    
    public function getBackupMenu() {
        $backup_dir = BACKUP_DIR;
        $backup_files = glob($backup_dir . '*.{csv,json,zip}', GLOB_BRACE);
        $backup_count = count($backup_files);
        
        $last_backup = 'None';
        if ($backup_count > 0) {
            $last_file = end($backup_files);
            $last_backup = date('d M Y H:i', filemtime($last_file));
        }
        
        $message = "📁 <b>Backup Management</b>\n\n";
        $message .= "• Backup Directory: $backup_dir\n";
        $message .= "• Total Backups: $backup_count\n";
        $message .= "• Last Backup: $last_backup\n\n";
        $message .= "Choose an option:";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📦 Create Backup', 'callback_data' => 'admin_backup_create']
                ],
                [
                    ['text' => '📋 List Backups', 'callback_data' => 'admin_backup_list']
                ],
                [
                    ['text' => '🔙 Main Menu', 'callback_data' => 'admin_main']
                ]
            ]
        ];
        
        return ['text' => $message, 'keyboard' => $keyboard];
    }
}

// ==================== HINGLISH LANGUAGE FUNCTIONS ====================
function detectUserLanguage($text) {
    $hindi_pattern = '/[\x{0900}-\x{097F}]/u';
    $hindi_words = ['है', 'हूं', 'का', 'की', 'के', 'में', 'से', 'को', 'पर', 'और', 'या', 'यह', 'वह', 'मैं', 'तुम', 'आप', 'क्या', 'क्यों', 'कैसे', 'कब', 'कहां', 'नहीं', 'बहुत', 'अच्छा', 'बुरा', 'था', 'थी', 'थे', 'गया', 'गई', 'गए'];
    
    $is_hindi = preg_match($hindi_pattern, $text);
    
    if ($is_hindi) {
        return 'hindi';
    }
    
    $hinglish_words = ['hai', 'hain', 'ka', 'ki', 'ke', 'mein', 'se', 'ko', 'par', 'aur', 'kya', 'kyun', 'kaise', 'kab', 'kahan', 'nahi', 'bahut', 'acha', 'bura', 'tha', 'the', 'gaya', 'gayi', 'bole', 'bolo', 'kar', 'karo', 'de', 'do', 'le', 'lo'];
    
    $words = explode(' ', strtolower($text));
    $hinglish_count = 0;
    
    foreach ($words as $word) {
        if (in_array($word, $hinglish_words)) {
            $hinglish_count++;
        }
    }
    
    if ($hinglish_count >= 2) {
        return 'hinglish';
    }
    
    return 'english';
}

function getHinglishResponse($key, $vars = []) {
    $responses = [
        'welcome' => "🎬 <b>Entertainment Tadka mein aapka swagat hai!</b>\n\n" .
                     "📢 <b>Bot kaise use karein:</b>\n" .
                     "• Bus movie ka naam likho\n" .
                     "• English ya Hindi dono mein likh sakte ho\n" .
                     "• 'theater' add karo theater print ke liye\n" .
                     "• Thoda sa naam bhi kaafi hai\n\n" .
                     "🔍 <b>Examples:</b>\n" .
                     "• Mandala Murders 2025\n" .
                     "• Lokah Chapter 1 Chandra 2025\n" .
                     "• Idli Kadai (2025)\n" .
                     "• IT - Welcome to Derry (2025) S01\n" .
                     "• hindi movie\n" .
                     "• kgf\n\n" .
                     "📢 <b>Hamare Channels:</b>\n" .
                     "🍿 Main: @EntertainmentTadka786\n" .
                     "🎬 Serial: @Entertainment_Tadka_Serial_786\n" .
                     "🎭 Theater: @threater_print_movies\n" .
                     "🔒 Backup: @ETBackup\n" .
                     "📥 Requests: @EntertainmentTadka7860\n\n" .
                     "🎬 <b>Movie Request System:</b>\n" .
                     "• /request MovieName se request karo\n" .
                     "• Ya likho: 'pls add MovieName'\n" .
                     "• Status check karo /myrequests se\n" .
                     "• Roz sirf 3 requests kar sakte ho\n\n" .
                     "💡 <b>Tip:</b> /help se saari commands dekho",
        
        'welcome_hindi' => "🎬 <b>Entertainment Tadka mein aapka hardik swagat hai!</b>\n\n" .
                           "📢 <b>Bot kaise use karein:</b>\n" .
                           "• Bus movie ka naam likhiye\n" .
                           "• English ya Hindi dono mein likh sakte hain\n" .
                           "• 'theater' likhein theater print ke liye\n" .
                           "• Thoda sa naam bhi kaafi hai\n\n" .
                           "🔍 <b>Examples:</b>\n" .
                           "• Mandala Murders 2025\n" .
                           "• Lokah Chapter 1 Chandra 2025\n" .
                           "• Idli Kadai (2025)\n" .
                           "• IT - Welcome to Derry (2025) S01\n" .
                           "• hindi movie\n" .
                           "• kgf\n\n" .
                           "📢 <b>Hamare Channels:</b>\n" .
                           "🍿 Main: @EntertainmentTadka786\n" .
                           "🎬 Serial: @Entertainment_Tadka_Serial_786\n" .
                           "🎭 Theater: @threater_print_movies\n" .
                           "🔒 Backup: @ETBackup\n" .
                           "📥 Requests: @EntertainmentTadka7860\n\n" .
                           "🎬 <b>Movie Request System:</b>\n" .
                           "• /request MovieName se request karein\n" .
                           "• Ya likhein: 'pls add MovieName'\n" .
                           "• Status check karein /myrequests se\n" .
                           "• Roz sirf 3 requests kar sakte hain\n\n" .
                           "💡 <b>Tip:</b> /help se saari commands dekhein",
        
        'help' => "🤖 <b>Entertainment Tadka Bot - Madad</b>\n\n" .
                  "📋 <b>Commands:</b>\n" .
                  "/start - Welcome message\n" .
                  "/help - Yeh help message\n" .
                  "/request MovieName - Naya movie request karo\n" .
                  "/myrequests - Apni requests dekho\n" .
                  "/checkdate - Statistics dekho\n" .
                  "/totalupload - Saari movies browse karo\n" .
                  "/testcsv - Database ki movies dekho\n" .
                  "/checkcsv - CSV data check karo\n" .
                  "/csvstats - CSV statistics\n" .
                  "/language - Bhasha badlo\n" .
                  "/admin - Admin panel (Admins only)\n\n" .
                  "🔍 <b>Search kaise karein:</b>\n" .
                  "• Bus movie ka naam likho\n" .
                  "• Thoda sa naam bhi kaafi hai\n" .
                  "• Example: 'kgf', 'pushpa', 'hindi movie'\n\n" .
                  "🎬 <b>Movie Requests:</b>\n" .
                  "• /request MovieName use karo\n" .
                  "• Ya likho: 'pls add MovieName'\n" .
                  "• Roz 3 requests max\n" .
                  "• Status check: /myrequests",
        
        'search_found' => "🔍 <b>{count} movies mil gaye '{query}' ke liye ({total_items} items):</b>\n\n{results}",
        'search_select' => "🚀 <b>Movie select karo saari copies pane ke liye:</b>",
        'search_send_all' => "📤 <b>Ya saari movies ek saath bhejein?</b>",
        'search_not_found' => "😔 <b>Yeh movie abhi available nahi hai!</b>\n\n📢 Join: @EntertainmentTadka786",
        'search_not_found_hindi' => "😔 <b>Yeh movie abhi available nahi hai!</b>\n\n📢 Join: @EntertainmentTadka786",
        'invalid_search' => "🎬 <b>Please enter a valid movie name!</b>\n\nExamples:\n• kgf\n• pushpa\n• avengers\n\n📢 Join: @EntertainmentTadka786",
        
        'request_success' => "✅ <b>Request successfully submit ho gayi!</b>\n\n🎬 Movie: {movie}\n📝 ID: #{id}\n🕒 Status: Pending\n\nApprove hote hi notification mil jayega.",
        'request_duplicate' => "⚠️ <b>Yeh movie aap already request kar chuke ho!</b>\n\nThoda wait karo dubara request karne se pehle.",
        'request_limit' => "❌ <b>Aapne daily limit reach kar li hai!</b>\n\nRoz sirf {limit} requests kar sakte ho. Kal try karo.",
        'request_guide' => "📝 <b>Movie Request Guide</b>\n\n" .
                           "🎬 <b>2 tarike hain movie request karne ke:</b>\n\n" .
                           "1️⃣ <b>Command se:</b>\n" .
                           "<code>/request Movie Name</code>\n" .
                           "Example: /request KGF Chapter 3\n\n" .
                           "2️⃣ <b>Natural Language se:</b>\n" .
                           "• pls add Movie Name\n" .
                           "• please add Movie Name\n" .
                           "• can you add Movie Name\n" .
                           "• request movie Movie Name\n\n" .
                           "📌 <b>Limit:</b> {limit} requests per day\n" .
                           "⏳ <b>Status Check:</b> /myrequests\n\n" .
                           "🔗 <b>Request Channel:</b> @EntertainmentTadka7860",
        
        'myrequests_empty' => "📭 <b>Aapne abhi tak koi request nahi ki hai.</b>\n\n/request MovieName use karo movie request karne ke liye.\n\nYa likho: 'pls add MovieName'",
        
        'myrequests_header' => "📋 <b>Aapki Movie Requests</b>\n\n📊 <b>Stats:</b>\n• Total: {total}\n• Approved: {approved}\n• Pending: {pending}\n• Rejected: {rejected}\n• Aaj: {today}/{limit}\n\n🎬 <b>Recent Requests:</b>\n\n",
        
        'stats' => "📊 <b>Bot Statistics</b>\n\n🎬 Total Movies: {movies}\n👥 Total Users: {users}\n🔍 Total Searches: {searches}\n🕒 Last Updated: {updated}\n\n📡 Movies by Channel:\n{channels}",
        
        'csv_stats' => "📊 <b>CSV Database Statistics</b>\n\n📁 File Size: {size} KB\n📄 Total Movies: {movies}\n🕒 Last Cache Update: {updated}\n\n📡 Movies by Channel:\n{channels}",
        
        'totalupload' => "📊 Total Uploads\n• Page {page}/{total_pages}\n• Showing: {showing} of {total}\n\n➡️ Buttons use karo navigate karne ke liye",
        
        'error' => "❌ <b>Error:</b> {message}",
        'maintenance' => "🛠️ <b>Bot Under Maintenance</b>\n\nWe're temporarily unavailable for updates.\nWill be back in few days!\n\nThanks for patience 🙏",
        
        'language_choose' => "🌐 <b>Choose your language / अपनी भाषा चुनें:</b>",
        'language_set' => "✅ {lang}",
        'language_english' => "Language set to English",
        'language_hindi' => "भाषा हिंदी में सेट हो गई",
        'language_hinglish' => "Hinglish mode active!",
        
        'admin_only' => "🔐 <b>Admin Only</b>\n\nThis command is only for bot administrators.",
        'admin_welcome' => "🔐 <b>Welcome to Admin Panel</b>\n\nUse /admin to open the admin control panel.",
        
        'copies_sent' => "✅ {count} copies of '{movie}' sent successfully!\n\n📢 Join: @EntertainmentTadka786",
        'all_copies_sent' => "📤 <b>All {count} copies of '{movie}' have been sent!</b>\n\n",
        'bulk_add_success' => "✅ {count} movies successfully added to database!\n\nAuto-approved {approved} requests.",
        'reputation_up' => "⭐ <b>Congratulations!</b>\n\nYour reputation has increased to {reputation}!\nYou now get {requests} requests per day.",
        
        'send_all_results' => "📤 Send All {count} Results",
        'send_all_copies' => "📤 Send All {count} Copies",
    ];
    
    $response = isset($responses[$key]) ? $responses[$key] : $key;
    
    foreach ($vars as $var => $value) {
        $response = str_replace('{' . $var . '}', $value, $response);
    }
    
    return $response;
}

function getUserLanguage($user_id) {
    if (file_exists(USERS_FILE)) {
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (isset($users_data['users'][$user_id]['language'])) {
            return $users_data['users'][$user_id]['language'];
        }
    }
    return 'hinglish';
}

function setUserLanguage($user_id, $lang) {
    if (!file_exists(USERS_FILE)) {
        return;
    }
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users_data) {
        $users_data = ['users' => []];
    }
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [];
    }
    
    $users_data['users'][$user_id]['language'] = $lang;
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

function sendHinglish($chat_id, $key, $vars = [], $reply_markup = null) {
    $message = getHinglishResponse($key, $vars);
    return sendMessage($chat_id, $message, $reply_markup, 'HTML');
}

// ==================== REQUEST SYSTEM CLASS ====================
class RequestSystem {
    private static $instance = null;
    private $db_file = 'requests.json';
    private $config;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = [
            'max_requests_per_day' => MAX_REQUESTS_PER_DAY,
            'duplicate_check_hours' => 24,
            'auto_approve_delay' => 300,
            'admin_ids' => ADMIN_IDS,
            'reputation_bonus' => REPUTATION_BONUS_REQUESTS,
            'reputation_threshold' => REPUTATION_THRESHOLD
        ];
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
            @chmod($this->db_file, 0666);
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
    
    public function getUserMaxRequests($user_id) {
        $base_limit = MAX_REQUESTS_PER_DAY;
        
        // Get user reputation
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        $reputation = $users_data['users'][$user_id]['reputation'] ?? 0;
        
        if ($reputation >= REPUTATION_THRESHOLD) {
            return $base_limit + REPUTATION_BONUS_REQUESTS;
        }
        
        return $base_limit;
    }
    
    public function submitRequest($user_id, $movie_name, $user_name = '') {
        $movie_name = validateInput($movie_name, 'movie_name');
        $user_id = validateInput($user_id, 'user_id');
        
        if (!$movie_name || !$user_id) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        if (empty($movie_name) || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        $duplicate_check = $this->checkDuplicateRequest($user_id, $movie_name);
        if ($duplicate_check['is_duplicate']) {
            return [
                'success' => false, 
                'message' => "You already requested '$movie_name' recently. Please wait before requesting again."
            ];
        }
        
        $max_requests = $this->getUserMaxRequests($user_id);
        $flood_check = $this->checkFloodControl($user_id, $max_requests);
        if (!$flood_check['allowed']) {
            return [
                'success' => false,
                'message' => "You've reached your daily limit of $max_requests requests. Please try again tomorrow."
            ];
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
            'approved_at' => null,
            'rejected_at' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'reason' => '',
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
                'pending' => 0,
                'last_request_time' => null,
                'requests_today' => 0,
                'last_request_date' => date('Y-m-d')
            ];
        }
        
        $data['user_stats'][$user_id]['total_requests']++;
        $data['user_stats'][$user_id]['pending']++;
        $data['user_stats'][$user_id]['last_request_time'] = time();
        
        if ($data['user_stats'][$user_id]['last_request_date'] != date('Y-m-d')) {
            $data['user_stats'][$user_id]['requests_today'] = 0;
            $data['user_stats'][$user_id]['last_request_date'] = date('Y-m-d');
        }
        
        $data['user_stats'][$user_id]['requests_today']++;
        
        $this->saveData($data);
        
        log_error("Request submitted", 'INFO', [
            'request_id' => $request_id,
            'user_id' => $user_id,
            'movie_name' => $movie_name
        ]);
        
        return [
            'success' => true,
            'request_id' => $request_id,
            'message' => "✅ Request submitted successfully!\n\n🎬 Movie: $movie_name\n📝 ID: #$request_id\n🕒 Status: Pending\n\nYou will be notified when it's approved."
        ];
    }
    
    private function checkDuplicateRequest($user_id, $movie_name) {
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $time_limit = time() - (24 * 3600);
        
        foreach ($data['requests'] as $request) {
            if ($request['user_id'] == $user_id && 
                strtolower($request['movie_name']) == $movie_lower &&
                strtotime($request['created_at']) > $time_limit) {
                return [
                    'is_duplicate' => true,
                    'request' => $request
                ];
            }
        }
        
        return ['is_duplicate' => false];
    }
    
    private function checkFloodControl($user_id, $max_requests) {
        $data = $this->loadData();
        
        if (!isset($data['user_stats'][$user_id])) {
            return ['allowed' => true, 'remaining' => $max_requests];
        }
        
        $user_stats = $data['user_stats'][$user_id];
        
        if ($user_stats['last_request_date'] != date('Y-m-d')) {
            return ['allowed' => true, 'remaining' => $max_requests];
        }
        
        $remaining = $max_requests - $user_stats['requests_today'];
        
        return [
            'allowed' => $user_stats['requests_today'] < $max_requests,
            'remaining' => max(0, $remaining)
        ];
    }
    
    public function approveRequest($request_id, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $data = $this->loadData();
        
        if (!isset($data['requests'][$request_id])) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        $request = $data['requests'][$request_id];
        
        if ($request['status'] != 'pending') {
            return ['success' => false, 'message' => "Request is already {$request['status']}"];
        }
        
        $data['requests'][$request_id]['status'] = 'approved';
        $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['approved_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        
        $data['system_stats']['approved']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['approved']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        log_error("Request approved", 'INFO', [
            'request_id' => $request_id,
            'admin_id' => $admin_id,
            'movie_name' => $request['movie_name']
        ]);
        
        return [
            'success' => true,
            'request' => $data['requests'][$request_id],
            'message' => "✅ Request #$request_id approved!"
        ];
    }
    
    public function rejectRequest($request_id, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $data = $this->loadData();
        
        if (!isset($data['requests'][$request_id])) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        $request = $data['requests'][$request_id];
        
        if ($request['status'] != 'pending') {
            return ['success' => false, 'message' => "Request is already {$request['status']}"];
        }
        
        $reason = validateInput($reason);
        
        $data['requests'][$request_id]['status'] = 'rejected';
        $data['requests'][$request_id]['rejected_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['rejected_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['reason'] = $reason;
        
        $data['system_stats']['rejected']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['rejected']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        log_error("Request rejected", 'INFO', [
            'request_id' => $request_id,
            'admin_id' => $admin_id,
            'movie_name' => $request['movie_name'],
            'reason' => $reason
        ]);
        
        return [
            'success' => true,
            'request' => $data['requests'][$request_id],
            'message' => "❌ Request #$request_id rejected!"
        ];
    }
    
    public function bulkApprove($request_ids, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $results = [];
        $success_count = 0;
        
        foreach ($request_ids as $request_id) {
            $result = $this->approveRequest($request_id, $admin_id);
            if ($result['success']) {
                $success_count++;
            }
            $results[$request_id] = $result;
        }
        
        return [
            'success' => true,
            'approved_count' => $success_count,
            'total_count' => count($request_ids),
            'results' => $results
        ];
    }
    
    public function bulkReject($request_ids, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $reason = validateInput($reason);
        
        $results = [];
        $success_count = 0;
        
        foreach ($request_ids as $request_id) {
            $result = $this->rejectRequest($request_id, $admin_id, $reason);
            if ($result['success']) {
                $success_count++;
            }
            $results[$request_id] = $result;
        }
        
        return [
            'success' => true,
            'rejected_count' => $success_count,
            'total_count' => count($request_ids),
            'results' => $results
        ];
    }
    
    public function smartAutoApprove($movie_name) {
        $movie_name = strtolower(trim($movie_name));
        $keywords = explode(' ', $movie_name);
        $pending = $this->getPendingRequests(100);
        
        $auto_approved = [];
        
        foreach ($pending as $req) {
            $req_words = explode(' ', strtolower($req['movie_name']));
            $match_percentage = $this->calculateMatch($keywords, $req_words);
            
            if ($match_percentage > 70) {
                $result = $this->approveRequest($req['id'], 'system');
                if ($result['success']) {
                    $auto_approved[] = $req['id'];
                }
            }
        }
        
        return $auto_approved;
    }
    
    private function calculateMatch($words1, $words2) {
        $common = array_intersect($words1, $words2);
        $total = max(count($words1), count($words2));
        
        if ($total == 0) return 0;
        
        return (count($common) / $total) * 100;
    }
    
    public function getPendingRequests($limit = 10, $filter_movie = '') {
        $data = $this->loadData();
        $pending = [];
        
        foreach ($data['requests'] as $request) {
            if ($request['status'] == 'pending') {
                if (!empty($filter_movie)) {
                    $movie_lower = strtolower($filter_movie);
                    $request_movie_lower = strtolower($request['movie_name']);
                    if (strpos($request_movie_lower, $movie_lower) === false) {
                        continue;
                    }
                }
                $pending[] = $request;
            }
        }
        
        usort($pending, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return array_slice($pending, 0, $limit);
    }
    
    public function getUserRequests($user_id, $limit = 20) {
        $data = $this->loadData();
        $user_requests = [];
        
        foreach ($data['requests'] as $request) {
            if ($request['user_id'] == $user_id) {
                $user_requests[] = $request;
            }
        }
        
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
    
    public function getUserStats($user_id) {
        $data = $this->loadData();
        return $data['user_stats'][$user_id] ?? [
            'total_requests' => 0,
            'approved' => 0,
            'rejected' => 0,
            'pending' => 0,
            'requests_today' => 0
        ];
    }
    
    public function checkAutoApprove($movie_name) {
        $movie_name = validateInput($movie_name, 'movie_name');
        if (!$movie_name) return [];
        
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $auto_approved = [];
        
        foreach ($data['requests'] as $request_id => $request) {
            if ($request['status'] == 'pending') {
                $request_movie_lower = strtolower($request['movie_name']);
                
                if (strpos($movie_lower, $request_movie_lower) !== false || 
                    strpos($request_movie_lower, $movie_lower) !== false ||
                    similar_text($movie_lower, $request_movie_lower) > 80) {
                    
                    $data['requests'][$request_id]['status'] = 'approved';
                    $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['approved_by'] = 'system';
                    $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['reason'] = 'Auto-approved: Movie added to database';
                    
                    $data['system_stats']['approved']++;
                    $data['system_stats']['pending']--;
                    
                    $user_id = $request['user_id'];
                    $data['user_stats'][$user_id]['approved']++;
                    $data['user_stats'][$user_id]['pending']--;
                    
                    $auto_approved[] = $request_id;
                }
            }
        }
        
        if (!empty($auto_approved)) {
            $this->saveData($data);
            log_error("Auto-approved requests", 'INFO', [
                'movie_name' => $movie_name,
                'request_ids' => $auto_approved
            ]);
        }
        
        return $auto_approved;
    }
    
    public function markAsNotified($request_id) {
        $data = $this->loadData();
        if (isset($data['requests'][$request_id])) {
            $data['requests'][$request_id]['is_notified'] = true;
            $this->saveData($data);
        }
    }
    
    public function getUnnotifiedRequests() {
        $data = $this->loadData();
        $unnotified = [];
        
        foreach ($data['requests'] as $request) {
            if ($request['status'] != 'pending' && !$request['is_notified']) {
                $unnotified[] = $request;
            }
        }
        
        return $unnotified;
    }
}

// ==================== CSV MANAGER CLASS WITH INDEXING ====================
class CSVManager {
    private static $buffer = [];
    private static $instance = null;
    private $cache_data = null;
    private $cache_timestamp = 0;
    private $movie_index = [];
    private $index_file;
    private $in_transaction = false;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->index_file = INDEX_FILE;
        $this->initializeFiles();
        $this->loadIndex();
        register_shutdown_function([$this, 'flushBuffer']);
    }
    
    private function initializeFiles() {
        if (!file_exists(BACKUP_DIR)) {
            @mkdir(BACKUP_DIR, 0777, true);
            @chmod(BACKUP_DIR, 0777);
        }
        if (!file_exists(CACHE_DIR)) {
            @mkdir(CACHE_DIR, 0777, true);
            @chmod(CACHE_DIR, 0777);
        }
        
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id\n";
            @file_put_contents(CSV_FILE, $header);
            @chmod(CSV_FILE, 0666);
            log_error("CSV file created", 'INFO');
        }
        
        if (!file_exists(USERS_FILE)) {
            $users_data = [
                'users' => [],
                'total_requests' => 0,
                'message_logs' => []
            ];
            @file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            @chmod(USERS_FILE, 0666);
        }
        
        if (!file_exists(STATS_FILE)) {
            $stats_data = [
                'total_movies' => 0,
                'total_users' => 0,
                'total_searches' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            @file_put_contents(STATS_FILE, json_encode($stats_data, JSON_PRETTY_PRINT));
            @chmod(STATS_FILE, 0666);
        }
    }
    
    private function loadIndex() {
        if (file_exists($this->index_file) && (time() - filemtime($this->index_file)) < CACHE_EXPIRY) {
            $this->movie_index = json_decode(file_get_contents($this->index_file), true);
            if ($this->movie_index === null) {
                $this->movie_index = [];
            }
        } else {
            $this->buildIndex();
        }
    }
    
    private function buildIndex() {
        $data = $this->getCachedData();
        $this->movie_index = [];
        
        foreach ($data as $item) {
            $movie_name = strtolower(trim($item['movie_name']));
            $first_char = substr($movie_name, 0, 1);
            
            if (!isset($this->movie_index[$first_char])) {
                $this->movie_index[$first_char] = [];
            }
            
            $this->movie_index[$first_char][] = $item;
        }
        
        file_put_contents($this->index_file, json_encode($this->movie_index, JSON_PRETTY_PRINT));
        log_error("Movie index rebuilt with " . count($data) . " items", 'INFO');
    }
    
    private function acquireLock($file, $mode = LOCK_EX) {
        $fp = fopen($file, 'r+');
        if ($fp && flock($fp, $mode)) {
            return $fp;
        }
        if ($fp) fclose($fp);
        return false;
    }
    
    private function releaseLock($fp) {
        if ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
    
    public function beginTransaction() {
        $this->in_transaction = true;
        log_error("Transaction started", 'INFO');
    }
    
    public function commit() {
        $this->flushBuffer();
        $this->in_transaction = false;
        $this->buildIndex();
        log_error("Transaction committed", 'INFO');
    }
    
    public function rollback() {
        self::$buffer = [];
        $this->in_transaction = false;
        log_error("Transaction rolled back", 'INFO');
    }
    
    public function bufferedAppend($movie_name, $message_id, $channel_id) {
        $movie_name = validateInput($movie_name, 'movie_name');
        $channel_id = validateInput($channel_id, 'telegram_id');
        
        if (!$movie_name || !$channel_id) {
            log_error("Invalid input for bufferedAppend", 'WARNING');
            return false;
        }
        
        if (empty(trim($movie_name))) {
            log_error("Empty movie name provided", 'WARNING');
            return false;
        }
        
        self::$buffer[] = [
            'movie_name' => trim($movie_name),
            'message_id' => intval($message_id),
            'channel_id' => $channel_id,
            'timestamp' => time()
        ];
        
        log_error("Added to buffer: " . trim($movie_name), 'INFO', [
            'message_id' => $message_id,
            'channel_id' => $channel_id
        ]);
        
        if (!$this->in_transaction && count(self::$buffer) >= CSV_BUFFER_SIZE) {
            $this->flushBuffer();
        }
        
        $this->clearCache();
        
        return true;
    }
    
    public function flushBuffer() {
        if (empty(self::$buffer)) {
            return true;
        }
        
        log_error("Flushing buffer with " . count(self::$buffer) . " items", 'INFO');
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_EX);
        if (!$fp) {
            log_error("Failed to lock CSV file for writing", 'ERROR');
            return false;
        }
        
        try {
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
            
            log_error("Buffer flushed successfully", 'INFO');
            self::$buffer = [];
            
            // Update index after flush
            $this->buildIndex();
            
            return true;
        } catch (Exception $e) {
            log_error("Error flushing buffer: " . $e->getMessage(), 'ERROR');
            return false;
        } finally {
            $this->releaseLock($fp);
        }
    }
    
    public function bulkAddMovies($movies_array) {
        $this->beginTransaction();
        
        $added_count = 0;
        foreach ($movies_array as $movie) {
            if (isset($movie['name']) && isset($movie['message_id']) && isset($movie['channel_id'])) {
                if ($this->bufferedAppend($movie['name'], $movie['message_id'], $movie['channel_id'])) {
                    $added_count++;
                }
            }
        }
        
        $this->commit();
        
        log_error("Bulk added $added_count movies", 'INFO');
        return $added_count;
    }
    
    public function readCSV() {
        $data = [];
        
        if (!file_exists(CSV_FILE)) {
            log_error("CSV file not found", 'ERROR');
            return $data;
        }
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_SH);
        if (!$fp) {
            log_error("Failed to lock CSV file for reading", 'ERROR');
            return $data;
        }
        
        try {
            $header = fgetcsv($fp);
            if ($header === false || $header[0] !== 'movie_name') {
                log_error("Invalid CSV header, rebuilding", 'WARNING');
                $this->rebuildCSV();
                return $this->readCSV();
            }
            
            $row_count = 0;
            while (($row = fgetcsv($fp)) !== FALSE) {
                if (count($row) >= 3 && !empty(trim($row[0]))) {
                    $data[] = [
                        'movie_name' => validateInput(trim($row[0]), 'movie_name'),
                        'message_id' => isset($row[1]) ? intval(trim($row[1])) : 0,
                        'channel_id' => isset($row[2]) ? validateInput(trim($row[2]), 'telegram_id') : ''
                    ];
                    $row_count++;
                }
            }
            log_error("Read $row_count rows from CSV", 'INFO');
            return $data;
        } catch (Exception $e) {
            log_error("Error reading CSV: " . $e->getMessage(), 'ERROR');
            return [];
        } finally {
            $this->releaseLock($fp);
        }
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
                        'movie_name' => validateInput(trim($parts[0]), 'movie_name'),
                        'message_id' => intval(trim($parts[1])),
                        'channel_id' => validateInput(trim($parts[2]), 'telegram_id')
                    ];
                }
            }
        }
        
        $fp = fopen(CSV_FILE, 'w');
        if ($fp) {
            fputcsv($fp, ['movie_name', 'message_id', 'channel_id']);
            foreach ($data as $row) {
                fputcsv($fp, [$row['movie_name'], $row['message_id'], $row['channel_id']]);
            }
            fclose($fp);
            @chmod(CSV_FILE, 0666);
        }
        
        $this->buildIndex();
        log_error("CSV rebuilt with " . count($data) . " rows", 'INFO');
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
                log_error("Loaded from file cache", 'INFO');
                return $this->cache_data;
            }
        }
        
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        
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
        
        if (file_exists($this->index_file)) {
            @unlink($this->index_file);
        }
    }
    
    public function searchMovies($query) {
        $query = validateInput($query, 'movie_name');
        if (!$query) {
            return [];
        }
        
        $query_lower = strtolower(trim($query));
        $first_char = substr($query_lower, 0, 1);
        
        $results = [];
        
        log_error("Searching for: $query", 'INFO');
        
        // Use index if available
        if (!empty($this->movie_index) && isset($this->movie_index[$first_char])) {
            $candidates = $this->movie_index[$first_char];
            log_error("Using index for first char '$first_char' with " . count($candidates) . " candidates", 'INFO');
        } else {
            // Fallback to full scan
            $candidates = $this->getCachedData();
            log_error("Index not available, scanning all " . count($candidates) . " items", 'INFO');
        }
        
        foreach ($candidates as $item) {
            if (empty($item['movie_name'])) continue;
            
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
    global $monitor;
    
    if (!RateLimiter::check('telegram_api', RATE_LIMIT_REQUESTS, RATE_LIMIT_WINDOW)) {
        log_error("Telegram API rate limit exceeded", 'WARNING');
        usleep(100000);
    }
    
    if ($monitor) {
        $monitor->trackApiCall();
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    log_error("API Request: $method", 'DEBUG', $params);
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $res = curl_exec($ch);
        if ($res === false) {
            log_error("CURL ERROR: " . curl_error($ch), 'ERROR');
            if ($monitor) $monitor->trackError();
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
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            log_error("apiRequest failed for method $method: " . ($error['message'] ?? 'Unknown error'), 'ERROR');
            if ($monitor) $monitor->trackError();
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

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = [
        'callback_query_id' => $callback_query_id,
        'show_alert' => $show_alert
    ];
    if ($text) $data['text'] = validateInput($text, 'text');
    return apiRequest('answerCallbackQuery', $data);
}

// ==================== CHANNEL MANAGEMENT ====================
function getChannelType($channel_id) {
    global $ENV_CONFIG;
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return 'public';
        }
    }
    
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return 'private';
        }
    }
    
    return 'unknown';
}

function getChannelUsername($channel_id) {
    global $ENV_CONFIG;
    
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return $channel['username'];
        }
    }
    
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) {
        if ($channel['id'] == $channel_id) {
            return $channel['username'] ?: 'Private Channel';
        }
    }
    
    return 'Unknown Channel';
}

// ==================== REQUEST NOTIFICATION FUNCTIONS ====================
function notifyUserAboutRequest($user_id, $request, $action) {
    global $requestSystem;
    
    $movie_name = htmlspecialchars($request['movie_name'], ENT_QUOTES, 'UTF-8');
    
    if ($action == 'approved') {
        $message = "🎉 <b>Good News!</b>\n\n";
        $message .= "✅ Your movie request has been <b>APPROVED</b>!\n\n";
        $message .= "🎬 <b>Movie:</b> $movie_name\n";
        $message .= "📝 <b>Request ID:</b> #" . $request['id'] . "\n";
        $message .= "🕒 <b>Approved at:</b> " . date('d M Y, H:i', strtotime($request['approved_at'])) . "\n\n";
        
        if (!empty($request['reason'])) {
            $message .= "📋 <b>Note:</b> " . htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8') . "\n\n";
        }
        
        $message .= "🔍 You can now search for this movie in the bot!\n";
        $message .= "📢 Join: @EntertainmentTadka786";
    } else {
        $message = "📭 <b>Update on Your Request</b>\n\n";
        $message .= "❌ Your movie request has been <b>REJECTED</b>.\n\n";
        $message .= "🎬 <b>Movie:</b> $movie_name\n";
        $message .= "📝 <b>Request ID:</b> #" . $request['id'] . "\n";
        $message .= "🕒 <b>Rejected at:</b> " . date('d M Y, H:i', strtotime($request['rejected_at'])) . "\n";
        
        if (!empty($request['reason'])) {
            $message .= "📋 <b>Reason:</b> " . htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8') . "\n";
        }
        
        $message .= "\n💡 <b>Tip:</b> Make sure the movie name is correct and check if it's already available.";
    }
    
    sendMessage($user_id, $message, null, 'HTML');
    $requestSystem->markAsNotified($request['id']);
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
    
    sendChatAction($chat_id, 'typing');
    
    if ($channel_type === 'public') {
        $result = forwardMessage($chat_id, $channel_id, $message_id);
        if ($result !== false) {
            log_error("Forwarded message successfully", 'INFO');
            return true;
        } else {
            log_error("Failed to forward message", 'ERROR');
            return false;
        }
    } elseif ($channel_type === 'private') {
        $result = copyMessage($chat_id, $channel_id, $message_id);
        if ($result !== false) {
            log_error("Copied message successfully", 'INFO');
            return true;
        } else {
            log_error("Failed to copy message", 'ERROR');
            return false;
        }
    }
    
    $text = "🎬 " . htmlspecialchars($item['movie_name'], ENT_QUOTES, 'UTF-8') . "\n";
    $text .= "📁 Channel: " . getChannelUsername($channel_id) . "\n";
    $text .= "🔗 Message ID: " . $message_id;
    sendMessage($chat_id, $text, null, 'HTML');
    log_error("Used fallback text delivery", 'WARNING');
    return false;
}

function deliver_all_copies($chat_id, $movie_name, $items) {
    $sent_count = 0;
    
    sendChatAction($chat_id, 'typing');
    
    foreach ($items as $item) {
        if (deliver_item_to_chat($chat_id, $item)) {
            $sent_count++;
            usleep(300000); // 300ms delay between sends
        }
    }
    
    if ($sent_count > 0) {
        $channel_type = getChannelType($items[0]['channel_id']);
        $source_note = $channel_type === 'public' ? 
            " (Forwarded from " . getChannelUsername($items[0]['channel_id']) . ")" : "";
        
        sendHinglish($chat_id, 'all_copies_sent', [
            'count' => $sent_count,
            'movie' => $movie_name
        ]);
        
        sendMessage($chat_id, "✅ $sent_count copies sent$source_note\n\n📢 Join: @EntertainmentTadka786", null, 'HTML');
    }
    
    return $sent_count;
}

// ==================== ADVANCED SEARCH FUNCTION ====================
function advanced_search($chat_id, $query, $user_id = null) {
    global $csvManager, $monitor;
    
    sendChatAction($chat_id, 'typing');
    
    $q = validateInput($query, 'movie_name');
    if (!$q) {
        sendHinglish($chat_id, 'error', ['message' => 'Invalid movie name format']);
        return;
    }
    
    $q = strtolower(trim($q));
    
    log_error("Advanced search initiated by $user_id", 'INFO', ['query' => $query]);
    
    if (strlen($q) < 2) {
        sendHinglish($chat_id, 'error', ['message' => 'Please enter at least 2 characters for search']);
        return;
    }
    
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
        sendHinglish($chat_id, 'invalid_search');
        log_error("Invalid search query detected", 'WARNING', ['query' => $query]);
        return;
    }
    
    $found = $csvManager->searchMovies($query);
    
    if ($monitor) {
        $monitor->trackSearch($query, count($found));
        if ($user_id) {
            $monitor->trackUserActivity($user_id);
        }
    }
    
    if (!empty($found)) {
        $total_items = 0;
        foreach ($found as $movie_data) {
            $total_items += $movie_data['count'];
        }
        
        $results_text = "";
        $i = 1;
        foreach ($found as $movie_name => $movie_data) {
            $results_text .= "$i. " . ucwords($movie_name) . " (" . $movie_data['count'] . " entries)\n";
            $i++;
            if ($i > 10) break;
        }
        
        sendHinglish($chat_id, 'search_found', [
            'count' => count($found),
            'query' => $query,
            'total_items' => $total_items,
            'results' => $results_text
        ]);
        
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
        
        // Add "Send All Results" button
        if (count($found) > 1) {
            $found_names = array_keys($found);
            $encoded_names = base64_encode(json_encode($found_names));
            $keyboard['inline_keyboard'][] = [[
                'text' => "📤 Send All " . count($found) . " Results",
                'callback_data' => 'send_all_results_' . $encoded_names
            ]];
        }
        
        sendHinglish($chat_id, 'search_select', [], $keyboard);
        
        if ($user_id) {
            update_user_points($user_id, 'found_movie');
            update_user_reputation($user_id, 'search');
        }
        
        log_error("Search successful, found " . count($found) . " movies", 'INFO');
    } else {
        $lang = getUserLanguage($user_id);
        if ($lang == 'hindi') {
            sendHinglish($chat_id, 'search_not_found_hindi');
        } else {
            sendHinglish($chat_id, 'search_not_found');
        }
        log_error("No results found for query", 'INFO', ['query' => $query]);
    }
    
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
    
    $points_map = ['search' => 1, 'found_movie' => 5, 'daily_login' => 10];
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'points' => 0,
            'reputation' => 0,
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

function update_user_reputation($user_id, $action) {
    if (!file_exists(USERS_FILE)) {
        return;
    }
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users_data) {
        return;
    }
    
    if (!isset($users_data['users'][$user_id])) {
        return;
    }
    
    $old_reputation = $users_data['users'][$user_id]['reputation'] ?? 0;
    
    // Calculate reputation based on activity
    $searches = $users_data['users'][$user_id]['searches'] ?? 0;
    $requests_approved = $users_data['users'][$user_id]['requests_approved'] ?? 0;
    $points = $users_data['users'][$user_id]['points'] ?? 0;
    
    $new_reputation = ($searches * 0.1) + ($requests_approved * 10) + ($points * 0.5);
    $new_reputation = min(1000, intval($new_reputation));
    
    $users_data['users'][$user_id]['reputation'] = $new_reputation;
    
    // Notify user if reputation crossed threshold
    if ($old_reputation < REPUTATION_THRESHOLD && $new_reputation >= REPUTATION_THRESHOLD) {
        global $requestSystem;
        $new_limit = $requestSystem->getUserMaxRequests($user_id);
        sendMessage($user_id, getHinglishResponse('reputation_up', [
            'reputation' => $new_reputation,
            'requests' => $new_limit
        ]), null, 'HTML');
    }
    
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

// ==================== ADMIN COMMANDS ====================
function admin_stats($chat_id) {
    global $csvManager, $ENV_CONFIG, $requestSystem, $adminPanel, $monitor;
    
    sendChatAction($chat_id, 'typing');
    
    $stats_view = $adminPanel->getStats($csvManager, $requestSystem, $monitor);
    sendMessage($chat_id, $stats_view['text'], $stats_view['keyboard'], 'HTML');
    
    log_error("Admin stats sent to $chat_id", 'INFO');
}

function csv_stats_command($chat_id) {
    global $csvManager;
    
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $csv_size = file_exists(CSV_FILE) ? filesize(CSV_FILE) : 0;
    
    $channels_text = "";
    foreach ($stats['channels'] as $channel_id => $count) {
        $channel_type = getChannelType($channel_id);
        $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
        $channels_text .= $type_icon . " " . getChannelUsername($channel_id) . ": " . $count . "\n";
    }
    
    sendHinglish($chat_id, 'csv_stats', [
        'size' => round($csv_size / 1024, 2),
        'movies' => $stats['total_movies'],
        'updated' => $stats['last_updated'],
        'channels' => $channels_text
    ]);
    
    log_error("CSV stats sent to $chat_id", 'INFO');
}

// ==================== PAGINATION & VIEW ====================
function totalupload_controller($chat_id, $page = 1) {
    global $csvManager;
    
    sendChatAction($chat_id, 'upload_document');
    
    $all = $csvManager->getCachedData();
    if (empty($all)) {
        sendMessage($chat_id, "⚠️ No movies found in database.", null, 'HTML');
        log_error("No movies found for pagination", 'WARNING');
        return;
    }
    
    $total = count($all);
    $total_pages = ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    $page_movies = array_slice($all, $start, ITEMS_PER_PAGE);
    
    log_error("Pagination: page $page/$total_pages, showing " . count($page_movies) . " items", 'INFO');
    
    $i = 1;
    foreach ($page_movies as $movie) {
        sendChatAction($chat_id, 'upload_document');
        deliver_item_to_chat($chat_id, $movie);
        usleep(500000);
        $i++;
    }
    
    sendHinglish($chat_id, 'totalupload', [
        'page' => $page,
        'total_pages' => $total_pages,
        'showing' => count($page_movies),
        'total' => $total
    ]);
    
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
    
    sendMessage($chat_id, "➡️ Buttons use karo navigate karne ke liye", $keyboard, 'HTML');
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
        sendMessage($chat_id, "📊 CSV file is empty.", null, 'HTML');
        return;
    }
    
    $message = "📊 CSV Movie Database\n\n";
    $message .= "📁 Total Movies: " . count($data) . "\n";
    $message .= "🔍 Showing latest 10 entries\n\n";
    
    $recent = array_slice($data, -10);
    $i = 1;
    foreach ($recent as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . $movie_name . "\n";
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
        sendMessage($chat_id, "📊 CSV file is empty.", null, 'HTML');
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
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . $movie_name . "\n";
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

// ==================== MAINTENANCE CHECK ====================
if (MAINTENANCE_MODE) {
    $update = json_decode(file_get_contents('php://input'), true);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $maintenance_msg = getHinglishResponse('maintenance');
        sendMessage($chat_id, $maintenance_msg, null, 'HTML');
    }
    exit;
}

// ==================== MAIN PROCESSING ====================
// Initialize Managers
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();
$adminPanel = AdminPanel::getInstance();
$monitor = BotMonitor::getInstance();

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

if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<h2>Webhook Deleted</h2>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

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
    
    $request_stats = $requestSystem->getStats();
    echo "<p><strong>Total Requests:</strong> " . $request_stats['total_requests'] . "</p>";
    echo "<p><strong>Pending Requests:</strong> " . $request_stats['pending'] . "</p>";
    
    $metrics = $monitor->getMetrics();
    echo "<p><strong>Avg Response:</strong> " . round(($metrics['avg_response_time'] ?? 0) * 1000, 2) . "ms</p>";
    echo "<p><strong>API Calls:</strong> " . ($metrics['api_calls'] ?? 0) . "</p>";
    
    echo "<h3>🚀 Quick Setup</h3>";
    echo "<p><a href='?setup=1'>Set Webhook Now</a></p>";
    echo "<p><a href='?deletehook=1'>Delete Webhook</a></p>";
    echo "<p><a href='?test_csv=1'>Test CSV Manager</a></p>";
    
    exit;
}

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

if (isset($_GET['test_html'])) {
    $chat_id = 1080317415;
    sendMessage($chat_id, "<b>Bold Text</b> <i>Italic</i> <code>Code</code>", null, 'HTML');
    echo "Test message sent! Check Telegram.";
    exit;
}

// ==================== TELEGRAM UPDATE PROCESSING ====================
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!RateLimiter::check($ip, 'telegram_update', 30, 60)) {
        http_response_code(429);
        exit;
    }
    
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        log_error("Channel post received", 'INFO', [
            'channel_id' => $chat_id,
            'message_id' => $message_id
        ]);
        
        // Fix: Properly merge channels
        $all_channels = array_merge(
            $ENV_CONFIG['PUBLIC_CHANNELS'] ?? [],
            $ENV_CONFIG['PRIVATE_CHANNELS'] ?? []
        );
        $all_channel_ids = array_column($all_channels, 'id');
        
        if (in_array($chat_id, $all_channel_ids)) {
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
                
                $auto_approved = $requestSystem->smartAutoApprove($text);
                if (!empty($auto_approved)) {
                    foreach ($auto_approved as $req_id) {
                        $request = $requestSystem->getRequest($req_id);
                        if ($request) {
                            notifyUserAboutRequest($request['user_id'], $request, 'approved');
                        }
                    }
                }
                
                log_error("Added channel post to CSV", 'INFO', [
                    'movie_name' => $text,
                    'channel_id' => $chat_id
                ]);
            }
        }
    }
    
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $message_id = $message['message_id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
        log_error("Message received", 'INFO', [
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'text' => substr($text, 0, 100)
        ]);
        
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!$users_data) $users_data = ['users' => []];
        
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '',
                'last_name' => $message['from']['last_name'] ?? '',
                'username' => $message['from']['username'] ?? '',
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s'),
                'points' => 0,
                'reputation' => 0,
                'language' => 'hinglish'
            ];
            $users_data['total_requests'] = ($users_data['total_requests'] ?? 0) + 1;
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            update_stats('total_users', 1);
            
            log_error("New user registered", 'INFO', [
                'user_id' => $user_id,
                'username' => $message['from']['username'] ?? 'N/A'
            ]);
        }
        
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
            log_error("Command received", 'INFO', ['command' => $command]);
            
            if ($command == '/start') {
                $lang = detectUserLanguage($text);
                setUserLanguage($user_id, $lang);
                
                if ($lang == 'hindi') {
                    $welcome = getHinglishResponse('welcome_hindi');
                } else {
                    $welcome = getHinglishResponse('welcome');
                }
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                            ['text' => '🎬 Serial Channel', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786']
                        ],
                        [
                            ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies'],
                            ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']
                        ],
                        [
                            ['text' => '📥 Request Guide', 'callback_data' => 'request_movie'],
                            ['text' => '❓ Help', 'callback_data' => 'help_command']
                        ],
                        [
                            ['text' => '📊 Stats', 'callback_data' => 'show_stats'],
                            ['text' => '🎬 Browse All', 'callback_data' => 'browse_all']
                        ]
                    ]
                ];
                
                if ($adminPanel->isAdmin($user_id)) {
                    $keyboard['inline_keyboard'][] = [
                        ['text' => '🔐 Admin Panel', 'callback_data' => 'admin_main']
                    ];
                }
                
                sendMessage($chat_id, $welcome, $keyboard, 'HTML');
                update_user_points($user_id, 'daily_login');
                update_user_reputation($user_id, 'login');
            }
            elseif ($command == '/help') {
                sendChatAction($chat_id, 'typing');
                $help = getHinglishResponse('help');
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start']
                        ]
                    ]
                ];
                
                sendMessage($chat_id, $help, $keyboard, 'HTML');
            }
            elseif ($command == '/language' || $command == '/lang') {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🇬🇧 English', 'callback_data' => 'lang_english'],
                            ['text' => '🇮🇳 हिंदी', 'callback_data' => 'lang_hindi'],
                            ['text' => '🎭 Hinglish', 'callback_data' => 'lang_hinglish']
                        ]
                    ]
                ];
                
                sendHinglish($chat_id, 'language_choose', [], $keyboard);
            }
            elseif ($command == '/admin') {
                if (!$adminPanel->isAdmin($user_id)) {
                    sendHinglish($chat_id, 'admin_only');
                    return;
                }
                
                $admin_menu = $adminPanel->getAdminMainMenu();
                sendMessage($chat_id, $admin_menu['text'], $admin_menu['keyboard'], 'HTML');
            }
            elseif ($command == '/request') {
                if (!REQUEST_SYSTEM_ENABLED) {
                    sendHinglish($chat_id, 'error', ['message' => 'Request system currently disabled']);
                    return;
                }
                
                if (!isset($parts[1])) {
                    sendHinglish($chat_id, 'request_guide', ['limit' => $requestSystem->getUserMaxRequests($user_id)]);
                    return;
                }
                
                $movie_name = implode(' ', array_slice($parts, 1));
                $user_name = $message['from']['first_name'] . ($message['from']['last_name'] ? ' ' . $message['from']['last_name'] : '');
                
                $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
                
                if ($result['success']) {
                    sendHinglish($chat_id, 'request_success', [
                        'movie' => $movie_name,
                        'id' => $result['request_id']
                    ]);
                } else {
                    if (strpos($result['message'], 'already requested') !== false) {
                        sendHinglish($chat_id, 'request_duplicate');
                    } elseif (strpos($result['message'], 'daily limit') !== false) {
                        sendHinglish($chat_id, 'request_limit', ['limit' => $requestSystem->getUserMaxRequests($user_id)]);
                    } else {
                        sendMessage($chat_id, $result['message'], null, 'HTML');
                    }
                }
            }
            elseif ($command == '/myrequests') {
                if (!REQUEST_SYSTEM_ENABLED) {
                    sendHinglish($chat_id, 'error', ['message' => 'Request system currently disabled']);
                    return;
                }
                
                $requests = $requestSystem->getUserRequests($user_id, 10);
                $user_stats = $requestSystem->getUserStats($user_id);
                $max_requests = $requestSystem->getUserMaxRequests($user_id);
                
                if (empty($requests)) {
                    sendHinglish($chat_id, 'myrequests_empty');
                    return;
                }
                
                $message_text = getHinglishResponse('myrequests_header', [
                    'total' => $user_stats['total_requests'],
                    'approved' => $user_stats['approved'],
                    'pending' => $user_stats['pending'],
                    'rejected' => $user_stats['rejected'],
                    'today' => $user_stats['requests_today'],
                    'limit' => $max_requests
                ]);
                
                $i = 1;
                foreach ($requests as $req) {
                    $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                    $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                    $message_text .= "$i. $status_icon <b>" . $movie_name . "</b>\n";
                    $message_text .= "   ID: #" . $req['id'] . " | " . ucfirst($req['status']) . "\n";
                    $message_text .= "   Date: " . date('d M, H:i', strtotime($req['created_at'])) . "\n\n";
                    $i++;
                }
                
                sendMessage($chat_id, $message_text, null, 'HTML');
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
            elseif ($command == '/stats' && in_array($user_id, ADMIN_IDS)) {
                admin_stats($chat_id);
            }
        } 
        elseif (!empty(trim($text)) && (
            stripos($text, 'add movie') !== false || 
            stripos($text, 'please add') !== false || 
            stripos($text, 'pls add') !== false ||
            stripos($text, 'can you add') !== false ||
            stripos($text, 'request movie') !== false
        )) {
            
            if (!REQUEST_SYSTEM_ENABLED) {
                sendHinglish($chat_id, 'error', ['message' => 'Request system currently disabled']);
                return;
            }
            
            $patterns = [
                '/add movie (.+)/i',
                '/please add (.+)/i',
                '/pls add (.+)/i',
                '/add (.+) movie/i',
                '/can you add (.+)/i',
                '/request movie (.+)/i',
                '/request (.+) movie/i'
            ];
            
            $movie_name = '';
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $movie_name = trim($matches[1]);
                    break;
                }
            }
            
            if (empty($movie_name)) {
                $clean_text = preg_replace('/add movie|please add|pls add|movie|add|request|can you/i', '', $text);
                $movie_name = trim($clean_text);
            }
            
            if (strlen($movie_name) < 2) {
                sendHinglish($chat_id, 'request_guide', ['limit' => $requestSystem->getUserMaxRequests($user_id)]);
                return;
            }
            
            $user_name = $message['from']['first_name'] . ($message['from']['last_name'] ? ' ' . $message['from']['last_name'] : '');
            $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
            
            if ($result['success']) {
                sendHinglish($chat_id, 'request_success', [
                    'movie' => $movie_name,
                    'id' => $result['request_id']
                ]);
            } else {
                if (strpos($result['message'], 'already requested') !== false) {
                    sendHinglish($chat_id, 'request_duplicate');
                } elseif (strpos($result['message'], 'daily limit') !== false) {
                    sendHinglish($chat_id, 'request_limit', ['limit' => $requestSystem->getUserMaxRequests($user_id)]);
                } else {
                    sendMessage($chat_id, $result['message'], null, 'HTML');
                }
            }
        }
        elseif (!empty(trim($text))) {
            advanced_search($chat_id, $text, $user_id);
        }
    }
    
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
        
        sendChatAction($chat_id, 'typing');
        
        if (strpos($data, 'movie_') === 0) {
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
                    // Show options: Send One or Send All
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🎬 Send First Copy', 'callback_data' => 'send_one_' . base64_encode($movie_name)],
                                ['text' => "📤 Send All {$movie_items[0]['count']} Copies", 'callback_data' => 'send_all_' . base64_encode($movie_name)]
                            ],
                            [
                                ['text' => '🔙 Back to Search', 'callback_data' => 'back_to_search_' . base64_encode($movie_name)]
                            ]
                        ]
                    ];
                    
                    sendHinglish($chat_id, 'search_select', [], $keyboard);
                    answerCallbackQuery($query['id'], "Select option");
                } else {
                    answerCallbackQuery($query['id'], "❌ Movie not found", true);
                }
            }
        }
        elseif (strpos($data, 'send_one_') === 0) {
            $movie_name_encoded = str_replace('send_one_', '', $data);
            $movie_name = base64_decode($movie_name_encoded);
            
            $all_movies = $csvManager->getCachedData();
            $movie_items = [];
            
            foreach ($all_movies as $item) {
                if (strtolower($item['movie_name']) === strtolower($movie_name)) {
                    $movie_items[] = $item;
                }
            }
            
            if (!empty($movie_items)) {
                deliver_item_to_chat($chat_id, $movie_items[0]);
                answerCallbackQuery($query['id'], "✅ First copy sent");
            } else {
                answerCallbackQuery($query['id'], "❌ Movie not found", true);
            }
        }
        elseif (strpos($data, 'send_all_') === 0) {
            $movie_name_encoded = str_replace('send_all_', '', $data);
            $movie_name = base64_decode($movie_name_encoded);
            
            $all_movies = $csvManager->getCachedData();
            $movie_items = [];
            
            foreach ($all_movies as $item) {
                if (strtolower($item['movie_name']) === strtolower($movie_name)) {
                    $movie_items[] = $item;
                }
            }
            
            if (!empty($movie_items)) {
                $sent = deliver_all_copies($chat_id, $movie_name, $movie_items);
                answerCallbackQuery($query['id'], "✅ $sent copies sent");
            } else {
                answerCallbackQuery($query['id'], "❌ Movie not found", true);
            }
        }
        elseif (strpos($data, 'send_all_results_') === 0) {
            $encoded = str_replace('send_all_results_', '', $data);
            $movie_names = json_decode(base64_decode($encoded), true);
            
            $all_movies = $csvManager->getCachedData();
            $sent_count = 0;
            
            foreach ($movie_names as $movie_name) {
                foreach ($all_movies as $item) {
                    if (strtolower($item['movie_name']) === strtolower($movie_name)) {
                        if (deliver_item_to_chat($chat_id, $item)) {
                            $sent_count++;
                            usleep(300000);
                        }
                    }
                }
            }
            
            answerCallbackQuery($query['id'], "✅ $sent_count movies sent");
            sendMessage($chat_id, "✅ $sent_count movies sent!\n\n📢 Join: @EntertainmentTadka786", null, 'HTML');
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
            sendMessage($chat_id, "✅ Pagination stopped. Type /totalupload to start again.", null, 'HTML');
            answerCallbackQuery($query['id'], "Stopped");
        }
        elseif ($data === 'request_movie') {
            sendHinglish($chat_id, 'request_guide', ['limit' => $requestSystem->getUserMaxRequests($user_id)]);
            answerCallbackQuery($query['id'], "📝 Request guide opened");
        }
        elseif ($data === 'browse_all') {
            totalupload_controller($chat_id, 1);
            answerCallbackQuery($query['id'], "Browsing all movies");
        }
        elseif ($data === 'help_command') {
            $help_text = getHinglishResponse('help');
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start']
                    ]
                ]
            ];
            
            editMessageText($chat_id, $message['message_id'], $help_text, $keyboard, 'HTML');
            answerCallbackQuery($query['id'], "Help information loaded");
        }
        elseif ($data === 'back_to_start') {
            $lang = getUserLanguage($user_id);
            
            if ($lang == 'hindi') {
                $welcome = getHinglishResponse('welcome_hindi');
            } else {
                $welcome = getHinglishResponse('welcome');
            }
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                        ['text' => '🎬 Serial Channel', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786']
                    ],
                    [
                        ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies'],
                        ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']
                    ],
                    [
                        ['text' => '📥 Request Guide', 'callback_data' => 'request_movie'],
                        ['text' => '❓ Help', 'callback_data' => 'help_command']
                    ],
                    [
                        ['text' => '📊 Stats', 'callback_data' => 'show_stats'],
                        ['text' => '🎬 Browse All', 'callback_data' => 'browse_all']
                    ]
                ]
            ];
            
            if ($adminPanel->isAdmin($user_id)) {
                $keyboard['inline_keyboard'][] = [
                    ['text' => '🔐 Admin Panel', 'callback_data' => 'admin_main']
                ];
            }
            
            editMessageText($chat_id, $message['message_id'], $welcome, $keyboard, 'HTML');
            answerCallbackQuery($query['id'], "Welcome back!");
        }
        elseif ($data === 'show_stats') {
            $stats = $csvManager->getStats();
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $total_users = count($users_data['users'] ?? []);
            $file_stats = json_decode(file_get_contents(STATS_FILE), true);
            
            $channels_text = "";
            foreach ($stats['channels'] as $channel_id => $count) {
                $channel_name = getChannelUsername($channel_id);
                $channel_type = getChannelType($channel_id);
                $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
                $channels_text .= $type_icon . " " . $channel_name . ": " . $count . " movies\n";
            }
            
            $stats_text = getHinglishResponse('stats', [
                'movies' => $stats['total_movies'],
                'users' => $total_users,
                'searches' => $file_stats['total_searches'] ?? 0,
                'updated' => $stats['last_updated'],
                'channels' => $channels_text
            ]);
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start'],
                        ['text' => '🔄 Refresh', 'callback_data' => 'show_stats']
                    ]
                ]
            ];
            
            editMessageText($chat_id, $message['message_id'], $stats_text, $keyboard, 'HTML');
            answerCallbackQuery($query['id'], "Statistics updated");
        }
        elseif (strpos($data, 'lang_') === 0) {
            $lang = str_replace('lang_', '', $data);
            setUserLanguage($user_id, $lang);
            
            $messages = [
                'english' => getHinglishResponse('language_english'),
                'hindi' => getHinglishResponse('language_hindi'),
                'hinglish' => getHinglishResponse('language_hinglish')
            ];
            
            editMessageText($chat_id, $message['message_id'], "✅ " . $messages[$lang], null, 'HTML');
            answerCallbackQuery($query['id'], $messages[$lang]);
        }
        elseif (strpos($data, 'admin_') === 0) {
            if (!$adminPanel->isAdmin($user_id)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            if ($data === 'admin_main') {
                $admin_menu = $adminPanel->getAdminMainMenu();
                editMessageText($chat_id, $message['message_id'], $admin_menu['text'], $admin_menu['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Admin Panel");
            }
            elseif ($data === 'admin_stats') {
                $stats_view = $adminPanel->getStats($csvManager, $requestSystem, $monitor);
                editMessageText($chat_id, $message['message_id'], $stats_view['text'], $stats_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Stats loaded");
            }
            elseif ($data === 'admin_performance') {
                $perf_view = $adminPanel->getPerformanceMetrics($monitor);
                editMessageText($chat_id, $message['message_id'], $perf_view['text'], $perf_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Performance metrics");
            }
            elseif (strpos($data, 'admin_users_') === 0 || $data === 'admin_users') {
                $page = 1;
                if (strpos($data, 'admin_users_') === 0) {
                    $page = (int)str_replace('admin_users_', '', $data);
                }
                $users_view = $adminPanel->getUsersList($page);
                editMessageText($chat_id, $message['message_id'], $users_view['text'], $users_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "User list loaded");
            }
            elseif (strpos($data, 'admin_movies_') === 0 || $data === 'admin_movies') {
                $page = 1;
                if (strpos($data, 'admin_movies_') === 0) {
                    $page = (int)str_replace('admin_movies_', '', $data);
                }
                $movies_view = $adminPanel->getMoviesList($csvManager, $page);
                editMessageText($chat_id, $message['message_id'], $movies_view['text'], $movies_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Movie list loaded");
            }
            elseif (strpos($data, 'admin_pending_') === 0 || $data === 'admin_pending') {
                $page = 1;
                if (strpos($data, 'admin_pending_') === 0) {
                    $page = (int)str_replace('admin_pending_', '', $data);
                }
                $pending_view = $adminPanel->getPendingRequestsView($requestSystem, $page);
                editMessageText($chat_id, $message['message_id'], $pending_view['text'], $pending_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Pending requests loaded");
            }
            elseif ($data === 'admin_broadcast') {
                $broadcast_view = $adminPanel->getBroadcastInstructions();
                editMessageText($chat_id, $message['message_id'], $broadcast_view['text'], $broadcast_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Broadcast mode");
                
                // Store broadcast state
                $broadcast_state = [
                    'chat_id' => $chat_id,
                    'admin_id' => $user_id,
                    'step' => 'awaiting_message'
                ];
                file_put_contents('broadcast_state.json', json_encode($broadcast_state));
            }
            elseif ($data === 'admin_settings') {
                $settings_view = $adminPanel->getSettingsMenu();
                editMessageText($chat_id, $message['message_id'], $settings_view['text'], $settings_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Settings");
            }
            elseif ($data === 'admin_backup') {
                $backup_view = $adminPanel->getBackupMenu();
                editMessageText($chat_id, $message['message_id'], $backup_view['text'], $backup_view['keyboard'], 'HTML');
                answerCallbackQuery($query['id'], "Backup menu");
            }
            elseif ($data === 'admin_backup_create') {
                $backup_dir = BACKUP_DIR;
                $files_to_backup = [CSV_FILE, USERS_FILE, STATS_FILE, REQUESTS_FILE, INDEX_FILE, METRICS_FILE];
                
                $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.zip';
                
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
                        foreach ($files_to_backup as $file) {
                            if (file_exists($file)) {
                                $zip->addFile($file, basename($file));
                            }
                        }
                        $zip->close();
                        
                        $size = filesize($backup_file);
                        $msg = "✅ Backup created successfully!\n\n";
                        $msg .= "📁 File: " . basename($backup_file) . "\n";
                        $msg .= "📊 Size: " . round($size / 1024, 2) . " KB";
                    } else {
                        $msg = "❌ Backup failed - could not create ZIP";
                    }
                } else {
                    // Fallback: copy files individually
                    $count = 0;
                    foreach ($files_to_backup as $file) {
                        if (file_exists($file)) {
                            $backup_file = $backup_dir . basename($file) . '.' . date('Y-m-d_H-i-s') . '.bak';
                            copy($file, $backup_file);
                            $count++;
                        }
                    }
                    $msg = "✅ Backup created! $count files backed up (ZIP not available)";
                }
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔙 Back to Backup', 'callback_data' => 'admin_backup'],
                            ['text' => '🔐 Main Menu', 'callback_data' => 'admin_main']
                        ]
                    ]
                ];
                
                editMessageText($chat_id, $message['message_id'], $msg, $keyboard, 'HTML');
                answerCallbackQuery($query['id'], "Backup created");
            }
            elseif ($data === 'admin_backup_list') {
                $backup_dir = BACKUP_DIR;
                $backup_files = glob($backup_dir . '*.{zip,bak}', GLOB_BRACE);
                rsort($backup_files);
                
                if (empty($backup_files)) {
                    $msg = "📁 No backups found.";
                } else {
                    $msg = "📋 <b>Backup List</b>\n\n";
                    $count = min(10, count($backup_files));
                    
                    for ($i = 0; $i < $count; $i++) {
                        $file = $backup_files[$i];
                        $filename = basename($file);
                        $size = round(filesize($file) / 1024, 2);
                        $date = date('d M Y H:i', filemtime($file));
                        
                        $msg .= ($i + 1) . ". <code>$filename</code>\n";
                        $msg .= "   📊 {$size}KB | 📅 $date\n\n";
                    }
                }
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔙 Back to Backup', 'callback_data' => 'admin_backup'],
                            ['text' => '🔐 Main Menu', 'callback_data' => 'admin_main']
                        ]
                    ]
                ];
                
                editMessageText($chat_id, $message['message_id'], $msg, $keyboard, 'HTML');
                answerCallbackQuery($query['id'], "Backup list");
            }
            elseif ($data === 'admin_toggle_request') {
                // This would require modifying environment variable - handle separately
                answerCallbackQuery($query['id'], "Toggle request system - manual config change required");
            }
            elseif ($data === 'admin_toggle_maintenance') {
                // This would require modifying environment variable - handle separately
                answerCallbackQuery($query['id'], "Toggle maintenance - manual config change required");
            }
            elseif ($data === 'admin_clear_cache') {
                $csvManager->clearCache();
                answerCallbackQuery($query['id'], "✅ Cache cleared");
            }
            elseif ($data === 'admin_backup_now') {
                // Quick backup without zip
                $backup_dir = BACKUP_DIR;
                $files_to_backup = [CSV_FILE, USERS_FILE, STATS_FILE, REQUESTS_FILE, INDEX_FILE, METRICS_FILE];
                
                $backup_time = date('Y-m-d_H-i-s');
                $count = 0;
                
                foreach ($files_to_backup as $file) {
                    if (file_exists($file)) {
                        $backup_file = $backup_dir . basename($file) . '.' . $backup_time . '.bak';
                        copy($file, $backup_file);
                        $count++;
                    }
                }
                
                answerCallbackQuery($query['id'], "✅ Backed up $count files");
            }
            elseif (strpos($data, 'admin_approve_') === 0) {
                $request_id = str_replace('admin_approve_', '', $data);
                $result = $requestSystem->approveRequest($request_id, $user_id);
                
                if ($result['success']) {
                    $request = $result['request'];
                    answerCallbackQuery($query['id'], "✅ Request #$request_id approved");
                    notifyUserAboutRequest($request['user_id'], $request, 'approved');
                    
                    // Refresh pending view
                    $pending_view = $adminPanel->getPendingRequestsView($requestSystem, 1);
                    editMessageText($chat_id, $message['message_id'], $pending_view['text'], $pending_view['keyboard'], 'HTML');
                } else {
                    answerCallbackQuery($query['id'], $result['message'], true);
                }
            }
            elseif (strpos($data, 'admin_reject_') === 0) {
                $request_id = str_replace('admin_reject_', '', $data);
                
                $reason_keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Already Available', 'callback_data' => 'admin_reject_reason_' . $request_id . '_Already Available'],
                            ['text' => 'Invalid Name', 'callback_data' => 'admin_reject_reason_' . $request_id . '_Invalid movie name']
                        ],
                        [
                            ['text' => 'Duplicate', 'callback_data' => 'admin_reject_reason_' . $request_id . '_Duplicate request'],
                            ['text' => 'Not Available', 'callback_data' => 'admin_reject_reason_' . $request_id . '_Not available']
                        ],
                        [
                            ['text' => '🔙 Cancel', 'callback_data' => 'admin_pending_1']
                        ]
                    ]
                ];
                
                editMessageText($chat_id, $message['message_id'], "Select rejection reason for Request #$request_id:", $reason_keyboard, 'HTML');
                answerCallbackQuery($query['id'], "Select reason");
            }
            elseif (strpos($data, 'admin_reject_reason_') === 0) {
                $parts = explode('_', $data, 5);
                $request_id = $parts[3];
                $reason = $parts[4];
                
                $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
                
                if ($result['success']) {
                    $request = $result['request'];
                    answerCallbackQuery($query['id'], "❌ Request #$request_id rejected");
                    notifyUserAboutRequest($request['user_id'], $request, 'rejected');
                    
                    $pending_view = $adminPanel->getPendingRequestsView($requestSystem, 1);
                    editMessageText($chat_id, $message['message_id'], $pending_view['text'], $pending_view['keyboard'], 'HTML');
                } else {
                    answerCallbackQuery($query['id'], $result['message'], true);
                }
            }
            elseif (strpos($data, 'admin_bulk_approve_') === 0) {
                $encoded = str_replace('admin_bulk_approve_', '', $data);
                $request_ids = json_decode(base64_decode($encoded), true);
                
                $result = $requestSystem->bulkApprove($request_ids, $user_id);
                
                foreach ($request_ids as $req_id) {
                    $request = $requestSystem->getRequest($req_id);
                    if ($request && $request['status'] == 'approved') {
                        notifyUserAboutRequest($request['user_id'], $request, 'approved');
                    }
                }
                
                answerCallbackQuery($query['id'], "✅ Approved {$result['approved_count']} requests");
                
                $pending_view = $adminPanel->getPendingRequestsView($requestSystem, 1);
                editMessageText($chat_id, $message['message_id'], $pending_view['text'], $pending_view['keyboard'], 'HTML');
            }
            elseif (strpos($data, 'admin_bulk_reject_') === 0) {
                $encoded = str_replace('admin_bulk_reject_', '', $data);
                $request_ids = json_decode(base64_decode($encoded), true);
                
                $result = $requestSystem->bulkReject($request_ids, $user_id, 'Bulk rejected');
                
                foreach ($request_ids as $req_id) {
                    $request = $requestSystem->getRequest($req_id);
                    if ($request && $request['status'] == 'rejected') {
                        notifyUserAboutRequest($request['user_id'], $request, 'rejected');
                    }
                }
                
                answerCallbackQuery($query['id'], "❌ Rejected {$result['rejected_count']} requests");
                
                $pending_view = $adminPanel->getPendingRequestsView($requestSystem, 1);
                editMessageText($chat_id, $message['message_id'], $pending_view['text'], $pending_view['keyboard'], 'HTML');
            }
            elseif ($data === 'admin_close') {
                sendMessage($chat_id, "Admin panel closed. Use /admin to reopen.", null, 'HTML');
                answerCallbackQuery($query['id'], "Closed");
            }
        }
    }
    
    // Handle broadcast message
    if (isset($update['message']) && file_exists('broadcast_state.json')) {
        $broadcast_state = json_decode(file_get_contents('broadcast_state.json'), true);
        if ($broadcast_state && $broadcast_state['admin_id'] == $user_id && $broadcast_state['step'] == 'awaiting_message') {
            $broadcast_text = $text;
            
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $users = $users_data['users'] ?? [];
            $user_count = count($users);
            
            $preview = $adminPanel->confirmBroadcast($broadcast_text, $user_count);
            
            // Store broadcast message
            $broadcast_state['step'] = 'confirm';
            $broadcast_state['message'] = $broadcast_text;
            file_put_contents('broadcast_state.json', json_encode($broadcast_state));
            
            editMessageText($chat_id, $message['message_id'], $preview['text'], $preview['keyboard'], 'HTML');
        }
    }
    
    // Handle broadcast confirmation
    if (isset($update['callback_query']) && file_exists('broadcast_state.json')) {
        $query = $update['callback_query'];
        $data = $query['data'];
        $user_id = $query['from']['id'];
        $chat_id = $query['message']['chat']['id'];
        $message_id = $query['message']['message_id'];
        
        if ($data === 'admin_broadcast_send') {
            $broadcast_state = json_decode(file_get_contents('broadcast_state.json'), true);
            if ($broadcast_state && $broadcast_state['admin_id'] == $user_id) {
                $broadcast_text = $broadcast_state['message'];
                
                $users_data = json_decode(file_get_contents(USERS_FILE), true);
                $users = $users_data['users'] ?? [];
                
                $sent = 0;
                $failed = 0;
                
                editMessageText($chat_id, $message_id, "📢 Sending broadcast to " . count($users) . " users...", null, 'HTML');
                
                foreach (array_keys($users) as $target_user_id) {
                    $result = sendMessage($target_user_id, $broadcast_text, null, 'HTML');
                    if ($result !== false) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                    usleep(50000);
                }
                
                $result_msg = "📢 <b>Broadcast Complete</b>\n\n";
                $result_msg .= "✅ Sent: $sent\n";
                $result_msg .= "❌ Failed: $failed\n";
                $result_msg .= "👥 Total: " . count($users);
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔐 Main Menu', 'callback_data' => 'admin_main']
                        ]
                    ]
                ];
                
                editMessageText($chat_id, $message_id, $result_msg, $keyboard, 'HTML');
                answerCallbackQuery($query['id'], "Broadcast complete");
                
                unlink('broadcast_state.json');
            }
        }
    }
    
    // Daily maintenance at 3 AM
    if (date('H:i') == '03:00') {
        $csvManager->flushBuffer();
        $csvManager->clearCache();
        log_error("Daily maintenance completed", 'INFO');
    }
    
    // Track performance at the end of processing
    $monitor->trackPerformance();
    
    http_response_code(200);
    echo "OK";
    exit;
}

// ==================== DEFAULT HTML PAGE ====================
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
        
        .security-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .warning-box {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .performance-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #ffc107;
            color: #333;
            border-radius: 12px;
            font-size: 0.7em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Bot <span class="security-badge">SECURE v4.0</span></h1>
        
        <div class="status-card">
            <h2>✅ Bot is Running</h2>
            <p>Telegram Bot for movie searches across multiple channels | Hosted on Render.com</p>
            <p><strong>Bot Username:</strong> @EntertainmentTadkaBot</p>
            <p><strong>Bot ID:</strong> 8315381064</p>
            <p><strong>Movie Request System:</strong> ✅ Active</p>
            <p><strong>Admin Panel:</strong> ✅ Active (Use /admin)</p>
            <p><strong>Hinglish Support:</strong> ✅ Active</p>
            <p><strong>User Reputation System:</strong> ✅ Active</p>
            <p><strong>Performance Monitoring:</strong> ✅ Active</p>
            <p><strong>Security Level:</strong> 🔒 High</p>
        </div>
        
        <?php
        $metrics = $monitor->getMetrics();
        if (($metrics['avg_response_time'] ?? 0) > 2.0): ?>
        <div class="warning-box">
            <strong>⚠️ PERFORMANCE WARNING:</strong> High response time detected (<?php echo round($metrics['avg_response_time'] * 1000, 2); ?>ms). Cache cleared automatically.
        </div>
        <?php endif; ?>
        
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
                $stats = $csvManager->getStats();
                $users_data = json_decode(@file_get_contents(USERS_FILE), true);
                $total_users = count($users_data['users'] ?? []);
                $request_stats = $requestSystem->getStats();
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
                    <div>📋 Total Requests</div>
                    <div class="stat-value"><?php echo $request_stats['total_requests']; ?></div>
                </div>
                <div class="stat-item">
                    <div>⏳ Pending</div>
                    <div class="stat-value"><?php echo $request_stats['pending']; ?></div>
                </div>
                <div class="stat-item">
                    <div>⚡ Response</div>
                    <div class="stat-value" style="font-size: 1.5em;"><?php echo round(($metrics['avg_response_time'] ?? 0) * 1000, 2); ?>ms</div>
                </div>
            </div>
        </div>
        
        <h3>📡 Configured Channels</h3>
        <div class="channels-grid">
            <?php
            foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) {
                if (!empty($channel['username'])) {
                    $class = $channel['username'] == '@EntertainmentTadka786' ? 'public' : 
                             ($channel['username'] == '@Entertainment_Tadka_Serial_786' ? 'public' : 
                             ($channel['username'] == '@threater_print_movies' ? 'public' : 'public'));
                    echo '<div class="channel-card ' . $class . '">';
                    echo '<div style="font-weight: bold; margin-bottom: 8px;">🌐 Public Channel</div>';
                    echo '<div style="font-size: 1.1em; margin-bottom: 5px;">' . htmlspecialchars($channel['username']) . '</div>';
                    echo '<div style="font-size: 0.9em; opacity: 0.8;">ID: ' . htmlspecialchars($channel['id']) . '</div>';
                    echo '</div>';
                }
            }
            
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
            <h3>✨ New Features <span class="security-badge">v4.0</span></h3>
            <div class="feature-item">✅ "Send All Results" button in search results</div>
            <div class="feature-item">✅ "Send All Copies" button for selected movies</div>
            <div class="feature-item">✅ Commands ke saath inline buttons</div>
            <div class="feature-item">✅ Database Optimization with indexing</div>
            <div class="feature-item">✅ Caching Strategy without Redis</div>
            <div class="feature-item">✅ Request System Enhancements (smart auto-approve)</div>
            <div class="feature-item">✅ Batch Operations for multiple movies</div>
            <div class="feature-item">✅ User Reputation System with bonus requests</div>
            <div class="feature-item">✅ Performance Monitoring with auto-cache clear</div>
            <div class="feature-item">✅ Bug fixes: array_column issue resolved</div>
        </div>
        
        <div class="feature-list">
            <h3>✨ All Features</h3>
            <div class="feature-item">✅ Multi-channel support (4 Public + 2 Private channels)</div>
            <div class="feature-item">✅ Smart movie search with partial matching</div>
            <div class="feature-item">✅ Movie Request System with moderation</div>
            <div class="feature-item">✅ Duplicate request blocking & flood control</div>
            <div class="feature-item">✅ Auto-approve when movie added to database</div>
            <div class="feature-item">✅ Admin Panel with complete moderation tools</div>
            <div class="feature-item">✅ Bulk approve/reject actions</div>
            <div class="feature-item">✅ User notification system</div>
            <div class="feature-item">✅ CSV-based database with indexing</div>
            <div class="feature-item">✅ Admin statistics and performance monitoring</div>
            <div class="feature-item">✅ Pagination for browsing all movies</div>
            <div class="feature-item">✅ Automatic channel post tracking and indexing</div>
            <div class="feature-item">✅ Hinglish Language Support with auto-detection</div>
            <div class="feature-item">✅ User points and reputation system</div>
            <div class="feature-item">✅ Broadcast messaging to all users</div>
            <div class="feature-item">✅ Backup management system</div>
            <div class="feature-item">✅ Rate limiting & DoS protection</div>
            <div class="feature-item">✅ Input validation & XSS protection</div>
            <div class="feature-item">✅ File locking for safe concurrent access</div>
            <div class="feature-item">✅ Environment variable configuration</div>
            <div class="feature-item">✅ Interactive Request Guide with Hindi/English instructions</div>
        </div>
        
        <div style="margin-top: 40px; padding: 25px; background: rgba(255, 255, 255, 0.15); border-radius: 15px;">
            <h3>🚀 Quick Start Guide</h3>
            <ol style="margin-left: 20px; margin-top: 15px;">
                <li style="margin-bottom: 10px;">Bot already configured with your settings</li>
                <li style="margin-bottom: 10px;">Click "Set Webhook" to configure Telegram webhook</li>
                <li style="margin-bottom: 10px;">Test the bot using the "Test Bot" button</li>
                <li style="margin-bottom: 10px;">Start searching movies in Telegram bot</li>
                <li style="margin-bottom: 10px;">Use /request or type "pls add MovieName" to request movies</li>
                <li style="margin-bottom: 10px;">Check status with /myrequests command</li>
                <li style="margin-bottom: 10px;">Click "📥 Request Guide" button for step-by-step guide</li>
                <li style="margin-bottom: 10px;">Use /language to change language</li>
                <li style="margin-bottom: 10px;">Admins: Use /admin to open Admin Panel</li>
                <li style="margin-bottom: 10px;">Search results mein "Send All Results" button dikhega</li>
                <li style="margin-bottom: 10px;">Movie select karne par "Send All Copies" button milega</li>
            </ol>
        </div>
        
        <footer>
            <p>🎬 Entertainment Tadka Bot | Powered by PHP & Telegram Bot API | Hosted on Render.com</p>
            <p style="margin-top: 10px; font-size: 0.9em;">© <?php echo date('Y'); ?> - All rights reserved | Secure Version 4.0 | Admin Panel | Performance Monitoring | User Reputation</p>
        </footer>
    </div>
</body>
</html>
<?php
// ==================== END OF FILE ====================
// Features: Movie Search, Request System, Admin Panel, Hinglish Support, 
// Send All Results, Send All Copies, Database Indexing, Batch Operations,
// User Reputation System, Performance Monitoring
?>
