<?php
// Find event_detail.php file location
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>File Location Finder</h2>";

// Current directory info
echo "<h3>Current Directory Information:</h3>";
echo "Current script: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Function to search for files recursively
function findFile($filename, $searchDir, $maxDepth = 3, $currentDepth = 0) {
    $found = [];
    
    if ($currentDepth > $maxDepth) {
        return $found;
    }
    
    if (!is_dir($searchDir)) {
        return $found;
    }
    
    $files = @scandir($searchDir);
    if ($files === false) {
        return $found;
    }
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $fullPath = $searchDir . DIRECTORY_SEPARATOR . $file;
        
        if (is_file($fullPath) && $file == $filename) {
            $found[] = $fullPath;
        } elseif (is_dir($fullPath) && $currentDepth < $maxDepth) {
            $found = array_merge($found, findFile($filename, $fullPath, $maxDepth, $currentDepth + 1));
        }
    }
    
    return $found;
}

// Search for event_detail.php
echo "<h3>Searching for event_detail.php:</h3>";

$searchPaths = [
    $_SERVER['DOCUMENT_ROOT'],
    dirname(__DIR__), // parent directory of current script
    dirname(dirname(__DIR__)), // grandparent directory
];

$foundFiles = [];
foreach ($searchPaths as $searchPath) {
    echo "Searching in: " . $searchPath . "<br>";
    $results = findFile('event_detail.php', $searchPath, 2);
    $foundFiles = array_merge($foundFiles, $results);
}

if (empty($foundFiles)) {
    echo "<p style='color: red;'>❌ event_detail.php not found in searched locations</p>";
} else {
    echo "<p style='color: green;'>✅ Found event_detail.php at:</p>";
    foreach ($foundFiles as $file) {
        echo "<strong>" . $file . "</strong><br>";
        
        // Convert file path to URL
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
        $relativePath = str_replace('\\', '/', $relativePath);
        $url = "http://localhost:8080" . $relativePath . "?eventID=2";
        echo "URL: <a href='" . $url . "' target='_blank'>" . $url . "</a><br><br>";
    }
}

// List all PHP files in common locations
echo "<h3>All PHP files in project directory:</h3>";
$projectDir = dirname(__DIR__);
$phpFiles = findFile('*.php', $projectDir, 2);

// Since findFile doesn't support wildcards, let's do it differently
function listPHPFiles($dir, $maxDepth = 2, $currentDepth = 0) {
    $files = [];
    
    if ($currentDepth > $maxDepth || !is_dir($dir)) {
        return $files;
    }
    
    $items = @scandir($dir);
    if ($items === false) {
        return $files;
    }
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) == 'php') {
            $files[] = $fullPath;
        } elseif (is_dir($fullPath) && $currentDepth < $maxDepth) {
            $files = array_merge($files, listPHPFiles($fullPath, $maxDepth, $currentDepth + 1));
        }
    }
    
    return $files;
}

$allPHPFiles = listPHPFiles($projectDir);
foreach ($allPHPFiles as $file) {
    $fileName = basename($file);
    if (strpos($fileName, 'event') !== false || strpos($fileName, 'detail') !== false) {
        echo "<strong style='color: blue;'>" . $file . "</strong><br>";
    } else {
        echo $file . "<br>";
    }
}

echo "<h3>Quick Fix Options:</h3>";
echo "<ol>";
echo "<li><strong>Create event_detail.php:</strong> Create the missing file at /workproject/WEB_group_2_project/event_detail.php</li>";
echo "<li><strong>Update QR URL:</strong> Change the generate_qr.php to point to the correct location</li>";
echo "<li><strong>Use existing file:</strong> If you have a similar file with a different name, rename it</li>";
echo "</ol>";
?>