// js/api.js
/**
 * API Wrapper for Google Apps Script Backend
 * User must replace SCRIPT_URL with their deployed Apps Script Web App URL.
 */
const API = {
    // Replace this URL after deploying Code.gs as a Web App
    SCRIPT_URL: "https://script.google.com/macros/s/AKfycbzLF1gkqUIL8ti7_pjjhuMHjtHmTFoYXDyXDYMFTT5Z53Wtsj6cNSJ3_jS_OtQdR4nzQw/exec",

    async request(action, payload = {}) {
        // Since we don't have a real URL yet, we mock responses for local dev
        if (this.SCRIPT_URL.includes("YOUR_SCRIPT_ID")) {
            console.warn("Using Mock API. Please set SCRIPT_URL in js/api.js");
            return this.mockRequest(action, payload);
        }

        try {
            const response = await fetch(this.SCRIPT_URL, {
                method: "POST",
                // 'no-cors' might be needed if not handling CORS properly in Apps Script, 
                // but we use form data or text/plain to bypass CORS preflight if needed.
                // However, standard fetch with CORS is best if Apps Script returns proper headers.
                headers: {
                    "Content-Type": "text/plain;charset=utf-8",
                },
                body: JSON.stringify({ action, ...payload })
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("API Request Failed:", error);
            throw error;
        }
    },

    // Mock data for local testing before deployment
    async mockRequest(action, payload) {
        return new Promise((resolve) => {
            setTimeout(() => {
                switch (action) {
                    case "getData":
                        resolve({
                            status: "success",
                            data: {
                                photos: [],
                                diaries: [],
                                settings: {}
                            }
                        });
                        break;
                    case "uploadImage":
                        resolve({
                            status: "success",
                            message: "Mock uploaded",
                            url: "mock-url",
                            id: "mock-id"
                        });
                        break;
                    case "saveDiary":
                        resolve({
                            status: "success",
                            message: "Mock diary saved",
                            id: "mock-id"
                        });
                        break;
                    default:
                        resolve({ status: "success", message: "Mock action complete" });
                }
            }, 500); // simulate network delay
        });
    }
};
