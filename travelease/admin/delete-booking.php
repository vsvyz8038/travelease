<?php
/**
 * Delete Booking API
 */

// Include bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../controllers/AdminController.php';

// Initialize controller and handle booking deletion
$controller = new AdminController();
$controller->deleteBooking();
?>