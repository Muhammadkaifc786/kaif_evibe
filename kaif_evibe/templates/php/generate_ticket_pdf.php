<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('User not logged in');
}

// Get booking ID from request
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) {
    die('Invalid booking ID');
}

try {
    // Include required files
    require_once 'db_connect.php';
    
    // Check if FPDF exists and include it
    $fpdf_path = __DIR__ . '/../vendor/fpdf/fpdf.php';
    if (!file_exists($fpdf_path)) {
        die('FPDF library not found. Please install it in: ' . $fpdf_path);
    }
    require_once $fpdf_path;
    
    // Create database connection
    $conn = connectDB();
    if (!$conn) {
        die('Database connection failed');
    }
    
    // Fetch booking details with event and user information
    $query = "SELECT 
        b.*, 
        e.title as event_name, 
        e.event_date, 
        e.venue as location, 
        e.ticket_price,
        e.image_url as event_image,
        u.fullname as username,
        u.email,
        u.contact_number as phone
    FROM bookings b
    JOIN events e ON b.event_id = e.event_id
    JOIN users u ON b.user_id = u.id
    WHERE b.booking_id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die('Failed to prepare query: ' . $conn->error);
    }
    
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        die('Booking not found or unauthorized');
    }

    // Create PDF
    class PDF extends FPDF {
        function Header() {
            // Logo
            $logo_path = __DIR__ . '/../images/logo.png';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 6, 30);
            }
            
            // Title
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(80);
            $this->Cell(30, 10, 'EVIBE - Event Ticket', 0, 0, 'C');
            $this->Ln(20);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

    // Create new PDF document
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('Arial', '', 12);
    
    // Ticket Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'EVENT TICKET', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Ticket ID and Date
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, 'Ticket ID:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, $booking['ticket_id'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, 'Booking Date:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, date('F j, Y', strtotime($booking['booking_date'])), 0, 1);
    
    $pdf->Ln(5);
    
    // Event Details
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Event Details', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $pdf->Cell(40, 10, 'Event Name:', 0);
    $pdf->Cell(0, 10, $booking['event_name'], 0, 1);
    
    $pdf->Cell(40, 10, 'Date:', 0);
    $pdf->Cell(0, 10, date('F j, Y', strtotime($booking['event_date'])), 0, 1);
    
    $pdf->Cell(40, 10, 'Location:', 0);
    $pdf->Cell(0, 10, $booking['location'], 0, 1);
    
    $pdf->Ln(5);
    
    // Attendee Details
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Attendee Details', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $pdf->Cell(40, 10, 'Name:', 0);
    $pdf->Cell(0, 10, $booking['username'], 0, 1);
    
    $pdf->Cell(40, 10, 'Email:', 0);
    $pdf->Cell(0, 10, $booking['email'], 0, 1);
    
    if ($booking['phone']) {
        $pdf->Cell(40, 10, 'Phone:', 0);
        $pdf->Cell(0, 10, $booking['phone'], 0, 1);
    }
    
    $pdf->Ln(5);
    
    // Booking Summary
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Booking Summary', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $pdf->Cell(40, 10, 'Tickets:', 0);
    $pdf->Cell(0, 10, $booking['ticket_count'], 0, 1);
    
    $pdf->Cell(40, 10, 'Price per Ticket:', 0);
    $pdf->Cell(0, 10, '$' . number_format($booking['ticket_price'], 2), 0, 1);
    
    $pdf->Cell(40, 10, 'Total Amount:', 0);
    $pdf->Cell(0, 10, '$' . number_format($booking['total_price'], 2), 0, 1);
    
    // Add QR Code or Barcode (if you have a library for this)
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'Please present this ticket at the event venue', 0, 1, 'C');
    
    // Generate filename
    $filename = 'ticket_' . $booking['ticket_id'] . '.pdf';
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output the PDF
    $pdf->Output('I', $filename);
    
} catch (Exception $e) {
    error_log("Error generating ticket PDF: " . $e->getMessage());
    die('Error generating ticket: ' . $e->getMessage());
}

// Ensure no output after PDF
exit;
?> 