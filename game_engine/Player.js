/**
 * Player.js - Top-Down Edition
 */

class Player {
    constructor(x, y, color, isPlayerOne = true, bodySpritePath = '', defaultHeadPath = '') {
        this.x = x;
        this.y = y;
        this.z = 0; 
        this.width = 70;
        this.height = 110; 
        this.color = color;
        this.isPlayerOne = isPlayerOne;
        
        // Movement properties
        this.velocityX = 0;
        this.velocityY = 0;
        this.velocityZ = 0;
        this.speed = 5;
        this.jumpForce = -8;
        this.grounded = true;

        // Combat properties
        this.health = 100;
        this.isStunned = false;
        this.stunTimer = 0;

        // Animation State
        this.animTimer = 0;
        this.bobOffset = 0;
        this.rotation = 0; 
        this.scaleY = 1;   
        this.throwTimer = 0;
        this.throwAngle = 0;

        // Body Sprite with Transparency Logic
        this.bodyImage = new Image();
        this.bodyImage.src = bodySpritePath;
        this.bodyLoaded = false;
        this.offscreenCanvas = document.createElement('canvas');
        this.offscreenCtx = this.offscreenCanvas.getContext('2d');

        this.bodyImage.onload = () => {
            this.processTransparency(this.bodyImage, this.offscreenCanvas, this.offscreenCtx);
            this.bodyLoaded = true;
        };

        // Default Anime Head
        this.defaultHeadImage = new Image();
        this.defaultHeadImage.src = defaultHeadPath;
        this.defaultHeadLoaded = false;
        this.headCanvas = document.createElement('canvas');
        this.headCtx = this.headCanvas.getContext('2d');
        this.defaultHeadImage.onload = () => {
            this.processTransparency(this.defaultHeadImage, this.headCanvas, this.headCtx);
            this.defaultHeadLoaded = true;
        };

        // Custom Face properties
        this.headImage = new Image();
        this.hasCustomFace = false;
        this.headRadius = 24;
    }

    processTransparency(img, canvas, ctx) {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
            if (data[i] > 240 && data[i+1] > 240 && data[i+2] > 240) data[i+3] = 0;
        }
        ctx.putImageData(imageData, 0, 0);
    }

    setFace(imagePath) {
        this.headImage.src = imagePath;
        this.headImage.onload = () => { this.hasCustomFace = true; };
    }

    triggerThrow(targetX, targetY) {
        this.throwTimer = 15; // 15 frames of throw animation
        this.throwAngle = Math.atan2(targetY - this.y, targetX - this.x);
    }

    update(canvasWidth, canvasHeight) {
        if (this.isStunned) {
            this.stunTimer--;
            if (this.stunTimer <= 0) this.isStunned = false;
            return;
        }

        // Apply 2D Movement
        this.x += this.velocityX;
        this.y += this.velocityY;

        // Procedural Animation Logic
        if (this.velocityX !== 0 || this.velocityY !== 0) {
            this.animTimer += 0.2;
            this.bobOffset = Math.sin(this.animTimer * 2) * 4;
            this.rotation = Math.sin(this.animTimer) * 0.1; // Sway
        } else {
            this.bobOffset = 0;
            this.rotation = 0;
        }

        // Throw Animation Decay
        if (this.throwTimer > 0) {
            this.throwTimer--;
            this.rotation = (this.throwTimer / 15) * 0.3 * (this.isPlayerOne ? 1 : -1);
        }

        // Simulated Jump Physics
        if (!this.grounded) {
            this.velocityZ += 0.5; 
            this.z += this.velocityZ;
            this.scaleY = 1.2; // Stretch up

            if (this.z >= 0) {
                this.z = 0;
                this.velocityZ = 0;
                this.grounded = true;
                this.scaleY = 1;
            }
        }

        // Boundaries
        if (this.x < 0) this.x = 0;
        if (this.x + this.width > canvasWidth) this.x = canvasWidth - this.width;
        if (this.y < 120) this.y = 120; // Street starting area
        if (this.y + this.height > canvasHeight) this.y = canvasHeight - this.height;
    }

    draw(ctx) {
        const drawX = this.x + this.width / 2;
        const drawY = this.y + this.height + this.z + this.bobOffset;

        // Shadow
        ctx.fillStyle = 'rgba(0,0,0,0.2)';
        ctx.beginPath();
        ctx.ellipse(this.x + this.width/2, this.y + this.height - 10, 25, 12, 0, 0, Math.PI * 2);
        ctx.fill();

        ctx.save();
        ctx.translate(drawX, drawY);
        ctx.rotate(this.rotation);
        ctx.scale(1, this.scaleY);
        ctx.translate(-this.width / 2, -this.height);

        // Full Body Sprite (includes head)
        if (this.bodyLoaded) {
            ctx.drawImage(this.offscreenCanvas, 0, 0, this.width, this.height);
        } else {
            ctx.fillStyle = this.color;
            ctx.fillRect(0, 0, this.width, this.height);
        }

        // Custom Face (Only if uploaded)
        const headX = this.width / 2;
        const headY = this.isPlayerOne ? 28 : 32; 

        if (this.hasCustomFace) {
            ctx.save();
            ctx.beginPath();
            ctx.arc(headX, headY, this.headRadius, 0, Math.PI * 2);
            ctx.clip();
            ctx.drawImage(this.headImage, headX - this.headRadius, headY - this.headRadius, this.headRadius * 2, this.headRadius * 2);
            ctx.restore();

            // Outline for custom face
            ctx.strokeStyle = 'rgba(255,255,255,0.3)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.arc(headX, headY, this.headRadius, 0, Math.PI * 2);
            ctx.stroke();
        }

        // Arm-like line for throw
        if (this.throwTimer > 5) {
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.moveTo(headX, headY + 20);
            ctx.lineTo(headX + (this.isPlayerOne ? -30 : 30), headY + 10);
            ctx.stroke();
        }

        ctx.restore();

        this.drawHealthBar(ctx);
        if (this.isStunned) this.drawStunStars(ctx);
    }

    drawStunStars(ctx) {
        const headX = this.x + this.width / 2;
        const headY = this.y + this.z + this.bobOffset - 10;
        const radius = 30;
        const stars = 3;
        const time = Date.now() / 200;

        for (let i = 0; i < stars; i++) {
            const angle = time + (i * Math.PI * 2) / stars;
            const sx = headX + Math.cos(angle) * radius;
            const sy = headY + Math.sin(angle * 0.5) * 10;
            
            ctx.fillStyle = '#ffeb3b';
            ctx.beginPath();
            ctx.arc(sx, sy, 4, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    drawHealthBar(ctx) {
        const barWidth = 60;
        const barHeight = 8;
        const barX = this.x;
        const barY = this.y + this.z + this.bobOffset - 50;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(barX, barY, barWidth, barHeight);
        ctx.fillStyle = this.health > 30 ? '#00ff00' : '#ff0000';
        ctx.fillRect(barX, barY, (this.health / 100) * barWidth, barHeight);
    }

    takeDamage(amount) {
        this.health -= amount;
        if (this.health < 0) this.health = 0;
    }

    stun(duration) {
        this.isStunned = true;
        this.stunTimer = duration;
    }

    jump() {
        if (this.grounded) {
            this.velocityZ = this.jumpForce;
            this.grounded = false;
        }
    }

    drawDefaultFace(ctx, x, y, radius) {
        // Skin
        ctx.fillStyle = '#ffdbac';
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.fill();

        // Eyes (Pixel Art Style)
        ctx.fillStyle = '#333';
        const eyeSize = 4;
        const eyeOffset = 6;
        ctx.fillRect(x - eyeOffset - eyeSize/2, y - 5, eyeSize, eyeSize);
        ctx.fillRect(x + eyeOffset - eyeSize/2, y - 5, eyeSize, eyeSize);

        // Mouth / Expression
        ctx.strokeStyle = '#333';
        ctx.lineWidth = 2;
        ctx.beginPath();
        if (this.isStunned) {
            // Dizzy mouth
            ctx.arc(x, y + 8, 5, 0, Math.PI * 2);
        } else {
            // Normal smile/neutral
            ctx.moveTo(x - 5, y + 8);
            ctx.lineTo(x + 5, y + 8);
        }
        ctx.stroke();
    }
}

export default Player;
