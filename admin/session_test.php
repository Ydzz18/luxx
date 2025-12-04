<?php
// Create test file: session_test.php in your admin folder
session_start();

if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = 0;
}
$_SESSION['test']++;

echo "Session count: " . $_SESSION['test'] . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session path: " . session_save_path() . "<br>";

$path = session_save_path();
if (is_writable($path)) {
    echo "<strong style='color: green;'>✓ Session path is writable</strong>";
} else {
    echo "<strong style='color: red;'>✗ Session path is NOT writable - THIS IS THE PROBLEM!</strong>";
}
?>