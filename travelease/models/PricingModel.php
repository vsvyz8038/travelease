<?php
/**
 * Pricing Model
 * Handles all database operations related to trip pricing and payment configuration
 */

class PricingModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get pricing for a specific trip type
     */
    public function getPricingByTripType($tripType) {
        $sql = "SELECT * FROM pricing WHERE trip_type = :trip_type LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':trip_type' => $tripType]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get pricing error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all pricing configurations
     */
    public function getAllPricing() {
        $sql = "SELECT * FROM pricing ORDER BY 
                FIELD(trip_type, 'one_way', 'return', 'airport_arrival', 'airport_departure', 'half_day', 'full_day', '24_hours')";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all pricing error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update pricing for a trip type
     */
    public function updatePricing($tripType, $data) {
        $sql = "UPDATE pricing SET 
                base_price = :base_price,
                price_per_hour = :price_per_hour,
                price_per_km = :price_per_km,
                description = :description,
                updated_at = NOW()
                WHERE trip_type = :trip_type";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':trip_type' => $tripType,
                ':base_price' => $data['base_price'],
                ':price_per_hour' => $data['price_per_hour'] ?: null,
                ':price_per_km' => $data['price_per_km'] ?: null,
                ':description' => $data['description']
            ]);
        } catch (PDOException $e) {
            error_log("Update pricing error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create pricing entry for a trip type
     */
    public function createPricing($tripType, $data) {
        $sql = "INSERT INTO pricing (trip_type, base_price, price_per_hour, price_per_km, description) 
                VALUES (:trip_type, :base_price, :price_per_hour, :price_per_km, :description)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':trip_type' => $tripType,
                ':base_price' => $data['base_price'],
                ':price_per_hour' => $data['price_per_hour'] ?: null,
                ':price_per_km' => $data['price_per_km'] ?: null,
                ':description' => $data['description']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Create pricing error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment configuration value
     */
    public function getPaymentConfig($key) {
        $sql = "SELECT config_value FROM payment_config WHERE config_key = :key LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':key' => $key]);
            $result = $stmt->fetch();
            return $result ? $result['config_value'] : '';
        } catch (PDOException $e) {
            error_log("Get payment config error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Update payment configuration
     */
    public function updatePaymentConfig($key, $value) {
        $sql = "INSERT INTO payment_config (config_key, config_value) 
                VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE config_value = :value, updated_at = NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':key' => $key,
                ':value' => $value
            ]);
        } catch (PDOException $e) {
            error_log("Update payment config error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate total price based on trip details
     */
    public function calculatePrice($tripType, $details = []) {
        $pricing = $this->getPricingByTripType($tripType);
        
        if (!$pricing) {
            return null;
        }
        
        $totalPrice = (float)$pricing['base_price'];
        
        // Add hour-based pricing if applicable
        if (!empty($pricing['price_per_hour']) && !empty($details['hours'])) {
            $totalPrice += (float)$pricing['price_per_hour'] * (int)$details['hours'];
        }
        
        // Add distance-based pricing if applicable
        if (!empty($pricing['price_per_km']) && !empty($details['distance_km'])) {
            $totalPrice += (float)$pricing['price_per_km'] * (float)$details['distance_km'];
        }
        
        return [
            'base_price' => (float)$pricing['base_price'],
            'price_per_hour' => $pricing['price_per_hour'] ? (float)$pricing['price_per_hour'] : null,
            'price_per_km' => $pricing['price_per_km'] ? (float)$pricing['price_per_km'] : null,
            'total_price' => $totalPrice,
            'currency' => 'INR'
        ];
    }
    
    /**
     * Delete pricing entry
     */
    public function deletePricing($tripType) {
        $sql = "DELETE FROM pricing WHERE trip_type = :trip_type";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':trip_type' => $tripType]);
        } catch (PDOException $e) {
            error_log("Delete pricing error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Initialize default pricing if not exists
     */
    public function initializeDefaultPricing() {
        $defaultPricing = [
            'one_way' => ['base_price' => 1000, 'description' => 'One-way transfer service'],
            'return' => ['base_price' => 1800, 'description' => 'Round-trip transfer service'],
            'airport_arrival' => ['base_price' => 1200, 'description' => 'Airport pickup service'],
            'airport_departure' => ['base_price' => 1200, 'description' => 'Airport drop-off service'],
            'half_day' => ['base_price' => 2500, 'price_per_hour' => 250, 'description' => 'Half day rental (4 hours)'],
            'full_day' => ['base_price' => 4500, 'price_per_hour' => 200, 'description' => 'Full day rental (8 hours)'],
            '24_hours' => ['base_price' => 6500, 'description' => '24-hour service']
        ];
        
        foreach ($defaultPricing as $tripType => $data) {
            $existing = $this->getPricingByTripType($tripType);
            if (!$existing) {
                $this->createPricing($tripType, [
                    'base_price' => $data['base_price'],
                    'price_per_hour' => $data['price_per_hour'] ?? null,
                    'price_per_km' => null,
                    'description' => $data['description']
                ]);
            }
        }
        
        return true;
    }
}
?>