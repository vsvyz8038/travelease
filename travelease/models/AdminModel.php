<?php
/**
 * Admin Model
 * Handles all database operations related to admin users and authentication
 */

class AdminModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Authenticate admin user
     */
    public function authenticate($username, $password) {
        $sql = "SELECT * FROM admin_users WHERE username = :username LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create admin session
     */
    public function createSession($adminId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_SESSION_TIMEOUT);
        
        $sql = "INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (:admin_id, :token, :ip, :user_agent, :expires_at)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':admin_id' => $adminId,
                ':token' => $token,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':expires_at' => $expiresAt
            ]);
            
            return $token;
        } catch (PDOException $e) {
            error_log("Create session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate session token
     */
    public function validateSession($token) {
        $sql = "SELECT s.*, u.id as admin_id, u.username, u.email 
                FROM admin_sessions s 
                JOIN admin_users u ON s.admin_id = u.id 
                WHERE s.session_token = :token 
                AND s.expires_at > NOW() 
                LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Validate session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update session expiry
     */
    public function updateSessionExpiry($token) {
        $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_SESSION_TIMEOUT);
        $sql = "UPDATE admin_sessions SET expires_at = :expires_at WHERE session_token = :token";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':expires_at' => $expiresAt,
                ':token' => $token
            ]);
        } catch (PDOException $e) {
            error_log("Update session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy session
     */
    public function destroySession($token) {
        $sql = "DELETE FROM admin_sessions WHERE session_token = :token";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':token' => $token]);
        } catch (PDOException $e) {
            error_log("Destroy session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions() {
        $sql = "DELETE FROM admin_sessions WHERE expires_at < NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Clean sessions error: " . $e->getMessage());
            return false;
        }
    }
}
?>