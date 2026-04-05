<?php

function env_value($key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function env_flag($key, $default = false) {
    $value = env_value($key);
    if ($value === null) {
        return $default;
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
}

function app_uploads_enabled() {
    return !env_flag('DISABLE_FILE_UPLOADS', false);
}

function app_free_mode() {
    return env_flag('RENDER', false) && env_flag('DISABLE_FILE_UPLOADS', false);
}

class DbResultCompat {
    private $rows = [];
    private $index = 0;
    public $num_rows = 0;

    public function __construct(array $rows) {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc() {
        if ($this->index >= $this->num_rows) {
            return null;
        }

        return $this->rows[$this->index++];
    }
}

class PgCompatConnection {
    public $connect_error = null;
    public $error = '';
    public $insert_id = 0;

    private $pdo;

    public function __construct($config) {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'],
                $config['name']
            );

            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $e) {
            $this->connect_error = $e->getMessage();
        }
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function query($sql) {
        $this->error = '';
        $this->insert_id = 0;
        $sql = $this->adaptQuery($sql);

        try {
            if ($this->isResultQuery($sql)) {
                $statement = $this->pdo->query($sql);
                return new DbResultCompat($statement ? $statement->fetchAll() : []);
            }

            $this->pdo->exec($sql);

            if (preg_match('/^\s*INSERT\b/i', $sql)) {
                $lastId = $this->pdo->query('SELECT LASTVAL()')->fetchColumn();
                $this->insert_id = $lastId ? (int) $lastId : 0;
            }

            return true;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function execScript($sql) {
        $this->pdo->exec($sql);
    }

    private function isResultQuery($sql) {
        return (bool) preg_match('/^\s*(SELECT|SHOW|WITH)\b/i', $sql);
    }

    private function adaptQuery($sql) {
        if (preg_match("/^\s*SHOW\s+TABLES\s+LIKE\s+'([^']+)'/i", $sql, $matches)) {
            $tableName = str_replace("'", "''", $matches[1]);
            return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '{$tableName}'";
        }

        $sql = preg_replace('/ORDER\s+BY\s+RAND\(\)/i', 'ORDER BY RANDOM()', $sql);
        $sql = preg_replace('/LIMIT\s+(\d+)\s*,\s*(\d+)/i', 'LIMIT $2 OFFSET $1', $sql);

        return $sql;
    }
}

function parse_database_config() {
    $databaseUrl = env_value('DATABASE_URL');
    $config = [
        'driver' => env_value('DB_CONNECTION', 'mysql'),
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', 3306),
        'name' => env_value('DB_DATABASE', 'hotel_booking'),
        'user' => env_value('DB_USERNAME', 'root'),
        'pass' => env_value('DB_PASSWORD', ''),
    ];

    if ($databaseUrl) {
        $parsedUrl = parse_url($databaseUrl);
        if ($parsedUrl !== false) {
            $scheme = isset($parsedUrl['scheme']) ? strtolower($parsedUrl['scheme']) : '';
            if (in_array($scheme, ['postgres', 'postgresql', 'pgsql'], true)) {
                $config['driver'] = 'pgsql';
                $config['port'] = 5432;
            }

            $config['host'] = isset($parsedUrl['host']) ? $parsedUrl['host'] : $config['host'];
            $config['port'] = isset($parsedUrl['port']) ? (int) $parsedUrl['port'] : $config['port'];
            $config['name'] = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : $config['name'];
            $config['user'] = isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : $config['user'];
            $config['pass'] = isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : $config['pass'];
        }
    }

    return $config;
}

function pg_table_exists(PDO $pdo, $tableName) {
    $statement = $pdo->prepare("
        SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = :table_name
        )
    ");
    $statement->execute(['table_name' => $tableName]);

    return (bool) $statement->fetchColumn();
}

function parse_mysql_insert_rows($valuesSql) {
    $rows = [];
    $length = strlen($valuesSql);
    $buffer = '';
    $depth = 0;
    $inString = false;
    $escaped = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $valuesSql[$i];

        if ($char === "\\" && $inString) {
            $buffer .= $char;
            $escaped = !$escaped;
            continue;
        }

        if ($char === "'" && !$escaped) {
            $inString = !$inString;
            $buffer .= $char;
            continue;
        }

        $escaped = false;

        if ($char === '(' && !$inString) {
            if ($depth === 0) {
                $buffer = '';
            } else {
                $buffer .= $char;
            }
            $depth++;
            continue;
        }

        if ($char === ')' && !$inString) {
            $depth--;
            if ($depth === 0) {
                $rows[] = str_getcsv($buffer, ',', "'", "\\");
                $buffer = '';
                continue;
            }
        }

        if ($depth > 0) {
            $buffer .= $char;
        }
    }

    return $rows;
}

function normalize_mysql_value($value) {
    $trimmed = trim($value);
    if (strcasecmp($trimmed, 'NULL') === 0) {
        return null;
    }

    $decoded = str_replace(["\\r", "\\n"], ["\r", "\n"], $trimmed);
    return $decoded;
}

function sync_pg_identity_sequence(PDO $pdo, $tableName) {
    $pdo->exec("
        SELECT setval(
            pg_get_serial_sequence('{$tableName}', 'id'),
            COALESCE((SELECT MAX(id) FROM {$tableName}), 1),
            true
        )
    ");
}

function ensure_default_admin(PDO $pdo) {
    $adminEmail = env_value('DEFAULT_ADMIN_EMAIL', 'admin@gmail.com');
    $adminPassword = env_value('DEFAULT_ADMIN_PASSWORD', '123456');
    $adminName = env_value('DEFAULT_ADMIN_NAME', 'Group 4');
    $adminPhone = env_value('DEFAULT_ADMIN_PHONE', '0765933135');

    $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $statement->execute([$adminEmail]);
    $exists = (int) $statement->fetchColumn();

    if ($exists === 0) {
        $insert = $pdo->prepare("
            INSERT INTO users (full_name, email, password, phone, role)
            VALUES (?, ?, ?, ?, 'admin')
        ");
        $insert->execute([$adminName, $adminEmail, $adminPassword, $adminPhone]);
    }
}

function seed_postgres_from_mysql_dump(PDO $pdo, $dumpPath) {
    if (!file_exists($dumpPath)) {
        return;
    }

    $dumpSql = file_get_contents($dumpPath);
    if ($dumpSql === false) {
        return;
    }

    preg_match_all('/INSERT INTO `([^`]+)` \(([^)]+)\) VALUES\s*(.+?);/si', $dumpSql, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $tableName = $match[1];
        $allowedTables = ['users', 'rooms', 'room_images', 'bookings', 'favorites', 'payments'];
        if (!in_array($tableName, $allowedTables, true)) {
            continue;
        }

        $columns = array_map(function ($column) {
            return trim($column, " `");
        }, explode(',', $match[2]));
        $rows = parse_mysql_insert_rows($match[3]);
        if (empty($rows)) {
            continue;
        }

        $quotedColumns = '"' . implode('", "', $columns) . '"';
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $statement = $pdo->prepare("
            INSERT INTO {$tableName} ({$quotedColumns})
            VALUES {$placeholders}
            ON CONFLICT (id) DO NOTHING
        ");

        foreach ($rows as $row) {
            $values = array_map('normalize_mysql_value', $row);
            $statement->execute($values);
        }
    }

    foreach (['users', 'rooms', 'room_images', 'bookings', 'favorites', 'payments'] as $tableName) {
        sync_pg_identity_sequence($pdo, $tableName);
    }
}

function bootstrap_postgres_database(PgCompatConnection $conn) {
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;

    $pdo = $conn->getPdo();
    if (!$pdo) {
        return;
    }

    $schemaPath = dirname(__DIR__) . '/database/postgres_schema.sql';
    $dumpPath = dirname(__DIR__) . '/hotel_booking.sql';
    $shouldAutoSeed = env_flag('AUTO_SEED', true);

    try {
        if (!pg_table_exists($pdo, 'users') && file_exists($schemaPath)) {
            $schemaSql = file_get_contents($schemaPath);
            if ($schemaSql !== false) {
                $pdo->exec($schemaSql);
            }
        }
    } catch (Throwable $e) {
        $conn->error = $e->getMessage();
        return;
    }

    if (!pg_table_exists($pdo, 'users')) {
        $conn->error = 'Bang users chua duoc tao tren Postgres.';
        return;
    }

    if ($shouldAutoSeed) {
        try {
            $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($userCount === 0) {
                seed_postgres_from_mysql_dump($pdo, $dumpPath);
            }
        } catch (Throwable $e) {
            $conn->error = $e->getMessage();
        }
    }

    try {
        ensure_default_admin($pdo);
    } catch (Throwable $e) {
        $conn->error = $e->getMessage();
    }
}

$dbConfig = parse_database_config();

if ($dbConfig['driver'] === 'pgsql') {
    $conn = new PgCompatConnection($dbConfig);
    if ($conn->connect_error) {
        $message = "Kết nối database thất bại.";
        if (env_value('APP_ENV', 'development') !== 'production') {
            $message .= " " . $conn->connect_error;
        }
        die($message);
    }

    bootstrap_postgres_database($conn);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
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
}

?>
