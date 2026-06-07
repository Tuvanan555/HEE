// js/editor.js
const PhotoEditor = {
    canvas: null,
    ctx: null,
    imageObj: null,
    
    // Adjustments
    brightness: 100,
    contrast: 100,
    saturation: 100,
    blur: 0,
    
    // State
    currentFilter: 'none',
    currentFrame: 'none',

    init() {
        this.canvas = document.getElementById('photo-editor-canvas');
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        
        this.bindEvents();
        this.renderToolContent('filters');
        
        // Setup close modal event
        const closeBtn = document.querySelector('.close-editor-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }
    },

    openModal(imageUrl) {
        const modal = document.getElementById('editor-modal');
        if (modal) {
            modal.classList.remove('hidden');
            if (imageUrl) {
                this.loadImage(imageUrl);
                document.getElementById('editor-placeholder').classList.add('hidden');
            } else {
                this.imageObj = null;
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                document.getElementById('editor-placeholder').classList.remove('hidden');
            }
        }
    },

    closeModal() {
        const modal = document.getElementById('editor-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    },

    bindEvents() {
        const fileInput = document.getElementById('editor-file-input');
        const placeholder = document.getElementById('editor-placeholder');
        const tabs = document.querySelectorAll('.editor-tab');
        const resetBtn = document.getElementById('btn-editor-reset');
        const saveBtn = document.getElementById('btn-editor-save');

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        this.loadImage(ev.target.result);
                        placeholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                tabs.forEach(t => t.classList.remove('active'));
                e.target.classList.add('active');
                this.renderToolContent(e.target.dataset.tool);
            });
        });

        if (resetBtn) resetBtn.addEventListener('click', () => this.resetAdjustments());
        if (saveBtn) saveBtn.addEventListener('click', () => this.saveImage());
    },

    loadImage(src) {
        this.imageObj = new Image();
        this.imageObj.onload = () => {
            this.canvas.width = this.imageObj.width;
            this.canvas.height = this.imageObj.height;
            this.applyFiltersAndDraw();
        };
        this.imageObj.src = src;
    },

    applyFiltersAndDraw() {
        if (!this.imageObj) return;

        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Build CSS filter string
        let filterStr = `brightness(${this.brightness}%) contrast(${this.contrast}%) saturate(${this.saturation}%) blur(${this.blur}px)`;
        
        // Add predefined filters
        switch(this.currentFilter) {
            case 'vintage': filterStr += ' sepia(50%) contrast(150%)'; break;
            case 'bw': filterStr += ' grayscale(100%)'; break;
            case 'warm': filterStr += ' sepia(30%) hue-rotate(-30deg)'; break;
            case 'cold': filterStr += ' hue-rotate(180deg) saturate(150%)'; break;
            case 'dreamy': filterStr += ' blur(2px) brightness(120%) saturate(120%)'; break;
        }

        this.ctx.filter = filterStr;
        this.ctx.drawImage(this.imageObj, 0, 0, this.canvas.width, this.canvas.height);
        
        // Reset filter for other drawings (like frames)
        this.ctx.filter = 'none';

        this.drawFrame();
    },

    drawFrame() {
        if (this.currentFrame === 'none') return;

        const w = this.canvas.width;
        const h = this.canvas.height;
        this.ctx.lineWidth = 40;

        switch(this.currentFrame) {
            case 'polaroid':
                // White thick border, extra thick at bottom
                this.ctx.fillStyle = '#FFFFFF';
                this.ctx.fillRect(0, 0, w, 40); // Top
                this.ctx.fillRect(0, 0, 40, h); // Left
                this.ctx.fillRect(w - 40, 0, 40, h); // Right
                this.ctx.fillRect(0, h - 120, w, 120); // Bottom
                break;
            case 'love':
                this.ctx.strokeStyle = '#F43F5E';
                this.ctx.strokeRect(20, 20, w - 40, h - 40);
                break;
            case 'film':
                this.ctx.fillStyle = '#111';
                this.ctx.fillRect(0, 0, w, 40);
                this.ctx.fillRect(0, h - 40, w, 40);
                // Draw holes
                this.ctx.fillStyle = '#FFF';
                for(let i=10; i<w; i+=40) {
                    this.ctx.fillRect(i, 10, 20, 20);
                    this.ctx.fillRect(i, h - 30, 20, 20);
                }
                break;
        }
    },

    renderToolContent(tool) {
        const container = document.getElementById('editor-tools-container');
        if (!container) return;
        container.innerHTML = '';

        if (tool === 'filters') {
            const filters = [
                { id: 'none', label: 'Normal' },
                { id: 'vintage', label: 'Vintage' },
                { id: 'bw', label: 'B&W' },
                { id: 'warm', label: 'Warm' },
                { id: 'cold', label: 'Cold' },
                { id: 'dreamy', label: 'Dreamy' }
            ];
            const grid = document.createElement('div');
            grid.className = 'filter-grid';
            filters.forEach(f => {
                const btn = document.createElement('button');
                btn.className = `filter-btn ${this.currentFilter === f.id ? 'active' : ''}`;
                btn.textContent = f.label;
                btn.onclick = () => {
                    this.currentFilter = f.id;
                    this.renderToolContent('filters');
                    this.applyFiltersAndDraw();
                };
                grid.appendChild(btn);
            });
            container.appendChild(grid);
        } else if (tool === 'frames') {
            const frames = [
                { id: 'none', label: 'No Frame' },
                { id: 'polaroid', label: 'Polaroid' },
                { id: 'love', label: 'Love Frame' },
                { id: 'film', label: 'Film Strip' }
            ];
            const grid = document.createElement('div');
            grid.className = 'frame-grid';
            frames.forEach(f => {
                const btn = document.createElement('button');
                btn.className = `frame-btn ${this.currentFrame === f.id ? 'active' : ''}`;
                btn.textContent = f.label;
                btn.onclick = () => {
                    this.currentFrame = f.id;
                    this.renderToolContent('frames');
                    this.applyFiltersAndDraw();
                };
                grid.appendChild(btn);
            });
            container.appendChild(grid);
        } else if (tool === 'adjust') {
            const controls = [
                { id: 'brightness', label: 'Brightness', min: 0, max: 200, val: this.brightness },
                { id: 'contrast', label: 'Contrast', min: 0, max: 200, val: this.contrast },
                { id: 'saturation', label: 'Saturation', min: 0, max: 200, val: this.saturation },
                { id: 'blur', label: 'Blur', min: 0, max: 20, val: this.blur }
            ];
            controls.forEach(c => {
                const group = document.createElement('div');
                group.className = 'adjust-group';
                group.innerHTML = `
                    <label>${c.label} <span>${c.val}</span></label>
                    <input type="range" class="adjust-slider" min="${c.min}" max="${c.max}" value="${c.val}">
                `;
                const input = group.querySelector('input');
                const span = group.querySelector('span');
                input.addEventListener('input', (e) => {
                    this[c.id] = parseInt(e.target.value);
                    span.textContent = this[c.id];
                    this.applyFiltersAndDraw();
                });
                container.appendChild(group);
            });
        }
    },

    resetAdjustments() {
        this.brightness = 100;
        this.contrast = 100;
        this.saturation = 100;
        this.blur = 0;
        this.currentFilter = 'none';
        this.currentFrame = 'none';
        this.renderToolContent(document.querySelector('.editor-tab.active').dataset.tool);
        this.applyFiltersAndDraw();
    },

    saveImage() {
        if (!this.imageObj) return;
        const dataUrl = this.canvas.toDataURL('image/jpeg', 0.9);
        // Add to gallery or trigger download
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = `edited_${Date.now()}.jpg`;
        a.click();
    }
};
