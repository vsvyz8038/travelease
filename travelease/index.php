<?php
/**
 * Main Entry Point with Payment Integration
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/models/AdminModel.php';
require_once __DIR__ . '/models/PricingModel.php';
require_once __DIR__ . '/models/PaymentModel.php';
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/AdminController.php';

// Get Razorpay key for frontend
$pricingModel = new PricingModel();
$razorpayKeyId = $pricingModel->getPaymentConfig('razorpay_key_id');
$paymentEnabled = $pricingModel->getPaymentConfig('payment_enabled');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelEase - Premium Trip Management Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: #ffffff;
        }

        header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 1.2rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        nav {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 3rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0a2540;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 3rem;
        }

        .nav-links a {
            color: #525f7f;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.3s;
            letter-spacing: 0.2px;
        }

        .nav-links a:hover {
            color: #0a2540;
        }

        .hero {
            background: linear-gradient(135deg, #0a2540 0%, #1a4d7a 100%);
            color: white;
            padding: 10rem 3rem 6rem;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80') center/cover;
            opacity: 0.15;
            z-index: 0;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -1px;
        }

        .hero-text p {
            font-size: 1.25rem;
            opacity: 0.9;
            font-weight: 300;
            line-height: 1.8;
            color: #e0e7ef;
        }

        .hero-booking-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-height: 85vh;
            overflow-y: auto;
        }

        .hero-booking-card h3 {
            color: #0a2540;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .price-display {
            background: linear-gradient(135deg, #e7f3ff 0%, #d4e9ff 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 2px solid #0066cc;
            display: none;
        }

        .price-display.show {
            display: block;
        }

        .price-display h4 {
            color: #0a2540;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .price-display .amount {
            color: #0066cc;
            font-size: 2rem;
            font-weight: 700;
        }

        .price-display .description {
            color: #525f7f;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 6rem 3rem;
        }

        .section {
            margin-bottom: 0;
            padding: 6rem 0;
        }

        .section.alt-bg {
            background: linear-gradient(135deg, rgba(10, 37, 64, 0.03) 0%, rgba(26, 77, 122, 0.05) 100%);
            position: relative;
        }

        .section.alt-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(10, 37, 64, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(26, 77, 122, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }

        .section.bg-pattern {
            background: linear-gradient(135deg, #0a2540 0%, #1a4d7a 100%);
            position: relative;
            overflow: hidden;
        }

        .section.bg-pattern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1464037866556-6812c9d1c72e?w=1600&q=80') center/cover;
            opacity: 0.08;
            z-index: 0;
        }

        .section.bg-pattern h2,
        .section.bg-pattern .section-subtitle {
            color: white;
            position: relative;
            z-index: 1;
        }

        .section.bg-image {
            background: linear-gradient(rgba(10, 37, 64, 0.92), rgba(26, 77, 122, 0.92)), 
                        url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=1600&q=80') center/cover fixed;
            position: relative;
        }

        .section.bg-image h2,
        .section.bg-image .section-subtitle {
            color: white;
            position: relative;
            z-index: 1;
        }

        .section h2 {
            font-size: 2.75rem;
            margin-bottom: 1rem;
            color: #0a2540;
            text-align: center;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .section-subtitle {
            text-align: center;
            color: #525f7f;
            margin-bottom: 4rem;
            font-size: 1.15rem;
            font-weight: 400;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .about-content {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
            font-size: 1.1rem;
            color: #525f7f;
            line-height: 1.9;
            background: white;
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .service-card {
            background: white;
            padding: 3rem 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid #f0f2f5;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0a2540 0%, #1a4d7a 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .service-icon i {
            font-size: 2rem;
            color: white;
        }

        .service-card h3 {
            color: #0a2540;
            margin-bottom: 1rem;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .service-card p {
            color: #525f7f;
            line-height: 1.7;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            color: #0a2540;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1.5px solid #e0e7ef;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: #fafbfc;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0a2540;
            background: white;
            box-shadow: 0 0 0 3px rgba(10, 37, 64, 0.05);
        }

        .submit-btn {
            background: linear-gradient(135deg, #0a2540 0%, #1a4d7a 100%);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            letter-spacing: 0.3px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(10, 37, 64, 0.3);
        }

        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .contact-section {
            padding: 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .contact-card {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .contact-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0a2540 0%, #1a4d7a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .contact-icon i {
            font-size: 2rem;
            color: white;
        }

        .contact-card h3 {
            color: #0a2540;
            margin-bottom: 0.8rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .contact-card p {
            color: #525f7f;
            font-size: 1rem;
            line-height: 1.6;
        }

        footer {
            background: #0a2540;
            color: #e0e7ef;
            text-align: center;
            padding: 3rem 2rem;
        }

        footer p {
            font-weight: 300;
            font-size: 0.95rem;
        }

        .success-message {
            display: none;
            background: #d4f4dd;
            color: #1e7e34;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #9ee2b0;
            font-weight: 500;
        }

        .stats-section {
            background: linear-gradient(135deg, #0a2540 0%, #1a4d7a 100%);
            padding: 5rem 3rem;
            color: white;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .field-optional {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 400;
        }

        @media (max-width: 968px) {
            .hero-content {
                grid-template-columns: 1fr;
            }
            
            .hero-text h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                gap: 1.5rem;
            }
            
            nav {
                padding: 0 1.5rem;
            }
            
            .container {
                padding: 4rem 1.5rem;
            }

            .hero-booking-card {
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">TravelEase</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Premium Trip Management Services</h1>
                <p>Experience excellence in corporate and leisure travel with our world-class fleet and professional chauffeurs. Your journey, perfectly orchestrated.</p>
            </div>
            
            <div class="hero-booking-card">
                <h3>Book Your Journey</h3>
                <div id="successMessage" class="success-message">
                    <i class="fas fa-check-circle"></i> Thank you! Your booking has been confirmed successfully.
                </div>
                
                <div id="priceDisplay" class="price-display">
                    <h4>Trip Cost</h4>
                    <div class="amount" id="priceAmount">₹0</div>
                    <div class="description" id="priceDescription"></div>
                </div>
                
                <form id="bookingForm">
                    <div class="form-group">
                        <label for="tripType">Trip Type</label>
                        <select id="tripType" name="trip_type" required>
                            <option value="">Select Trip Type</option>
                            <option value="one_way">One Way Transfer</option>
                            <option value="return">Return Transfer</option>
                            <option value="airport_arrival">Airport Transfer - Arrival</option>
                            <option value="airport_departure">Airport Transfer - Departure</option>
                            <option value="half_day">Half Day Service</option>
                            <option value="full_day">Full Day Service</option>
                            <option value="24_hours">24 Hours Service</option>
                        </select>
                    </div>

                    <div id="dynamicFields"></div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-lock"></i> Proceed to Payment
                    </button>
                </form>
            </div>
        </div>
    </section>

    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>10+</h3>
                <p>Years of Excellence</p>
            </div>
            <div class="stat-item">
                <h3>50K+</h3>
                <p>Happy Clients</p>
            </div>
            <div class="stat-item">
                <h3>500+</h3>
                <p>Premium Vehicles</p>
            </div>
            <div class="stat-item">
                <h3>24/7</h3>
                <p>Customer Support</p>
            </div>
        </div>
    </div>

    <div class="container">
        <section id="about" class="section bg-pattern">
            <div style="max-width: 1400px; margin: 0 auto; padding: 0 3rem; position: relative; z-index: 1;">
                <h2>About TravelEase</h2>
                <p class="section-subtitle">Your Trusted Partner in Premium Travel Management</p>
                <div class="about-content">
                    <p>TravelEase stands at the forefront of premium trip management services, serving Fortune 500 companies and discerning travelers worldwide. With over a decade of excellence, we have redefined corporate and leisure travel through our commitment to precision, reliability, and unparalleled customer service.</p>
                </div>
            </div>
        </section>

        <section id="services" class="section alt-bg">
            <div style="max-width: 1400px; margin: 0 auto; padding: 0 3rem; position: relative; z-index: 1;">
                <h2>Our Services</h2>
                <p class="section-subtitle">Comprehensive travel solutions tailored to your needs</p>
                <div class="services-grid">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-plane-departure"></i>
                        </div>
                        <h3>Airport Transfers</h3>
                        <p>Punctual and luxurious airport pickup and drop-off services with flight tracking.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-city"></i>
                        </div>
                        <h3>City Tours</h3>
                        <p>Experience cities with our curated tour packages and expert guides.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3>Corporate Travel</h3>
                        <p>Dedicated solutions with flexible scheduling and priority support.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>Event Transportation</h3>
                        <p>Specialized transportation for weddings and conferences.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <h3>Outstation Travel</h3>
                        <p>Long-distance journeys made comfortable with luxury vehicles.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Hourly Rentals</h3>
                        <p>Flexible hourly rental options for your convenience.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="section bg-image">
            <div style="max-width: 1400px; margin: 0 auto; padding: 0 3rem; position: relative; z-index: 1;">
                <h2>Get In Touch</h2>
                <p class="section-subtitle">We're here to serve you 24/7</p>
                <div class="contact-section">
                    <div class="contact-grid">
                        <div class="contact-card">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3>Our Location</h3>
                            <p>123 Business Avenue<br>New York, NY 10001</p>
                        </div>
                        <div class="contact-card">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h3>Phone</h3>
                            <p>Main: +1 (555) 123-4567<br>Available 24/7</p>
                        </div>
                        <div class="contact-card">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3>Email</h3>
                            <p>bookings@travelease.com<br>support@travelease.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer>
        <p>&copy; 2025 TravelEase Premium Services. All rights reserved.</p>
    </footer>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const RAZORPAY_KEY = '<?php echo $razorpayKeyId; ?>';
        
        let currentPricing = null;
        let currentBookingData = null;

        const tripTypeFields = {
            one_way: [
                { name: 'date', label: 'Date', type: 'date', required: true },
                { name: 'pickup_time', label: 'Pick Up Time', type: 'time', required: true },
                { name: 'pickup_location', label: 'Pick Up Location', type: 'text', required: true },
                { name: 'dropoff_location', label: 'Drop Off Location', type: 'text', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: false, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'special_requests', label: 'Special Requests', type: 'textarea', required: false }
            ],
            return: [
                { name: 'date', label: 'Outbound Date', type: 'date', required: true },
                { name: 'pickup_time', label: 'Outbound Pick Up Time', type: 'time', required: true },
                { name: 'pickup_location', label: 'Pick Up Location', type: 'text', required: true },
                { name: 'dropoff_location', label: 'Drop Off Location', type: 'text', required: true },
                { name: 'return_date', label: 'Return Date', type: 'date', required: true },
                { name: 'return_time', label: 'Return Time', type: 'time', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: false, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'special_requests', label: 'Special Requests', type: 'textarea', required: false }
            ],
            airport_arrival: [
                { name: 'date', label: 'Arrival Date', type: 'date', required: true },
                { name: 'airport_name', label: 'Airport Name', type: 'text', required: true },
                { name: 'terminal', label: 'Terminal', type: 'text', required: true },
                { name: 'arrival_time', label: 'Arrival Time', type: 'time', required: true },
                { name: 'flight_number', label: 'Flight Number', type: 'text', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: false, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'dropoff_location', label: 'Drop Off Location', type: 'text', required: true },
                { name: 'special_requests', label: 'Special Requests', type: 'textarea', required: false }
            ],
            airport_departure: [
                { name: 'date', label: 'Departure Date', type: 'date', required: true },
                { name: 'pickup_location', label: 'Pick Up Location', type: 'text', required: true },
                { name: 'pickup_time', label: 'Pick Up Time', type: 'time', required: true },
                { name: 'airport_name', label: 'Airport Name', type: 'text', required: true },
                { name: 'terminal', label: 'Terminal', type: 'text', required: true },
                { name: 'flight_number', label: 'Flight Number', type: 'text', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: false, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'special_requests', label: 'Special Requests', type: 'textarea', required: false }
            ],
            half_day: [
                { name: 'date', label: 'Service Date', type: 'date', required: true },
                { name: 'pickup_location', label: 'Pick Up Location', type: 'text', required: true },
                { name: 'start_time', label: 'Start Time', type: 'time', required: true },
                { name: 'end_time', label: 'End Time', type: 'time', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: true, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'itinerary', label: 'Itinerary', type: 'textarea', required: false }
            ],
            full_day: [
                { name: 'date', label: 'Service Date', type: 'date', required: true },
                { name: 'pickup_location', label: 'Pick Up Location', type: 'text', required: true },
                { name: 'start_time', label: 'Start Time', type: 'time', required: true },
                { name: 'end_time', label: 'End Time', type: 'time', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: true, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'itinerary', label: 'Itinerary', type: 'textarea', required: false }
            ],
            '24_hours': [
                { name: 'date', label: 'Service Start Date', type: 'date', required: true },
                { name: 'pickup_location', label: 'Pick Up Location', type: 'text', required: true },
                { name: 'start_time', label: 'Start Time', type: 'time', required: true },
                { name: 'end_time', label: 'End Time (Next Day)', type: 'time', required: true },
                { name: 'passengers', label: 'Number of Passengers', type: 'number', required: true, min: 1 },
                { name: 'vehicle_type', label: 'Vehicle Type', type: 'select', required: true, options: ['Sedan', 'SUV', 'Van', 'Luxury Car', 'Mini Bus'] },
                { name: 'itinerary', label: 'Itinerary', type: 'textarea', required: false }
            ]
        };

        async function fetchPrice(tripType) {
            try {
                const response = await fetch(`${BASE_URL}/api/get-price.php?trip_type=${tripType}`);
                const result = await response.json();
                
                if (result.success) {
                    currentPricing = result.pricing;
                    document.getElementById('priceAmount').textContent = 
                        `${result.pricing.currency_symbol}${result.pricing.base_price.toFixed(2)}`;
                    document.getElementById('priceDescription').textContent = result.pricing.description;
                    document.getElementById('priceDisplay').classList.add('show');
                } else {
                    document.getElementById('priceDisplay').classList.remove('show');
                }
            } catch (error) {
                console.error('Error fetching price:', error);
            }
        }

        function generateFields(tripType) {
            const container = document.getElementById('dynamicFields');
            container.innerHTML = '';

            if (!tripType || !tripTypeFields[tripType]) {
                return;
            }

            const fields = tripTypeFields[tripType];
            fields.forEach(field => {
                const formGroup = document.createElement('div');
                formGroup.className = 'form-group';

                const label = document.createElement('label');
                label.setAttribute('for', field.name);
                label.innerHTML = field.label + (field.required ? '' : ' <span class="field-optional">(Optional)</span>');
                formGroup.appendChild(label);

                let input;
                if (field.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.setAttribute('placeholder', `Enter ${field.label.toLowerCase()}`);
                } else if (field.type === 'select') {
                    input = document.createElement('select');
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = `Select ${field.label}`;
                    input.appendChild(defaultOption);
                    
                    if (field.options) {
                        field.options.forEach(option => {
                            const opt = document.createElement('option');
                            opt.value = option.toLowerCase().replace(/\s+/g, '_');
                            opt.textContent = option;
                            input.appendChild(opt);
                        });
                    }
                } else {
                    input = document.createElement('input');
                    input.setAttribute('type', field.type);
                    if (field.min) input.setAttribute('min', field.min);
                    if (field.type === 'date') {
                        const today = new Date().toISOString().split('T')[0];
                        input.setAttribute('min', today);
                    }
                }

                input.setAttribute('id', field.name);
                input.setAttribute('name', field.name);
                if (field.required) {
                    input.setAttribute('required', 'required');
                }

                formGroup.appendChild(input);
                container.appendChild(formGroup);
            });
        }

        document.getElementById('tripType').addEventListener('change', function() {
            generateFields(this.value);
            if (this.value) {
                fetchPrice(this.value);
            } else {
                document.getElementById('priceDisplay').classList.remove('show');
            }
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            const formData = new FormData(this);
            currentBookingData = Object.fromEntries(formData);
            
            try {
                const response = await fetch(`${BASE_URL}/api/booking.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(currentBookingData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    initiatePayment(result.booking_id, currentPricing.base_price);
                } else {
                    let errorMessage = 'Please check the following errors:\n\n';
                    if (result.errors) {
                        for (const [field, error] of Object.entries(result.errors)) {
                            errorMessage += `• ${error}\n`;
                        }
                    } else {
                        errorMessage = result.message;
                    }
                    alert(errorMessage);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
            }
        });

        function initiatePayment(bookingId, amount) {
            const options = {
                key: RAZORPAY_KEY,
                amount: Math.round(amount * 100),
                currency: 'INR',
                name: 'TravelEase',
                description: 'Trip Booking Payment',
                image: '',
                handler: function (response) {
                    verifyPayment(bookingId, response);
                },
                prefill: {
                    name: currentBookingData.pickup_location || '',
                    email: '',
                    contact: ''
                },
                notes: {
                    booking_id: bookingId
                },
                theme: {
                    color: '#0a2540'
                },
                modal: {
                    ondismiss: function() {
                        document.getElementById('submitBtn').disabled = false;
                        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
                        alert('Payment cancelled. Your booking is saved and you can complete payment later.');
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
        }

        async function verifyPayment(bookingId, paymentResponse) {
            try {
                const response = await fetch(`${BASE_URL}/payment-callback.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        booking_id: bookingId,
                        razorpay_payment_id: paymentResponse.razorpay_payment_id,
                        razorpay_order_id: paymentResponse.razorpay_order_id || '',
                        razorpay_signature: paymentResponse.razorpay_signature || ''
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.querySelector('.hero-booking-card').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    setTimeout(() => {
                        document.getElementById('successMessage').style.display = 'block';
                        document.getElementById('bookingForm').reset();
                        document.getElementById('dynamicFields').innerHTML = '';
                        document.getElementById('priceDisplay').classList.remove('show');
                        document.getElementById('submitBtn').disabled = false;
                        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
                    }, 300);
                    
                    setTimeout(() => {
                        document.getElementById('successMessage').style.display = 'none';
                    }, 5500);
                } else {
                    alert('Payment verification failed. Please contact support.');
                    document.getElementById('submitBtn').disabled = false;
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during payment verification.');
                document.getElementById('submitBtn').disabled = false;
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-lock"></i> Proceed to Payment';
            }
        }
    </script>
</body>
</html>