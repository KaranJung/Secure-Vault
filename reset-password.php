<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';
$user_id = null;

// Validate token
if (empty($token)) {
    $errors['token'] = 'Invalid or missing reset token';
} else {
    try {
        // Check if token exists and is valid
        $stmt = $pdo->prepare("SELECT pr.user_id, pr.expires_at, u.email, u.name 
                              FROM password_resets pr 
                              JOIN users u ON pr.user_id = u.id 
                              WHERE pr.token = ? AND pr.is_valid = 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            $errors['token'] = 'Invalid or expired reset token';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $errors['token'] = 'This reset link has expired';
            
            // Invalidate expired token
            $stmt = $pdo->prepare("UPDATE password_resets SET is_valid = 0 WHERE token = ?");
            $stmt->execute([$token]);
        } else {
            $user_id = $reset['user_id'];
            $user_email = $reset['email'];
            $user_name = $reset['name'];
        }
    } catch (Exception $e) {
        $errors['general'] = 'System error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = 'New password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $errors['new_password'] = 'Password must include uppercase, lowercase, and numbers';
    }
    
    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no validation errors, update password
    if (empty($errors)) {
        try {
            // Update user password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$password_hash, $user_id]);
            
            if ($result) {
                // Invalidate the used token and record usage time
                $stmt = $pdo->prepare("UPDATE password_resets SET is_valid = 0, used_at = NOW() WHERE token = ?");
                $stmt->execute([$token]);
                
                // We'll skip the security_log entry since that table doesn't exist
                
                $success = true;
            } else {
                $errors['general'] = 'Failed to update password. Please try again.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'System error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | SECURE_VAULT</title>
    <link rel="icon" type="image/x-icon" href="vault.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        body {
            background: radial-gradient(circle at 10% 20%, rgb(21, 25, 40) 0%, rgb(11, 31, 62) 90%);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.6);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.8);
        }
        
        /* Password strength meter */
        .password-strength-meter {
            height: 4px;
            background-color: rgba(100, 116, 139, 0.5);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .password-strength-meter div {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        .strength-weak {
            background-color: rgb(239, 68, 68);
            width: 25%;
        }
        
        .strength-medium {
            background-color: rgb(234, 179, 8);
            width: 50%;
        }
        
        .strength-good {
            background-color: rgb(59, 130, 246);
            width: 75%;
        }
        
        .strength-strong {
            background-color: rgb(34, 197, 94);
            width: 100%;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation Bar -->
    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-50 py-3">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="mr-3 text-indigo-500">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <a href="index.php" class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-500 to-indigo-600">VAULT</a>
            </div>
            <div>
                <a href="login.php" class="text-slate-300 hover:text-indigo-400 transition-all duration-300">
                    <i class="fas fa-sign-in-alt mr-1"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow flex items-center justify-center">
        <div class="glass-card rounded-2xl p-8 max-w-md w-full border border-slate-700/50">
            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="text-center">
                    <div class="bg-emerald-900/50 text-emerald-400 p-4 rounded-xl mb-6 border border-emerald-700/50 flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <div>
                            <p class="font-medium">Password successfully reset!</p>
                            <p class="text-sm">You can now log in with your new password.</p>
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <div class="text-indigo-400 text-5xl mb-4">
                            <i class="fas fa-lock-open"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">Password Reset Complete</h2>
                        <p class="text-slate-400">
                            Your password has been successfully updated. You can now log in to your account using your new password.
                        </p>
                    </div>
                    
                    <div class="flex flex-col space-y-4">
                        <a href="login.php" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                            <i class="fas fa-sign-in-alt mr-2"></i> Log In Now
                        </a>
                    </div>
                </div>
            <?php elseif (!empty($errors['token'])): ?>
                <!-- Invalid Token Message -->
                <div class="text-center">
                    <div class="bg-rose-900/50 text-rose-400 p-4 rounded-xl mb-6 border border-rose-700/50 flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                        <div>
                            <p class="font-medium">Invalid Reset Link</p>
                            <p class="text-sm"><?php echo $errors['token']; ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <div class="text-rose-400 text-5xl mb-4">
                            <i class="fas fa-link-slash"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">Reset Link Problem</h2>
                        <p class="text-slate-400">
                            The password reset link you used is invalid or has expired. Please request a new password reset link.
                        </p>
                    </div>
                    
                    <div class="flex flex-col space-y-4">
                        <a href="forgot-password.php" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                            <i class="fas fa-redo mr-2"></i> Request New Reset Link
                        </a>
                        
                        <a href="login.php" class="inline-flex items-center justify-center px-6 py-3 border border-slate-600/50 text-slate-300 rounded-xl hover:bg-slate-800/70 transition-all duration-300 font-medium">
                            <i class="fas fa-sign-in-alt mr-2"></i> Return to Login
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Reset Password Form -->
                <div>
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 mb-4">
                            <i class="fas fa-key text-white text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white">Create New Password</h2>
                        <p class="text-slate-400 mt-2">
                            Hi <?php echo isset($user_name) ? htmlspecialchars($user_name) : 'there'; ?>, please create a new secure password for your account.
                        </p>
                    </div>
                    
                    <?php if (!empty($errors['general'])): ?>
                        <div class="bg-rose-900/50 text-rose-400 p-4 rounded-xl mb-6 border border-rose-700/50 flex items-center">
                            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                            <div>
                                <p class="font-medium">Error</p>
                                <p class="text-sm"><?php echo $errors['general']; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6" x-data="{ passwordStrength: 0, passwordMessage: '' }">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-indigo-300 mb-2">New Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-indigo-400"></i>
                                </div>
                                <input type="password" id="new_password" name="new_password" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                       placeholder="Enter your new password"
                                       @input="checkPasswordStrength($event.target.value)" autofocus>
                            </div>
                            
                            <!-- Password strength meter -->
                            <div class="password-strength-meter mt-2">
                                <div :class="{
                                    'strength-weak': passwordStrength === 1,
                                    'strength-medium': passwordStrength === 2,
                                    'strength-good': passwordStrength === 3,
                                    'strength-strong': passwordStrength === 4
                                }"></div>
                            </div>
                            <p class="text-sm mt-1" :class="{
                                'text-rose-400': passwordStrength === 1,
                                'text-amber-400': passwordStrength === 2,
                                'text-blue-400': passwordStrength === 3,
                                'text-emerald-400': passwordStrength === 4
                            }" x-text="passwordMessage"></p>
                            
                            <?php if (!empty($errors['new_password'])): ?>
                                <p class="text-rose-400 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <?php echo $errors['new_password']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-indigo-300 mb-2">Confirm New Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-check-circle text-indigo-400"></i>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                       placeholder="Confirm your new password">
                            </div>
                            <?php if (!empty($errors['confirm_password'])): ?>
                                <p class="text-rose-400 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <?php echo $errors['confirm_password']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bg-slate-800/30 p-4 rounded-xl border border-slate-700/50 text-sm text-slate-400">
                            <h3 class="font-medium text-indigo-400 mb-2">Password Requirements:</h3>
                            <ul class="list-disc list-inside space-y-1">
                                <li>At least 8 characters long</li>
                                <li>Include at least one uppercase letter</li>
                                <li>Include at least one lowercase letter</li>
                                <li>Include at least one number</li>
                                <li>Avoid using easily guessable information</li>
                            </ul>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                                <i class="fas fa-save mr-2"></i> Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-10 mb-6 text-center text-slate-500 text-sm">
        <p>SECURE_VAULT • <?php echo date('Y'); ?> • Quantum-Secured API Management</p>
    </div>

    <script>
    // Password strength checker
    function checkPasswordStrength(password) {
        // Get Alpine component
        const component = Alpine.evaluate(document.querySelector('[x-data]'), 'passwordStrength');
        
        if (!password) {
            component.passwordStrength = 0;
            component.passwordMessage = '';
            return;
        }
        
        // Calculate strength
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        
        // Character type checks
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        // Set strength level (1-4)
        let strengthLevel = 1;
        if (strength >= 5) strengthLevel = 4;
        else if (strength >= 4) strengthLevel = 3;
        else if (strength >= 3) strengthLevel = 2;
        else strengthLevel = 1;
        
        // Update Alpine component
        component.passwordStrength = strengthLevel;
        
        // Set message based on strength
        switch (strengthLevel) {
            case 1:
                component.passwordMessage = 'Weak password';
                break;
            case 2:
                component.passwordMessage = 'Medium strength password';
                break;
            case 3:
                component.passwordMessage = 'Good password';
                break;
            case 4:
                component.passwordMessage = 'Strong password';
                break;
        }
    }
    </script>
</body>
</html>

