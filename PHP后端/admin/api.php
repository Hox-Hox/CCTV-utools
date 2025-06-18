<?php
require_once 'config.php';
require_once 'includes/header.php';

// 获取服务器域名和协议
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $domain;

// API接口地址
$api_url = $base_url . dirname($_SERVER['PHP_SELF']) . '/../api/streams.php';
?>

<!-- API说明卡片 -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">API接口说明</h3>
            </div>
            <div class="card-body">
                <p>本系统提供以下API接口，可用于对接其他应用或播放器。所有接口均返回JSON格式数据。</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    API接口无需认证，可直接访问。
                </div>
                
                <h5 class="mt-4">1. 获取所有直播源</h5>
                <div class="api-code mb-3">
                    <div class="d-flex justify-content-between">
                        <code><?php echo $api_url; ?></code>
                        <button class="btn btn-sm btn-outline-primary copy-api" data-api="<?php echo $api_url; ?>">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                <p>此接口返回所有直播源数据，包括分类信息。</p>
                
                <h5 class="mt-4">2. 按分类获取直播源</h5>
                <div class="api-code mb-3">
                    <div class="d-flex justify-content-between">
                        <code><?php echo $api_url; ?>?category_id=1</code>
                        <button class="btn btn-sm btn-outline-primary copy-api" data-api="<?php echo $api_url; ?>?category_id=1">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                <p>将 <code>category_id</code> 参数替换为实际的分类ID，可获取该分类下的所有直播源。</p>
                
                <h5 class="mt-4">3. 获取分类列表</h5>
                <div class="api-code mb-3">
                    <div class="d-flex justify-content-between">
                        <code><?php echo $api_url; ?>?type=categories</code>
                        <button class="btn btn-sm btn-outline-primary copy-api" data-api="<?php echo $api_url; ?>?type=categories">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                <p>此接口返回所有分类数据。</p>
                
                <h5 class="mt-4">4. 获取单个直播源</h5>
                <div class="api-code mb-3">
                    <div class="d-flex justify-content-between">
                        <code><?php echo $api_url; ?>?id=1</code>
                        <button class="btn btn-sm btn-outline-primary copy-api" data-api="<?php echo $api_url; ?>?id=1">
                            <i class="fas fa-copy"></i> 复制
                        </button>
                    </div>
                </div>
                <p>将 <code>id</code> 参数替换为实际的直播源ID，可获取单个直播源详细信息。</p>
            </div>
        </div>
    </div>
</div>

<!-- 返回格式说明 -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">API返回格式说明</h3>
            </div>
            <div class="card-body">
                <h5>获取所有直播源返回格式示例：</h5>
                <pre class="api-code">
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": 1,
            "category_id": 1,
            "category_name": "CCTV",
            "name": "CCTV-1",
            "icon": "fa-circle-play",
            "url": "http://example.com/cctv1.m3u8",
            "sort": 1
        },
        ...
    ]
}
                </pre>
                
                <h5 class="mt-4">获取分类列表返回格式示例：</h5>
                <pre class="api-code">
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": 1,
            "name": "CCTV",
            "icon": "fa-tv",
            "sort": 1
        },
        {
            "id": 2,
            "name": "卫视",
            "icon": "fa-map",
            "sort": 2
        },
        ...
    ]
}
                </pre>
            </div>
        </div>
    </div>
</div>

<!-- 调用示例 -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">API调用示例</h3>
            </div>
            <div class="card-body">
                <h5>JavaScript调用示例：</h5>
                <pre class="api-code">
fetch('<?php echo $api_url; ?>')
    .then(response => response.json())
    .then(data => {
        if (data.code === 200) {
            console.log('获取成功:', data.data);
            // 处理数据...
        } else {
            console.error('获取失败:', data.message);
        }
    })
    .catch(error => {
        console.error('请求错误:', error);
    });
                </pre>
                
                <h5 class="mt-4">PHP调用示例：</h5>
                <pre class="api-code">
&lt;?php
$api_url = '<?php echo $api_url; ?>';
$response = file_get_contents($api_url);
$data = json_decode($response, true);

if ($data['code'] === 200) {
    // 处理数据
    $streams = $data['data'];
    foreach ($streams as $stream) {
        echo $stream['name'] . ': ' . $stream['url'] . "\n";
    }
} else {
    echo '获取失败: ' . $data['message'];
}
?&gt;
                </pre>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 复制API地址功能
    document.querySelectorAll('.copy-api').forEach(function(button) {
        button.addEventListener('click', function() {
            const api = this.getAttribute('data-api');
            copyToClipboard(api);
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?> 