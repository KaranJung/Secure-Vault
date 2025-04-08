<?php
require_once 'config.php';
requireLogin();

$user_id = getUserId();
$errors = [];
$success = false;

// Get common API models for suggestions
$commonModels = [
    'gpt-3.5-turbo',
    'gpt-4',
    'gpt-4o',
    'claude-3-opus',
    'claude-3-sonnet',
    'llama-3',
    'gemini-pro',
    'mistral-large'
];

// Get common tags from existing credentials
$commonTags = [];
try {
    $stmt = $pdo->prepare("SELECT tags FROM api_credentials WHERE user_id = ? AND tags IS NOT NULL");
    $stmt->execute([$user_id]);
    $tagResults = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tagResults as $tagString) {
        $tags = explode(',', $tagString);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag) && !in_array($tag, $commonTags)) {
                $commonTags[] = $tag;
            }
        }
    }
    
    // Sort and limit to 10 most common tags
    $commonTags = array_slice($commonTags, 0, 10);
    sort($commonTags);
} catch (Exception $e) {
    // Silently fail - suggestions are not critical
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_name = trim($_POST['api_name'] ?? '');
    $base_url = trim($_POST['base_url'] ?? '');
    $api_key = $_POST['api_key'] ?? '';
    $model_version = trim($_POST['model_version'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $documentation_url = trim($_POST['documentation_url'] ?? '');
    
    if (empty($api_name)) $errors['api_name'] = 'Node ID is required';
    if (empty($api_key)) $errors['api_key'] = 'Access Code is required';
    if (!empty($documentation_url) && !filter_var($documentation_url, FILTER_VALIDATE_URL)) 
        $errors['documentation_url'] = 'Enter a valid Datastream URL';
    
    if (empty($errors)) {
        try {
            $encrypted_api_key = encrypt($api_key, $encryption_key);
            $stmt = $pdo->prepare("INSERT INTO api_credentials (user_id, api_name, base_url, api_key, model_version, tags, notes, expiry_date, documentation_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$user_id, $api_name, $base_url ?: null, $encrypted_api_key, $model_version ?: null, $tags ?: null, $notes ?: null, $expiry_date ?: null, $documentation_url ?: null])) {
                $success = true;
                // Redirect after a short delay to show success message
                header("refresh:1;url=dashboard.php");
            } else {
                $errors['general'] = 'Failed to deploy node. Retry initialization.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'System error: ' . $e->getMessage();
        }
    }
}

// Calculate default expiry date (30 days from now)
$defaultExpiryDate = date('Y-m-d', strtotime('+30 days'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy New Node | SECURE_VAULT</title>
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
        
        /* Input focus effect */
        input:focus, textarea:focus, select:focus {
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
            outline: none;
        }
        
        /* Floating label effect */
        .floating-label {
            position: relative;
        }
        
        .floating-label input:focus + label,
        .floating-label input:not(:placeholder-shown) + label,
        .floating-label textarea:focus + label,
        .floating-label textarea:not(:placeholder-shown) + label {
            transform: translateY(-1.5rem) scale(0.85);
            color: #818cf8;
        }
        
        .floating-label label {
            position: absolute;
            left: 1rem;
            top: 0.75rem;
            padding: 0 0.25rem;
            color: #94a3b8;
            pointer-events: none;
            transition: 0.2s ease all;
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
                <span class="text-slate-300">Deploy New Node</span>
            </div>
        </div>
        
        <!-- Main Form Card -->
        <div class="glass-card rounded-2xl p-8 max-w-4xl mx-auto border border-slate-700/50">
            <div class="flex items-center mb-8">
                <div class="mr-4 bg-gradient-to-br from-indigo-600 to-purple-700 p-3 rounded-xl shadow-lg">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-500 to-indigo-600">Deploy New API</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-emerald-900/50 text-emerald-400 p-4 rounded-xl mb-6 border border-emerald-700/50 flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium">Node successfully deployed!</p>
                        <p class="text-sm">Redirecting to Command Center...</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="bg-rose-900/50 text-rose-400 p-4 rounded-xl mb-6 border border-rose-700/50 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium">Deployment Failed</p>
                        <p class="text-sm"><?php echo $errors['general']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-8" x-data="{ showApiKey: false }">
                <!-- Basic Information Section -->
                <div>
                    <h2 class="text-lg font-semibold text-indigo-400 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Basic Information
                    </h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="api_name" class="block text-sm font-medium text-slate-300 mb-2">
                                Node Name <span class="text-rose-400">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-signature text-indigo-400"></i>
                                </div>
                                <input type="text" id="api_name" name="api_name" value="<?php echo htmlspecialchars($_POST['api_name'] ?? ''); ?>" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                       placeholder="e.g., QuantumAPI">
                            </div>
                            <?php if (!empty($errors['api_name'])): ?>
                                <p class="text-rose-400 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <?php echo $errors['api_name']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="base_url" class="block text-sm font-medium text-slate-300 mb-2">
                                Endpoint URL
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-globe text-indigo-400"></i>
                                </div>
                                <input type="text" id="base_url" name="base_url" value="<?php echo htmlspecialchars($_POST['base_url'] ?? ''); ?>" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                       placeholder="https://api.example.com">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Section -->
                <div>
                    <h2 class="text-lg font-semibold text-indigo-400 mb-4 flex items-center">
                        <i class="fas fa-lock mr-2"></i> Security Credentials
                    </h2>
                    
                    <div>
                        <label for="api_key" class="block text-sm font-medium text-slate-300 mb-2">
                            API Key <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-indigo-400"></i>
                            </div>
                            <input :type="showApiKey ? 'text' : 'password'" id="api_key" name="api_key" 
                                   class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter your API key">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" @click="showApiKey = !showApiKey" class="text-indigo-400 hover:text-indigo-300 focus:outline-none">
                                    <i class="fas" :class="showApiKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-slate-500 text-sm mt-2 flex items-center">
                            <i class="fas fa-shield-alt mr-1 text-indigo-400"></i>
                            Secured with Quantum Encryption (AES-256)
                        </p>
                        <?php if (!empty($errors['api_key'])): ?>
                            <p class="text-rose-400 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?php echo $errors['api_key']; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Additional Details Section -->
                <div>
                    <h2 class="text-lg font-semibold text-indigo-400 mb-4 flex items-center">
                        <i class="fas fa-cogs mr-2"></i> Additional Details
                    </h2>
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="model_version" class="block text-sm font-medium text-slate-300 mb-2">
                                Model Version
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-code-branch text-indigo-400"></i>
                                </div>
                                <input type="text" id="model_version" name="model_version" value="<?php echo htmlspecialchars($_POST['model_version'] ?? ''); ?>" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                       placeholder="e.g., gpt-4, claude-3, etc."
                                       list="model-suggestions">
                                <datalist id="model-suggestions">
                                    <?php foreach ($commonModels as $model): ?>
                                        <option value="<?php echo htmlspecialchars($model); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div>
                            <label for="expiry_date" class="block text-sm font-medium text-slate-300 mb-2">
                                Expiry Date
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar-alt text-indigo-400"></i>
                                </div>
                                <input type="date" id="expiry_date" name="expiry_date" 
                                       value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? $defaultExpiryDate); ?>" 
                                       class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                            </div>
                            <p class="text-slate-500 text-sm mt-2">Leave blank for non-expiring credentials</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="tags" class="block text-sm font-medium text-slate-300 mb-2">
                            Tags
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-tags text-indigo-400"></i>
                            </div>
                            <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" 
                                   class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                   placeholder="e.g., ai, quantum, gpt-4, production">
                        </div>
                        <p class="text-slate-500 text-sm mt-2">Separate tags with commas</p>
                        
                        <?php if (!empty($commonTags)): ?>
                        <div class="mt-3">
                            <p class="text-sm text-slate-400 mb-2">Common tags:</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($commonTags as $tag): ?>
                                    <button type="button" onclick="addTag('<?php echo htmlspecialchars($tag); ?>')" 
                                            class="bg-slate-800/70 text-indigo-300 text-xs font-medium px-3 py-1.5 rounded-full border border-indigo-700/30 hover:bg-indigo-900/50 hover:border-indigo-500/50 transition-all">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-6">
                        <label for="documentation_url" class="block text-sm font-medium text-slate-300 mb-2">
                            Documentation URL
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-book text-indigo-400"></i>
                            </div>
                            <input type="url" id="documentation_url" name="documentation_url" value="<?php echo htmlspecialchars($_POST['documentation_url'] ?? ''); ?>" 
                                   class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300"
                                   placeholder="https://docs.example.com">
                        </div>
                        <?php if (!empty($errors['documentation_url'])): ?>
                            <p class="text-rose-400 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <?php echo $errors['documentation_url']; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-300 mb-2">
                            Notes
                        </label>
                        <div class="relative">
                            <div class="absolute top-3 left-3 pointer-events-none">
                                <i class="fas fa-sticky-note text-indigo-400"></i>
                            </div>
                            <textarea id="notes" name="notes" rows="4" 
                                      class="pl-10 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 resize-y"
                                      placeholder="Additional information about this API..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 pt-4 border-t border-slate-700/50">
                    <a href="dashboard.php" class="inline-flex items-center justify-center px-6 py-3 border border-slate-600/50 text-slate-300 rounded-xl hover:bg-slate-800/70 transition-all duration-300 font-medium">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                        <i class="fas fa-rocket mr-2"></i> Deploy API
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-10 mb-6 text-center text-slate-500 text-sm">
        <p>SECURE_VAULT • <?php echo date('Y'); ?> • Quantum-Secured API Management</p>
    </div>

    <script>
    // Function to add a tag to the tags input
    function addTag(tag) {
        const tagsInput = document.getElementById('tags');
        const currentTags = tagsInput.value.split(',').map(t => t.trim()).filter(t => t !== '');
        
        // Only add the tag if it's not already there
        if (!currentTags.includes(tag)) {
            if (currentTags.length > 0 && currentTags[0] !== '') {
                tagsInput.value = currentTags.join(', ') + ', ' + tag;
            } else {
                tagsInput.value = tag;
            }
        }
        
        // Focus the input
        tagsInput.focus();
    }
    
    // Set minimum date for expiry date picker to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('expiry_date').min = today;
    });
    </script>
</body>
</html>