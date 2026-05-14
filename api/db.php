<?php
// Set session garbage collection lifetime to 30 days
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(0); // Default to session cookie, will be overridden in login.php if requested

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================
// SECURITY HEADERS
// =========================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// =========================================================
// AUTHORIZATION & RATE LIMITING
// =========================================================

function validateAuth()
{
    $token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
    if (empty($token) || !isset($_SESSION['api_token']) || $token !== $_SESSION['api_token']) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized access.']);
        exit;
    }
}

function checkRateLimit($endpoint, $limit = 60, $seconds = 60)
{
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];

    try {
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint, request_count, last_request) 
                               VALUES (?, ?, 1, CURRENT_TIMESTAMP)
                               ON DUPLICATE KEY UPDATE 
                               request_count = IF(TIMESTAMPDIFF(SECOND, last_request, CURRENT_TIMESTAMP) > ?, 1, request_count + 1),
                               last_request = IF(TIMESTAMPDIFF(SECOND, last_request, CURRENT_TIMESTAMP) > ?, CURRENT_TIMESTAMP, last_request)");
        $stmt->execute([$ip, $endpoint, $seconds, $seconds]);

        $stmt = $pdo->prepare("SELECT request_count FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
        $stmt->execute([$ip, $endpoint]);
        $row = $stmt->fetch();

        if ($row && $row['request_count'] > $limit) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please try again later.']);
            exit;
        }
    } catch (PDOException $e) {
        // Log error and continue if rate limit table fails, don't block user
        error_log("Rate limit error: " . $e->getMessage());
    }
}

// =========================================================
// GLOBAL ERROR HANDLING
// =========================================================
define('APP_DEBUG', true); // SET TO FALSE IN PRODUCTION

function jsonErrorHandler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity))
        return;
    throw new ErrorException($message, 0, $severity, $file, $line);
}

function jsonExceptionHandler($exception)
{
    // If headers already sent, we can't set status code but we can still output JSON
    if (!headers_sent()) {
        http_response_code(500);
    }

    $response = ['error' => 'Internal Server Error'];

    if (APP_DEBUG) {
        $response['message'] = $exception->getMessage();
        $response['file'] = $exception->getFile();
        $response['line'] = $exception->getLine();
    } else {
        error_log("TrikeFare API Error: " . $exception->getMessage());
    }

    echo json_encode($response);
    exit;
}

set_error_handler("jsonErrorHandler");
set_exception_handler("jsonExceptionHandler");

// Enable error display internally
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : 0);

// =========================================================
// DATABASE CONNECTION
// =========================================================
date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$dbname = 'trikefare_db';
$user = 'root';
$pass = 'Gwapokoko123-';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Ensure MySQL session is in Asia/Manila (+08:00)
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    throw new Exception('Database connection failed' . (APP_DEBUG ? ': ' . $e->getMessage() : ''));
}
?>