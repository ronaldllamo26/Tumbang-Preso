/**
 * Input.js - Handles keyboard and mouse input for the game.
 */

class Input {
    constructor() {
        this.keys = {};
        this.mouse = { x: 0, y: 0, pressed: false, charge: 0 };

        window.addEventListener('keydown', (e) => {
            this.keys[e.code] = true;
        });

        window.addEventListener('keyup', (e) => {
            this.keys[e.code] = false;
        });

        window.addEventListener('mousedown', (e) => {
            this.mouse.pressed = true;
        });

        window.addEventListener('mouseup', (e) => {
            this.mouse.pressed = false;
        });
    }

    isPressed(keyCode) {
        return !!this.keys[keyCode];
    }
}

export default new Input();
