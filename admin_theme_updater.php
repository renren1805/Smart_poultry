<?php
$directory = 'c:/xampp/htdocs/FINALS/admin/';
$files = glob($directory . '*.php');

$replacements = [
    // Fonts
    'family=Poppins:wght@300;400;500;600;700' => 'family=Outfit:wght@300;400;500;600;700;800',
    'font-family:\'Poppins\', sans-serif;' => 'font-family:\'Outfit\', sans-serif;',
    'font-family:\'Poppins\',sans-serif;' => 'font-family:\'Outfit\',sans-serif;',
    
    // CSS Variables
    '--primary:#4285F4;' => '--primary:#3b82f6;',
    '--primary-hover:#5c97ff;' => '--primary-hover:#2563eb;',
    '--bg-dark:#070b14;' => '--bg-dark:#0f172a;',
    '--bg-card:rgba(17,25,40,0.75);' => '--bg-card:rgba(15,23,42,0.75);',
    
    // RGB colors
    'rgba(66,133,244' => 'rgba(59,130,246',
    'rgba(17,25,40' => 'rgba(15,23,42',
    'rgba(8,12,30' => 'rgba(15,23,42',
    
    // Hex colors
    '#4285F4' => '#3b82f6',
    '#070b14' => '#0f172a',
    '#050816' => '#090e1a',
    '#111928' => '#1e293b',
    '#0f172a' => '#0f172a' // this is already slate, just in case
];

$count = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: " . basename($file) . "\n";
        $count++;
    }
}

echo "Finished updating $count admin files.\n";
?>
