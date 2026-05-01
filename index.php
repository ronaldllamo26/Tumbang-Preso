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
        body { background: #000; color: #fff; font-family: 'Outfit', sans-serif; margin: 0; overflow: hidden; height: 100vh; display: flex; justify-content: center; align-items: center; }
        
        /* --- ARCADE BOX --- */
        #gameWrapper {
            position: relative;
            width: 1400px;
            height: 900px;
            background: #000 url('assets/sprites/top_down_bg.png') no-repeat center center;
            background-size: 100% 100%;
            overflow: hidden;
            box-shadow: 0 0 100px rgba(0,0,0,1), 0 0 20px rgba(255,204,0,0.2);
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
    </style>
</head>
<body>

<!-- Pre-load stable sounds -->
<audio id="audioThrow" src="https://www.soundjay.com/button/sounds/button-20.mp3" preload="auto"></audio>
<audio id="audioHit" src="https://www.soundjay.com/button/sounds/button-10.mp3" preload="auto"></audio>
<audio id="audioBGM" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-8.mp3" preload="auto" loop></audio>

<button onclick="toggleMute()" style="position:fixed; top:20px; right:350px; z-index:3000; background:rgba(0,0,0,0.5); border:none; color:white; font-size:2rem; cursor:pointer; border-radius:50%; width:60px; height:60px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(10px);" id="muteIcon">🔊</button>
<div id="gameWrapper">
    <div id="comboText">SAPUL!</div>
    <canvas id="gameCanvas"></canvas>

    <div class="hud-card p1-panel">
        <div class="label-text" style="color: var(--p1-blue);">P1: BATU-BATO</div>
        <div class="progress mb-2"><div id="p1-health" class="progress-bar bg-primary" style="width: 100%"></div></div>
        <div class="small text-uppercase opacity-75">TSINELAS: <span id="tsinelas-count">10</span></div>
    </div>

    <div class="hud-card score-panel">
        <h1 class="main-title">TUMBANG PRESO</h1>
        <div class="score-display" id="current-score">0 / 3</div>
    </div>

    <div class="hud-card p2-panel">
        <div class="label-text" style="color: var(--p2-red);">P2: TAYÂ (BOT)</div>
        <div class="progress mb-2"><div id="p2-health" class="progress-bar bg-danger" style="width: 100%"></div></div>
        <div class="small text-uppercase opacity-75">LVL: <span id="difficulty-text">MEDIUM</span></div>
    </div>

    <!-- Start Overlay -->
    <div id="startOverlay" class="full-overlay">
        <h1 class="main-title" style="font-size: 6rem;">TUMBANG PRESO</h1>
        <div id="step1"><button onclick="showDiff()" class="btn-arcade">START LARO!</button></div>
        <div id="step2" style="display: none; text-align: center;">
            <div class="d-flex gap-3 justify-content-center">
                <button onclick="startGame('easy')" class="btn-arcade" style="background:#4caf50; font-size:1.5rem;">EASY</button>
                <button onclick="startGame('medium')" class="btn-arcade" style="background:#ff9800; font-size:1.5rem;">MED</button>
                <button onclick="startGame('hard')" class="btn-arcade" style="background:#f44336; font-size:1.5rem;">HARD</button>
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

        <button onclick="location.reload()" class="btn-arcade">TRY AGAIN!</button>
    </div>
</div>

<script type="module">
    import Player from './game_engine/Player.js';
    import Projectile from './game_engine/Projectile.js';
    import input from './game_engine/Input.js';

    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 1400; canvas.height = 900;

    let isGameOver = false, tsinelasCount = 10, lataScore = 0;
    let pendingScore = false, slipperRecovered = false, projectiles = [];
    let currentDifficulty = 'medium', aiDecisionTimer = 0;

    const aiConfig = {
        easy: { speed: 4, reaction: 0.1, chaseRange: 500, decisionDelay: 60 },
        medium: { speed: 6, reaction: 0.4, chaseRange: 900, decisionDelay: 20 },
        hard: { speed: 8.5, reaction: 0.8, chaseRange: 2000, decisionDelay: 0 }
    };

    let processedLata = null, processedTsinelas = null;
    const lataImg = new Image(), tsinelasImg = new Image();
    const sounds = { 
        throw: document.getElementById('audioThrow'), 
        hit: document.getElementById('audioHit'),
        bgm: document.getElementById('audioBGM')
    };
    sounds.bgm.volume = 0.3;
    let isMuted = false;
    window.toggleMute = () => { 
        isMuted = !isMuted; 
        for(let s in sounds) sounds[s].muted = isMuted;
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
        img.onload = () => { os.width = img.width; os.height = img.height; osCtx.drawImage(img, 0, 0); const id = osCtx.getImageData(0, 0, os.width, os.height); const d = id.data; for (let i = 0; i < d.length; i += 4) { if (d[i] > 240 && d[i+1] > 240 && d[i+2] > 240) d[i+3] = 0; } osCtx.putImageData(id, 0, 0); callback(os); };
    }
    lataImg.src = 'assets/sprites/lata.png'; processImageTransparency(lataImg, (c) => processedLata = c);
    tsinelasImg.src = 'assets/sprites/tsinelas.png'; processImageTransparency(tsinelasImg, (c) => processedTsinelas = c);

    window.showDiff = () => { 
        document.getElementById('step1').style.display = 'none'; 
        document.getElementById('step2').style.display = 'block'; 
        sounds.bgm.play().catch(e => console.log("Audio blocked"));
    };
    window.startGame = (level) => {
        isGameOver = false; lataScore = 0; tsinelasCount = 10; p1.health = 100; p2.health = 100;
        lata.x = CIRCLE_X; lata.y = CIRCLE_Y; lata.z = 0; lata.isDown = false; lata.isBeingCarried = false;
        currentDifficulty = level; p2.speed = aiConfig[level].speed;
        document.getElementById('difficulty-text').innerText = level.toUpperCase();
        document.getElementById('startOverlay').style.display = 'none'; 
        sounds.bgm.play().catch(e => {}); // Double check play on game start
        gameLoop();
    };

    function updateAI() {
        if (p2.isStunned || isGameOver) return;
        const config = aiConfig[currentDifficulty];
        p2.velocityX = 0; p2.velocityY = 0; const p2cX = p2.x + p2.width / 2, p2cY = p2.y + p2.height / 2;
        if (lata.isDown) {
            if (currentDifficulty !== 'hard' && aiDecisionTimer < config.decisionDelay) { aiDecisionTimer++; return; }
            if (!lata.isBeingCarried) { const dx = lata.x - p2cX, dy = lata.y - p2cY, dist = Math.sqrt(dx*dx + dy*dy); if (dist > 25) { p2.velocityX = (dx / dist) * p2.speed; p2.velocityY = (dy / dist) * p2.speed; } else { lata.isBeingCarried = true; aiDecisionTimer = 0; } }
            else { const dx = lata.startX - p2cX, dy = lata.startY - p2cY, dist = Math.sqrt(dx*dx + dy*dy); if (dist > 25) { p2.velocityX = (dx / dist) * p2.speed; p2.velocityY = (dy / dist) * p2.speed; } else { lata.isDown = false; lata.isBeingCarried = false; lata.x = lata.startX; lata.y = lata.startY; lata.z = 0; aiDecisionTimer = 0; } }
        } else {
            const p1cX = p1.x + p1.width / 2, p1cY = p1.y + p1.height / 2; const dx = p1cX - p2cX, dy = p1cY - p2cY, dist = Math.sqrt(dx*dx + dy*dy);
            if (p1.x < SAFE_LINE_X && dist < config.chaseRange) { p2.velocityX = (dx / dist) * p2.speed; p2.velocityY = (dy / dist) * p2.speed; }
            else { const gdx = (lata.startX - 150) - p2cX, gdy = (lata.startY + Math.sin(Date.now()/400) * 80) - p2cY; const gdist = Math.sqrt(gdx*gdx + gdy*gdy); if (gdist > 20) { p2.velocityX = (gdx / gdist) * (p2.speed * 0.7); p2.velocityY = (gdy / gdist) * (p2.speed * 0.7); } }
        }
    }

    let isAiming = false, dragStartX = 0, dragStartY = 0, dragX = 0, dragY = 0;
    canvas.addEventListener('mousedown', (e) => {
        if (tsinelasCount <= 0 || p1.isStunned || isGameOver) return;
        const rect = canvas.getBoundingClientRect(); const mx = (e.clientX - rect.left) * (1400 / rect.width), my = (e.clientY - rect.top) * (900 / rect.height);
        if (Math.sqrt((mx - (p1.x + p1.width/2))**2 + (my - (p1.y + p1.height/2))**2) < 120) { isAiming = true; dragStartX = p1.x + p1.width/2; dragStartY = p1.y + p1.height/2; dragX = mx; dragY = my; }
    });
    window.addEventListener('mousemove', (e) => { if (isAiming) { const rect = canvas.getBoundingClientRect(); dragX = (e.clientX - rect.left) * (1400 / rect.width); dragY = (e.clientY - rect.top) * (900 / rect.height); } });
    window.addEventListener('mouseup', (e) => {
        if (!isAiming) return; isAiming = false;
        const dx = dragStartX - dragX, dy = dragStartY - dragY, dist = Math.min(Math.sqrt(dx*dx + dy*dy), 150);
        const angle = Math.atan2(dy, dx), diff = Math.abs((window.currentNeedleAngle || 0) - Math.PI * 1.5);
        if (dist > 10) { 
            let finalAngle = angle;
            let deviation = 0;
            
            // --- CONSISTENCY LOGIC: GUARANTEED HIT ON CYAN ---
            if (diff < 0.1) { 
                deviation = 0; 
                // SNAP directly to lata for a guaranteed hit
                finalAngle = Math.atan2(lata.y - (p1.y+p1.height/2), lata.x - (p1.x+p1.width/2));
            } 
            else if (diff < 0.22) { deviation = (Math.random()-0.5)*0.15; } 
            else { deviation = (Math.random() > 0.5 ? 1 : -1) * (0.6 + Math.random() * 0.4); showCombo("BANOOOO!"); } 

            const power = (dist / 150) * 48 + 5; // Increased power and base
            p1.triggerThrow(p1.x + dx, p1.y + dy); sounds.throw.play(); 
            projectiles.push(new Projectile(p1.x+p1.width/2, p1.y+p1.height/2, Math.cos(finalAngle+deviation)*power, Math.sin(finalAngle+deviation)*power, processedTsinelas || tsinelasImg)); tsinelasCount--; document.getElementById('tsinelas-count').innerText = tsinelasCount; }
    });

    function showCombo(text) { const el = document.getElementById('comboText'); if (el) { el.innerText = text; el.classList.add('active'); setTimeout(() => el.classList.remove('active'), 1000); } }

    function gameLoop() {
        if (isGameOver) return; ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // --- NO CIRCLE DRAWING (USE BACKGROUND IMAGE ONE) ---
        // I will draw it ONLY if it's very faint, but for now let's see if the alignment works.
        // If I draw it, I must align it to 415, 600.
        
        p1.velocityX = 0; p1.velocityY = 0;
        if (input.isPressed('KeyW')) p1.velocityY = -p1.speed; if (input.isPressed('KeyS')) p1.velocityY = p1.speed; if (input.isPressed('KeyA')) p1.velocityX = -p1.speed; if (input.isPressed('KeyD')) p1.velocityX = p1.speed; if (input.isPressed('Space')) p1.jump();
        updateAI(); lata.update(); p1.update(canvas.width, canvas.height); p2.update(canvas.width, canvas.height);
        
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

        if (pendingScore && slipperRecovered && p1.x >= SAFE_LINE_X) { lataScore++; pendingScore = false; slipperRecovered = false; document.getElementById('current-score').innerText = `${lataScore} / 3`; }
        projectiles.forEach((p, index) => {
            p.update(canvas.width, canvas.height); p.draw(ctx);
            if (p.onGround && Math.sqrt((p1.x+p1.width/2-p.x)**2 + (p1.y+p1.height/2-p.y)**2) < 110) { p.active = false; tsinelasCount++; document.getElementById('tsinelas-count').innerText = tsinelasCount; if (pendingScore) slipperRecovered = true; }
            if (p.active && !lata.isDown && Math.sqrt((p.x-lata.x)**2 + (p.y-lata.y)**2) < 90) { 
                lata.hit(p.velocityX || 15, p.velocityY || 15); p.onGround = true; p.velocityX = 0; p.velocityY = 0;
                sounds.hit.currentTime = 0; sounds.hit.play(); 
                p2.stun(140); pendingScore = true; slipperRecovered = false;
                showCombo("SAPUL!");
            }
            if (p.active && !p.onGround && Math.sqrt((p.x-(p2.x+p2.width/2))**2 + (p.y-(p2.y+p2.height/2))**2) < 85) { 
                p2.takeDamage(15); p2.stun(170); 
                sounds.hit.currentTime = 0; sounds.hit.play();
                if (lata.isBeingCarried) { lata.isBeingCarried = false; lata.hit(p.velocityX*0.6, p.velocityY*0.6); } p.active = false; 
                showCombo("SAPUL! KA BOI!");
            }
            if (!p.active) projectiles.splice(index, 1);
        });
        if (!lata.isDown && !lata.isBeingCarried && !p2.isStunned && p1.x < SAFE_LINE_X && Math.sqrt((p1.x-p2.x)**2 + (p1.y-p2.y)**2) < 95) showFinalResult("NA-TAG KA!");
        lata.draw(ctx); p1.draw(ctx); p2.draw(ctx); document.getElementById('p1-health').style.width = p1.health + '%'; document.getElementById('p2-health').style.width = p2.health + '%';
        if (!isGameOver) { if (p2.health <= 0 || lataScore >= 3) showFinalResult("VICTORY!"); else if (p1.health <= 0) showFinalResult("TAYÂ WINS!"); else if (tsinelasCount === 0 && projectiles.length === 0 && !lata.isDown) showFinalResult("NAUBUSAN KA!"); }
        if (!isGameOver) requestAnimationFrame(gameLoop);
    }
    function showFinalResult(title) { 
        isGameOver = true; 
        document.getElementById('winnerTitle').innerText = title; 
        
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
        document.getElementById('resultOverlay').style.display = 'flex'; 
    }
</script>
</body>
</html>
