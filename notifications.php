<?php
require_once 'config.php';
require_once 'notification_function.php';
requireLogin();

$user_id = getUserId();



// Get all notifications for the user
$notifications = getUserNotifications($pdo, $user_id, 50); // Get up to 50 notifications
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SECURE_VAULT</title>
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

    <!-- Navigation Bar -->
    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-50 py-3">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="mr-3 text-indigo-500">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-500 to-indigo-600">VAULT</h1>
            </div>
            <div class="flex items-center space-x-4">
                <?php echo renderNotificationDropdown($pdo, $user_id); ?>
                
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
                <h2 class="text-2xl md:text-3xl font-bold text-white mb-2">Notifications</h2>
                <p class="text-slate-400 text-lg">Stay updated on your API status</p>
            </div>
            <div class="mt-6 md:mt-0 flex space-x-4">
                <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-slate-800/70 text-white rounded-xl hover:bg-slate-700/70 transition-all duration-300 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
                <form method="post" action="">
                    <button type="submit" name="" class="inline-flex items-center px-4 py-2 bg-indigo-600/80 text-white rounded-xl hover:bg-indigo-700/80 transition-all duration-300 font-medium">
                        <i class="fas fa-check-double mr-2"></i> Mark All as Read
                    </button>
                </form>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="glass-card rounded-2xl overflow-hidden border border-slate-700/50">
            <?php if (empty($notifications)): ?>
                <div class="border-2 border-dashed border-slate-600/50 rounded-xl p-12 text-center m-6">
                    <div class="text-indigo-400 mb-6">
                        <i class="fas fa-bell-slash text-6xl"></i>
                    </div>
                    <h2 class="text-2xl font-semibold text-white mb-3">No Notifications</h2>
                    <p class="text-slate-400 mb-6">You're all caught up! No pending notifications.</p>
                    <a href="dashboard.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-700 text-white rounded-xl hover:from-indigo-700 hover:to-purple-800 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:translate-y-[-2px]">
                        <i class="fas fa-home mr-2"></i> Return to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-700/30">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="p-6 hover:bg-slate-800/30 transition-colors duration-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-<?php echo $notification['color']; ?>-900/70 text-<?php echo $notification['color']; ?>-400">
                                        <i class="fas <?php echo $notification['icon']; ?> text-lg"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between">
                                        <h3 class="text-lg font-semibold text-white"><?php echo $notification['title']; ?></h3>
                                        <span class="text-xs text-slate-400"><?php echo date('M j, Y', strtotime($notification['date'])); ?></span>
                                    </div>
                                    <p class="mt-1 text-slate-300"><?php echo $notification['message']; ?></p>
                                    <div class="mt-3">
                                        <a href="<?php echo $notification['action_url']; ?>" class="inline-flex items-center text-sm font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                                            <span>View Details</span>
                                            <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    
    </div>
</body>
</html>

