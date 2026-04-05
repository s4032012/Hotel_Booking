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

function bson_to_array($value) {
    if (is_array($value)) {
        $output = [];
        foreach ($value as $key => $item) {
            $output[$key] = bson_to_array($item);
        }
        return $output;
    }

    if (is_object($value)) {
        if ($value instanceof MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d H:i:s');
        }

        $output = [];
        foreach (get_object_vars($value) as $key => $item) {
            $output[$key] = bson_to_array($item);
        }
        return $output;
    }

    return $value;
}

function parse_database_config() {
    $databaseUrl = env_value('DATABASE_URL');
    $mongoUrl = env_value('MONGODB_URI');
    $config = [
        'driver' => env_value('DB_CONNECTION', 'mysql'),
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', 3306),
        'name' => env_value('DB_DATABASE', 'hotel_booking'),
        'user' => env_value('DB_USERNAME', 'root'),
        'pass' => env_value('DB_PASSWORD', ''),
        'uri' => $mongoUrl ?: $databaseUrl,
    ];

    if ($mongoUrl) {
        $config['driver'] = 'mongodb';
        $mongoDbName = env_value('MONGODB_DATABASE');
        if ($mongoDbName) {
            $config['name'] = $mongoDbName;
        } else {
            $parsedMongoUrl = parse_url($mongoUrl);
            if ($parsedMongoUrl !== false && !empty($parsedMongoUrl['path'])) {
                $config['name'] = ltrim($parsedMongoUrl['path'], '/');
            }
        }
        return $config;
    }

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

    return str_replace(["\\r", "\\n"], ["\r", "\n"], $trimmed);
}

class PgCompatConnection {
    public $connect_error = null;
    public $error = '';
    public $insert_id = 0;
    private $pdo;

    public function __construct($config) {
        try {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['name']);
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
            if ((bool) preg_match('/^\s*(SELECT|SHOW|WITH)\b/i', $sql)) {
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

class MongoCompatConnection {
    public $connect_error = null;
    public $error = '';
    public $insert_id = 0;

    private $manager;
    private $dbName;

    public function __construct($config) {
        try {
            $this->dbName = $config['name'] ?: 'hotel_booking';
            $this->manager = new MongoDB\Driver\Manager($config['uri']);
            $this->command(['ping' => 1]);
        } catch (Throwable $e) {
            $this->connect_error = $e->getMessage();
        }
    }

    public function query($sql) {
        $this->error = '';
        $this->insert_id = 0;
        $sql = trim($sql);

        try {
            $result = $this->handleQuery($sql);
            if ($result instanceof DbResultCompat || $result === true || $result === false) {
                return $result;
            }

            return new DbResultCompat($result);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function bootstrap() {
        if ($this->countDocuments('users', []) > 0) {
            return;
        }

        $dumpPath = dirname(__DIR__) . '/hotel_booking.sql';
        if (file_exists($dumpPath)) {
            $dumpSql = file_get_contents($dumpPath);
            if ($dumpSql !== false) {
                preg_match_all('/INSERT INTO `([^`]+)` \(([^)]+)\) VALUES\s*(.+?);/si', $dumpSql, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $tableName = $match[1];
                    $allowed = ['users', 'rooms', 'room_images', 'bookings', 'favorites', 'payments'];
                    if (!in_array($tableName, $allowed, true)) {
                        continue;
                    }

                    $columns = array_map(function ($column) {
                        return trim($column, " `");
                    }, explode(',', $match[2]));
                    $rows = parse_mysql_insert_rows($match[3]);
                    foreach ($rows as $row) {
                        $doc = [];
                        foreach ($columns as $index => $column) {
                            $doc[$column] = normalize_mysql_value($row[$index]);
                        }
                        if (isset($doc['id'])) {
                            $doc['id'] = (int) $doc['id'];
                        }
                        $this->insertDocument($tableName, $doc, false);
                    }
                }
            }
        }

        $this->ensureDefaultAdmin();
        $this->syncAllCounters();
    }

    private function ensureDefaultAdmin() {
        $adminEmail = env_value('DEFAULT_ADMIN_EMAIL', 'admin@gmail.com');
        $existing = $this->findOne('users', ['email' => $adminEmail]);
        if ($existing) {
            return;
        }

        $this->insertDocument('users', [
            'full_name' => env_value('DEFAULT_ADMIN_NAME', 'Group 4'),
            'email' => $adminEmail,
            'password' => env_value('DEFAULT_ADMIN_PASSWORD', '123456'),
            'phone' => env_value('DEFAULT_ADMIN_PHONE', '0765933135'),
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'avatar' => null,
        ]);
    }

    private function syncAllCounters() {
        foreach (['users', 'rooms', 'room_images', 'bookings', 'favorites', 'payments'] as $collection) {
            $maxId = $this->maxId($collection);
            $this->setCounter($collection, $maxId);
        }
    }

    private function handleQuery($sql) {
        if (preg_match("/^SHOW TABLES LIKE 'favorites'$/i", $sql)) {
            return $this->collectionExists('favorites') ? [['table_name' => 'favorites']] : [];
        }

        if (preg_match("/^SELECT \* FROM users WHERE email = '([^']*)' AND password = '([^']*)'$/i", $sql, $m)) {
            $user = $this->findOne('users', ['email' => $m[1], 'password' => $m[2]]);
            return $user ? [$user] : [];
        }

        if (preg_match("/^SELECT id FROM users WHERE email = '([^']*)'$/i", $sql, $m)) {
            $user = $this->findOne('users', ['email' => $m[1]], ['id']);
            return $user ? [$user] : [];
        }

        if (preg_match("/^SELECT \* FROM users WHERE id = (\d+)$/i", $sql, $m)) {
            $user = $this->findOne('users', ['id' => (int) $m[1]]);
            return $user ? [$user] : [];
        }

        if (preg_match("/^INSERT INTO users \(full_name, email, phone, password, role\) VALUES \('(.+)', '(.+)', '(.+)', '(.+)', '(.+)'\)$/i", $sql, $m)) {
            $this->insertDocument('users', [
                'full_name' => $m[1],
                'email' => $m[2],
                'phone' => $m[3],
                'password' => $m[4],
                'role' => $m[5],
                'created_at' => date('Y-m-d H:i:s'),
                'avatar' => null,
            ]);
            return true;
        }

        if (preg_match("/^UPDATE users SET full_name = '(.+)', phone = '(.+)' WHERE id = (\d+)$/i", $sql, $m)) {
            return $this->updateOne('users', ['id' => (int) $m[3]], ['$set' => ['full_name' => $m[1], 'phone' => $m[2]]]);
        }

        if (preg_match("/^UPDATE users SET avatar = '(.+)' WHERE id = (\d+)$/i", $sql, $m)) {
            return $this->updateOne('users', ['id' => (int) $m[2]], ['$set' => ['avatar' => $m[1]]]);
        }

        if (preg_match("/^SELECT count\(\*\) as total FROM bookings\s+WHERE room_id = '(\d+)'\s+AND status != 'cancelled'\s+AND \(check_in_date < '([^']+)' AND check_out_date > '([^']+)'\)$/is", $sql, $m)) {
            $count = 0;
            foreach ($this->findMany('bookings', ['room_id' => (int) $m[1]]) as $booking) {
                if ($booking['status'] === 'cancelled') {
                    continue;
                }
                if ($booking['check_in_date'] < $m[2] && $booking['check_out_date'] > $m[3]) {
                    $count++;
                }
            }
            return [['total' => $count]];
        }

        if (preg_match("/^INSERT INTO bookings \(user_id, room_id, check_in_date, check_out_date, total_price, status\)\s+VALUES \('(\d+)', '(\d+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)'\)$/is", $sql, $m)) {
            $this->insertDocument('bookings', [
                'user_id' => (int) $m[1],
                'room_id' => (int) $m[2],
                'check_in_date' => $m[3],
                'check_out_date' => $m[4],
                'total_price' => (float) $m[5],
                'status' => $m[6],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        }

        if (preg_match("/^UPDATE bookings SET status = '([^']+)' WHERE id = (\d+)(?: AND user_id = (\d+) AND status = '([^']+)')?$/i", $sql, $m)) {
            $filter = ['id' => (int) $m[2]];
            if (!empty($m[3])) {
                $filter['user_id'] = (int) $m[3];
            }
            if (!empty($m[4])) {
                $filter['status'] = $m[4];
            }
            return $this->updateOne('bookings', $filter, ['$set' => ['status' => $m[1]]]);
        }

        if (preg_match("/^DELETE FROM bookings WHERE id = (\d+)$/i", $sql, $m)) {
            return $this->deleteCascadeBooking((int) $m[1]);
        }

        if (preg_match("/^SELECT b\.\*, r\.room_name, r\.image FROM bookings b JOIN rooms r ON b\.room_id = r\.id WHERE b\.user_id = (\d+) ORDER BY b\.created_at DESC$/i", $sql, $m)) {
            $rows = [];
            foreach ($this->findMany('bookings', ['user_id' => (int) $m[1]], ['sort' => ['created_at' => -1]]) as $booking) {
                $room = $this->findOne('rooms', ['id' => (int) $booking['room_id']]);
                if (!$room) {
                    continue;
                }
                $booking['room_name'] = $room['room_name'];
                $booking['image'] = $room['image'];
                $rows[] = $booking;
            }
            return $rows;
        }

        if (preg_match("/^SELECT \* FROM rooms WHERE id = (\d+)$/i", $sql, $m)) {
            $room = $this->findOne('rooms', ['id' => (int) $m[1]]);
            return $room ? [$room] : [];
        }

        if (preg_match("/^SELECT image_path FROM room_images WHERE room_id = (\d+)$/i", $sql, $m)) {
            return $this->findMany('room_images', ['room_id' => (int) $m[1]], ['sort' => ['id' => 1], 'projection' => ['image_path' => 1]]);
        }

        if (preg_match("/^SELECT \* FROM room_images WHERE room_id = (\d+)$/i", $sql, $m)) {
            return $this->findMany('room_images', ['room_id' => (int) $m[1]], ['sort' => ['id' => 1]]);
        }

        if (preg_match("/^SELECT id FROM favorites WHERE user_id = (\d+) AND room_id = (\d+)$/i", $sql, $m)) {
            $favorite = $this->findOne('favorites', ['user_id' => (int) $m[1], 'room_id' => (int) $m[2]], ['id']);
            return $favorite ? [$favorite] : [];
        }

        if (preg_match("/^INSERT INTO favorites \(user_id, room_id\) VALUES \((\d+), (\d+)\)$/i", $sql, $m)) {
            $existing = $this->findOne('favorites', ['user_id' => (int) $m[1], 'room_id' => (int) $m[2]]);
            if (!$existing) {
                $this->insertDocument('favorites', ['user_id' => (int) $m[1], 'room_id' => (int) $m[2], 'created_at' => date('Y-m-d H:i:s')]);
            }
            return true;
        }

        if (preg_match("/^DELETE FROM favorites WHERE user_id = (\d+) AND room_id = (\d+)$/i", $sql, $m)) {
            return $this->deleteOne('favorites', ['user_id' => (int) $m[1], 'room_id' => (int) $m[2]]);
        }

        if (preg_match("/^SELECT \* FROM rooms ORDER BY id DESC$/i", $sql)) {
            return $this->findMany('rooms', [], ['sort' => ['id' => -1]]);
        }

        if (preg_match("/^SELECT COUNT\(\*\) as c FROM rooms$/i", $sql)) {
            return [['c' => $this->countDocuments('rooms', [])]];
        }

        if (preg_match("/^SELECT COUNT\(\*\) as c FROM bookings WHERE status='([^']+)' AND created_at BETWEEN '([^']+)' AND '([^']+)'$/i", $sql, $m)) {
            $count = 0;
            foreach ($this->findMany('bookings', ['status' => $m[1]]) as $booking) {
                if ($booking['created_at'] >= $m[2] && $booking['created_at'] <= $m[3]) {
                    $count++;
                }
            }
            return [['c' => $count]];
        }

        if (preg_match("/^SELECT COUNT\(\*\) as c FROM bookings WHERE \(status='confirmed' OR status='completed'\) AND created_at BETWEEN '([^']+)' AND '([^']+)'$/i", $sql, $m)) {
            $count = 0;
            foreach ($this->findMany('bookings', []) as $booking) {
                if (in_array($booking['status'], ['confirmed', 'completed'], true) && $booking['created_at'] >= $m[1] && $booking['created_at'] <= $m[2]) {
                    $count++;
                }
            }
            return [['c' => $count]];
        }

        if (preg_match("/^SELECT SUM\(total_price\) as s FROM bookings WHERE \(status='confirmed' OR status='completed'\) AND created_at BETWEEN '([^']+)' AND '([^']+)'$/i", $sql, $m)) {
            $sum = 0;
            foreach ($this->findMany('bookings', []) as $booking) {
                if (in_array($booking['status'], ['confirmed', 'completed'], true) && $booking['created_at'] >= $m[1] && $booking['created_at'] <= $m[2]) {
                    $sum += (float) $booking['total_price'];
                }
            }
            return [['s' => $sum]];
        }

        if (preg_match("/^SELECT b\.\*, u\.full_name, r\.room_name\s+FROM bookings b\s+JOIN users u ON b\.user_id = u\.id\s+JOIN rooms r ON b\.room_id = r\.id\s+ORDER BY b\.created_at DESC LIMIT 5$/is", $sql)) {
            return $this->bookingJoinRows(true, false, 5);
        }

        if (preg_match("/^SELECT b\.\*, u\.full_name, u\.phone, r\.room_name\s+FROM bookings b\s+JOIN users u ON b\.user_id = u\.id\s+JOIN rooms r ON b\.room_id = r\.id\s+ORDER BY b\.created_at DESC$/is", $sql)) {
            return $this->bookingJoinRows(false, true, null);
        }

        if (preg_match("/^DELETE FROM room_images WHERE id = (\d+)$/i", $sql, $m)) {
            return $this->deleteOne('room_images', ['id' => (int) $m[1]]);
        }

        if (preg_match("/^INSERT INTO room_images \(room_id, image_path\) VALUES (.+)$/is", $sql, $m)) {
            $rows = parse_mysql_insert_rows($m[1]);
            foreach ($rows as $row) {
                $this->insertDocument('room_images', [
                    'room_id' => (int) normalize_mysql_value($row[0]),
                    'image_path' => normalize_mysql_value($row[1]),
                ]);
            }
            return true;
        }

        if (preg_match("/^DELETE FROM rooms WHERE id = (\d+)$/i", $sql, $m)) {
            return $this->deleteCascadeRoom((int) $m[1]);
        }

        if (preg_match("/^INSERT INTO rooms \((.+)\)\s+VALUES \((.+)\)$/is", $sql, $m)) {
            $columns = array_map(function ($column) {
                return trim($column, " `");
            }, explode(',', $m[1]));
            $values = str_getcsv($m[2], ',', "'", "\\");
            $doc = [];
            foreach ($columns as $index => $column) {
                $doc[$column] = normalize_mysql_value($values[$index]);
            }
            foreach (['price'] as $numericField) {
                if (isset($doc[$numericField])) {
                    $doc[$numericField] = (float) $doc[$numericField];
                }
            }
            foreach (['capacity', 'has_pool', 'has_breakfast', 'has_parking'] as $intField) {
                if (isset($doc[$intField])) {
                    $doc[$intField] = (int) $doc[$intField];
                }
            }
            $doc['created_at'] = date('Y-m-d H:i:s');
            $this->insertDocument('rooms', $doc);
            return true;
        }

        if (preg_match("/^UPDATE rooms SET\s+(.+)\s+WHERE id = (\d+)$/is", $sql, $m)) {
            $set = [];
            $parts = preg_split('/,\s*(?=[a-z_]+\s*=)/i', trim($m[1]));
            foreach ($parts as $part) {
                if (preg_match("/^([a-z_]+)\s*=\s*'?(.*?)'?$/i", trim($part), $setMatch)) {
                    $value = normalize_mysql_value($setMatch[2]);
                    if (in_array($setMatch[1], ['capacity', 'has_pool', 'has_breakfast', 'has_parking'], true)) {
                        $value = (int) $value;
                    }
                    if ($setMatch[1] === 'price') {
                        $value = (float) $value;
                    }
                    $set[$setMatch[1]] = $value;
                }
            }
            return $this->updateOne('rooms', ['id' => (int) $m[2]], ['$set' => $set]);
        }

        if (preg_match("/^SELECT count\(id\) AS total FROM rooms (WHERE .+)$/is", $sql, $m)) {
            return [['total' => count($this->filterRooms($m[1]))]];
        }

        if (preg_match("/^SELECT \* FROM rooms (WHERE .+) ORDER BY id DESC LIMIT (\d+), (\d+)$/is", $sql, $m)) {
            $rooms = $this->filterRooms($m[1]);
            usort($rooms, function ($a, $b) {
                return $b['id'] <=> $a['id'];
            });
            return array_slice($rooms, (int) $m[2], (int) $m[3]);
        }

        if (preg_match("/^SELECT \* FROM rooms WHERE status = 'available'(.*) ORDER BY RAND\(\) LIMIT 3$/is", $sql, $m)) {
            $rooms = $this->filterIndexRooms($sql);
            shuffle($rooms);
            return array_slice($rooms, 0, 3);
        }

        throw new RuntimeException('Mongo adapter chua ho tro query nay: ' . $sql);
    }

    private function filterIndexRooms($sql) {
        $rooms = $this->findMany('rooms', []);
        $output = [];
        foreach ($rooms as $room) {
            if (($room['status'] ?? '') !== 'available') {
                continue;
            }

            if (preg_match("/room_name LIKE '%([^']+)%' OR address LIKE '%([^']+)%'/i", $sql, $m)) {
                $keyword = mb_strtolower($m[1]);
                $haystack = mb_strtolower(($room['room_name'] ?? '') . ' ' . ($room['address'] ?? ''));
                if (mb_strpos($haystack, $keyword) === false) {
                    continue;
                }
            }

            if (preg_match("/room_type = '([^']+)'/i", $sql, $m) && ($room['room_type'] ?? '') !== $m[1]) {
                continue;
            }

            if (preg_match("/price <= ([0-9.]+)/i", $sql, $m) && (float) ($room['price'] ?? 0) > (float) $m[1]) {
                continue;
            }

            if (preg_match("/capacity >= (\d+)/i", $sql, $m) && (int) ($room['capacity'] ?? 0) < (int) $m[1]) {
                continue;
            }

            if (preg_match("/id NOT IN \(SELECT room_id FROM bookings WHERE \(check_in_date < '([^']+)' AND check_out_date > '([^']+)'\) AND status != 'cancelled'\)/i", $sql, $m)) {
                $blocked = false;
                foreach ($this->findMany('bookings', ['room_id' => (int) $room['id']]) as $booking) {
                    if (($booking['status'] ?? '') !== 'cancelled' && $booking['check_in_date'] < $m[1] && $booking['check_out_date'] > $m[2]) {
                        $blocked = true;
                        break;
                    }
                }
                if ($blocked) {
                    continue;
                }
            }

            $output[] = $room;
        }

        return $output;
    }

    private function filterRooms($whereSql) {
        $rooms = $this->findMany('rooms', []);
        $output = [];

        foreach ($rooms as $room) {
            if (preg_match("/status = '([^']+)'/i", $whereSql, $m) && ($room['status'] ?? '') !== $m[1]) {
                continue;
            }

            if (preg_match("/room_name LIKE '%([^']+)%' OR address LIKE '%([^']+)%'/i", $whereSql, $m)) {
                $keyword = mb_strtolower($m[1]);
                $haystack = mb_strtolower(($room['room_name'] ?? '') . ' ' . ($room['address'] ?? ''));
                if (mb_strpos($haystack, $keyword) === false) {
                    continue;
                }
            }

            if (preg_match("/room_type IN \(([^)]+)\)/i", $whereSql, $m)) {
                $types = array_map(function ($item) {
                    return trim($item, " '");
                }, explode(',', $m[1]));
                if (!in_array($room['room_type'] ?? '', $types, true)) {
                    continue;
                }
            }

            if (preg_match("/\((.+price.+)\)/i", $whereSql, $m) && str_contains($m[1], 'price')) {
                $priceOk = false;
                if (preg_match("/price < 500000/i", $m[1]) && (float) $room['price'] < 500000) {
                    $priceOk = true;
                }
                if (preg_match("/price BETWEEN 500000 AND 2000000/i", $m[1]) && (float) $room['price'] >= 500000 && (float) $room['price'] <= 2000000) {
                    $priceOk = true;
                }
                if (preg_match("/price > 2000000/i", $m[1]) && (float) $room['price'] > 2000000) {
                    $priceOk = true;
                }
                if (!$priceOk) {
                    continue;
                }
            }

            $output[] = $room;
        }

        return $output;
    }

    private function bookingJoinRows($limitRecent = false, $includePhone = false, $limit = null) {
        $bookings = $this->findMany('bookings', [], ['sort' => ['created_at' => -1]]);
        $rows = [];
        foreach ($bookings as $booking) {
            $user = $this->findOne('users', ['id' => (int) $booking['user_id']]);
            $room = $this->findOne('rooms', ['id' => (int) $booking['room_id']]);
            if (!$user || !$room) {
                continue;
            }

            $booking['full_name'] = $user['full_name'];
            $booking['room_name'] = $room['room_name'];
            if ($includePhone) {
                $booking['phone'] = $user['phone'] ?? '';
            }
            $rows[] = $booking;
            if ($limitRecent && $limit !== null && count($rows) >= $limit) {
                break;
            }
        }
        return $rows;
    }

    private function command(array $command) {
        $cursor = $this->manager->executeCommand($this->dbName, new MongoDB\Driver\Command($command));
        $rows = $cursor->toArray();
        return $rows[0] ?? null;
    }

    private function namespace($collection) {
        return $this->dbName . '.' . $collection;
    }

    private function collectionExists($collection) {
        $cursor = $this->manager->executeCommand($this->dbName, new MongoDB\Driver\Command(['listCollections' => 1, 'filter' => ['name' => $collection]]));
        return count($cursor->toArray()) > 0;
    }

    private function findMany($collection, array $filter = [], array $options = []) {
        $projection = [];
        if (!empty($options['projection'])) {
            foreach ($options['projection'] as $field) {
                $projection[$field] = 1;
            }
        }

        $queryOptions = [];
        if ($projection) {
            $queryOptions['projection'] = $projection;
        }
        if (!empty($options['sort'])) {
            $queryOptions['sort'] = $options['sort'];
        }
        if (isset($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
        }
        if (isset($options['skip'])) {
            $queryOptions['skip'] = $options['skip'];
        }

        $cursor = $this->manager->executeQuery($this->namespace($collection), new MongoDB\Driver\Query($filter, $queryOptions));
        $rows = [];
        foreach ($cursor as $doc) {
            $row = bson_to_array($doc);
            unset($row['_id']);
            $rows[] = $row;
        }
        return $rows;
    }

    private function findOne($collection, array $filter = [], array $fields = []) {
        $options = ['limit' => 1];
        if ($fields) {
            $options['projection'] = $fields;
        }
        $rows = $this->findMany($collection, $filter, $options);
        return $rows[0] ?? null;
    }

    private function countDocuments($collection, array $filter = []) {
        return count($this->findMany($collection, $filter));
    }

    private function maxId($collection) {
        $rows = $this->findMany($collection, [], ['sort' => ['id' => -1], 'limit' => 1]);
        return isset($rows[0]['id']) ? (int) $rows[0]['id'] : 0;
    }

    private function setCounter($collection, $seq) {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['_id' => $collection],
            ['$set' => ['seq' => (int) $seq]],
            ['upsert' => true]
        );
        $this->manager->executeBulkWrite($this->namespace('counters'), $bulk);
    }

    private function nextSequence($collection) {
        $result = $this->command([
            'findAndModify' => 'counters',
            'query' => ['_id' => $collection],
            'update' => ['$inc' => ['seq' => 1]],
            'new' => true,
            'upsert' => true,
        ]);

        $value = bson_to_array($result);
        return isset($value['value']['seq']) ? (int) $value['value']['seq'] : 1;
    }

    private function insertDocument($collection, array $document, $assignId = true) {
        if ($assignId && !isset($document['id'])) {
            $document['id'] = $this->nextSequence($collection);
        } elseif (isset($document['id'])) {
            $document['id'] = (int) $document['id'];
        }

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        $this->manager->executeBulkWrite($this->namespace($collection), $bulk);
        $this->insert_id = $document['id'] ?? 0;
        return true;
    }

    private function updateOne($collection, array $filter, array $update) {
        $existing = $this->findOne($collection, $filter);
        if (!$existing) {
            return true;
        }
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update($filter, $update, ['multi' => false, 'upsert' => false]);
        $this->manager->executeBulkWrite($this->namespace($collection), $bulk);
        return true;
    }

    private function deleteOne($collection, array $filter) {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete($filter, ['limit' => 1]);
        $this->manager->executeBulkWrite($this->namespace($collection), $bulk);
        return true;
    }

    private function deleteMany($collection, array $filter) {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete($filter, ['limit' => 0]);
        $this->manager->executeBulkWrite($this->namespace($collection), $bulk);
        return true;
    }

    private function deleteCascadeRoom($roomId) {
        $this->deleteOne('rooms', ['id' => $roomId]);
        $this->deleteMany('room_images', ['room_id' => $roomId]);
        $this->deleteMany('bookings', ['room_id' => $roomId]);
        $this->deleteMany('favorites', ['room_id' => $roomId]);
        return true;
    }

    private function deleteCascadeBooking($bookingId) {
        $this->deleteOne('bookings', ['id' => $bookingId]);
        $this->deleteMany('payments', ['booking_id' => $bookingId]);
        return true;
    }
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

function ensure_default_admin_pg(PDO $pdo) {
    $adminEmail = env_value('DEFAULT_ADMIN_EMAIL', 'admin@gmail.com');
    $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $statement->execute([$adminEmail]);
    if ((int) $statement->fetchColumn() > 0) {
        return;
    }

    $insert = $pdo->prepare("
        INSERT INTO users (full_name, email, password, phone, role)
        VALUES (?, ?, ?, ?, 'admin')
    ");
    $insert->execute([
        env_value('DEFAULT_ADMIN_NAME', 'Group 4'),
        $adminEmail,
        env_value('DEFAULT_ADMIN_PASSWORD', '123456'),
        env_value('DEFAULT_ADMIN_PHONE', '0765933135')
    ]);
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
        ensure_default_admin_pg($pdo);
    } catch (Throwable $e) {
        $conn->error = $e->getMessage();
    }
}

$dbConfig = parse_database_config();

if ($dbConfig['driver'] === 'mongodb') {
    $conn = new MongoCompatConnection($dbConfig);
    if ($conn->connect_error) {
        die('Kết nối MongoDB thất bại: ' . $conn->connect_error);
    }
    if (env_flag('AUTO_SEED', true)) {
        $conn->bootstrap();
    }
} elseif ($dbConfig['driver'] === 'pgsql') {
    $conn = new PgCompatConnection($dbConfig);
    if ($conn->connect_error) {
        die('Kết nối database thất bại: ' . $conn->connect_error);
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
        die('Kết nối database thất bại: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
}

?>
