<?php
require_once 'config.php';
include 'header.php';
?>

<div class="max-w-4xl mx-auto my-12 bg-gray-800/80 backdrop-blur-md rounded-xl shadow-neon overflow-hidden border border-gray-700/50">
    <div class="bg-gradient-to-r from-futuristic-700 to-futuristic-900 p-6 text-white text-center border-b border-futuristic-500/30">
        <h1 class="text-3xl font-bold font-mono">PRIVACY PROTOCOLS</h1>
        <p class="text-futuristic-300 mt-1 text-sm font-mono">// Last Updated: <?php echo date('F j, Y'); ?></p>
    </div>
    
    <div class="p-8 text-gray-300 space-y-6">
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">1. DATA COLLECTION MATRIX</h2>
            <p>1.1 <span class="text-futuristic-300">Identity Data:</span> Name, email, and contact details provided during registration.</p>
            <p>1.2 <span class="text-futuristic-300">Operational Data:</span> IP addresses, device information, and access logs.</p>
            <p>1.3 <span class="text-futuristic-300">Credential Data:</span> API keys and secrets encrypted before storage.</p>
            <p>1.4 <span class="text-futuristic-300">Usage Data:</span> System interaction patterns for service improvement.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">2. DATA PROCESSING FRAMEWORK</h2>
            <p>2.1 <span class="text-futuristic-300">Encryption:</span> All sensitive data is encrypted using AES-256 with quantum-resistant protocols.</p>
            <p>2.2 <span class="text-futuristic-300">Access Control:</span> Strict role-based access to user data.</p>
            <p>2.3 <span class="text-futuristic-300">Minimization:</span> Only data necessary for service operation is collected.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">3. THIRD-PARTY INTEGRATIONS</h2>
            <p>3.1 <span class="text-futuristic-300">Analytics:</span> Anonymous usage data may be shared with analytics providers.</p>
            <p>3.2 <span class="text-futuristic-300">Compliance:</span> Data may be disclosed if required by law enforcement.</p>
            <p>3.3 <span class="text-futuristic-300">Subprocessors:</span> All third parties undergo security vetting.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">4. USER RIGHTS PROTOCOL</h2>
            <p>4.1 <span class="text-futuristic-300">Access:</span> Users may request copies of their personal data.</p>
            <p>4.2 <span class="text-futuristic-300">Correction:</span> Users may update inaccurate information.</p>
            <p>4.3 <span class="text-futuristic-300">Deletion:</span> Account deletion requests will be processed within 30 days.</p>
            <p>4.4 <span class="text-futuristic-300">Objection:</span> Users may opt out of non-essential processing.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">5. SECURITY MEASURES</h2>
            <p>5.1 <span class="text-futuristic-300">Network Security:</span> Enterprise-grade firewalls and intrusion detection.</p>
            <p>5.2 <span class="text-futuristic-300">Physical Security:</span> Data centers with biometric access controls.</p>
            <p>5.3 <span class="text-futuristic-300">Personnel Training:</span> Regular security awareness training.</p>
            <p>5.4 <span class="text-futuristic-300">Incident Response:</span> 24/7 monitoring and breach notification protocols.</p>
        </div>
        
        <div class="border-b border-gray-700/50 pb-6">
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">6. DATA RETENTION POLICY</h2>
            <p>6.1 <span class="text-futuristic-300">Active Accounts:</span> Data retained while account is active.</p>
            <p>6.2 <span class="text-futuristic-300">Inactive Accounts:</span> Data archived after 12 months of inactivity.</p>
            <p>6.3 <span class="text-futuristic-300">Deleted Accounts:</span> Complete erasure within 90 days of deletion request.</p>
        </div>
        
        <div>
            <h2 class="text-xl font-bold text-futuristic-400 mb-4 font-mono">7. POLICY UPDATES</h2>
            <p>7.1 Users will be notified of material changes 30 days in advance.</p>
            <p>7.2 Continued use after changes constitutes acceptance.</p>
            <p>7.3 Archive of previous versions available upon request.</p>
        </div>
        
        <div class="mt-8 p-4 bg-gray-700/30 rounded-lg border border-gray-600/50">
            <p class="text-center font-mono text-sm">FOR DATA REQUESTS OR SECURITY CONCERNS, CONTACT: <span class="text-futuristic-400">underside001@gmail.com</span></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>