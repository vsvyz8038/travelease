<?php
/**
 * Booking API Endpoint
 * Handles booking form submissions
 */

// Include bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../controllers/BookingController.php';

// Initialize controller and handle request
$controller = new BookingController();
$controller->create();
?>