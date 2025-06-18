<?php
/**
 * CCTV直播源管理系统 - API接口
 */

// 设置跨域访问允许
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// 引入配置文件
require_once dirname(__DIR__) . '/admin/config.php';

// 获取请求参数
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// 获取分类数据
$categories = read_json_file(CATEGORIES_FILE);
$categories_map = [];

// 创建分类映射，方便查找
foreach ($categories as $category) {
    $categories_map[$category['id']] = $category;
}

// 处理请求类型
if ($type === 'categories') {
    // 只返回分类列表
    usort($categories, function($a, $b) {
        return $a['sort'] - $b['sort'];
    });
    
    api_response(200, 'success', $categories);
} else {
    // 获取直播源数据
    $streams = read_json_file(STREAMS_FILE);
    
    // 处理单个直播源请求
    if ($id > 0) {
        $stream = null;
        foreach ($streams as $s) {
            if ($s['id'] == $id) {
                $stream = $s;
                // 添加分类名称
                if (isset($categories_map[$s['category_id']])) {
                    $stream['category_name'] = $categories_map[$s['category_id']]['name'];
                } else {
                    $stream['category_name'] = '未分类';
                }
                break;
            }
        }
        
        if ($stream) {
            api_response(200, 'success', $stream);
        } else {
            api_response(404, '直播源不存在', null);
        }
    }
    
    // 处理分类筛选
    if ($category_id > 0) {
        $filtered_streams = [];
        foreach ($streams as $stream) {
            if ($stream['category_id'] == $category_id) {
                // 添加分类名称
                if (isset($categories_map[$stream['category_id']])) {
                    $stream['category_name'] = $categories_map[$stream['category_id']]['name'];
                } else {
                    $stream['category_name'] = '未分类';
                }
                $filtered_streams[] = $stream;
            }
        }
        
        if (empty($filtered_streams)) {
            api_response(404, '此分类下没有直播源', []);
        } else {
            // 按排序升序排序
            usort($filtered_streams, function($a, $b) {
                return $a['sort'] - $b['sort'];
            });
            
            api_response(200, 'success', $filtered_streams);
        }
    } else {
        // 返回所有直播源
        // 先按分类排序，再按自定义排序
        usort($streams, function($a, $b) {
            if ($a['category_id'] == $b['category_id']) {
                return $a['sort'] - $b['sort'];
            }
            return $a['category_id'] - $b['category_id'];
        });
        
        // 添加分类名称
        foreach ($streams as &$stream) {
            if (isset($categories_map[$stream['category_id']])) {
                $stream['category_name'] = $categories_map[$stream['category_id']]['name'];
            } else {
                $stream['category_name'] = '未分类';
            }
        }
        
        api_response(200, 'success', $streams);
    }
}

/**
 * 输出API响应
 * 
 * @param int $code 状态码
 * @param string $message 消息
 * @param mixed $data 数据
 */
function api_response($code, $message, $data) {
    $response = [
        'code' => $code,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
} 