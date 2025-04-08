<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$login_attempts = 0;

// Check for login attempts in session
if (isset($_SESSION['login_attempts'])) {
    $login_attempts = $_SESSION['login_attempts'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, email, name, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            
            // Reset login attempts
            $_SESSION['login_attempts'] = 0;
            
            // Set remember me cookie if requested
            if ($remember_me) {
                setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 days
            }
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            redirect('dashboard.php');
        } else {
            // Login failed
            $_SESSION['login_attempts'] = $login_attempts + 1;
            
            if ($_SESSION['login_attempts'] >= 5) {
                $errors['general'] = 'Too many failed login attempts. Please try again later.';
            } else {
                $remaining_attempts = 5 - $_SESSION['login_attempts'];
                $errors['general'] = "Invalid email or password. $remaining_attempts attempts remaining.";
            }
        }
    }
}

include 'header.php';
?>

<script>
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

// Animated login button
function animateLoginButton() {
    const button = document.getElementById('login-button');
    const icon = document.getElementById('login-icon');
    const text = document.getElementById('login-text');
    
    button.disabled = true;
    text.textContent = 'AUTHENTICATING';
    icon.className = 'fas fa-circle-notch fa-spin ml-2';
    
    return true;
}
</script>

<div class="max-w-md mx-auto my-12 bg-gray-800/80 backdrop-blur-md rounded-xl shadow-neon overflow-hidden border border-gray-700/50">
    <div class="bg-gradient-to-r from-futuristic-700 to-futuristic-900 p-6 text-white text-center border-b border-futuristic-500/30">
        <h1 class="text-2xl font-bold font-mono">SYSTEM ACCESS</h1>
        <p class="text-futuristic-300 mt-1 text-sm font-mono">Authentication required</p>
    </div>
    
    <div class="p-8">
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-900/50 text-red-300 p-4 rounded-md mb-6 flex items-start border border-red-700/50">
                <i class="fas fa-exclamation-triangle mt-1 mr-3 text-red-400"></i>
                <div class="font-mono text-sm"><?php echo htmlspecialchars($errors['general'], ENT_QUOTES); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($login_attempts >= 5): ?>
        <!-- Account Locked Message -->
        <div class="bg-red-900/50 text-red-300 p-6 rounded-md mb-6 flex flex-col items-center border border-red-700/50 text-center">
            <div class="text-4xl mb-4 text-red-400">
                <i class="fas fa-user-lock"></i>
            </div>
            <h3 class="text-lg font-bold mb-2">ACCOUNT LOCKED</h3>
            <p class="mb-4">Too many failed login attempts. Please try again later or reset your password.</p>
            <a href="forgot-password.php" class="mt-2 inline-flex items-center text-futuristic-400 hover:text-futuristic-300">
                <i class="fas fa-key mr-2"></i> Reset your password
            </a>
        </div>
        <?php else: ?>
        <!-- Login Form -->
        <form method="POST" class="space-y-6" onsubmit="return animateLoginButton()">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2 font-mono">USER_ID</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-at text-gray-500"></i>
                    </div>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $_COOKIE['remember_email'] ?? '', ENT_QUOTES); ?>" 
                           class="pl-10 w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-futuristic-500 focus:border-transparent text-gray-200 placeholder-gray-500"
                           placeholder="your.email@example.com" autocomplete="email" autofocus>
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <p class="text-red-400 text-sm mt-2 flex items-center font-mono">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($errors['email'], ENT_QUOTES); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label for="password" class="block text-sm font-medium text-gray-300 font-mono">PASSWORD</label>
                    <a href="forgot-password.php" class="text-sm text-futuristic-400 hover:text-futuristic-300 hover:underline">
                        Forgot password?
                    </a>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-key text-gray-500"></i>
                    </div>
                    <input type="password" id="password" name="password" 
                           class="pl-10 pr-10 w-full px-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-futuristic-500 focus:border-transparent text-gray-200 placeholder-gray-500"
                           placeholder="••••••••" autocomplete="current-password">
                    <button type="button" id="password-toggle" onclick="togglePasswordVisibility('password')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-futuristic-300">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <p class="text-red-400 text-sm mt-2 flex items-center font-mono">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($errors['password'], ENT_QUOTES); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center">
                <input id="remember-me" name="remember_me" type="checkbox" 
                       class="h-4 w-4 text-futuristic-500 focus:ring-futuristic-500 border-gray-600 rounded bg-gray-700/50"
                       <?php echo isset($_POST['remember_me']) || isset($_COOKIE['remember_email']) ? 'checked' : ''; ?>>
                <label for="remember-me" class="ml-2 block text-sm text-gray-300">PERSIST_SESSION</label>
            </div>
            
            <button id="login-button" type="submit" class="w-full bg-gradient-to-r from-futuristic-600 to-futuristic-800 hover:from-futuristic-500 hover:to-futuristic-700 text-white py-3 px-4 rounded-lg font-medium shadow-neon hover:shadow-neon-lg transition-all group">
                <span id="login-text">AUTHENTICATE</span> <i id="login-icon" class="fas fa-fingerprint ml-2 group-hover:animate-pulse"></i>
            </button>
        </form>
        <?php endif; ?>
        
        <!-- Security Notice -->
        <div class="mt-6 text-center">
            <div class="inline-flex items-center text-xs text-gray-500">
                <i class="fas fa-shield-alt mr-2 text-futuristic-400"></i>
                <span>Secured with advanced encryption</span>
            </div>
        </div>
    </div>
    
    <div class="bg-gray-800/50 px-8 py-4 border-t border-gray-700/30 text-center">
        <p class="text-gray-400 text-sm font-mono">
            NEW USER? <a href="register.php" class="text-futuristic-400 font-medium hover:underline hover:text-futuristic-300">INITIALIZE_ACCOUNT</a>
        </p>
    </div>
</div>

<style>
@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

.animate-pulse {
    animation: pulse 1.5s ease-in-out infinite;
}
</style>

<?php include 'footer.php'; ?>