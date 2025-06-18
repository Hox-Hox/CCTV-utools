<?php
require_once 'config.php';
require_once 'includes/header.php';

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

// 获取最近添加的直播源（最后5个）
$recent_streams = array_slice(array_reverse($streams), 0, 5);

// 统计每个分类的直播源数量
$category_counts = [];
foreach ($categories as $category) {
    $category_counts[$category['id']] = 0;
}

foreach ($streams as $stream) {
    if (isset($category_counts[$stream['category_id']])) {
        $category_counts[$stream['category_id']]++;
    }
}

// 获取系统信息
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'os' => PHP_OS,
    'max_upload_size' => ini_get('upload_max_filesize'),
    'max_post_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'datetime' => date('Y-m-d H:i:s')
];
?>

<!-- 统计卡片 -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo count($streams); ?></h3>
                <p>直播源总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <a href="streams.php" class="small-box-footer">
                管理直播源 <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo count($categories); ?></h3>
                <p>分类总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-folder"></i>
            </div>
            <a href="categories.php" class="small-box-footer">
                管理分类 <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo date('Y-m-d'); ?></h3>
                <p>当前日期</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="small-box-footer">
                <?php echo date('H:i:s'); ?> <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>API</h3>
                <p>接口调用</p>
            </div>
            <div class="icon">
                <i class="fas fa-code"></i>
            </div>
            <a href="../api/streams.php" target="_blank" class="small-box-footer">
                查看API <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- 分类数据 -->
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-2"></i>分类统计
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>分类名称</th>
                                <th>直播源数量</th>
                                <th>百分比</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <i class="fas <?php echo $category['icon']; ?> mr-2"></i>
                                    <?php echo $category['name']; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $category_counts[$category['id']]; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $percentage = count($streams) > 0 ? round(($category_counts[$category['id']] / count($streams)) * 100, 1) : 0;
                                    ?>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <small><?php echo $percentage; ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 最近添加的直播源 -->
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>最近添加的直播源
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    <?php if (empty($recent_streams)): ?>
                    <li class="item">
                        <div class="product-info ml-3">
                            <span class="text-muted">暂无直播源数据</span>
                        </div>
                    </li>
                    <?php else: ?>
                        <?php foreach ($recent_streams as $stream): ?>
                        <li class="item">
                            <div class="product-img">
                                <i class="fas <?php echo $stream['icon'] ?: 'fa-play-circle'; ?> fa-2x text-primary"></i>
                            </div>
                            <div class="product-info">
                                <a href="<?php echo $stream['url']; ?>" target="_blank" class="product-title">
                                    <?php echo $stream['name']; ?>
                                    <?php 
                                    $category_name = '未分类';
                                    foreach ($categories as $category) {
                                        if ($category['id'] == $stream['category_id']) {
                                            $category_name = $category['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge badge-primary float-right"><?php echo $category_name; ?></span>
                                </a>
                                <span class="product-description">
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 400px;" title="<?php echo $stream['url']; ?>">
                                        <?php echo $stream['url']; ?>
                                    </small>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="streams.php" class="uppercase">查看所有直播源</a>
            </div>
        </div>
    </div>
</div>

<!-- 系统信息 -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-server mr-2"></i>系统信息
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">PHP版本</span>
                                <span class="info-box-number text-center text-muted mb-0"><?php echo $system_info['php_version']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">服务器软件</span>
                                <span class="info-box-number text-center text-muted mb-0"><?php echo $system_info['server_software']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">操作系统</span>
                                <span class="info-box-number text-center text-muted mb-0"><?php echo $system_info['os']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">最大上传大小</span>
                                <span class="info-box-number text-center text-muted mb-0"><?php echo $system_info['max_upload_size']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">最大POST大小</span>
                                <span class="info-box-number text-center text-muted mb-0"><?php echo $system_info['max_post_size']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-light">
                            <div class="info-box-content">
                                <span class="info-box-text text-center text-muted">内存限制</span>
                                <span class="info-box-number text-center text-muted mb-0"><?php echo $system_info['memory_limit']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <div class="alert alert-light">
                        <i class="fas fa-info-circle mr-2"></i>系统当前时间：<?php echo $system_info['datetime']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 快速操作 -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-2"></i>快速操作
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <a href="streams.php" class="btn btn-primary btn-block mb-3">
                            <i class="fas fa-play-circle mr-2"></i>管理直播源
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="categories.php" class="btn btn-success btn-block mb-3">
                            <i class="fas fa-folder mr-2"></i>管理分类
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="../api/streams.php" target="_blank" class="btn btn-info btn-block mb-3">
                            <i class="fas fa-code mr-2"></i>查看API
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="logout.php" class="btn btn-danger btn-block mb-3">
                            <i class="fas fa-sign-out-alt mr-2"></i>退出登录
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?> 