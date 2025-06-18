<?php
/**
 * 导出直播源文件
 * 支持导出为m3u8格式
 */

// 开启输出缓冲
ob_start();

// 引入配置文件
require_once 'config.php';

// 检查是否登录
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// 获取导出格式和筛选类别
$format = isset($_GET['format']) ? $_GET['format'] : 'm3u8';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// 获取分类和直播源数据
$categories = read_json_file(CATEGORIES_FILE);
$streams = read_json_file(STREAMS_FILE);

// 排序分类和直播源
usort($categories, function($a, $b) {
    return $a['sort'] - $b['sort'];
});

usort($streams, function($a, $b) {
    if ($a['category_id'] == $b['category_id']) {
        return $a['sort'] - $b['sort'];
    }
    return $a['category_id'] - $b['category_id'];
});

// 如果有分类筛选，则只保留该分类的直播源
if ($category_id > 0) {
    $filtered_streams = [];
    foreach ($streams as $stream) {
        if ($stream['category_id'] == $category_id) {
            $filtered_streams[] = $stream;
        }
    }
    $streams = $filtered_streams;
}

// 根据格式导出数据
if ($format === 'm3u8') {
    // 设置响应头
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="tv_channels.m3u8"');
    
    // 输出m3u8文件头
    echo "#EXTM3U\n";
    
    // 按分类组织输出
    $current_category_id = 0;
    
    foreach ($streams as $stream) {
        // 查找分类名
        $category_name = '未分类';
        foreach ($categories as $category) {
            if ($category['id'] == $stream['category_id']) {
                $category_name = $category['name'];
                break;
            }
        }
        
        // 如果是新的分类，添加分类注释
        if ($stream['category_id'] != $current_category_id) {
            echo "\n# {$category_name}\n";
            $current_category_id = $stream['category_id'];
        }
        
        // 输出直播源信息
        echo "#EXTINF:-1 tvg-name=\"{$stream['name']}\" tvg-logo=\"\" group-title=\"{$category_name}\",{$stream['name']}\n";
        echo "{$stream['url']}\n";
    }
    
    exit;
} elseif ($format === 'txt') {
    // 设置响应头
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="tv_channels.txt"');
    
    // 按分类组织输出
    $current_category_id = 0;
    
    foreach ($streams as $stream) {
        // 查找分类名
        $category_name = '未分类';
        foreach ($categories as $category) {
            if ($category['id'] == $stream['category_id']) {
                $category_name = $category['name'];
                break;
            }
        }
        
        // 如果是新的分类，添加分类标题
        if ($stream['category_id'] != $current_category_id) {
            echo "\n----- {$category_name} -----\n\n";
            $current_category_id = $stream['category_id'];
        }
        
        // 输出直播源信息
        echo "{$stream['name']}\n";
        echo "{$stream['url']}\n\n";
    }
    
    exit;
} elseif ($format === 'json') {
    // 设置响应头
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="tv_channels.json"');
    
    // 准备JSON数据
    $export_data = [];
    
    foreach ($categories as $category) {
        $category_streams = [];
        
        foreach ($streams as $stream) {
            if ($stream['category_id'] == $category['id']) {
                $category_streams[] = [
                    'name' => $stream['name'],
                    'url' => $stream['url']
                ];
            }
        }
        
        if (!empty($category_streams) || $category_id == 0) {
            $export_data[] = [
                'category' => $category['name'],
                'streams' => $category_streams
            ];
        }
    }
    
    // 输出JSON
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} else {
    // 不支持的格式，重定向回管理页面
    header('Location: streams.php?message=' . urlencode('不支持的导出格式') . '&type=error');
    exit;
}
?> 