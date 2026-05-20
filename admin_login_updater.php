<?php
$directory = 'c:/xampp/htdocs/FINALS/admin/';
$files = ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php'];

$replacements = [
    // Colors from login.php
    'rgba(0,102,255' => 'rgba(59,130,246',
    '#0066ff' => '#3b82f6',
    '#008cff' => '#60a5fa',
    '#000000' => '#0f172a',
    '#1d4ed8' => '#2563eb', // register.php glow
];

$count = 0;

foreach ($files as $filename) {
    $file = $directory . $filename;
    if (file_exists($file)) {
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
}

echo "Finished updating $count login/register files.\n";
?>
