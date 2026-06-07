// js/timeline.js
const Timeline = {
    init() {
        this.renderTimeline();
    },

    renderTimeline() {
        const container = document.getElementById('timeline-container');
        if (!container) return;

        // Start empty
        const events = [];
        
        if (events.length === 0) {
            container.innerHTML = '<p class="empty-state">ยังไม่มีไทม์ไลน์ความทรงจำ</p>';
            return;
        }

        let html = '<div class="timeline">';
        events.forEach(event => {
            html += `
                <div class="timeline-item glass-panel">
                    <div class="timeline-icon"><i class="fas ${event.icon}"></i></div>
                    <div class="timeline-content">
                        <span class="timeline-date">${event.date}</span>
                        <h3>${event.title}</h3>
                        <p>Type: ${event.type}</p>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        // simple styling injected dynamically or assume in style.css
        const style = document.createElement('style');
        style.textContent = `
            .timeline { position: relative; padding-left: 30px; margin-top: 20px; }
            .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: var(--accent-color); }
            .timeline-item { position: relative; padding: 20px; margin-bottom: 20px; }
            .timeline-icon { position: absolute; left: -36px; top: 20px; background: var(--bg-color); border: 2px solid var(--accent-color); color: var(--accent-color); width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
            .timeline-date { font-size: 0.8rem; color: var(--text-secondary); }
        `;
        document.head.appendChild(style);

        container.innerHTML = html;
    }
};
