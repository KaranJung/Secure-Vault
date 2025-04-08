<?php
require_once 'config.php';
include 'header.php';
?>

<div class="max-w-4xl mx-auto my-12 bg-gray-800/80 backdrop-blur-md rounded-xl shadow-neon overflow-hidden border border-gray-700/50">
    <div class="bg-gradient-to-r from-futuristic-700 to-futuristic-900 p-6 text-white text-center border-b border-futuristic-500/30">
        <h1 class="text-3xl font-bold font-mono">TERMS OF SERVICE</h1>
        <p class="text-futuristic-300 mt-1 text-sm font-mono">// Last Updated: <?php echo date('F j, Y'); ?></p>
    </div>
    
    <div class="p-8 text-gray-300 space-y-6">
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">1. SYSTEM OVERVIEW</h2>
            <p>Welcome to SECURE_VAULT ("System", "Service", "Platform"), a secure credential management solution. By accessing or using the System, you ("User", "Operator") agree to be bound by these Terms of Service ("Terms").</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">2. ACCESS CREDENTIALS</h2>
            <p>2.1 Users must provide accurate identification data during registration.</p>
            <p>2.2 Credentials must be kept secure. The System is not liable for unauthorized access due to credential compromise.</p>
            <p>2.3 The System reserves the right to terminate suspicious accounts without notice.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">3. DATA PROTOCOLS</h2>
            <p>3.1 Users retain ownership of stored credentials but grant the System necessary access rights for operation.</p>
            <p>3.2 The System employs quantum-resistant encryption but cannot guarantee absolute security.</p>
            <p>3.3 Users are solely responsible for maintaining backups of critical credentials.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">4. PROHIBITED ACTIVITIES</h2>
            <p>4.1 Reverse engineering or attempting to compromise System security.</p>
            <p>4.2 Storing illegal or malicious credentials.</p>
            <p>4.3 Using the System to conduct unauthorized penetration testing.</p>
            <p>4.4 Any activity that violates applicable laws.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">5. SERVICE MODIFICATIONS</h2>
            <p>5.1 The System may update these Terms with 30 days notice.</p>
            <p>5.2 Core functionality changes will be announced through System channels.</p>
            <p>5.3 The System reserves the right to modify or discontinue service at any time.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">6. LIABILITY LIMITATIONS</h2>
            <p>6.1 The System is provided "as is" without warranties of any kind.</p>
            <p>6.2 In no event shall the System be liable for indirect damages.</p>
            <p>6.3 Maximum liability is limited to fees paid in the last 6 months.</p>
        </div>
        
        <div>
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">7. GOVERNANCE</h2>
            <p>7.1 These Terms are governed by the laws of the System's jurisdiction.</p>
            <p>7.2 Disputes will be resolved through binding arbitration.</p>
            <p>7.3 If any provision is found invalid, the remainder remains in effect.</p>
        </div>
        
        <div class="mt-8 p-4 bg-gray-700/30 rounded-lg border border-gray-600/50">
            <p class="text-center font-mono text-sm">BY USING THIS SYSTEM, YOU ACKNOWLEDGE THAT YOU HAVE READ, UNDERSTOOD, AND AGREE TO BE BOUND BY THESE TERMS.</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>