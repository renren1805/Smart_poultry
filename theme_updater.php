<?php
$directory = 'c:/xampp/htdocs/FINALS/user/';
$files = glob($directory . '*.php');

$replacements = [
    // Fonts
    'family=Poppins:wght@300;400;500;600;700' => 'family=Outfit:wght@300;400;500;600;700;800',
    'font-family:\'Poppins\', sans-serif;' => 'font-family:\'Outfit\', sans-serif;',
    'font-family:\'Poppins\',sans-serif;' => 'font-family:\'Outfit\',sans-serif;',
    
    // CSS Variables
    '--primary: #f44242;' => '--primary: #f59e0b;',
    '--accent: #EA4335;' => '--accent: #d97706;',
    '--dark: #3b1e1e;' => '--dark: #0f172a;',
    
    // RGB colors
    'rgba(244, 66, 66' => 'rgba(245, 158, 11',
    'rgba(234,67,53' => 'rgba(217, 119, 6',
    'rgba(255,0,0' => 'rgba(245, 158, 11',
    'rgba(255,50,50' => 'rgba(245, 158, 11',
    'rgba(255, 0, 0' => 'rgba(245, 158, 11',
    'rgba(42, 15, 15' => 'rgba(15, 23, 42',
    
    // Hex colors
    '#2a0f0f' => '#090e1a',
    '#3b1e1e' => '#0f172a',
    '#120000' => '#090e1a',
    '#050505' => '#0f172a', // maybe in login.php
    '#ff1a1a' => '#f59e0b',
    '#b30000' => '#d97706',
    '#ff4d4d' => '#f59e0b',
    '#ff0000' => '#f59e0b',
    '#ff3c3c' => '#f59e0b',
    '#ff3b3b' => '#f59e0b',
    '#ff4747' => '#f59e0b',
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

echo "Finished updating $count files.\n";
?>
