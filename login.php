<?php
// Set page character encoding
header('Content-Type: text/html; charset=utf-8');

// Start session
session_start();

// Prevent session fixation attacks
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection and login handling
include 'db.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $login_error = "Invalid security token";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $login_error = "Please enter email and password";
        } else {
            $stmt = $conn->prepare("SELECT id, name, password, is_active FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $name, $hashed_password, $is_active);
                $stmt->fetch();

                if (!$is_active) {
                    $login_error = "Your account has been disabled";
                } elseif (password_verify($password, $hashed_password)) {
                    // Regenerate session ID after successful login to prevent session hijacking
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['last_login'] = time();
                    
                    header("Location: menu.php");
                    exit;
                } else {
                    $login_error = "Invalid email or password";
                }
            } else {
                $login_error = "Invalid email or password";
            }

            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <style>
        :root {
            --primary-color: #6c757d;
            --primary-light: #f8f9fa;
            --text-color: #495057;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Helvetica Neue', sans-serif;
        }
        
        body {
            background-color: #f1f3f5;
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            transition: all 0.3s ease;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: border-color 0.3s;
            background-color: var(--light-gray);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.1);
        }
        
        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #5a6268;
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
            text-align: center;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Please enter your credentials to access your account</p>
        </div>
        
        <?php if (!empty($login_error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
            <p><a href="forgot-password.php">Forgot password?</a></p>
        </div>
    </div>
</body>
</html>