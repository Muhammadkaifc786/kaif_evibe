<?php
// Ensure no output before headers
ob_start();

require_once 'db_connection.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

class PDF extends FPDF {
    // Page header
    function Header() {
        // Logo
        $logo_path = __DIR__ . '/../assets/images/logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 6, 30);
        }
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'EVIBE - Event Booking Receipt', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

function generateReceipt($booking_id) {
    global $conn;
    
    try {
        // Fetch booking details with event and user information
        $query = "SELECT 
            b.*, 
            e.title as event_name, 
            e.event_date, 
            e.venue as location, 
            e.ticket_price,
            u.fullname as username,
            u.email
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            throw new Exception('Booking not found');
        }
        
        // Create PDF
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('Arial', '', 12);
        
        // Receipt Header
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'BOOKING RECEIPT', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Booking Details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 10, 'Booking ID:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, $booking['ticket_id'], 0, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 10, 'Date:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, date('Y-m-d H:i:s'), 0, 1);
        
        $pdf->Ln(5);
        
        // Event Details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Event Details', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(40, 10, 'Event Name:', 0);
        $pdf->Cell(0, 10, $booking['event_name'], 0, 1);
        
        $pdf->Cell(40, 10, 'Date:', 0);
        $pdf->Cell(0, 10, date('F j, Y', strtotime($booking['event_date'])), 0, 1);
        
        $pdf->Cell(40, 10, 'Location:', 0);
        $pdf->Cell(0, 10, $booking['location'], 0, 1);
        
        $pdf->Ln(5);
        
        // User Details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'User Details', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(40, 10, 'Name:', 0);
        $pdf->Cell(0, 10, $booking['username'], 0, 1);
        
        $pdf->Cell(40, 10, 'Email:', 0);
        $pdf->Cell(0, 10, $booking['email'], 0, 1);
        
        $pdf->Ln(5);
        
        // Booking Summary
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Booking Summary', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(40, 10, 'Tickets:', 0);
        $pdf->Cell(0, 10, $booking['ticket_count'], 0, 1);
        
        $pdf->Cell(40, 10, 'Price per Ticket:', 0);
        $pdf->Cell(0, 10, '$' . number_format($booking['ticket_price'], 2), 0, 1);
        
        $pdf->Cell(40, 10, 'Total Price:', 0);
        $pdf->Cell(0, 10, '$' . number_format($booking['total_price'], 2), 0, 1);
        
        // Output the PDF
        return $pdf->Output('S');
        
    } catch (Exception $e) {
        error_log("Error generating receipt: " . $e->getMessage());
        throw $e;
    }
}

// Get booking ID or filename from request
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$filename = isset($_GET['filename']) ? $_GET['filename'] : '';

if ($booking_id <= 0 && empty($filename)) {
    die('Invalid request: No booking ID or filename provided');
}

try {
    // If filename is provided, extract booking_id from it
    if (!empty($filename)) {
        // Filename format: receipt_T{timestamp}{random}.pdf
        $ticket_id = substr($filename, 8, -4); // Remove 'receipt_' prefix and '.pdf' suffix
        
        // Get booking_id from ticket_id
        $stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE ticket_id = ?");
        $stmt->execute([$ticket_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception('Booking not found for the given ticket ID');
        }
        
        $booking_id = $result['booking_id'];
    }

    // Generate the receipt
    $pdf_content = generateReceipt($booking_id);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output the PDF
    echo $pdf_content;
    
} catch (Exception $e) {
    error_log("Error generating receipt: " . $e->getMessage());
    die('Error generating receipt: ' . $e->getMessage());
}

// Ensure no output after PDF
exit;
?> 