<?php
/**
 * Export Bookings to Excel
 * Generates an Excel file with all bookings or filtered by status
 */

// Include bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/BookingModel.php';

// Start session
session_name(SESSION_NAME);
session_start();

// Check authentication
$adminModel = new AdminModel();
if (!isset($_SESSION['admin_token'])) {
    die('Unauthorized');
}

$session = $adminModel->validateSession($_SESSION['admin_token']);
if (!$session) {
    die('Session expired');
}

// Get status filter if provided
$status = $_GET['status'] ?? null;

// Get all bookings
$bookingModel = new BookingModel();
$bookings = $bookingModel->getAllBookings(10000, 0, $status); // Get all bookings

// Generate filename
$filename = 'TravelEase_Bookings_' . date('Y-m-d_His');
if ($status) {
    $filename .= '_' . ucfirst($status);
}
$filename .= '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (helps Excel recognize UTF-8 encoding)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers with all possible fields
fputcsv($output, [
    'Booking ID',
    'Trip Type',
    'Date',
    'Pickup Time',
    'Pickup Location',
    'Drop-off Location',
    'Return Pickup Location',
    'Return Time',
    'Airport Name',
    'Terminal',
    'Flight Number',
    'Arrival Time',
    'Start Time',
    'End Time',
    'Passengers',
    'Vehicle Type',
    'Special Requests',
    'Itinerary',
    'Status',
    'Booking Date',
    'Last Updated'
]);

// Helper function to format trip type
function formatTripType($tripType) {
    $types = [
        'one_way' => 'One Way Transfer',
        'return' => 'Return Transfer',
        'airport_arrival' => 'Airport Arrival',
        'airport_departure' => 'Airport Departure',
        'half_day' => 'Half Day Service',
        'full_day' => 'Full Day Service',
        '24_hours' => '24 Hours Service'
    ];
    return $types[$tripType] ?? ucwords(str_replace('_', ' ', $tripType));
}

// Add data rows
foreach ($bookings as $booking) {
    fputcsv($output, [
        $booking['id'],
        formatTripType($booking['trip_type']),
        !empty($booking['date']) ? date('Y-m-d', strtotime($booking['date'])) : '',
        !empty($booking['pickup_time']) ? $booking['pickup_time'] : '',
        $booking['pickup_location'] ?? '',
        $booking['dropoff_location'] ?? '',
        $booking['return_pickup_location'] ?? '',
        !empty($booking['return_time']) ? $booking['return_time'] : '',
        $booking['airport_name'] ?? '',
        $booking['terminal'] ?? '',
        $booking['flight_number'] ?? '',
        !empty($booking['arrival_time']) ? $booking['arrival_time'] : '',
        !empty($booking['start_time']) ? $booking['start_time'] : '',
        !empty($booking['end_time']) ? $booking['end_time'] : '',
        $booking['passengers'] ?? '',
        $booking['vehicle_type'] ? ucwords(str_replace('_', ' ', $booking['vehicle_type'])) : '',
        $booking['special_requests'] ?? '',
        $booking['itinerary'] ?? '',
        ucfirst($booking['status']),
        date('Y-m-d H:i:s', strtotime($booking['created_at'])),
        date('Y-m-d H:i:s', strtotime($booking['updated_at']))
    ]);
}

fclose($output);
exit;
?>