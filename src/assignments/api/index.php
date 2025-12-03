<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json; charset=utf-8');

// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once __DIR__ . '/Database.php'; // Make sure this file exists and defines Database class

// TODO: Create database connection
$database = new Database();
$db = $database->getConnection();

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);;


// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$rawInput = file_get_contents('php://input');
$data = null;
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $decoded = json_decode($rawInput, true);
    $data = is_array($decoded) ? $decoded : [];
}

// TODO: Parse query parameters
$queryParams = $_GET;


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments WHERE 1=1";
    $params = [];

    // TODO: Check if 'search' query parameter exists in $_GET
    if (!empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    // TODO: Check if 'sort' and 'order' query parameters exist
    $allowedSortFields = ['title', 'due_date', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    $sort = !empty($_GET['sort']) && validateAllowedValue($_GET['sort'], $allowedSortFields)
        ? $_GET['sort'] : 'created_at';
    $order = !empty($_GET['order']) && validateAllowedValue(strtolower($_GET['order']), $allowedOrder)
        ? strtolower($_GET['order']) : 'asc';

    $sql .= " ORDER BY {$sort} {$order}";

    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if search is used
    if (isset($params[':search'])) {
        $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
    }

    // TODO: Execute the prepared statement
    $stmt->execute();

    // TODO: Fetch all results as associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($results as &$assignment) {
        if (!empty($assignment['files'])) {
            $decodedFiles = json_decode($assignment['files'], true);
            $assignment['files'] = is_array($decodedFiles) ? $decodedFiles : [];
        } else {
            $assignment['files'] = [];
        }
    }

    // TODO: Return JSON response
    sendResponse($results);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
    }

    // TODO: Prepare SQL query to select assignment by id
    $sql = "SELECT * FROM assignments WHERE id = :id";

    // TODO: Bind the :id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if assignment was found
    if (!$assignment) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }

    // TODO: Decode the 'files' field from JSON to array
    if (!empty($assignment['files'])) {
        $decodedFiles = json_decode($assignment['files'], true);
        $assignment['files'] = is_array($decodedFiles) ? $decodedFiles : [];
    } else {
        $assignment['files'] = [];
    }

    // TODO: Return success response with assignment data
    sendResponse($assignment);
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(['error' => 'title, description, and due_date are required'], 400);
    }

    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $due_date = $data['due_date'];

    // TODO: Validate due_date format
    if (!validateDate($due_date)) {
        sendResponse(['error' => 'Invalid due_date format. Expected YYYY-MM-DD'], 400);
    }

    // TODO: Generate a unique assignment ID
    // Using AUTO_INCREMENT id from the database, so no manual ID generation here.

    // TODO: Handle the 'files' field
    $filesJson = '[]';
    if (isset($data['files']) && is_array($data['files'])) {
        $filesJson = json_encode($data['files']);
    }

    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";

    // TODO: Bind all parameters
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':due_date', $due_date, PDO::PARAM_STR);
    $stmt->bindValue(':files', $filesJson, PDO::PARAM_STR);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Check if insert was successful
    if ($stmt->rowCount() === 0) {
        // TODO: If insert failed, return 500 error
        sendResponse(['error' => 'Failed to create assignment'], 500);
    }

    $newId = $db->lastInsertId();
    $created = [
        'id' => (int)$newId,
        'title' => $title,
        'description' => $description,
        'due_date' => $due_date,
        'files' => json_decode($filesJson, true),
    ];

    sendResponse(['success' => true, 'assignment' => $created], 201);
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if (empty($data['id'])) {
        sendResponse(['error' => 'Assignment ID (id) is required for update'], 400);
    }

    // TODO: Store assignment ID in variable
    $id = (int)$data['id'];

    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    $fields = [];
    $params = [':id' => $id];

    // TODO: Check which fields are provided and add to SET clause
    if (isset($data['title'])) {
        $fields[] = 'title = :title';
        $params[':title'] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $fields[] = 'description = :description';
        $params[':description'] = sanitizeInput($data['description']);
    }
    if (isset($data['due_date'])) {
        if (!validateDate($data['due_date'])) {
            sendResponse(['error' => 'Invalid due_date format. Expected YYYY-MM-DD'], 400);
        }
        $fields[] = 'due_date = :due_date';
        $params[':due_date'] = $data['due_date'];
    }
    if (isset($data['files'])) {
        if (!is_array($data['files'])) {
            sendResponse(['error' => 'files must be an array'], 400);
        }
        $fields[] = 'files = :files';
        $params[':files'] = json_encode($data['files']);
    }

    // TODO: If no fields to update (besides updated_at), return 400 error
    if (empty($fields)) {
        sendResponse(['error' => 'No fields provided to update'], 400);
    }

    // TODO: Complete the UPDATE query
    $fields[] = 'updated_at = NOW()';
    $setClause = implode(', ', $fields);
    $sql = "UPDATE assignments SET $setClause WHERE id = :id";

    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);

    // TODO: Bind all parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Check if update was successful
    if ($stmt->rowCount() === 0) {
        // TODO: If no rows affected, return appropriate message
        sendResponse(['success' => true, 'message' => 'No changes made'], 200);
    }

    sendResponse(['success' => true, 'message' => 'Assignment updated successfully'], 200);
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
    }

    $assignmentId = (int)$assignmentId;

    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }

    // TODO: Delete associated comments first (due to foreign key constraint)
    $deleteComments = $db->prepare("DELETE FROM comments WHERE assignment_id = :assignment_id");
    $deleteComments->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $deleteComments->execute();

    // TODO: Prepare DELETE query for assignment
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");

    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Check if delete was successful
    if ($stmt->rowCount() === 0) {
        // TODO: If delete failed, return 500 error
        sendResponse(['error' => 'Failed to delete assignment'], 500);
    }

    sendResponse(['success' => true, 'message' => 'Assignment deleted successfully'], 200);
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'assignment_id is required'], 400);
    }

    $assignmentId = (int)$assignmentId;

    // TODO: Prepare SQL query to select all comments for the assignment
    $sql = "SELECT * FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at ASC";

    // TODO: Bind the :assignment_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch all results as associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    sendResponse($comments);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['error' => 'assignment_id, author, and text are required'], 400);
    }

    // TODO: Sanitize input data
    $assignment_id = (int)$data['assignment_id'];
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    // TODO: Validate that text is not empty after trimming
    if (trim($text) === '') {
        sendResponse(['error' => 'Comment text cannot be empty'], 400);
    }

    // TODO: Verify that the assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignment_id, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }

    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments (assignment_id, author, text, created_at)
            VALUES (:assignment_id, :author, :text, NOW())";

    // TODO: Bind all parameters
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':assignment_id', $assignment_id, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author, PDO::PARAM_STR);
    $stmt->bindValue(':text', $text, PDO::PARAM_STR);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Get the ID of the inserted comment
    $newId = $db->lastInsertId();

    // TODO: Return success response with created comment data
    $created = [
        'id' => (int)$newId,
        'assignment_id' => $assignment_id,
        'author' => $author,
        'text' => $text,
    ];

    sendResponse(['success' => true, 'comment' => $created], 201);
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        sendResponse(['error' => 'Comment ID is required'], 400);
    }

    $commentId = (int)$commentId;

    // TODO: Check if comment exists
    $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $checkStmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Comment not found'], 404);
    }

    // TODO: Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");

    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Check if delete was successful
    if ($stmt->rowCount() === 0) {
        // TODO: If delete failed, return 500 error
        sendResponse(['error' => 'Failed to delete comment'], 500);
    }

    sendResponse(['success' => true, 'message' => 'Comment deleted successfully'], 200);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    $resource = isset($queryParams['resource']) ? $queryParams['resource'] : null;

    if (!$resource) {
        sendResponse(['error' => 'Resource parameter is required'], 400);
    }

    // TODO: Route based on HTTP method and resource type
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if (isset($queryParams['id'])) {
                getAssignmentById($db, $queryParams['id']);
            } else {
                getAllAssignments($db);
            }
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            if (isset($queryParams['assignment_id'])) {
                getCommentsByAssignment($db, $queryParams['assignment_id']);
            } else {
                sendResponse(['error' => 'assignment_id query parameter is required'], 400);
            }
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db, $data);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db, $data);
        } else {
            // TODO: PUT not supported for other resources
            sendResponse(['error' => 'PUT not supported for this resource'], 405);
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            $assignmentId = $queryParams['id'] ?? ($data['id'] ?? null);
            deleteAssignment($db, $assignmentId);
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            $commentId = $queryParams['id'] ?? null;
            deleteComment($db, $commentId);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } else {
        // TODO: Method not supported
        sendResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    // TODO: Handle general errors
    sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    $result = in_array($value, $allowedValues, true);
    
    // TODO: Return the result
    return $result;
}

?>
