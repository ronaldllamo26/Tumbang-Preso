<?php
/**
 * api/upload_handler.php
 * Handles user face uploads for the "Bobblehead" character customization.
 */
require_once '../core/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['face_image']) && $_FILES['face_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['face_image']['tmp_name'];
        $fileName = $_FILES['face_image']['name'];
        $fileSize = $_FILES['face_image']['size'];
        $fileType = $_FILES['face_image']['type'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (in_array($fileExtension, $allowedExtensions)) {
            // Create a unique name for the image
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/faces/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Update user in database (assuming user_id 1 for now or from session)
                $userId = 1; // This should come from a session in a real app
                $stmt = $pdo->prepare("UPDATE users SET face_path = ? WHERE id = ?");
                $stmt->execute([$newFileName, $userId]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Face uploaded successfully!',
                    'file_path' => 'uploads/faces/' . $newFileName
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error moving the uploaded file.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Upload failed. Allowed types: ' . implode(',', $allowedExtensions)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
