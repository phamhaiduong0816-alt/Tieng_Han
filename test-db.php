
<?php
require_once __DIR__ . '/config/database.php';

echo "Đường dẫn: " . realpath(__DIR__ . '/config/database.php') . "<br>";

if (function_exists('checkDbConnection')) {
    echo "OK: Đã tìm thấy hàm checkDbConnection().";
} else {
    echo "LỖI: Không tìm thấy hàm checkDbConnection().";
}
