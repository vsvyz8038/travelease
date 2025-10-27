<?php
/**
 * Booking Model
 * Handles all database operations related to bookings
 */

class BookingModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new booking with dynamic fields based on trip type
     */
    public function createBooking($data) {
        // Prepare field lists
        $fields = ['trip_type', 'status'];
        $placeholders = [':trip_type', ':status'];
        $values = [
            ':trip_type' => $data['trip_type'],
            ':status' => 'pending'
        ];
        
        // Define all possible fields
        $possibleFields = [
            'date', 'pickup_time', 'pickup_location', 'dropoff_location', 
            'passengers', 'vehicle_type', 'special_requests',
            'return_pickup_location', 'return_time',
            'airport_name', 'terminal', 'arrival_time', 'flight_number',
            'start_time', 'end_time', 'itinerary'
        ];
        
        // Add fields that exist in the data
        foreach ($possibleFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $fields[] = $field;
                $placeholders[] = ':' . $field;
                $values[':' . $field] = $data[$field];
            }
        }
        
        $sql = "INSERT INTO bookings (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Booking creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all bookings with pagination
     */
    public function getAllBookings($limit = 20, $offset = 0, $status = null) {
        $sql = "SELECT * FROM bookings";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
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
            error_log("Get bookings error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total booking count
     */
    public function getTotalBookings($status = null) {
        $sql = "SELECT COUNT(*) as total FROM bookings";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Get total bookings error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get booking by ID
     */
    public function getBookingById($id) {
        $sql = "SELECT * FROM bookings WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get booking error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update booking status
     */
    public function updateBookingStatus($id, $status) {
        $sql = "UPDATE bookings SET status = :status WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':status' => $status
            ]);
        } catch (PDOException $e) {
            error_log("Update booking status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete booking
     */
    public function deleteBooking($id) {
        $sql = "DELETE FROM bookings WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Delete booking error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get booking statistics
     */
    public function getBookingStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM bookings";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get booking stats error: " . $e->getMessage());
            return null;
        }
    }
}
?>