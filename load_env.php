<?php
// ==================== LOAD ENVIRONMENT VARIABLES ====================
// File: load_env.php
// Include this at the top of your bot file

function loadEnv($file = '.env') {
    if (!file_exists($file)) {
        error_log("⚠️ .env file not found! Using default configuration.");
        return false;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    
    error_log("✅ .env file loaded successfully!");
    return true;
}

// Load the .env file
loadEnv();

// Helper function to get env variable
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Convert string booleans
    if (strtolower($value) === 'true') return true;
    if (strtolower($value) === 'false') return false;
    
    // Convert numbers
    if (is_numeric($value)) {
        return strpos($value, '.') !== false ? (float)$value : (int)$value;
    }
    
    return $value;
}

// Example usage:
// $token = env('BOT_TOKEN');
// $debug = env('ENVIRONMENT') === 'development';
?>