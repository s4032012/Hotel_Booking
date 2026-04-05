<?php
mysqli_report(MYSQLI_REPORT_OFF);

function env_value($key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

$databaseUrl = env_value('DATABASE_URL');
$dbConfig = [
    'host' => env_value('DB_HOST', '127.0.0.1'),
    'port' => (int) env_value('DB_PORT', 3306),
    'name' => env_value('DB_DATABASE', 'hotel_booking'),
    'user' => env_value('DB_USERNAME', 'root'),
    'pass' => env_value('DB_PASSWORD', '')
];

if ($databaseUrl) {
    $parsedUrl = parse_url($databaseUrl);
    if ($parsedUrl !== false) {
        $dbConfig['host'] = isset($parsedUrl['host']) ? $parsedUrl['host'] : $dbConfig['host'];
        $dbConfig['port'] = isset($parsedUrl['port']) ? (int) $parsedUrl['port'] : $dbConfig['port'];
        $dbConfig['name'] = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : $dbConfig['name'];
        $dbConfig['user'] = isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : $dbConfig['user'];
        $dbConfig['pass'] = isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : $dbConfig['pass'];
    }
}

$conn = @new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['pass'],
    $dbConfig['name'],
    $dbConfig['port']
);

if ($conn->connect_error) {
    $message = "Kết nối database thất bại.";
    if (env_value('APP_ENV', 'development') !== 'production') {
        $message .= " " . $conn->connect_error;
    }

    die($message);
}

$conn->set_charset("utf8mb4");
?>
