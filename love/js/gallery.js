// js/gallery.js
const Gallery = {
    photos: [], // will store {id, url, user, folder, caption, etc}
    filesToUpload: [], // temporary holding for batch upload

    init() {
        this.bindEvents();
        this.loadPhotos();
    },

    bindEvents() {
        const uploadBtn = document.getElementById('btn-upload-photo');
        const modal = document.getElementById('upload-modal');
        const closeModal = document.querySelector('.close-modal');
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const processBtn = document.getElementById('btn-process-upload');

        if (uploadBtn) uploadBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        if (closeModal) closeModal.addEventListener('click', () => modal.classList.add('hidden'));

        // Drag & Drop
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    this.handleFiles(e.dataTransfer.files);
                }
            });
            dropZone.addEventListener('click', () => fileInput.click());
        }

        if (fileInput) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) this.handleFiles(fileInput.files);
            });
        }

        if (processBtn) {
            processBtn.addEventListener('click', () => this.uploadFiles());
        }
        
        // Filter Chips
        const chips = document.querySelectorAll('.chip');
        chips.forEach(chip => {
            chip.addEventListener('click', (e) => {
                chips.forEach(c => c.classList.remove('active'));
                e.target.classList.add('active');
                this.renderGallery(e.target.dataset.folder);
            });
        });
    },
    compressImage(dataUrl, maxWidth, maxHeight) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {
                let width = img.width;
                let height = img.height;
                if (width > height && width > maxWidth) {
                    height = Math.round(height * (maxWidth / width));
                    width = maxWidth;
                } else if (height >= width && height > maxHeight) {
                    width = Math.round(width * (maxHeight / height));
                    height = maxHeight;
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                resolve(canvas.toDataURL('image/jpeg', 0.8));
            };
            img.src = dataUrl;
        });
    },

    async handleFiles(files) {
        const previewGrid = document.getElementById('upload-preview');
        const processBtn = document.getElementById('btn-process-upload');
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')) continue;

            const reader = new FileReader();
            reader.onload = async (e) => {
                const imgData = e.target.result;
                
                // Create a temporary img element for face detection
                const img = new Image();
                img.src = imgData;
                await new Promise(r => img.onload = r);

                app.showLoading();
                // Determine folder using Face API
                const predictedFolder = typeof FaceDetector !== 'undefined' ? 
                    await FaceDetector.analyzeImage(img) : 'อื่นๆ';
                app.hideLoading();

                // Compress image
                const compressedDataUrl = await this.compressImage(imgData, 1200, 1200);

                this.filesToUpload.push({
                    file: file,
                    dataUrl: compressedDataUrl,
                    folder: predictedFolder
                });

                this.renderPreviews();
                processBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }
    },

    renderPreviews() {
        const previewGrid = document.getElementById('upload-preview');
        previewGrid.innerHTML = '';
        
        this.filesToUpload.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${item.dataUrl}" alt="Preview">
                <div class="gallery-item-tag" style="position:absolute; bottom:5px; left:5px;">${item.folder}</div>
                <div class="preview-item-remove" data-index="${index}"><i class="fas fa-times"></i></div>
            `;
            previewGrid.appendChild(div);
        });

        // Bind remove buttons
        document.querySelectorAll('.preview-item-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = e.currentTarget.dataset.index;
                this.filesToUpload.splice(idx, 1);
                this.renderPreviews();
                if (this.filesToUpload.length === 0) {
                    document.getElementById('btn-process-upload').disabled = true;
                }
            });
        });
    },

    async uploadFiles() {
        app.showLoading();
        
        for (let item of this.filesToUpload) {
            try {
                const res = await API.request('uploadImage', {
                    user: Auth.currentUser || 'Unknown',
                    folder: item.folder,
                    fileName: item.file.name,
                    mimeType: 'image/jpeg', // Since we compress to jpeg
                    fileData: item.dataUrl.split(',')[1] 
                });
                
                if (res.status === 'success') {
                    this.photos.unshift({
                        id: res.data.id,
                        url: res.data.url, // Use the Drive URL from the server
                        folder: item.folder,
                        user: Auth.currentUser,
                        date: new Date().toISOString()
                    });
                } else {
                    alert("อัปโหลดไม่สำเร็จ: " + res.message);
                }
            } catch (e) {
                console.error("Failed to upload", e);
                alert("เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่ (อาจต้องรัน setup() ใน Google Apps Script ก่อน)");
            }
        }

        this.filesToUpload = [];
        this.renderPreviews();
        document.getElementById('btn-process-upload').disabled = true;
        document.getElementById('upload-modal').classList.add('hidden');
        
        this.renderGallery();
        app.hideLoading();
    },

    async loadPhotos() {
        try {
            const res = await API.request('getData');
            if (res.status === 'success' && res.data && res.data.photos) {
                this.photos = res.data.photos.map(p => {
                    let finalUrl = p.ImageURL;
                    // Google Drive block uc?export=view for <img> tags. Convert to thumbnail endpoint.
                    if (finalUrl && finalUrl.includes('drive.google.com/uc')) {
                        const match = finalUrl.match(/id=([^&]+)/);
                        if (match && match[1]) {
                            finalUrl = `https://drive.google.com/thumbnail?id=${match[1]}&sz=w1000`;
                        }
                    }
                    
                    return {
                        id: p.ID,
                        url: finalUrl,
                        folder: p.Folder,
                        user: p.User,
                        date: p.CreatedDate || p.Timestamp
                    };
                });
            }
            this.renderGallery();
        } catch (e) {
            console.error('Failed to load photos:', e);
            this.renderGallery();
        }
    },

    renderGallery(folderFilter = 'all') {
        const container = document.getElementById('gallery-container');
        if (!container) return;

        container.innerHTML = '';
        
        const filtered = folderFilter === 'all' ? 
            this.photos : 
            this.photos.filter(p => p.folder === folderFilter);

        if (filtered.length === 0) {
            container.innerHTML = `<p class="empty-state" style="grid-column: 1/-1;">ไม่มีรูปภาพในหมวดหมู่นี้</p>`;
            return;
        }

        filtered.forEach(photo => {
            const el = document.createElement('div');
            el.className = 'polaroid-card animate-up';
            el.innerHTML = `
                <div class="polaroid-img-wrapper">
                    <img class="polaroid-img" src="${photo.url}" alt="Photo">
                </div>
                <div class="polaroid-caption">
                    <span class="polaroid-tag">${photo.folder}</span>
                </div>
                <div class="polaroid-actions">
                    <button class="polaroid-btn" title="แก้ไข/แต่งรูป" onclick="PhotoEditor.openModal('${photo.url}')">
                        <i class="fas fa-paint-brush"></i>
                    </button>
                    <button class="polaroid-btn" title="แชร์">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
            `;
            container.appendChild(el);
        });
    }
};
