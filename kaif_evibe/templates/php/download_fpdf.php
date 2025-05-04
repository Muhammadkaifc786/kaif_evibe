<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FPDF download URL
$fpdf_url = 'http://www.fpdf.org/en/download/fpdf184.zip';

// Target directory
$target_dir = __DIR__ . '/../vendor/fpdf';

// Create directory if it doesn't exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Download FPDF
echo "Downloading FPDF...<br>";
$zip_file = $target_dir . '/fpdf.zip';
file_put_contents($zip_file, file_get_contents($fpdf_url));

// Extract ZIP file
echo "Extracting FPDF...<br>";
$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo($target_dir);
    $zip->close();
    echo "FPDF extracted successfully.<br>";
} else {
    echo "Failed to extract FPDF.<br>";
}

// Clean up
unlink($zip_file);

// Verify installation
if (file_exists($target_dir . '/fpdf.php')) {
    echo "FPDF installed successfully!<br>";
} else {
    echo "FPDF installation failed. Please check the directory: " . $target_dir . "<br>";
}
?> 