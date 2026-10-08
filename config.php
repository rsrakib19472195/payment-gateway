<?php
declare(strict_types=1);

/*
 * TWIXO API SUPPORT SERVER
 * Configure these values before deployment.
 */

const API_KEY = 'CHANGE_THIS_TO_A_LONG_RANDOM_KEY';
const DB_FILE = __DIR__ . '/data/twixo.sqlite';

const CORS_ORIGIN = '*';
const MAX_BODY_BYTES = 1024 * 1024; // 1 MB
const LOG_FILE = __DIR__ . '/data/api.log';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dir = dirname(DB_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA busy_timeout=5000');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id TEXT NOT NULL,
            service TEXT NOT NULL,
            sender TEXT,
            amount REAL,
            sms_balance REAL,
            raw_sms TEXT,
            verification_reason TEXT,
            device_id TEXT,
            sms_timestamp INTEGER,
            payload_json TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'received',
            api_status TEXT NOT NULL DEFAULT 'received',
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL,
            UNIQUE(transaction_id, service)
        )
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_transactions_created
        ON transactions(created_at DESC)
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_transactions_service
        ON transactions(service)
    ");

    return $pdo;
}

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function log_api(string $message): void {
    $line = '[' . date('c') . '] ' . $message . PHP_EOL;
    @file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function authenticate(): void {
    $key = $_SERVER['HTTP_X_ACCESS_KEY'] ?? '';

    if ($key === '') {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', trim($auth), $m)) {
            $key = trim($m[1]);
        }
    }

    if (!hash_equals(API_KEY, (string)$key)) {
        log_api('Unauthorized request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        json_response([
            'success' => false,
            'error' => 'unauthorized',
            'message' => 'Invalid or missing API key'
        ], 401);
    }
}

function request_json(): array {
    $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length > MAX_BODY_BYTES) {
        json_response(['success'=>false,'error'=>'payload_too_large'], 413);
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        json_response(['success'=>false,'error'=>'empty_body'], 400);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['success'=>false,'error'=>'invalid_json'], 400);
    }

    return [$data, $raw];
}

function first_value(array $data, array $keys, mixed $default = null): mixed {
    foreach ($keys as $key) {
        if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
            return $data[$key];
        }
    }
    return $default;
}
