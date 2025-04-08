<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // If no validation errors, process the request
    if (empty($errors)) {
        // Check if email exists in the database
        $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store the token in the database
            try {
                // First, invalidate any existing tokens for this user
                $stmt = $pdo->prepare("UPDATE password_resets SET is_valid = 0 WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Then create a new token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at, is_valid, created_at) 
                                      VALUES (?, ?, ?, 1, NOW())");
                $stmt->execute([$user['id'], $token, $expires]);
                
                // Log the password reset request - Modified to avoid security_log dependency
                // Instead of using security_log, we'll just log to the password_resets table
                
                // Create reset link
                $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
                
                // In a real application, you would send an email with the reset link
                // For demonstration purposes, we'll just show the success message
                
                $success = true;
                
                // For development/testing, you can display the token
                if (getenv('APP_ENV') === 'development') {
                    $debug_link = $resetLink;
                }
            } catch (Exception $e) {
                $errors['general'] = 'System error: ' . $e->getMessage();
            }
        } else {
            // We don't want to reveal if an email exists or not for security reasons
            // So we show the same success message even if the email doesn't exist
            $success = true;
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
                            <p class="font-medium">Password reset link sent!</p>
                            <p class="text-sm">Check your email for instructions.</p>
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <div class="text-indigo-400 text-5xl mb-4">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">Check Your Inbox</h2>
                        <p class="text-slate-400">
                            We've sent a password reset link to your email address. Please check your inbox and follow the instructions to reset your password.
                        </p>
                    </div>
                    
                    <div class="bg-slate-800/30 p-4 rounded-xl border border-slate-700/50 text-sm text-slate-400 mb-6">
                        <p><i class="fas fa-info-circle text-indigo-400 mr-2"></i> The reset link will expire in 1 hour for security reasons.</p>
                    </div>
                    
                    <?php if (isset($debug_link)): ?>
                        <div class="mt-6 p-4 bg-slate-800/50 rounded-lg text-left w-full mb-6">
                            <p class="text-amber-400 text-sm font-mono mb-2">Development Mode: Reset Link</p>
                            <a href="<?php echo $debug_link; ?>" class="text-indigo-400 text-sm break-all hover:underline"><?php echo $debug_link; ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex flex-col space-y-4">
                        <a href="login.php" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                            <i class="fas fa-sign-in-alt mr-2"></i> Return to Login
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Request Form -->
                <div>
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 mb-4">
                            <i class="fas fa-key text-white text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white">Reset Your Password</h2>
                        <p class="text-slate-400 mt-2">Enter your email address and we'll send you a link to reset your password.</p>
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
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-indigo-300 mb-2">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-indigo-400"></i>
                                </div>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                       placeholder="Enter your email address" autofocus>
                            </div>
                            <?php if (!empty($errors['email'])): ?>
                                <p class="text-rose-400 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <?php echo $errors['email']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                                <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="login.php" class="text-indigo-400 hover:text-indigo-300 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-10 mb-6 text-center text-slate-500 text-sm">
        <p>SECURE_VAULT • <?php echo date('Y'); ?> • Quantum-Secured API Management</p>
    </div>
</body>
</html>

