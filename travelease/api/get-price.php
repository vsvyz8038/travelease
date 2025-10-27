<?php
/**
 * Get Price API
 * Returns price for selected trip type
 */

header('Content-Type: application/json');

// Include bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/PricingModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$tripType = $_GET['trip_type'] ?? '';

if (empty($tripType)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Trip type is required']);
    exit;
}

try {
    $pricingModel = new PricingModel();
    $pricing = $pricingModel->getPricingByTripType($tripType);
    
    if ($pricing) {
        echo json_encode([
            'success' => true,
            'pricing' => [
                'base_price' => (float)$pricing['base_price'],
                'price_per_hour' => $pricing['price_per_hour'] ? (float)$pricing['price_per_hour'] : null,
                'price_per_km' => $pricing['price_per_km'] ? (float)$pricing['price_per_km'] : null,
                'description' => $pricing['description'],
                'currency' => 'INR',
                'currency_symbol' => '₹'
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pricing not found for this trip type']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>