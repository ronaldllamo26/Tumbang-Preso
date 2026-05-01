/**
 * Projectile.js - Handles the 'Tsinelas' (Slipper) physics and rendering.
 */

class Projectile {
    constructor(x, y, velocityX, velocityY, image) {
        this.x = x;
        this.y = y;
        this.z = -10; // Start slightly above ground
        this.velocityX = velocityX;
        this.velocityY = velocityY;
        this.velocityZ = -6; // Initial upward arc (slightly higher)
        this.gravity = 0.2; // Lower gravity for longer air time
        this.radius = 15;
        this.rotation = 0;
        this.rotationSpeed = (Math.random() - 0.5) * 0.5;
        this.active = true;
        this.onGround = false;
        this.image = image;
        this.width = 50;
        this.height = 30;
    }

    update(canvasWidth, canvasHeight) {
        if (this.onGround) return;

        // Move ground position
        this.x += this.velocityX;
        this.y += this.velocityY;
        
        // Arc physics
        this.velocityZ += this.gravity;
        this.z += this.velocityZ;

        this.rotation += this.rotationSpeed;

        // Ground Collision (when z returns to 0)
        if (this.z >= 0) {
            this.z = 0;
            this.velocityX = 0;
            this.velocityY = 0;
            this.onGround = true;
            this.rotation = 0; 
        }

        // Boundary Collision (Clamp to stay on screen)
        if (this.x < 10) { this.x = 10; this.velocityX = 0; }
        if (this.x > canvasWidth - 10) { this.x = canvasWidth - 10; this.velocityX = 0; }
        if (this.y < 55) { this.y = 55; this.velocityY = 0; }
        if (this.y > canvasHeight - 10) { this.y = canvasHeight - 10; this.velocityY = 0; }
    }

    draw(ctx) {
        if (!this.active) return;

        // Draw Shadow
        if (!this.onGround) {
            ctx.fillStyle = 'rgba(0,0,0,0.2)';
            ctx.beginPath();
            ctx.ellipse(this.x, this.y, 15, 8, 0, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.save();
        // Render at ground Y + height Z
        ctx.translate(this.x, this.y + this.z);
        ctx.rotate(this.rotation);
        
        const isCanvas = this.image instanceof HTMLCanvasElement;
        const isImageComplete = this.image instanceof HTMLImageElement && this.image.complete;

        if (this.image && (isCanvas || isImageComplete)) {
            ctx.drawImage(this.image, -this.width / 2, -this.height / 2, this.width, this.height);
        } else {
            ctx.fillStyle = '#8d6e63';
            ctx.fillRect(-this.width / 2, -this.height / 2, this.width, this.height);
        }
        
        ctx.restore();
    }
}

export default Projectile;
