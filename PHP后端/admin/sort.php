<?php
/**
 * 排序处理文件
 * 用于处理拖拽排序功能
 */

// 开启输出缓冲
ob_start();

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

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不正确'
    ]);
    exit;
}

// 获取POST数据
$type = isset($_POST['type']) ? $_POST['type'] : '';
$items = isset($_POST['items']) ? $_POST['items'] : [];

// 验证数据
if (empty($type) || empty($items) || !is_array($items)) {
    echo json_encode([
        'success' => false,
        'message' => '参数错误'
    ]);
    exit;
}

// 根据不同类型处理排序
if ($type === 'category') {
    // 处理分类排序
    $categories = read_json_file(CATEGORIES_FILE);
    $result = processSorting($categories, $items);
    
    if ($result['success']) {
        // 保存数据
        if (save_json_file(CATEGORIES_FILE, $result['data'])) {
            echo json_encode([
                'success' => true,
                'message' => '分类排序已更新'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '保存数据失败，请检查文件权限'
            ]);
        }
    } else {
        echo json_encode($result);
    }
} elseif ($type === 'stream') {
    // 处理直播源排序
    $streams = read_json_file(STREAMS_FILE);
    $result = processSorting($streams, $items);
    
    if ($result['success']) {
        // 保存数据
        if (save_json_file(STREAMS_FILE, $result['data'])) {
            echo json_encode([
                'success' => true,
                'message' => '直播源排序已更新'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '保存数据失败，请检查文件权限'
            ]);
        }
    } else {
        echo json_encode($result);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '未知的排序类型'
    ]);
}

// 处理排序的函数
function processSorting($items, $sortedIds) {
    // 创建ID到索引的映射
    $idToIndex = [];
    foreach ($sortedIds as $index => $id) {
        $idToIndex[$id] = $index + 1; // 从1开始排序
    }
    
    // 更新排序值
    $updated = false;
    foreach ($items as $key => $item) {
        if (isset($idToIndex[$item['id']])) {
            if ($items[$key]['sort'] != $idToIndex[$item['id']]) {
                $items[$key]['sort'] = $idToIndex[$item['id']];
                $updated = true;
            }
        }
    }
    
    // 如果已更新，按新的排序值重新排序
    if ($updated) {
        usort($items, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });
    }
    
    return [
        'success' => true,
        'data' => $items,
        'updated' => $updated
    ];
} 