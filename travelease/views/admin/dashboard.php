<?php
// Helper function to format trip type
function formatTripType($tripType) {
    $types = [
        'one_way' => 'One Way Transfer',
        'return' => 'Return Transfer',
        'airport_arrival' => 'Airport Arrival',
        'airport_departure' => 'Airport Departure',
        'half_day' => 'Half Day Service',
        'full_day' => 'Full Day Service',
        '24_hours' => '24 Hours Service'
    ];
    return $types[$tripType] ?? ucwords(str_replace('_', ' ', $tripType));
}

// Helper function to get booking details
function getBookingDetails($booking) {
    $details = [];
    
    if (!empty($booking['date'])) $details[] = '<strong>Date:</strong> ' . date('M d, Y', strtotime($booking['date']));
    if (!empty($booking['pickup_time'])) $details[] = '<strong>Pickup Time:</strong> ' . date('g:i A', strtotime($booking['pickup_time']));
    if (!empty($booking['pickup_location'])) $details[] = '<strong>Pickup:</strong> ' . htmlspecialchars($booking['pickup_location']);
    if (!empty($booking['dropoff_location'])) $details[] = '<strong>Drop-off:</strong> ' . htmlspecialchars($booking['dropoff_location']);
    if (!empty($booking['return_pickup_location'])) $details[] = '<strong>Return Pickup:</strong> ' . htmlspecialchars($booking['return_pickup_location']);
    if (!empty($booking['return_time'])) $details[] = '<strong>Return Time:</strong> ' . date('g:i A', strtotime($booking['return_time']));
    if (!empty($booking['airport_name'])) $details[] = '<strong>Airport:</strong> ' . htmlspecialchars($booking['airport_name']);
    if (!empty($booking['terminal'])) $details[] = '<strong>Terminal:</strong> ' . htmlspecialchars($booking['terminal']);
    if (!empty($booking['flight_number'])) $details[] = '<strong>Flight:</strong> ' . htmlspecialchars($booking['flight_number']);
    if (!empty($booking['arrival_time'])) $details[] = '<strong>Arrival:</strong> ' . date('g:i A', strtotime($booking['arrival_time']));
    if (!empty($booking['start_time'])) $details[] = '<strong>Start:</strong> ' . date('g:i A', strtotime($booking['start_time']));
    if (!empty($booking['end_time'])) $details[] = '<strong>End:</strong> ' . date('g:i A', strtotime($booking['end_time']));
    if (!empty($booking['passengers'])) $details[] = '<strong>Passengers:</strong> ' . $booking['passengers'];
    if (!empty($booking['vehicle_type'])) $details[] = '<strong>Vehicle:</strong> ' . htmlspecialchars(ucwords(str_replace('_', ' ', $booking['vehicle_type'])));
    
    return implode('<br>', $details);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TravelEase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1a1a1a; }
        .header { background: white; padding: 1.5rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #0a2540; font-size: 1.8rem; font-weight: 700; }
        .header-right { display: flex; align-items: center; gap: 2rem; }
        .user-info { color: #525f7f; font-size: 0.95rem; }
        .logout-btn { background: #dc3545; color: white; padding: 0.6rem 1.5rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: #c82333; }
        .container { max-width: 1600px; margin: 2rem auto; padding: 0 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card h3 { color: #525f7f; font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem; }
        .stat-card .number { color: #0a2540; font-size: 2.5rem; font-weight: 700; }
        .stat-card.pending .number { color: #ffc107; }
        .stat-card.confirmed .number { color: #28a745; }
        .stat-card.completed .number { color: #007bff; }
        .stat-card.cancelled .number { color: #dc3545; }
        .filter-section { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .filter-section h3 { color: #0a2540; margin-bottom: 1rem; font-size: 1.2rem; }
        .filter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .filter-buttons { display: flex; gap: 1rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.6rem 1.5rem; border: 2px solid #e0e7ef; background: white; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #525f7f; text-decoration: none; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: #0a2540; color: white; border-color: #0a2540; }
        .export-btn { background: #28a745; color: white; padding: 0.6rem 1.5rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s; }
        .export-btn:hover { background: #218838; transform: translateY(-2px); }
        .bookings-table { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { padding: 1.5rem; border-bottom: 1px solid #e0e7ef; }
        .table-header h2 { color: #0a2540; font-size: 1.3rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 1rem; text-align: left; font-weight: 600; color: #0a2540; font-size: 0.9rem; }
        td { padding: 1rem; border-bottom: 1px solid #e0e7ef; color: #525f7f; font-size: 0.85rem; line-height: 1.6; }
        tr:hover { background: #f8f9fa; }
        .trip-type-badge { background: #e7f3ff; color: #0066cc; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .action-btn { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; font-size: 0.85rem; cursor: pointer; font-weight: 500; }
        .btn-confirm { background: #28a745; color: white; }
        .btn-complete { background: #007bff; color: white; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn-delete { background: #6c757d; color: white; }
        .action-btn:hover { opacity: 0.8; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; padding: 1.5rem; }
        .page-btn { padding: 0.5rem 1rem; border: 1px solid #e0e7ef; background: white; border-radius: 4px; cursor: pointer; text-decoration: none; color: #525f7f; font-weight: 500; }
        .page-btn:hover, .page-btn.active { background: #0a2540; color: white; border-color: #0a2540; }
        .empty-state { text-align: center; padding: 3rem; color: #525f7f; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 1rem; }
            .table-responsive { overflow-x: auto; }
            table { min-width: 1000px; }
            .filter-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TravelEase Admin Dashboard</h1>
        <div class="header-right">
            <div class="user-info">
                <i class="fas fa-user-circle"></i> 
                Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="number"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card confirmed">
                <h3>Confirmed</h3>
                <div class="number"><?php echo $stats['confirmed'] ?? 0; ?></div>
            </div>
            <div class="stat-card completed">
                <h3>Completed</h3>
                <div class="number"><?php echo $stats['completed'] ?? 0; ?></div>
            </div>
        </div>

        <div class="filter-section">
            <div class="filter-header">
                <h3>Filter by Status</h3>
                <button onclick="exportToExcel()" class="export-btn">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
            <div class="filter-buttons">
                <a href="?page=1" class="filter-btn <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">All Bookings</a>
                <a href="?status=pending&page=1" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'active' : ''; ?>">Pending</a>
                <a href="?status=confirmed&page=1" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'confirmed') ? 'active' : ''; ?>">Confirmed</a>
                <a href="?status=completed&page=1" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'active' : ''; ?>">Completed</a>
                <a href="?status=cancelled&page=1" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'active' : ''; ?>">Cancelled</a>
            </div>
        </div>

        <div class="bookings-table">
            <div class="table-header">
                <h2>Recent Bookings</h2>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No bookings found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Trip Type</th>
                                <th>Booking Details</th>
                                <th>Status</th>
                                <th>Booked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr data-booking-id="<?php echo $booking['id']; ?>">
                                    <td>
                                        <span class="trip-type-badge">
                                            <?php echo formatTripType($booking['trip_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo getBookingDetails($booking); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <button class="action-btn btn-confirm" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                    <i class="fas fa-check"></i> Confirm
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] === 'confirmed'): ?>
                                                <button class="action-btn btn-complete" onclick="updateStatus(<?php echo $booking['id']; ?>, 'completed')">
                                                    <i class="fas fa-flag-checkered"></i> Complete
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] !== 'cancelled'): ?>
                                                <button class="action-btn btn-cancel" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="action-btn btn-delete" onclick="deleteBooking(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" 
                               class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" class="page-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportToExcel() {
            const currentStatus = new URLSearchParams(window.location.search).get('status') || '';
            const exportUrl = '<?php echo BASE_URL; ?>/admin/export-bookings.php' + (currentStatus ? '?status=' + currentStatus : '');
            window.location.href = exportUrl;
        }

        function updateStatus(bookingId, status) {
            if (!confirm(`Are you sure you want to mark this booking as ${status}?`)) return;
            fetch('<?php echo BASE_URL; ?>/admin/update-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `booking_id=${bookingId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.message);
            })
            .catch(error => alert('An error occurred. Please try again.'));
        }

        function deleteBooking(bookingId) {
            if (!confirm('Are you sure you want to delete this booking? This action cannot be undone.')) return;
            fetch('<?php echo BASE_URL; ?>/admin/delete-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `booking_id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.message);
            })
            .catch(error => alert('An error occurred. Please try again.'));
        }
    </script>
</body>
</html>