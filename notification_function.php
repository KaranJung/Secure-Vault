<?php
/**
 * API Notification System
 * Handles notifications for expiring and expired API credentials
 */

/**
 * Get notifications for a specific user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $limit Maximum number of notifications to return
 * @return array Array of notification objects
 */
function getUserNotifications($pdo, $user_id, $limit = 10) {
    // First check if we have a read_notifications table
    ensureReadNotificationsTable($pdo);
    
    // Get expiring credentials (within 30 days)
    $expiringStmt = $pdo->prepare("
        SELECT 
            ac.id, 
            ac.api_name, 
            ac.expiry_date, 
            'expiring' as notification_type,
            DATEDIFF(ac.expiry_date, CURDATE()) as days_remaining,
            CASE WHEN rn.credential_id IS NOT NULL THEN 1 ELSE 0 END as is_read
        FROM api_credentials ac
        LEFT JOIN read_notifications rn ON ac.id = rn.credential_id AND rn.user_id = ? AND rn.notification_type = 'expiring'
        WHERE ac.user_id = ? 
        AND ac.expiry_date IS NOT NULL
        AND ac.expiry_date > CURDATE() 
        AND DATEDIFF(ac.expiry_date, CURDATE()) <= 30
        ORDER BY ac.expiry_date ASC
    ");
    $expiringStmt->execute([$user_id, $user_id]);
    $expiringCredentials = $expiringStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get expired credentials
    $expiredStmt = $pdo->prepare("
        SELECT 
            ac.id, 
            ac.api_name, 
            ac.expiry_date, 
            'expired' as notification_type,
            DATEDIFF(CURDATE(), ac.expiry_date) as days_expired,
            CASE WHEN rn.credential_id IS NOT NULL THEN 1 ELSE 0 END as is_read
        FROM api_credentials ac
        LEFT JOIN read_notifications rn ON ac.id = rn.credential_id AND rn.user_id = ? AND rn.notification_type = 'expired'
        WHERE ac.user_id = ? 
        AND ac.expiry_date IS NOT NULL
        AND ac.expiry_date < CURDATE() 
        ORDER BY ac.expiry_date DESC
    ");
    $expiredStmt->execute([$user_id, $user_id]);
    $expiredCredentials = $expiredStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and format notifications
    $notifications = [];
    
    foreach ($expiringCredentials as $credential) {
        $notifications[] = [
            'id' => 'exp_' . $credential['id'],
            'title' => 'API Expiring Soon',
            'message' => $credential['api_name'] . ' will expire in ' . $credential['days_remaining'] . ' days',
            'type' => 'warning',
            'icon' => 'fa-hourglass-half',
            'color' => 'amber',
            'credential_id' => $credential['id'],
            'date' => $credential['expiry_date'],
            'read' => $credential['is_read'] == 1,
            'notification_type' => 'expiring',
            'action_url' => 'edit_credential.php?id=' . $credential['id']
        ];
    }
    
    foreach ($expiredCredentials as $credential) {
        $notifications[] = [
            'id' => 'exp_' . $credential['id'],
            'title' => 'API Expired',
            'message' => $credential['api_name'] . ' expired ' . $credential['days_expired'] . ' days ago',
            'type' => 'danger',
            'icon' => 'fa-exclamation-circle',
            'color' => 'rose',
            'credential_id' => $credential['id'],
            'date' => $credential['expiry_date'],
            'read' => $credential['is_read'] == 1,
            'notification_type' => 'expired',
            'action_url' => 'edit_credential.php?id=' . $credential['id']
        ];
    }
    
    // Sort by date (most recent first) and limit
    usort($notifications, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return array_slice($notifications, 0, $limit);
}

/**
 * Count unread notifications for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function countUnreadNotifications($pdo, $user_id) {
    ensureReadNotificationsTable($pdo);
    
    // Count expiring credentials that haven't been read
    $expiringStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM api_credentials ac
        LEFT JOIN read_notifications rn ON ac.id = rn.credential_id AND rn.user_id = ? AND rn.notification_type = 'expiring'
        WHERE ac.user_id = ? 
        AND ac.expiry_date IS NOT NULL
        AND ac.expiry_date > CURDATE() 
        AND DATEDIFF(ac.expiry_date, CURDATE()) <= 30
        AND rn.credential_id IS NULL
    ");
    $expiringStmt->execute([$user_id, $user_id]);
    $expiringCount = $expiringStmt->fetchColumn();
    
    // Count expired credentials that haven't been read
    $expiredStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM api_credentials ac
        LEFT JOIN read_notifications rn ON ac.id = rn.credential_id AND rn.user_id = ? AND rn.notification_type = 'expired'
        WHERE ac.user_id = ? 
        AND ac.expiry_date IS NOT NULL
        AND ac.expiry_date < CURDATE()
        AND rn.credential_id IS NULL
    ");
    $expiredStmt->execute([$user_id, $user_id]);
    $expiredCount = $expiredStmt->fetchColumn();
    
    return $expiringCount + $expiredCount;
}

/**
 * Ensure the read_notifications table exists
 * 
 * @param PDO $pdo Database connection
 */
function ensureReadNotificationsTable($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS read_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        credential_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_notification (user_id, credential_id, notification_type)
    )";
    
    $pdo->exec($sql);
}

/**
 * Mark all notifications as read for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 */
function markAllNotificationsAsRead($pdo, $user_id) {
    ensureReadNotificationsTable($pdo);
    
    // Get all expiring credentials
    $expiringStmt = $pdo->prepare("
        SELECT id FROM api_credentials
        WHERE user_id = ? 
        AND expiry_date IS NOT NULL
        AND expiry_date > CURDATE() 
        AND DATEDIFF(expiry_date, CURDATE()) <= 30
    ");
    $expiringStmt->execute([$user_id]);
    $expiringCredentials = $expiringStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all expired credentials
    $expiredStmt = $pdo->prepare("
        SELECT id FROM api_credentials
        WHERE user_id = ? 
        AND expiry_date IS NOT NULL
        AND expiry_date < CURDATE()
    ");
    $expiredStmt->execute([$user_id]);
    $expiredCredentials = $expiredStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Mark all as read by inserting into read_notifications
    $insertStmt = $pdo->prepare("
        INSERT IGNORE INTO read_notifications (user_id, credential_id, notification_type)
        VALUES (?, ?, ?)
    ");
    
    // Mark expiring credentials as read
    foreach ($expiringCredentials as $credentialId) {
        $insertStmt->execute([$user_id, $credentialId, 'expiring']);
    }
    
    // Mark expired credentials as read
    foreach ($expiredCredentials as $credentialId) {
        $insertStmt->execute([$user_id, $credentialId, 'expired']);
    }
}

/**
 * Mark a specific notification as read
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $credential_id Credential ID
 * @param string $notification_type Type of notification ('expiring' or 'expired')
 */
function markNotificationAsRead($pdo, $user_id, $credential_id, $notification_type) {
    ensureReadNotificationsTable($pdo);
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO read_notifications (user_id, credential_id, notification_type)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $credential_id, $notification_type]);
}

/**
 * Render notification dropdown for the navbar
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return string HTML for the notification dropdown
 */
function renderNotificationDropdown($pdo, $user_id) {
    $notifications = getUserNotifications($pdo, $user_id, 5);
    $unreadCount = countUnreadNotifications($pdo, $user_id);
    
    $html = '<div x-data="{ open: false }" class="relative">';
    
    // Notification button with counter
    $html .= '
        <button @click="open = !open" class="relative flex items-center justify-center w-10 h-10 text-slate-300 hover:text-indigo-400 transition-all duration-300 bg-slate-800/50 rounded-xl border border-slate-700/50">
            <i class="fas fa-bell"></i>
            ' . ($unreadCount > 0 ? '<span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-xs font-medium text-white">' . $unreadCount . '</span>' : '') . '
        </button>';
    
    // Dropdown content
    $html .= '
        <div x-show="open" @click.away="open = false" x-cloak 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 scale-95" 
             x-transition:enter-end="opacity-100 scale-100" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 scale-100" 
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute right-0 mt-3 w-80 glass-card rounded-xl shadow-lg py-2 z-50">
            <div class="px-4 py-3 border-b border-slate-700/50">
                <h3 class="text-sm font-semibold text-white">Notifications</h3>
                <p class="text-xs text-slate-400 mt-1">Stay updated on your API status</p>
            </div>';
    
    // Notification items
    if (count($notifications) > 0) {
        $html .= '<div class="max-h-[60vh] overflow-y-auto">';
        foreach ($notifications as $notification) {
            // Add read/unread styling
            $readClass = $notification['read'] ? 'opacity-70' : '';
            $readIndicator = $notification['read'] ? '' : '<span class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1 h-8 bg-indigo-500 rounded-r-full"></span>';
            
            $html .= '
                <a href="' . $notification['action_url'] . '" class="block px-4 py-3 hover:bg-slate-700/50 transition-colors duration-200 border-b border-slate-700/20 last:border-0 relative ' . $readClass . '" 
                   onclick="markAsRead(event, ' . $user_id . ', ' . $notification['credential_id'] . ', \'' . $notification['notification_type'] . '\')">
                    ' . $readIndicator . '
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mr-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-' . $notification['color'] . '-900/70 text-' . $notification['color'] . '-400">
                                <i class="fas ' . $notification['icon'] . ' text-sm"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">' . $notification['title'] . '</p>
                            <p class="text-xs text-slate-400 mt-1 truncate">' . $notification['message'] . '</p>
                        </div>
                    </div>
                </a>';
        }
        $html .= '</div>';
        
        // Add JavaScript for marking individual notifications as read
        $html .= '
        <script>
        function markAsRead(event, userId, credentialId, notificationType) {
            // Create a hidden form and submit it
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "mark_notification_read.php";
            
            const userIdInput = document.createElement("input");
            userIdInput.type = "hidden";
            userIdInput.name = "user_id";
            userIdInput.value = userId;
            form.appendChild(userIdInput);
            
            const credentialIdInput = document.createElement("input");
            credentialIdInput.type = "hidden";
            credentialIdInput.name = "credential_id";
            credentialIdInput.value = credentialId;
            form.appendChild(credentialIdInput);
            
            const typeInput = document.createElement("input");
            typeInput.type = "hidden";
            typeInput.name = "notification_type";
            typeInput.value = notificationType;
            form.appendChild(typeInput);
            
            document.body.appendChild(form);
            form.submit();
            
            // Prevent the default link behavior
            event.preventDefault();
        }
        </script>';
    } else {
        $html .= '
            <div class="px-4 py-6 text-center">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-800/70 text-slate-400 mb-3">
                    <i class="fas fa-check text-lg"></i>
                </div>
                <p class="text-sm font-medium text-white">All caught up!</p>
                <p class="text-xs text-slate-400 mt-1">No pending notifications</p>
            </div>';
    }
    
    // View all link
    $html .= '
            <div class="px-4 py-2 border-t border-slate-700/50 text-center">
                <a href="notifications.php" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                    View all notifications
                </a>
            </div>
        </div>
    </div>';
    
    return $html;
}
?>

