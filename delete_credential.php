<?php
require_once 'config.php';
requireLogin();

$user_id = getUserId();
$credential_id = $_GET['id'] ?? 0;

// Only process deletion if confirmation is received
if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    $stmt = $pdo->prepare("SELECT id FROM api_credentials WHERE id = ? AND user_id = ?");
    $stmt->execute([$credential_id, $user_id]);
    $credential = $stmt->fetch();

    if (!$credential) {
        redirect('dashboard.php');
    }

    $stmt = $pdo->prepare("DELETE FROM api_credentials WHERE id = ? AND user_id = ?");
    $stmt->execute([$credential_id, $user_id]);


    redirect('dashboard.php');
} else {
    // Get credential details for the confirmation page
    $stmt = $pdo->prepare("SELECT api_name FROM api_credentials WHERE id = ? AND user_id = ?");
    $stmt->execute([$credential_id, $user_id]);
    $credential = $stmt->fetch();

    if (!$credential) {
        redirect('dashboard.php');
    }

    include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminate Node | SECURE_VAULT</title>
    <link rel="icon" type="image/x-icon" href="vault.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        @keyframes warning-pulse {
            0%, 100% {
                box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
            }
            50% {
                box-shadow: 0 0 30px rgba(239, 68, 68, 0.7);
            }
        }
        
        .animate-warning {
            animation: warning-pulse 2s infinite;
        }
        
        .animate-typing::after {
            content: '|';
            animation: blink 1s step-end infinite;
        }
        
        @keyframes blink {
            from, to { opacity: 1; }
            50% { opacity: 0; }
        }
        
        .countdown-animation {
            transition: width 5s linear;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-12 flex-grow flex items-center justify-center">
        <div class="glass-card rounded-2xl overflow-hidden border border-red-700/50 max-w-lg w-full animate-warning">
            <div class="bg-gradient-to-r from-red-800 to-red-900 p-6 text-white text-center border-b border-red-700/50">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-red-900/70 rounded-full flex items-center justify-center">
                        <i class="fas fa-radiation text-red-400 text-3xl animate-pulse"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold font-mono">CRITICAL OPERATION</h1>
                <p class="text-red-300 mt-1 text-sm font-mono">NODE TERMINATION SEQUENCE</p>
            </div>
            
            <div class="p-8">
                <div class="bg-red-900/20 border border-red-700/30 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <div class="text-red-400 mr-3 mt-1">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-medium mb-2">WARNING: IRREVERSIBLE ACTION</h3>
                            <p class="text-red-200/80 text-sm">
                                You are about to permanently terminate node <span class="font-mono bg-red-900/40 px-2 py-0.5 rounded text-red-300"><?php echo htmlspecialchars($credential['api_name']); ?></span>. 
                                This operation cannot be undone and all associated data will be purged from the system.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-8">
                    <div class="font-mono text-sm text-slate-300 mb-2">SYSTEM VERIFICATION:</div>
                    <div class="bg-slate-800/50 rounded-lg p-3 border border-slate-700/50">
                        <div class="animate-typing text-sm font-mono text-red-300" id="typingText">
                            Preparing to terminate node...
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="font-mono text-sm text-slate-300 mb-2">AUTHORIZATION COUNTDOWN:</div>
                    <div class="h-2 bg-slate-800/70 rounded-full overflow-hidden">
                        <div id="countdownBar" class="h-full bg-red-600 countdown-animation" style="width: 100%;"></div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-slate-400 font-mono">
                        <span>ABORT WINDOW</span>
                        <span id="countdownTimer">5s</span>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <a href="dashboard.php" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white py-3 px-4 rounded-lg font-medium transition-all text-center">
                        ABORT SEQUENCE
                    </a>
                    <a href="delete_credential.php?id=<?php echo $credential_id; ?>&confirm=true" 
                       id="confirmButton"
                       class="flex-1 bg-red-700 hover:bg-red-600 text-white py-3 px-4 rounded-lg font-medium transition-all text-center opacity-50 pointer-events-none">
                        CONFIRM TERMINATION
                    </a>
                </div>
                
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center text-xs text-slate-500">
                        <i class="fas fa-shield-alt mr-2 text-red-400"></i>
                        <span>Security protocol: Requires explicit confirmation</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Typing animation
            const textElement = document.getElementById('typingText');
            const fullText = "Initiating node termination sequence... Preparing to delete all associated data... Verification required.";
            let charIndex = 0;
            
            function typeText() {
                if (charIndex < fullText.length) {
                    textElement.textContent = fullText.substring(0, charIndex + 1);
                    charIndex++;
                    setTimeout(typeText, 30);
                }
            }
            
            typeText();
            
            // Countdown timer
            const countdownBar = document.getElementById('countdownBar');
            const countdownTimer = document.getElementById('countdownTimer');
            const confirmButton = document.getElementById('confirmButton');
            let timeLeft = 5;
            
            // Start with full width
            countdownBar.style.width = '100%';
            
            // After a small delay, start the countdown animation
            setTimeout(() => {
                countdownBar.style.width = '0%';
                
                const interval = setInterval(() => {
                    timeLeft--;
                    countdownTimer.textContent = timeLeft + 's';
                    
                    if (timeLeft <= 0) {
                        clearInterval(interval);
                        confirmButton.classList.remove('opacity-50', 'pointer-events-none');
                        confirmButton.classList.add('animate-pulse');
                        countdownTimer.textContent = 'READY';
                    }
                }, 1000);
            }, 200);
        });
    </script>
</body>
</html>
<?php
}
?>
