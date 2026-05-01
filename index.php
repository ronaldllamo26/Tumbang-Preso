<?php
session_start();
require_once 'core/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tumbang Preso | Street King</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&family=Bangers&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --accent-yellow: #ffcc00; --p1-blue: #007bff; --p2-red: #dc3545; }
        body { background: #000; color: #fff; font-family: 'Outfit', sans-serif; margin: 0; overflow: hidden; height: 100vh; display: flex; justify-content: center; align-items: center; width: 100vw; }
        
        #orientation-warning {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 9999; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px;
        }
        @media (orientation: portrait) { #orientation-warning { display: flex; } }
        
        /* --- ARCADE BOX --- */
        #gameWrapper {
            position: relative;
            width: 1400px;
            max-width: 100vw;
            height: 900px;
            max-height: calc(100vw * 900 / 1400);
            background: #000 url('assets/sprites/top_down_bg.png') no-repeat center center;
            background-size: 100% 100%;
            overflow: hidden;
            box-shadow: 0 0 100px rgba(0,0,0,1), 0 0 20px rgba(255,204,0,0.2);
            margin: auto;
        }

        #gameCanvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 5; }

        .hud-card {
            position: absolute; z-index: 100; background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px); border-radius: 12px; padding: 12px 20px;
            border: 1px solid rgba(255, 255, 255, 0.1); border-top: 5px solid white;
            pointer-events: auto;
        }
        .p1-panel { top: 20px; left: 20px; width: 320px; border-color: var(--p1-blue); }
        .p2-panel { top: 20px; right: 20px; width: 320px; border-color: var(--p2-red); text-align: right; }
        .score-panel { top: 20px; left: 50%; transform: translateX(-50%); background: none; border: none; text-align: center; }

        .main-title { font-family: 'Permanent Marker', cursive; font-size: 3rem; color: var(--accent-yellow); text-shadow: 3px 3px 0px #000; margin: 0; }
        .score-display { font-family: 'Bangers', cursive; font-size: 5rem; background: rgba(0,0,0,0.6); padding: 0 40px; border-radius: 15px; display: inline-block; }
        .label-text { font-family: 'Bangers', cursive; font-size: 1.6rem; margin-bottom: 2px; }
        .progress { height: 14px; background: rgba(255,255,255,0.1); border-radius: 7px; }

        #comboText {
            position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) scale(0);
            font-family: 'Bangers', cursive; font-size: 8rem; color: var(--accent-yellow);
            text-shadow: 6px 6px 0px #000; z-index: 1000; transition: transform 0.2s; pointer-events: none;
        }
        #comboText.active { transform: translate(-50%, -50%) scale(1); }

        .full-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 2000; display: flex; flex-direction: column; justify-content: center; align-items: center;
        }
        .btn-arcade {
            background: var(--accent-yellow); color: #000; font-family: 'Bangers', cursive;
            font-size: 2.5rem; padding: 12px 50px; border-radius: 12px; border: none;
            box-shadow: 0 8px 0 #b38f00; margin-bottom: 30px; cursor: pointer;
        }
        #resultOverlay { background: rgba(0,0,0,0.95); text-align: center; }
        .rank-badge { font-size: 10rem; font-family: 'Bangers', cursive; color: var(--accent-yellow); text-shadow: 0 0 30px rgba(255,204,0,0.5); margin: -20px 0; line-height: 1; }
        .leaderboard-table { width: 450px; background: rgba(255,255,255,0.05); border-radius: 15px; margin: 25px 0; font-family: 'Outfit', sans-serif; border: 1px solid rgba(255,255,255,0.1); }
        .leaderboard-table th { color: var(--accent-yellow); font-family: 'Bangers'; border-bottom: 1px solid rgba(255,255,255,0.1); padding: 12px; font-size: 1.4rem; }
        .leaderboard-table td { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 1.1rem; }
        .stat-label { font-size: 1rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 3px; font-weight: bold; }
        
        .game-status {
            position: absolute; top: 120px; left: 50%; transform: translateX(-50%);
            text-align: center; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            z-index: 10;
        }
        .round-tag {
            background: rgba(255, 204, 0, 0.9); color: #000;
            padding: 5px 15px; border-radius: 20px; font-weight: 900;
            font-size: 1.2rem; display: inline-block; margin-bottom: 5px;
            box-shadow: 0 4px 15px rgba(255,204,0,0.4);
            text-transform: uppercase;
        }
        .boss-tag {
            background: #f44336; color: #fff;
            animation: pulse-red 1s infinite;
        }
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244,67,54,0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(244,67,54,0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(244,67,54,0); }
        }
        
        /* Skills UI */
        .skills-container { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); display: flex; gap: 20px; z-index: 100; }
        .skill-box { 
            width: 70px; height: 70px; 
            background: rgba(20, 20, 20, 0.85); 
            border: 2px solid rgba(255, 255, 255, 0.2); 
            backdrop-filter: blur(8px);
            border-radius: 15px; 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            position: relative; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            transition: transform 0.2s, border-color 0.2s;
        }
        .skill-box:hover { transform: scale(1.05); border-color: var(--accent-yellow); }
        .skill-box canvas, .skill-box img { width: 45px; height: 45px; object-fit: contain; filter: drop-shadow(0 0 5px rgba(255,255,255,0.2)); z-index: 5; }
        .skill-key { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--accent-yellow); color: #000; font-family: 'Bangers'; padding: 2px 10px; border-radius: 4px; font-size: 0.8rem; box-shadow: 0 2px 5px rgba(0,0,0,0.3); white-space: nowrap; z-index: 20; }
        .skill-cooldown { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(255, 204, 0, 0.4); height: 0%; transition: height 0.1s; border-radius: 0 0 13px 13px; z-index: 10; }
        .skill-count { font-family: 'Bangers'; font-size: 0.8rem; color: #fff; margin-top: 5px; letter-spacing: 1px; }

        /* Mobile Joystick */
        #joystick-container {
            position: absolute; bottom: 40px; left: 40px; width: 150px; height: 150px;
            background: rgba(255,255,255,0.05); border-radius: 50%; display: none; z-index: 1000;
            border: 2px solid rgba(255,255,255,0.1); backdrop-filter: blur(2px);
        }
        #joystick-knob {
            position: absolute; top: 50%; left: 50%; width: 60px; height: 60px;
            background: rgba(255, 204, 0, 0.7); border-radius: 50%; transform: translate(-50%, -50%);
            box-shadow: 0 0 15px rgba(255,204,0,0.3); border: 2px solid rgba(255,255,255,0.3);
        }
        
        @media (max-width: 1024px) {
            #gameWrapper { width: 100vw; height: calc(100vw * 900 / 1400); max-height: 100vh; }
            #joystick-container { display: block; bottom: 20px; left: 20px; width: 120px; height: 120px; }
            .skills-container { right: 20px; left: auto; transform: none; flex-direction: column; bottom: 20px; gap: 8px; }
            .skill-box { width: 55px; height: 55px; }
            .skill-box canvas, .skill-box img { width: 30px; height: 30px; }
            
            .hud-card { padding: 6px 12px; }
            .p1-panel { top: 10px; left: 10px; width: 160px; }
            .p2-panel { top: 10px; right: 10px; width: 160px; }
            .score-panel { top: 5px; }
            .score-panel .main-title { display: none; }
            .score-panel .score-display { font-size: 2rem; padding: 0 15px; }
            .label-text { font-size: 1rem; }
            #muteIcon { top: 10px; right: 180px; width: 40px; height: 40px; font-size: 1.2rem; }
            #comboText { font-size: 4rem; top: 30%; }
            
            /* Result Overlay Mobile */
            #resultOverlay { padding: 10px; }
            #resultOverlay .main-title { font-size: 2.5rem; margin-bottom: 5px; }
            .rank-badge { width: 80px; height: 80px; font-size: 3rem; margin-bottom: 10px; }
            .leaderboard-table { width: 95%; font-size: 0.9rem; margin-bottom: 10px; }
            .leaderboard-table th { font-size: 1.1rem; padding: 6px; }
            .leaderboard-table td { padding: 6px; }
            .btn-arcade { padding: 10px 25px; font-size: 1.2rem; }
        }
    </style>
</head>
<body>

<div id="orientation-warning">
    <div class="main-title" style="font-size: 3rem; margin-bottom: 20px;">LIPAT MO CP MO!</div>
    <p style="font-size: 1.5rem; opacity: 0.8;">Mas masarap maglaro pag naka-Landscape, pre.</p>
    <div style="font-size: 4rem; margin-top: 20px; animation: rotate 2s infinite;">📱🔄</div>
    <style>
        @keyframes rotate { 0% { transform: rotate(0deg); } 50% { transform: rotate(90deg); } 100% { transform: rotate(0deg); } }
    </style>
</div>

<!-- Procedures Sounds (No External Files) -->
<audio id="audioBGM" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" preload="auto" loop></audio>

<button onclick="toggleMute()" style="position:fixed; top:20px; right:350px; z-index:3000; background:rgba(0,0,0,0.5); border:none; color:white; font-size:2rem; cursor:pointer; border-radius:50%; width:60px; height:60px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(10px);" id="muteIcon">🔊</button>
    <div id="gameWrapper">
        <div id="comboText">SAPUL!</div>
        <canvas id="gameCanvas"></canvas>

        <!-- Mobile Controls -->
        <div id="joystick-container">
            <div id="joystick-knob"></div>
        </div>

    <div class="hud-card p1-panel">
        <div class="label-text" style="color: var(--p1-blue);">P1: BATU-BATO</div>
        <div class="progress mb-2"><div id="p1-health" class="progress-bar bg-primary" style="width: 100%"></div></div>
        <div class="small text-uppercase opacity-75">TSINELAS: <span id="tsinelas-count">10</span></div>
    </div>

    <div class="game-status">
        <div id="roundIndicator" class="round-tag">GAME 1</div>
        <div class="score-display" id="current-score">0 / 3</div>
    </div>

    <div class="hud-card p2-panel">
        <div class="label-text" style="color: var(--p2-red);">P2: TAYÂ (BOT)</div>
        <div class="progress mb-2"><div id="p2-health" class="progress-bar bg-danger" style="width: 100%"></div></div>
        <div class="small text-uppercase opacity-75">LVL: <span id="difficulty-text">MEDIUM</span></div>
    </div>

    <!-- Skills UI -->
    <div class="skills-container">
        <div class="skill-box" id="skill-dash">
            <div class="skill-key">SHIFT</div>
            <div id="dash-icon-container"></div>
            <div class="skill-cooldown" id="dash-cd-overlay"></div>
            <div class="skill-count">DASH</div>
        </div>
        <div class="skill-box" id="skill-trap">
            <div class="skill-key">E</div>
            <div id="trap-icon-container"></div>
            <div class="skill-cooldown" id="trap-cd-overlay"></div>
            <div class="skill-count">PEEL: <span id="trap-count">2</span></div>
        </div>
    </div>

    <!-- Start Overlay -->
    <div id="startOverlay" class="full-overlay">
        <h1 class="main-title" style="font-size: 6rem;">TUMBANG PRESO</h1>
        <div id="step1" class="d-flex flex-column gap-2">
            <button onclick="showDiff()" class="btn-arcade">START LARO!</button>
        </div>
        <div id="step2" style="display: none; text-align: center;">
            <div class="d-flex gap-3 justify-content-center">
                <button onclick="startGame('easy')" class="btn-arcade" style="background:#4caf50; font-size:1.5rem;">EASY</button>
                <button onclick="startGame('medium')" class="btn-arcade" style="background:#ff9800; font-size:1.5rem;">MED</button>
                <button onclick="startGame('hard')" class="btn-arcade" style="background:#f44336; font-size:1.5rem;">HARD</button>
            </div>
            <div class="mt-4">
                <button onclick="window.location.href='index.php'" class="btn-arcade" style="background:#666; font-size:1rem; padding:8px 20px; box-shadow: 0 4px 0 #333;">RESTART FROM GAME 1</button>
            </div>
        </div>
    </div>

    <div id="resultOverlay" class="full-overlay" style="display: none;">
        <div class="stat-label">FINAL PERFORMANCE</div>
        <div id="rankBadge" class="rank-badge">S</div>
        <h1 id="winnerTitle" class="main-title" style="font-size: 4rem;">STREET KING!</h1>
        
        <table class="leaderboard-table">
            <thead><tr><th>RANK</th><th>PLAYER</th><th>SCORE</th></tr></thead>
            <tbody>
                <tr style="background: rgba(255,204,0,0.1);"><td>1st</td><td>YOU (P1)</td><td id="finalScoreText">3 - 0</td></tr>
                <tr><td>2nd</td><td>LUPIN</td><td>2 - 1</td></tr>
                <tr><td>3rd</td><td>BATO</td><td>1 - 2</td></tr>
            </tbody>
        </table>

        <button id="resultBtn" class="btn-arcade">NEXT ROUND</button>
    </div>
</div>

<script type="module">
    import Player from './game_engine/Player.js';
    import Projectile from './game_engine/Projectile.js';
    import input from './game_engine/Input.js';

    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 1400; canvas.height = 900;

    function getMousePos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    const urlParams = new URLSearchParams(window.location.search);
    let roundCount = parseInt(urlParams.get('round')) || 0;
    if (!urlParams.get('next')) roundCount = 0; // Force Game 1 on manual refresh/new session
    
    let savedLevel = urlParams.get('level');
    let isBossRound = (roundCount >= 1); // After 1st win, it's Tanod territory forever!
    
    // Global Audio Resume
    window.onclick = () => { if (audioCtx.state === 'suspended') audioCtx.resume(); };
    let bossWarningTimer = 0; 
    let isGameOver = false; let lataScore = 0; let tsinelasCount = 10; 
    let pendingScore = false, slipperRecovered = false, projectiles = [];
    let currentDifficulty = 'medium', aiDecisionTimer = 0;
    let traps = [], maxTraps = 2, trapsLeft = 2, trapCooldown = 0;
    let aiFatigue = 0, aiMaxFatigue = 600;
    let dogs = [], lastDogSpawn = 0;
    let powerups = [], lastPowerSpawn = 0;
    let vehicles = [], lastVehicleSpawn = 0, vehicleWarning = null;
    let shoutText = "", shoutTimer = 0;
    let gameStartTime = 0;
    let globalSpawnTimer = 0; // Prevent overlapping spawns

    const aiConfig = {
        easy: { speed: 4, reaction: 0.1, chaseRange: 500, decisionDelay: 60 },
        medium: { speed: 6, reaction: 0.4, chaseRange: 900, decisionDelay: 20 },
        hard: { speed: 8.5, reaction: 0.8, chaseRange: 2000, decisionDelay: 0 }
    };

    let processedLata = null, processedTsinelas = null;
    const lataImg = new Image(), tsinelasImg = new Image();
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffers = {};

    async function loadSound(name, url) {
        try {
            const response = await fetch(url);
            const arrayBuffer = await response.arrayBuffer();
            audioBuffers[name] = await audioCtx.decodeAudioData(arrayBuffer);
        } catch (e) { console.error("Failed to load sound:", name); }
    }
    loadSound('bark', 'https://actions.google.com/sounds/v1/animals/dog_barking.ogg');
    loadSound('throw', 'https://actions.google.com/sounds/v1/foley/fast_whoosh.ogg');
    loadSound('horn', 'https://actions.google.com/sounds/v1/transportation/car_horn.ogg');

    const sounds = { 
        bgm: document.getElementById('audioBGM'),
        play(type) {
            if (isMuted) return;
            if (audioBuffers[type]) {
                const source = audioCtx.createBufferSource();
                source.buffer = audioBuffers[type];
                source.connect(audioCtx.destination);
                source.start(0);
                if (type === 'bark') source.stop(audioCtx.currentTime + 1.5);
                if (type === 'horn') source.stop(audioCtx.currentTime + 1.2);
                return;
            }
            const now = audioCtx.currentTime;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain); gain.connect(audioCtx.destination);
            
            if (type === 'throw') {
                const bufferSize = audioCtx.sampleRate * 0.4;
                const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) data[i] = Math.random() * 2 - 1;
                const noise = audioCtx.createBufferSource(); noise.buffer = buffer;
                const filter = audioCtx.createBiquadFilter();
                filter.type = 'lowpass'; filter.frequency.setValueAtTime(1200, now);
                filter.frequency.exponentialRampToValueAtTime(100, now + 0.4);
                noise.connect(filter); filter.connect(gain); gain.connect(audioCtx.destination);
                gain.gain.setValueAtTime(0.6, now); gain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
                noise.start(now); noise.stop(now + 0.4);
            } else if (type === 'hit') {
                osc.type = 'sine'; osc.frequency.setValueAtTime(150, now);
                osc.frequency.exponentialRampToValueAtTime(40, now + 0.1);
                gain.gain.setValueAtTime(1, now); gain.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
                osc.start(now); osc.stop(now + 0.15);
            } else if (type === 'bark') {
                // Procedural Bark (Old School Arcade Style)
                [now, now + 0.12].forEach(t => {
                    const bOsc = audioCtx.createOscillator();
                    const bGain = audioCtx.createGain();
                    bOsc.type = 'square';
                    bOsc.frequency.setValueAtTime(150, t);
                    bOsc.frequency.linearRampToValueAtTime(50, t + 0.08);
                    bGain.gain.setValueAtTime(0.5, t);
                    bGain.gain.linearRampToValueAtTime(0, t + 0.08);
                    bOsc.connect(bGain); bGain.connect(audioCtx.destination);
                    bOsc.start(t); bOsc.stop(t + 0.08);
                });
            } else if (type === 'slip') {
                osc.type = 'sine'; osc.frequency.setValueAtTime(200, now);
                osc.frequency.exponentialRampToValueAtTime(800, now + 0.5);
                gain.gain.setValueAtTime(0.4, now); gain.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
                osc.start(now); osc.stop(now + 0.5);
            } else if (type === 'horn') {
                // Pinoy Jeepney Horn (Beep-Beep!)
                [now, now + 0.25].forEach(t => {
                    const hOsc = audioCtx.createOscillator(); const hGain = audioCtx.createGain();
                    hOsc.type = 'triangle'; hOsc.frequency.setValueAtTime(440, t);
                    hGain.gain.setValueAtTime(0.3, t); hGain.gain.exponentialRampToValueAtTime(0.01, t + 0.15);
                    hOsc.connect(hGain); hGain.connect(audioCtx.destination);
                    hOsc.start(t); hOsc.stop(t + 0.15);
                });
            } else if (type === 'whistle') {
                // Tanod Whistle (Sharp High Pitch)
                const wOsc = audioCtx.createOscillator(); const wGain = audioCtx.createGain();
                wOsc.type = 'sine'; wOsc.frequency.setValueAtTime(2500, now);
                wOsc.frequency.exponentialRampToValueAtTime(2800, now + 0.1);
                wOsc.frequency.exponentialRampToValueAtTime(2500, now + 0.2);
                wGain.gain.setValueAtTime(0.3, now); wGain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
                wOsc.connect(wGain); wGain.connect(audioCtx.destination);
                wOsc.start(now); wOsc.stop(now + 0.4);
            }
        }
    };
    sounds.bgm.loop = true; sounds.bgm.volume = 0.15;
    let isMuted = false;
    window.toggleMute = () => { 
        isMuted = !isMuted; sounds.bgm.muted = isMuted;
        document.getElementById('muteIcon').innerText = isMuted ? '🔇' : '🔊'; 
    };

    // --- ALIGNED TO BACKGROUND IMAGE ---
    // Background Circle is at roughly 415, 600 in a 1400x900 scale
    // Background Line is at roughly 1150 in a 1400x900 scale
    const CIRCLE_X = 415, CIRCLE_Y = 600, SAFE_LINE_X = 1150;

    const p1 = new Player(1250, 450, '#1976d2', true, 'assets/sprites/p1_body.png'); 
    const p2 = new Player(450, 450, '#d32f2f', false, 'assets/sprites/p2_body.png'); 

    const lata = {
        x: CIRCLE_X, y: CIRCLE_Y, startX: CIRCLE_X, startY: CIRCLE_Y, z: 0, 
        vx: 0, vy: 0, vz: 0, rotation: 0, rotationSpeed: 0, width: 55, height: 80, isDown: false, isBeingCarried: false, gravity: 0.4,
        update() {
            if (this.isBeingCarried) { this.x = p2.x + p2.width/2; this.y = p2.y + p2.height/2; this.z = -40; return; }
            if (this.isDown) {
                this.x += this.vx; this.y += this.vy; this.vz += this.gravity; this.z += this.vz; this.rotation += this.rotationSpeed;
                // Boundaries for Lata
                if (this.x < 40) { this.x = 40; this.vx *= -0.5; }
                if (this.x > 1360) { this.x = 1360; this.vx *= -0.5; }
                if (this.y < 160) { this.y = 160; this.vy *= -0.5; }
                if (this.y > 860) { this.y = 860; this.vy *= -0.5; }
                
                if (this.z >= 0) { this.z = 0; this.vz *= -0.5; this.vx *= 0.85; this.vy *= 0.85; this.rotationSpeed *= 0.8; }
            }
        },
        hit(forceX, forceY) { this.isDown = true; this.vx = forceX * 0.35; this.vy = forceY * 0.35; this.vz = -9; this.rotationSpeed = 0.3; slipperRecovered = false; },
        draw(ctx) {
            ctx.save(); ctx.fillStyle = 'rgba(0,0,0,0.3)'; ctx.beginPath(); ctx.ellipse(this.x, this.y, 35, 18, 0, 0, Math.PI*2); ctx.fill();
            ctx.translate(this.x, this.y + this.z); ctx.rotate(this.isDown ? this.rotation : 0);
            if (processedLata) ctx.drawImage(processedLata, -this.width/2, -this.height, this.width, this.height);
            else { ctx.fillStyle = '#bbb'; ctx.fillRect(-this.width/2, -this.height, this.width, this.height); }
            ctx.restore();
        }
    };

    function processImageTransparency(img, callback) {
        const os = document.createElement('canvas'); const osCtx = os.getContext('2d');
        img.onload = () => { os.width = img.width; os.height = img.height; osCtx.drawImage(img, 0, 0); const id = osCtx.getImageData(0, 0, os.width, os.height); const d = id.data; for (let i = 0; i < d.length; i += 4) { if (d[i] > 220 && d[i+1] > 220 && d[i+2] > 220) d[i+3] = 0; } osCtx.putImageData(id, 0, 0); callback(os); };
    }
    lataImg.src = 'assets/sprites/lata.png'; processImageTransparency(lataImg, (c) => processedLata = c);
    tsinelasImg.src = 'assets/sprites/tsinelas.png'; processImageTransparency(tsinelasImg, (c) => processedTsinelas = c);
    const trapImg = new Image(); trapImg.src = 'assets/sprites/banana_peel.png';
    const dogImg = new Image(); dogImg.src = 'assets/sprites/stray_dog.png';
    let processedTrap = null, processedDog = null; 
    processImageTransparency(trapImg, (c) => { 
        processedTrap = c; 
        const trapIcon = c.cloneNode(); trapIcon.getContext('2d').drawImage(c, 0, 0);
        document.getElementById('trap-icon-container').appendChild(c);
    });
    processImageTransparency(dogImg, (c) => processedDog = c);
    const powerupsImg = new Image(); powerupsImg.src = 'assets/sprites/powerups.png';
    let processedPowerups = null;
    processImageTransparency(powerupsImg, (c) => processedPowerups = c);
    const jeepImg = new Image(); jeepImg.src = 'assets/sprites/jeepney.png';
    let processedJeep = null;
    processImageTransparency(jeepImg, (c) => processedJeep = c);
    const tanodImg = new Image(); tanodImg.src = 'assets/sprites/tanod_body.png';
    let processedTanod = null;
    processImageTransparency(tanodImg, (c) => processedTanod = c);
    const dashIconImg = new Image(); dashIconImg.src = 'assets/sprites/dash_icon.png';
    processImageTransparency(dashIconImg, (c) => {
        document.getElementById('dash-icon-container').appendChild(c);
    });

    window.showDiff = () => { 
        document.getElementById('step1').style.display = 'none'; 
        document.getElementById('step2').style.display = 'block'; 
        sounds.bgm.play().catch(e => console.log("Audio blocked"));
    };
    window.startGame = (level) => {
        isGameOver = false; 
        currentDifficulty = level; 
        gameStartTime = Date.now(); // Record start time for grace period
        
        p2.bodyLoaded = false; 
        p2.bodyImage.onload = () => { 
            p2.processTransparency(p2.bodyImage, p2.offscreenCanvas, p2.offscreenCtx); 
            p2.bodyLoaded = true; 
        };

        // Initial setup for the round (SET SRC AFTER ONLOAD)
        if (isBossRound) {
            bossWarningTimer = 180; 
            p2.bodyImage.src = 'assets/sprites/tanod_body.png';
            p2.speed = aiConfig[level].speed * 1.3;
            p2.tagRadius = 130;
        } else {
            p2.bodyImage.src = 'assets/sprites/p2_body.png';
            p2.speed = aiConfig[level].speed;
            p2.tagRadius = 95;
        }

        document.getElementById('startOverlay').style.display = 'none'; 
        document.getElementById('difficulty-text').innerText = level.toUpperCase();
        
        if (audioCtx.state === 'suspended') audioCtx.resume();
        sounds.bgm.play().catch(e => {}); 
        
        const indicator = document.getElementById('roundIndicator');
        if (indicator) {
            indicator.innerText = `GAME ${roundCount + 1}`;
            if (isBossRound) {
                indicator.classList.add('boss-tag');
                indicator.innerText += " (BOSS)";
            } else {
                indicator.classList.remove('boss-tag');
            }
        }

        gameLoop();
    };

    function updateAI() {
        if (p2.isStunned || isGameOver) return;
        const config = aiConfig[currentDifficulty];
        
        // Boss Whistle Logic
        if (isBossRound && Math.random() < 0.005) { 
            sounds.play('whistle'); p1.slowTimer = 120; // 2s slow
            shoutText = "PITOOOO!";
            shoutTimer = 120; // 2s display
        }

        // --- AI BALANCE: REACTION & FATIGUE ---
        // AI occasionally "blinks" or reacts slower
        if (Math.random() < (1 - config.reaction) * 0.05) return; 

        // AI gets tired after long chases
        if (!lata.isDown && p1.x < SAFE_LINE_X) {
            aiFatigue = Math.min(aiFatigue + 1, aiMaxFatigue);
        } else {
            aiFatigue = Math.max(aiFatigue - 2, 0);
        }
        const fatigueSlowdown = (aiFatigue / aiMaxFatigue) * 2;
        const currentSpeed = p2.speed - fatigueSlowdown;

        p2.velocityX = 0; p2.velocityY = 0; const p2cX = p2.x + p2.width / 2, p2cY = p2.y + p2.height / 2;
        
        // --- BOSS SPECIFIC BEHAVIORS ---
        if (isBossRound && !p2.isStunned) {
            // 1. Confiscate Slipper Logic (Prioritize slippers on ground)
            const targetSlipper = projectiles.find(p => p.onGround && p.active);
            if (targetSlipper) {
                const dx = targetSlipper.x - p2cX, dy = targetSlipper.y - p2cY, dist = Math.sqrt(dx*dx + dy*dy);
                if (dist > 25) { p2.velocityX = (dx / dist) * currentSpeed; p2.velocityY = (dy / dist) * currentSpeed; }
                else { targetSlipper.active = false; showCombo("KUMPISKA!"); sounds.play('hit'); return; }
            }
            
            // 2. Shout Stun (Sermon)
            if (Math.random() < 0.003) {
                const shouts = ["BAWAL TAMBAY!", "UWI NA!", "PUNYETA!", "HOY ADIK!", "PAGONG!", "SAAN KA PUPUNTA?!"];
                shoutText = shouts[Math.floor(Math.random()*shouts.length)];
                shoutTimer = 90; // 1.5s display
                const dist = Math.sqrt((p1.x-p2.x)**2 + (p1.y-p2.y)**2);
                if (dist < 300) p1.stun(45); // Short confusion stun
            }
        }

        if (lata.isDown) {
            if (currentDifficulty !== 'hard' && aiDecisionTimer < config.decisionDelay) { aiDecisionTimer++; return; }
            if (!lata.isBeingCarried) { const dx = lata.x - p2cX, dy = lata.y - p2cY, dist = Math.sqrt(dx*dx + dy*dy); if (dist > 25) { p2.velocityX = (dx / dist) * currentSpeed; p2.velocityY = (dy / dist) * currentSpeed; } else { lata.isBeingCarried = true; aiDecisionTimer = 0; } }
            else { const dx = lata.startX - p2cX, dy = lata.startY - p2cY, dist = Math.sqrt(dx*dx + dy*dy); if (dist > 25) { p2.velocityX = (dx / dist) * currentSpeed; p2.velocityY = (dy / dist) * currentSpeed; } else { lata.isDown = false; lata.isBeingCarried = false; lata.x = lata.startX; lata.y = lata.startY; lata.z = 0; aiDecisionTimer = 0; } }
        } else {
            const p1cX = p1.x + p1.width / 2, p1cY = p1.y + p1.height / 2; const dx = p1cX - p2cX, dy = p1cY - p2cY, dist = Math.sqrt(dx*dx + dy*dy);
            if (p1.x < SAFE_LINE_X && dist < config.chaseRange) { p2.velocityX = (dx / dist) * currentSpeed; p2.velocityY = (dy / dist) * currentSpeed; }
            else { const gdx = (lata.startX - 150) - p2cX, gdy = (lata.startY + Math.sin(Date.now()/400) * 80) - p2cY; const gdist = Math.sqrt(gdx*gdx + gdy*gdy); if (gdist > 20) { p2.velocityX = (gdx / gdist) * (currentSpeed * 0.7); p2.velocityY = (gdy / gdist) * (currentSpeed * 0.7); } }
        }
    }

    let isAiming = false, dragStartX = 0, dragStartY = 0, dragX = 0, dragY = 0;
    
    function startAim(e) {
        if (tsinelasCount <= 0 || p1.isStunned || isGameOver) return;
        const pos = getMousePos(e);
        if (Math.sqrt((pos.x - (p1.x + p1.width/2))**2 + (pos.y - (p1.y + p1.height/2))**2) < 120) { 
            isAiming = true; dragStartX = p1.x + p1.width/2; dragStartY = p1.y + p1.height/2; 
            dragX = pos.x; dragY = pos.y; 
        }
    }
    function moveAim(e) { if (isAiming) { const pos = getMousePos(e); dragX = pos.x; dragY = pos.y; } }
    function endAim(e) {
        if (!isAiming) return; isAiming = false;
        const dx = dragStartX - dragX, dy = dragStartY - dragY, dist = Math.min(Math.sqrt(dx*dx + dy*dy), 150);
        const angle = Math.atan2(dy, dx), diff = Math.abs((window.currentNeedleAngle || 0) - Math.PI * 1.5);
        if (dist > 10) { 
            let finalAngle = angle, deviation = 0;
            if (diff < 0.1) { deviation = 0; finalAngle = Math.atan2(lata.y - (p1.y+p1.height/2), lata.x - (p1.x+p1.width/2)); } 
            else if (diff < 0.22) { deviation = (Math.random()-0.5)*0.15; } 
            else { deviation = (Math.random() > 0.5 ? 1 : -1) * (0.6 + Math.random() * 0.4); showCombo("BANOOOO!"); } 
            const power = (dist / 150) * 35 + 5;
            p1.triggerThrow(p1.x + dx, p1.y + dy); 
            sounds.play('throw');
            projectiles.push(new Projectile(p1.x+p1.width/2, p1.y+p1.height/2, Math.cos(finalAngle+deviation)*power, Math.sin(finalAngle+deviation)*power, processedTsinelas || tsinelasImg)); tsinelasCount--; document.getElementById('tsinelas-count').innerText = tsinelasCount; 
        }
    }

    canvas.addEventListener('mousedown', startAim);
    window.addEventListener('mousemove', moveAim);
    window.addEventListener('mouseup', endAim);
    
    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); startAim(e); }, {passive: false});
    window.addEventListener('touchmove', (e) => { e.preventDefault(); moveAim(e); }, {passive: false});
    window.addEventListener('touchend', endAim);

    // Mobile Joystick Logic
    let joystickActive = false, joystickX = 0, joystickY = 0;
    const joystick = document.getElementById('joystick-container'), knob = document.getElementById('joystick-knob');
    
    function handleJoystick(e) {
        if (!joystickActive) return;
        const rect = joystick.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        let dx = clientX - (rect.left + rect.width / 2);
        let dy = clientY - (rect.top + rect.height / 2);
        const dist = Math.min(Math.sqrt(dx*dx + dy*dy), rect.width / 2);
        const angle = Math.atan2(dy, dx);
        joystickX = Math.cos(angle) * (dist / (rect.width/2));
        joystickY = Math.sin(angle) * (dist / (rect.width/2));
        knob.style.transform = `translate(calc(-50% + ${Math.cos(angle) * dist}px), calc(-50% + ${Math.sin(angle) * dist}px))`;
    }

    joystick.addEventListener('touchstart', (e) => { joystickActive = true; handleJoystick(e); });
    window.addEventListener('touchmove', handleJoystick);
    window.addEventListener('touchend', () => { joystickActive = false; joystickX = 0; joystickY = 0; knob.style.transform = 'translate(-50%, -50%)'; });

    // Clickable Skills for Mobile
    document.getElementById('skill-dash').addEventListener('click', () => { if (p1.dash()) showCombo("RATSADA!"); });
    document.getElementById('skill-trap').addEventListener('click', () => {
        if (trapsLeft > 0 && !traps.some(t => Math.sqrt((t.x-p1.x)**2 + (t.y-p1.y)**2) < 50)) {
            traps.push({ x: p1.x + p1.width/2, y: p1.y + p1.height - 10, active: true });
            trapsLeft--; trapCooldown = 30; document.getElementById('trap-count').innerText = trapsLeft; showCombo("BALAT NG SAGING!");
        }
    });

    function showCombo(text) { 
        const el = document.getElementById('comboText'); 
        if (el) { 
            const trashTalks = {
                "BANOOOO!": ["DULENG!", "TAPON ANG SABAW!", "BANO!"],
                "SAPUL!": ["LOPET!", "AGNAS!!", "PETMALU!"],
                "NADULAS! HAHA!": ["ARAY KO!", "BAGSAK!!"],
                "RATSADA!": ["BILIS!", "KILAT!", "TAKBO!", "WASH!", "HARING KALSADA!"]
            };
            let displayMsg = text;
            if (trashTalks[text]) displayMsg = trashTalks[text][Math.floor(Math.random() * trashTalks[text].length)];
            
            // Auto-resize for long phrases
            const isMobile = window.innerWidth <= 1024;
            if (displayMsg.length > 10) {
                el.style.fontSize = isMobile ? '2.5rem' : '4.5rem';
            } else {
                el.style.fontSize = isMobile ? '4rem' : '8rem';
            }

            el.innerText = displayMsg; el.classList.add('active'); 
            setTimeout(() => el.classList.remove('active'), 1000); 
        } 
    }

    function updateDogs() {
        if (isGameOver) return;
        const now = Date.now();
        // Spawn Logic (After 15s, with 5s gap between any spawn)
        if (now - gameStartTime > 15000 && now - globalSpawnTimer > 5000 && now - lastDogSpawn > 20000 + Math.random() * 15000) {
            const side = Math.random() > 0.5 ? 'left' : 'right';
            dogs.push({ 
                x: side === 'left' ? -150 : canvas.width + 150, 
                y: 350 + Math.random() * 450, 
                vx: side === 'left' ? 7.5 : -7.5, 
                active: true, 
                damaged: {p1:false, p2:false},
                hasBarked: false 
            });
            lastDogSpawn = now;
            globalSpawnTimer = now;
        }
        
        for (let i = dogs.length - 1; i >= 0; i--) {
            const d = dogs[i]; d.x += d.vx;
            
            // Bark only when entering the screen (between 150 and 50 pixels from edge)
            if (!d.hasBarked && ((d.vx > 0 && d.x > -50) || (d.vx < 0 && d.x < canvas.width + 50))) {
                sounds.play('bark');
                d.hasBarked = true;
            }

            if (processedDog) {
                ctx.save(); ctx.translate(d.x, d.y); ctx.scale(d.vx > 0 ? 1 : -1, 1);
                ctx.drawImage(processedDog, -40, -40, 80, 80); ctx.restore();
            } else { ctx.fillStyle = 'brown'; ctx.fillRect(d.x-20, d.y-20, 40, 40); }
            
            // Collisions
            if (!d.damaged.p1 && !p1.isInvincible && Math.sqrt((d.x - (p1.x+p1.width/2))**2 + (d.y - (p1.y+p1.height/2))**2) < 60) { 
                p1.takeDamage(10); p1.stun(60); d.damaged.p1 = true; showCombo("KAGAT!"); 
            }
            if (!d.damaged.p2 && Math.sqrt((d.x - (p2.x+p2.width/2))**2 + (d.y - (p2.y+p2.height/2))**2) < 60) { 
                p2.takeDamage(10); p2.stun(60, 0.4); d.damaged.p2 = true; showCombo("AW-AW!"); 
            }
            if (Math.sqrt((d.x - lata.x)**2 + (d.y - lata.y)**2) < 50 && !lata.isDown) { lata.hit(d.vx*2, 0); showCombo("LATA GULO!"); }
            
            if (d.x < -200 || d.x > canvas.width + 200) dogs.splice(i, 1);
        }
    }

    function updatePowerups() {
        if (isGameOver) return;
        const now = Date.now();
        // After 10s, with 5s gap
        if (now - gameStartTime > 10000 && now - globalSpawnTimer > 5000 && now - lastPowerSpawn > 25000 + Math.random() * 20000) {
            const types = ['sili', 'isaw', 'agimat'];
            powerups.push({ x: 100 + Math.random() * 1200, y: 350 + Math.random() * 450, type: types[Math.floor(Math.random() * 3)], spawnTime: Date.now() });
            lastPowerSpawn = now;
            globalSpawnTimer = now;
        }
        for (let i = powerups.length - 1; i >= 0; i--) {
            const p = powerups[i];
            const age = Date.now() - p.spawnTime;
            if (age > 10000) { powerups.splice(i, 1); continue; }
            
            // Draw
            ctx.save(); ctx.translate(p.x, p.y);
            const bounce = Math.sin(Date.now() / 200) * 10;
            const pulse = 1 + Math.sin(Date.now() / 150) * 0.1;
            ctx.scale(pulse, pulse);
            
            // Glow
            const colors = { sili: 'rgba(255,0,0,0.5)', isaw: 'rgba(255,204,0,0.5)', agimat: 'rgba(0,255,255,0.5)' };
            ctx.shadowBlur = 15; ctx.shadowColor = colors[p.type];
            
            if (processedPowerups) {
                const sw = processedPowerups.width / 3;
                const sx = p.type === 'sili' ? 0 : (p.type === 'isaw' ? sw : sw * 2);
                ctx.drawImage(processedPowerups, sx, 0, sw, processedPowerups.height, -30, -30 + bounce, 60, 60);
            } else { ctx.fillStyle = colors[p.type]; ctx.beginPath(); ctx.arc(0, bounce, 25, 0, Math.PI*2); ctx.fill(); }
            ctx.restore();

            // Collision with P1
            const dist = Math.sqrt((p.x - (p1.x+p1.width/2))**2 + (p.y - (p1.y+p1.height/2))**2);
            if (dist < 60) {
                if (p.type === 'sili') { p1.speed = 15; p1.speedTimer = 420; showCombo("ANASANG!"); }
                else if (p.type === 'isaw') { p1.health = Math.min(p1.health + 25, 100); showCombo("SOLVE!"); }
                else if (p.type === 'agimat') { p1.isInvincible = true; p1.invinceTimer = 420; showCombo("BALAT-SIBUNYAS!"); }
                powerups.splice(i, 1); sounds.play('hit');
            }
        }
    }

    function updateVehicles() {
        if (isGameOver) return;
        const now = Date.now();
        // After 25s, with 5s gap
        if (now - gameStartTime > 25000 && !vehicleWarning && now - globalSpawnTimer > 5000 && now - lastVehicleSpawn > 40000 + Math.random() * 20000) {
            const side = Math.random() > 0.5 ? 'left' : 'right';
            vehicleWarning = { side, startTime: now };
        }
        
        if (vehicleWarning) {
            const warningAge = now - vehicleWarning.startTime;
            if (warningAge > 3000) { // After 3s warning, spawn
                vehicles.push({ 
                    x: vehicleWarning.side === 'left' ? -400 : canvas.width + 400, 
                    y: 450 + Math.random() * 200, 
                    vx: vehicleWarning.side === 'left' ? 8.5 : -8.5, 
                    width: 350, height: 180,
                    damaged: {p1:false, p2:false, lata:false},
                    hasHorned: false
                });
                vehicleWarning = null;
                lastVehicleSpawn = now;
                globalSpawnTimer = now;
            } else {
                // Draw Warning Icon
                ctx.save();
                const alpha = Math.abs(Math.sin(now / 150));
                ctx.fillStyle = `rgba(255, 0, 0, ${alpha})`;
                ctx.font = 'bold 80px Arial';
                const wx = vehicleWarning.side === 'left' ? 50 : canvas.width - 130;
                ctx.fillText('⚠️', wx, 500);
                ctx.restore();
            }
        }

        for (let i = vehicles.length - 1; i >= 0; i--) {
            const v = vehicles[i]; v.x += v.vx;
            
            // Horn when entering screen
            if (!v.hasHorned && ((v.vx > 0 && v.x > -300) || (v.vx < 0 && v.x < canvas.width + 300))) {
                sounds.play('horn');
                v.hasHorned = true;
            }

            // Draw Jeepney
            ctx.save(); 
            const bounce = Math.sin(Date.now() / 60) * 4;
            ctx.translate(v.x, v.y + bounce);
            if (v.vx > 0) ctx.scale(-1, 1); // Flip if moving right (assuming sprite faces left)
            if (processedJeep) {
                ctx.drawImage(processedJeep, -v.width/2, -v.height/2, v.width, v.height);
            } else {
                ctx.fillStyle = 'blue'; ctx.fillRect(-v.width/2, -v.height/2, v.width, v.height);
            }
            ctx.restore();

            // Collision with P1
            if (!v.damaged.p1 && !p1.isInvincible && Math.abs(v.x - (p1.x+p1.width/2)) < 150 && Math.abs(v.y - (p1.y+p1.height/2)) < 80) {
                p1.takeDamage(25); p1.stun(120, 0.5); v.damaged.p1 = true;
                showCombo("PARPARA!"); sounds.play('hit');
            }
            // Collision with P2
            if (!v.damaged.p2 && Math.abs(v.x - (p2.x+p2.width/2)) < 150 && Math.abs(v.y - (p2.y+p2.height/2)) < 80) {
                p2.takeDamage(25); p2.stun(120, -0.5); v.damaged.p2 = true;
                showCombo("TABI!"); sounds.play('hit');
            }
            // Collision with Lata
            if (!v.damaged.lata && Math.abs(v.x - lata.x) < 150 && Math.abs(v.y - lata.y) < 80) {
                lata.hit(v.vx * 1.5, (Math.random()-0.5)*10); v.damaged.lata = true;
                showCombo("LATA GULO!");
            }

            if (v.x < -1000 || v.x > canvas.width + 1000) vehicles.splice(i, 1);
        }
    }

    function gameLoop() {
        if (isGameOver) return; ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        if (bossWarningTimer > 0) {
            bossWarningTimer--;
            ctx.fillStyle = bossWarningTimer % 20 < 10 ? 'rgba(255, 0, 0, 0.4)' : 'rgba(0, 0, 0, 0.6)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'white'; ctx.font = 'bold 80px Courier New'; ctx.textAlign = 'center';
            ctx.fillText("PATAY! MAY TANOD!", canvas.width/2, canvas.height/2);
            ctx.font = '40px Courier New'; ctx.fillText("BOSS ROUND INCOMING", canvas.width/2, canvas.height/2 + 80);
            ctx.textAlign = 'start';
            if (bossWarningTimer === 170) sounds.play('whistle');
            requestAnimationFrame(gameLoop); return;
        }
        
        // --- NO CIRCLE DRAWING (USE BACKGROUND IMAGE ONE) ---
        // I will draw it ONLY if it's very faint, but for now let's see if the alignment works.
        // If I draw it, I must align it to 415, 600.
        
        p1.velocityX = 0; p1.velocityY = 0;
        if (input.isPressed('KeyW') || joystickY < -0.3) p1.velocityY = -p1.speed; 
        if (input.isPressed('KeyS') || joystickY > 0.3) p1.velocityY = p1.speed; 
        if (input.isPressed('KeyA') || joystickX < -0.3) p1.velocityX = -p1.speed; 
        if (input.isPressed('KeyD') || joystickX > 0.3) p1.velocityX = p1.speed; 
        if (input.isPressed('Space')) p1.jump();
        
        // --- SKILLS INPUT ---
        if (input.isPressed('ShiftLeft')) { if (p1.dash()) showCombo("RATSADA!"); }
        if (input.isPressed('KeyE') && trapCooldown <= 0) {
            if (trapsLeft > 0 && !traps.some(t => Math.sqrt((t.x-p1.x)**2 + (t.y-p1.y)**2) < 50)) {
                traps.push({ x: p1.x + p1.width/2, y: p1.y + p1.height - 10, active: true });
                trapsLeft--; trapCooldown = 30; // 0.5s cooldown
                document.getElementById('trap-count').innerText = trapsLeft;
                showCombo("BALAT NG SAGING!");
            }
        }
        if (trapCooldown > 0) trapCooldown--;

        updateDogs();
        updatePowerups();
        updateVehicles();
        updateAI(); lata.update(); p1.update(canvas.width, canvas.height); p2.update(canvas.width, canvas.height);
        
        // Update Skill UI
        document.getElementById('dash-cd-overlay').style.height = (p1.dashCooldown / 120) * 100 + '%';
        
        // Draw Traps
        traps.forEach(t => {
            ctx.save();
            ctx.translate(t.x, t.y);
            if (!t.rotation) t.rotation = Math.random() * Math.PI * 2;
            ctx.rotate(t.rotation);

            // Shadow
            ctx.fillStyle = 'rgba(0,0,0,0.3)';
            ctx.beginPath(); ctx.ellipse(0, 5, 20, 10, 0, 0, Math.PI*2); ctx.fill();

            if (processedTrap) {
                ctx.drawImage(processedTrap, -25, -25, 50, 50);
            } else {
                ctx.fillStyle = 'yellow'; ctx.fillRect(-15, -5, 30, 10);
            }
            ctx.restore();
            
            // Check Collision with P2
            if (t.active && Math.sqrt((t.x - (p2.x+p2.width/2))**2 + (t.y - (p2.y+p2.height/2))**2) < 40) {
                t.active = false; traps = traps.filter(tr => tr !== t);
                p2.stun(180, Math.PI / 2); // 90 degree tilt (fell down)
                sounds.play('slip'); 
                showCombo("NADULAS! HAHA!");
            }
        });
        
        if (isAiming) {
            const dx = dragStartX - dragX, dy = dragStartY - dragY, dist = Math.min(Math.sqrt(dx*dx + dy*dy), 150); const angle = Math.atan2(dy, dx);
            const gX = p1.x + p1.width/2, gY = p1.y + p1.height/2 - 160, gR = 65;
            ctx.lineWidth = 18; ctx.setLineDash([12, 4]); // Dotted arcade style
            
            // --- DRAW SEGMENTS SEPARATELY TO AVOID COLOR HALOS ---
            // Red Sides
            ctx.strokeStyle = 'rgba(244,67,54,0.6)'; 
            ctx.beginPath(); ctx.arc(gX, gY, gR, Math.PI*0.8, Math.PI*1.3); ctx.stroke();
            ctx.beginPath(); ctx.arc(gX, gY, gR, Math.PI*1.7, Math.PI*2.2); ctx.stroke();
            
            // Yellow Sides
            ctx.strokeStyle = '#ffeb3b'; 
            ctx.beginPath(); ctx.arc(gX, gY, gR, Math.PI*1.3, Math.PI*1.42); ctx.stroke();
            ctx.beginPath(); ctx.arc(gX, gY, gR, Math.PI*1.58, Math.PI*1.7); ctx.stroke();
            
            // Cyan Center (Perfect)
            ctx.strokeStyle = '#00ffff'; 
            ctx.beginPath(); ctx.arc(gX, gY, gR, Math.PI*1.42, Math.PI*1.58); ctx.stroke();
            
            const needleAngle = Math.PI * 1.5 + Math.sin(Date.now() / 150) * (Math.PI * 0.6);
            ctx.fillStyle = 'white'; ctx.beginPath(); ctx.arc(gX + Math.cos(needleAngle)*gR, gY + Math.sin(needleAngle)*gR, 12, 0, Math.PI*2); ctx.fill();
            window.currentNeedleAngle = needleAngle; ctx.setLineDash([10, 10]); ctx.strokeStyle = 'rgba(255,204,0,0.6)'; ctx.lineWidth = 4;
            ctx.beginPath(); ctx.moveTo(p1.x+p1.width/2, p1.y+p1.height/2); ctx.lineTo(p1.x+p1.width/2 + Math.cos(angle)*dist*7, p1.y+p1.height/2 + Math.sin(angle)*dist*7); ctx.stroke();
        }

        if (pendingScore && slipperRecovered && p1.x >= SAFE_LINE_X) { 
            lataScore++; pendingScore = false; slipperRecovered = false; 
            document.getElementById('current-score').innerText = `${lataScore} / 3`; 
        }
        for (let i = projectiles.length - 1; i >= 0; i--) {
            const p = projectiles[i];
            p.update(canvas.width, canvas.height); p.draw(ctx);
            if (p.onGround && Math.sqrt((p1.x+p1.width/2-p.x)**2 + (p1.y+p1.height/2-p.y)**2) < 110) { p.active = false; tsinelasCount++; document.getElementById('tsinelas-count').innerText = tsinelasCount; if (pendingScore) slipperRecovered = true; }
            if (p.active && !lata.isDown && Math.sqrt((p.x-lata.x)**2 + (p.y-lata.y)**2) < 90) { 
                lata.hit(p.velocityX || 15, p.velocityY || 15); 
                p.onGround = true; p.velocityX = 0; p.velocityY = 0; p.z = 0;
                sounds.play('hit');
                p2.stun(140, 0.2); // Small tilt for hit stun
                pendingScore = true; slipperRecovered = false;
                showCombo("SAPUL!");
            }
            if (p.active && !p.onGround && Math.sqrt((p.x-(p2.x+p2.width/2))**2 + (p.y-(p2.y+p2.height/2))**2) < 85) { 
                p2.takeDamage(15); p2.stun(170, 0.3); // Small tilt for hit stun
                sounds.play('hit');
                if (lata.isBeingCarried) { lata.isBeingCarried = false; lata.hit(p.velocityX*0.6, p.velocityY*0.6); } 
                p.active = false; 
                showCombo("SAPUL! KA BOI!");
            }
            if (!p.active) projectiles.splice(i, 1);
        }
        if (!lata.isDown && !lata.isBeingCarried && !p2.isStunned && p1.x < SAFE_LINE_X && !p1.isInvincible && Math.sqrt((p1.x-p2.x)**2 + (p1.y-p2.y)**2) < (isBossRound ? 130 : 95)) showFinalResult("NA-TAG KA!");
        lata.draw(ctx); p1.draw(ctx); p2.draw(ctx); 
        
        // --- DRAW SHOUT TEXT ABOVE TANOD ---
        if (shoutTimer > 0) {
            shoutTimer--;
            ctx.save();
            ctx.fillStyle = 'white'; ctx.strokeStyle = 'black'; ctx.lineWidth = 4;
            ctx.font = 'bold 24px Arial'; ctx.textAlign = 'center';
            const tx = p2.x + p2.width/2, ty = p2.y - 30;
            // Bubble-like background
            ctx.fillStyle = 'rgba(0,0,0,0.7)';
            const tw = ctx.measureText(shoutText).width + 20;
            ctx.fillRect(tx - tw/2, ty - 30, tw, 40);
            ctx.fillStyle = 'white';
            ctx.strokeText(shoutText, tx, ty);
            ctx.fillText(shoutText, tx, ty);
            ctx.restore();
        }

        document.getElementById('p1-health').style.width = p1.health + '%'; document.getElementById('p2-health').style.width = p2.health + '%';
        if (!isGameOver) { if (p2.health <= 0 || lataScore >= 3) showFinalResult("VICTORY!"); else if (p1.health <= 0) showFinalResult("TAYÂ WINS!"); else if (tsinelasCount === 0 && projectiles.length === 0 && !lata.isDown) showFinalResult("NAUBUSAN KA!"); }
        if (!isGameOver) requestAnimationFrame(gameLoop);
    }
    function showFinalResult(title) { 
        isGameOver = true; 
        document.getElementById('winnerTitle').innerText = title; 
        
        if (title === "VICTORY!") roundCount++; // Increment games won

        // Calculate Rank
        let rank = "C";
        if (title === "VICTORY!") {
            if (p1.health === 100) rank = "S";
            else if (p1.health > 70) rank = "A";
            else rank = "B";
        } else {
            rank = "F";
        }
        
        document.getElementById('rankBadge').innerText = rank;
        document.getElementById('finalScoreText').innerText = `${lataScore} / ${3 - Math.floor(p2.health/33)}`;
        
        // Update Button Text & Action
        const resultBtn = document.getElementById('resultBtn');
        if (resultBtn) {
            const nextRound = (title === "VICTORY!") ? roundCount + 1 : 0;
            resultBtn.innerText = (title === "VICTORY!") ? "NEXT ROUND" : "TRY AGAIN";
            resultBtn.onclick = () => { 
                const levelParam = (title === "VICTORY!") ? "&level=" + currentDifficulty : "";
                window.location.href = window.location.pathname + "?round=" + nextRound + "&next=true" + levelParam;
            };
        }
        
        document.getElementById('resultOverlay').style.display = 'flex'; 
    }
</script>
</body>
</html>
