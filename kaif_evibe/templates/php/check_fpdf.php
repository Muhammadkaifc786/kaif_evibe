<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FPDF download URL
$fpdf_url = 'http://www.fpdf.org/en/download/fpdf184.zip';

// Target directory
$target_dir = __DIR__ . '/../vendor/fpdf';

// Check if FPDF is already installed
if (file_exists($target_dir . '/fpdf.php')) {
    echo "FPDF is already installed.\n";
    exit;
}

// Create directory if it doesn't exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Download FPDF
echo "Downloading FPDF...\n";
$zip_file = $target_dir . '/fpdf.zip';
file_put_contents($zip_file, file_get_contents($fpdf_url));

// Extract ZIP file
echo "Extracting FPDF...\n";
$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo($target_dir);
    $zip->close();
    echo "FPDF extracted successfully.\n";
} else {
    echo "Failed to extract FPDF.\n";
}

// Clean up
unlink($zip_file);

// Verify installation
if (file_exists($target_dir . '/fpdf.php')) {
    echo "FPDF installed successfully!\n";
} else {
    echo "FPDF installation failed. Please check the directory: " . $target_dir . "\n";
}
?> 