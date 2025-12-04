<?php
/**
 * Student Management API
 * 
 * RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 *
 * Table: students
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE)
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255))  -- hashed
 *   - created_at (TIMESTAMP)
 */

/* -------------------------------------------------
   Headers: JSON output + CORS
------------------------------------------------- */

// NOTE: All responses are JSON.
header('Content-Type: application/json; charset=utf-8');

// NOTE: Basic CORS setup. For local development this is usually enough.
// If your instructor gave specific origin, replace * with that origin.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* -------------------------------------------------
   Include database connection
------------------------------------------------- */

// NOTE: Adjust the path if your Database.php file is in a different folder.
require_once __DIR__ . '/Database.php';

$database = new Database();
$db       = $database->getConnection();

/* -------------------------------------------------
   Read request info (method + body + query)
------------------------------------------------- */

$method = $_SERVER['REQUEST_METHOD'];

// Raw body (for POST, PUT, DELETE that send JSON)
$rawInput = file_get_contents('php://input');
$inputData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}

// Query parameters
$queryParams = $_GET;


/* -------------------------------------------------
   MAIN FUNCTIONS
------------------------------------------------- */

/**
 * Get all students or search/filter.
 */
function getStudents(PDO $db, array $queryParams)
{
    $search = $queryParams['search'] ?? null;
    $sort   = $queryParams['sort']   ?? null;
    $order  = $queryParams['order']  ?? null;

    // Base query (we never return the password hash)
    $sql = "SELECT id, student_id, name, email, created_at FROM students WHERE 1=1";
    $params = [];

    // Search on name, student_id, email
    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR student_id LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    // Sorting
    $allowedSortFields = ['name', 'student_id', 'email', 'created_at'];
    $allowedOrder      = ['asc', 'desc'];

    $sort  = in_array($sort,  $allowedSortFields, true) ? $sort  : 'name';
    $order = in_array(strtolower($order), $allowedOrder, true) ? strtolower($order) : 'asc';

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $students
    ]);
}

/**
 * Get a single student by student_id.
 */
function getStudentById(PDO $db, string $studentId)
{
    $sql = "SELECT id, student_id, name, email, created_at 
            FROM students 
            WHERE student_id = :student_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        sendResponse([
            'success' => true,
            'data'    => $student
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }
}

/**
 * Create a new student (Admin creates account for a student).
 */
function createStudent(PDO $db, array $data)
{
    // Required fields
    $student_id = sanitizeInput($data['student_id'] ?? '');
    $name       = sanitizeInput($data['name']       ?? '');
    $email      = sanitizeInput($data['email']      ?? '');
    $password   = $data['password']                ?? '';

    if ($student_id === '' || $name === '' || $email === '' || $password === '') {
        sendResponse([
            'success' => false,
            'message' => 'student_id, name, email and password are required'
        ], 400);
    }

    if (!validateEmail($email)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid email format'
        ], 400);
    }

    // Check duplicates (student_id or email)
    $checkSql = "SELECT id FROM students 
                 WHERE student_id = :student_id OR email = :email";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([
        ':student_id' => $student_id,
        ':email'      => $email
    ]);

    if ($checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Student with this ID or email already exists'
        ], 409); // Conflict
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertSql = "INSERT INTO students (student_id, name, email, password)
                  VALUES (:student_id, :name, :email, :password)";
    $insertStmt = $db->prepare($insertSql);
    $ok = $insertStmt->execute([
        ':student_id' => $student_id,
        ':name'       => $name,
        ':email'      => $email,
        ':password'   => $passwordHash
    ]);

    if ($ok) {
        sendResponse([
            'success' => true,
            'message' => 'Student created successfully'
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create student'
        ], 500);
    }
}

/**
 * Update an existing student (name/email).
 */
function updateStudent(PDO $db, array $data)
{
    $student_id = sanitizeInput($data['student_id'] ?? '');

    if ($student_id === '') {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required for update'
        ], 400);
    }

    // Check student exists
    $check = $db->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $check->execute([':student_id' => $student_id]);
    $student = $check->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $fields = [];
    $params = [':student_id' => $student_id];

    // Optional name
    if (isset($data['name'])) {
        $name = sanitizeInput($data['name']);
        $fields[] = "name = :name";
        $params[':name'] = $name;
    }

    // Optional email
    if (isset($data['email'])) {
        $email = sanitizeInput($data['email']);
        if (!validateEmail($email)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid email format'
            ], 400);
        }

        // Check duplicate email (exclude current student)
        $checkEmail = $db->prepare(
            "SELECT id FROM students WHERE email = :email AND student_id <> :student_id"
        );
        $checkEmail->execute([
            ':email'      => $email,
            ':student_id' => $student_id
        ]);

        if ($checkEmail->fetch()) {
            sendResponse([
                'success' => false,
                'message' => 'Another student already uses this email'
            ], 409);
        }

        $fields[] = "email = :email";
        $params[':email'] = $email;
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'Nothing to update'
        ], 400);
    }

    $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE student_id = :student_id";
    $stmt = $db->prepare($sql);
    $ok = $stmt->execute($params);

    if ($ok) {
        sendResponse([
            'success' => true,
            'message' => 'Student updated successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update student'
        ], 500);
    }
}

/**
 * Delete a student by student_id.
 */
function deleteStudent(PDO $db, ?string $studentId)
{
    if (!$studentId) {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required for delete'
        ], 400);
    }

    // Check exists
    $check = $db->prepare("SELECT id FROM students WHERE student_id = :student_id");
    $check->execute([':student_id' => $studentId]);
    if (!$check->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $del = $db->prepare("DELETE FROM students WHERE student_id = :student_id");
    $ok  = $del->execute([':student_id' => $studentId]);

    if ($ok) {
        sendResponse([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete student'
        ], 500);
    }
}

/**
 * Change password for a student.
 * POST with action=change_password
 */
function changePassword(PDO $db, array $data)
{
    $student_id       = sanitizeInput($data['student_id']       ?? '');
    $current_password = $data['current_password']               ?? '';
    $new_password     = $data['new_password']                   ?? '';

    if ($student_id === '' || $current_password === '' || $new_password === '') {
        sendResponse([
            'success' => false,
            'message' => 'student_id, current_password and new_password are required'
        ], 400);
    }

    if (strlen($new_password) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'New password must be at least 8 characters'
        ], 400);
    }

    // Get current password hash
    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    if (!password_verify($current_password, $row['password'])) {
        sendResponse([
            'success' => false,
            'message' => 'Current password is incorrect'
        ], 401);
    }

    $newHash = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $db->prepare("UPDATE students SET password = :password WHERE student_id = :student_id");
    $ok = $update->execute([
        ':password'   => $newHash,
        ':student_id' => $student_id
    ]);

    if ($ok) {
        sendResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to change password'
        ], 500);
    }
}

/* -------------------------------------------------
   REQUEST ROUTER
------------------------------------------------- */

try {
    if ($method === 'GET') {

        // /students?student_id=...
        if (!empty($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db, $queryParams);
        }

    } elseif ($method === 'POST') {

        // /students?action=change_password
        if (!empty($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($db, $inputData);
        } else {
            createStudent($db, $inputData);
        }

    } elseif ($method === 'PUT') {

        updateStudent($db, $inputData);

    } elseif ($method === 'DELETE') {

        // student_id from query or body
        $studentId = $queryParams['student_id'] ?? ($inputData['student_id'] ?? null);
        deleteStudent($db, $studentId);

    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed'
        ], 405);
    }

} catch (PDOException $e) {
    // NOTE: In real apps we would log this error.
    sendResponse([
        'success' => false,
        'message' => 'Database error'
    ], 500);

} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Unexpected server error'
    ], 500);
}


/* -------------------------------------------------
   HELPER FUNCTIONS
------------------------------------------------- */

/**
 * Helper: send JSON response and stop execution.
 */
function sendResponse($data, int $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Helper: validate email format.
 */
function validateEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Helper: sanitize string input.
 */
function sanitizeInput(string $data): string
{
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>
