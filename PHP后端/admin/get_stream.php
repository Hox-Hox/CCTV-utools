<?php
/**
 * 获取单个直播源数据API
 * 用于编辑功能AJAX请求
 */

// 设置内容类型
header('Content-Type: application/json; charset=utf-8');

// 引入配置文件
require_once 'config.php';

// 检查是否登录
if (!is_logged_in()) {
    echo json_encode([
        'success' => false, 
        'message' => '未授权访问'
    ]);
    exit;
}

// 获取ID参数
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => '缺少有效的ID参数'
    ]);
    exit;
}

// 读取直播源数据
$streams = read_json_file(STREAMS_FILE);
$stream = null;

// 查找对应ID的直播源
foreach ($streams as $s) {
    if ($s['id'] == $id) {
        $stream = $s;
        break;
    }
}

// 返回结果
if ($stream) {
    echo json_encode([
        'success' => true,
        'data' => $stream
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '未找到对应ID的直播源'
    ]);
} 