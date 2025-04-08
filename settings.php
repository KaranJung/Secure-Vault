<?php
require_once 'config.php';
requireLogin();

$user_id = getUserId();
$errors = [];
$success = false;

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php'); // Redirect if user not found
}

// Get user stats
$credential_count = 0;
$last_login = null;
$account_age = null;

try {
    // Count credentials
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_credentials WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $credential_count = $stmt->fetchColumn();
    
    // Get last login time if available
    $stmt = $pdo->prepare("SELECT login_time FROM user_logins WHERE user_id = ? ORDER BY login_time DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $last_login = $stmt->fetchColumn();
    
    // Calculate account age
    $created_date = new DateTime($user['created_at']);
    $now = new DateTime();
    $interval = $created_date->diff($now);
    $account_age = $interval->days;
} catch (Exception $e) {
    // Silently fail - stats are not critical
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validate name and email
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($email !== $user['email']) {
            // Check if new email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $errors['email'] = 'Email already in use';
            }
        }
        
        // If no errors, update user
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$name, $email, $user_id]);
                
                if ($result) {
                    // Update session
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $name;
                    
                    $success = true;
                    $success_message = 'Profile information updated successfully.';
                } else {
                    $errors['general'] = 'Failed to update profile. Please try again.';
                }
            } catch (Exception $e) {
                $errors['general'] = 'System error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        
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
        
        // If no errors, update password
        if (empty($errors)) {
            try {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$password_hash, $user_id]);
                
                if ($result) {
                    $success = true;
                    $success_message = 'Password updated successfully. Your account is now more secure.';
                    
                    // Log the password change
                    try {
                        $stmt = $pdo->prepare("INSERT INTO security_log (user_id, action, ip_address, timestamp) VALUES (?, 'password_change', ?, NOW())");
                        $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
                    } catch (Exception $e) {
                        // Silently fail - logging is not critical
                    }
                } else {
                    $errors['general'] = 'Failed to update password. Please try again.';
                }
            } catch (Exception $e) {
                $errors['general'] = 'System error: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | SECURE_VAULT</title>
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
    <!-- Enhanced Navigation Bar -->
    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-50 py-3">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="mr-3 text-indigo-500">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <a href="dashboard.php" class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-500 to-indigo-600">VAULT</a>
            </div>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 text-slate-300 hover:text-indigo-400 transition-all duration-300 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white font-medium">
                        <?php echo substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                    </div>
                    <span class="font-medium"><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300" :class="{'rotate-180': open}"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-3 w-56 glass-card rounded-xl shadow-lg py-2 z-50">
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-indigo-400 transition-all duration-300">
                        <i class="fas fa-tachometer-alt mr-3 text-indigo-400"></i> Command Center
                    </a>
                    <div class="border-t border-slate-700/50 my-1"></div>
                    <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-red-400 transition-all duration-300">
                        <i class="fas fa-sign-out-alt mr-3 text-red-400"></i> Secure Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">
        <!-- Breadcrumb Navigation -->
        <div class="mb-6">
            <div class="flex items-center text-sm text-slate-400">
                <a href="dashboard.php" class="hover:text-indigo-400 transition-colors">Command Center</a>
                <i class="fas fa-chevron-right mx-2 text-xs text-slate-600"></i>
                <span class="text-slate-300">Account Settings</span>
            </div>
        </div>
        
        <?php if ($success): ?>
        <div class="mb-6 bg-emerald-900/50 text-emerald-400 p-4 rounded-xl border border-emerald-700/50 flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <div>
                <p class="font-medium"><?php echo $success_message ?? 'Settings updated successfully.'; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
        <div class="mb-6 bg-rose-900/50 text-rose-400 p-4 rounded-xl border border-rose-700/50 flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <div>
                <p class="font-medium"><?php echo $errors['general']; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar with User Info -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-2xl p-6 border border-slate-700/50 h-full">
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white text-4xl font-bold mb-4">
                            <?php echo substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                        </div>
                        <h2 class="text-xl font-bold text-white"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-slate-400 mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <div class="w-full mt-6 pt-6 border-t border-slate-700/50">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-slate-400 text-sm">Account Status</span>
                                <span class="bg-emerald-900/50 text-emerald-400 text-xs px-2 py-1 rounded-full border border-emerald-700/30">Active</span>
                            </div>
                            
                            <div class="space-y-4 text-left">
                                <div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-400">API Nodes</span>
                                        <span class="text-white font-medium"><?php echo $credential_count; ?></span>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-400">Account Age</span>
                                        <span class="text-white font-medium"><?php echo $account_age; ?> days</span>
                                    </div>
                                </div>
                                
                                <?php if ($last_login): ?>
                                <div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-400">Last Login</span>
                                        <span class="text-white font-medium"><?php echo date('M j, Y', strtotime($last_login)); ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-400">Member Since</span>
                                        <span class="text-white font-medium"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <a href="dashboard.php" class="w-full inline-flex items-center justify-center px-4 py-3 border border-slate-600/50 text-slate-300 rounded-xl hover:bg-slate-800/70 transition-all duration-300 font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Command Center
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div x-data="{ activeTab: 'profile' }">
                    <!-- Tabs -->
                    <div class="flex border-b border-slate-700/50 mb-6">
                        <button @click="activeTab = 'profile'" 
                                :class="{'text-indigo-400 border-indigo-400': activeTab === 'profile', 'text-slate-400 border-transparent hover:text-slate-300': activeTab !== 'profile'}"
                                class="px-4 py-3 font-medium border-b-2 transition-all duration-200 mr-4 focus:outline-none">
                            <i class="fas fa-user mr-2"></i> Profile
                        </button>
                        <button @click="activeTab = 'security'" 
                                :class="{'text-indigo-400 border-indigo-400': activeTab === 'security', 'text-slate-400 border-transparent hover:text-slate-300': activeTab !== 'security'}"
                                class="px-4 py-3 font-medium border-b-2 transition-all duration-200 focus:outline-none">
                            <i class="fas fa-lock mr-2"></i> Security
                        </button>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <div class="glass-card rounded-2xl p-6 border border-slate-700/50">
                            <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500 mb-6 flex items-center">
                                <i class="fas fa-user-edit mr-3 text-indigo-400"></i> Profile Information
                            </h2>
                            
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div>
                                    <label for="name" class="block text-sm font-medium text-indigo-300 mb-2">Full Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-indigo-400"></i>
                                        </div>
                                        <input type="text" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>" 
                                               class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                               placeholder="Enter your full name">
                                    </div>
                                    <?php if (!empty($errors['name'])): ?>
                                        <p class="text-rose-400 text-sm mt-2 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <?php echo $errors['name']; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-indigo-300 mb-2">Email Address</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-indigo-400"></i>
                                        </div>
                                        <input type="email" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" 
                                               class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                               placeholder="Enter your email address">
                                    </div>
                                    <?php if (!empty($errors['email'])): ?>
                                        <p class="text-rose-400 text-sm mt-2 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <?php echo $errors['email']; ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-slate-500 text-sm mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        This email is used for account recovery and notifications.
                                    </p>
                                </div>
                                
                                <div class="pt-4 flex justify-end">
                                    <button type="submit" 
                                            class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                                        <i class="fas fa-save mr-2"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <div class="glass-card rounded-2xl p-6 border border-slate-700/50">
                            <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500 mb-6 flex items-center">
                                <i class="fas fa-shield-alt mr-3 text-indigo-400"></i> Security Settings
                            </h2>
                            
                            <form method="POST" class="space-y-6" x-data="{ passwordStrength: 0, passwordMessage: '' }">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-indigo-300 mb-2">Current Password</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-indigo-400"></i>
                                        </div>
                                        <input type="password" id="current_password" name="current_password" 
                                               class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                               placeholder="Enter your current password">
                                    </div>
                                    <?php if (!empty($errors['current_password'])): ?>
                                        <p class="text-rose-400 text-sm mt-2 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <?php echo $errors['current_password']; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-indigo-300 mb-2">New Password</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-key text-indigo-400"></i>
                                        </div>
                                        <input type="password" id="new_password" name="new_password" 
                                               class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                               placeholder="Enter your new password"
                                               @input="checkPasswordStrength($event.target.value)">
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
                                
                                <div class="pt-4 flex justify-end">
                                    <button type="submit" 
                                            class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                                        <i class="fas fa-shield-alt mr-2"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Additional Security Options -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-700/50 mt-6">
                            <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500 mb-4 flex items-center">
                                <i class="fas fa-fingerprint mr-3 text-indigo-400"></i> Additional Security
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-slate-800/30 rounded-xl border border-slate-700/50">
                                    <div>
                                        <h4 class="font-medium text-white">Two-Factor Authentication</h4>
                                        <p class="text-sm text-slate-400 mt-1">Add an extra layer of security to your account</p>
                                    </div>
                                    <div>
                                        <span class="bg-slate-700/50 text-slate-400 text-xs px-3 py-1 rounded-full">Coming Soon</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-slate-800/30 rounded-xl border border-slate-700/50">
                                    <div>
                                        <h4 class="font-medium text-white">Login History</h4>
                                        <p class="text-sm text-slate-400 mt-1">View your recent login activity</p>
                                    </div>
                                    <div>
                                        <a href="#" class="text-indigo-400 hover:text-indigo-300 transition-colors">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-slate-800/30 rounded-xl border border-slate-700/50">
                                    <div>
                                        <h4 class="font-medium text-white">API Access Logs</h4>
                                        <p class="text-sm text-slate-400 mt-1">Monitor when your API credentials are accessed</p>
                                    </div>
                                    <div>
                                        <a href="#" class="text-indigo-400 hover:text-indigo-300 transition-colors">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Danger Zone -->
                        <div class="glass-card rounded-2xl p-6 border border-rose-700/30 mt-6">
                            <h3 class="text-xl font-bold text-rose-400 mb-4 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-3"></i> Danger Zone
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-rose-900/20 rounded-xl border border-rose-700/30">
                                    <div>
                                        <h4 class="font-medium text-white">Delete Account</h4>
                                        <p class="text-sm text-slate-400 mt-1">Permanently delete your account and all associated data</p>
                                    </div>
                                    <div>
                                        <button type="button" onclick="confirmDelete()" class="px-4 py-2 bg-rose-900/50 text-rose-400 border border-rose-700/50 rounded-lg hover:bg-rose-800/50 transition-all duration-300">
                                            Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    
    // Confirm account deletion
    function confirmDelete() {
        if (confirm('WARNING: This action cannot be undone. All your API credentials and account data will be permanently deleted. Are you sure you want to proceed?')) {
            // Redirect to delete account page
            window.location.href = 'delete_account.php';
        }
    }
    </script>
</body>
</html>