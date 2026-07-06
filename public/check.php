<?php

/**
 * check.php - Kiểm tra môi trường cần thiết để chạy project (Dylan HRM / Laravel 13).
 *
 * Đặt tại thư mục public/ (document root của Laravel) để kiểm tra qua trình duyệt.
 *
 * Cách dùng:
 *   - Trình duyệt: truy cập http://your-host/check.php
 *   - CLI:         php public/check.php
 *
 * Script này KHÔNG cần Laravel/composer để chạy, dùng để kiểm tra nhanh
 * server đã đủ điều kiện cài đặt project hay chưa.
 */

// ---------------------------------------------------------------------------
// Cấu hình yêu cầu (đọc/khớp theo composer.json + .env.example của project)
// ---------------------------------------------------------------------------

$isCli = (PHP_SAPI === 'cli');

// Phiên bản PHP tối thiểu (composer.json: "php": "^8.3")
$minPhpVersion = '8.3.0';

// Các PHP extension bắt buộc cho Laravel + MySQL.
$requiredExtensions = [
    'openssl',
    'pdo',
    'pdo_mysql',   // DB_CONNECTION=mysql
    'mbstring',
    'tokenizer',
    'xml',
    'ctype',
    'json',
    'bcmath',
    'fileinfo',
    'curl',
    'dom',
    'filter',
    'hash',
    'session',
    'pcre',
];

// Extension khuyến nghị (không bắt buộc nhưng nên có).
$recommendedExtensions = [
    'intl',
    'gd',
    'zip',
    'redis',       // REDIS_CLIENT=phpredis trong .env.example
    'exif',
    'sodium',
];

// Cấu hình PHP nên bật/kiểm tra (setting => giá trị mong muốn để hiển thị).
$phpIniChecks = [
    // key => [label, hàm kiểm tra trả về bool, mô tả giá trị hiện tại]
];

// Thư mục cần quyền ghi (Laravel).
$writableDirs = [
    'storage',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

// Command dòng lệnh cần có.
$requiredBinaries = [
    'composer' => 'Quản lý dependency PHP',
];
$recommendedBinaries = [
    'node' => 'Chạy Vite build (frontend)',
    'npm'  => 'Cài package frontend',
    'mysql' => 'MySQL client',
    'git'  => 'Quản lý version',
];

// File nằm trong public/, nên thư mục gốc project là thư mục cha.
// Nếu chạy trực tiếp ở root thì tự fallback về __DIR__.
$baseDir = is_dir(dirname(__DIR__) . '/vendor') || file_exists(dirname(__DIR__) . '/composer.json')
    ? dirname(__DIR__)
    : __DIR__;

// ---------------------------------------------------------------------------
// Helper hiển thị (hỗ trợ cả CLI có màu và HTML)
// ---------------------------------------------------------------------------

$results = [];   // gom kết quả để tổng kết
function record(&$results, $status)
{
    // $status: 'ok' | 'fail' | 'warn'
    $results[] = $status;
}

function fmt($status, $label, $detail = '', $isCli = true)
{
    $icons = [
        'ok'   => ['[ OK ]', "\033[32m", '#16a34a', '✅'],
        'fail' => ['[FAIL]', "\033[31m", '#dc2626', '❌'],
        'warn' => ['[WARN]', "\033[33m", '#d97706', '⚠️'],
        'info' => ['[INFO]', "\033[36m", '#2563eb', 'ℹ️'],
    ];
    [$tag, $color, $html, $emoji] = $icons[$status] ?? $icons['info'];

    if ($isCli) {
        $reset = "\033[0m";
        $line = sprintf('%s%s%s %s', $color, $tag, $reset, $label);
        if ($detail !== '') {
            $line .= "  \033[90m" . $detail . $reset;
        }
        echo $line . PHP_EOL;
    } else {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES);
        $safeDetail = htmlspecialchars($detail, ENT_QUOTES);
        echo '<div class="row">';
        echo '<span class="badge" style="background:' . $html . '">' . $emoji . '</span>';
        echo '<span class="label">' . $safeLabel . '</span>';
        if ($safeDetail !== '') {
            echo '<span class="detail">' . $safeDetail . '</span>';
        }
        echo '</div>';
    }
}

function section($title, $isCli)
{
    if ($isCli) {
        echo PHP_EOL . "\033[1m== " . $title . " ==\033[0m" . PHP_EOL;
    } else {
        echo '<h2>' . htmlspecialchars($title, ENT_QUOTES) . '</h2>';
    }
}

function commandExists($cmd)
{
    $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'command -v';
    $out = @shell_exec($which . ' ' . escapeshellarg($cmd) . ' 2>/dev/null');
    return !empty(trim((string) $out));
}

// ---------------------------------------------------------------------------
// Bắt đầu output
// ---------------------------------------------------------------------------

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Kiểm tra môi trường - Dylan HRM</title>';
    echo '<style>
        body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:24px;}
        .wrap{max-width:900px;margin:0 auto;}
        h1{font-size:22px;margin:0 0 4px;}
        .sub{color:#94a3b8;margin-bottom:20px;font-size:14px;}
        h2{font-size:15px;color:#38bdf8;margin:24px 0 8px;border-bottom:1px solid #1e293b;padding-bottom:6px;}
        .row{display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:8px;}
        .row:hover{background:#1e293b;}
        .badge{width:22px;text-align:center;}
        .label{flex:0 0 auto;min-width:220px;font-weight:500;}
        .detail{color:#94a3b8;font-size:13px;}
        .summary{margin-top:28px;padding:16px;border-radius:12px;font-size:15px;font-weight:600;}
        .summary.ok{background:#052e16;color:#4ade80;border:1px solid #166534;}
        .summary.fail{background:#450a0a;color:#f87171;border:1px solid #991b1b;}
    </style></head><body><div class="wrap">';
    echo '<h1>Kiểm tra môi trường cài đặt</h1>';
    echo '<div class="sub">Project: Dylan HRM (Laravel 13) — yêu cầu PHP ^8.3, MySQL</div>';
} else {
    echo "\033[1m" . '===========================================' . "\033[0m" . PHP_EOL;
    echo "\033[1m" . '  Kiểm tra môi trường - Dylan HRM (Laravel)' . "\033[0m" . PHP_EOL;
    echo "\033[1m" . '===========================================' . "\033[0m" . PHP_EOL;
}

// --- 1. Phiên bản PHP ---
section('Phiên bản PHP', $isCli);
$currentPhp = PHP_VERSION;
if (version_compare($currentPhp, $minPhpVersion, '>=')) {
    fmt('ok', 'PHP version', "Yêu cầu >= {$minPhpVersion}, hiện tại: {$currentPhp}", $isCli);
    record($results, 'ok');
} else {
    fmt('fail', 'PHP version', "Cần >= {$minPhpVersion}, hiện tại: {$currentPhp}", $isCli);
    record($results, 'fail');
}
fmt('info', 'PHP SAPI', PHP_SAPI, $isCli);
fmt('info', 'Kiến trúc', (PHP_INT_SIZE * 8) . '-bit', $isCli);

// --- 2. Extension bắt buộc ---
section('PHP Extensions (bắt buộc)', $isCli);
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        fmt('ok', $ext, 'đã cài', $isCli);
        record($results, 'ok');
    } else {
        fmt('fail', $ext, 'THIẾU - cần cài đặt', $isCli);
        record($results, 'fail');
    }
}

// --- 3. Extension khuyến nghị ---
section('PHP Extensions (khuyến nghị)', $isCli);
foreach ($recommendedExtensions as $ext) {
    if (extension_loaded($ext)) {
        fmt('ok', $ext, 'đã cài', $isCli);
    } else {
        fmt('warn', $ext, 'chưa có (không bắt buộc)', $isCli);
    }
}

// --- 4. Cấu hình PHP ---
section('Cấu hình PHP', $isCli);
$memory = ini_get('memory_limit');
fmt('info', 'memory_limit', (string) $memory, $isCli);
$maxExec = ini_get('max_execution_time');
fmt('info', 'max_execution_time', (string) $maxExec, $isCli);
$uploadMax = ini_get('upload_max_filesize');
fmt('info', 'upload_max_filesize', (string) $uploadMax, $isCli);
$postMax = ini_get('post_max_size');
fmt('info', 'post_max_size', (string) $postMax, $isCli);

// --- 5. Quyền ghi thư mục ---
section('Quyền ghi thư mục (Laravel)', $isCli);
foreach ($writableDirs as $dir) {
    $path = $baseDir . DIRECTORY_SEPARATOR . $dir;
    if (!file_exists($path)) {
        fmt('warn', $dir, 'chưa tồn tại (sẽ được tạo khi cài Laravel)', $isCli);
        continue;
    }
    if (is_writable($path)) {
        fmt('ok', $dir, 'ghi được', $isCli);
        record($results, 'ok');
    } else {
        fmt('fail', $dir, 'KHÔNG ghi được - cần chmod', $isCli);
        record($results, 'fail');
    }
}

// --- 6. File cấu hình ---
section('File cấu hình', $isCli);
if (file_exists($baseDir . '/.env')) {
    fmt('ok', '.env', 'đã tồn tại', $isCli);
    record($results, 'ok');
} else {
    fmt('warn', '.env', 'chưa có (chạy: cp .env.example .env)', $isCli);
}
if (file_exists($baseDir . '/vendor/autoload.php')) {
    fmt('ok', 'vendor/', 'dependencies đã cài', $isCli);
} else {
    fmt('warn', 'vendor/', 'chưa cài (chạy: composer install)', $isCli);
}

// --- 7. Công cụ dòng lệnh (chỉ chạy được ở CLI, shell_exec) ---
if (function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))))) {
    section('Công cụ dòng lệnh', $isCli);
    foreach ($requiredBinaries as $bin => $desc) {
        if (commandExists($bin)) {
            fmt('ok', $bin, $desc, $isCli);
            record($results, 'ok');
        } else {
            fmt('fail', $bin, "THIẾU - {$desc}", $isCli);
            record($results, 'fail');
        }
    }
    foreach ($recommendedBinaries as $bin => $desc) {
        if (commandExists($bin)) {
            fmt('ok', $bin, $desc, $isCli);
        } else {
            fmt('warn', $bin, "chưa có - {$desc}", $isCli);
        }
    }
}

// --- 8. Kết nối MySQL (thử nếu có thông tin trong .env) ---
section('Kết nối cơ sở dữ liệu (MySQL)', $isCli);
$env = [];
if (file_exists($baseDir . '/.env')) {
    foreach (file($baseDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ln) {
        $ln = trim($ln);
        if ($ln === '' || $ln[0] === '#' || !str_contains($ln, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $ln, 2);
        $env[trim($k)] = trim($v, " \"'");
    }
}
if (($env['DB_CONNECTION'] ?? 'mysql') === 'mysql' && extension_loaded('pdo_mysql') && !empty($env['DB_DATABASE'])) {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $dbname = $env['DB_DATABASE'];
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
        new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        fmt('ok', 'MySQL', "kết nối thành công tới {$host}:{$port}/{$dbname}", $isCli);
        record($results, 'ok');
    } catch (Throwable $e) {
        fmt('fail', 'MySQL', 'không kết nối được: ' . $e->getMessage(), $isCli);
        record($results, 'fail');
    }
} else {
    fmt('info', 'MySQL', 'bỏ qua (chưa có .env hợp lệ hoặc thiếu pdo_mysql)', $isCli);
}

// ---------------------------------------------------------------------------
// Tổng kết
// ---------------------------------------------------------------------------
$failCount = count(array_filter($results, fn ($s) => $s === 'fail'));

if ($isCli) {
    echo PHP_EOL;
    if ($failCount === 0) {
        echo "\033[42m\033[30m ✔ Môi trường ĐỦ điều kiện để cài đặt project. \033[0m" . PHP_EOL;
    } else {
        echo "\033[41m\033[37m ✘ Có {$failCount} mục lỗi cần khắc phục trước khi cài đặt. \033[0m" . PHP_EOL;
    }
    echo PHP_EOL;
    exit($failCount === 0 ? 0 : 1);
} else {
    if ($failCount === 0) {
        echo '<div class="summary ok">✅ Môi trường ĐỦ điều kiện để cài đặt project.</div>';
    } else {
        echo '<div class="summary fail">❌ Có ' . $failCount . ' mục lỗi cần khắc phục trước khi cài đặt.</div>';
    }
    echo '</div></body></html>';
}
