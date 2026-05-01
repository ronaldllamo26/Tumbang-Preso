<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script type="module">
    import Player from './game_engine/Player.js';
    import Projectile from './game_engine/Projectile.js';
    import input from './game_engine/Input.js';

    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');

    // Difficulty Settings
    let currentDifficulty = 'medium';
    const aiConfig = {
        easy: { speed: 3, reaction: 0.1, chaseRange: 300, decisionDelay: 60 },
        medium: { speed: 5, reaction: 0.4, chaseRange: 600, decisionDelay: 20 },
        hard: { speed: 7, reaction: 0.8, chaseRange: 1200, decisionDelay: 0 }
    };

    let aiDecisionTimer = 0;
    let isGameOver = false;

    window.setDifficulty = (level) => {
        currentDifficulty = level;
        if (p2) p2.speed = aiConfig[level].speed;
    };

    // Audio
    let isMuted = false;
    const sounds = {
        throw: new Audio('https://assets.mixkit.co/sfx/preview/mixkit-arrow-whoosh-1491.mp3'),
        hit: new Audio('https://www.soundjay.com/button/sounds/button-10.mp3'),
        bgm: new Audio('https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3')
    };
    sounds.bgm.loop = true;
    sounds.bgm.volume = 0.3;

    window.toggleMute = () => {
        isMuted = !isMuted;
        sounds.bgm.muted = isMuted;
        sounds.throw.muted = isMuted;
        sounds.hit.muted = isMuted;
        document.querySelectorAll('#muteBtn').forEach(btn => btn.innerText = isMuted ? '🔇' : '🔊');
    };

    // Start Overlay
    const startOverlay = document.createElement('div');
    startOverlay.id = "startOverlay";
    startOverlay.className = "glass-panel";
    startOverlay.style = "position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:1000; color:white; font-family:'Permanent Marker', cursive; border-radius:15px;";
    startOverlay.innerHTML = `
        <div id="step1" style="display:flex; flex-direction:column; align-items:center;">
            <h1 style="font-size:4.5rem; color:var(--accent-color); text-shadow: 0 0 30px var(--accent-color); margin-bottom:10px;">TUMBANG PRESO</h1>
            <p style="font-size:1.5rem; margin-bottom:40px; color: #ccc;">STREET KING ARCADE EDITION</p>
            <button onclick="nextStep()" class="btn btn-glass" style="font-size:2.5rem; padding: 20px 60px; animation: pulse 2s infinite;">START LARO!</button>
        </div>
        <div id="step2" style="display:none; flex-direction:column; align-items:center;">
            <h2 style="font-size:3rem; color:var(--accent-color); margin-bottom:30px;">PILI NG DIFFICULTY:</h2>
            <div style="display:flex; gap:25px;">
                <button class="btn btn-glass diff-btn" onclick="startGame('easy')" style="border-color:#4caf50; color:#4caf50; font-size:1.5rem;">EASY</button>
                <button class="btn btn-glass diff-btn" onclick="startGame('medium')" style="border-color:#ff9800; color:#ff9800; font-size:1.5rem;">MEDIUM</button>
                <button class="btn btn-glass diff-btn" onclick="startGame('hard')" style="border-color:#f44336; color:#f44336; font-size:1.5rem;">HARD</button>
            </div>
        </div>
    `;
    const container = document.querySelector('.game-container');
    if (container) container.appendChild(startOverlay);

    window.nextStep = () => {
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'flex';
        sounds.bgm.play().catch(e => console.log("Audio play blocked"));
    };

    window.startGame = (level) => {
        setDifficulty(level);
        isGameOver = false;
        lataScore = 0;
        tsinelasCount = 10;
        p1.health = 100;
        p2.health = 100;
        aiDecisionTimer = 0;
        document.getElementById('startOverlay').style.display = 'none';
        gameLoop();
    };

    function showCombo(text) {
        const el = document.getElementById('comboText');
        if (el) {
            el.innerText = text;
            el.classList.add('active');
            setTimeout(() => el.classList.remove('active'), 1000);
        }
    }

    // Images
    let processedLata = null;
    let processedTsinelas = null;
    const lataImg = new Image();
    lataImg.src = 'assets/sprites/lata.png?v=2';
    const tsinelasImg = new Image();
    tsinelasImg.src = 'assets/sprites/tsinelas.png?v=2';

    // Player Init
    const p1 = new Player(1250, 450, '#1976d2', true, 'assets/sprites/p1_body.png'); 
    const p2 = new Player(400, 450, '#d32f2f', false, 'assets/sprites/p2_body.png'); 
    const projectiles = [];
    let tsinelasCount = 10;
    let lataScore = 0;
    const targetScore = 3;
    let pendingScore = false; 
    let slipperRecovered = false;

    // Lata Object
    const lata = {
        x: 450, y: 500, startX: 450, startY: 500, z: 0,
        vx: 0, vy: 0, vz: 0, rotation: 0, rotationSpeed: 0,
        width: 40, height: 60, isDown: false, isBeingCarried: false, gravity: 0.3,
        
        update() {
            if (this.isBeingCarried) {
                this.x = p2.x + p2.width/2;
                this.y = p2.y + p2.height/2;
                this.z = -30;
                this.rotation = 0;
                return;
            }
            if (this.isDown) {
                this.x += this.vx; this.y += this.vy; this.vz += this.gravity; this.z += this.vz;
                this.rotation += this.rotationSpeed;
                if (this.x < 20) { this.x = 20; this.vx *= -0.5; }
                if (this.x > 1380) { this.x = 1380; this.vx *= -0.5; }
                if (this.y < 20) { this.y = 20; this.vy *= -0.5; }
                if (this.y > 680) { this.y = 680; this.vy *= -0.5; }
                if (this.z >= 0) {
                    this.z = 0; this.vz *= -0.5; this.vx *= 0.8; this.vy *= 0.8;
                    this.rotationSpeed *= 0.8;
                    if (Math.abs(this.vz) < 0.5) { this.vz = 0; this.vx = 0; this.vy = 0; }
                }
            }
        },
        hit(forceX, forceY) {
            this.isDown = true; this.vx = forceX * 0.25; this.vy = forceY * 0.25;
            this.vz = -6; this.rotationSpeed = 0.15; slipperRecovered = false;
        },
        draw(ctx) {
            ctx.save();
            ctx.fillStyle = 'rgba(0,0,0,0.2)';
            ctx.beginPath(); ctx.ellipse(this.x, this.y, 20, 10, 0, 0, Math.PI * 2); ctx.fill();
            ctx.translate(this.x, this.y + this.z);
            ctx.rotate(this.isDown ? this.rotation : 0);
            ctx.fillStyle = '#999';
            ctx.fillRect(-this.width/2, -this.height, this.width, this.height);
            ctx.restore();
        }
    };

    function updateAI() {
        if (p2.isStunned) return;
        const config = aiConfig[currentDifficulty];
        p2.velocityX = 0; p2.velocityY = 0;
        const p2CenterX = p2.x + p2.width / 2;
        const p2CenterY = p2.y + p2.height / 2;

        if (lata.isDown) {
            if (aiDecisionTimer < config.decisionDelay) { aiDecisionTimer++; return; }
            if (!lata.isBeingCarried) {
                const dx = lata.x - p2CenterX; const dy = lata.y - p2CenterY;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if (dist > 25) { p2.velocityX = (dx / dist) * p2.speed; p2.velocityY = (dy / dist) * p2.speed; }
                else { lata.isBeingCarried = true; }
            } else {
                const dx = lata.startX - p2CenterX; const dy = lata.startY - p2CenterY;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if (dist > 15) { p2.velocityX = (dx / dist) * p2.speed; p2.velocityY = (dy / dist) * p2.speed; }
                else {
                    lata.isDown = false; lata.isBeingCarried = false;
                    lata.x = lata.startX; lata.y = lata.startY; lata.z = 0;
                    aiDecisionTimer = 0;
                }
            }
        } else {
            const p1CenterX = p1.x + p1.width / 2;
            const p1CenterY = p1.y + p1.height / 2;
            const dx = p1CenterX - p2CenterX; const dy = p1CenterY - p2CenterY;
            const dist = Math.sqrt(dx*dx + dy*dy);
            if (p1.x < 1100 && dist < config.chaseRange) { 
                p2.velocityX = (dx / dist) * p2.speed; p2.velocityY = (dy / dist) * p2.speed;
            } else {
                const gdx = (lata.startX - 80) - p2CenterX; const gdy = lata.startY - p2CenterY;
                const gdist = Math.sqrt(gdx*gdx + gdy*gdy);
                if (gdist > 10) { p2.velocityX = (gdx / gdist) * p2.speed; p2.velocityY = (gdy / gdist) * p2.speed; }
            }
        }
    }

    let isAiming = false;
    let dragStartX = 0, dragStartY = 0, dragX = 0, dragY = 0;

    canvas.addEventListener('mousedown', (e) => {
        if (tsinelasCount <= 0 || p1.isStunned) return;
        const rect = canvas.getBoundingClientRect();
        const mx = e.clientX - rect.left; const my = e.clientY - rect.top;
        if (Math.sqrt((mx - (p1.x + p1.width/2))**2 + (my - (p1.y + p1.height/2))**2) < 80) {
            isAiming = true; dragStartX = p1.x + p1.width/2; dragStartY = p1.y + p1.height/2;
            dragX = mx; dragY = my;
        }
    });

    window.addEventListener('mousemove', (e) => {
        if (!isAiming) return;
        const rect = canvas.getBoundingClientRect();
        dragX = e.clientX - rect.left; dragY = e.clientY - rect.top;
    });

    window.addEventListener('mouseup', (e) => {
        if (!isAiming) return;
        isAiming = false;
        const dx = dragStartX - dragX; const dy = dragStartY - dragY;
        const dist = Math.min(Math.sqrt(dx*dx + dy*dy), 150);
        let angle = Math.atan2(dy, dx);
        const needle = (window.currentNeedleAngle || 0);
        const diff = Math.abs(needle - Math.PI * 1.5);
        let accuracyText = "SABLAY!", deviation = 0;
        if (diff < 0.1) { accuracyText = "SAPUL!"; deviation = 0; }
        else if (diff < 0.25) { accuracyText = "MUNTIKAN NA!"; deviation = (Math.random() - 0.5) * 0.15; }
        else { accuracyText = "BANOOOO!"; deviation = (Math.random() > 0.5 ? 1 : -1) * 0.6; }
        showCombo(accuracyText); 
        const power = (dist / 150) * 35;
        if (dist > 10) {
            p1.triggerThrow(p1.x + dx, p1.y + dy);
            sounds.throw.currentTime = 0; sounds.throw.play();
            projectiles.push(new Projectile(p1.x + p1.width/2, p1.y + p1.height/2, Math.cos(angle + deviation) * power, Math.sin(angle + deviation) * power, tsinelasImg));
            tsinelasCount--; document.getElementById('tsinelas-count').innerText = tsinelasCount;
        }
    });

    function drawTrajectory() {
        if (!isAiming) return;
        ctx.save();
        const dx = dragStartX - dragX; const dy = dragStartY - dragY;
        const dist = Math.min(Math.sqrt(dx*dx + dy*dy), 150);
        const angle = Math.atan2(dy, dx);
        const powerRatio = dist / 150;
        const color = `rgb(${255 * powerRatio}, ${255 * (1 - powerRatio)}, 0)`;
        ctx.setLineDash([5, 5]); ctx.strokeStyle = color; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(dragStartX, dragStartY);
        const lx = dragStartX + Math.cos(angle) * dist * 6; const ly = dragStartY + Math.sin(angle) * dist * 6;
        ctx.lineTo(lx, ly); ctx.stroke(); ctx.setLineDash([]);
        ctx.strokeStyle = color; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.arc(lx, ly, 15, 0, Math.PI * 2); ctx.stroke();
        ctx.fillStyle = color; ctx.font = "bold 1.2rem 'Permanent Marker', cursive";
        ctx.fillText(`POWER: ${Math.round(powerRatio * 100)}%`, dragStartX - 40, dragStartY - 160);
        const gaugeX = dragStartX, gaugeY = dragStartY - 120, gaugeRadius = 45;
        ctx.lineWidth = 12; ctx.strokeStyle = 'rgba(244, 67, 54, 0.8)';
        ctx.beginPath(); ctx.arc(gaugeX, gaugeY, gaugeRadius, Math.PI * 0.8, Math.PI * 2.2); ctx.stroke();
        ctx.strokeStyle = 'rgba(255, 235, 59, 0.9)';
        ctx.beginPath(); ctx.arc(gaugeX, gaugeY, gaugeRadius, Math.PI * 1.3, Math.PI * 1.7); ctx.stroke();
        ctx.strokeStyle = '#4caf50';
        ctx.beginPath(); ctx.arc(gaugeX, gaugeY, gaugeRadius, Math.PI * 1.45, Math.PI * 1.55); ctx.stroke();
        const time = Date.now() / 150; const needleAngle = Math.PI * 1.5 + Math.sin(time) * (Math.PI * 0.6);
        const nx = gaugeX + Math.cos(needleAngle) * gaugeRadius; const ny = gaugeY + Math.sin(needleAngle) * gaugeRadius;
        ctx.fillStyle = 'white'; ctx.beginPath(); ctx.arc(nx, ny, 8, 0, Math.PI * 2); ctx.fill();
        ctx.strokeStyle = 'black'; ctx.lineWidth = 2; ctx.stroke();
        window.currentNeedleAngle = needleAngle;
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
        ctx.beginPath(); ctx.arc(dragStartX, dragStartY, 150, 0, Math.PI * 2); ctx.stroke();
        ctx.restore();
    }

    function gameLoop() {
        if (isGameOver) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        p1.velocityX = 0; p1.velocityY = 0;
        if (input.isPressed('KeyW')) p1.velocityY = -p1.speed;
        if (input.isPressed('KeyS')) p1.velocityY = p1.speed;
        if (input.isPressed('KeyA')) p1.velocityX = -p1.speed;
        if (input.isPressed('KeyD')) p1.velocityX = p1.speed;
        if (input.isPressed('Space')) p1.jump();
        updateAI(); lata.update(); p1.update(canvas.width, canvas.height); p2.update(canvas.width, canvas.height);
        drawTrajectory();
        if (pendingScore && slipperRecovered && p1.x >= 1150) {
            lataScore++; pendingScore = false; slipperRecovered = false;
            document.getElementById('current-score').innerText = `${lataScore} / ${targetScore}`;
            showCombo("POINT SECURED!");
            if (lataScore >= targetScore) showResult("VICTORY! HARING KALSADA!");
        }
        for (let i = projectiles.length - 1; i >= 0; i--) {
            const p = projectiles[i];
            p.update(canvas.width, canvas.height); p.draw(ctx);
            const p1cX = p1.x + p1.width / 2, p1cY = p1.y + p1.height / 2;
            if (p.onGround && Math.sqrt((p1cX - p.x)**2 + (p1cY - p.y)**2) < 70) {
                p.active = false; tsinelasCount++;
                document.getElementById('tsinelas-count').innerText = tsinelasCount;
                if (pendingScore) slipperRecovered = true;
            }
            if (p.active && !p.onGround && !lata.isDown && Math.sqrt((p.x-lata.x)**2 + (p.y-lata.y)**2) < 40) {
                lata.hit(p.velocityX, p.velocityY); 
                p.onGround = true; 
                p.velocityX = 0; p.velocityY = 0; p.z = 0;
                sounds.hit.play();
                p2.stun(120); pendingScore = true; slipperRecovered = false;
                showCombo("LATA DOWN! KUNIN ANG TSINELAS!");
            }
            const p2cX = p2.x + p2.width / 2, p2cY = p2.y + p2.height / 2;
            if (p.active && !p.onGround && Math.sqrt((p.x - p2cX)**2 + (p.y - p2cY)**2) < 50 && p2.z > -20) {
                p2.takeDamage(10); p2.stun(60); p.active = false; showCombo("SWABE!");
            }
            if (!p.active) projectiles.splice(i, 1);
        }
        const p1cX = p1.x + p1.width / 2, p1cY = p1.y + p1.height / 2;
        const p2cX = p2.x + p2.width / 2, p2cY = p2.y + p2.height / 2;
        if (!lata.isDown && !lata.isBeingCarried && !p2.isStunned && p1.x < 1150) {
            if (Math.sqrt((p1cX - p2cX)**2 + (p1cY - p2cY)**2) < 60) showResult("NA-TAG KA! TAYA KA NA!");
        }
        lata.draw(ctx); p1.draw(ctx); p2.draw(ctx); updateHealthBars();
        if (p2.health <= 0) showResult("P1 WINS! HARING KALSADA!");
        else if (p1.health <= 0) showResult("TAYÂ WINS! BUTAW!");
        else if (tsinelasCount === 0 && projectiles.length === 0 && !lata.isDown) showResult("TAYÂ WINS! NAUBUSAN KA!");
        requestAnimationFrame(gameLoop);
    }

    function updateHealthBars() {
        document.getElementById('p1-health').style.width = p1.health + '%';
        document.getElementById('p2-health').style.width = p2.health + '%';
    }

    function showResult(title) {
        if (isGameOver) return;
        isGameOver = true;
        document.getElementById('winnerTitle').innerText = title;
        const modalEl = document.getElementById('resultModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            alert(title);
        }
    }

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await (await fetch('api/upload_handler.php', {method: 'POST', body: new FormData(uploadForm)})).json();
        if (res.status === 'success') p1.setFace(res.file_path);
    });
</script>
