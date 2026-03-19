<?php
// Bắt đầu output buffering để tránh output không mong muốn
ob_start();

require_once 'includes/auth.php';

// Xóa buffer và set header JSON
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => '',
    'url' => ''
];

// Kiểm tra có file không
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Không có file được upload hoặc có lỗi xảy ra';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy ảnh cũ để xóa (không dùng sanitize vì sẽ encode HTML entities)
$oldImage = isset($_POST['old_image']) ? trim(strip_tags($_POST['old_image'])) : '';

$file = $_FILES['image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Lấy extension
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Các extension được phép
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Kiểm tra extension
if (!in_array($fileExt, $allowedExts)) {
    $response['message'] = 'Chỉ chấp nhận file ảnh: JPG, JPEG, PNG, GIF, WEBP';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra kích thước (max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    $response['message'] = 'File quá lớn. Kích thước tối đa: 5MB';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra là file ảnh thật
$imageInfo = getimagesize($fileTmpName);
if ($imageInfo === false) {
    $response['message'] = 'File không phải là ảnh hợp lệ';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Tạo tên file mới (unique)
$newFileName = uniqid('product_', true) . '.' . $fileExt;

// Đường dẫn lưu file
$uploadDir = '../images/products/';
$uploadPath = $uploadDir . $newFileName;

// Tạo thư mục nếu chưa có
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Di chuyển file
if (move_uploaded_file($fileTmpName, $uploadPath)) {
    // Xóa ảnh cũ nếu có
    if (!empty($oldImage)) {
        $oldImagePath = '../' . $oldImage;
        if (file_exists($oldImagePath) && strpos($oldImagePath, '../images/products/') !== false) {
            unlink($oldImagePath);
        }
    }
    
    // Resize ảnh nếu quá lớn (optional)
    resizeImage($uploadPath, 800, 800);
    
    $response['success'] = true;
    $response['message'] = 'Upload ảnh thành công';
    $response['url'] = 'images/products/' . $newFileName;
} else {
    $response['message'] = 'Lỗi khi lưu file';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Resize ảnh nếu lớn hơn kích thước cho phép
 */
function resizeImage($filePath, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) return false;
    
    list($width, $height, $type) = $imageInfo;
    
    // Nếu ảnh nhỏ hơn max thì không resize
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true;
    }
    
    // Tính tỷ lệ
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Tạo ảnh mới
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Load ảnh gốc
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filePath);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filePath);
            break;
        default:
            return false;
    }
    
    // Resize
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Lưu ảnh
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $filePath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $filePath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $filePath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($newImage, $filePath, 90);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($newImage);
    
    return true;
}
?>

