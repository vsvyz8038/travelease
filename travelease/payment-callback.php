<?php
/**
 * Payment Callback Handler
 * Verifies Razorpay payment and updates booking status
 */

header('Content-Type: application/json');

// Include bootstrap
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/models/PaymentModel.php';
require_once __DIR__ . '/models/PricingModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$bookingId = $_POST['booking_id'] ?? '';
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
$razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
$razorpaySignature = $_POST['razorpay_signature'] ?? '';

if (empty($bookingId) || empty($razorpayPaymentId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $bookingModel = new BookingModel();
    $paymentModel = new PaymentModel();
    $pricingModel = new PricingModel();
    
    // Get booking details
    $booking = $bookingModel->getBookingById($bookingId);
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // Get Razorpay secret key
    $keySecret = $pricingModel->getPaymentConfig('razorpay_key_secret');
    
    // Verify signature (if provided)
    $signatureVerified = true;
    if (!empty($razorpaySignature) && !empty($razorpayOrderId)) {
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $keySecret);
        $signatureVerified = ($expectedSignature === $razorpaySignature);
    }
    
    if ($signatureVerified) {
        // Update payment record
        $paymentData = [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
            'status' => 'success',
            'payment_method' => 'razorpay',
            'error_description' => null
        ];
        
        // Create or update payment
        $payment = $paymentModel->getPaymentByBookingId($bookingId);
        
        if ($payment) {
            $paymentModel->updatePaymentStatus($payment['razorpay_order_id'], $paymentData);
        } else {
            // Create new payment record
            $paymentModel->createPayment([
                'booking_id' => $bookingId,
                'razorpay_order_id' => $razorpayOrderId ?: 'direct_' . $razorpayPaymentId,
                'amount' => $booking['amount'],
                'currency' => 'INR',
                'status' => 'success'
            ]);
            $paymentModel->updatePaymentStatus($razorpayOrderId ?: 'direct_' . $razorpayPaymentId, $paymentData);
        }
        
        // Update booking status to pending (payment done, waiting for admin confirmation)
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE bookings SET status = 'pending', payment_status = 'paid' WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $bookingId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'booking_id' => $bookingId
        ]);
    } else {
        // Signature verification failed
        $paymentModel->updatePaymentStatus($razorpayOrderId, [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
            'status' => 'failed',
            'payment_method' => 'razorpay',
            'error_description' => 'Signature verification failed'
        ]);
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Payment verification failed'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Payment callback error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error during payment verification'
    ]);
}
?>