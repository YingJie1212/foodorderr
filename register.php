<?php
// db.php should contain database connection code
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $password_hash);

            if ($stmt->execute()) {
                $success = 'Registration successful! <a href="login.php">Login here</a>';
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Account</title>
    <style>
        :root {
            --primary: #6B7280;
            --primary-light: #9CA3AF;
            --primary-dark: #4B5563;
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --rounded-sm: 0.125rem;
            --rounded: 0.25rem;
            --rounded-md: 0.375rem;
            --rounded-lg: 0.5rem;
            --rounded-xl: 0.75rem;
            --rounded-full: 9999px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 28rem;
            width: 100%;
            margin: auto;
            padding: 1.5rem;
        }

        .card {
            background-color: white;
            border-radius: var(--rounded-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            text-align: center;
        }

        .card-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .card-header p {
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--rounded);
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.625rem 0.875rem;
            background-color: white;
            border: 1px solid var(--gray-300);
            border-radius: var(--rounded);
            color: var(--gray-800);
            font-size: 0.875rem;
            line-height: 1.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.625rem 1rem;
            background-color: var(--primary);
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25rem;
            border: none;
            border-radius: var(--rounded);
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.3);
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-gray-600 {
            color: var(--gray-600);
        }

        .text-primary {
            color: var(--primary);
        }

        .link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .link:hover {
            text-decoration: underline;
        }

        .password-strength {
            height: 0.25rem;
            background-color: var(--gray-200);
            border-radius: var(--rounded-full);
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid var(--gray-200);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Create your account</h1>
                <p>Get started with our platform</p>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name ?? ''); ?>" required autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="8">
                            <div class="password-strength">
                                <div class="strength-meter" id="strength-meter"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn">Register</button>
                    </form>
                    
                    <div class="text-center mt-4 text-sm text-gray-600">
                        Already have an account? <a href="login.php" class="link">Sign in</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.getElementById('strength-meter');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Character type checks
            if (/[0-9]/.test(password)) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Update strength meter
            let width = 0;
            let color = '#EF4444'; // Red
            
            if (strength <= 2) {
                width = 33;
                color = '#EF4444'; // Red
            } else if (strength <= 4) {
                width = 66;
                color = '#F59E0B'; // Amber
            } else {
                width = 100;
                color = '#10B981'; // Green
            }
            
            strengthMeter.style.width = width + '%';
            strengthMeter.style.backgroundColor = color;
        });
    </script>
</body>
</html>