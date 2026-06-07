// js/analytics.js
const Analytics = {
    moodChart: null,
    activityChart: null,

    init() {
        // Only initialize when dashboard is active or stats are opened
        this.renderCharts();
    },

    renderCharts() {
        const moodCtx = document.getElementById('mood-chart');
        const activityCtx = document.getElementById('activity-chart');

        if (!moodCtx || !activityCtx) return;

        // Mock Data
        const moodData = {
            labels: ['😊 Happy', '🥰 Love', '😢 Sad', '😡 Angry', '😴 Tired'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: [
                    '#10B981', // green
                    '#F43F5E', // pink
                    '#3B82F6', // blue
                    '#EF4444', // red
                    '#8B5CF6'  // purple
                ],
                borderWidth: 0
            }]
        };

        const activityData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Photos',
                data: [0, 0, 0, 0, 0, 0],
                borderColor: '#38BDF8',
                tension: 0.4
            }, {
                label: 'Diaries',
                data: [0, 0, 0, 0, 0, 0],
                borderColor: '#F43F5E',
                tension: 0.4
            }]
        };

        this.moodChart = new Chart(moodCtx, {
            type: 'doughnut',
            data: moodData,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#F8FAFC' } },
                    title: { display: true, text: 'Mood Distribution', color: '#F8FAFC' }
                }
            }
        });

        this.activityChart = new Chart(activityCtx, {
            type: 'line',
            data: activityData,
            options: {
                responsive: true,
                scales: {
                    y: { ticks: { color: '#94A3B8' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                    x: { ticks: { color: '#94A3B8' }, grid: { color: 'rgba(255,255,255,0.1)' } }
                },
                plugins: {
                    legend: { labels: { color: '#F8FAFC' } },
                    title: { display: true, text: 'Activity Over Time', color: '#F8FAFC' }
                }
            }
        });
    }
};
