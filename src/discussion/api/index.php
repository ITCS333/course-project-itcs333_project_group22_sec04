<?php
session_start();
$_SESSION['initialized'] = true;
/**
 * Discussion Board API
 * RESTful API for topics & replies using PDO + JSON.
 */

// ---------------------------------------------------------------------
// HEADERS & CORS
// ---------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
// لو تحتاج CORS فعلاً، تقدر تفعل هذي:
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ---------------------------------------------------------------------
// INCLUDE DATABASE & INIT
// ---------------------------------------------------------------------
require_once __DIR__ . '/Database.php'; // عدّل المسار حسب مشروعك

$database = new Database();
$db       = $database->getConnection();

// HTTP method & input
$method    = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawBody   = file_get_contents('php://input');
$bodyData  = json_decode($rawBody, true) ?? [];

// Query params
$resource  = isset($_GET['resource']) ? $_GET['resource'] : null;

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Get all topics or search
 */
function getAllTopics(PDO $db)
{
    $sql    = "SELECT topic_id, subject, message, author,
                      DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
               FROM topics";
    $params = [];

    // Search
    if (!empty($_GET['search'])) {
        $search       = '%' . $_GET['search'] . '%';
        $sql         .= " WHERE subject LIKE :search
                          OR message LIKE :search
                          OR author  LIKE :search";
        $params[':search'] = $search;
    }

    // Sorting
    $allowedSort  = ['subject', 'author', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    $sort  = isset($_GET['sort'])  && in_array($_GET['sort'],  $allowedSort, true)
           ? $_GET['sort']
           : 'created_at';

    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedOrder, true)
           ? strtolower($_GET['order'])
           : 'desc';

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $topics
    ]);
}

/**
 * Get single topic by topic_id
 */
function getTopicById(PDO $db, $topicId)
{
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic id is required.'
        ], 400);
    }

    $sql  = "SELECT topic_id, subject, message, author,
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
             FROM topics
             WHERE topic_id = :topic_id
             LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->execute();

    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topic) {
        sendResponse([
            'success' => true,
            'data'    => $topic
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found.'
        ], 404);
    }
}

/**
 * Create new topic
 */
function createTopic(PDO $db, array $data)
{
    if (
        empty($data['topic_id']) ||
        empty($data['subject'])  ||
        empty($data['message'])  ||
        empty($data['author'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'topic_id, subject, message, and author are required.'
        ], 400);
    }

    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author  = sanitizeInput($data['author']);

    // Check duplicate topic_id
    $checkSql  = "SELECT COUNT(*) FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':topic_id' => $topicId]);
    if ($checkStmt->fetchColumn() > 0) {
        sendResponse([
            'success' => false,
            'message' => 'Topic with this topic_id already exists.'
        ], 409);
    }

    // Insert
    $sql = "INSERT INTO topics (topic_id, subject, message, author)
            VALUES (:topic_id, :subject, :message, :author)";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->bindValue(':subject',  $subject, PDO::PARAM_STR);
    $stmt->bindValue(':message',  $message, PDO::PARAM_STR);
    $stmt->bindValue(':author',   $author,  PDO::PARAM_STR);

    if ($stmt->execute()) {
        sendResponse([
            'success'  => true,
            'message'  => 'Topic created successfully.',
            'topic_id' => $topicId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create topic.'
        ], 500);
    }
}

/**
 * Update topic
 */
function updateTopic(PDO $db, array $data)
{
    if (empty($data['topic_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'topic_id is required.'
        ], 400);
    }

    $topicId = sanitizeInput($data['topic_id']);

    // Check exists
    $checkSql  = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':topic_id' => $topicId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found.'
        ], 404);
    }

    $updates = [];
    $params  = [':topic_id' => $topicId];

    if (isset($data['subject'])) {
        $updates[]          = "subject = :subject";
        $params[':subject'] = sanitizeInput($data['subject']);
    }

    if (isset($data['message'])) {
        $updates[]          = "message = :message";
        $params[':message'] = sanitizeInput($data['message']);
    }

    if (empty($updates)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided to update.'
        ], 400);
    }

    $sql = "UPDATE topics SET " . implode(', ', $updates) . " WHERE topic_id = :topic_id";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            sendResponse([
                'success' => true,
                'message' => 'Topic updated successfully.'
            ]);
        } else {
            sendResponse([
                'success' => true,
                'message' => 'No changes were made.'
            ]);
        }
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update topic.'
        ], 500);
    }
}

/**
 * Delete topic (+ its replies)
 */
function deleteTopic(PDO $db, $topicId)
{
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic id is required.'
        ], 400);
    }

    // Check exists
    $checkSql  = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':topic_id' => $topicId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found.'
        ], 404);
    }

    try {
        $db->beginTransaction();

        // Delete replies first
        $delRepliesSql  = "DELETE FROM replies WHERE topic_id = :topic_id";
        $delRepliesStmt = $db->prepare($delRepliesSql);
        $delRepliesStmt->execute([':topic_id' => $topicId]);

        // Delete topic
        $delTopicSql  = "DELETE FROM topics WHERE topic_id = :topic_id";
        $delTopicStmt = $db->prepare($delTopicSql);
        $delTopicStmt->execute([':topic_id' => $topicId]);

        $db->commit();

        sendResponse([
            'success' => true,
            'message' => 'Topic and its replies deleted successfully.'
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Error deleting topic: ' . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete topic.'
        ], 500);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Get all replies for a topic
 */
function getRepliesByTopicId(PDO $db, $topicId)
{
    if (empty($topicId)) {
        sendResponse([
            'success' => false,
            'message' => 'topic_id is required.'
        ], 400);
    }

    $sql = "SELECT reply_id, topic_id, text, author,
                   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
            FROM replies
            WHERE topic_id = :topic_id
            ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->execute();

    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $replies
    ]);
}

/**
 * Create a new reply
 */
function createReply(PDO $db, array $data)
{
    if (
        empty($data['reply_id']) ||
        empty($data['topic_id']) ||
        empty($data['text'])     ||
        empty($data['author'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'reply_id, topic_id, text, and author are required.'
        ], 400);
    }

    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text    = sanitizeInput($data['text']);
    $author  = sanitizeInput($data['author']);

    // Check topic exists
    $checkTopicSql  = "SELECT topic_id FROM topics WHERE topic_id = :topic_id";
    $checkTopicStmt = $db->prepare($checkTopicSql);
    $checkTopicStmt->execute([':topic_id' => $topicId]);
    if (!$checkTopicStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Parent topic not found.'
        ], 404);
    }

    // Check duplicate reply_id
    $checkReplySql  = "SELECT COUNT(*) FROM replies WHERE reply_id = :reply_id";
    $checkReplyStmt = $db->prepare($checkReplySql);
    $checkReplyStmt->execute([':reply_id' => $replyId]);
    if ($checkReplyStmt->fetchColumn() > 0) {
        sendResponse([
            'success' => false,
            'message' => 'Reply with this reply_id already exists.'
        ], 409);
    }

    $sql = "INSERT INTO replies (reply_id, topic_id, text, author)
            VALUES (:reply_id, :topic_id, :text, :author)";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':reply_id', $replyId, PDO::PARAM_STR);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->bindValue(':text',     $text,    PDO::PARAM_STR);
    $stmt->bindValue(':author',   $author,  PDO::PARAM_STR);

    if ($stmt->execute()) {
        sendResponse([
            'success'  => true,
            'message'  => 'Reply created successfully.',
            'reply_id' => $replyId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create reply.'
        ], 500);
    }
}

/**
 * Delete a reply
 */
function deleteReply(PDO $db, $replyId)
{
    if (empty($replyId)) {
        sendResponse([
            'success' => false,
            'message' => 'Reply id is required.'
        ], 400);
    }

    // Check exists
    $checkSql  = "SELECT reply_id FROM replies WHERE reply_id = :reply_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':reply_id' => $replyId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Reply not found.'
        ], 404);
    }

    $sql  = "DELETE FROM replies WHERE reply_id = :reply_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':reply_id', $replyId, PDO::PARAM_STR);

    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Reply deleted successfully.'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete reply.'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if (!isValidResource($resource)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource parameter.'
        ], 400);
    }

    if ($resource === 'topics') {
        if ($method === 'GET') {
            $topicId = isset($_GET['id']) ? $_GET['id'] : null;
            if ($topicId) {
                getTopicById($db, $topicId);
            } else {
                getAllTopics($db);
            }

        } elseif ($method === 'POST') {
            createTopic($db, $bodyData);

        } elseif ($method === 'PUT') {
            updateTopic($db, $bodyData);

        } elseif ($method === 'DELETE') {
            // id can come from query or body
            $topicId = $_GET['id'] ?? ($bodyData['topic_id'] ?? null);
            deleteTopic($db, $topicId);

        } else {
            sendResponse([
                'success' => false,
                'message' => 'Method not allowed for topics.'
            ], 405);
        }

    } elseif ($resource === 'replies') {
        if ($method === 'GET') {
            $topicId = isset($_GET['topic_id']) ? $_GET['topic_id'] : null;
            getRepliesByTopicId($db, $topicId);

        } elseif ($method === 'POST') {
            createReply($db, $bodyData);

        } elseif ($method === 'DELETE') {
            $replyId = $_GET['id'] ?? ($bodyData['reply_id'] ?? null);
            deleteReply($db, $replyId);

        } else {
            sendResponse([
                'success' => false,
                'message' => 'Method not allowed for replies.'
            ], 405);
        }
    }
} catch (PDOException $e) {
    error_log('PDOException in discussion API: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred.'
    ], 500);
} catch (Exception $e) {
    error_log('Exception in discussion API: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An unexpected error occurred.'
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send JSON response and exit
 */
function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    $json = json_encode($data);
    if ($json === false) {
        // fallback لو في مشكلة encode
        $json = json_encode([
            'success' => false,
            'message' => 'JSON encoding error.'
        ]);
    }

    echo $json;
    exit;
}

/**
 * Sanitize input string
 */
function sanitizeInput($data)
{
    if (!is_string($data)) {
        return $data;
    }

    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return $data;
}

/**
 * Validate resource name
 */
function isValidResource($resource)
{
    $allowed = ['topics', 'replies'];
    return in_array($resource, $allowed, true);
}

?>
