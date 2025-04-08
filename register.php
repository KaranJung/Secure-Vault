<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$show_loading = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validation
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = 'Email already in use';
        }
    }
    
    // Process registration if no errors
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $password_hash])) {
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;
            session_regenerate_id(true);
            $show_loading = true; // Flag to show loading animation
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

include 'header.php';
?>

<script>
// Password strength validation with visual meter
function validatePassword() {
    const password = document.getElementById('password').value;
    const meter = document.getElementById('password-meter');
    const strengthText = document.getElementById('password-strength-text');
    const requirements = document.getElementById('password-requirements');
    
    if (!password) {
        meter.style.width = '0%';
        meter.className = 'h-full rounded-full';
        strengthText.textContent = '';
        requirements.style.display = 'none';
        return;
    }
    
    requirements.style.display = 'block';
    
    // Check requirements
    const hasLength = password.length >= 8;
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[^A-Za-z0-9]/.test(password);
    
    // Update requirement indicators
    document.getElementById('req-length').className = hasLength ? 'text-green-400' : 'text-gray-500';
    document.getElementById('req-upper').className = hasUpper ? 'text-green-400' : 'text-gray-500';
    document.getElementById('req-lower').className = hasLower ? 'text-green-400' : 'text-gray-500';
    document.getElementById('req-number').className = hasNumber ? 'text-green-400' : 'text-gray-500';
    document.getElementById('req-special').className = hasSpecial ? 'text-green-400' : 'text-gray-500';
    
    // Calculate strength score (0-4)
    let score = 0;
    if (hasLength) score++;
    if (hasUpper && hasLower) score++;
    if (hasNumber) score++;
    if (hasSpecial) score++;
    
    // Update meter
    const percentage = (score / 4) * 100;
    meter.style.width = `${percentage}%`;
    
    // Update meter color and text
    if (score < 2) {
        meter.className = 'h-full rounded-full bg-red-500';
        strengthText.textContent = 'Weak';
        strengthText.className = 'text-red-400 text-sm ml-2';
    } else if (score < 3) {
        meter.className = 'h-full rounded-full bg-yellow-500';
        strengthText.textContent = 'Moderate';
        strengthText.className = 'text-yellow-400 text-sm ml-2';
    } else if (score < 4) {
        meter.className = 'h-full rounded-full bg-blue-500';
        strengthText.textContent = 'Strong';
        strengthText.className = 'text-blue-400 text-sm ml-2';
    } else {
        meter.className = 'h-full rounded-full bg-green-500';
        strengthText.textContent = 'Very Strong';
        strengthText.className = 'text-green-400 text-sm ml-2';
    }
}

// Password visibility toggle
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.querySelector(`#${fieldId}-toggle i`);
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Password match validation
function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirm').value;
    const matchIndicator = document.getElementById('password-match-indicator');
    
    if (!confirmPassword) {
        matchIndicator.style.display = 'none';
        return;
    }
    
    matchIndicator.style.display = 'flex';
    
    if (password === confirmPassword) {
        matchIndicator.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Passwords match';
        matchIndicator.className = 'text-green-400 text-sm mt-2 flex items-center';
    } else {
        matchIndicator.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Passwords do not match';
        matchIndicator.className = 'text-red-400 text-sm mt-2 flex items-center';
    }
}

// Show loading animation and redirect
<?php if ($show_loading): ?>
    window.onload = function() {
        document.getElementById('registration-form').style.display = 'none';
        document.getElementById('loading-overlay').style.display = 'flex';
        
        // Simulate progress
        const progressBar = document.getElementById('progress-bar');
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 100) progress = 100;
            progressBar.style.width = `${progress}%`;
            
            if (progress === 100) {
                clearInterval(interval);
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 500);
            }
        }, 500);
    }
<?php endif; ?>
</script>

<div class="max-w-md mx-auto my-12 bg-gray-800/80 backdrop-blur-md rounded-xl shadow-neon overflow-hidden border border-gray-700/50">
    <div class="bg-gradient-to-r from-futuristic-700 to-futuristic-900 p-6 text-white text-center border-b border-futuristic-500/30">
        <h1 class="text-2xl font-bold font-mono">ACCOUNT INITIALIZATION</h1>
        <p class="text-futuristic-300 mt-1 text-sm font-mono">New user registration</p>
    </div>
    
    <div class="p-8 relative">
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-900/50 text-red-300 p-4 rounded-md mb-6 flex items-start border border-red-700/50">
                <i class="fas fa-exclamation-triangle mt-1 mr-3 text-red-400"></i>
                <div class="font-mono text-sm"><?php echo htmlspecialchars($errors['general'], ENT_QUOTES); ?></div>
            </div>
        <?php endif; ?>
        
        <form id="registration-form" method="POST" class="space-y-6" <?php echo $show_loading ? 'style="display: none;"' : ''; ?>>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2 font-mono">USER IDENTIFIER</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-id-card text-gray-500"></i>
                    </div>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES); ?>" 
                           class="pl-10 w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-futuristic-500 focus:border-transparent text-gray-200 placeholder-gray-500"
                           placeholder="Enter your full name" autocomplete="name">
                </div>
                <?php if (!empty($errors['name'])): ?>
                    <p class="text-red-400 text-sm mt-2 flex items-center font-mono">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($errors['name'], ENT_QUOTES); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2 font-mono">EMAIL</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-at text-gray-500"></i>
                    </div>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" 
                           class="pl-10 w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-futuristic-500 focus:border-transparent text-gray-200 placeholder-gray-500"
                           placeholder="your.email@example.com" autocomplete="email">
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <p class="text-red-400 text-sm mt-2 flex items-center font-mono">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($errors['email'], ENT_QUOTES); ?>
                    </p>
                <?php else: ?>
                    <p class="text-gray-500 text-xs mt-1">
                        <i class="fas fa-info-circle mr-1"></i> We'll never share your email with anyone else.
                    </p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2 font-mono">PASSWORD</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-key text-gray-500"></i>
                    </div>
                    <input type="password" id="password" name="password" 
                           oninput="validatePassword()"
                           class="pl-10 pr-10 w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-futuristic-500 focus:border-transparent text-gray-200 placeholder-gray-500"
                           placeholder="••••••••" autocomplete="new-password">
                    <button type="button" id="password-toggle" onclick="togglePasswordVisibility('password')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-futuristic-300">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <!-- Password Strength Meter -->
                <div class="flex items-center mt-2">
                    <div class="h-2 flex-grow bg-gray-700 rounded-full overflow-hidden">
                        <div id="password-meter" class="h-full rounded-full" style="width: 0%"></div>
                    </div>
                    <span id="password-strength-text" class="text-sm ml-2"></span>
                </div>
                
                <!-- Password Requirements -->
                <div id="password-requirements" style="display:none;" class="text-xs mt-3 grid grid-cols-2 gap-2">
                    <div id="req-length" class="text-gray-500 flex items-center">
                        <i class="fas fa-check-circle mr-1"></i> 8+ characters
                    </div>
                    <div id="req-upper" class="text-gray-500 flex items-center">
                        <i class="fas fa-check-circle mr-1"></i> Uppercase letter
                    </div>
                    <div id="req-lower" class="text-gray-500 flex items-center">
                        <i class="fas fa-check-circle mr-1"></i> Lowercase letter
                    </div>
                    <div id="req-number" class="text-gray-500 flex items-center">
                        <i class="fas fa-check-circle mr-1"></i> Number
                    </div>
                    <div id="req-special" class="text-gray-500 flex items-center col-span-2">
                        <i class="fas fa-check-circle mr-1"></i> Special character (recommended)
                    </div>
                </div>
                
                <?php if (!empty($errors['password'])): ?>
                    <p class="text-red-400 text-sm mt-2 flex items-center font-mono">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($errors['password'], ENT_QUOTES); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password_confirm" class="block text-sm font-medium text-gray-300 mb-2 font-mono">VERIFY PASSWORD</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-shield-alt text-gray-500"></i>
                    </div>
                    <input type="password" id="password_confirm" name="password_confirm"
                           oninput="validatePasswordMatch()"
                           class="pl-10 pr-10 w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-futuristic-500 focus:border-transparent text-gray-200 placeholder-gray-500"
                           placeholder="••••••••" autocomplete="new-password">
                    <button type="button" id="password_confirm-toggle" onclick="togglePasswordVisibility('password_confirm')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-futuristic-300">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <p id="password-match-indicator" class="text-sm mt-2 hidden items-center"></p>
                <?php if (!empty($errors['password_confirm'])): ?>
                    <p class="text-red-400 text-sm mt-2 flex items-center font-mono">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($errors['password_confirm'], ENT_QUOTES); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="terms" name="terms" type="checkbox" class="focus:ring-futuristic-500 h-4 w-4 text-futuristic-500 border-gray-600 rounded bg-gray-700/50" required>
                </div>
                <div class="ml-3 text-sm">
                    <label for="terms" class="font-medium text-gray-300">I agree to the <a href="terms.php" class="text-futuristic-400 hover:underline">Terms of Service</a> and <a href="privacy.php" class="text-futuristic-400 hover:underline">Privacy Policy</a></label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-futuristic-600 to-futuristic-800 hover:from-futuristic-500 hover:to-futuristic-700 text-white py-3 px-4 rounded-lg font-medium shadow-neon hover:shadow-neon-lg transition-all group">
                COMPLETE REGISTRATION <i class="fas fa-user-astronaut ml-2 group-hover:animate-bounce"></i>
            </button>
        </form>

        <!-- Loading Animation -->
        <div id="loading-overlay" class="fixed inset-0 bg-gray-900/90 backdrop-blur-sm flex items-center justify-center z-50" <?php echo !$show_loading ? 'style="display: none;"' : ''; ?>>
            <div class="text-center max-w-md w-full px-6">
                <div class="relative mb-8">
                    <div class="w-24 h-24 border-4 border-futuristic-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <div class="w-16 h-16 border-4 border-futuristic-400 border-t-transparent rounded-full animate-spin absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 animation-reverse"></div>
                    <div class="w-8 h-8 bg-futuristic-600 rounded-full animate-pulse absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                </div>
                
                <h3 class="text-2xl font-bold text-white mb-4 font-mono">ACCOUNT ACTIVATED</h3>
                <p class="text-futuristic-300 font-mono text-lg animate-pulse mb-6">Initializing Dashboard...</p>
                
                <div class="space-y-4 mb-8">
                    <div class="bg-gray-800/50 rounded-lg p-3 flex items-center">
                        <div class="w-8 h-8 rounded-full bg-futuristic-900/50 flex items-center justify-center mr-3">
                            <i class="fas fa-user-check text-futuristic-400"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-gray-300 text-sm">Creating user profile</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800/50 rounded-lg p-3 flex items-center">
                        <div class="w-8 h-8 rounded-full bg-futuristic-900/50 flex items-center justify-center mr-3">
                            <i class="fas fa-key text-futuristic-400"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-gray-300 text-sm">Generating security keys</p>
                        </div>
                        <div class="ml-auto">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800/50 rounded-lg p-3 flex items-center">
                        <div class="w-8 h-8 rounded-full bg-futuristic-900/50 flex items-center justify-center mr-3">
                            <i class="fas fa-cogs text-futuristic-400"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-gray-300 text-sm">Configuring dashboard</p>
                        </div>
                        <div class="ml-auto animate-pulse">
                            <i class="fas fa-spinner fa-spin text-futuristic-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="w-full bg-gray-700 rounded-full h-2 mb-2">
                    <div id="progress-bar" class="bg-futuristic-500 h-2 rounded-full" style="width: 0%"></div>
                </div>
                <p class="text-gray-400 text-sm">Please wait while we prepare your secure environment...</p>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800/50 px-8 py-4 border-t border-gray-700/30 text-center">
        <p class="text-gray-400 text-sm font-mono">
            EXISTING USER? <a href="login.php" class="text-futuristic-400 font-medium hover:underline hover:text-futuristic-300">ACCESS_PORTAL</a>
        </p>
    </div>
</div>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animation-reverse {
    animation-direction: reverse;
}

.animate-spin {
    animation: spin 2s linear infinite;
}

.animate-pulse {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>

<?php include 'footer.php'; ?>