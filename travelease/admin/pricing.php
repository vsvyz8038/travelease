<?php
/**
 * Admin Pricing Configuration Page
 */

// Include bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../models/PricingModel.php';

// Start session
session_name(SESSION_NAME);
session_start();

// Check authentication
$adminModel = new AdminModel();
if (!isset($_SESSION['admin_token'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$session = $adminModel->validateSession($_SESSION['admin_token']);
if (!$session) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

// Handle form submission
$pricingModel = new PricingModel();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pricing'])) {
    $tripType = $_POST['trip_type'];
    $data = [
        'base_price' => $_POST['base_price'],
        'price_per_hour' => $_POST['price_per_hour'],
        'price_per_km' => $_POST['price_per_km'],
        'description' => $_POST['description']
    ];
    
    if ($pricingModel->updatePricing($tripType, $data)) {
        $message = '<div class="alert alert-success">Pricing updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-error">Failed to update pricing.</div>';
    }
}

// Handle payment config update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_config'])) {
    $pricingModel->updatePaymentConfig('razorpay_key_id', $_POST['razorpay_key_id']);
    $pricingModel->updatePaymentConfig('razorpay_key_secret', $_POST['razorpay_key_secret']);
    $pricingModel->updatePaymentConfig('payment_mode', $_POST['payment_mode']);
    $message = '<div class="alert alert-success">Payment configuration updated successfully!</div>';
}

// Get all pricing
$pricingList = $pricingModel->getAllPricing();

// Get payment config
$razorpayKeyId = $pricingModel->getPaymentConfig('razorpay_key_id');
$razorpayKeySecret = $pricingModel->getPaymentConfig('razorpay_key_secret');
$paymentMode = $pricingModel->getPaymentConfig('payment_mode');

$tripTypeNames = [
    'one_way' => 'One Way Transfer',
    'return' => 'Return Transfer',
    'airport_arrival' => 'Airport Transfer - Arrival',
    'airport_departure' => 'Airport Transfer - Departure',
    'half_day' => 'Half Day Service',
    'full_day' => 'Full Day Service',
    '24_hours' => '24 Hours Service'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Configuration - TravelEase Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1a1a1a; }
        .header { background: white; padding: 1.5rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #0a2540; font-size: 1.8rem; font-weight: 700; }
        .header-right { display: flex; align-items: center; gap: 2rem; }
        .back-btn { background: #6c757d; color: white; padding: 0.6rem 1.5rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
        .back-btn:hover { background: #5a6268; }
        .logout-btn { background: #dc3545; color: white; padding: 0.6rem 1.5rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: #c82333; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { color: #0a2540; font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; }
        .pricing-card { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 2px solid #e0e7ef; }
        .pricing-card h3 { color: #0a2540; margin-bottom: 1rem; font-size: 1.2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #0a2540; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.8rem; border: 1.5px solid #e0e7ef; border-radius: 6px; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #0a2540; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .submit-btn { background: #0a2540; color: white; padding: 0.8rem 2rem; border: none; border-radius: 6px; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        .submit-btn:hover { background: #1a4d7a; }
        .info-box { background: #e7f3ff; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #0066cc; }
        .info-box p { color: #004085; font-size: 0.9rem; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pricing Configuration</h1>
        <div class="header-right">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Payment Gateway Configuration -->
        <div class="card">
            <h2><i class="fas fa-credit-card"></i> Payment Gateway Configuration</h2>
            <div class="info-box">
                <p><strong>Razorpay Setup Instructions:</strong></p>
                <p>1. Sign up at <a href="https://razorpay.com" target="_blank">razorpay.com</a></p>
                <p>2. Go to Settings → API Keys</p>
                <p>3. Generate Test/Live Keys and paste them below</p>
                <p>4. Use "test" mode for development, "live" for production</p>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="razorpay_key_id">Razorpay Key ID</label>
                    <input type="text" id="razorpay_key_id" name="razorpay_key_id" value="<?php echo htmlspecialchars($razorpayKeyId); ?>" required>
                </div>
                <div class="form-group">
                    <label for="razorpay_key_secret">Razorpay Key Secret</label>
                    <input type="text" id="razorpay_key_secret" name="razorpay_key_secret" value="<?php echo htmlspecialchars($razorpayKeySecret); ?>" required>
                </div>
                <div class="form-group">
                    <label for="payment_mode">Payment Mode</label>
                    <select id="payment_mode" name="payment_mode" required>
                        <option value="test" <?php echo $paymentMode === 'test' ? 'selected' : ''; ?>>Test Mode</option>
                        <option value="live" <?php echo $paymentMode === 'live' ? 'selected' : ''; ?>>Live Mode</option>
                    </select>
                </div>
                <button type="submit" name="update_payment_config" class="submit-btn">
                    <i class="fas fa-save"></i> Save Payment Configuration
                </button>
            </form>
        </div>

        <!-- Trip Type Pricing -->
        <div class="card">
            <h2><i class="fas fa-rupee-sign"></i> Trip Type Pricing</h2>
            <div class="pricing-grid">
                <?php foreach ($pricingList as $pricing): ?>
                    <div class="pricing-card">
                        <h3><?php echo $tripTypeNames[$pricing['trip_type']] ?? $pricing['trip_type']; ?></h3>
                        <form method="POST">
                            <input type="hidden" name="trip_type" value="<?php echo $pricing['trip_type']; ?>">
                            
                            <div class="form-group">
                                <label for="base_price_<?php echo $pricing['trip_type']; ?>">Base Price (₹)</label>
                                <input type="number" step="0.01" id="base_price_<?php echo $pricing['trip_type']; ?>" name="base_price" value="<?php echo $pricing['base_price']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price_per_hour_<?php echo $pricing['trip_type']; ?>">Price Per Hour (₹) - Optional</label>
                                <input type="number" step="0.01" id="price_per_hour_<?php echo $pricing['trip_type']; ?>" name="price_per_hour" value="<?php echo $pricing['price_per_hour']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="price_per_km_<?php echo $pricing['trip_type']; ?>">Price Per KM (₹) - Optional</label>
                                <input type="number" step="0.01" id="price_per_km_<?php echo $pricing['trip_type']; ?>" name="price_per_km" value="<?php echo $pricing['price_per_km']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description_<?php echo $pricing['trip_type']; ?>">Description</label>
                                <textarea id="description_<?php echo $pricing['trip_type']; ?>" name="description"><?php echo htmlspecialchars($pricing['description']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_pricing" class="submit-btn">
                                <i class="fas fa-save"></i> Update Pricing
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>