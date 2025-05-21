
<?php
$zip = new ZipArchive();
$filename = "streamify_export.zip";

if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    exit("Cannot open <$filename>\n");
}

// Add all project files
$rootPath = realpath("./");

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);
        
        // Skip git files and zip files
        if (strpos($relativePath, '.git') === false && 
            strpos($relativePath, '.zip') === false) {
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$zip->close();
echo "Project exported to $filename";
?>
