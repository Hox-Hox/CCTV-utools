<?php
require_once 'config.php';

$error = '';

// 已登录则重定向到管理首页
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // 简单验证用户名和密码
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } elseif ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // 登录成功
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_last_activity'] = time();
        
        // 重定向到管理首页
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?php echo APP_NAME; ?></title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AdminLTE CSS -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
            margin: 0 auto;
        }
        .login-logo {
            margin-bottom: 25px;
            text-align: center;
            font-size: 2.1rem;
            font-weight: 300;
        }
        .card {
            border: none;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            border-radius: 0.5rem;
        }
        .card-body {
            padding: 35px 30px 40px;
        }
        .input-group-text {
            background-color: transparent;
        }
        .btn-primary {
            background-color: #3c8dbc;
            border-color: #3c8dbc;
        }
        .btn-primary:hover {
            background-color: #367fa9;
            border-color: #367fa9;
        }
        .error-message {
            color: #dc3545;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b><?php echo APP_NAME; ?></b></a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">请登录以开始您的会话</p>
                
                <?php if (!empty($error)): ?>
                <div class="error-message mb-3">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form action="login.php" method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="用户名" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-4">
                        <input type="password" class="form-control" placeholder="密码" name="password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">登录</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html> 