<?php
require_once 'config.php';
require_once 'notification_function.php';
requireLogin();

$user_id = getUserId();

$stmt = $pdo->prepare("SELECT * FROM api_credentials WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$credentials = $stmt->fetchAll();

$totalCredentials = count($credentials);
$expiringSoon = 0;
$expired = 0;
$today = date('Y-m-d');

foreach ($credentials as $credential) {
    if ($credential['expiry_date']) {
        $expiryDate = new DateTime($credential['expiry_date']);
        $todayDate = new DateTime($today);
        $interval = $todayDate->diff($expiryDate);
        if ($interval->invert) $expired++;
        elseif ($interval->days <= 30) $expiringSoon++;
    }
}

// Get notifications for the user
$notifications = getUserNotifications($pdo, $user_id, 5);
$unreadCount = countUnreadNotifications($pdo, $user_id);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECURE_VAULT</title>
    <link rel="icon" type="image/x-icon" href="vault.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.min.js"></script>
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
        
        .glow-effect {
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
        }
        
        .cyber-text {
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            background-image: linear-gradient(90deg, #4d6bff, #0a2aff);
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
        
        /* Select dropdown arrow */
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238b5cf6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="flashMessage" class="fixed top-5 right-5 z-50 max-w-md glass-card rounded-xl border <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'border-green-500/50 bg-green-900/20' : 'border-red-500/50 bg-red-900/20'; ?> p-4 shadow-lg transform transition-all duration-500 translate-x-0">
            <div class="flex items-start">
                <div class="flex-shrink-0 pt-0.5">
                    <i class="fas <?php echo $_SESSION['flash_message']['type'] === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'; ?> text-lg"></i>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium text-white"><?php echo $_SESSION['flash_message']['message']; ?></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button onclick="document.getElementById('flashMessage').remove();" class="inline-flex text-gray-400 hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const flashMessage = document.getElementById('flashMessage');
                if (flashMessage) {
                    flashMessage.classList.add('opacity-0');
                    setTimeout(() => flashMessage.remove(), 500);
                }
            }, 5000);
        </script>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <!-- Enhanced Navigation Bar -->
    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-50 py-3">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="mr-3 text-indigo-500">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-500 to-indigo-600">VAULT</h1>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Notification Center -->
                <?php echo renderNotificationDropdown($pdo, $user_id); ?>
                
                <!-- User Profile Dropdown -->
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
        </div>
    </nav>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-white mb-2">Command Center</h2>
                <p class="text-slate-400 text-lg">Your gateway to API mastery</p>
            </div>
            <div class="mt-6 md:mt-0">
                <a href="add_credential.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                    <i class="fas fa-plus-circle mr-2"></i> Add API
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="glass-card rounded-2xl p-6 border border-slate-700/50 hover:border-indigo-500/50 transition-all duration-300 transform hover:translate-y-[-5px]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">Active Nodes</p>
                        <h3 class="text-4xl font-bold text-white mt-2"><?php echo $totalCredentials; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-900 p-4 rounded-xl shadow-lg">
                        <i class="fas fa-network-wired text-white text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-700/50">
                    <p class="text-slate-400 text-sm">
                        <i class="fas fa-arrow-circle-up text-emerald-400 mr-1"></i> 
                        Total operational connections
                    </p>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-6 border border-slate-700/50 hover:border-amber-500/50 transition-all duration-300 transform hover:translate-y-[-5px]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">Temporal Alerts</p>
                        <h3 class="text-4xl font-bold text-white mt-2"><?php echo $expiringSoon; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-700 p-4 rounded-xl shadow-lg">
                        <i class="fas fa-hourglass-half text-white text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-700/50">
                    <p class="text-slate-400 text-sm">
                        <i class="fas fa-clock text-amber-400 mr-1"></i> 
                        Expiring within 30 days
                    </p>
                </div>
            </div>
            
            <div class="glass-card rounded-2xl p-6 border border-slate-700/50 hover:border-rose-500/50 transition-all duration-300 transform hover:translate-y-[-5px]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">Expired Links</p>
                        <h3 class="text-4xl font-bold text-white mt-2"><?php echo $expired; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-rose-600 to-rose-800 p-4 rounded-xl shadow-lg">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-700/50">
                    <p class="text-slate-400 text-sm">
                        <i class="fas fa-ban text-rose-400 mr-1"></i> 
                        Requires immediate attention
                    </p>
                </div>
            </div>
            
            <!-- New Notifications Card -->
            <div class="glass-card rounded-2xl p-6 border border-slate-700/50 hover:border-indigo-500/50 transition-all duration-300 transform hover:translate-y-[-5px]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">Notifications</p>
                        <h3 class="text-4xl font-bold text-white mt-2"><?php echo $unreadCount; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-900 p-4 rounded-xl shadow-lg">
                        <i class="fas fa-bell text-white text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-700/50">
                    <a href="notifications.php" class="text-slate-400 text-sm hover:text-indigo-400 transition-colors flex items-center">
                        <i class="fas fa-arrow-circle-right text-indigo-400 mr-1"></i> 
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <div class="glass-card rounded-2xl p-6 mb-10 border border-slate-700/50">
            <h3 class="text-lg font-semibold text-white mb-4">Advanced Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-indigo-400"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search by name..." 
                           class="pl-11 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 placeholder-slate-500">
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-tag text-indigo-400"></i>
                    </div>
                    <select id="tagFilter" class="pl-11 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 appearance-none">
                        <option value="">All Signatures</option>
                        <?php
                        $allTags = [];
                        foreach ($credentials as $credential) {
                            if ($credential['tags']) {
                                foreach (explode(',', $credential['tags']) as $tag) {
                                    $tag = trim($tag);
                                    if ($tag && !in_array($tag, $allTags)) $allTags[] = $tag;
                                }
                            }
                        }
                        sort($allTags);
                        foreach ($allTags as $tag) {
                            echo '<option value="' . htmlspecialchars($tag) . '">' . htmlspecialchars($tag) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-calendar text-indigo-400"></i>
                    </div>
                    <select id="expiryFilter" class="pl-11 w-full px-4 py-3 bg-slate-800/50 text-white border border-slate-600/50 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 appearance-none">
                        <option value="">All Phases</option>
                        <option value="expired">Terminated</option>
                        <option value="expiring">Approaching</option>
                        <option value="active">Operational</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Credentials Table -->
        <div class="glass-card rounded-2xl overflow-hidden border border-slate-700/50">
            <?php if (empty($credentials)): ?>
                <div class="border-2 border-dashed border-slate-600/50 rounded-xl p-12 text-center m-6">
                    <div class="text-indigo-400 mb-6 animate-pulse-slow">
                        <i class="fas fa-satellite-dish text-6xl"></i>
                    </div>
                    <h2 class="text-2xl font-semibold text-white mb-3">No Data Signals Detected</h2>
                    <p class="text-slate-400 mb-6">Initialize your first API node to begin.</p>
                    <a href="add_credential.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                        <i class="fas fa-plus-circle mr-2"></i> Create First Node
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-700/50">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Node ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Endpoint</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Protocol</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Signatures</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Controls</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/30" id="credentialsTable">
                            <?php foreach ($credentials as $credential): 
                                $expiryStatus = '';
                                $statusColor = 'bg-emerald-900/70 text-emerald-400';
                                $statusIcon = 'fas fa-check-circle';
                                if ($credential['expiry_date']) {
                                    $expiryDate = new DateTime($credential['expiry_date']);
                                    $todayDate = new DateTime($today);
                                    $interval = $todayDate->diff($expiryDate);
                                    if ($interval->invert) {
                                        $expiryStatus = 'Terminated';
                                        $statusColor = 'bg-rose-900/70 text-rose-400';
                                        $statusIcon = 'fas fa-times-circle';
                                    } elseif ($interval->days <= 30) {
                                        $expiryStatus = 'Expires in ' . $interval->days . ' days';
                                        $statusColor = 'bg-amber-900/70 text-amber-400';
                                        $statusIcon = 'fas fa-exclamation-circle';
                                    } else {
                                        $expiryStatus = 'Operational';
                                    }
                                } else {
                                    $expiryStatus = 'Infinite';
                                }
                            ?>
                            <tr class="hover:bg-slate-800/50 transition-all duration-300 credential-row"
                                data-name="<?php echo strtolower(sanitizeInput($credential['api_name'])); ?>"
                                data-tags="<?php echo strtolower(sanitizeInput($credential['tags'])); ?>"
                                data-status="<?php echo strtolower(str_replace(' ', '-', $expiryStatus)); ?>">
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl flex items-center justify-center shadow-lg">
                                            <i class="fas fa-satellite-dish text-white text-lg"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-base font-medium text-white truncate max-w-xs"><?php echo sanitizeInput($credential['api_name']); ?></div>
                                            <div class="text-sm text-slate-400 flex items-center mt-1">
                                                <i class="fas fa-calendar-alt mr-1 text-indigo-400"></i>
                                                <?php echo formatDate($credential['created_at'], 'M j, Y'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="text-sm text-white">
                                        <?php if ($credential['base_url']): ?>
                                            <a href="<?php echo $credential['base_url']; ?>" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition-colors duration-300 truncate max-w-xs block flex items-center">
                                                <i class="fas fa-globe mr-2"></i>
                                                <?php echo parse_url($credential['base_url'], PHP_URL_HOST); ?>
                                                <i class="fas fa-external-link-alt ml-2 text-xs"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-500 flex items-center">
                                                <i class="fas fa-minus-circle mr-2"></i> Not specified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <?php if ($credential['model_version']): ?>
                                        <div class="text-sm text-white bg-slate-800/70 px-3 py-1 rounded-lg inline-flex items-center">
                                            <i class="fas fa-code-branch mr-2 text-indigo-400"></i>
                                            <?php echo sanitizeInput($credential['model_version']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-500 flex items-center">
                                            <i class="fas fa-minus-circle mr-2"></i> N/A
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <span class="px-3 py-1.5 inline-flex items-center text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                        <i class="<?php echo $statusIcon; ?> mr-1.5"></i> <?php echo $expiryStatus; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($credential['tags']): ?>
                                            <?php foreach (explode(',', $credential['tags']) as $tag): ?>
                                                <span class="bg-indigo-900/50 text-indigo-300 text-xs font-medium px-3 py-1 rounded-full border border-indigo-700/50"><?php echo sanitizeInput(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-xs flex items-center">
                                                <i class="fas fa-tag mr-1 opacity-50"></i> No signatures
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-3">
                                        <a href="view_credential.php?id=<?php echo $credential['id']; ?>" class="bg-slate-800/70 text-indigo-400 hover:text-indigo-300 transition-colors duration-300 p-2 rounded-lg" title="Inspect">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_credential.php?id=<?php echo $credential['id']; ?>" class="bg-slate-800/70 text-amber-400 hover:text-amber-300 transition-colors duration-300 p-2 rounded-lg" title="Modify">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_credential.php?id=<?php echo $credential['id']; ?>" 
                                           class="bg-slate-800/70 text-rose-400 hover:text-rose-300 transition-colors duration-300 p-2 rounded-lg" title="Terminate">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="mt-10 text-center text-slate-500 text-sm">
            <p>SECURE_VAULT • <?php echo date('Y'); ?> • Quantum-Secured API Management</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tagFilter = document.getElementById('tagFilter');
        const expiryFilter = document.getElementById('expiryFilter');
        const credentialRows = document.querySelectorAll('.credential-row');
        
        function filterCredentials() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedTag = tagFilter.value.toLowerCase();
            const selectedStatus = expiryFilter.value.toLowerCase();
            
            credentialRows.forEach(row => {
                const nameMatch = row.dataset.name.includes(searchTerm);
                const tagsMatch = selectedTag === '' || row.dataset.tags.includes(selectedTag);
                const statusMatch = selectedStatus === '' || 
                                  (selectedStatus === 'expired' && row.dataset.status.includes('terminated')) ||
                                  (selectedStatus === 'expiring' && row.dataset.status.includes('expires-in')) ||
                                  (selectedStatus === 'active' && (row.dataset.status.includes('operational') || row.dataset.status.includes('infinite')));
                
                if (nameMatch && tagsMatch && statusMatch) {
                    row.classList.remove('hidden');
                    row.style.display = '';
                } else {
                    row.classList.add('hidden');
                    row.style.display = 'none';
                }
            });
            
            // Show empty state if no results
            const visibleRows = document.querySelectorAll('.credential-row:not(.hidden)').length;
            const tableBody = document.getElementById('credentialsTable');
            const emptyState = document.getElementById('emptySearchState');
            
            if (visibleRows === 0 && !emptyState && tableBody) {
                const emptyStateHtml = `
                    <tr id="emptySearchState">
                        <td colspan="6" class="px-6 py-10 text-center">
                            <div class="text-slate-400 mb-3">
                                <i class="fas fa-search text-3xl"></i>
                            </div>
                            <p class="text-white font-medium mb-1">No matching nodes found</p>
                            <p class="text-slate-400 text-sm">Try adjusting your search criteria</p>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', emptyStateHtml);
            } else if (visibleRows > 0 && emptyState) {
                emptyState.remove();
            }
        }
        
        searchInput.addEventListener('input', filterCredentials);
        tagFilter.addEventListener('change', filterCredentials);
        expiryFilter.addEventListener('change', filterCredentials);
        
        // Enhanced tooltips
        tippy('[title]', {
            content(reference) { return reference.getAttribute('title'); },
            animation: 'shift-away',
            theme: 'dark',
            placement: 'top',
            arrow: true,
            inertia: true
        });
    });
    </script>
</body>
</html>
