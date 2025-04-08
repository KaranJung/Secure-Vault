<?php
require_once 'config.php';
requireLogin();

$user_id = getUserId();
$credential_id = $_GET['id'] ?? 0;

// Check for success message from edit or add pages
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']); // Clear after use

// Get usage statistics if available
$usage_stats = null;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as access_count, MAX(accessed_at) as last_accessed FROM credential_access_log WHERE credential_id = ?");
    $stmt->execute([$credential_id]);
    $usage_stats = $stmt->fetch();
} catch (Exception $e) {
    // Silently fail - stats are not critical
}

// Fetch the credential
$stmt = $pdo->prepare("SELECT * FROM api_credentials WHERE id = ? AND user_id = ?");
$stmt->execute([$credential_id, $user_id]);
$credential = $stmt->fetch();

if (!$credential) redirect('dashboard.php');

// Log this view in access log if table exists
try {
    $stmt = $pdo->prepare("INSERT INTO credential_access_log (credential_id, user_id, accessed_at) VALUES (?, ?, NOW())");
    $stmt->execute([$credential_id, $user_id]);
} catch (Exception $e) {
    // Silently fail - logging is not critical
}

// Decrypt API key
try {
    $decrypted_api_key = decrypt($credential['api_key'], $encryption_key);
} catch (Exception $e) {
    $decrypted_api_key = 'Error: Unable to decrypt key';
}

// Calculate days until expiry if expiry date exists
$days_until_expiry = null;
$expiry_warning = false;
if (!empty($credential['expiry_date'])) {
    $expiry_date = new DateTime($credential['expiry_date']);
    $today = new DateTime();
    $interval = $today->diff($expiry_date);
    $days_until_expiry = $interval->days;
    $expiry_warning = $interval->invert ? true : ($days_until_expiry <= 14);
}

// Note: We're using the sanitizeInput() and formatDate() functions from config.php
// Do NOT redefine them here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node Inspection - <?php echo sanitizeInput($credential['api_name']); ?></title>
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
        
        /* Tooltip styles */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: auto;
            min-width: 120px;
            background-color: rgba(15, 23, 42, 0.95);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
            white-space: nowrap;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Copy animation */
        @keyframes copy-success {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .copy-success {
            animation: copy-success 0.5s ease-in-out;
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
                    <a href="settings.php" class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-indigo-400 transition-all duration-300">
                        <i class="fas fa-cog mr-3 text-indigo-400"></i> Account Settings
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
                <span class="text-slate-300"><?php echo sanitizeInput($credential['api_name']); ?></span>
            </div>
        </div>
        
        <?php if ($success_message): ?>
        <div class="mb-6 bg-emerald-900/50 text-emerald-400 p-4 rounded-xl border border-emerald-700/50 flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <div>
                <p class="font-medium"><?php echo $success_message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Card -->
        <div class="glass-card rounded-2xl p-8 max-w-4xl mx-auto border border-slate-700/50">
            <!-- Header with API Name and Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div class="flex items-center">
                    <div class="mr-4 bg-gradient-to-br from-indigo-500 to-indigo-700 p-3 rounded-xl shadow-lg">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-500 to-indigo-600 truncate max-w-[70%]">
                            <?php echo sanitizeInput($credential['api_name']); ?>
                        </h1>
                        <p class="text-slate-400 mt-1">
                            <?php if ($credential['model_version']): ?>
                                <span class="inline-flex items-center">
                                    <i class="fas fa-code-branch mr-2 text-indigo-400"></i>
                                    <?php echo sanitizeInput($credential['model_version']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($days_until_expiry !== null): ?>
                                <span class="inline-flex items-center ml-4">
                                    <i class="fas fa-clock mr-2 <?php echo $expiry_warning ? 'text-amber-400' : 'text-indigo-400'; ?>"></i>
                                    <?php if ($expiry_warning && $days_until_expiry <= 0): ?>
                                        <span class="text-amber-400">Expired</span>
                                    <?php else: ?>
                                        <?php echo $expiry_warning ? '<span class="text-amber-400">' : ''; ?>
                                        <?php echo $days_until_expiry; ?> days 
                                        <?php echo $expiry_warning ? ($days_until_expiry <= 0 ? 'overdue' : 'remaining') . '</span>' : 'until expiry'; ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 sm:mt-0 w-full sm:w-auto">
                    <a href="edit_credential.php?id=<?php echo $credential['id']; ?>" 
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 border border-indigo-500/50 text-indigo-400 rounded-xl hover:bg-indigo-900/50 transition-all duration-300 font-medium">
                        <i class="fas fa-edit mr-2"></i> Modify
                    </a>
                    <a href="delete_credential.php?id=<?php echo $credential['id']; ?>" 
                       onclick="return confirm('Are you sure you want to terminate this node? This action is irreversible.')" 
                       class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-rose-900/50 text-rose-400 border border-rose-700/50 rounded-xl hover:bg-rose-800/50 transition-all duration-300 font-medium">
                        <i class="fas fa-trash-alt mr-2"></i> Terminate
                    </a>
                </div>
            </div>
            
            <!-- Main Content Sections -->
            <div class="space-y-8">
                <!-- API Key and URL Section -->
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- URL Field -->
                    <div class="glass-card p-5 rounded-xl border border-slate-700/50">
                        <h3 class="text-sm font-semibold text-indigo-400 mb-3 flex items-center">
                            <i class="fas fa-globe mr-2"></i> Endpoint URL
                        </h3>
                        <div class="mt-1 flex items-center">
                            <div class="flex-1 break-all bg-slate-800/50 p-3 rounded-lg border border-slate-700/50">
                                <?php if ($credential['base_url']): ?>
                                    <code class="text-slate-300"><?php echo sanitizeInput($credential['base_url']); ?></code>
                                <?php else: ?>
                                    <span class="text-slate-500 italic">Not specified</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($credential['base_url']): ?>
                                <button onclick="copyToClipboard('<?php echo addslashes($credential['base_url']); ?>', this)" 
                                        class="ml-3 text-indigo-400 hover:text-indigo-300 transition-colors duration-300 tooltip">
                                    <i class="fas fa-copy"></i>
                                    <span class="tooltip-text">Copy URL</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- API Key Field -->
                    <div x-data="{ showKey: false }" class="glass-card p-5 rounded-xl border border-slate-700/50">
                        <h3 class="text-sm font-semibold text-indigo-400 mb-3 flex items-center">
                            <i class="fas fa-key mr-2"></i> API Key
                            <span class="ml-2 text-xs bg-indigo-900/50 text-indigo-300 px-2 py-0.5 rounded-full">Encrypted</span>
                        </h3>
                        <div class="mt-1 flex items-center">
                            <div class="flex-1 break-all bg-slate-800/50 p-3 rounded-lg border border-slate-700/50 font-mono">
                                <template x-if="!showKey">
                                    <div class="flex items-center">
                                        <span class="text-slate-400">••••••••••••••••••••••••••</span>
                                    </div>
                                </template>
                                <template x-if="showKey">
                                    <code class="text-slate-300"><?php echo htmlspecialchars($decrypted_api_key); ?></code>
                                </template>
                            </div>
                            <div class="ml-3 flex flex-col gap-3">
                                <button @click="showKey = !showKey" 
                                        class="text-indigo-400 hover:text-indigo-300 transition-colors duration-300 tooltip">
                                    <i x-show="!showKey" class="fas fa-eye"></i>
                                    <i x-show="showKey" class="fas fa-eye-slash"></i>
                                    <span class="tooltip-text" x-text="showKey ? 'Hide Key' : 'Show Key'"></span>
                                </button>
                                <button onclick="copyToClipboard('<?php echo addslashes($decrypted_api_key); ?>', this)" 
                                        class="text-indigo-400 hover:text-indigo-300 transition-colors duration-300 tooltip">
                                    <i class="fas fa-copy"></i>
                                    <span class="tooltip-text">Copy Key</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Details Section -->
                <div class="glass-card p-5 rounded-xl border border-slate-700/50">
                    <h3 class="text-sm font-semibold text-indigo-400 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Additional Details
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-x-8 gap-y-4">
                        <!-- Model Version -->
                        <div>
                            <h4 class="text-xs font-medium text-slate-400 mb-1">Model Version</h4>
                            <p class="text-slate-300">
                                <?php echo $credential['model_version'] ? sanitizeInput($credential['model_version']) : '<span class="text-slate-500 italic">Not specified</span>'; ?>
                            </p>
                        </div>
                        
                        <!-- Expiry Date -->
                        <div>
                            <h4 class="text-xs font-medium text-slate-400 mb-1">Expiry Date</h4>
                            <p class="text-slate-300 flex items-center">
                                <?php if ($credential['expiry_date']): ?>
                                    <?php if ($expiry_warning && $days_until_expiry <= 0): ?>
                                        <span class="text-rose-400"><?php echo formatDate($credential['expiry_date']); ?> (Expired)</span>
                                    <?php elseif ($expiry_warning): ?>
                                        <span class="text-amber-400"><?php echo formatDate($credential['expiry_date']); ?></span>
                                        <span class="ml-2 bg-amber-900/50 text-amber-300 text-xs px-2 py-0.5 rounded-full">
                                            Expires soon
                                        </span>
                                    <?php else: ?>
                                        <?php echo formatDate($credential['expiry_date']); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-500 italic">No expiration</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- Created Date -->
                        <div>
                            <h4 class="text-xs font-medium text-slate-400 mb-1">Created</h4>
                            <p class="text-slate-300">
                                <?php echo formatDate($credential['created_at']); ?>
                            </p>
                        </div>
                        
                        <!-- Last Updated -->
                        <div>
                            <h4 class="text-xs font-medium text-slate-400 mb-1">Last Updated</h4>
                            <p class="text-slate-300">
                                <?php echo $credential['updated_at'] ? formatDate($credential['updated_at']) : formatDate($credential['created_at']); ?>
                            </p>
                        </div>
                        
                        <?php if ($usage_stats): ?>
                        <!-- Usage Statistics -->
                        <div>
                            <h4 class="text-xs font-medium text-slate-400 mb-1">Access Count</h4>
                            <p class="text-slate-300">
                                <?php echo $usage_stats['access_count']; ?> times
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-medium text-slate-400 mb-1">Last Accessed</h4>
                            <p class="text-slate-300">
                                <?php echo $usage_stats['last_accessed'] ? date('M j, Y, g:i a', strtotime($usage_stats['last_accessed'])) : 'Never'; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tags Section -->
                <?php if ($credential['tags']): ?>
                <div class="glass-card p-5 rounded-xl border border-slate-700/50">
                    <h3 class="text-sm font-semibold text-indigo-400 mb-3 flex items-center">
                        <i class="fas fa-tags mr-2"></i> Tags
                    </h3>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <?php foreach (explode(',', $credential['tags']) as $tag): ?>
                            <?php $tag = trim($tag); ?>
                            <?php if (!empty($tag)): ?>
                                <span class="bg-indigo-900/50 text-indigo-300 text-xs font-medium px-3 py-1.5 rounded-full border border-indigo-700/30">
                                    <?php echo sanitizeInput($tag); ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Documentation URL Section -->
                <?php if ($credential['documentation_url']): ?>
                <div class="glass-card p-5 rounded-xl border border-slate-700/50">
                    <h3 class="text-sm font-semibold text-indigo-400 mb-3 flex items-center">
                        <i class="fas fa-book mr-2"></i> Documentation
                    </h3>
                    <div class="mt-1 flex items-center">
                        <div class="flex-1 break-all">
                            <a href="<?php echo $credential['documentation_url']; ?>" target="_blank" rel="noopener noreferrer" 
                               class="text-indigo-400 hover:text-indigo-300 transition-colors duration-300 flex items-center">
                                <?php echo sanitizeInput($credential['documentation_url']); ?>
                                <i class="fas fa-external-link-alt ml-2 text-xs"></i>
                            </a>
                        </div>
                        <button onclick="copyToClipboard('<?php echo addslashes($credential['documentation_url']); ?>', this)" 
                                class="ml-3 text-indigo-400 hover:text-indigo-300 transition-colors duration-300 tooltip">
                            <i class="fas fa-copy"></i>
                            <span class="tooltip-text">Copy URL</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Notes Section -->
                <?php if ($credential['notes']): ?>
                <div class="glass-card p-5 rounded-xl border border-slate-700/50">
                    <h3 class="text-sm font-semibold text-indigo-400 mb-3 flex items-center">
                        <i class="fas fa-sticky-note mr-2"></i> Notes
                    </h3>
                    <div class="mt-1 bg-slate-800/50 p-4 rounded-xl border border-slate-700/50">
                        <p class="text-slate-300 whitespace-pre-wrap"><?php echo sanitizeInput($credential['notes']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 pt-6 border-t border-slate-700/50">
                    <a href="dashboard.php" class="inline-flex items-center justify-center px-6 py-3 border border-slate-600/50 text-slate-300 rounded-xl hover:bg-slate-800/70 transition-all duration-300 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Command Center
                    </a>
                        
                        <a href="edit_credential.php?id=<?php echo $credential['id']; ?>" 
                           class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                            <i class="fas fa-edit mr-2"></i> Edit Node
                        </a>
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
    // Function to copy text to clipboard with visual feedback
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            // Visual feedback
            button.classList.add('copy-success');
            
            // Change icon temporarily
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check text-emerald-400"></i>';
            
            // Reset after animation
            setTimeout(function() {
                button.classList.remove('copy-success');
                button.innerHTML = originalIcon;
            }, 1500);
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            
            // Error feedback
            button.innerHTML = '<i class="fas fa-times text-rose-400"></i>';
            setTimeout(function() {
                button.innerHTML = '<i class="fas fa-copy"></i>';
            }, 1500);
        });
    }
    </script>
</body>
</html>