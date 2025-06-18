/**
 * CCTV直播源管理系统 - 自定义脚本
 */

// 页面加载完成
$(document).ready(function() {
    // 更新页面标题
    updatePageTitle();
    
    // 初始化提示工具
    initTooltips();
    
    // 删除确认
    initDeleteConfirmation();
    
    // 表单验证
    initFormValidation();
    
    // 初始化分类选择器
    initCategorySelector();
    
    // 源列表排序
    initSortable();
});

// 更新页面标题
function updatePageTitle() {
    let pageTitle = '仪表盘';
    
    // 根据当前页面设置标题
    const currentPage = window.location.pathname.split('/').pop();
    
    switch(currentPage) {
        case 'index.php':
            pageTitle = '仪表盘';
            break;
        case 'categories.php':
            pageTitle = '分类管理';
            break;
        case 'streams.php':
            pageTitle = '直播源管理';
            break;
        case 'api.php':
            pageTitle = 'API接口';
            break;
        default:
            pageTitle = '仪表盘';
    }
    
    $('#page-title').text(pageTitle);
}

// 初始化提示工具
function initTooltips() {
    $('[data-toggle="tooltip"]').tooltip();
}

// 删除确认
function initDeleteConfirmation() {
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        
        const id = $(this).data('id');
        const type = $(this).data('type');
        const url = $(this).attr('href');
        
        let title = '确认删除';
        let text = '您确定要删除这条记录吗？此操作无法撤销！';
        
        if (type === 'category') {
            text = '您确定要删除此分类吗？所有属于该分类的直播源将丢失分类关联！';
        } else if (type === 'stream') {
            text = '您确定要删除此直播源吗？此操作无法撤销！';
        }
        
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '是，删除它！',
            cancelButtonText: '取消'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
}

// 表单验证
function initFormValidation() {
    // 分类表单验证
    $('#category-form').on('submit', function(e) {
        const name = $('#category-name').val().trim();
        if (name === '') {
            e.preventDefault();
            Swal.fire({
                title: '错误',
                text: '分类名称不能为空！',
                icon: 'error'
            });
            return false;
        }
        return true;
    });
    
    // 直播源表单验证
    $('#stream-form').on('submit', function(e) {
        const name = $('#stream-name').val().trim();
        const url = $('#stream-url').val().trim();
        const categoryId = $('#category-id').val();
        
        if (name === '') {
            e.preventDefault();
            Swal.fire({
                title: '错误',
                text: '直播源名称不能为空！',
                icon: 'error'
            });
            return false;
        }
        
        if (url === '') {
            e.preventDefault();
            Swal.fire({
                title: '错误',
                text: '播放地址不能为空！',
                icon: 'error'
            });
            return false;
        }
        
        if (categoryId === '') {
            e.preventDefault();
            Swal.fire({
                title: '错误',
                text: '请选择一个分类！',
                icon: 'error'
            });
            return false;
        }
        
        return true;
    });
}

// 初始化分类选择器
function initCategorySelector() {
    // 如果存在分类选择器，加载分类数据
    if ($('#category-id').length) {
        $.getJSON('../data/categories.json', function(data) {
            const select = $('#category-id');
            select.empty();
            select.append('<option value="">-- 选择分类 --</option>');
            
            if (Array.isArray(data) && data.length > 0) {
                // 按排序升序排序
                data.sort((a, b) => a.sort - b.sort);
                
                $.each(data, function(index, category) {
                    select.append('<option value="' + category.id + '">' + category.name + '</option>');
                });
            } else {
                select.append('<option value="" disabled>没有可用的分类，请先添加分类</option>');
            }
        });
    }
}

// 源列表排序
function initSortable() {
    if ($('.sortable-list').length) {
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
                                text: '排序更新失败！',
                                icon: 'error'
                            });
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
}

// 显示操作消息
function showAlert(message, type = 'success') {
    Swal.fire({
        title: type === 'success' ? '成功' : '错误',
        text: message,
        icon: type,
        timer: 2000,
        showConfirmButton: false
    });
}

// 复制文本到剪贴板
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    Swal.fire({
        title: '已复制',
        text: '内容已复制到剪贴板！',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
} 