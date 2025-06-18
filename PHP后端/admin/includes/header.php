<?php 
// 确保没有输出内容
ob_start();
require_once dirname(__DIR__) . '/config.php'; 
require_login();

// 设置页面标题
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'index.php' => '控制面板',
    'dashboard.php' => '控制面板',
    'categories.php' => '分类管理',
    'streams.php' => '直播源管理',
    'api.php' => 'API接口文档',
    'export.php' => '导出数据'
];

$page_title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : '管理后台';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . APP_NAME; ?> 管理系统</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AdminLTE CSS -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- jQuery UI CSS (for sortable) -->
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
    /* 自定义样式 */
    .nav-sidebar .nav-link p {
        margin-left: 5px;
    }
    .brand-link .brand-image {
        margin-top: 3px;
    }
    .content-wrapper {
        background-color: #f4f6f9;
    }
    .card {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        margin-bottom: 1rem;
    }
    .card-outline {
        border-top: 3px solid #007bff;
    }
    .card-primary.card-outline {
        border-top-color: #007bff;
    }
    .card-success.card-outline {
        border-top-color: #28a745;
    }
    .card-warning.card-outline {
        border-top-color: #ffc107;
    }
    .card-info.card-outline {
        border-top-color: #17a2b8;
    }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link">首页</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../api/streams.php" target="_blank" class="nav-link">API</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="../" target="_blank" role="button" data-bs-toggle="tooltip" title="访问前台">
                    <i class="fas fa-home"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php" role="button" data-bs-toggle="tooltip" title="退出登录">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button" data-bs-toggle="tooltip" title="全屏显示">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="dashboard.php" class="brand-link">
            <i class="fas fa-tv brand-image elevation-3 ml-3" style="opacity: .8"></i>
            <span class="brand-text font-weight-light"><?php echo APP_NAME; ?></span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <i class="fas fa-user-circle fa-2x text-light"></i>
                </div>
                <div class="info">
                    <a href="#" class="d-block">管理员</a>
                </div>
            </div>
            
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo in_array($current_page, ['dashboard.php', 'index.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>控制面板</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-th-list"></i>
                            <p>分类管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="streams.php" class="nav-link <?php echo $current_page == 'streams.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-broadcast-tower"></i>
                            <p>直播源管理</p>
                        </a>
                    </li>
                    <li class="nav-header">数据工具</li>
                    <li class="nav-item">
                        <a href="export.php" class="nav-link <?php echo $current_page == 'export.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-download"></i>
                            <p>导出数据</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../api/streams.php" target="_blank" class="nav-link">
                            <i class="nav-icon fas fa-code"></i>
                            <p>API接口 <i class="fas fa-external-link-alt fa-xs ml-1"></i></p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">首页</a></li>
                            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid"> 