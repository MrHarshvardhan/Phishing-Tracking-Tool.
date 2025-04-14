<?php
if (!defined('ALLOW_ACCESS')) {
    http_response_code(404);
    die("Forbidden");
}

$file = "uk.js";

if (!file_exists($file)) {
    http_response_code(500);
    die("Download file not found.");
}

// Log file downloaded status
$stmt = $conn->prepare("UPDATE emp SET file_downloaded = 'YES' WHERE hash = ?");
$stmt->execute([$token]);

session_regenerate_id();
session_destroy();

// Serve the file
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . urlencode($file));
header("Content-Transfer-Encoding: utf-8");
header("Content-Description: File Transfer");
header("Content-Length: " . filesize($file));
readfile($file);
exit();
?>
