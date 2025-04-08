<?php 
require_once 'config.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Get system stats for display
$stats = [
    'users' => 0,
    'credentials' => 0,
    'uptime' => '99.9%'
];

try {
    // Count total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['users'] = number_format($stmt->fetchColumn());
    
    // Count total credentials
    $stmt = $pdo->query("SELECT COUNT(*) FROM api_credentials");
    $stats['credentials'] = number_format($stmt->fetchColumn());
    
    // Get latest system news
    $stmt = $pdo->query("SELECT * FROM system_news ORDER BY created_at DESC LIMIT 1");
    $latest_news = $stmt->fetch();
} catch (Exception $e) {
    // Silently fail - stats are not critical
}

// Set page title and description for SEO
$page_title = "Secure API Credential Management";
$page_description = "SECURE_VAULT provides military-grade encryption for managing and storing your API credentials with advanced security features.";

include 'header.php'; 
?>

<!-- Main Content -->
<main class="min-h-screen">
    <!-- Hero Section -->
    <section class="py-16 md:py-24 bg-gradient-to-b from-midnight-300 via-midnight-400 to-midnight-300 relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1NiIgaGVpZ2h0PSIxMDAiPgo8cGF0aCBkPSJNMjggNjZMMCA1MEwwIDE2TDI4IDBMNTYgMTZMNTYgNTBMMjggNjZMMjggMTAwIiBmaWxsPSJub25lIiBzdHJva2U9IiMzMzMzMzMiIHN0cm9rZS13aWR0aD0iMSIvPgo8L3N2Zz4=')]"></div>
        </div>
        
        <!-- Floating Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-futuristic-600/10 rounded-full filter blur-3xl animate-float"></div>
            <div class="absolute bottom-1/3 right-1/3 w-96 h-96 bg-cyber-700/10 rounded-full filter blur-3xl animate-float-reverse" style="animation-delay: 1s;"></div>
            <div class="absolute top-2/3 right-1/4 w-48 h-48 bg-futuristic-400/5 rounded-full filter blur-2xl animate-float-slow" style="animation-delay: 2s;"></div>
        </div>
        
        <!-- Hero Content -->
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col md:flex-row items-center justify-between gap-12">
                <!-- Left Column: Text Content -->
                <div class="md:w-1/2 space-y-8 text-center md:text-left">
                    <div class="space-y-6">
                        <div class="inline-flex items-center space-x-2 bg-midnight-200/30 px-3 py-1 rounded-full text-xs font-mono text-futuristic-400 border border-futuristic-500/20 shadow-neon">
                            <span class="h-2 w-2 rounded-full bg-futuristic-400 animate-pulse"></span>
                            <span>SYSTEM ONLINE</span>
                        </div>
                        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl leading-tight font-mono relative">
                            <span class="cyber-text animate-text-shimmer">SECURE<span class="text-futuristic-400 animate-pulse-slow">_</span>VAULT</span>
                            <span class="absolute -inset-1 bg-futuristic-500/5 blur-xl opacity-30 animate-pulse-slow"></span>
                        </h1>
                        <p class="text-xl text-gray-300 leading-relaxed">
                            <span class="text-futuristic-400 font-medium">>></span> Advanced credential management system for the modern developer <span class="text-futuristic-400 font-medium"><<</span>
                        </p>
                        <p class="text-gray-400">
                            Protect your API keys, tokens, and sensitive credentials with military-grade encryption and intuitive management tools.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-6 justify-center md:justify-start">
                        <a href="register.php" 
                           class="futuristic-btn group">
                            INITIALIZE SYSTEM <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="login.php" 
                           class="border-2 border-futuristic-500 text-futuristic-400 hover:bg-midnight-300/50 px-8 py-3 rounded-lg font-semibold text-lg transition-all duration-300 group shadow-neon hover:shadow-neon-lg">
                            <i class="fas fa-terminal mr-2 group-hover:text-futuristic-300 transition-colors"></i> ACCESS PORTAL
                        </a>
                    </div>
                    
                    <!-- System Stats -->
                    <div class="grid grid-cols-3 gap-4 pt-8 max-w-lg mx-auto md:mx-0">
                        <div class="text-center p-3 bg-midnight-300/50 rounded-lg border border-futuristic-500/20 hover:border-futuristic-500/40 transition-colors shadow-neon cyberpunk-card group">
                            <div class="text-futuristic-400 text-2xl font-bold group-hover:text-futuristic-300 transition-colors"><?php echo $stats['users']; ?>+</div>
                            <div class="text-gray-400 text-sm font-mono">USERS</div>
                        </div>
                        <div class="text-center p-3 bg-midnight-300/50 rounded-lg border border-futuristic-500/20 hover:border-futuristic-500/40 transition-colors shadow-neon cyberpunk-card group">
                            <div class="text-futuristic-400 text-2xl font-bold group-hover:text-futuristic-300 transition-colors"><?php echo $stats['credentials']; ?>+</div>
                            <div class="text-gray-400 text-sm font-mono">CREDENTIALS</div>
                        </div>
                        <div class="text-center p-3 bg-midnight-300/50 rounded-lg border border-futuristic-500/20 hover:border-futuristic-500/40 transition-colors shadow-neon cyberpunk-card group">
                            <div class="text-futuristic-400 text-2xl font-bold group-hover:text-futuristic-300 transition-colors"><?php echo $stats['uptime']; ?></div>
                            <div class="text-gray-400 text-sm font-mono">UPTIME</div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Animated Illustration -->
                <div class="md:w-1/2 flex justify-center">
                    <div class="relative w-full max-w-md">
                        <!-- Main Vault Illustration -->
                        <div class="holographic-card p-8 rounded-2xl shadow-hologram border border-futuristic-500/30 transform rotate-3 animate-float">
                            <div class="aspect-square relative overflow-hidden rounded-xl bg-midnight-400/80 border border-futuristic-600/50 flex items-center justify-center">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-32 h-32 rounded-full bg-futuristic-600/20 animate-pulse-slow"></div>
                                    <div class="w-48 h-48 rounded-full border-2 border-futuristic-500/30 absolute animate-spin-slow"></div>
                                    <div class="w-64 h-64 rounded-full border border-futuristic-500/20 absolute animate-spin-reverse"></div>
                                </div>
                                <div class="relative z-10 text-center">
                                    <i class="fas fa-shield-alt text-6xl text-futuristic-400 mb-4 animate-pulse-slow"></i>
                                    <div class="text-xl font-bold text-white font-mono">SECURE VAULT</div>
                                    <div class="text-futuristic-400 text-sm mt-2 font-mono terminal-text">SYSTEM ONLINE</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating Elements -->
                        <div class="absolute -top-6 -right-6 holographic-card p-4 rounded-lg shadow-neon border border-futuristic-500/30 transform -rotate-6 animate-float" style="animation-delay: 0.5s;">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-key text-futuristic-400"></i>
                                <div class="font-mono text-sm terminal-text">API_KEY_SECURE</div>
                            </div>
                        </div>
                        
                        <div class="absolute -bottom-4 -left-4 holographic-card p-4 rounded-lg shadow-neon border border-futuristic-500/30 transform rotate-12 animate-float" style="animation-delay: 1s;">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-fingerprint text-futuristic-400"></i>
                                <div class="font-mono text-sm terminal-text">BIOMETRIC_AUTH</div>
                            </div>
                        </div>
                        
                        <div class="absolute top-1/2 -right-10 holographic-card p-3 rounded-lg shadow-neon border border-futuristic-500/30 transform rotate-6 animate-float-slow" style="animation-delay: 1.5s;">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-lock text-futuristic-400"></i>
                                <div class="font-mono text-xs terminal-text">ENCRYPTION_ACTIVE</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="pt-12 flex justify-center">
                <div class="inline-flex items-center px-4 py-2 bg-midnight-300/50 border border-futuristic-500/20 rounded-full text-sm text-gray-300 shadow-neon">
                    <span class="h-2 w-2 rounded-full bg-green-400 mr-2 animate-pulse"></span>
                    <span class="font-mono">SYSTEM STATUS: ONLINE</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 md:py-24 bg-midnight-400/30 backdrop-blur-sm relative">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="h-full w-full bg-[radial-gradient(#00b9ff_1px,transparent_1px)] [background-size:20px_20px]"></div>
        </div>
        
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <div class="inline-flex items-center space-x-2 bg-midnight-200/30 px-3 py-1 rounded-full text-xs font-mono text-futuristic-400 border border-futuristic-500/20 shadow-neon mb-4">
                    <i class="fas fa-microchip"></i>
                    <span>ADVANCED TECHNOLOGY</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-100 mb-4 font-mono shimmer-text">CORE SYSTEMS</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">Essential modules for credential security</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="cyberpunk-card p-6 rounded-xl shadow-neon hover:shadow-neon-lg transition-all duration-300 group">
                    <div class="text-futuristic-400 mb-4 group-hover:text-futuristic-300 transition-colors">
                        <i class="fas fa-lock text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3 font-mono">QUANTUM ENCRYPTION</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Military-grade AES-256 encryption with quantum-resistant algorithms protects your credentials.
                    </p>
                    <div class="mt-4 pt-4 border-t border-midnight-200/30">
                        <ul class="text-sm text-gray-400 space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check text-futuristic-400 mt-1 mr-2"></i>
                                <span>End-to-end encryption</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-futuristic-400 mt-1 mr-2"></i>
                                <span>Zero-knowledge architecture</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="cyberpunk-card p-6 rounded-xl shadow-neon hover:shadow-neon-lg transition-all duration-300 group">
                    <div class="text-futuristic-400 mb-4 group-hover:text-futuristic-300 transition-colors">
                        <i class="fas fa-brain text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3 font-mono">NEURAL INTERFACE</h3>
                    <p class="text-gray-400 leading-relaxed">
                        AI-powered organization system learns your patterns for intuitive credential management.
                    </p>
                    <div class="mt-4 pt-4 border-t border-midnight-200/30">
                        <ul class="text-sm text-gray-400 space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check text-futuristic-400 mt-1 mr-2"></i>
                                <span>Smart categorization</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-futuristic-400 mt-1 mr-2"></i>
                                <span>Usage analytics</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="cyberpunk-card p-6 rounded-xl shadow-neon hover:shadow-neon-lg transition-all duration-300 group">
                    <div class="text-futuristic-400 mb-4 group-hover:text-futuristic-300 transition-colors">
                        <i class="fas fa-shield-virus text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3 font-mono">THREAT DETECTION</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Real-time monitoring for credential leaks with instant breach alerts and auto-rotation.
                    </p>
                    <div class="mt-4 pt-4 border-t border-midnight-200/30">
                        <ul class="text-sm text-gray-400 space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check text-futuristic-400 mt-1 mr-2"></i>
                                <span>Breach detection</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-futuristic-400 mt-1 mr-2"></i>
                                <span>Automated key rotation</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- How It Works Section -->
            <div class="mt-24">
                <div class="text-center mb-16">
                    <div class="inline-flex items-center space-x-2 bg-midnight-200/30 px-3 py-1 rounded-full text-xs font-mono text-futuristic-400 border border-futuristic-500/20 shadow-neon mb-4">
                        <i class="fas fa-code-branch"></i>
                        <span>WORKFLOW</span>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-100 mb-4 font-mono shimmer-text">HOW IT WORKS</h2>
                    <p class="text-gray-400 max-w-2xl mx-auto">Simple, secure, and efficient credential management</p>
                </div>
                
                <div class="grid md:grid-cols-4 gap-8 max-w-5xl mx-auto">
                    <div class="text-center group">
                        <div class="w-16 h-16 rounded-full bg-midnight-300 border border-futuristic-500 flex items-center justify-center mx-auto mb-4 relative shadow-neon group-hover:shadow-neon-lg transition-all duration-300">
                            <span class="text-futuristic-400 font-bold text-xl">1</span>
                            <div class="absolute -right-4 top-1/2 transform -translate-y-1/2 w-8 h-0.5 bg-futuristic-500/50 hidden md:block"></div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-100 mb-2 font-mono">REGISTER</h3>
                        <p class="text-gray-400 text-sm">Create your secure account with multi-factor authentication</p>
                    </div>
                    
                    <div class="text-center group">
                        <div class="w-16 h-16 rounded-full bg-midnight-300 border border-futuristic-500 flex items-center justify-center mx-auto mb-4 relative shadow-neon group-hover:shadow-neon-lg transition-all duration-300">
                            <span class="text-futuristic-400 font-bold text-xl">2</span>
                            <div class="absolute -right-4 top-1/2 transform -translate-y-1/2 w-8 h-0.5 bg-futuristic-500/50 hidden md:block"></div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-100 mb-2 font-mono">STORE</h3>
                        <p class="text-gray-400 text-sm">Add your API keys and credentials with custom metadata</p>
                    </div>
                    
                    <div class="text-center group">
                        <div class="w-16 h-16 rounded-full bg-midnight-300 border border-futuristic-500 flex items-center justify-center mx-auto mb-4 relative shadow-neon group-hover:shadow-neon-lg transition-all duration-300">
                            <span class="text-futuristic-400 font-bold text-xl">3</span>
                            <div class="absolute -right-4 top-1/2 transform -translate-y-1/2 w-8 h-0.5 bg-futuristic-500/50 hidden md:block"></div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-100 mb-2 font-mono">ACCESS</h3>
                        <p class="text-gray-400 text-sm">Retrieve credentials securely whenever you need them</p>
                    </div>
                    
                    <div class="text-center group">
                        <div class="w-16 h-16 rounded-full bg-midnight-300 border border-futuristic-500 flex items-center justify-center mx-auto mb-4 relative shadow-neon group-hover:shadow-neon-lg transition-all duration-300">
                            <span class="text-futuristic-400 font-bold text-xl">4</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-100 mb-2 font-mono">MONITOR</h3>
                        <p class="text-gray-400 text-sm">Track usage and receive security alerts</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    

    <!-- CTA Section -->
    <section class="py-16 md:py-20 bg-midnight-400/30 backdrop-blur-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center space-x-2 bg-midnight-200/30 px-3 py-1 rounded-full text-xs font-mono text-futuristic-400 border border-futuristic-500/20 shadow-neon mb-4">
                    <i class="fas fa-rocket"></i>
                    <span>GET STARTED</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-100 mb-6 font-mono shimmer-text">READY TO SECURE YOUR CREDENTIALS?</h2>
                <p class="text-xl text-gray-300 mb-8">
                    Join thousands of developers who trust SECURE_VAULT with their sensitive API keys and tokens.
                </p>
                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <a href="register.php" 
                       class="futuristic-btn group py-4 px-8 text-lg">
                        GET STARTED NOW <i class="fas fa-rocket ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <?php if (isset($latest_news) && $latest_news): ?>
    <!-- System News Banner -->
    <div class="bg-midnight-400/80 border-y border-futuristic-500/30 py-3 scanline">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-center flex-wrap">
                <div class="flex items-center text-futuristic-400 mr-3">
                    <i class="fas fa-newspaper mr-2 animate-pulse-slow"></i>
                    <span class="font-medium font-mono">SYSTEM NEWS:</span>
                </div>
                <div class="text-gray-300 font-mono">
                    <?php echo htmlspecialchars($latest_news['content']); ?>
                </div>
                <div class="text-gray-500 text-sm ml-3 font-mono">
                    <?php echo date('M j, Y', strtotime($latest_news['created_at'])); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
