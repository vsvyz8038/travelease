<?php
/**
 * Admin Controller
 * Handles admin authentication and dashboard operations
 */

class AdminController {
    private $adminModel;
    private $bookingModel;
    
    public function __construct() {
        $this->adminModel = new AdminModel();
        $this->bookingModel = new BookingModel();
        $this->startSession();
    }
    
    /**
     * Start secure session
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }
    
    /**
     * Display login page
     */
    public function login() {
        if ($this->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        }
        
        include __DIR__ . '/../views/admin/login.php';
    }
    
    /**
     * Handle login authentication
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username and password are required';
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
        
        $user = $this->adminModel->authenticate($username, $password);
        
        if ($user) {
            $token = $this->adminModel->createSession($user['id']);
            
            if ($token) {
                $_SESSION['admin_token'] = $token;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
                exit;
            }
        }
        
        $_SESSION['error'] = 'Invalid username or password';
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    
    /**
     * Display dashboard
     */
    public function dashboard() {
        if (!$this->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
        
        $page = $_GET['page'] ?? 1;
        $status = $_GET['status'] ?? null;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $bookings = $this->bookingModel->getAllBookings($limit, $offset, $status);
        $totalBookings = $this->bookingModel->getTotalBookings($status);
        $stats = $this->bookingModel->getBookingStats();
        $totalPages = ceil($totalBookings / $limit);
        
        include __DIR__ . '/../views/admin/dashboard.php';
    }
    
    /**
     * Update booking status
     */
    public function updateStatus() {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }
        
        $bookingId = $_POST['booking_id'] ?? null;
        $status = $_POST['status'] ?? null;
        
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
        
        if (!$bookingId || !$status || !in_array($status, $validStatuses)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
            return;
        }
        
        $result = $this->bookingModel->updateBookingStatus($bookingId, $status);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }
    
    /**
     * Delete booking
     */
    public function deleteBooking() {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }
        
        $bookingId = $_POST['booking_id'] ?? null;
        
        if (!$bookingId) {
            $this->jsonResponse(['success' => false, 'message' => 'Booking ID required'], 400);
            return;
        }
        
        $result = $this->bookingModel->deleteBooking($bookingId);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Booking deleted successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete booking'], 500);
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        if (isset($_SESSION['admin_token'])) {
            $this->adminModel->destroySession($_SESSION['admin_token']);
        }
        
        session_destroy();
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    
    /**
     * Check if user is authenticated
     */
    private function isAuthenticated() {
        if (!isset($_SESSION['admin_token'])) {
            return false;
        }
        
        $session = $this->adminModel->validateSession($_SESSION['admin_token']);
        
        if ($session) {
            $this->adminModel->updateSessionExpiry($_SESSION['admin_token']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>