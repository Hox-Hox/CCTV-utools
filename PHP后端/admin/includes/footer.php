            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#"><?php echo APP_NAME; ?></a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> <?php echo APP_VERSION; ?>
        </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- jQuery UI (for sortable) -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- 自定义脚本 -->
<script src="assets/js/script.js"></script>

<script>
// 通用的复制到剪贴板函数
function copyToClipboard(text) {
    // 创建一个临时文本区域
    const textArea = document.createElement('textarea');
    textArea.value = text;
    
    // 确保文本区域不可见
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    let success = false;
    try {
        // 执行复制命令
        success = document.execCommand('copy');
    } catch (err) {
        console.error('复制失败:', err);
    }
    
    // 移除临时文本区域
    document.body.removeChild(textArea);
    
    // 显示提示
    if (success) {
        Swal.fire({
            title: '已复制',
            text: '内容已成功复制到剪贴板',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
        });
    } else {
        Swal.fire({
            title: '复制失败',
            text: '请手动复制文本',
            icon: 'error',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    }
    
    return success;
}
</script>
</body>
</html>
<?php
// 输出所有缓冲内容
ob_end_flush();
?> 