<?php
define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
include ABSPATH . 'analysis/db_config.php';
include ABSPATH . "analysis/include/Pdoa.php";

$sql = "ALTER TABLE `import_db_logs` ADD COLUMN `status` INT(11) NOT NULL DEFAULT 0";

try {
    Pdo_an::db_query($sql);
    echo "Column status added successfully to import_db_logs.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
