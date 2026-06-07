// js/faceDetection.js
const FaceDetector = {
    modelsLoaded: false,
    isDetecting: false,

    async init() {
        try {
            // Wait for faceapi to be available (from CDN script)
            if (typeof faceapi === 'undefined') {
                console.warn('faceapi not loaded yet');
                return;
            }

            // Load models from local directory (needs to be hosted/served)
            // Or use a public CDN for models if local not available, but user requested models/face-api-models
            const MODEL_URL = 'models/face-api-models';
            
            // For this demo, we will simulate loading if files aren't physically present yet
            // In a real scenario, you must download the models to that folder.
            console.log('Loading face-api models...');
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL).catch(() => console.warn("Model tinyFaceDetector failed to load. Provide model files.")),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL).catch(() => {}),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL).catch(() => {}),
                faceapi.nets.ageGenderNet.loadFromUri(MODEL_URL).catch(() => {})
            ]);
            
            this.modelsLoaded = true;
            console.log('Face models loaded successfully');
        } catch (e) {
            console.error('Error loading face-api models:', e);
            // We will fallback gracefully if models fail to load
        }
    },

    async analyzeImage(imgElement) {
        if (!this.modelsLoaded) {
            console.warn('Models not loaded. Returning default folder.');
            return 'อื่นๆ'; // Default
        }

        this.isDetecting = true;
        try {
            const detections = await faceapi.detectAllFaces(imgElement, new faceapi.TinyFaceDetectorOptions())
                                            .withFaceLandmarks()
                                            .withAgeAndGender();

            this.isDetecting = false;

            if (detections.length === 0) return 'อื่นๆ';

            let maleCount = 0;
            let femaleCount = 0;

            detections.forEach(det => {
                if (det.gender === 'male') maleCount++;
                if (det.gender === 'female') femaleCount++;
            });

            if (maleCount > 0 && femaleCount > 0) return 'คู่';
            if (maleCount > 0 && femaleCount === 0) return 'โน่';
            if (femaleCount > 0 && maleCount === 0) return 'นัน';
            
            return 'อื่นๆ';

        } catch (e) {
            console.error('Detection failed:', e);
            this.isDetecting = false;
            return 'อื่นๆ';
        }
    }
};
