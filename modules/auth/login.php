<?php
/**
 * Admin Login Page
 * Teacher Timetable Management System
 */

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /ttc/modules/dashboard/index.php');
    exit;
}

$error = '';
$dbError = '';
$dbErrorDetails = '';

// Test database connection
try {
    $db = getDB();
    // Check if admins table exists
    $test = $db->query("SELECT 1 FROM admins LIMIT 1");
} catch (PDOException $e) {
    $dbError = 'Database connection failed.';
    $dbErrorDetails = $e->getMessage();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($dbError)) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Check if user exists
            $admin = dbFetch(
                "SELECT * FROM admins WHERE username = ? AND status = 'active'",
                [$username]
            );
            
            if (!$admin) {
                $error = 'Invalid username.';
            } elseif (!password_verify($password, $admin['password'])) {
                $error = 'Invalid password.';
            } else {
                // Login successful - set session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['is_super_admin'] = $admin['is_super_admin'];
                $_SESSION['role_id'] = $admin['role_id'];
                $_SESSION['institution_id'] = $admin['institution_id'];
                
                // Update last login
                dbQuery("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
                
                // Remember me functionality
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
                }
                
                // Redirect to dashboard
                header('Location: /ttc/modules/dashboard/index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}

// Handle logout message
if (isset($_GET['logout'])) {
    $alert = ['message' => 'You have been successfully logged out.', 'type' => 'success'];
}

// Handle account inactive error
if (isset($_GET['error']) && $_GET['error'] === 'account_inactive') {
    $error = 'Your account has been deactivated. Please contact the administrator.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Teacher Timetable Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            margin-bottom: 15px;
        }
        
        .logo h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            cursor: pointer;
        }
        
        .remember-me input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: #764ba2;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .system-info {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">&#128197;</div>
            <h1>TTMS</h1>
            <p>Teacher Timetable Management System</p>
        </div>
        
        <?php if ($dbError): ?>
            <div class="alert alert-error">
                <strong>Database Error:</strong> <?php echo $dbError; ?><br>
                <small>Please ensure MySQL is running on port 3307 and database 'ttc_system' exists.</small><br>
                <small style="color: #999;">Error: <?php echo htmlspecialchars($dbErrorDetails); ?></small>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($alert)): ?>
            <div class="alert alert-<?php echo $alert['type']; ?>"><?php echo $alert['message']; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus <?php echo $dbError ? 'disabled' : ''; ?>>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required <?php echo $dbError ? 'disabled' : ''; ?>>
            </div>
            
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" value="1" <?php echo $dbError ? 'disabled' : ''; ?>>
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot-password" onclick="alert('Please contact the administrator to reset your password.'); return false;">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn-login" <?php echo $dbError ? 'disabled' : ''; ?>>Sign In</button>
        </form>
        
        <div class="system-info">
            <p>Default Login: admin / admin123</p>
            <p style="margin-top: 5px;">&copy; <?php echo date('Y'); ?> Teacher Timetable Management System</p>
        </div>
    </div>
</body>
</html>
