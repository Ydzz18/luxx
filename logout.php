<?php
require_once 'config.php';
require_once 'User.php';

// Logout user
$user = new User();
$user->logout();

// Set flash message
flash('success', 'You have been logged out successfully');

// Redirect to login page
redirect('login.php');