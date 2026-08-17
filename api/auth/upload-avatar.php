<?php
/**
 * SFI Queuing System - Upload Avatar API
 * POST /api/auth/upload-avatar.php
 *
 * Handles profile picture uploads for the logged-in user.
 * Accepts jpg, jpeg, png, gif, webp up to 2MB.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();
initAPI();
requirePost();

try {
    // ---- Remove the current profile picture ----
    if (post('action') === 'remove') {
        $uid = (int)getUserId();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT avatar FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $uid]);
        $old = (string)$stmt->fetchColumn();
        if ($old !== '') {
            $oldPath = ROOT_PATH . '/assets/uploads/avatars/' . $old;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $db->prepare("UPDATE users SET avatar = NULL WHERE id = :id")->execute([':id' => $uid]);
        unset($_SESSION['avatar']);
        logActivity($uid, getUsername(), 'update_profile', 'User removed their profile picture');
        Response::success('Profile picture removed.');
    }

    if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        Response::error('Please choose an image to upload.');
    }

    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        Response::error('Upload failed. Please try again.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        Response::error('Image is too large. Maximum size is 2MB.');
    }

    // Verify it is really an image
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        Response::error('Invalid image file.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $mime = $info['mime'];
    if (!isset($allowed[$mime])) {
        Response::error('Only JPG, PNG, GIF or WEBP images are allowed.');
    }
    $ext = $allowed[$mime];

    $uid   = (int)getUserId();
    $name  = 'u' . $uid . '_' . date('Ymd_His') . '.' . $ext;

    $dir = ROOT_PATH . '/assets/uploads/avatars';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $dest = $dir . '/' . $name;

    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        Response::error('Unable to save the image. Check folder permissions.');
    }

    $db = Database::getConnection();
    $stmt = $db->prepare("UPDATE users SET avatar = :a WHERE id = :id");
    $stmt->execute([':a' => $name, ':id' => $uid]);

    $_SESSION['avatar'] = $name; // reflect immediately in the topbar

    logActivity($uid, getUsername(), 'update_profile', 'User updated their profile picture');

    Response::success('Profile picture updated.', ['avatar' => $name]);

} catch (Exception $e) {
    error_log('SFI Upload Avatar Error: ' . $e->getMessage());
    Response::error('Failed to upload profile picture.', [], 500);
}
