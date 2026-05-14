<?php
// api/middleware.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

function sendUnauthorized($message = 'Unauthorized')
{
    http_response_code(401);
    echo json_encode(['error' => $message]);
    exit;
}

function sendRateLimitExceeded()
{
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again later.']);
    exit;
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$headers = getallheaders();
// Normalize header keys to lowercase for consistent lookup
$normalizedHeaders = array_change_key_case($headers, CASE_LOWER);

$apiKey = isset($normalizedHeaders['x-api-key']) ? $normalizedHeaders['x-api-key'] : null;
$sessionToken = isset($normalizedHeaders['x-session-token']) ? $normalizedHeaders['x-session-token'] : null;

$identifier = null;

if ($apiKey) {
    // Validate API Key
    try {
        $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$apiKey]);
        $keyData = $stmt->fetch();

        if (!$keyData) {
            sendUnauthorized('Invalid API Key');
        }
        $identifier = 'api_key_' . $apiKey;
    } catch (PDOException $e) {
        sendUnauthorized('Internal server error during validation');
    }
} elseif ($sessionToken) {
    // Validate Session Token
    if (empty($_SESSION['app_token']) || !hash_equals($_SESSION['app_token'], $sessionToken)) {
        sendUnauthorized('Invalid Session Token');
    }
    $identifier = 'session_' . $sessionToken;
} else {
    sendUnauthorized('Missing Authentication Token');
}

// RATE LIMITING
// Configuration
$rateLimitWindow = 60; // seconds (1 minute)
$maxRequests = 100;    // allowed requests per window

$endpoint = basename($_SERVER['SCRIPT_NAME']);
$currentTime = time();

try {
    // Delete expired rate limits to keep the table clean
    $pdo->exec("DELETE FROM api_rate_limits WHERE reset_time < " . $currentTime);

    // Check existing rate limit
    $stmt = $pdo->prepare("SELECT id, request_count, reset_time FROM api_rate_limits WHERE identifier = ? AND endpoint = ?");
    $stmt->execute([$identifier, $endpoint]);
    $limitData = $stmt->fetch();

    if ($limitData) {
        if ($limitData['request_count'] >= $maxRequests) {
            // Exceeded limit
            sendRateLimitExceeded();
        } else {
            // Increment count
            $stmt = $pdo->prepare("UPDATE api_rate_limits SET request_count = request_count + 1 WHERE id = ?");
            $stmt->execute([$limitData['id']]);
        }
    } else {
        // Insert new rate limit record
        $resetTime = $currentTime + $rateLimitWindow;
        $stmt = $pdo->prepare("INSERT INTO api_rate_limits (identifier, endpoint, request_count, reset_time) VALUES (?, ?, 1, ?)");
        $stmt->execute([$identifier, $endpoint, $resetTime]);
    }
} catch (PDOException $e) {
    // Fail open or closed? Better to fail closed for security, but we don't want to break the app if the rate limit table fails.
    // We will log and proceed to avoid total outage if DB is acting up (but API key check already worked).
    error_log("Rate limiting error: " . $e->getMessage());
}
?>