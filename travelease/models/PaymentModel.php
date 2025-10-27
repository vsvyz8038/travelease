<?php
/**
 * Payment Model
 * Handles all database operations related to payments and Razorpay integration
 */

class PaymentModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new payment record
     */
    public function createPayment($data) {
        $sql = "INSERT INTO payments 
                (booking_id, razorpay_order_id, amount, currency, status) 
                VALUES (:booking_id, :order_id, :amount, :currency, :status)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':booking_id' => $data['booking_id'],
                ':order_id' => $data['razorpay_order_id'],
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'INR',
                ':status' => $data['status'] ?? 'pending'
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Create payment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment by booking ID
     */
    public function getPaymentByBookingId($bookingId) {
        $sql = "SELECT * FROM payments WHERE booking_id = :booking_id ORDER BY created_at DESC LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':booking_id' => $bookingId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get payment by booking error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get payment by Razorpay order ID
     */
    public function getPaymentByOrderId($orderId) {
        $sql = "SELECT * FROM payments WHERE razorpay_order_id = :order_id LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':order_id' => $orderId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get payment by order error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get payment by Razorpay payment ID
     */
    public function getPaymentByPaymentId($paymentId) {
        $sql = "SELECT * FROM payments WHERE razorpay_payment_id = :payment_id LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get payment by payment ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update payment status and details
     */
    public function updatePaymentStatus($orderId, $data) {
        $sql = "UPDATE payments SET 
                razorpay_payment_id = :payment_id,
                razorpay_signature = :signature,
                status = :status,
                payment_method = :payment_method,
                error_description = :error_description,
                updated_at = NOW()
                WHERE razorpay_order_id = :order_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':order_id' => $orderId,
                ':payment_id' => $data['razorpay_payment_id'] ?? null,
                ':signature' => $data['razorpay_signature'] ?? null,
                ':status' => $data['status'],
                ':payment_method' => $data['payment_method'] ?? null,
                ':error_description' => $data['error_description'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Update payment status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update payment by ID
     */
    public function updatePaymentById($paymentId, $data) {
        $fields = [];
        $params = [':id' => $paymentId];
        
        if (isset($data['razorpay_payment_id'])) {
            $fields[] = "razorpay_payment_id = :payment_id";
            $params[':payment_id'] = $data['razorpay_payment_id'];
        }
        
        if (isset($data['razorpay_signature'])) {
            $fields[] = "razorpay_signature = :signature";
            $params[':signature'] = $data['razorpay_signature'];
        }
        
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['payment_method'])) {
            $fields[] = "payment_method = :payment_method";
            $params[':payment_method'] = $data['payment_method'];
        }
        
        if (isset($data['error_description'])) {
            $fields[] = "error_description = :error_description";
            $params[':error_description'] = $data['error_description'];
        }
        
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE payments SET " . implode(', ', $fields) . " WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update payment by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all payments with optional filters
     */
    public function getAllPayments($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT p.*, b.trip_type, b.pickup_location, b.dropoff_location 
                FROM payments p 
                LEFT JOIN bookings b ON p.booking_id = b.id";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE p.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all payments error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_revenue
                FROM payments";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get payment stats error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get total revenue
     */
    public function getTotalRevenue($startDate = null, $endDate = null) {
        $sql = "SELECT SUM(amount) as total_revenue 
                FROM payments 
                WHERE status = 'success'";
        $params = [];
        
        if ($startDate) {
            $sql .= " AND created_at >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND created_at <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['total_revenue'] ?? 0;
        } catch (PDOException $e) {
            error_log("Get total revenue error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verify Razorpay signature
     */
    public function verifySignature($orderId, $paymentId, $signature, $keySecret) {
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Mark payment as failed
     */
    public function markPaymentFailed($orderId, $errorDescription = null) {
        return $this->updatePaymentStatus($orderId, [
            'status' => 'failed',
            'error_description' => $errorDescription,
            'razorpay_payment_id' => null,
            'razorpay_signature' => null,
            'payment_method' => null
        ]);
    }
    
    /**
     * Delete payment record
     */
    public function deletePayment($paymentId) {
        $sql = "DELETE FROM payments WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $paymentId]);
        } catch (PDOException $e) {
            error_log("Delete payment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent payments for a booking
     */
    public function getPaymentHistory($bookingId, $limit = 10) {
        $sql = "SELECT * FROM payments 
                WHERE booking_id = :booking_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get payment history error: " . $e->getMessage());
            return [];
        }
    }
}
?>