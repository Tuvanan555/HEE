// js/diary.js
const Diary = {
    entries: [],
    
    init() {
        this.renderUI();
        this.bindEvents();
        this.loadEntries();
    },

    renderUI() {
        const container = document.getElementById('diary-container');
        if (!container) return;

        container.innerHTML = `
            <div class="diary-layout">
                <div class="diary-sidebar glass-panel">
                    <button class="btn-primary" id="btn-new-diary"><i class="fas fa-plus"></i> เขียนไดอารี่ใหม่</button>
                    <div class="diary-list" id="diary-list">
                        <!-- Entries populated here -->
                    </div>
                </div>
                <div class="diary-editor-container glass-panel">
                    <input type="text" class="diary-title-input" id="diary-title" placeholder="หัวข้อไดอารี่...">
                    <div class="diary-meta-inputs">
                        <span class="text-secondary">อารมณ์วันนี้:</span>
                        <div class="mood-selector" id="diary-mood-selector">
                            <button class="mood-btn" data-mood="😊">😊</button>
                            <button class="mood-btn" data-mood="🥰">🥰</button>
                            <button class="mood-btn" data-mood="😢">😢</button>
                            <button class="mood-btn" data-mood="😡">😡</button>
                            <button class="mood-btn" data-mood="😴">😴</button>
                        </div>
                    </div>
                    <div class="rich-text-editor">
                        <div class="editor-toolbar">
                            <button onclick="document.execCommand('bold', false, null)"><i class="fas fa-bold"></i></button>
                            <button onclick="document.execCommand('italic', false, null)"><i class="fas fa-italic"></i></button>
                            <button onclick="document.execCommand('underline', false, null)"><i class="fas fa-underline"></i></button>
                            <button onclick="document.execCommand('insertUnorderedList', false, null)"><i class="fas fa-list-ul"></i></button>
                        </div>
                        <div class="editor-content" id="diary-content" contenteditable="true" placeholder="เริ่มเขียนความรู้สึกของคุณ..."></div>
                    </div>
                    <div class="diary-actions">
                        <button class="btn-secondary" id="btn-save-draft">บันทึกร่าง</button>
                        <button class="btn-primary" id="btn-save-diary">บันทึกไดอารี่</button>
                    </div>
                </div>
            </div>
        `;
    },

    bindEvents() {
        const moodBtns = document.querySelectorAll('#diary-mood-selector .mood-btn');
        let selectedMood = '😊';
        moodBtns[0].classList.add('active'); // default

        moodBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                moodBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                selectedMood = e.target.dataset.mood;
            });
        });

        const saveBtn = document.getElementById('btn-save-diary');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const title = document.getElementById('diary-title').value;
                const content = document.getElementById('diary-content').innerHTML;
                
                if (!title || !document.getElementById('diary-content').textContent.trim()) {
                    alert("กรุณาใส่หัวข้อและเนื้อหา");
                    return;
                }

                this.saveEntry({
                    id: Date.now(),
                    title,
                    content,
                    mood: selectedMood,
                    user: Auth.currentUser,
                    date: new Date().toISOString()
                });
            });
        }
    },

    async loadEntries() {
        try {
            const res = await API.request('getData');
            if (res.status === 'success' && res.data && res.data.diaries) {
                this.entries = res.data.diaries.map(d => ({
                    id: d.ID,
                    title: d.Title,
                    content: d.Description,
                    mood: d.Mood || '😊',
                    user: d.User,
                    date: d.CreatedDate || d.Timestamp
                }));
            }
        } catch (e) {
            console.error('Failed to load diary entries:', e);
        }
        this.renderList();
    },

    renderList() {
        const list = document.getElementById('diary-list');
        if (!list) return;
        
        list.innerHTML = '';
        this.entries.forEach(entry => {
            const div = document.createElement('div');
            div.className = 'diary-item';
            div.innerHTML = `
                <h4>${entry.mood} ${entry.title}</h4>
                <div class="diary-item-meta">
                    <span>${entry.date.split('T')[0]}</span>
                </div>
            `;
            list.appendChild(div);
        });
    },

    async saveEntry(entry) {
        app.showLoading();
        try {
            // Mock Apps Script API call
            await API.request('saveDiary', entry);
            
            this.entries.unshift(entry);
            this.renderList();
            
            // Clear form
            document.getElementById('diary-title').value = '';
            document.getElementById('diary-content').innerHTML = '';
            
            alert('บันทึกเรียบร้อย!');
        } catch (e) {
            console.error(e);
            alert('เกิดข้อผิดพลาด');
        } finally {
            app.hideLoading();
        }
    }
};
