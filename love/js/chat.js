// js/chat.js
const Chat = {
    messages: [],
    pollInterval: null,
    lastMessageId: 0,

    init() {
        this.renderUI();
        this.bindEvents();
        this.loadMessages();
        // Poll for new messages every 5 seconds
        this.pollInterval = setInterval(() => this.loadMessages(), 5000);
    },

    renderUI() {
        const container = document.getElementById('chat-container');
        if (!container) return;

        container.innerHTML = `
            <div class="chat-layout">
                <div class="chat-header glass-panel">
                    <div class="chat-header-info">
                        <div class="chat-avatar-pair">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Noh" alt="โน่" class="chat-avatar">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Nan" alt="นัน" class="chat-avatar">
                        </div>
                        <div>
                            <h3>โน่ 💙 นัน</h3>
                            <p class="chat-status">แชทส่วนตัว</p>
                        </div>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="chat-empty">
                        <i class="fas fa-comments"></i>
                        <p>ยังไม่มีข้อความ เริ่มพิมพ์ส่งข้อความแรกกันเลย! 💙</p>
                    </div>
                </div>
                <div class="chat-input-area glass-panel">
                    <input type="text" id="chat-input" placeholder="พิมพ์ข้อความ..." autocomplete="off">
                    <button id="btn-send-chat" class="btn-primary chat-send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;
    },

    bindEvents() {
        const sendBtn = document.getElementById('btn-send-chat');
        const input = document.getElementById('chat-input');

        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendMessage());
        }
        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
    },

    async sendMessage() {
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text) return;

        const msg = {
            id: Date.now().toString(),
            user: Auth.currentUser || 'Unknown',
            text: text,
            timestamp: new Date().toISOString()
        };

        input.value = '';
        input.focus();

        // Optimistically add to local messages
        this.messages.push(msg);
        this.renderMessages();

        try {
            await API.request('sendChat', msg);
        } catch (e) {
            console.error('Failed to send chat:', e);
        }
    },

    async loadMessages() {
        try {
            const res = await API.request('getChats');
            if (res.status === 'success' && res.data) {
                this.messages = res.data;
                this.renderMessages();
            }
        } catch (e) {
            console.error('Failed to load chats:', e);
        }
    },

    renderMessages() {
        const container = document.getElementById('chat-messages');
        if (!container) return;

        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="chat-empty">
                    <i class="fas fa-comments"></i>
                    <p>ยังไม่มีข้อความ เริ่มพิมพ์ส่งข้อความแรกกันเลย! 💙</p>
                </div>`;
            return;
        }

        const currentUser = Auth.currentUser;
        container.innerHTML = this.messages.map(msg => {
            const isMine = msg.user === currentUser;
            const time = this.formatTime(msg.timestamp);
            const avatarUrl = Auth.getProfilePic(msg.user);
            
            return `
                <div class="chat-message-wrapper ${isMine ? 'mine' : 'theirs'}" style="display: flex; gap: 8px; margin-bottom: 15px; flex-direction: ${isMine ? 'row-reverse' : 'row'}; align-items: flex-end;">
                    <img src="${avatarUrl}" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid var(--glass-border); flex-shrink: 0;" alt="${msg.user}">
                    <div class="chat-bubble ${isMine ? 'mine' : 'theirs'}" style="margin-bottom: 0;">
                        ${!isMine ? `<div class="chat-bubble-name">${msg.user}</div>` : ''}
                        <div class="chat-bubble-text">${this.escapeHtml(msg.text)}</div>
                        <div class="chat-bubble-time">${time}</div>
                    </div>
                </div>
            `;
        }).join('');

        // Auto scroll to bottom
        container.scrollTop = container.scrollHeight;
    },

    formatTime(isoString) {
        try {
            const d = new Date(isoString);
            const day = d.getDate();
            const month = d.getMonth() + 1;
            const h = d.getHours().toString().padStart(2, '0');
            const m = d.getMinutes().toString().padStart(2, '0');
            return `${day}/${month} ${h}:${m}`;
        } catch {
            return '';
        }
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
