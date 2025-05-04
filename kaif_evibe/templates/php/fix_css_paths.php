<?php
$html_dir = __DIR__ . '/../html';
$files = glob($html_dir . '/*.html');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace absolute CSS paths with relative paths
    $content = preg_replace(
        '/href="\/css\/([^"]+)"/',
        'href="../css/$1"',
        $content
    );
    
    // Replace absolute image paths with relative paths
    $content = preg_replace(
        '/src="\/images\/([^"]+)"/',
        'src="../images/$1"',
        $content
    );
    
    // Replace absolute PHP paths with relative paths
    $content = preg_replace(
        '/action="\/php\/([^"]+)"/',
        'action="../php/$1"',
        $content
    );

    // Replace absolute HTML paths with relative paths
    $content = preg_replace(
        '/href="\/html\/([^"]+)"/',
        'href="$1"',
        $content
    );

    // Replace background image paths in style attributes
    $content = preg_replace(
        '/background: url\(\'\/images\/([^\']+)\'\)/',
        'background: url(\'../images/$1\')',
        $content
    );
    
    file_put_contents($file, $content);
    echo "Fixed paths in: " . basename($file) . "\n";
}

// Also fix CSS files
$css_dir = __DIR__ . '/../css';
$css_files = glob($css_dir . '/*.css');

foreach ($css_files as $file) {
    $content = file_get_contents($file);
    
    // Replace background image paths in CSS files
    $content = preg_replace(
        '/url\(\'\/images\/([^\']+)\'\)/',
        'url(\'../images/$1\')',
        $content
    );
    
    $content = preg_replace(
        '/url\("\/images\/([^"]+)"\)/',
        'url("../images/$1")',
        $content
    );

    // Fix background property with url
    $content = preg_replace(
        '/background: url\(\'\/images\/([^\']+)\'\)/',
        'background: url(\'../images/$1\')',
        $content
    );

    $content = preg_replace(
        '/background: url\("\/images\/([^"]+)"\)/',
        'background: url("../images/$1")',
        $content
    );

    // Fix background-image property
    $content = preg_replace(
        '/background-image: url\(\'\/images\/([^\']+)\'\)/',
        'background-image: url(\'../images/$1\')',
        $content
    );

    $content = preg_replace(
        '/background-image: url\("\/images\/([^"]+)"\)/',
        'background-image: url("../images/$1")',
        $content
    );
    
    file_put_contents($file, $content);
    echo "Fixed paths in CSS: " . basename($file) . "\n";
}

echo "All files have been updated with correct paths.\n";
?> 