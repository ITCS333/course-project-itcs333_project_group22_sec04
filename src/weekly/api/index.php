<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json; charset=utf-8');

$allowedMethods = 'GET, POST, PUT, DELETE, OPTIONS';
$allowedHeaders = 'Content-Type, Authorization';

// Optional: restrict origins using a whitelist
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://yourdomain.com'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
    header('Access-Control-Allow-Credentials: true');
    }

    // Methods and headers allowed in CORS requests
    header('Access-Control-Allow-Methods: ' . $allowedMethods);
    header('Access-Control-Allow-Headers: ' . $allowedHeaders);
    header('Access-Control-Max-Age: 86400'); 

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    echo json_encode(['status' => 'ok']);


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// HTTP method


// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()

// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$rawBody = file_get_contents('php://input');
$bodyData = [];
if ($rawBody !== false && $rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $bodyData = $decoded;
    }
}
$resource = isset($_GET['resource']) ? strtolower(trim($_GET['resource'])) : 'weeks';

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // Initialize variables
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort   = isset($_GET['sort']) ? strtolower($_GET['sort']) : 'start_date';
    $order  = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';

    // Validate sort and order
    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }
    $order = ($order === 'desc') ? 'DESC' : 'ASC';

    // Build query
    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $params = [];

    if ($search !== '') {
        $query .= " WHERE title LIKE ? OR description LIKE ?";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $query .= " ORDER BY $sort $order";

    try {
        // Prepare and execute
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        // Fetch results
        $weeks = $stmt->fetchAll();

        // Decode links JSON
        foreach ($weeks as &$week) {
            $week['links'] = json_decode($week['links'], true) ?? [];
        }

        // Return response
        sendResponse(200, ['success' => true, 'data' => $weeks]);
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Failed to retrieve weeks']);
    }
}

/**
 * Helper: Send JSON response
 */
function sendResponse($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId || trim($weekId) === '') {
        sendResponse(400, ['success' => false, 'error' => 'Missing or invalid week_id']);
         return;
    }
    
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?";
    
    // TODO: Bind the week_id parameter
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([$weekId]);  
    
    // TODO: Execute the query
     $week = $stmt->fetch();
    // TODO: Fetch the result
    
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if ($week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
        sendResponse(200, ['success' => true, 'data' => $week]);
         } else {
            sendResponse(404, ['success' => false, 'error' => 'Week not found']);
         }
         } catch (PDOException $e) {
            sendResponse(500, ['success' => false, 'error' => 'Failed to retrieve week']);
         }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            sendResponse(400, ['success' => false, 'error' => "Missing or invalid field: $field"]);
            return;
        }
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $weekId     = trim($data['week_id']);
    $title      = trim($data['title']);
    $startDate  = trim($data['start_date']);
    $description= trim($data['description']);

    
    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
         $dateObj = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $startDate) {
        sendResponse(400, ['success' => false, 'error' => 'Invalid start_date format. Use YYYY-MM-DD']);
        return;
    }
    
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    try {
        $checkQuery = "SELECT id FROM weeks WHERE week_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$weekId]);
        if ($checkStmt->fetch()) {
            sendResponse(409, ['success' => false, 'error' => 'Week ID already exists']);
            return;
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during duplicate check']);
        return;
    }
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
     $insertQuery = "INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)";
    
    // TODO: Bind parameters
    
    // TODO: Execute the query
    try {
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$weekId, $title, $startDate, $description, $links]);

        // Return success response
        $newWeek = [
            'week_id'    => $weekId,
            'title'      => $title,
            'start_date' => $startDate,
            'description'=> $description,
            'links'      => json_decode($links, true),
        ];
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    sendResponse(201, ['success' => true, 'data' => $newWeek]);
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Failed to create week']);
    }
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!isset($data['week_id']) || trim($data['week_id']) === '') {
        sendResponse(400, ['success' => false, 'error' => 'Missing or invalid week_id']);
        return;
    }

    $weekId = trim($data['week_id']);
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    try {
        $checkStmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
        $checkStmt->execute([$weekId]);
        if (!$checkStmt->fetch()) {
            sendResponse(404, ['success' => false, 'error' => 'Week not found']);
            return;
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during lookup']);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $values = [];

    
    
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    if (isset($data['title'])) {
        $setClauses[] = "title = ?";
        $values[] = trim($data['title']);
    }
    // If start_date is provided, validate format and add "start_date = ?"
    if (isset($data['start_date'])) {
        $startDate = trim($data['start_date']);
        $dateObj = DateTime::createFromFormat('Y-m-d', $startDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $startDate) {
            sendResponse(400, ['success' => false, 'error' => 'Invalid start_date format. Use YYYY-MM-DD']);
            return;
        }
        $setClauses[] = "start_date = ?";
        $values[] = $startDate;
    }
    // If description is provided, add "description = ?"
     if (isset($data['description'])) {
        $setClauses[] = "description = ?";
        $values[] = trim($data['description']);
    }
    // If links is provided, encode to JSON and add "links = ?"
    if (isset($data['links'])) {
        $encodedLinks = is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
        $setClauses[] = "links = ?";
        $values[] = $encodedLinks;
    }
    
    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendResponse(400, ['success' => false, 'error' => 'No fields provided for update']);
        return;
    }
    
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $query = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE week_id = ?";
    $values[] = $weekId;
    
    // TODO: Prepare the query
    
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    
    // TODO: Execute the query
    
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($values);

        // Return updated data
        $getStmt = $db->prepare("SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE week_id = ?");
        $getStmt->execute([$weekId]);
        $updatedWeek = $getStmt->fetch();

        if ($updatedWeek) {
            $updatedWeek['links'] = json_decode($updatedWeek['links'], true) ?? [];
            sendResponse(200, ['success' => true, 'data' => $updatedWeek]);
        } else {
            sendResponse(500, ['success' => false, 'error' => 'Failed to retrieve updated week']);
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Failed to update week']);
    }
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId || trim($weekId) === '') {
        sendResponse(400, ['success' => false, 'error' => 'Missing or invalid week_id']);
        return;
    }
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    try {
         $checkStmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
        $checkStmt->execute([$weekId]);
        if (!$checkStmt->fetch()) {
            sendResponse(404, ['success' => false, 'error' => 'Week not found']);
            return;
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during lookup']);
        return;
    }
    
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    try {
        $deleteCommentsStmt = $db->prepare("DELETE FROM comments WHERE week_id = ?");
        $deleteCommentsStmt->execute([$weekId]);

    
    // TODO: Execute comment deletion query

    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $deleteWeekStmt = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
        $deleteWeekStmt->execute([$weekId]);
    
    // TODO: Bind the week_id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if ($deleteWeekStmt->rowCount() > 0) {
            sendResponse(200, ['success' => true, 'message' => 'Week and associated comments deleted']);
        } else {
            sendResponse(500, ['success' => false, 'error' => 'Failed to delete week']);
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during deletion']);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId || trim($weekId) === '') {
        sendResponse(400, ['success' => false, 'error' => 'Missing or invalid week_id']);
        return;
    }
    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $query = "SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC";
    
    // TODO: Bind the week_id parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    try {
        // Prepare and execute the query
        $stmt = $db->prepare($query);
        $stmt->execute([$weekId]);

        // Fetch all results as an associative array
        $comments = $stmt->fetchAll();

        // Return JSON response with success status and data
        sendResponse(200, ['success' => true, 'data' => $comments]);
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Failed to retrieve comments']);
    }
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            sendResponse(400, ['success' => false, 'error' => "Missing or invalid field: $field"]);
            return;
        }
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = trim($data['week_id']);
    $author = trim($data['author']);
    $text   = trim($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if ($text === '') {
        sendResponse(400, ['success' => false, 'error' => 'Comment text cannot be empty']);
        return;
    }
    
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
     try {
        $checkStmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
        $checkStmt->execute([$weekId]);
        if (!$checkStmt->fetch()) {
            sendResponse(404, ['success' => false, 'error' => 'Week not found']);
            return;
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during week lookup']);
        return;
    }
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
     $insertQuery = "INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)";
    
    // TODO: Bind parameters
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
    try {
        $stmt = $db->prepare($insertQuery);
        $stmt->execute([$weekId, $author, $text]);

        // Get the last inserted comment
        $commentId = $db->lastInsertId();
        $getStmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments WHERE id = ?");
        $getStmt->execute([$commentId]);
        $newComment = $getStmt->fetch();

        sendResponse(201, ['success' => true, 'data' => $newComment]);
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Failed to create comment']);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse(400, ['success' => false, 'error' => 'Missing or invalid comment ID']);
        return;
    }
    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
     try {
        $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
        $checkStmt->execute([$commentId]);
        if (!$checkStmt->fetch()) {
            sendResponse(404, ['success' => false, 'error' => 'Comment not found']);
            return;
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during lookup']);
        return;
    }
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?

    
    // TODO: Bind the id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    try {
        $deleteStmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $deleteStmt->execute([$commentId]);

        if ($deleteStmt->rowCount() > 0) {
            sendResponse(200, ['success' => true, 'message' => 'Comment deleted successfully']);
        } else {
            sendResponse(500, ['success' => false, 'error' => 'Failed to delete comment']);
        }
    } catch (PDOException $e) {
        sendResponse(500, ['success' => false, 'error' => 'Database error during deletion']);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource = isset($_GET['resource']) ? strtolower($_GET['resource']) : 'weeks';
    $method = $_SERVER['REQUEST_METHOD'];

    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
             $weekId = isset($_GET['week_id']) ? trim($_GET['week_id']) : '';
            if ($weekId !== '') {
                getWeekById($db, $weekId);
            } else {
                getAllWeeks($db);
            }
            
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
             createWeek($db, $data);
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db, $data);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            $weekId = isset($_GET['week_id']) ? trim($_GET['week_id']) : ($data['week_id'] ?? '');
            deleteWeek($db, $weekId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
             $weekId = isset($_GET['week_id']) ? trim($_GET['week_id']) : '';
        getCommentsByWeek($db, $weekId);
            
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $data);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $commentId = isset($_GET['id']) ? trim($_GET['id']) : ($data['id'] ?? '');
            deleteComment($db, $commentId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => "Invalid resource. Use 'weeks' or 'comments'"
    ]);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred'
    ]);
} 
    



// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $error = [
        'success' => false,
        'error' => $message
    ];
    
    // TODO: Call sendResponse() with the error array and status code
    sendResponse($error, $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields, true);
}

?>
