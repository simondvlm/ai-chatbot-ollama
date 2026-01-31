<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page introuvable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            background: #0f0f0f;
            color: #e8e8e8;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #dc2626;
            border-radius: 50%;
            opacity: 0;
            animation: float 15s infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        .container {
            text-align: center;
            z-index: 1;
            position: relative;
            padding: 40px 20px;
            max-width: 600px;
        }

        .error-code {
            font-size: 180px;
            font-weight: 900;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #7f1d1d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            animation: glitch 3s infinite;
            text-shadow: 0 0 30px rgba(220, 38, 38, 0.5);
        }

        @keyframes glitch {
            0%, 100% {
                transform: translate(0);
            }
            20% {
                transform: translate(-2px, 2px);
            }
            40% {
                transform: translate(-2px, -2px);
            }
            60% {
                transform: translate(2px, 2px);
            }
            80% {
                transform: translate(2px, -2px);
            }
        }

        .error-title {
            font-size: 32px;
            font-weight: 700;
            color: #e8e8e8;
            margin-bottom: 16px;
            animation: fadeInUp 0.8s ease;
        }

        .error-message {
            font-size: 16px;
            color: #9a9a9a;
            margin-bottom: 40px;
            line-height: 1.6;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1.2s ease;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5);
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .btn-secondary {
            background: #2a2a2a;
            color: #e8e8e8;
            border: 1px solid #3a3a3a;
        }

        .btn-secondary:hover {
            background: #3a3a3a;
            border-color: #dc2626;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .icon {
            width: 20px;
            height: 20px;
        }
        .glitch-wrapper {
            position: relative;
            display: inline-block;
        }

        .glitch-wrapper::before,
        .glitch-wrapper::after {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #7f1d1d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glitch-wrapper::before {
            animation: glitchBefore 3s infinite;
            clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
        }

        .glitch-wrapper::after {
            animation: glitchAfter 3s infinite;
            clip-path: polygon(0 55%, 100% 55%, 100% 100%, 0 100%);
        }

        @keyframes glitchBefore {
            0%, 100% {
                transform: translate(0);
            }
            33% {
                transform: translate(-4px, 0);
            }
        }

        @keyframes glitchAfter {
            0%, 100% {
                transform: translate(0);
            }
            33% {
                transform: translate(4px, 0);
            }
        }
        @media (max-width: 768px) {
            .error-code {
                font-size: 120px;
            }

            .error-title {
                font-size: 24px;
            }

            .error-message {
                font-size: 14px;
            }

            .buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    <div class="container">
        <div class="glitch-wrapper">
            <div class="error-code">500</div>
        </div>
        
        <h1 class="error-title">Erreur serveur</h1>
        
        <p class="error-message">
           Il semble que notre serveur ait rencontré un petit problème. 
        </p>

        <div class="buttons">
            <a href="/" class="btn btn-primary">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Retour à l'accueil
            </a>
            
            <button onclick="history.back()" class="btn btn-secondary">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Page précédente
            </button>
        </div>
    </div>

    <script>
        const particlesContainer = document.getElementById('particles');
        const particleCount = 50;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particlesContainer.appendChild(particle);
        }
        setInterval(() => {
            const errorCode = document.querySelector('.error-code');
            if (Math.random() > 0.95) {
                errorCode.style.transform = `translate(${Math.random() * 4 - 2}px, ${Math.random() * 4 - 2}px)`;
                setTimeout(() => {
                    errorCode.style.transform = 'translate(0, 0)';
                }, 100);
            }
        }, 100);
    </script>
</body>
</html>