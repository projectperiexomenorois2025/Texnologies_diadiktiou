
<?php
require_once 'includes/db_config.php';

// Export database
$db_export = shell_exec("mysqldump -h {$db_host} -u {$db_user} -p{$db_pass} {$db_name} > database_backup.sql");

// Create ZIP archive
$zip = new ZipArchive();
$filename = "streamify_docker.zip";

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
        
        // Skip certain files
        if (!in_array($relativePath, ['streamify_docker.zip', '.git', '.env'])) {
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$zip->close();
echo "Project exported successfully to {$filename}. You can now use 'docker-compose up' to run it.";
?>
