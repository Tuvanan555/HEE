// js/theme.js
const ThemeManager = {
    currentTheme: 'theme-midnight-blue',
    canvas: null,
    ctx: null,
    particles: [],
    animationId: null,

    init() {
        this.loadPreference();
        this.bindEvents();
        this.initCanvas();
    },

    loadPreference() {
        const savedTheme = localStorage.getItem('love_memory_theme');
        if (savedTheme) {
            this.setTheme(savedTheme);
        } else {
            this.setTheme(this.currentTheme);
        }
    },

    bindEvents() {
        // We will remove the old toggle logic and expose ThemeManager.setTheme globally or via data-theme buttons.
        const themeBtns = document.querySelectorAll('.theme-btn-select');
        themeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setTheme(e.currentTarget.dataset.theme);
            });
        });
        
        window.addEventListener('resize', () => {
            if (this.canvas) {
                this.canvas.width = window.innerWidth;
                this.canvas.height = window.innerHeight;
                this.createParticles();
            }
        });
    },

    setTheme(themeName) {
        document.body.classList.remove(
            'theme-midnight-blue', 
            'theme-pink-love', 
            'theme-cream', 
            'theme-light-blue', 
            'theme-monochrome'
        );
        document.body.classList.add(themeName);
        this.currentTheme = themeName;
        localStorage.setItem('love_memory_theme', themeName);
        
        // Re-initialize particles for new theme colors/shapes
        if (this.ctx) {
            this.createParticles();
        }
    },

    initCanvas() {
        this.canvas = document.getElementById('bg-canvas');
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
        
        this.createParticles();
        this.animate();
    },

    createParticles() {
        this.particles = [];
        const isPink = this.currentTheme === 'theme-pink-love';
        const numParticles = isPink ? 40 : 80;

        for (let i = 0; i < numParticles; i++) {
            this.particles.push({
                x: Math.random() * this.canvas.width,
                y: Math.random() * this.canvas.height,
                size: Math.random() * 25 + 5,
                speedY: Math.random() * 0.8 + 0.2,
                speedX: (Math.random() - 0.5) * 0.4,
                opacity: Math.random() * 0.4 + 0.1,
                type: Math.random() > 0.4 ? 'bokeh' : 'heart',
                hueOffset: Math.random() * 20 - 10
            });
        }
    },
    getThemeColor(isHeart) {
        // Read CSS variables dynamically so it's always in sync with themes.css
        const style = getComputedStyle(document.body);
        return isHeart ? style.getPropertyValue('--heart-color').trim() || '#f43f5e' 
                       : style.getPropertyValue('--accent-color').trim() || '#38bdf8';
    },

    drawHeart(ctx, x, y, size, opacity) {
        ctx.save();
        ctx.translate(x, y);
        ctx.scale(size / 30, size / 30);
        
        ctx.shadowBlur = 15;
        ctx.shadowColor = this.getThemeColor(true);
        ctx.fillStyle = this.getThemeColor(true);
        ctx.globalAlpha = opacity;
        
        ctx.beginPath();
        ctx.moveTo(0, 10);
        ctx.bezierCurveTo(0, -10, -20, -10, -20, 10);
        ctx.bezierCurveTo(-20, 30, 0, 40, 0, 50);
        ctx.bezierCurveTo(0, 40, 20, 30, 20, 10);
        ctx.bezierCurveTo(20, -10, 0, -10, 0, 10);
        ctx.fill();
        ctx.restore();
    },

    drawBokeh(ctx, x, y, size, opacity) {
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, size, 0, Math.PI * 2);
        
        // Premium gradient bokeh
        let gradient = ctx.createRadialGradient(x, y, 0, x, y, size);
        let color = this.getThemeColor(false);
        gradient.addColorStop(0, color);
        gradient.addColorStop(1, 'transparent');
        
        ctx.globalAlpha = opacity * 0.6; // Softer opacity for bokeh
        ctx.fillStyle = gradient;
        ctx.fill();
        ctx.restore();
    },

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        for (let i = 0; i < this.particles.length; i++) {
            let p = this.particles[i];
            
            p.y -= p.speedY; // move up
            p.x += p.speedX;

            if (p.y < -50) {
                p.y = this.canvas.height + 50;
                p.x = Math.random() * this.canvas.width;
            }

            if (p.type === 'heart') {
                this.drawHeart(this.ctx, p.x, p.y, p.size, p.opacity);
            } else {
                this.drawBokeh(this.ctx, p.x, p.y, p.size * 1.5, p.opacity);
            }
        }

        this.animationId = requestAnimationFrame(() => this.animate());
    }
};
