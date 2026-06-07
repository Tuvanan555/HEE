// js/auth.js
const Auth = {
    currentUser: null,
    
    init() {
        this.checkSession();
        this.bindEvents();
    },

    bindEvents() {
        const userBtns = document.querySelectorAll('.btn-user');
        const loginScreen = document.getElementById('login-screen');
        const appLayout = document.getElementById('app-layout');
        const passContainer = document.getElementById('password-container');
        const selectedUserName = document.getElementById('selected-user-name');
        const passInput = document.getElementById('password-input');
        const loginBtn = document.getElementById('login-btn');
        const errorMsg = document.getElementById('login-error');
        const backBtn = document.getElementById('back-to-users');
        const logoutBtn = document.getElementById('logout-btn');

        let selectedUser = '';

        userBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                selectedUser = btn.dataset.user;
                selectedUserName.textContent = `รหัสผ่านสำหรับ ${selectedUser}`;
                document.querySelector('.user-selection').classList.add('hidden');
                passContainer.classList.remove('hidden');
                passInput.focus();
            });
        });

        backBtn.addEventListener('click', () => {
            selectedUser = '';
            passInput.value = '';
            errorMsg.classList.add('hidden');
            passContainer.classList.add('hidden');
            document.querySelector('.user-selection').classList.remove('hidden');
        });

        const performLogin = () => {
            const password = passInput.value;
            if ((selectedUser === 'โน่' && password === 'LoVe') || 
                (selectedUser === 'นัน' && password === 'love')) {
                
                this.login(selectedUser);
                
                // Hide login, show app
                loginScreen.classList.add('hidden');
                appLayout.classList.remove('hidden');
                
            } else {
                errorMsg.classList.remove('hidden');
            }
        };

        loginBtn.addEventListener('click', performLogin);
        passInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performLogin();
        });

        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }

        // Profile Picture Setup
        const btnChangeProfile = document.getElementById('btn-change-profile');
        const profileContainer = document.getElementById('profile-upload-container');
        const btnSaveProfile = document.getElementById('btn-save-profile');
        const profileUrlInput = document.getElementById('profile-url-input');

        if (btnChangeProfile) {
            btnChangeProfile.addEventListener('click', () => {
                profileContainer.classList.toggle('hidden');
            });
        }

        if (btnSaveProfile) {
            btnSaveProfile.addEventListener('click', () => {
                const url = profileUrlInput.value.trim();
                if (url && this.currentUser) {
                    this.setProfilePic(this.currentUser, url);
                    this.updateProfileUI();
                    profileContainer.classList.add('hidden');
                    profileUrlInput.value = '';
                }
            });
        }
    },

    getProfilePic(user) {
        const customPic = localStorage.getItem(`profile_pic_${user}`);
        if (customPic) return customPic;
        return user === 'โน่' ? 
            'https://api.dicebear.com/7.x/avataaars/svg?seed=Noh' : 
            'https://api.dicebear.com/7.x/avataaars/svg?seed=Nan';
    },

    setProfilePic(user, url) {
        localStorage.setItem(`profile_pic_${user}`, url);
    },

    updateProfileUI() {
        if (!this.currentUser) return;
        
        // Settings UI
        const currentProfileImg = document.getElementById('current-profile-img');
        const currentProfileName = document.getElementById('current-profile-name');
        if (currentProfileImg) currentProfileImg.src = this.getProfilePic(this.currentUser);
        if (currentProfileName) currentProfileName.textContent = this.currentUser;

        // Top Nav UI
        const nameEl = document.getElementById('current-user-name');
        const avatarEl = document.getElementById('current-user-avatar');
        if (nameEl) nameEl.textContent = `สวัสดี, ${this.currentUser}`;
        if (avatarEl) avatarEl.src = this.getProfilePic(this.currentUser);

        // Update login screen if it's visible (for next time)
        const nohImg = document.querySelector('.btn-user[data-user="โน่"] img');
        const nanImg = document.querySelector('.btn-user[data-user="นัน"] img');
        if (nohImg) nohImg.src = this.getProfilePic('โน่');
        if (nanImg) nanImg.src = this.getProfilePic('นัน');
    },

    checkSession() {
        const sessionUser = localStorage.getItem('love_memory_user');
        if (sessionUser) {
            this.login(sessionUser);
            document.getElementById('login-screen').classList.add('hidden');
            document.getElementById('app-layout').classList.remove('hidden');
        }
    },

    login(user) {
        this.currentUser = user;
        localStorage.setItem('love_memory_user', user);
        
        this.updateProfileUI();

        // Admin Panel Logic
        const adminPanel = document.getElementById('admin-panel');
        if (adminPanel) {
            if (user === 'โน่') {
                adminPanel.classList.remove('hidden');
                document.getElementById('btn-clear-cache').onclick = () => {
                    if(confirm('ยืนยันล้างข้อมูลแคชและเริ่มระบบใหม่?')) {
                        localStorage.clear();
                        window.location.reload();
                    }
                };
            } else {
                adminPanel.classList.add('hidden');
            }
        }
    },

    logout() {
        localStorage.removeItem('love_memory_user');
        this.currentUser = null;
        window.location.reload();
    }
};
