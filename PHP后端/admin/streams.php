<?php
require_once 'config.php';
require_once 'includes/header.php';

// 获取分类和直播源数据
$categories = read_json_file(CATEGORIES_FILE);
$streams = read_json_file(STREAMS_FILE);

// 处理添加/编辑直播源
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : 'fa-circle-play';
    $sort = isset($_POST['sort']) ? intval($_POST['sort']) : count($streams) + 1;
    
    // 验证数据
    if (empty($name) || empty($url) || empty($category_id)) {
        $message = ['type' => 'error', 'text' => '请完整填写表单信息'];
    } else {
        if ($id > 0) {
            // 更新直播源
            foreach ($streams as $key => $stream) {
                if ($stream['id'] == $id) {
                    $streams[$key]['category_id'] = $category_id;
                    $streams[$key]['name'] = $name;
                    $streams[$key]['url'] = $url;
                    $streams[$key]['icon'] = $icon;
                    $streams[$key]['sort'] = $sort;
                    break;
                }
            }
            $message = ['type' => 'success', 'text' => '直播源已更新'];
        } else {
            // 添加新直播源
            $new_id = 1;
            if (!empty($streams)) {
                $ids = array_column($streams, 'id');
                $new_id = max($ids) + 1;
            }
            
            $streams[] = [
                'id' => $new_id,
                'category_id' => $category_id,
                'name' => $name,
                'url' => $url,
                'icon' => $icon,
                'sort' => $sort
            ];
            $message = ['type' => 'success', 'text' => '直播源已添加'];
        }
        
        // 保存数据
        if (save_json_file(STREAMS_FILE, $streams)) {
            // 重定向以避免表单重复提交
            header('Location: streams.php?message=' . urlencode($message['text']) . '&type=' . $message['type']);
            exit;
        } else {
            $message = ['type' => 'error', 'text' => '保存数据失败，请检查文件权限'];
        }
    }
}

// 处理删除直播源
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $found = false;
    
    foreach ($streams as $key => $stream) {
        if ($stream['id'] == $delete_id) {
            unset($streams[$key]);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        // 重新索引数组
        $streams = array_values($streams);
        
        // 保存数据
        if (save_json_file(STREAMS_FILE, $streams)) {
            $message = ['type' => 'success', 'text' => '直播源已删除'];
        } else {
            $message = ['type' => 'error', 'text' => '删除失败，请检查文件权限'];
        }
    } else {
        $message = ['type' => 'error', 'text' => '未找到要删除的直播源'];
    }
    
    // 重定向以避免重复删除
    header('Location: streams.php?message=' . urlencode($message['text']) . '&type=' . $message['type']);
    exit;
}

// 处理编辑直播源的表单显示
$edit_stream = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    foreach ($streams as $stream) {
        if ($stream['id'] == $edit_id) {
            $edit_stream = $stream;
            break;
        }
    }
}

// 处理按分类筛选
$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
if ($filter_category > 0) {
    $filtered_streams = [];
    foreach ($streams as $stream) {
        if ($stream['category_id'] == $filter_category) {
            $filtered_streams[] = $stream;
        }
    }
    $streams = $filtered_streams;
}

// 处理排序
usort($streams, function($a, $b) {
    // 先按分类排序，再按自定义排序
    if ($a['category_id'] == $b['category_id']) {
        return $a['sort'] - $b['sort'];
    }
    return $a['category_id'] - $b['category_id'];
});

// 排序分类数据
usort($categories, function($a, $b) {
    return $a['sort'] - $b['sort'];
});

// 显示消息
if (isset($_GET['message'])) {
    $message = [
        'text' => $_GET['message'],
        'type' => isset($_GET['type']) && $_GET['type'] === 'error' ? 'error' : 'success'
    ];
}
?>

<!-- 顶部统计卡片 -->
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo count($streams); ?></h3>
                <p>当前直播源</p>
            </div>
            <div class="icon">
                <i class="fas fa-broadcast-tower"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo count($categories); ?></h3>
                <p>分类总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-th-list"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="info-box bg-gradient-warning">
            <span class="info-box-icon"><i class="fas fa-info-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-white">提示</span>
                <span class="info-box-number text-white">可通过拖拽<i class="fas fa-arrows-alt ml-1 mr-1"></i>图标调整排序，拖拽完成后自动保存</span>
            </div>
        </div>
    </div>
</div>

<!-- 筛选和添加按钮 -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-2">
                <form action="streams.php" method="get" class="form-inline">
                    <div class="input-group">
                        <select name="category" class="form-control">
                            <option value="0">全部分类</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $filter_category == $category['id'] ? 'selected' : ''; ?>>
                                <i class="fas <?php echo $category['icon']; ?>"></i> <?php echo $category['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-filter"></i> 筛选
                            </button>
                            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> 导出
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="export.php?format=m3u8<?php echo $filter_category > 0 ? '&category=' . $filter_category : ''; ?>">
                                    <i class="fas fa-file-export mr-2"></i> 导出为m3u8
                                </a>
                                <a class="dropdown-item" href="export.php?format=txt<?php echo $filter_category > 0 ? '&category=' . $filter_category : ''; ?>">
                                    <i class="fas fa-file-alt mr-2"></i> 导出为TXT
                                </a>
                                <a class="dropdown-item" href="export.php?format=json<?php echo $filter_category > 0 ? '&category=' . $filter_category : ''; ?>">
                                    <i class="fas fa-file-code mr-2"></i> 导出为JSON
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 text-right">
        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#streamModal">
            <i class="fas fa-plus"></i> 添加直播源
        </button>
    </div>
</div>

<!-- 直播源列表 -->
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list mr-2"></i>直播源列表
        </h3>
        <div class="card-tools">
            <span class="badge badge-primary"><?php echo count($streams); ?> 个直播源</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th style="width: 60px">ID</th>
                    <th style="width: 120px">分类</th>
                    <th>频道名称</th>
                    <th>直播源地址</th>
                    <th style="width: 80px">排序</th>
                    <th style="width: 130px">操作</th>
                </tr>
            </thead>
            <tbody class="sortable-list" data-type="stream">
                <?php if (empty($streams)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>暂无直播源数据，请添加新直播源</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($streams as $stream): ?>
                    <tr class="sortable-item" data-id="<?php echo $stream['id']; ?>">
                        <td><?php echo $stream['id']; ?></td>
                        <td>
                            <?php 
                            $category_name = '未分类';
                            $category_icon = 'fa-folder';
                            foreach ($categories as $category) {
                                if ($category['id'] == $stream['category_id']) {
                                    $category_name = $category['name'];
                                    $category_icon = $category['icon'];
                                    break;
                                }
                            }
                            ?>
                            <span class="badge bg-primary">
                                <i class="fas <?php echo $category_icon; ?> mr-1"></i>
                                <?php echo $category_name; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($stream['icon'])): ?>
                            <i class="fas <?php echo $stream['icon']; ?> mr-2"></i>
                            <?php endif; ?>
                            <?php echo $stream['name']; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="text-muted text-truncate d-inline-block" style="max-width: 300px;" title="<?php echo $stream['url']; ?>">
                                    <?php echo $stream['url']; ?>
                                </span>
                                <button type="button" class="btn btn-xs btn-default copy-url ml-2" data-url="<?php echo $stream['url']; ?>" data-bs-toggle="tooltip" title="复制链接">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <a href="<?php echo $stream['url']; ?>" target="_blank" class="btn btn-xs btn-default ml-1" data-bs-toggle="tooltip" title="测试播放">
                                    <i class="fas fa-play"></i>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="sort-index badge bg-secondary"><?php echo $stream['sort']; ?></span>
                                <i class="fas fa-arrows-alt sort-handle ml-2" style="cursor: move;"></i>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info btn-sm btn-edit" data-id="<?php echo $stream['id']; ?>">
                                    <i class="fas fa-edit"></i> 编辑
                                </button>
                                <a href="streams.php?delete=<?php echo $stream['id']; ?>" class="btn btn-danger btn-sm btn-delete" data-id="<?php echo $stream['id']; ?>" data-type="stream">
                                    <i class="fas fa-trash"></i> 删除
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="text-muted small">
            提示: 点击"复制链接"可复制直播源地址，点击"测试播放"可在新窗口中测试直播源是否有效
        </div>
    </div>
</div>

<!-- 添加/编辑直播源模态框 -->
<div class="modal fade" id="streamModal" tabindex="-1" aria-labelledby="streamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="streamModalLabel">
                    <i class="fas fa-plus-circle mr-2"></i>
                    <span id="modal-title-text">添加直播源</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="streams.php" method="post" id="stream-form">
                <div class="modal-body">
                    <input type="hidden" name="id" id="stream-id" value="">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="category-id" class="form-label">
                                    <i class="fas fa-folder mr-1"></i> 选择分类 <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="category-id" name="category_id" required>
                                    <option value="">-- 选择分类 --</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <i class="fas <?php echo $category['icon']; ?>"></i> <?php echo $category['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">选择直播源所属的分类</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="stream-name" class="form-label">
                                    <i class="fas fa-tag mr-1"></i> 频道名称 <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="stream-name" name="name" required>
                                <div class="form-text">输入直播频道名称，如：CCTV-1</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="stream-url" class="form-label">
                            <i class="fas fa-link mr-1"></i> 直播源地址 <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="stream-url" name="url" placeholder="http://example.com/stream.m3u8" required>
                            <button class="btn btn-outline-secondary" type="button" id="test-stream-btn">
                                <i class="fas fa-play"></i> 测试
                            </button>
                        </div>
                        <div class="form-text">输入直播源的URL地址，支持m3u8、flv、mp4等格式</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="stream-icon" class="form-label">
                                    <i class="fas fa-icons mr-1"></i> 图标
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-circle-play" id="stream-icon-preview"></i>
                                    </span>
                                    <input type="text" class="form-control" id="stream-icon" name="icon" value="fa-circle-play" placeholder="fa-circle-play">
                                </div>
                                <div class="form-text">输入Font Awesome图标类名，默认为fa-circle-play</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="stream-sort" class="form-label">
                                    <i class="fas fa-sort-numeric-down mr-1"></i> 排序
                                </label>
                                <input type="number" class="form-control" id="stream-sort" name="sort" min="1" value="1">
                                <div class="form-text">数字越小排序越靠前，默认为最大值+1</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        所有带 <span class="text-danger">*</span> 的字段为必填项
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> 取消
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <span id="submit-btn-text">保存</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 图标预览功能
    const iconInput = document.getElementById('stream-icon');
    const iconPreview = document.getElementById('stream-icon-preview');
    
    if (iconInput) {
        iconInput.addEventListener('input', function() {
            iconPreview.className = 'fas ' + this.value;
        });
    }
    
    // 复制URL功能
    document.querySelectorAll('.copy-url').forEach(function(button) {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            copyToClipboard(url);
        });
    });
    
    // 测试直播源功能
    document.getElementById('test-stream-btn').addEventListener('click', function() {
        const url = document.getElementById('stream-url').value.trim();
        if (url) {
            window.open(url, '_blank');
        } else {
            Swal.fire({
                title: '错误',
                text: '请先输入直播源地址',
                icon: 'error'
            });
        }
    });
    
    // 编辑按钮功能
    document.querySelectorAll('.btn-edit').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            // 通过AJAX获取数据
            fetch('get_stream.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stream = data.data;
                        
                        // 填充表单
                        document.getElementById('stream-id').value = stream.id;
                        document.getElementById('category-id').value = stream.category_id;
                        document.getElementById('stream-name').value = stream.name;
                        document.getElementById('stream-url').value = stream.url;
                        document.getElementById('stream-icon').value = stream.icon;
                        document.getElementById('stream-sort').value = stream.sort;
                        
                        // 更新图标预览
                        document.getElementById('stream-icon-preview').className = 'fas ' + stream.icon;
                        
                        // 更新模态框标题和按钮文本
                        document.getElementById('modal-title-text').textContent = '编辑直播源';
                        document.getElementById('submit-btn-text').textContent = '更新';
                        
                        // 显示模态框
                        const modal = new bootstrap.Modal(document.getElementById('streamModal'));
                        modal.show();
                    } else {
                        Swal.fire({
                            title: '错误',
                            text: data.message || '获取直播源数据失败',
                            icon: 'error'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // 如果直接从页面获取数据
                    const row = this.closest('tr');
                    if (row) {
                        const id = row.getAttribute('data-id');
                        const categoryId = getCategoryIdByName(row.cells[1].textContent.trim());
                        const name = row.cells[2].textContent.trim();
                        const url = row.cells[3].querySelector('.text-truncate').textContent.trim();
                        const sort = row.cells[4].querySelector('.sort-index').textContent.trim();
                        let icon = 'fa-circle-play';
                        
                        const iconElement = row.cells[2].querySelector('.fas');
                        if (iconElement) {
                            icon = Array.from(iconElement.classList)
                                .find(cls => cls.startsWith('fa-') && cls !== 'fas');
                        }
                        
                        // 填充表单
                        document.getElementById('stream-id').value = id;
                        document.getElementById('category-id').value = categoryId;
                        document.getElementById('stream-name').value = name;
                        document.getElementById('stream-url').value = url;
                        document.getElementById('stream-icon').value = icon;
                        document.getElementById('stream-sort').value = sort;
                        
                        // 更新图标预览
                        document.getElementById('stream-icon-preview').className = 'fas ' + icon;
                        
                        // 更新模态框标题和按钮文本
                        document.getElementById('modal-title-text').textContent = '编辑直播源';
                        document.getElementById('submit-btn-text').textContent = '更新';
                        
                        // 显示模态框
                        const modal = new bootstrap.Modal(document.getElementById('streamModal'));
                        modal.show();
                    }
                });
        });
    });
    
    // 辅助函数：根据分类名称获取分类ID
    function getCategoryIdByName(categoryName) {
        const categories = <?php echo json_encode($categories); ?>;
        for (const category of categories) {
            if (categoryName.includes(category.name)) {
                return category.id;
            }
        }
        return 1; // 默认返回第一个分类
    }
    
    // 初始化工具提示
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 初始化排序功能
    if (typeof $.fn.sortable !== 'undefined') {
        $('.sortable-list').sortable({
            handle: '.sort-handle',
            update: function(event, ui) {
                const items = $(this).find('.sortable-item');
                const type = $(this).data('type');
                const idList = [];
                
                items.each(function(index) {
                    $(this).find('.sort-index').text(index + 1);
                    idList.push($(this).data('id'));
                });
                
                // 发送排序请求
                $.ajax({
                    url: 'sort.php',
                    type: 'POST',
                    data: {
                        type: type,
                        items: idList
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: '成功',
                                    text: '排序已更新！',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: '错误',
                                    text: data.message || '排序更新失败！',
                                    icon: 'error'
                                });
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: '错误',
                            text: '服务器错误，排序更新失败！',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }
    
    // 添加直播源按钮清空表单
    document.querySelector('[data-bs-target="#streamModal"]').addEventListener('click', function() {
        document.getElementById('stream-form').reset();
        document.getElementById('stream-id').value = '';
        document.getElementById('stream-icon-preview').className = 'fas fa-circle-play';
        document.getElementById('modal-title-text').textContent = '添加直播源';
        document.getElementById('submit-btn-text').textContent = '保存';
    });
    
    // 如果有成功消息，显示提示
    <?php if (isset($message) && $message['type'] === 'success'): ?>
    Swal.fire({
        title: '成功',
        text: '<?php echo $message['text']; ?>',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
    <?php elseif (isset($message) && $message['type'] === 'error'): ?>
    Swal.fire({
        title: '错误',
        text: '<?php echo $message['text']; ?>',
        icon: 'error'
    });
    <?php endif; ?>
});
</script>

<?php
require_once 'includes/footer.php';
?> 