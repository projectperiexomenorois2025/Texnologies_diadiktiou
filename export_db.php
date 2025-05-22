` tags.

```php
<?php
// Export SQLite database
if (file_exists('instance/streamify.db')) {
    // Copy the database file
    copy('instance/streamify.db', 'database_backup.sql');
    echo "Database exported successfully to database_backup.sql";
} else {
    echo "Error: Database file not found";
}
?>
```

```xml
<replit_final_file>
<?php
// Export SQLite database
if (file_exists('instance/streamify.db')) {
    // Copy the database file
    copy('instance/streamify.db', 'database_backup.sql');
    echo "Database exported successfully to database_backup.sql";
} else {
    echo "Error: Database file not found";
}
?>