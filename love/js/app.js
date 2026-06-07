// js/app.js
const app = {
    currentView: 'dashboard',

    init() {
        // Initialize Core Components
        ThemeManager.init();
        Auth.init();
        
        // Wait for sub-modules if they exist
        if (typeof Gallery !== 'undefined') Gallery.init();
        if (typeof FaceDetector !== 'undefined') FaceDetector.init();
        if (typeof Diary !== 'undefined') Diary.init();
        if (typeof Chat !== 'undefined') Chat.init();
        if (typeof PhotoEditor !== 'undefined') PhotoEditor.init();
        if (typeof Timeline !== 'undefined') Timeline.init();
        if (typeof Analytics !== 'undefined') Analytics.init();

        this.bindEvents();
        this.updateRelationshipCounter();
        
        // Initial routing if hash exists
        const hash = window.location.hash.substring(1);
        if (hash) {
            this.navigate(hash);
        }
    },

    bindEvents() {
        // Sidebar and Bottom Navigation
        const navLinks = document.querySelectorAll('.nav-links a, .bottom-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = link.dataset.target;
                this.navigate(target);
                
                // Close mobile menu if open
                const sidebar = document.getElementById('sidebar');
                if (sidebar) sidebar.classList.remove('open');
            });
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });
        }
    },

    navigate(viewId) {
        // Hide all views
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.add('hidden');
            section.classList.remove('active');
        });

        // Remove active class from nav
        document.querySelectorAll('.nav-links a, .bottom-nav a').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.target === viewId) {
                link.classList.add('active');
            }
        });

        // Show target view
        const targetView = document.getElementById(`view-${viewId}`);
        if (targetView) {
            targetView.classList.remove('hidden');
            targetView.classList.add('active');
            this.currentView = viewId;
            window.location.hash = viewId;
        } else {
            // Fallback to dashboard
            document.getElementById('view-dashboard').classList.remove('hidden');
            document.getElementById('view-dashboard').classList.add('active');
        }
    },

    showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    },

    hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    },

    milestones: [
        { label: 'ทักมา', date: new Date('2026-01-27'), emoji: '💬' },
        { label: 'เป็นคนคุย', date: new Date('2026-02-06'), emoji: '💙' },
        { label: 'คบกัน', date: new Date('2026-05-29'), emoji: '💕' },
    ],

    updateRelationshipCounter() {
        const counterEl = document.getElementById('relationship-counter');
        if (!counterEl) return;

        const update = () => {
            const now = new Date();
            // Find the most relevant milestone (the latest one that has passed)
            let activeMilestone = this.milestones[0];
            for (const m of this.milestones) {
                if (now >= m.date) activeMilestone = m;
            }
            const diff = now - activeMilestone.date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            counterEl.textContent = `${activeMilestone.emoji} ${activeMilestone.label} ${days} วันแล้ว`;
        };

        update();
        setInterval(update, 60000); // update every minute
    }
};

// Bootstrap application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
