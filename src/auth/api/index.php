<?php
/**
 * Authentication Handler for Login Form
 *
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Helper: JSON response ---
function sendJson($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// --- Set Response Headers ---
header('Content-Type: application/json; charset=utf-8');

// (اختياري) لو بتشتغل من دومين ثاني
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight للـ OPTIONS (لو تستخدم CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson([
        'success' => false,
        'message' => 'Invalid request method. POST required.'
    ], 405);
}

// --- Get POST Data ---
$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);

// لو الـ JSON مو صحيح
if (!is_array($data)) {
    sendJson([
        'success' => false,
        'message' => 'Invalid JSON payload.'
    ], 400);
}

// --- Extract email & password ---
if (!isset($data['email'], $data['password'])) {
    sendJson([
        'success' => false,
        'message' => 'Email and password are required.'
    ], 400);
}

$email    = trim((string) $data['email']);
$password = (string) $data['password'];

// --- Server-side validation ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJson([
        'success' => false,
        'message' => 'Invalid email format.'
    ], 400);
}

if (strlen($password) < 8) {
    sendJson([
        'success' => false,
        'message' => 'Password must be at least 8 characters.'
    ], 400);
}

// --- Database Connection ---
require_once __DIR__ . '/config.php'; // عدّل المسار لو ملفك في مكان ثاني

try {
    // نفترض أن عندك دالة getDBConnection() ترجع PDO
    $db = getDBConnection();

    // --- Prepare SQL Query ---
    $sql = 'SELECT id, name, email, password 
            FROM users
            WHERE email = :email
            LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
        // Successful login

        // Store safe info in session
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in']  = true;

        // (اختياري) لو عندك عمود role في الجدول
        if (isset($user['role'])) {
            $_SESSION['role'] = $user['role'];
        }

        sendJson([
            'success' => true,
            'message' => 'Login successful',
            'user'    => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email']
            ]
        ], 200);
    }

    // --- Failed Authentication ---
    sendJson([
        'success' => false,
        'message' => 'Invalid email or password'
    ], 401);

} catch (PDOException $e) {
    // Log error on server
    error_log('PDO Error in login handler: ' . $e->getMessage());

    // Generic message to client
    sendJson([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ], 500);
} catch (Exception $e) {
    error_log('General Error in login handler: ' . $e->getMessage());

    sendJson([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ], 500);
}

// --- End of Script ---
?>
