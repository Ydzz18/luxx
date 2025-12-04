<?php
/**
 * Setup Script - Creates Required Folders
 * Run this once: http://localhost/LuxStore_Ecom/setup_folders.php
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>LuxStore Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h1 { color: #333; }
        .btn { display: inline-block; padding: 10px 20px; background: #d4af37; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🔧 LuxStore Setup</h1>";

// Create required folders
$folders = ['uploads', 'logs', 'uploads/products'];
$created = [];
$errors = [];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        if (mkdir($folder, 0755, true)) {
            $created[] = $folder;
            echo "<div class='success'>✓ Created folder: $folder</div>";
        } else {
            $errors[] = $folder;
            echo "<div class='error'>✗ Failed to create: $folder</div>";
        }
    } else {
        echo "<div class='success'>✓ Folder already exists: $folder</div>";
    }
}

// Create .htaccess file
$htaccess_content = '# LuxStore Apache Configuration

# Enable PHP processing
AddType application/x-httpd-php .php
AddHandler application/x-httpd-php .php

# Security: Prevent directory listing
Options -Indexes

# Set default charset
AddDefaultCharset UTF-8

# Enable error display for development (disable in production)
php_flag display_errors On
php_value error_reporting E_ALL
';

if (!file_exists('.htaccess')) {
    if (file_put_contents('.htaccess', $htaccess_content)) {
        echo "<div class='success'>✓ Created .htaccess file</div>";
    } else {
        echo "<div class='error'>✗ Failed to create .htaccess (this is optional)</div>";
    }
} else {
    echo "<div class='success'>✓ .htaccess already exists</div>";
}

// Create index.php in uploads (security)
$security_content = '<?php
// Security: Prevent direct access to uploads folder
header("HTTP/1.0 403 Forbidden");
die("Access Denied");
?>';

foreach (['uploads/index.php', 'logs/index.php'] as $security_file) {
    if (!file_exists($security_file)) {
        if (file_put_contents($security_file, $security_content)) {
            echo "<div class='success'>✓ Created security file: $security_file</div>";
        }
    }
}

// Create a sample .gitignore
$gitignore = 'logs/
uploads/
*.log
config.php
.htaccess
';

if (!file_exists('.gitignore')) {
    file_put_contents('.gitignore', $gitignore);
    echo "<div class='success'>✓ Created .gitignore file</div>";
}

echo "<hr>";

if (empty($errors)) {
    echo "<div class='success'>
        <h2>✅ Setup Complete!</h2>
        <p>All required folders and files have been created.</p>
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Your site is ready to use!</li>
            <li>Visit: <a href='index.html'>Homepage</a></li>
            <li>Run: <a href='diagnostic.php'>Diagnostic Check</a></li>
        </ol>
    </div>";
} else {
    echo "<div class='error'>
        <h3>⚠️ Some folders failed to create</h3>
        <p>You may need to create these manually:</p>
        <ul>";
    foreach ($errors as $folder) {
        echo "<li>$folder</li>";
    }
    echo "</ul>
    </div>";
}

echo "<a href='index.html' class='btn'>Go to Homepage →</a>";
echo "</body></html>";
?>