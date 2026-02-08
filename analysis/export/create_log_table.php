<?php
define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
include ABSPATH . 'analysis/db_config.php';
include ABSPATH . "analysis/include/Pdoa.php";

$sql = "CREATE TABLE IF NOT EXISTS `import_db_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action` VARCHAR(255) NULL,
  `function_name` VARCHAR(255) NULL,
  `request` LONGTEXT NULL,
  `response` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    Pdo_an::db_query($sql);
    echo "Table import_db_logs created successfully.";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
