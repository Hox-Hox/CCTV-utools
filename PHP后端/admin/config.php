<?php
/**
 * CCTV直播源管理系统配置文件
 */

// 启用输出缓冲，解决headers already sent问题
if(!ob_get_level()) {
    ob_start();
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 系统配置
define('APP_NAME', 'CCTV直播源管理系统');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Asia/Shanghai');

// 登录凭证（安全考虑，实际应用中应使用更安全的方式）
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // 建议改为更复杂的密码

// 数据文件路径
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('CATEGORIES_FILE', DATA_PATH . 'categories.json');
define('STREAMS_FILE', DATA_PATH . 'streams.json');

// 系统URL配置
define('BASE_URL', ''); // 设置您的网站基础URL，如: 'http://example.com/'
define('ADMIN_URL', BASE_URL . 'admin/');
define('API_URL', BASE_URL . 'api/');

// 会话配置
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 检查用户是否已登录
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 确保用户已登录，否则重定向到登录页面
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 读取JSON数据文件
 * @param string $file 文件路径
 * @return array
 */
function read_json_file($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * 保存数据到JSON文件
 * @param string $file 文件路径
 * @param array $data 要保存的数据
 * @return bool
 */
function save_json_file($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
} 