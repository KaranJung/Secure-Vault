<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Check for system maintenance mode
$maintenance_mode = false;
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT value FROM system_settings WHERE setting = 'maintenance_mode'");
        $maintenance_mode = $stmt->fetchColumn() === '1';
    }
} catch (Exception $e) {
    // Silently fail - not critical
}

// Get system version
$system_version = "";
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT value FROM system_settings WHERE setting = 'version'");
        $version = $stmt->fetchColumn();
        if ($version) {
            $system_version = $version;
        }
    }
} catch (Exception $e) {
    // Silently fail - not critical
}

// Check for unread notifications if user is logged in
$notification_count = 0;
if (isLoggedIn() && isset($pdo)) {
    try {
        $user_id = getUserId();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $notification_count = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Silently fail - not critical
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | SECURE_VAULT' : 'SECURE_VAULT'; ?></title>
    <link rel="icon" type="image/x-icon" href="vault.png">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'SECURE_VAULT provides military-grade encryption for managing and storing your API credentials safely.'; ?>">
    <meta name="keywords" content="API, security, vault, management, secure storage, credentials, tokens, keys, encryption">
    <meta name="author" content="Karan Jung Budhathoki">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | SECURE_VAULT' : 'SECURE_VAULT'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Manage and store your API credentials with military-grade encryption.'; ?>">
    <meta property="og:image" content="vault.png"> 
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SECURE_VAULT">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | SECURE_VAULT' : 'SECURE_VAULT'; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Manage and store your API credentials with military-grade encryption.'; ?>">
    <meta name="twitter:image" content="vault.png">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts - Futuristic typeface -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Rajdhani:wght@300;400;500;600;700&family=JetBrains+Mono:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <!-- Tippy.js for tooltips -->
    <script src="https://unpkg.com/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://unpkg.com/tippy.js@6.3.7/dist/tippy-bundle.umd.js"></script>
    <!-- Three.js for 3D effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                fontFamily: {
                    sans: ['Rajdhani', 'sans-serif'],
                    mono: ['Orbitron', 'monospace'],
                    code: ['JetBrains Mono', 'monospace'],
                },
                extend: {
                    colors: {
                        futuristic: {
                            50: '#e0f7ff',
                            100: '#b8edff',
                            200: '#7de2ff',
                            300: '#3acbff',
                            400: '#00b9ff',
                            500: '#0095ff',
                            600: '#0077ff',
                            700: '#0066ff',
                            800: '#0051d6',
                            900: '#003c9e',
                        },
                        cyber: {
                            50: '#e0f0ff',
                            100: '#bae0ff',
                            200: '#7cc3ff',
                            300: '#36a5ff',
                            400: '#0d8eff',
                            500: '#0077ff',
                            600: '#0062d6',
                            700: '#0052b3',
                            800: '#003f8a',
                            900: '#002c61',
                        },
                        neon: {
                            pink: '#ff00ff',
                            blue: '#00ffff',
                            green: '#00ff00',
                            yellow: '#ffff00',
                            purple: '#8a2be2',
                        },
                        matrix: {
                            green: '#00ff41',
                            dark: '#0d0208',
                        },
                        hacker: {
                            black: '#0a0a0a',
                            green: '#00ff41',
                            red: '#ff0043',
                        },
                        midnight: {
                            100: '#1e293b',
                            200: '#172032',
                            300: '#111827',
                            400: '#0f172a',
                            500: '#0c1222',
                            600: '#090d1a',
                            700: '#060911',
                            800: '#030509',
                            900: '#010203',
                        }
                    },
                    boxShadow: {
                        'neon': '0 0 5px rgba(0, 183, 255, 0.5), 0 0 10px rgba(0, 183, 255, 0.3)',
                        'neon-lg': '0 0 10px rgba(0, 183, 255, 0.5), 0 0 20px rgba(0, 183, 255, 0.3), 0 0 30px rgba(0, 183, 255, 0.2)',
                        'neon-xl': '0 0 15px rgba(0, 183, 255, 0.5), 0 0 30px rgba(0, 183, 255, 0.3), 0 0 45px rgba(0, 183, 255, 0.2)',
                        'neon-pink': '0 0 5px rgba(255, 0, 255, 0.5), 0 0 10px rgba(255, 0, 255, 0.3)',
                        'neon-green': '0 0 5px rgba(0, 255, 65, 0.5), 0 0 10px rgba(0, 255, 65, 0.3)',
                        'neon-purple': '0 0 5px rgba(138, 43, 226, 0.5), 0 0 10px rgba(138, 43, 226, 0.3)',
                        'hologram': '0 0 15px rgba(0, 183, 255, 0.5), 0 0 30px rgba(0, 183, 255, 0.3), inset 0 0 15px rgba(0, 183, 255, 0.3)'
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'float-slow': 'float 8s ease-in-out infinite',
                        'float-reverse': 'floatReverse 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'typing': 'typing 3.5s steps(30, end), blink-caret .75s step-end infinite',
                        'spin-slow': 'spin 6s linear infinite',
                        'spin-reverse': 'spin 8s linear infinite reverse',
                        'glitch': 'glitch 5s infinite',
                        'scanline': 'scanline 6s linear infinite',
                        'matrix-rain': 'matrixRain 20s linear infinite',
                        'hue-rotate': 'hueRotate 10s linear infinite',
                        'flicker': 'flicker 3s linear infinite',
                        'text-shimmer': 'textShimmer 3s ease infinite',
                        'border-flow': 'borderFlow 2s linear infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        floatReverse: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(10px)' },
                        },
                        glow: {
                            'from': { 'box-shadow': '0 0 5px rgba(0, 183, 255, 0.5)' },
                            'to': { 'box-shadow': '0 0 20px rgba(0, 183, 255, 0.9)' },
                        },
                        typing: {
                            'from': { width: '0' },
                            'to': { width: '100%' }
                        },
                        'blink-caret': {
                            'from, to': { 'border-color': 'transparent' },
                            '50%': { 'border-color': 'rgba(0, 183, 255, 0.75)' }
                        },
                        glitch: {
                            '0%, 100%': { transform: 'translate(0)' },
                            '20%': { transform: 'translate(-2px, 2px)' },
                            '40%': { transform: 'translate(-2px, -2px)' },
                            '60%': { transform: 'translate(2px, 2px)' },
                            '80%': { transform: 'translate(2px, -2px)' },
                        },
                        scanline: {
                            '0%': { transform: 'translateY(-100%)' },
                            '100%': { transform: 'translateY(100%)' },
                        },
                        matrixRain: {
                            '0%': { top: '-50%' },
                            '100%': { top: '110%' },
                        },
                        hueRotate: {
                            '0%': { filter: 'hue-rotate(0deg)' },
                            '100%': { filter: 'hue-rotate(360deg)' },
                        },
                        flicker: {
                            '0%, 19.999%, 22%, 62.999%, 64%, 64.999%, 70%, 100%': { opacity: '1' },
                            '20%, 21.999%, 63%, 63.999%, 65%, 69.999%': { opacity: '0.33' },
                        },
                        textShimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' },
                        },
                        borderFlow: {
                            '0%': { backgroundPosition: '0% 0%' },
                            '100%': { backgroundPosition: '100% 0%' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        
        /* Base Styles */
        body {
            font-feature-settings: "ss01", "ss02", "cv01", "cv02";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background-color: #0a0e17;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(0, 119, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(0, 119, 255, 0.05) 0%, transparent 50%);
            overflow-x: hidden;
        }
        
        /* Gradient Text Effects */
        .gradient-text {
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            background-image: linear-gradient(90deg, #00b9ff, #0066ff);
        }
        
        .cyber-text {
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            background-image: linear-gradient(90deg, #00b9ff, #0066ff);
        }
        
        .shimmer-text {
            background: linear-gradient(90deg, #00b9ff, #0066ff, #00b9ff);
            background-size: 200% auto;
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            animation: textShimmer 3s ease infinite;
        }
        
        /* Smooth Transitions */
        .transition-smooth {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Tech Border Effect */
        .tech-border {
            position: relative;
        }
        
        .tech-border:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00b9ff, transparent);
        }
        
        /* Holographic Card Effect */
        .holographic-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 183, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .holographic-card:hover {
            border-color: rgba(0, 183, 255, 0.3);
            box-shadow: 0 0 15px rgba(0, 183, 255, 0.2);
        }
        
        .holographic-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 0%,
                rgba(0, 183, 255, 0.05) 30%,
                rgba(0, 183, 255, 0.1) 40%,
                transparent 50%
            );
            transform: rotate(45deg);
            transition: all 0.5s ease;
            opacity: 0;
        }
        
        .holographic-card:hover::before {
            opacity: 1;
            animation: holographicSweep 2s infinite linear;
        }
        
        @keyframes holographicSweep {
            0% {
                transform: rotate(45deg) translateY(-100%);
            }
            100% {
                transform: rotate(45deg) translateY(100%);
            }
        }
        
        /* Typing Animation */
        .typing-animation {
            overflow: hidden;
            white-space: nowrap;
            border-right: 3px solid rgba(0, 183, 255, 0.75);
            animation: typing 3.5s steps(30, end), blink-caret .75s step-end infinite;
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 183, 255, 0.6);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 183, 255, 0.8);
        }
        
        /* Selection Styling */
        ::selection {
            background: rgba(0, 183, 255, 0.3);
            color: #ffffff;
        }
        
        /* Focus Outline */
        *:focus-visible {
            outline: 2px solid rgba(0, 183, 255, 0.6);
            outline-offset: 2px;
        }
        
        /* Tippy.js Theme */
        .tippy-box[data-theme~='cyber'] {
            background-color: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(0, 183, 255, 0.3);
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 183, 255, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .tippy-box[data-theme~='cyber'] .tippy-arrow {
            color: rgba(15, 23, 42, 0.95);
        }
        
        /* Grid Pattern Background */
        .grid-pattern {
            background-size: 50px 50px;
            background-image: 
                linear-gradient(to right, rgba(0, 183, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(0, 183, 255, 0.05) 1px, transparent 1px);
        }
        
        /* Animated Gradient Border */
        .gradient-border {
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .gradient-border::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00b9ff, #0066ff, #00b9ff);
            background-size: 200% 200%;
            animation: gradientBorder 3s ease infinite;
            z-index: -1;
            border-radius: 0.6rem;
        }
        
        @keyframes gradientBorder {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Terminal Text Effect */
        .terminal-text {
            font-family: 'JetBrains Mono', monospace;
            color: #00b9ff;
            text-shadow: 0 0 5px rgba(0, 183, 255, 0.5);
        }
        
        /* Glitch Effect */
        .glitch {
            position: relative;
            animation: glitch 5s infinite;
        }
        
        .glitch::before,
        .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.9);
            clip: rect(0, 0, 0, 0);
        }
        
        .glitch::before {
            left: 2px;
            text-shadow: -1px 0 #00b9ff;
            clip: rect(44px, 450px, 56px, 0);
            animation: glitch-anim 5s infinite linear alternate-reverse;
        }
        
        .glitch::after {
            left: -2px;
            text-shadow: -1px 0 #0066ff;
            clip: rect(44px, 450px, 56px, 0);
            animation: glitch-anim2 5s infinite linear alternate-reverse;
        }
        
        @keyframes glitch-anim {
            0% { clip: rect(42px, 9999px, 44px, 0); }
            5% { clip: rect(12px, 9999px, 59px, 0); }
            10% { clip: rect(48px, 9999px, 29px, 0); }
            15% { clip: rect(42px, 9999px, 73px, 0); }
            20% { clip: rect(63px, 9999px, 27px, 0); }
            25% { clip: rect(34px, 9999px, 55px, 0); }
            30% { clip: rect(86px, 9999px, 73px, 0); }
            35% { clip: rect(20px, 9999px, 20px, 0); }
            40% { clip: rect(26px, 9999px, 60px, 0); }
            45% { clip: rect(25px, 9999px, 66px, 0); }
            50% { clip: rect(57px, 9999px, 98px, 0); }
            55% { clip: rect(5px, 9999px, 46px, 0); }
            60% { clip: rect(82px, 9999px, 31px, 0); }
            65% { clip: rect(54px, 9999px, 27px, 0); }
            70% { clip: rect(28px, 9999px, 99px, 0); }
            75% { clip: rect(45px, 9999px, 69px, 0); }
            80% { clip: rect(23px, 9999px, 85px, 0); }
            85% { clip: rect(54px, 9999px, 84px, 0); }
            90% { clip: rect(45px, 9999px, 47px, 0); }
            95% { clip: rect(37px, 9999px, 20px, 0); }
            100% { clip: rect(4px, 9999px, 91px, 0); }
        }
        
        @keyframes glitch-anim2 {
            0% { clip: rect(65px, 9999px, 100px, 0); }
            5% { clip: rect(52px, 9999px, 74px, 0); }
            10% { clip: rect(79px, 9999px, 85px, 0); }
            15% { clip: rect(75px, 9999px, 5px, 0); }
            20% { clip: rect(67px, 9999px, 61px, 0); }
            25% { clip: rect(14px, 9999px, 79px, 0); }
            30% { clip: rect(1px, 9999px, 66px, 0); }
            35% { clip: rect(86px, 9999px, 30px, 0); }
            40% { clip: rect(23px, 9999px, 98px, 0); }
            45% { clip: rect(85px, 9999px, 72px, 0); }
            50% { clip: rect(71px, 9999px, 75px, 0); }
            55% { clip: rect(2px, 9999px, 48px, 0); }
            60% { clip: rect(30px, 9999px, 16px, 0); }
            65% { clip: rect(59px, 9999px, 50px, 0); }
            70% { clip: rect(41px, 9999px, 62px, 0); }
            75% { clip: rect(2px, 9999px, 82px, 0); }
            80% { clip: rect(47px, 9999px, 73px, 0); }
            85% { clip: rect(3px, 9999px, 27px, 0); }
            90% { clip: rect(26px, 9999px, 55px, 0); }
            95% { clip: rect(42px, 9999px, 97px, 0); }
            100% { clip: rect(38px, 9999px, 49px, 0); }
        }
        
        /* Scanline Effect */
        .scanline {
            position: relative;
            overflow: hidden;
        }
        
        .scanline::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: rgba(0, 183, 255, 0.1);
            animation: scanline 6s linear infinite;
            z-index: 2;
            pointer-events: none;
        }
        
        /* Matrix Rain Effect */
        .matrix-rain {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        .matrix-column {
            position: absolute;
            top: -50%;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, rgba(0, 255, 65, 0.5), transparent);
            animation: matrixRain 20s linear infinite;
        }
        
        /* Neon Button Effect */
        .neon-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .neon-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 183, 255, 0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .neon-btn:hover::before {
            left: 100%;
        }
        
        /* Cyberpunk Card */
        .cyberpunk-card {
            position: relative;
            background: rgba(10, 14, 23, 0.8);
            border: 1px solid rgba(0, 183, 255, 0.2);
            box-shadow: 0 0 10px rgba(0, 183, 255, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .cyberpunk-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #00b9ff, #0066ff);
            z-index: 1;
        }
        
        .cyberpunk-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 183, 255, 0.2);
        }
        
        /* Digital Noise */
        .digital-noise {
            position: relative;
        }
        
        .digital-noise::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
            opacity: 0.05;
            pointer-events: none;
        }
        
        /* Hexagon Grid */
        .hexagon-grid {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49' viewBox='0 0 28 49'%3E%3Cg fill-rule='evenodd'%3E%3Cg id='hexagons' fill='%230077ff' fill-opacity='0.05' fill-rule='nonzero'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l  fill-rule='nonzero'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l10.99 6.34L25 30.6v-12.7l-11-6.34L3 17.9zm.5 6.63v6.34l5.5 3.18v-6.34l-5.5-3.18zm6.5 3.18v6.34l5.5-3.18v-6.34l-5.5 3.18zM13.99 0L0 7.5v15l14 8.5 14-8.5v-15L13.99 0zM3 17.9v12.7l11 6.34 11-6.34V17.9l-11-6.34-11 6.34zm10.5-1.58l11-6.34v-7.9l-11 6.34v7.9zm-1 0l-11-6.34v-7.9l11 6.34v7.9zm0 1l11 6.34v7.9l-11-6.34v-7.9zm-1 0l-11 6.34v7.9l11-6.34v-7.9z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        /* Circuit Board Pattern */
        .circuit-pattern {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 304 304' width='304' height='304'%3E%3Cpath fill='%230077ff' fill-opacity='0.05' d='M44.1 224a5 5 0 1 1 0 2H0v-2h44.1zm160 48a5 5 0 1 1 0 2H82v-2h122.1zm57.8-46a5 5 0 1 1 0-2H304v2h-42.1zm0 16a5 5 0 1 1 0-2H304v2h-42.1zm6.2-114a5 5 0 1 1 0 2h-86.2a5 5 0 1 1 0-2h86.2zm-256-48a5 5 0 1 1 0 2H0v-2h12.1zm185.8 34a5 5 0 1 1 0-2h86.2a5 5 0 1 1 0 2h-86.2zM258 12.1a5 5 0 1 1-2 0V0h2v12.1zm-64 208a5 5 0 1 1-2 0v-54.2a5 5 0 1 1 2 0v54.2zm48-198.2V80h62v2h-64V21.9a5 5 0 1 1 2 0zm16 16V64h46v2h-48V37.9a5 5 0 1 1 2 0zm-128 96V208h16v12.1a5 5 0 1 1-2 0V210h-16v-76.1a5 5 0 1 1 2 0zm-5.9-21.9a5 5 0 1 1 0 2H114v48H85.9a5 5 0 1 1 0-2H112v-48h12.1zm-6.2 130a5 5 0 1 1 0-2H176v-74.1a5 5 0 1 1 2 0V242h-60.1zm-16-64a5 5 0 1 1 0-2H114v48h10.1a5 5 0 1 1 0 2H112v-48h-10.1zM66 284.1a5 5 0 1 1-2 0V274H50v30h-2v-32h18v12.1zM236.1 176a5 5 0 1 1 0 2H226v94h48v32h-2v-30h-48v-98h12.1zm25.8-30a5 5 0 1 1 0-2H274v44.1a5 5 0 1 1-2 0V146h-10.1zm-64 96a5 5 0 1 1 0-2H208v-80h16v-14h-42.1a5 5 0 1 1 0-2H226v18h-16v80h-12.1zm86.2-210a5 5 0 1 1 0 2H272V0h2v32h10.1zM98 101.9V146H53.9a5 5 0 1 1 0-2H96v-42.1a5 5 0 1 1 2 0zM53.9 34a5 5 0 1 1 0-2H80V0h2v34H53.9zm60.1 3.9V66H82v64H69.9a5 5 0 1 1 0-2H80V64h32V37.9a5 5 0 1 1 2 0zM101.9 82a5 5 0 1 1 0-2H128V37.9a5 5 0 1 1 2 0V82h-28.1zm16-64a5 5 0 1 1 0-2H146v44.1a5 5 0 1 1-2 0V18h-26.1zm102.2 270a5 5 0 1 1 0 2H98v14h-2v-16h124.1zM242 149.9V160h16v34h-16v62h48v48h-2v-46h-48v-66h16v-30h-16v-12.1a5 5 0 1 1 2 0zM53.9 18a5 5 0 1 1 0-2H64V2H48V0h18v18H53.9zm112 32a5 5 0 1 1 0-2H192V0h50v2h-48v48h-28.1zm-48-48a5 5 0 0 1-9.8-2h2.07a3 3 0 1 0 5.66 0H178v34h-18V21.9a5 5 0 1 1 2 0V32h14V2h-58.1zm0 96a5 5 0 1 1 0-2H137l32-32h39V21.9a5 5 0 1 1 2 0V66h-40.17l-32 32H117.9zm28.1 90.1a5 5 0 1 1-2 0v-76.51L175.59 80H224V21.9a5 5 0 1 1 2 0V82h-49.59L146 112.41v75.69zm16 32a5 5 0 1 1-2 0v-99.51L184.59 96H300.1a5 5 0 0 1 3.9-3.9v2.07a3 3 0 0 0 0 5.66v2.07a5 5 0 0 1-3.9-3.9H185.41L162 121.41v98.69zm-144-64a5 5 0 1 1-2 0v-3.51l48-48V48h32V0h2v50H66v55.41l-48 48v2.69zM50 53.9v43.51l-48 48V208h26.1a5 5 0 1 1 0 2H0v-65.41l48-48V53.9a5 5 0 1 1 2 0zm-16 16V89.41l-34 34v-2.82l32-32V69.9a5 5 0 1 1 2 0zM12.1 32a5 5 0 1 1 0 2H9.41L0 43.41V40.6L8.59 32h3.51zm265.8 18a5 5 0 1 1 0-2h18.69l7.41-7.41v2.82L297.41 50H277.9zm-16 160a5 5 0 1 1 0-2H288v-71.41l16-16v2.82l-14 14V210h-28.1zm-208 32a5 5 0 1 1 0-2H64v-22.59L40.59 194H21.9a5 5 0 1 1 0-2H41.41L66 217.59V242H53.9zm150.2 14a5 5 0 1 1 0 2H96v-56.6L56.6 162H37.9a5 5 0 1 1 0-2h19.5L98 200.6V256h106.1zm-150.2 2a5 5 0 1 1 0-2H80v-46.59L48.59 178H21.9a5 5 0 1 1 0-2H49.41L82 208.59V258H53.9zM34 39.8v1.61L9.41 66H0v-2h8.59L32 40.59V0h2v39.8zM2 300.1a5 5 0 0 1 3.9 3.9H3.83A3 3 0 0 0 0 302.17V256h18v48h-2v-46H2v42.1zM34 241v63h-2v-62H0v-2h34v1zM17 18H0v-2h16V0h2v18h-1zm273-2h14v2h-16V0h2v16zm-32 273v15h-2v-14h-14v14h-2v-16h18v1zM0 92.1A5.02 5.02 0 0 1 6 97a5 5 0 0 1-6 4.9v-2.07a3 3 0 1 0 0-5.66V92.1zM80 272h2v32h-2v-32zm37.9 32h-2.07a3 3 0 0 0-5.66 0h-2.07a5 5 0 0 1 9.8 0zM5.9 0A5.02 5.02 0 0 1 0 5.9V3.83A3 3 0 0 0 3.83 0H5.9zm294.2 0h2.07A3 3 0 0 0 304 3.83V5.9a5 5 0 0 1-3.9-5.9zm3.9 300.1v2.07a3 3 0 0 0-1.83 1.83h-2.07a5 5 0 0 1 3.9-3.9zM97 100a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-48 32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm32 48a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm32-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0-32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm32 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16-64a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 96a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-144a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-96 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm96 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16-64a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-32 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM49 36a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-32 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm32 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM33 68a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-48a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 240a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16-64a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16-32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm80-176a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm32 48a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0-32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm112 176a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-16 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM17 180a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0-32a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM17 84a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm32 64a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm16-16a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'%3E%3C/path%3E%3C/svg%3E");
        }
        
        /* Futuristic Button */
        .futuristic-btn {
            position: relative;
            background: linear-gradient(to right, #0066ff, #00b9ff);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(0, 183, 255, 0.5);
        }
        
        .futuristic-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .futuristic-btn:hover {
            box-shadow: 0 0 20px rgba(0, 183, 255, 0.7);
            transform: translateY(-2px);
        }
        
        .futuristic-btn:hover::before {
            left: 100%;
        }
        
        /* Animated Border */
        .animated-border {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .animated-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00b9ff, transparent);
            animation: borderFlow 2s linear infinite;
        }
        
        .animated-border::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 200%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #0066ff, transparent);
            animation: borderFlow 2s linear infinite reverse;
        }
        
        /* Cyber Badge */
        .cyber-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(0, 183, 255, 0.1);
            border: 1px solid rgba(0, 183, 255, 0.3);
            border-radius: 4px;
            padding: 4px 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: #00b9ff;
            box-shadow: 0 0 5px rgba(0, 183, 255, 0.2);
        }
        
        /* Data Terminal */
        .data-terminal {
            background: rgba(10, 14, 23, 0.9);
            border: 1px solid rgba(0, 183, 255, 0.3);
            border-radius: 4px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            color: #00b9ff;
            position: relative;
            overflow: hidden;
        }
        
        .data-terminal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #0066ff, #00b9ff);
        }
        
        /* Cyber Divider */
        .cyber-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 183, 255, 0.5), transparent);
            position: relative;
        }
        
        .cyber-divider::before {
            content: '';
            position: absolute;
            top: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 10px;
            height: 5px;
            background: #00b9ff;
            border-radius: 2px;
        }
        
        /* Mobile Responsive Enhancements */
        @media (max-width: 640px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            .cyber-text {
                font-size: 0.9rem;
            }
            
            .holographic-card {
                padding: 0.75rem;
            }
            
            .data-terminal {
                padding: 0.75rem;
                font-size: 0.8rem;
            }
            
            .futuristic-btn {
                padding: 8px 16px;
                font-size: 0.8rem;
            }
        }
    </style>
    
    <?php if (isset($extra_head)): ?>
        <?php echo $extra_head; ?>
    <?php endif; ?>
</head>
<body class="bg-midnight-500 min-h-screen flex flex-col text-gray-100 circuit-pattern">
    <?php if ($maintenance_mode && !isset($_SESSION['admin_mode'])): ?>
    <!-- Maintenance Mode Banner -->
    <div class="bg-amber-900/80 text-amber-200 py-2 px-4 text-center font-mono text-sm border-b border-amber-700/50 backdrop-blur-md">
        <i class="fas fa-wrench mr-2 animate-pulse"></i> SYSTEM MAINTENANCE IN PROGRESS. Some features may be temporarily unavailable.
    </div>
    <?php endif; ?>
    
    <header class="bg-midnight-400/80 shadow-md sticky top-0 z-50 backdrop-blur-md border-b border-futuristic-700/30">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo and Site Title -->
            <a href="index.php" class="text-xl font-bold text-futuristic-400 flex items-center space-x-2 font-mono group">
                <div class="relative w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center">
                    <div class="absolute inset-0 bg-midnight-300 rounded-lg opacity-50 group-hover:opacity-70 transition-opacity"></div>
                    <div class="absolute inset-0 bg-gradient-to-br from-futuristic-500/20 to-futuristic-700/20 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <i class="fas fa-shield-alt text-futuristic-400 relative z-10 group-hover:scale-110 transition-transform text-sm sm:text-base"></i>
                </div>
                <div class="flex flex-col">
                    <span class="cyber-text group-hover:text-futuristic-300 transition-colors leading-none text-base sm:text-xl">SECURE_VAULT</span>
                    <?php if (isset($system_version)): ?>
                    <span class="text-[8px] sm:text-[10px] text-gray-500 group-hover:text-futuristic-500/70 transition-colors"><?php echo htmlspecialchars($system_version); ?></span>
                    <?php endif; ?>
                </div>
            </a>
            
            <!-- Navigation -->
            <nav class="flex items-center">
                <?php if (isLoggedIn()): ?>
                    <!-- Mobile Menu Button (visible on small screens) -->
                    <div class="sm:hidden mr-2">
                        <button type="button" id="mobile-menu-button" class="text-gray-300 hover:text-futuristic-400 focus:outline-none p-1">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                    </div>
                    
                    <!-- Desktop Navigation (hidden on small screens) -->
                    <div class="hidden sm:flex items-center space-x-6 mr-6">
                        <a href="dashboard.php" class="text-sm font-medium <?php echo $current_page === 'dashboard.php' ? 'text-futuristic-400' : 'text-gray-300 hover:text-futuristic-400'; ?> transition-colors relative group">
                            <i class="fas fa-tachometer-alt mr-1"></i> DASHBOARD
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-futuristic-400 group-hover:w-full transition-all duration-300 <?php echo $current_page === 'dashboard.php' ? 'w-full' : ''; ?>"></span>
                        </a>
                        <a href="credentials.php" class="text-sm font-medium <?php echo $current_page === 'credentials.php' ? 'text-futuristic-400' : 'text-gray-300 hover:text-futuristic-400'; ?> transition-colors relative group">
                            <i class="fas fa-key mr-1"></i> CREDENTIALS
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-futuristic-400 group-hover:w-full transition-all duration-300 <?php echo $current_page === 'credentials.php' ? 'w-full' : ''; ?>"></span>
                        </a>
                        <a href="analytics.php" class="text-sm font-medium <?php echo $current_page === 'analytics.php' ? 'text-futuristic-400' : 'text-gray-300 hover:text-futuristic-400'; ?> transition-colors relative group">
                            <i class="fas fa-chart-line mr-1"></i> ANALYTICS
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-futuristic-400 group-hover:w-full transition-all duration-300 <?php echo $current_page === 'analytics.php' ? 'w-full' : ''; ?>"></span>
                        </a>
                    </div>
                    
                    <!-- User Menu -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-300 hover:text-futuristic-400 focus:outline-none transition-smooth">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-midnight-300 flex items-center justify-center text-futuristic-400 border border-futuristic-500/50 relative shadow-neon overflow-hidden group">
                                <div class="absolute inset-0 bg-gradient-to-br from-futuristic-500/10 to-futuristic-700/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <?php echo substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                                <?php if ($notification_count > 0): ?>
                                <span class="absolute -top-1 -right-1 w-3 h-3 sm:w-4 sm:h-4 bg-red-500 rounded-full text-white text-[8px] sm:text-xs flex items-center justify-center animate-pulse">
                                    <?php echo $notification_count > 9 ? '9+' : $notification_count; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <span class="font-medium hidden sm:inline-block"><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
                            <i class="fas fa-chevron-down text-xs transition-smooth" :class="{'transform rotate-180': open}"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute right-0 mt-2 w-48 sm:w-60 bg-midnight-300/90 backdrop-blur-md rounded-md shadow-lg py-1 z-50 border border-futuristic-700/30 cyberpunk-card">
                            <div class="px-4 py-3 border-b border-futuristic-700/30">
                                <p class="text-sm font-medium text-gray-200"><?php echo $_SESSION['user_name'] ?? 'User'; ?></p>
                                <p class="text-xs text-gray-400 truncate font-mono"><?php echo $_SESSION['user_email'] ?? ''; ?></p>
                                <div class="mt-2 flex items-center">
                                    <span class="text-xs text-futuristic-400 font-mono">ACCESS LEVEL:</span>
                                    <span class="ml-1 text-xs font-mono text-gray-300">
                                        <?php echo isset($_SESSION['admin_mode']) && $_SESSION['admin_mode'] ? 'ADMINISTRATOR' : 'STANDARD'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <a href="dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-midnight-200/20 hover:text-futuristic-400 transition-smooth">
                                <i class="fas fa-tachometer-alt mr-2 text-futuristic-400"></i> Command Center
                            </a>
                            
                            <?php if ($notification_count > 0): ?>
                            <a href="notifications.php" class="flex items-center justify-between px-4 py-2 text-sm text-gray-300 hover:bg-midnight-200/20 hover:text-futuristic-400 transition-smooth">
                                <div>
                                    <i class="fas fa-bell mr-2 text-futuristic-400"></i> Notifications
                                </div>
                                <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                                    <?php echo $notification_count; ?>
                                </span>
                            </a>
                            <?php else: ?>
                            <a href="notifications.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-midnight-200/20 hover:text-futuristic-400 transition-smooth">
                                <i class="fas fa-bell mr-2 text-futuristic-400"></i> Notifications
                            </a>
                            <?php endif; ?>
                            
                            <a href="settings.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-midnight-200/20 hover:text-futuristic-400 transition-smooth">
                                <i class="fas fa-cog mr-2 text-futuristic-400"></i> Settings
                            </a>
                            
                            <?php if (isset($_SESSION['admin_mode']) && $_SESSION['admin_mode']): ?>
                            <a href="admin.php" class="flex items-center px-4 py-2 text-sm text-amber-300 hover:bg-midnight-200/20 transition-smooth">
                                <i class="fas fa-user-shield mr-2"></i> Admin Panel
                            </a>
                            <?php endif; ?>
                            
                            <div class="cyber-divider my-1 mx-4"></div>
                            
                            <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-midnight-200/20 hover:text-red-400 transition-smooth">
                                <i class="fas fa-sign-out-alt mr-2 text-red-400"></i> Secure Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="space-x-2 sm:space-x-3">
                        <a href="login.php" class="text-gray-300 hover:text-futuristic-400 font-medium transition-smooth px-2 py-1.5 sm:px-3 sm:py-1.5 rounded-md hover:bg-midnight-300/50 border border-transparent hover:border-futuristic-500/30 text-sm">
                            <i class="fas fa-sign-in-alt mr-1"></i><span class="hidden sm:inline">ACCESS</span>
                        </a>
                        <a href="register.php" class="bg-gradient-to-r from-futuristic-600 to-futuristic-500 hover:from-futuristic-500 hover:to-futuristic-400 text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded-md font-medium shadow-neon hover:shadow-neon-lg transition-all duration-300 border border-futuristic-400/30 text-sm">
                            <i class="fas fa-user-plus mr-1"></i><span class="hidden sm:inline">REGISTER</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- Mobile Navigation Menu (hidden by default) -->
        <?php if (isLoggedIn()): ?>
        <div id="mobile-menu" class="sm:hidden hidden bg-midnight-300/95 backdrop-blur-md border-b border-futuristic-700/30">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'dashboard.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?>">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a href="credentials.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'credentials.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?>">
                    <i class="fas fa-key mr-2"></i> Credentials
                </a>
                <a href="analytics.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'analytics.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?>">
                    <i class="fas fa-chart-line mr-2"></i> Analytics
                </a>
                <a href="add_credential.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'add_credential.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?>">
                    <i class="fas fa-plus-circle mr-2"></i> Add New API
                </a>
                <a href="settings.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'settings.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?>">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <?php if ($notification_count > 0): ?>
                <a href="notifications.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'notifications.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?> flex justify-between items-center">
                    <div>
                        <i class="fas fa-bell mr-2"></i> Notifications
                    </div>
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        <?php echo $notification_count; ?>
                    </span>
                </a>
                <?php else: ?>
                <a href="notifications.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'notifications.php' ? 'bg-midnight-200 text-futuristic-400' : 'text-gray-300 hover:bg-midnight-200/50 hover:text-futuristic-400'; ?>">
                    <i class="fas fa-bell mr-2"></i> Notifications
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </header>
    
    <main class="flex-grow">
    
    <script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // Initialize tooltips
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]', {
                theme: 'cyber',
                animation: 'shift-away',
                duration: [200, 150],
                arrow: true
            });
        }
        
        // Create matrix rain effect
        function createMatrixRain() {
            const matrixContainer = document.createElement('div');
            matrixContainer.className = 'matrix-rain';
            document.body.appendChild(matrixContainer);
            
            const containerWidth = window.innerWidth;
            const columnCount = Math.floor(containerWidth / 20);
            
            for (let i = 0; i < columnCount; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = `${(i * 20) + Math.random() * 10}px`;
                column.style.animationDelay = `${Math.random() * 20}s`;
                column.style.opacity = `${Math.random() * 0.5}`;
                matrixContainer.appendChild(column);
            }
        }
        
        // Uncomment to enable matrix rain effect
        //createMatrixRain();
    });
    </script>
