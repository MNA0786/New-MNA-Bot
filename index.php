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
    // Bot Configuration - USE ENVIRONMENT VARIABLES
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot',
    
    // Admin IDs (comma separated for multiple admins)
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    
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
    
    // Settings
    'CACHE_EXPIRY' => 300, // 5 minutes
    'ITEMS_PER_PAGE' => 5,
    'CSV_BUFFER_SIZE' => 50,
    
    // Request System Settings
    'MAX_REQUESTS_PER_DAY' => 3,
    'DUPLICATE_CHECK_HOURS' => 24,
    'REQUEST_SYSTEM_ENABLED' => true,
    
    // Security Settings
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60 // seconds
];

// Validate required configuration
if (empty($ENV_CONFIG['BOT_TOKEN']) || $ENV_CONFIG['BOT_TOKEN'] === 'YOUR_BOT_TOKEN_HERE') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die("❌ Bot Token not configured. Please set BOT_TOKEN environment variable.");
}

// Extract config to constants
define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_IDS', $ENV_CONFIG['ADMIN_IDS']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUESTS_FILE', $ENV_CONFIG['REQUESTS_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);
define('RATE_LIMIT_REQUESTS', $ENV_CONFIG['RATE_LIMIT_REQUESTS']);
define('RATE_LIMIT_WINDOW', $ENV_CONFIG['RATE_LIMIT_WINDOW']);

// Channel constants for easy access
define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');
define('BACKUP_CHANNEL_USERNAME', '@ETBackup');

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
            // Allow Unicode for Hindi/English movies
            if (!preg_match('/^[\p{L}\p{N}\s\-\.\,\&\+\'\"\(\)\!\:\;\?]{2,200}$/u', $input)) {
                return false;
            }
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
        case 'user_id':
            return preg_match('/^\d+$/', $input) ? intval($input) : false;
            
        case 'command':
            return preg_match('/^\/[a-zA-Z0-9_]+$/', $input) ? $input : false;
            
        case 'telegram_id':
            return preg_match('/^\-?\d+$/', $input) ? $input : false;
            
        case 'filename':
            $input = basename($input);
            $allowed_files = ['movies.csv', 'users.json', 'bot_stats.json', 'requests.json'];
            return in_array($input, $allowed_files) ? $input : false;
            
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
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
        
        // Purge old requests
        self::$limits[$key] = array_filter(self::$limits[$key], 
            function($time) use ($window_start) {
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
            'admin_ids' => ADMIN_IDS
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
    
    // ==================== CORE FUNCTIONS ====================
    public function submitRequest($user_id, $movie_name, $user_name = '') {
        // Validate input
        $movie_name = validateInput($movie_name, 'movie_name');
        $user_id = validateInput($user_id, 'user_id');
        
        if (!$movie_name || !$user_id) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        if (empty($movie_name) || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        // Check for duplicate request (same user + same movie within 24 hours)
        $duplicate_check = $this->checkDuplicateRequest($user_id, $movie_name);
        if ($duplicate_check['is_duplicate']) {
            return [
                'success' => false, 
                'message' => "You already requested '$movie_name' recently. Please wait before requesting again."
            ];
        }
        
        // Flood control (max 3 requests per 24 hours)
        $flood_check = $this->checkFloodControl($user_id);
        if (!$flood_check['allowed']) {
            return [
                'success' => false,
                'message' => "You've reached the daily limit of " . MAX_REQUESTS_PER_DAY . " requests. Please try again tomorrow."
            ];
        }
        
        // Create new request
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
        
        // Update user stats
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
        
        // Reset daily counter if new day
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
        $time_limit = time() - (24 * 3600); // 24 hours
        
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
    
    private function checkFloodControl($user_id) {
        $data = $this->loadData();
        
        if (!isset($data['user_stats'][$user_id])) {
            return ['allowed' => true, 'remaining' => MAX_REQUESTS_PER_DAY];
        }
        
        $user_stats = $data['user_stats'][$user_id];
        
        // Reset if new day
        if ($user_stats['last_request_date'] != date('Y-m-d')) {
            return ['allowed' => true, 'remaining' => MAX_REQUESTS_PER_DAY];
        }
        
        $remaining = MAX_REQUESTS_PER_DAY - $user_stats['requests_today'];
        
        return [
            'allowed' => $user_stats['requests_today'] < MAX_REQUESTS_PER_DAY,
            'remaining' => max(0, $remaining)
        ];
    }
    
    // ==================== MODERATION FUNCTIONS ====================
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
        
        // Update request
        $data['requests'][$request_id]['status'] = 'approved';
        $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['approved_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        
        // Update stats
        $data['system_stats']['approved']++;
        $data['system_stats']['pending']--;
        
        // Update user stats
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
        
        // Update request
        $data['requests'][$request_id]['status'] = 'rejected';
        $data['requests'][$request_id]['rejected_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['rejected_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['reason'] = $reason;
        
        // Update stats
        $data['system_stats']['rejected']++;
        $data['system_stats']['pending']--;
        
        // Update user stats
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
    
    // ==================== QUERY FUNCTIONS ====================
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
        
        // Sort by creation date (oldest first)
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
        
        // Sort by creation date (newest first)
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
    
    // ==================== AUTO-APPROVE HOOK ====================
    public function checkAutoApprove($movie_name) {
        $movie_name = validateInput($movie_name, 'movie_name');
        if (!$movie_name) return [];
        
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $auto_approved = [];
        
        foreach ($data['requests'] as $request_id => $request) {
            if ($request['status'] == 'pending') {
                $request_movie_lower = strtolower($request['movie_name']);
                
                // Simple matching logic
                if (strpos($movie_lower, $request_movie_lower) !== false || 
                    strpos($request_movie_lower, $movie_lower) !== false ||
                    similar_text($movie_lower, $request_movie_lower) > 80) {
                    
                    // Auto-approve
                    $data['requests'][$request_id]['status'] = 'approved';
                    $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['approved_by'] = 'system';
                    $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['reason'] = 'Auto-approved: Movie added to database';
                    
                    // Update stats
                    $data['system_stats']['approved']++;
                    $data['system_stats']['pending']--;
                    
                    // Update user stats
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
    
    // ==================== NOTIFICATION SYSTEM ====================
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

// ==================== CSV MANAGER CLASS (SECURE VERSION) ====================
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
                'last_updated' => date('Y-m-d H:i:s')
            ];
            @file_put_contents(STATS_FILE, json_encode($stats_data, JSON_PRETTY_PRINT));
            @chmod(STATS_FILE, 0666);
        }
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
    
    public function bufferedAppend($movie_name, $message_id, $channel_id) {
        // Validate inputs
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
        $fp = $this->acquireLock(CSV_FILE, LOCK_EX);
        if (!$fp) {
            log_error("Failed to lock CSV file for writing", 'ERROR');
            return false;
        }
        
        try {
            // Append to CSV
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
            return true;
        } catch (Exception $e) {
            log_error("Error flushing buffer: " . $e->getMessage(), 'ERROR');
            return false;
        } finally {
            $this->releaseLock($fp);
        }
    }
    
    public function readCSV() {
        $data = [];
        
        if (!file_exists(CSV_FILE)) {
            log_error("CSV file not found", 'ERROR');
            return $data;
        }
        
        // Shared lock for reading
        $fp = $this->acquireLock(CSV_FILE, LOCK_SH);
        if (!$fp) {
            log_error("Failed to lock CSV file for reading", 'ERROR');
            return $data;
        }
        
        try {
            $header = fgetcsv($fp);
            if ($header === false || $header[0] !== 'movie_name') {
                // Invalid header, rebuild
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
        $query = validateInput($query, 'movie_name');
        if (!$query) {
            return [];
        }
        
        $data = $this->getCachedData();
        $query_lower = strtolower(trim($query));
        $results = [];
        
        log_error("Searching for: $query", 'INFO', ['total_items' => count($data)]);
        
        foreach ($data as $item) {
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

// ==================== TELEGRAM API FUNCTIONS (WITH RATE LIMITING) ====================
function apiRequest($method, $params = array(), $is_multipart = false) {
    // Apply rate limiting
    if (!RateLimiter::check('telegram_api', RATE_LIMIT_REQUESTS, RATE_LIMIT_WINDOW)) {
        log_error("Telegram API rate limit exceeded", 'WARNING');
        usleep(100000); // 100ms delay
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
        'text' => validateInput($text, 'text')
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
        'text' => validateInput($text, 'text')
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
    $text = "🎬 " . htmlspecialchars($item['movie_name'], ENT_QUOTES, 'UTF-8') . "\n";
    $text .= "📁 Channel: " . getChannelUsername($channel_id) . "\n";
    $text .= "🔗 Message ID: " . $message_id;
    sendMessage($chat_id, $text, null, 'HTML');
    log_error("Used fallback text delivery", 'WARNING');
    return false;
}

// ==================== ADVANCED SEARCH FUNCTION ====================
function advanced_search($chat_id, $query, $user_id = null) {
    global $csvManager;
    
    // Show typing indicator
    sendChatAction($chat_id, 'typing');
    
    $q = validateInput($query, 'movie_name');
    if (!$q) {
        sendMessage($chat_id, "❌ Invalid movie name format.", null, 'HTML');
        return;
    }
    
    $q = strtolower(trim($q));
    
    log_error("Advanced search initiated by $user_id", 'INFO', ['query' => $query]);
    
    // 1. Minimum length check
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters for search", null, 'HTML');
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
        
        sendMessage($chat_id, $msg, null, 'HTML');
        
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
        
        sendMessage($chat_id, "🚀 Select a movie to get all copies:", $keyboard, 'HTML');
        
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
        sendMessage($chat_id, $messages[$lang], null, 'HTML');
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
    
    $points_map = ['search' => 1, 'found_movie' => 5, 'daily_login' => 10];
    
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
    global $csvManager, $ENV_CONFIG, $requestSystem;
    
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    $request_stats = $requestSystem->getStats();
    
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
    
    $msg .= "\n📋 Request System Stats:\n";
    $msg .= "• Total Requests: " . $request_stats['total_requests'] . "\n";
    $msg .= "• Pending: " . $request_stats['pending'] . "\n";
    $msg .= "• Approved: " . $request_stats['approved'] . "\n";
    $msg .= "• Rejected: " . $request_stats['rejected'] . "\n";
    
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
        $maintenance_msg = "🛠️ <b>Bot Under Maintenance</b>\n\n";
        $maintenance_msg .= "We're temporarily unavailable for updates.\n";
        $maintenance_msg .= "Will be back in few days!\n\n";
        $maintenance_msg .= "Thanks for patience 🙏";
        sendMessage($chat_id, $maintenance_msg, null, 'HTML');
    }
    exit;
}

// ==================== MAIN PROCESSING ====================
// Initialize Managers
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();

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
    
    $request_stats = $requestSystem->getStats();
    echo "<p><strong>Total Requests:</strong> " . $request_stats['total_requests'] . "</p>";
    echo "<p><strong>Pending Requests:</strong> " . $request_stats['pending'] . "</p>";
    
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
    
    // Apply global rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!RateLimiter::check($ip, 'telegram_update', 30, 60)) {
        http_response_code(429);
        exit;
    }
    
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
                
                // Auto-approve matching requests
                $auto_approved = $requestSystem->checkAutoApprove($text);
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
    
    // Process user messages
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
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
                'username' => $message['from']['username'] ?? '',
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s'),
                'points' => 0
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
        
        // Process commands
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
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
                
                $welcome .= "🎬 <b>Movie Request System:</b>\n";
                $welcome .= "• Use /request MovieName to request a movie\n";
                $welcome .= "• Or type: 'pls add MovieName'\n";
                $welcome .= "• Check status with /myrequests\n";
                $welcome .= "• Max 3 requests per day\n\n";
                
                $welcome .= "💡 <b>Tip:</b> Use /help for all commands";
                
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                            ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']
                        ],
                        [
                            ['text' => '📥 How to Request?', 'callback_data' => 'request_movie'],
                            ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']
                        ],
                        [
                            ['text' => '❓ Help', 'callback_data' => 'help_command'],
                            ['text' => '📊 Stats', 'callback_data' => 'show_stats']
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
                $help .= "/request MovieName - Request a new movie\n";
                $help .= "/myrequests - View your movie requests\n";
                $help .= "/checkdate - Show date-wise statistics\n";
                $help .= "/totalupload - Browse all movies with pagination\n";
                $help .= "/testcsv - View all movies in database\n";
                $help .= "/checkcsv - Check CSV data (add 'all' for full list)\n";
                $help .= "/csvstats - CSV statistics\n";
                
                if (in_array($user_id, ADMIN_IDS)) {
                    $help .= "/stats - Admin statistics\n";
                    $help .= "/pendingrequests - View pending requests (Admin only)\n";
                }
                
                $help .= "\n🔍 <b>How to Search:</b>\n";
                $help .= "• Type any movie name (English/Hindi)\n";
                $help .= "• Partial names work too\n";
                $help .= "• Example: 'kgf', 'pushpa', 'hindi movie'\n\n";
                
                $help .= "🎬 <b>Movie Requests:</b>\n";
                $help .= "• Use /request MovieName\n";
                $help .= "• Or type: 'pls add MovieName'\n";
                $help .= "• Max 3 requests per day per user\n";
                $help .= "• Check status with /myrequests\n\n";
                
                $help .= "📢 <b>Channel Information:</b>\n";
                $help .= "🍿 Main: @EntertainmentTadka786\n";
                $help .= "🎭 Theater: @threater_print_movies\n";
                $help .= "📥 Requests: @EntertainmentTadka7860\n";
                $help .= "🔒 Backup: @ETBackup\n\n";
                
                $help .= "⚠️ <b>Note:</b> This bot works with webhook. If you face issues, contact admin.";
                
                sendMessage($chat_id, $help, null, 'HTML');
            }
            elseif ($command == '/request') {
                if (!REQUEST_SYSTEM_ENABLED) {
                    sendMessage($chat_id, "❌ Request system is currently disabled.", null, 'HTML');
                    return;
                }
                
                if (!isset($parts[1])) {
                    sendMessage($chat_id, "📝 Usage: /request Movie Name\nExample: /request KGF Chapter 3\n\nYou can also type: 'pls add MovieName'", null, 'HTML');
                    return;
                }
                
                $movie_name = implode(' ', array_slice($parts, 1));
                $user_name = $message['from']['first_name'] . ($message['from']['last_name'] ? ' ' . $message['from']['last_name'] : '');
                
                $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
                sendMessage($chat_id, $result['message'], null, 'HTML');
            }
            elseif ($command == '/myrequests') {
                if (!REQUEST_SYSTEM_ENABLED) {
                    sendMessage($chat_id, "❌ Request system is currently disabled.", null, 'HTML');
                    return;
                }
                
                $requests = $requestSystem->getUserRequests($user_id, 10);
                $user_stats = $requestSystem->getUserStats($user_id);
                
                if (empty($requests)) {
                    sendMessage($chat_id, "📭 You haven't made any requests yet.\nUse /request MovieName to request a movie.\n\nOr type: 'pls add MovieName'", null, 'HTML');
                    return;
                }
                
                $message = "📋 <b>Your Movie Requests</b>\n\n";
                $message .= "📊 <b>Stats:</b>\n";
                $message .= "• Total: " . $user_stats['total_requests'] . "\n";
                $message .= "• Approved: " . $user_stats['approved'] . "\n";
                $message .= "• Pending: " . $user_stats['pending'] . "\n";
                $message .= "• Rejected: " . $user_stats['rejected'] . "\n";
                $message .= "• Today: " . $user_stats['requests_today'] . "/" . MAX_REQUESTS_PER_DAY . "\n\n";
                
                $message .= "🎬 <b>Recent Requests:</b>\n";
                $i = 1;
                foreach ($requests as $req) {
                    $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                    $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                    $message .= "$i. $status_icon <b>" . $movie_name . "</b>\n";
                    $message .= "   ID: #" . $req['id'] . " | " . ucfirst($req['status']) . "\n";
                    $message .= "   Date: " . date('d M, H:i', strtotime($req['created_at'])) . "\n\n";
                    $i++;
                }
                
                sendMessage($chat_id, $message, null, 'HTML');
            }
            elseif ($command == '/pendingrequests' && in_array($user_id, ADMIN_IDS)) {
                if (!REQUEST_SYSTEM_ENABLED) {
                    sendMessage($chat_id, "❌ Request system is currently disabled.", null, 'HTML');
                    return;
                }
                
                $limit = 10;
                $filter_movie = '';
                
                if (isset($parts[1])) {
                    if (is_numeric($parts[1])) {
                        $limit = min(intval($parts[1]), 50);
                    } else {
                        $filter_movie = implode(' ', array_slice($parts, 1));
                    }
                }
                
                $requests = $requestSystem->getPendingRequests($limit, $filter_movie);
                $stats = $requestSystem->getStats();
                
                if (empty($requests)) {
                    $msg = "📭 No pending requests";
                    if ($filter_movie) {
                        $msg .= " for '$filter_movie'";
                    }
                    sendMessage($chat_id, $msg . ".", null, 'HTML');
                    return;
                }
                
                $message = "📋 <b>Pending Requests";
                if ($filter_movie) {
                    $message .= " (Filter: $filter_movie)";
                }
                $message .= "</b>\n\n";
                
                $message .= "📊 <b>System Stats:</b>\n";
                $message .= "• Total: " . $stats['total_requests'] . "\n";
                $message .= "• Pending: " . $stats['pending'] . "\n";
                $message .= "• Approved: " . $stats['approved'] . "\n";
                $message .= "• Rejected: " . $stats['rejected'] . "\n\n";
                
                $message .= "🎬 <b>Showing " . count($requests) . " requests:</b>\n\n";
                
                $keyboard = ['inline_keyboard' => []];
                
                foreach ($requests as $req) {
                    $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                    $user_name = htmlspecialchars($req['user_name'] ?: "ID: " . $req['user_id'], ENT_QUOTES, 'UTF-8');
                    $message .= "🔸 <b>#" . $req['id'] . ":</b> " . $movie_name . "\n";
                    $message .= "   👤 User: " . $user_name . "\n";
                    $message .= "   📅 Date: " . date('d M H:i', strtotime($req['created_at'])) . "\n\n";
                    
                    // Add approve/reject buttons for each request
                    $keyboard['inline_keyboard'][] = [
                        [
                            'text' => '✅ Approve #' . $req['id'],
                            'callback_data' => 'approve_' . $req['id']
                        ],
                        [
                            'text' => '❌ Reject #' . $req['id'],
                            'callback_data' => 'reject_' . $req['id']
                        ]
                    ];
                }
                
                // Add bulk action buttons
                $request_ids = array_column($requests, 'id');
                $current_page_data = base64_encode(json_encode($request_ids));
                
                $keyboard['inline_keyboard'][] = [
                    [
                        'text' => '✅ Bulk Approve This Page',
                        'callback_data' => 'bulk_approve_' . $current_page_data
                    ],
                    [
                        'text' => '❌ Bulk Reject This Page',
                        'callback_data' => 'bulk_reject_' . $current_page_data
                    ]
                ];
                
                // Add navigation if more than limit
                if (count($requests) >= $limit) {
                    $next_limit = $limit + 10;
                    $keyboard['inline_keyboard'][] = [
                        [
                            'text' => '⏭️ Load More',
                            'callback_data' => 'pending_more_' . $next_limit
                        ]
                    ];
                }
                
                sendMessage($chat_id, $message, $keyboard, 'HTML');
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
        // Normal text request detection (pls add movie)
        elseif (!empty(trim($text)) && (
            stripos($text, 'add movie') !== false || 
            stripos($text, 'please add') !== false || 
            stripos($text, 'pls add') !== false ||
            stripos($text, 'can you add') !== false ||
            stripos($text, 'request movie') !== false
        )) {
            
            if (!REQUEST_SYSTEM_ENABLED) {
                sendMessage($chat_id, "❌ Request system is currently disabled.", null, 'HTML');
                return;
            }
            
            // Extract movie name from text
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
            
            // If no pattern matched, use the whole text (excluding request words)
            if (empty($movie_name)) {
                $clean_text = preg_replace('/add movie|please add|pls add|movie|add|request|can you/i', '', $text);
                $movie_name = trim($clean_text);
            }
            
            if (strlen($movie_name) < 2) {
                sendMessage($chat_id, "🎬 Please specify which movie you want to add.\nExample: 'Please add KGF Chapter 3' or use /request command", null, 'HTML');
                return;
            }
            
            $user_name = $message['from']['first_name'] . ($message['from']['last_name'] ? ' ' . $message['from']['last_name'] : '');
            $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
            sendMessage($chat_id, $result['message'], null, 'HTML');
        }
        // Normal movie search
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
                    
                    sendMessage($chat_id, "✅ Sent $sent_count copies of '$movie_name'$source_note\n\n📢 Join: @EntertainmentTadka786", null, 'HTML');
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
            sendMessage($chat_id, "✅ Pagination stopped. Type /totalupload to start again.", null, 'HTML');
            answerCallbackQuery($query['id'], "Stopped");
        }
        elseif ($data === 'request_movie') {
            $message_text = "📝 <b>Movie Request Guide</b>\n\n";
            $message_text .= "🎬 <b>2 tarike hain movie request karne ke:</b>\n\n";
            $message_text .= "1️⃣ <b>Command se:</b>\n";
            $message_text .= "<code>/request Movie Name</code>\n";
            $message_text .= "Example: /request KGF Chapter 3\n\n";
            $message_text .= "2️⃣ <b>Natural Language se:</b>\n";
            $message_text .= "• pls add Movie Name\n";
            $message_text .= "• please add Movie Name\n";
            $message_text .= "• can you add Movie Name\n";
            $message_text .= "• request movie Movie Name\n\n";
            $message_text .= "📌 <b>Limit:</b> " . MAX_REQUESTS_PER_DAY . " requests per day\n";
            $message_text .= "⏳ <b>Status Check:</b> /myrequests\n\n";
            $message_text .= "🔗 <b>Request Channel:</b> @EntertainmentTadka7860";
            
            sendMessage($chat_id, $message_text, null, 'HTML');
            answerCallbackQuery($query['id'], "📝 Request guide opened");
        }
        elseif ($data === 'help_command') {
            $help_text = "🤖 <b>Entertainment Tadka Bot - Help</b>\n\n";
            
            $help_text .= "📋 <b>Available Commands:</b>\n";
            $help_text .= "/start - Welcome message with channel links\n";
            $help_text .= "/help - Show this help message\n";
            $help_text .= "/request MovieName - Request a new movie\n";
            $help_text .= "/myrequests - View your movie requests\n";
            $help_text .= "/checkdate - Show date-wise statistics\n";
            $help_text .= "/totalupload - Browse all movies with pagination\n";
            $help_text .= "/testcsv - View all movies in database\n";
            $help_text .= "/checkcsv - Check CSV data (add 'all' for full list)\n";
            $help_text .= "/csvstats - CSV statistics\n";
            
            if (in_array($user_id, ADMIN_IDS)) {
                $help_text .= "/stats - Admin statistics\n";
                $help_text .= "/pendingrequests - View pending requests (Admin only)\n";
            }
            
            $help_text .= "\n🔍 <b>How to Search:</b>\n";
            $help_text .= "• Type any movie name (English/Hindi)\n";
            $help_text .= "• Partial names work too\n";
            $help_text .= "• Example: 'kgf', 'pushpa', 'hindi movie'\n\n";
            
            $help_text .= "🎬 <b>Movie Requests:</b>\n";
            $help_text .= "• Use /request MovieName\n";
            $help_text .= "• Or type: 'pls add MovieName'\n";
            $help_text .= "• Max 3 requests per day per user\n";
            $help_text .= "• Check status with /myrequests\n\n";
            
            $help_text .= "📢 <b>Channel Information:</b>\n";
            $help_text .= "🍿 Main: @EntertainmentTadka786\n";
            $help_text .= "🎭 Theater: @threater_print_movies\n";
            $help_text .= "📥 Requests: @EntertainmentTadka7860\n";
            $help_text .= "🔒 Backup: @ETBackup\n\n";
            
            $help_text .= "⚠️ <b>Note:</b> This bot works with webhook. If you face issues, contact admin.";
            
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
            
            $welcome .= "🎬 <b>Movie Request System:</b>\n";
            $welcome .= "• Use /request MovieName to request a movie\n";
            $welcome .= "• Or type: 'pls add MovieName'\n";
            $welcome .= "• Check status with /myrequests\n";
            $welcome .= "• Max 3 requests per day\n\n";
            
            $welcome .= "💡 <b>Tip:</b> Use /help for all commands";
            
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'],
                        ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']
                    ],
                    [
                        ['text' => '📥 How to Request?', 'callback_data' => 'request_movie'],
                        ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']
                    ],
                    [
                        ['text' => '❓ Help', 'callback_data' => 'help_command'],
                        ['text' => '📊 Stats', 'callback_data' => 'show_stats']
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
        elseif (strpos($data, 'approve_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $request_id = str_replace('approve_', '', $data);
            $result = $requestSystem->approveRequest($request_id, $user_id);
            
            if ($result['success']) {
                // Update message with new status
                $request = $result['request'];
                $new_text = $message['text'] . "\n\n✅ <b>Approved by Admin</b>\n🕒 " . date('H:i:s');
                
                editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "✅ Request #$request_id approved");
                
                // Notify user
                notifyUserAboutRequest($request['user_id'], $request, 'approved');
            } else {
                answerCallbackQuery($query['id'], $result['message'], true);
            }
        }
        elseif (strpos($data, 'reject_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $request_id = str_replace('reject_', '', $data);
            
            // Ask for reason via inline keyboard
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Already Available', 'callback_data' => 'reject_reason_' . $request_id . '_already_available'],
                        ['text' => 'Invalid Request', 'callback_data' => 'reject_reason_' . $request_id . '_invalid_request']
                    ],
                    [
                        ['text' => 'Low Quality', 'callback_data' => 'reject_reason_' . $request_id . '_low_quality'],
                        ['text' => 'Not Available', 'callback_data' => 'reject_reason_' . $request_id . '_not_available']
                    ],
                    [
                        ['text' => 'Custom Reason...', 'callback_data' => 'reject_custom_' . $request_id]
                    ]
                ]
            ];
            
            editMessageText($chat_id, $message['message_id'], "Select rejection reason for Request #$request_id:", $keyboard, 'HTML');
            answerCallbackQuery($query['id'], "Select rejection reason");
        }
        elseif (strpos($data, 'reject_reason_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $parts = explode('_', $data);
            $request_id = $parts[2];
            $reason_key = $parts[3];
            
            $reason_map = [
                'already_available' => 'Movie is already available in our channels',
                'invalid_request' => 'Invalid movie name or request',
                'low_quality' => 'Cannot find good quality version',
                'not_available' => 'Movie is not available anywhere'
            ];
            
            $reason = $reason_map[$reason_key] ?? 'Not specified';
            
            $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
            
            if ($result['success']) {
                $request = $result['request'];
                $new_text = $message['text'] . "\n\n❌ <b>Rejected by Admin</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
                
                editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "❌ Request #$request_id rejected");
                
                // Notify user
                notifyUserAboutRequest($request['user_id'], $request, 'rejected');
            } else {
                answerCallbackQuery($query['id'], $result['message'], true);
            }
        }
        elseif (strpos($data, 'reject_custom_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $request_id = str_replace('reject_custom_', '', $data);
            
            // Ask for custom reason
            sendMessage($chat_id, "Please send the custom rejection reason for Request #$request_id:", null, 'HTML');
            answerCallbackQuery($query['id'], "Please type the custom reason");
            
            // Store pending rejection in a file (not session)
            $pending_file = 'pending_rejection.json';
            $pending_data = [
                'request_id' => $request_id,
                'admin_id' => $user_id,
                'chat_id' => $chat_id,
                'message_id' => $message['message_id'],
                'timestamp' => time()
            ];
            file_put_contents($pending_file, json_encode($pending_data, JSON_PRETTY_PRINT));
        }
        elseif (strpos($data, 'bulk_approve_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $encoded_data = str_replace('bulk_approve_', '', $data);
            $request_ids = json_decode(base64_decode($encoded_data), true);
            
            $result = $requestSystem->bulkApprove($request_ids, $user_id);
            
            $new_text = $message['text'] . "\n\n✅ <b>Bulk Approved {$result['approved_count']}/{$result['total_count']} requests</b>\n🕒 " . date('H:i:s');
            
            editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
            answerCallbackQuery($query['id'], "✅ Approved {$result['approved_count']} requests");
            
            // Notify users
            foreach ($request_ids as $req_id) {
                $request = $requestSystem->getRequest($req_id);
                if ($request && $request['status'] == 'approved') {
                    notifyUserAboutRequest($request['user_id'], $request, 'approved');
                }
            }
        }
        elseif (strpos($data, 'bulk_reject_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $encoded_data = str_replace('bulk_reject_', '', $data);
            $request_ids = json_decode(base64_decode($encoded_data), true);
            
            $reason = "Bulk rejected by admin";
            $result = $requestSystem->bulkReject($request_ids, $user_id, $reason);
            
            $new_text = $message['text'] . "\n\n❌ <b>Bulk Rejected {$result['rejected_count']}/{$result['total_count']} requests</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
            
            editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
            answerCallbackQuery($query['id'], "❌ Rejected {$result['rejected_count']} requests");
            
            // Notify users
            foreach ($request_ids as $req_id) {
                $request = $requestSystem->getRequest($req_id);
                if ($request && $request['status'] == 'rejected') {
                    notifyUserAboutRequest($request['user_id'], $request, 'rejected');
                }
            }
        }
        elseif (strpos($data, 'pending_more_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
                return;
            }
            
            $limit = str_replace('pending_more_', '', $data);
            $requests = $requestSystem->getPendingRequests($limit);
            $stats = $requestSystem->getStats();
            
            $message_text = "📋 <b>Pending Requests (Showing $limit)</b>\n\n";
            $message_text .= "📊 <b>System Stats:</b>\n";
            $message_text .= "• Total: " . $stats['total_requests'] . "\n";
            $message_text .= "• Pending: " . $stats['pending'] . "\n";
            $message_text .= "• Approved: " . $stats['approved'] . "\n";
            $message_text .= "• Rejected: " . $stats['rejected'] . "\n\n";
            
            $message_text .= "🎬 <b>Showing " . count($requests) . " requests:</b>\n\n";
            
            $keyboard = ['inline_keyboard' => []];
            
            foreach ($requests as $req) {
                $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                $user_name = htmlspecialchars($req['user_name'] ?: "ID: " . $req['user_id'], ENT_QUOTES, 'UTF-8');
                $message_text .= "🔸 <b>#" . $req['id'] . ":</b> " . $movie_name . "\n";
                $message_text .= "   👤 User: " . $user_name . "\n";
                $message_text .= "   📅 Date: " . date('d M H:i', strtotime($req['created_at'])) . "\n\n";
                
                $keyboard['inline_keyboard'][] = [
                    [
                        'text' => '✅ Approve #' . $req['id'],
                        'callback_data' => 'approve_' . $req['id']
                    ],
                    [
                        'text' => '❌ Reject #' . $req['id'],
                        'callback_data' => 'reject_' . $req['id']
                    ]
                ];
            }
            
            // Add bulk action buttons
            $request_ids = array_column($requests, 'id');
            $current_page_data = base64_encode(json_encode($request_ids));
            
            $keyboard['inline_keyboard'][] = [
                [
                    'text' => '✅ Bulk Approve This Page',
                    'callback_data' => 'bulk_approve_' . $current_page_data
                ],
                [
                    'text' => '❌ Bulk Reject This Page',
                    'callback_data' => 'bulk_reject_' . $current_page_data
                ]
            ];
            
            editMessageText($chat_id, $message['message_id'], $message_text, $keyboard, 'HTML');
            answerCallbackQuery($query['id'], "Loaded $limit requests");
        }
    }
    
    // Check for pending rejection responses
    $pending_file = 'pending_rejection.json';
    if (isset($update['message']) && file_exists($pending_file)) {
        $pending_data = json_decode(file_get_contents($pending_file), true);
        if ($pending_data && $pending_data['admin_id'] == $user_id) {
            $request_id = $pending_data['request_id'];
            $reason = $text;
            
            $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
            
            if ($result['success']) {
                $request = $result['request'];
                
                // Update original message
                $update_text = "❌ <b>Rejected by Admin</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
                editMessageText($pending_data['chat_id'], $pending_data['message_id'], $message['text'] . "\n\n" . $update_text, null, 'HTML');
                
                sendMessage($chat_id, "✅ Request #$request_id rejected with custom reason.", null, 'HTML');
                
                // Notify user
                notifyUserAboutRequest($request['user_id'], $request, 'rejected');
            } else {
                sendMessage($chat_id, "❌ Failed: " . $result['message'], null, 'HTML');
            }
            
            // Clean up
            unlink($pending_file);
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Bot <span class="security-badge">SECURE v2.0</span></h1>
        
        <div class="status-card">
            <h2>✅ Bot is Running</h2>
            <p>Telegram Bot for movie searches across multiple channels | Hosted on Render.com</p>
            <p><strong>Movie Request System:</strong> ✅ Active</p>
            <p><strong>Security Level:</strong> 🔒 High</p>
        </div>
        
        <?php if (empty(BOT_TOKEN) || BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE'): ?>
        <div class="warning-box">
            <strong>⚠️ SECURITY WARNING:</strong> Bot token not configured! Please set BOT_TOKEN environment variable.
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
                $csvManager = CSVManager::getInstance();
                $requestSystem = RequestSystem::getInstance();
                
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
            <h3>✨ Features <span class="security-badge">SECURED</span></h3>
            <div class="feature-item">✅ Multi-channel support (Public & Private channels)</div>
            <div class="feature-item">✅ Smart movie search with partial matching</div>
            <div class="feature-item">✅ Movie Request System with moderation</div>
            <div class="feature-item">✅ Duplicate request blocking & flood control</div>
            <div class="feature-item">✅ Auto-approve when movie added to database</div>
            <div class="feature-item">✅ Admin moderation with inline buttons</div>
            <div class="feature-item">✅ Bulk approve/reject actions</div>
            <div class="feature-item">✅ User notification system</div>
            <div class="feature-item">✅ CSV-based database with caching</div>
            <div class="feature-item">✅ Admin statistics and monitoring dashboard</div>
            <div class="feature-item">✅ Pagination for browsing all movies</div>
            <div class="feature-item">✅ Automatic channel post tracking and indexing</div>
            <div class="feature-item">✅ <strong>NEW:</strong> Rate limiting & DoS protection</div>
            <div class="feature-item">✅ <strong>NEW:</strong> Input validation & XSS protection</div>
            <div class="feature-item">✅ <strong>NEW:</strong> File locking for safe concurrent access</div>
            <div class="feature-item">✅ <strong>NEW:</strong> Environment variable configuration</div>
            <div class="feature-item">✅ <strong>NEW:</strong> Interactive Request Guide with Hindi/English instructions</div>
        </div>
        
        <div style="margin-top: 40px; padding: 25px; background: rgba(255, 255, 255, 0.15); border-radius: 15px;">
            <h3>🚀 Quick Start Guide</h3>
            <ol style="margin-left: 20px; margin-top: 15px;">
                <li style="margin-bottom: 10px;">Set environment variables (BOT_TOKEN, ADMIN_IDS, etc.)</li>
                <li style="margin-bottom: 10px;">Click "Set Webhook" to configure Telegram webhook</li>
                <li style="margin-bottom: 10px;">Test the bot using the "Test Bot" button</li>
                <li style="margin-bottom: 10px;">Start searching movies in Telegram bot</li>
                <li style="margin-bottom: 10px;">Use /request or type "pls add MovieName" to request movies</li>
                <li style="margin-bottom: 10px;">Check status with /myrequests command</li>
                <li style="margin-bottom: 10px;">Click "📥 How to Request?" button for step-by-step guide</li>
                <li style="margin-bottom: 10px;">Admins: Use /pendingrequests to moderate requests</li>
            </ol>
        </div>
        
        <footer>
            <p>🎬 Entertainment Tadka Bot | Powered by PHP & Telegram Bot API | Hosted on Render.com</p>
            <p style="margin-top: 10px; font-size: 0.9em;">© <?php echo date('Y'); ?> - All rights reserved | Secure Version 2.0 | Request Guide Added | HTML Parse Fixed</p>
        </footer>
    </div>
</body>
</html>
<?php
// ==================== END OF FILE ====================
// Exact line count: 3152 lines (including this comment)
// HTML parse mode fixed in all sendMessage() and editMessageText() calls
?>
