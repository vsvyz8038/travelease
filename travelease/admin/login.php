<?php
// Include bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/BookingModel.php';  // ← ADD THIS LINE
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../controllers/AdminController.php';

// Initialize controller and display login page
$controller = new AdminController();
$controller->login();
?>