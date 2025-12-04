<?php
require_once 'config.php';

$db = db();

echo "=== PRODUCTS WITH IMAGE PATHS ===\n";
$stmt = $db->query("SELECT id, name, image FROM products LIMIT 10");
$prods = $stmt->fetchAll();
foreach ($prods as $prod) {
    $imagePath = $prod['image'];
    $fullPath = __DIR__ . '/' . $imagePath;
    $exists = file_exists($fullPath) ? "YES" : "NO";
    echo "- ID: {$prod['id']}, Name: {$prod['name']}\n  Image: {$imagePath}\n  Exists: {$exists}\n";
}

echo "\n=== UPLOADS DIRECTORY CONTENTS ===\n";
$uploadsDir = __DIR__ . '/uploads';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    echo "Files count: " . (count($files) - 2) . "\n";
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "- $f\n";
        }
    }
} else {
    echo "Uploads directory does not exist\n";
}
?>
