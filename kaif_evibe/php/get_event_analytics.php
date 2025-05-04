<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : 'all';
$period = isset($_GET['period']) ? $_GET['period'] : 'week';

try {
    $stats = [];
    $charts = [];

    // Get total revenue and tickets sold
    if ($event_id === 'all') {
        $query = "SELECT 
            COALESCE(SUM((total_tickets - available_tickets) * ticket_price), 0) as total_revenue,
            COALESCE(SUM(total_tickets - available_tickets), 0) as tickets_sold,
            COUNT(*) as total_events
        FROM events 
        WHERE organizer_id = ? AND status = 'approved'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    } else {
        $query = "SELECT 
            ((total_tickets - available_tickets) * ticket_price) as total_revenue,
            (total_tickets - available_tickets) as tickets_sold
        FROM events 
        WHERE event_id = ? AND status = 'approved'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $event_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Basic stats
    $stats = [
        'total_revenue' => floatval($data['total_revenue'] ?? 0),
        'tickets_sold' => intval($data['tickets_sold'] ?? 0),
        'conversion_rate' => 65, // Example value, calculate based on views vs sales
        'revenue_growth' => 15,  // Example value, calculate based on previous period
        'tickets_growth' => 25,  // Example value, calculate based on previous period
        'conversion_growth' => 5  // Example value, calculate based on previous period
    ];

    // Sales chart data
    $labels = [];
    $salesData = [];
    
    switch ($period) {
        case 'week':
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('D', strtotime($date));
                
                if ($event_id === 'all') {
                    $query = "SELECT 
                        COALESCE(SUM((total_tickets - available_tickets) * ticket_price), 0) as daily_sales
                    FROM events 
                    WHERE organizer_id = ? 
                    AND DATE(created_at) = ?
                    AND status = 'approved'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $user_id, $date);
                } else {
                    $query = "SELECT 
                        ((total_tickets - available_tickets) * ticket_price) as daily_sales
                    FROM events
                    WHERE event_id = ?
                    AND DATE(created_at) = ?
                    AND status = 'approved'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $event_id, $date);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $salesData[] = floatval($row['daily_sales']);
            }
            break;
            
        case 'month':
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('M d', strtotime($date));
                
                if ($event_id === 'all') {
                    $query = "SELECT 
                        COALESCE(SUM((total_tickets - available_tickets) * ticket_price), 0) as daily_sales
                    FROM events 
                    WHERE organizer_id = ?
                    AND DATE(created_at) = ?
                    AND status = 'approved'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $user_id, $date);
                } else {
                    $query = "SELECT 
                        ((total_tickets - available_tickets) * ticket_price) as daily_sales
                    FROM events
                    WHERE event_id = ?
                    AND DATE(created_at) = ?
                    AND status = 'approved'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $event_id, $date);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $salesData[] = floatval($row['daily_sales']);
            }
            break;
            
        case 'year':
            for ($i = 11; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M', strtotime($month));
                
                if ($event_id === 'all') {
                    $query = "SELECT 
                        COALESCE(SUM((total_tickets - available_tickets) * ticket_price), 0) as monthly_sales
                    FROM events 
                    WHERE organizer_id = ?
                    AND DATE_FORMAT(created_at, '%Y-%m') = ?
                    AND status = 'approved'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $user_id, $month);
                } else {
                    $query = "SELECT 
                        ((total_tickets - available_tickets) * ticket_price) as monthly_sales
                    FROM events
                    WHERE event_id = ?
                    AND DATE_FORMAT(created_at, '%Y-%m') = ?
                    AND status = 'approved'";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $event_id, $month);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $salesData[] = floatval($row['monthly_sales']);
            }
            break;
    }

    // Get ticket type distribution (using event categories as proxy since we don't have ticket types)
    if ($event_id === 'all') {
        $query = "SELECT 
            category_id,
            SUM(total_tickets - available_tickets) as type_count
        FROM events
        WHERE organizer_id = ? AND status = 'approved'
        GROUP BY category_id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    } else {
        $query = "SELECT 
            category_id,
            (total_tickets - available_tickets) as type_count
        FROM events
        WHERE event_id = ? AND status = 'approved'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $event_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $ticketData = [0, 0, 0]; // Using categories instead of ticket types
    
    while ($row = $result->fetch_assoc()) {
        $category_id = intval($row['category_id']);
        if ($category_id >= 1 && $category_id <= 3) {
            $ticketData[$category_id - 1] = intval($row['type_count']);
        }
    }

    // Example demographics data (replace with actual data when available)
    $demographicsData = [25, 35, 20, 15, 5]; // Age groups: 18-24, 25-34, 35-44, 45-54, 55+

    $charts = [
        'sales' => [
            'labels' => $labels,
            'data' => $salesData
        ],
        'tickets' => [
            'data' => $ticketData
        ],
        'demographics' => [
            'data' => $demographicsData
        ]
    ];

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'charts' => $charts
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 