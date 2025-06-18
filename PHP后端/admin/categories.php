<?php
require_once 'config.php';
require_once 'includes/header.php';

// 获取分类数据
$categories = read_json_file(CATEGORIES_FILE);

// 处理添加/编辑分类
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : 'fa-folder';
    $sort = isset($_POST['sort']) ? intval($_POST['sort']) : count($categories) + 1;
    
    // 验证数据
    if (empty($name)) {
        $message = ['type' => 'error', 'text' => '分类名称不能为空'];
    } else {
        if ($id > 0) {
            // 更新分类
            foreach ($categories as $key => $category) {
                if ($category['id'] == $id) {
                    $categories[$key]['name'] = $name;
                    $categories[$key]['icon'] = $icon;
                    $categories[$key]['sort'] = $sort;
                    break;
                }
            }
            $message = ['type' => 'success', 'text' => '分类已更新'];
        } else {
            // 添加新分类
            $new_id = 1;
            if (!empty($categories)) {
                $ids = array_column($categories, 'id');
                $new_id = max($ids) + 1;
            }
            
            $categories[] = [
                'id' => $new_id,
                'name' => $name,
                'icon' => $icon,
                'sort' => $sort
            ];
            $message = ['type' => 'success', 'text' => '分类已添加'];
        }
        
        // 保存数据
        if (save_json_file(CATEGORIES_FILE, $categories)) {
            // 重定向以避免表单重复提交
            header('Location: categories.php?message=' . urlencode($message['text']) . '&type=' . $message['type']);
            exit;
        } else {
            $message = ['type' => 'error', 'text' => '保存数据失败，请检查文件权限'];
        }
    }
}

// 处理删除分类
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $found = false;
    
    foreach ($categories as $key => $category) {
        if ($category['id'] == $delete_id) {
            unset($categories[$key]);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        // 重新索引数组
        $categories = array_values($categories);
        
        // 保存数据
        if (save_json_file(CATEGORIES_FILE, $categories)) {
            $message = ['type' => 'success', 'text' => '分类已删除'];
        } else {
            $message = ['type' => 'error', 'text' => '删除失败，请检查文件权限'];
        }
    } else {
        $message = ['type' => 'error', 'text' => '未找到要删除的分类'];
    }
    
    // 重定向以避免重复删除
    header('Location: categories.php?message=' . urlencode($message['text']) . '&type=' . $message['type']);
    exit;
}

// 处理编辑分类的表单显示
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    foreach ($categories as $category) {
        if ($category['id'] == $edit_id) {
            $edit_category = $category;
            break;
        }
    }
}

// 处理排序
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

// 获取每个分类下的直播源数量
$streams = read_json_file(STREAMS_FILE);
$category_counts = [];

foreach ($categories as $category) {
    $category_counts[$category['id']] = 0;
}

foreach ($streams as $stream) {
    if (isset($category_counts[$stream['category_id']])) {
        $category_counts[$stream['category_id']]++;
    }
}
?>

<!-- 顶部统计卡片 -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo count($categories); ?></h3>
                <p>分类总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-folder"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-8 col-md-6">
        <div class="info-box bg-gradient-warning">
            <span class="info-box-icon"><i class="fas fa-info-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-white">提示</span>
                <span class="info-box-number text-white">可通过拖拽<i class="fas fa-arrows-alt ml-1 mr-1"></i>图标调整排序，拖拽完成后自动保存</span>
            </div>
        </div>
    </div>
</div>

<!-- 分类列表 -->
<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-th-list mr-2"></i>分类列表
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary"><?php echo count($categories); ?> 个分类</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 60px">ID</th>
                            <th>分类信息</th>
                            <th style="width: 100px">直播源数量</th>
                            <th style="width: 80px">排序</th>
                            <th style="width: 130px">操作</th>
                        </tr>
                    </thead>
                    <tbody class="sortable-list" data-type="category">
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>暂无分类数据，请添加新分类</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr class="sortable-item" data-id="<?php echo $category['id']; ?>">
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas <?php echo $category['icon']; ?> mr-2 fa-lg text-primary"></i>
                                        <strong><?php echo $category['name']; ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo isset($category_counts[$category['id']]) ? $category_counts[$category['id']] : 0; ?> 个
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="sort-index badge bg-secondary"><?php echo $category['sort']; ?></span>
                                        <i class="fas fa-arrows-alt sort-handle ml-2" style="cursor: move;"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i> 编辑
                                        </a>
                                        <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn btn-danger btn-sm btn-delete" data-id="<?php echo $category['id']; ?>" data-type="category">
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
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas <?php echo $edit_category ? 'fa-edit' : 'fa-plus-circle'; ?> mr-2"></i>
                    <?php echo $edit_category ? '编辑分类' : '添加新分类'; ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if (isset($message) && $message['type'] === 'error'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $message['text']; ?>
                </div>
                <?php endif; ?>
                
                <form action="categories.php" method="post" id="category-form">
                    <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group mb-3">
                        <label for="category-name" class="form-label">
                            <i class="fas fa-tag mr-1"></i> 分类名称 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="category-name" name="name" value="<?php echo $edit_category ? $edit_category['name'] : ''; ?>" required>
                        <div class="form-text">输入分类的显示名称，如：央视频道、卫视频道等</div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="icon" class="form-label">
                            <i class="fas fa-icons mr-1"></i> 图标
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas <?php echo $edit_category ? $edit_category['icon'] : 'fa-folder'; ?>" id="icon-preview"></i>
                            </span>
                            <input type="text" class="form-control" id="icon" name="icon" value="<?php echo $edit_category ? $edit_category['icon'] : 'fa-folder'; ?>" placeholder="fa-folder">
                        </div>
                        <div class="form-text">输入Font Awesome图标类名，如：fa-folder、fa-tv等</div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="sort" class="form-label">
                            <i class="fas fa-sort-numeric-down mr-1"></i> 排序
                        </label>
                        <input type="number" class="form-control" id="sort" name="sort" value="<?php echo $edit_category ? $edit_category['sort'] : count($categories) + 1; ?>" min="1">
                        <div class="form-text">数字越小排序越靠前，默认为最大值+1</div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save mr-1"></i> <?php echo $edit_category ? '更新分类' : '添加分类'; ?>
                        </button>
                        <?php if ($edit_category): ?>
                        <a href="categories.php" class="btn btn-secondary btn-lg ml-2">
                            <i class="fas fa-times mr-1"></i> 取消编辑
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card card-outline card-info mt-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-icons mr-2"></i>常用图标
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-folder fa-2x mb-2"></i>
                        <div><small>fa-folder</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-tv fa-2x mb-2"></i>
                        <div><small>fa-tv</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-film fa-2x mb-2"></i>
                        <div><small>fa-film</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-video fa-2x mb-2"></i>
                        <div><small>fa-video</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-map fa-2x mb-2"></i>
                        <div><small>fa-map</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-globe fa-2x mb-2"></i>
                        <div><small>fa-globe</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-music fa-2x mb-2"></i>
                        <div><small>fa-music</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-play fa-2x mb-2"></i>
                        <div><small>fa-play</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-broadcast-tower fa-2x mb-2"></i>
                        <div><small>fa-broadcast-tower</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-satellite-dish fa-2x mb-2"></i>
                        <div><small>fa-satellite-dish</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-camera fa-2x mb-2"></i>
                        <div><small>fa-camera</small></div>
                    </div>
                    <div class="col-3 text-center mb-3">
                        <i class="fas fa-rss fa-2x mb-2"></i>
                        <div><small>fa-rss</small></div>
                    </div>
                </div>
                <div class="text-muted small">
                    <i class="fas fa-info-circle mr-1"></i> 点击图标可快速选择
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 图标预览功能
    const iconInput = document.getElementById('icon');
    const iconPreview = document.getElementById('icon-preview');
    
    iconInput.addEventListener('input', function() {
        iconPreview.className = 'fas ' + this.value;
    });
    
    // 快速选择图标
    const iconExamples = document.querySelectorAll('.card-body .fas.fa-2x');
    iconExamples.forEach(function(icon) {
        icon.style.cursor = 'pointer';
        icon.addEventListener('click', function() {
            const iconClass = Array.from(this.classList)
                .find(cls => cls.startsWith('fa-'));
            iconInput.value = iconClass;
            iconPreview.className = 'fas ' + iconClass;
        });
    });
    
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
    
    // 删除确认
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            const href = this.getAttribute('href');
            
            Swal.fire({
                title: '确认删除',
                text: '确定要删除这个分类吗？该操作无法撤销！',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '是，删除它！',
                cancelButtonText: '取消'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
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
    <?php endif; ?>
});
</script>

<?php
require_once 'includes/footer.php';
?> 