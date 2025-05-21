
<?php
require_once 'includes/db_config.php';

// Get all tables
$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$output = "";

// Export structure and data for each table
foreach ($tables as $table) {
    // Get create table statement
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    $output .= "\n\n" . $row[1] . ";\n\n";
    
    // Get table data
    $result = $conn->query("SELECT * FROM $table");
    while ($row = $result->fetch_assoc()) {
        $output .= "INSERT INTO $table VALUES('" . implode("','", array_map(array($conn, 'real_escape_string'), $row)) . "');\n";
    }
}

// Save to file
file_put_contents("database_backup.sql", $output);
echo "Database exported successfully!";
?>
