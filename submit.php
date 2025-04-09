<?php
header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'shoutout_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Create upload directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required = ['senderName', 'senderEmail', 'recipientName', 'recipientEmail', 'recipientEmpId', 'message'];
        $errors = [];
        
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }

        // Validate email formats
        $emails = [
            'senderEmail' => $_POST['senderEmail'] ?? '',
            'recipientEmail' => $_POST['recipientEmail'] ?? '',
            'ccEmail' => $_POST['ccEmail'] ?? ''
        ];

        foreach ($emails as $field => $value) {
            if ($field !== 'ccEmail' && empty($value)) {
                $errors[] = "$field is required";
            } elseif (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format for $field";
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode("\n", $errors));
        }

        // Handle file upload
        $photoPath = null;
        if (!empty($_FILES['photoUpload']['name']) && $_FILES['photoUpload']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['photoUpload'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error: " . $file['error']);
            }
            
            // Validate file type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif'
            ];
            
            if (!array_key_exists($mime, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed");
            }

            // Validate file size (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size exceeds 5MB limit");
            }

            // Generate safe filename
            $extension = $allowedTypes[$mime];
            $filename = sprintf('%s.%s', bin2hex(random_bytes(8)), $extension);
            $photoPath = 'uploads/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
                throw new Exception("Failed to save uploaded file");
            }
        }

        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO shoutouts (
                sender_name,
                sender_email,
                cc_email,
                recipient_name,
                recipient_email,
                recipient_emp_id,
                message,
                photo_path
            ) VALUES (
                :senderName, 
                :senderEmail, 
                :ccEmail, 
                :recipientName, 
                :recipientEmail, 
                :recipientEmpId, 
                :message, 
                :photoPath
            )
        ");

        $stmt->execute([
            ':senderName' => htmlspecialchars($_POST['senderName']),
            ':senderEmail' => filter_var($_POST['senderEmail'], FILTER_SANITIZE_EMAIL),
            ':ccEmail' => !empty($_POST['ccEmail']) ? filter_var($_POST['ccEmail'], FILTER_SANITIZE_EMAIL) : null,
            ':recipientName' => htmlspecialchars($_POST['recipientName']),
            ':recipientEmail' => filter_var($_POST['recipientEmail'], FILTER_SANITIZE_EMAIL),
            ':recipientEmpId' => htmlspecialchars($_POST['recipientEmpId']),
            ':message' => htmlspecialchars($_POST['message']),
            ':photoPath' => $photoPath
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Shoutout submitted successfully'
        ]);

    } catch (Exception $e) {
        error_log("Submission error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}