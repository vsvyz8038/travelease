<?php
/**
 * Booking Controller
 * Handles booking form submission and validation for different trip types
 */

class BookingController {
    private $bookingModel;
    
    public function __construct() {
        $this->bookingModel = new BookingModel();
    }
    
    /**
     * Handle booking form submission
     */
    public function create() {
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        // Validate and sanitize input
        $data = $this->validateBookingData($_POST);
        
        if (!$data['valid']) {
            $this->jsonResponse(['success' => false, 'errors' => $data['errors']], 400);
            return;
        }
        
        // Create booking
        $bookingId = $this->bookingModel->createBooking($data['data']);
        
        if ($bookingId) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Booking request submitted successfully!',
                'booking_id' => $bookingId
            ], 201);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to create booking. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Validate booking data based on trip type
     */
    private function validateBookingData($input) {
        $errors = [];
        $data = [];
        
        // Trip Type validation
        if (empty($input['trip_type'])) {
            $errors['trip_type'] = 'Trip type is required';
            return ['valid' => false, 'errors' => $errors, 'data' => []];
        }
        
        $tripType = $input['trip_type'];
        $data['trip_type'] = $tripType;
        
        // Define required fields for each trip type
        $requiredFields = $this->getRequiredFields($tripType);
        
        // Validate each field
        foreach ($requiredFields as $field => $rules) {
            if ($rules['required'] && empty($input[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                continue;
            }
            
            if (!empty($input[$field])) {
                $value = $this->sanitizeField($input[$field], $rules['type']);
                
                // Additional validation based on type
                if ($rules['type'] === 'number' && ($value < 1 || $value > 50)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be between 1 and 50';
                } elseif ($rules['type'] === 'date' && strtotime($value) < strtotime('today')) {
                    $errors[$field] = 'Date cannot be in the past';
                } else {
                    $data[$field] = $value;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }
    
    /**
     * Get required fields for each trip type
     */
    private function getRequiredFields($tripType) {
        $fieldDefinitions = [
            'one_way' => [
                'date' => ['required' => true, 'type' => 'date'],
                'pickup_time' => ['required' => true, 'type' => 'time'],
                'pickup_location' => ['required' => true, 'type' => 'text'],
                'dropoff_location' => ['required' => true, 'type' => 'text'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => false, 'type' => 'text'],
                'special_requests' => ['required' => false, 'type' => 'textarea']
            ],
            'return' => [
                'date' => ['required' => true, 'type' => 'date'],
                'pickup_time' => ['required' => true, 'type' => 'time'],
                'pickup_location' => ['required' => true, 'type' => 'text'],
                'dropoff_location' => ['required' => true, 'type' => 'text'],
                'return_pickup_location' => ['required' => true, 'type' => 'text'],
                'return_time' => ['required' => true, 'type' => 'time'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => false, 'type' => 'text'],
                'special_requests' => ['required' => false, 'type' => 'textarea']
            ],
            'airport_arrival' => [
                'airport_name' => ['required' => true, 'type' => 'text'],
                'terminal' => ['required' => true, 'type' => 'text'],
                'arrival_time' => ['required' => true, 'type' => 'time'],
                'flight_number' => ['required' => true, 'type' => 'text'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => false, 'type' => 'text'],
                'dropoff_location' => ['required' => true, 'type' => 'text']
            ],
            'airport_departure' => [
                'pickup_location' => ['required' => true, 'type' => 'text'],
                'pickup_time' => ['required' => true, 'type' => 'time'],
                'airport_name' => ['required' => true, 'type' => 'text'],
                'terminal' => ['required' => true, 'type' => 'text'],
                'flight_number' => ['required' => true, 'type' => 'text'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => false, 'type' => 'text']
            ],
            'half_day' => [
                'pickup_location' => ['required' => true, 'type' => 'text'],
                'start_time' => ['required' => true, 'type' => 'time'],
                'end_time' => ['required' => true, 'type' => 'time'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => true, 'type' => 'text'],
                'itinerary' => ['required' => false, 'type' => 'textarea']
            ],
            'full_day' => [
                'pickup_location' => ['required' => true, 'type' => 'text'],
                'start_time' => ['required' => true, 'type' => 'time'],
                'end_time' => ['required' => true, 'type' => 'time'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => true, 'type' => 'text'],
                'itinerary' => ['required' => false, 'type' => 'textarea']
            ],
            '24_hours' => [
                'pickup_location' => ['required' => true, 'type' => 'text'],
                'start_time' => ['required' => true, 'type' => 'time'],
                'end_time' => ['required' => true, 'type' => 'time'],
                'passengers' => ['required' => true, 'type' => 'number'],
                'vehicle_type' => ['required' => true, 'type' => 'text'],
                'itinerary' => ['required' => false, 'type' => 'textarea']
            ]
        ];
        
        return $fieldDefinitions[$tripType] ?? [];
    }
    
    /**
     * Sanitize field based on type
     */
    private function sanitizeField($value, $type) {
        switch ($type) {
            case 'number':
                return filter_var($value, FILTER_VALIDATE_INT);
            case 'date':
            case 'time':
            case 'text':
            case 'textarea':
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            default:
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
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