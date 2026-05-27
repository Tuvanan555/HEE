/* ============================================
   DepreSim AI — Chatbot Application Logic
   Mental Health Chatbot for Depression Simulation
   ============================================ */

// ──────────────── API Configuration ────────────────
const GROQ_API_KEY = "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";

// ──────────────── Crisis Keywords ────────────────
const CRISIS_KEYWORDS = [
  'ฆ่าตัวตาย', 'อยากตาย', 'ไม่อยากมีชีวิต', 'ทำร้ายตัวเอง',
  'กรีดข้อมือ', 'กินยาตาย', 'ตายดีกว่า', 'หมดหวัง', 'ไม่ไหวแล้ว',
  'ตายไปเลย', 'ไม่อยากอยู่', 'เบื่อชีวิต', 'อยากจบชีวิต',
  'ชีวิตไม่มีค่า', 'suicide', 'kill myself', 'อยากหายไป',
  'ไม่มีใครสนใจ', 'ไม่มีใครรัก', 'โลกไม่ต้องการ',
  // คำสะกดผิด / แสลง / คำพ้องความหมาย / วิธีการ
  'หยากตาย', 'ตุย', 'อยากตุย', 'ขิต', 'สู่ขิต', 'อยากขิต', 'คิวเซฟ',
  'ตายๆไป', 'ไม่อยากหายใจ', 'หยุดหายใจ', 'ไม่อยากตื่น', 'หลับไม่ตื่น',
  'กรีดแขน', 'โดดตึก', 'กระโดดตึก', 'ผูกคอ', 'แขวนคอ', 'กินยาเกินขนาด',
  'เหนื่อยกับชีวิต', 'ไม่มีค่าพอ', 'อยู่ไปก็ไร้ค่า', 'suiside'
];

const CRISIS_RESPONSE = `🚨 <strong>เราเป็นห่วงคุณมากค่ะ</strong>\n\nสิ่งที่คุณรู้สึกอยู่ตอนนี้สำคัญมาก และมีคนพร้อมรับฟังและช่วยเหลือคุณ\n\n📞 <strong>กรุณาโทรหาสายด่วนสุขภาพจิตทันที:</strong>\n<span class="highlight-number" style="font-size:1.4rem;">☎️ 1323</span>\n(เปิดให้บริการ 24 ชั่วโมง ไม่เสียค่าใช้จ่าย)\n\nคุณไม่ได้อยู่คนเดียวนะคะ ความรู้สึกแย่ๆ เหล่านี้เป็นอาการของโรคที่รักษาได้ — ไม่ใช่ความจริงเกี่ยวกับตัวคุณ\n\nถ้าอยู่ใกล้คนที่ไว้ใจได้ ลองบอกเขาเกี่ยวกับสิ่งที่คุณรู้สึกด้วยนะคะ 💜`;

// ──────────────── System Prompt & History ────────────────
const SYSTEM_PROMPT = `คุณคือ "DepreSim AI" ผู้ช่วยด้านสุขภาพจิตประจำเกม "Depression Simulation" ซึ่งพัฒนาโดย ด.ช. ธุวานันท์ ไชยวงศ์ชินเดช, ด.ช. ธนดล ทืนรส, ด.ญ. สุพรรณี จันทร์งาม จากโรงเรียนศรีเมืองวิทยาคาร จ.อุบลราชธานี (ที่ปรึกษา: น.ส. สุภวรรณ ธิวงศ์ษา)
คุณมีหน้าที่ตอบคำถามเกี่ยวกับโรคซึมเศร้า อาการ สาเหตุ การรับมือ และการช่วยเหลือผู้อื่น
บุคลิกของคุณ: เป็นมิตร อ่อนโยน เข้าอกเข้าใจ ไม่ตัดสิน ไม่ใช้ศัพท์วิชาการมากเกินไป ใช้ภาษาไทยที่เข้าใจง่าย ใช้ "ค่ะ" ลงท้ายประโยค และมักใช้อีโมจิ 💜

ข้อมูลความรู้ที่คุณต้องใช้:
1. นิยาม (WHO): โรคซึมเศร้า (Depressive Disorders) มีลักษณะคือ เศร้า สูญเสียความสนใจ รู้สึกผิดและไร้ค่า นอนไม่หลับ ไม่อยากอาหาร เหนื่อยล้า สมาธิแย่ลง รบกวนการใช้ชีวิต ไม่ใช่แค่ "คิดมาก" หรือ "อ่อนแอ"
2. สาเหตุทางชีวภาพ (5 ข้อ): พันธุกรรม, โครงสร้างสมอง (เลือดและกลูโคสไปเลี้ยงสมองส่วนหน้าลดลง), สารสื่อประสาท (เซโรโทนิน/นอร์เอพิเนฟรินน้อยลง), ฮอร์โมน (HPA axis ทำงานมากขึ้นเมื่อเครียด), การนอนผิดปกติ
3. สาเหตุทางจิตสังคม (4 ข้อ): พลวัตทางจิต (ปมในอดีต), มุมมองความคิดเชิงลบ, พฤติกรรม (ล้มเหลวซ้ำๆ), สัมพันธภาพ/สังคม
4. อาการ (5 กลุ่ม): อารมณ์ (หดหู่), ความคิด (โทษตัวเอง อยากตาย), จิตใจและการเคลื่อนไหว (ช้าลงหรือกระสับกระส่าย), ทางกาย (นอน/กินเปลี่ยนไป), สัมพันธภาพ (แยกตัว)
5. ปัญหาทักษะสังคม (8 ข้อ): หมดความสนใจ, กลัวถูกปฏิเสธ, ยอมถูกเอาเปรียบ, ตีความอารมณ์ผู้อื่นแง่ลบ, ขาดความร่วมมือ, ขาดความเห็นอกเห็นใจ, ใช้โซเชียลผิดปกติ, ขาดเครือข่ายสังคม
6. แบบประเมิน: 2Q (2 คำถาม คัดกรอง), 9Q (9 คำถาม ประเมินความรุนแรง <7 ไม่มี, 7-12 น้อย, 13-18 ปานกลาง, >=19 รุนแรง), 8Q (8 คำถาม ประเมินฆ่าตัวตาย >=17 รุนแรง)
7. เกมจำลองสถานการณ์โรคซึมเศร้า: เป็นเกมมุมมอง 2D Top-down ผู้เล่นเผชิญสถานการณ์และต้องตัดสินใจ เพื่อฝึก Empathy

ข้อควรระวัง:
- ห้ามวินิจฉัยโรคเด็ดขาด ให้แนะนำว่าควรพบแพทย์
- หากพบผู้ใช้งานมีความเสี่ยงอยากฆ่าตัวตาย ให้แนะนำสายด่วน 1323 ทันที
- ตอบให้ตรงคำถามแบบกระชับ ถ้ามีข้อมูลเยอะให้เว้นบรรทัดให้อ่านง่าย ถ้ามีการจัดรูปแบบให้ใช้ HTML tag พื้นฐาน เช่น <strong> หรือ <ul> ได้เลย`;

let conversationHistory = [
  { role: "system", content: SYSTEM_PROMPT }
];

// ──────────────── Core Logic ────────────────

/**
 * Fetch response from Groq API
 */
async function getGroqResponse(userMessage) {
  const msg = userMessage.trim();
  if (!msg) return null;

  // 1. Check crisis keywords FIRST (highest priority)
  const lowerMsg = msg.toLowerCase();
  for (const keyword of CRISIS_KEYWORDS) {
    if (lowerMsg.includes(keyword.toLowerCase())) {
      return { text: CRISIS_RESPONSE, isEmergency: true };
    }
  }

  // 2. Call Groq API
  conversationHistory.push({ role: "user", content: msg });

  try {
    const response = await fetch("https://api.groq.com/openai/v1/chat/completions", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + GROQ_API_KEY
      },
      body: JSON.stringify({
        model: "llama-3.3-70b-versatile",
        messages: conversationHistory,
        temperature: 0.7,
        max_tokens: 1024
      })
    });

    if (!response.ok) {
      throw new Error("API request failed");
    }

    const data = await response.json();
    let aiMessage = data.choices[0].message.content;

    // Convert markdown bold to html strong for rendering
    aiMessage = aiMessage.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Add to history
    conversationHistory.push({ role: "assistant", content: aiMessage });

    return { text: aiMessage, isEmergency: false };
  } catch (error) {
    console.error("Groq API Error:", error);
    // Remove the last user message from history so they can retry
    conversationHistory.pop();
    return {
      text: "ขออภัยค่ะ ตอนนี้ระบบของเรากำลังมีปัญหานิดหน่อย 🥺 รบกวนลองส่งข้อความใหม่อีกครั้งนะคะ 💜",
      isEmergency: false
    };
  }
}

// ──────────────── DOM References ────────────────
const chatContainer = document.getElementById('chat-messages');
const inputField = document.getElementById('message-input');
const sendButton = document.getElementById('send-btn');
const quickButtons = document.querySelectorAll('.quick-actions__btn');

// ──────────────── Chat Functions ────────────────

/**
 * Append a message bubble to the chat
 */
function addMessage(text, sender, isEmergency = false) {
  // Remove welcome card if present
  const welcomeCard = chatContainer.querySelector('.welcome-card');
  if (welcomeCard) {
    welcomeCard.style.animation = 'none';
    welcomeCard.style.opacity = '0';
    welcomeCard.style.transition = 'opacity 0.3s ease';
    setTimeout(() => welcomeCard.remove(), 300);
  }

  const messageDiv = document.createElement('div');
  messageDiv.className = `message message--${sender}`;
  if (isEmergency) messageDiv.classList.add('message--emergency');

  const avatar = document.createElement('div');
  avatar.className = 'message__avatar';
  avatar.textContent = sender === 'ai' ? '🧠' : '💬';

  const bubble = document.createElement('div');
  bubble.className = 'message__bubble';
  bubble.innerHTML = text.replace(/\n/g, '<br>');

  // Add TTS button for AI messages
  if (sender === 'ai') {
    const ttsBtn = document.createElement('button');
    ttsBtn.className = 'tts-btn';
    ttsBtn.innerHTML = '🔊 ฟังเสียง';
    ttsBtn.addEventListener('click', () => speakText(text, ttsBtn));
    bubble.appendChild(ttsBtn);
  }

  messageDiv.appendChild(avatar);
  messageDiv.appendChild(bubble);
  chatContainer.appendChild(messageDiv);

  // Scroll to bottom
  requestAnimationFrame(() => {
    chatContainer.scrollTop = chatContainer.scrollHeight;
  });
}

/**
 * Show typing indicator
 */
function showTypingIndicator() {
  const indicator = document.createElement('div');
  indicator.className = 'typing-indicator';
  indicator.id = 'typing-indicator';
  indicator.innerHTML = `
    <div class="typing-indicator__avatar">🧠</div>
    <div class="typing-indicator__dots">
      <div class="typing-indicator__dot"></div>
      <div class="typing-indicator__dot"></div>
      <div class="typing-indicator__dot"></div>
    </div>
  `;
  chatContainer.appendChild(indicator);
  chatContainer.scrollTop = chatContainer.scrollHeight;
}

/**
 * Remove typing indicator
 */
function removeTypingIndicator() {
  const indicator = document.getElementById('typing-indicator');
  if (indicator) indicator.remove();
}

/**
 * Handle sending a message
 */
async function handleSend() {
  const text = inputField.value.trim();
  if (!text) return;

  // Add user message
  addMessage(text, 'user');

  // Clear input
  inputField.value = '';
  inputField.style.height = 'auto';
  sendButton.disabled = true;

  // Show typing indicator
  showTypingIndicator();

  // Get AI response from Groq
  const result = await getGroqResponse(text);

  removeTypingIndicator();
  if (result) {
    addMessage(result.text, 'ai', result.isEmergency);
  }
}

// ──────────────── Event Listeners ────────────────

// Send button click
sendButton.addEventListener('click', handleSend);

// Enter key (Shift+Enter for newline)
inputField.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    handleSend();
  }
});

// Auto-resize textarea
inputField.addEventListener('input', () => {
  inputField.style.height = 'auto';
  inputField.style.height = Math.min(inputField.scrollHeight, 120) + 'px';

  // Enable/disable send button
  sendButton.disabled = !inputField.value.trim();
});

// Quick action buttons
quickButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const question = btn.dataset.question;
    if (question) {
      inputField.value = question;
      handleSend();
    }
  });
});

// Initial state
sendButton.disabled = true;

// ──────────────── Tab Navigation ────────────────
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    // Remove active class from all tabs
    tabBtns.forEach(b => b.classList.remove('active'));
    tabContents.forEach(c => {
      c.classList.remove('active-tab');
      c.style.display = 'none';
    });

    // Add active class to clicked tab
    btn.classList.add('active');
    const tabId = `tab-${btn.dataset.tab}`;
    const targetTab = document.getElementById(tabId);
    if (targetTab) {
      targetTab.classList.add('active-tab');
      targetTab.style.display = 'flex';
    }
  });
});

// ──────────────── Assessment (9Q) Logic ────────────────
const questions9Q = [
  "1. เบื่อ ไม่สนใจอยากทำอะไร",
  "2. ไม่สบายใจ ซึมเศร้า ท้อแท้",
  "3. หลับยาก หรือหลับๆ ตื่นๆ หรือหลับมากไป",
  "4. เหนื่อยง่าย หรือ ไม่ค่อยมีแรง",
  "5. เบื่ออาหาร หรือ กินมากเกินไป",
  "6. รู้สึกไม่ดีกับตัวเอง คิดว่าตัวเองล้มเหลว หรือทำให้ตนเองหรือครอบครัวผิดหวัง",
  "7. สมาธิไม่ดีเวลาทำอะไร เช่น ดูโทรทัศน์ ฟังวิทยุ หรือทำงานที่ต้องใช้ความตั้งใจ",
  "8. พูดช้า ทำอะไรช้าลง จนคนอื่นสังเกตเห็นได้ หรือกระสับกระส่ายไม่สามารถอยู่นิ่งได้เหมือนที่เคยเป็น",
  "9. คิดทำร้ายตนเอง หรือคิดว่าถ้าตายไปคงจะดี"
];

const options9Q = [
  { value: 0, label: "ไม่มีเลย" },
  { value: 1, label: "เป็นบางวัน (1-7 วัน)" },
  { value: 2, label: "เป็นบ่อย (มากกว่า 7 วัน)" },
  { value: 3, label: "เป็นทุกวัน" }
];

const questionList = document.getElementById('question-list');
const assessmentForm = document.getElementById('assessment-form');
const assessmentResult = document.getElementById('assessment-result');
const scoreValue = document.getElementById('score-value');
const resultText = document.getElementById('result-text');
const resultAction = document.getElementById('result-action');
const btnResetAssessment = document.getElementById('btn-reset-assessment');

// Initialize questions
if (questionList) {
  questions9Q.forEach((q, index) => {
    const qDiv = document.createElement('div');
    qDiv.className = 'question-item';
    
    const qText = document.createElement('div');
    qText.className = 'question-text';
    qText.textContent = q;
    qDiv.appendChild(qText);

    const optionsGroup = document.createElement('div');
    optionsGroup.className = 'options-group';

    options9Q.forEach(opt => {
      const label = document.createElement('label');
      label.className = 'option-label';
      label.innerHTML = `
        <input type="radio" name="q${index}" value="${opt.value}" required>
        <span>${opt.label}</span>
      `;
      optionsGroup.appendChild(label);
    });

    qDiv.appendChild(optionsGroup);
    questionList.appendChild(qDiv);
  });
}

// Handle form submission
if (assessmentForm) {
  assessmentForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData(assessmentForm);
    let totalScore = 0;
    let allAnswered = true;

    for (let i = 0; i < questions9Q.length; i++) {
      const val = formData.get(`q${i}`);
      if (val === null) {
        allAnswered = false;
        break;
      }
      totalScore += parseInt(val, 10);
    }

    if (!allAnswered) {
      alert("กรุณาตอบคำถามให้ครบทุกข้อค่ะ");
      return;
    }

    // Display result
    assessmentForm.style.display = 'none';
    assessmentResult.style.display = 'block';
    scoreValue.textContent = totalScore;

    let resultMsg = "";
    let actionHtml = "";

    if (totalScore < 7) {
      resultMsg = "ไม่มีอาการซึมเศร้า หรือมีอาการในระดับปกติค่ะ 💚";
    } else if (totalScore >= 7 && totalScore <= 12) {
      resultMsg = "มีอาการซึมเศร้าระดับน้อย ควรพักผ่อนและพูดคุยกับคนใกล้ชิดนะคะ 💛";
    } else if (totalScore >= 13 && totalScore <= 18) {
      resultMsg = "มีอาการซึมเศร้าระดับปานกลาง แนะนำให้ลองปรึกษาผู้เชี่ยวชาญค่ะ 🧡";
    } else {
      resultMsg = "มีอาการซึมเศร้าระดับรุนแรง ควรพบแพทย์หรือโทรสายด่วนสุขภาพจิตทันทีค่ะ ❤️";
      actionHtml = `
        <div style="margin-top: 15px; padding: 15px; background: rgba(252, 129, 129, 0.2); border-radius: 8px;">
          <strong>📞 สายด่วนสุขภาพจิต 1323 (โทรฟรี 24 ชม.)</strong><br>
          มีคนพร้อมรับฟังและช่วยเหลือคุณอยู่นะคะ
        </div>
      `;
    }

    resultText.innerHTML = resultMsg;
    resultAction.innerHTML = actionHtml;
    
    if(btnResetAssessment) {
        btnResetAssessment.style.display = 'block';
        assessmentForm.appendChild(btnResetAssessment); // ensure it's outside the hidden form? No, I'll place it outside. Wait, in HTML it's inside the form.
        // Actually, it's better to just show it. Let's move it out of form in HTML or just leave it. In HTML btnResetAssessment is inside the form, but if form is display:none, button is hidden too.
    }
  });
}

// Handle reset
if (btnResetAssessment) {
  btnResetAssessment.addEventListener('click', () => {
    assessmentForm.reset();
    assessmentForm.style.display = 'block';
    assessmentResult.style.display = 'none';
    btnResetAssessment.style.display = 'none';
    
    // Reset scroll
    const container = document.querySelector('.assessment-container');
    if (container) container.scrollTop = 0;
  });
}

/* ============================================
   NEW FEATURES LOGIC
   ============================================ */

// ──────────────── Mood Tracker ────────────────
const moodHistoryEl = document.getElementById('mood-history');
const clearMoodBtn = document.getElementById('clear-mood-history');
const moodBtns = document.querySelectorAll('.mood-btn');
const MOOD_STORAGE_KEY = 'depresim_mood_history';

function loadMoodHistory() {
  if (!moodHistoryEl) return;
  const history = JSON.parse(localStorage.getItem(MOOD_STORAGE_KEY) || '[]');
  moodHistoryEl.innerHTML = '';
  if (history.length === 0) {
    moodHistoryEl.innerHTML = '<p style="color: var(--text-muted); text-align: center; margin-top: 20px;">ยังไม่มีประวัติการบันทึกอารมณ์</p>';
    return;
  }
  history.forEach(item => {
    const div = document.createElement('div');
    div.className = 'mood-history-item';
    const date = new Date(item.timestamp).toLocaleString('th-TH');
    div.innerHTML = `
      <div>
        <span style="font-size: 1.5rem; margin-right: 10px;">${item.emoji}</span>
        <span>${item.label}</span>
      </div>
      <div style="font-size: 0.8rem; color: var(--text-muted);">${date}</div>
    `;
    moodHistoryEl.appendChild(div);
  });
}

moodBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    const mood = btn.dataset.mood;
    // Extract emoji exactly
    const emojiNode = Array.from(btn.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim().length > 0);
    const emoji = emojiNode ? emojiNode.textContent.trim() : '✨';
    const label = btn.querySelector('span').textContent;
    const history = JSON.parse(localStorage.getItem(MOOD_STORAGE_KEY) || '[]');
    history.unshift({ mood, emoji, label, timestamp: new Date().toISOString() });
    localStorage.setItem(MOOD_STORAGE_KEY, JSON.stringify(history));
    loadMoodHistory();
    
    // Add visual feedback
    const originalText = btn.querySelector('span').textContent;
    btn.querySelector('span').textContent = 'บันทึกแล้ว!';
    setTimeout(() => {
      btn.querySelector('span').textContent = originalText;
    }, 1500);
  });
});

if (clearMoodBtn) {
  clearMoodBtn.addEventListener('click', () => {
    if (confirm('ต้องการล้างประวัติอารมณ์ทั้งหมดหรือไม่?')) {
      localStorage.removeItem(MOOD_STORAGE_KEY);
      loadMoodHistory();
    }
  });
}
loadMoodHistory();

// ──────────────── Breathing Exercise ────────────────
const startBreathingBtn = document.getElementById('start-breathing');
const stopBreathingBtn = document.getElementById('stop-breathing');
const breathingCircle = document.getElementById('breathing-circle');
const breathingText = document.getElementById('breathing-text');
let breathingInterval;
let breathingTimeout1, breathingTimeout2;

function startBreathing() {
  startBreathingBtn.classList.add('hidden');
  stopBreathingBtn.classList.remove('hidden');
  
  function cycle() {
    // Inhale (4s)
    breathingText.textContent = 'หายใจเข้า...';
    breathingCircle.style.transform = 'scale(1.5)';
    breathingCircle.style.transition = 'transform 4s linear';
    
    breathingTimeout1 = setTimeout(() => {
      // Hold (7s)
      breathingText.textContent = 'กลั้นไว้...';
      breathingCircle.style.transform = 'scale(1.5)';
      breathingCircle.style.transition = 'none';
      
      breathingTimeout2 = setTimeout(() => {
        // Exhale (8s)
        breathingText.textContent = 'หายใจออก...';
        breathingCircle.style.transform = 'scale(1)';
        breathingCircle.style.transition = 'transform 8s linear';
        
      }, 7000);
    }, 4000);
  }
  
  cycle();
  breathingInterval = setInterval(cycle, 19000); // 4 + 7 + 8 = 19s
}

function stopBreathing() {
  clearInterval(breathingInterval);
  clearTimeout(breathingTimeout1);
  clearTimeout(breathingTimeout2);
  
  startBreathingBtn.classList.remove('hidden');
  stopBreathingBtn.classList.add('hidden');
  breathingText.textContent = 'พร้อมไหม?';
  breathingCircle.style.transform = 'scale(1)';
  breathingCircle.style.transition = 'transform 0.5s ease';
}

if (startBreathingBtn) {
  startBreathingBtn.addEventListener('click', startBreathing);
  stopBreathingBtn.addEventListener('click', stopBreathing);
}

// ──────────────── Relaxing Sounds ────────────────
const soundCards = document.querySelectorAll('.sound-card');
const masterVolume = document.getElementById('volume-control');

soundCards.forEach(card => {
  const btn = card.querySelector('.play-btn');
  const audio = card.querySelector('audio');
  
  btn.addEventListener('click', () => {
    const isPlaying = card.classList.contains('playing');
    
    // Pause all other sounds
    soundCards.forEach(c => {
      c.classList.remove('playing');
      c.querySelector('audio').pause();
      c.querySelector('.play-btn').textContent = '▶ เล่น';
    });

    if (!isPlaying) {
      audio.volume = masterVolume ? masterVolume.value : 0.5;
      audio.play().catch(e => console.error("Audio play failed", e));
      card.classList.add('playing');
      btn.textContent = '⏸ หยุด';
    } else {
      audio.pause();
      card.classList.remove('playing');
      btn.textContent = '▶ เล่น';
    }
  });
});

if (masterVolume) {
  masterVolume.addEventListener('input', (e) => {
    const vol = e.target.value;
    soundCards.forEach(card => {
      const audio = card.querySelector('audio');
      if (audio) audio.volume = vol;
    });
  });
}

// ──────────────── Private Diary ────────────────
const diaryTextarea = document.getElementById('diary-textarea');
const saveDiaryBtn = document.getElementById('save-diary-btn');
const diaryEntriesEl = document.getElementById('diary-entries');
const DIARY_STORAGE_KEY = 'depresim_diary_entries';

function loadDiaryEntries() {
  if (!diaryEntriesEl) return;
  const entries = JSON.parse(localStorage.getItem(DIARY_STORAGE_KEY) || '[]');
  diaryEntriesEl.innerHTML = '';
  if (entries.length === 0) {
    diaryEntriesEl.innerHTML = '<p style="color: var(--text-muted); text-align: center;">ยังไม่มีบันทึกไดอารี่ เขียนเรื่องราวของคุณได้เลยค่ะ 💜</p>';
    return;
  }
  entries.forEach((item, index) => {
    const div = document.createElement('div');
    div.className = 'diary-entry';
    const date = new Date(item.timestamp).toLocaleString('th-TH');
    
    div.innerHTML = `
      <div class="diary-entry-date">${date}</div>
      <div class="diary-entry-text"></div>
      <button class="delete-diary-btn" data-index="${index}" title="ลบบันทึกนี้">❌</button>
    `;
    
    // Use textContent to prevent XSS
    div.querySelector('.diary-entry-text').textContent = item.text;
    diaryEntriesEl.appendChild(div);
  });
  
  // Attach delete events
  document.querySelectorAll('.delete-diary-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (confirm('ต้องการลบบันทึกนี้หรือไม่?')) {
        const idx = e.target.dataset.index;
        const currentEntries = JSON.parse(localStorage.getItem(DIARY_STORAGE_KEY) || '[]');
        currentEntries.splice(idx, 1);
        localStorage.setItem(DIARY_STORAGE_KEY, JSON.stringify(currentEntries));
        loadDiaryEntries();
      }
    });
  });
}

if (saveDiaryBtn) {
  saveDiaryBtn.addEventListener('click', () => {
    const text = diaryTextarea.value.trim();
    if (!text) return;
    
    const entries = JSON.parse(localStorage.getItem(DIARY_STORAGE_KEY) || '[]');
    entries.unshift({ text, timestamp: new Date().toISOString() });
    localStorage.setItem(DIARY_STORAGE_KEY, JSON.stringify(entries));
    
    diaryTextarea.value = '';
    loadDiaryEntries();
    
    // Feedback
    const originalText = saveDiaryBtn.textContent;
    saveDiaryBtn.textContent = 'บันทึกสำเร็จ! 💜';
    setTimeout(() => {
      saveDiaryBtn.textContent = originalText;
    }, 2000);
  });
}
loadDiaryEntries();

/* ============================================
   DAILY AFFIRMATIONS & ANIMATIONS
   ============================================ */

// ──────────────── Background Particles ────────────────
const particlesContainer = document.getElementById('particles-container');
if (particlesContainer) {
  const particleCount = 20;
  for (let i = 0; i < particleCount; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.left = Math.random() * 100 + 'vw';
    p.style.animationDuration = (Math.random() * 10 + 10) + 's'; // 10s to 20s
    p.style.animationDelay = (Math.random() * 10) + 's';
    particlesContainer.appendChild(p);
  }
}

// ──────────────── Daily Affirmations ────────────────
const affirmations = [
  "คุณเก่งมากแล้วนะที่ผ่านวันนี้มาได้ 💜",
  "ไม่เป็นไรนะที่จะรู้สึกเหนื่อยบ้าง พักผ่อนเถอะ 🌿",
  "คุณมีค่าเสมอ ไม่ว่าจะเกิดอะไรขึ้น ✨",
  "รอยยิ้มของคุณคือสิ่งที่สวยงามที่สุดในโลก 😊",
  "ทุกๆ วันคือการเริ่มต้นใหม่ ค่อยๆ ก้าวไปนะ 🌅",
  "ขอบคุณที่เข้มแข็งมาจนถึงตอนนี้นะ 💖",
  "คุณไม่ได้อยู่คนเดียวนะ เราพร้อมรับฟังเสมอ 🧠",
  "ความพยายามของคุณมีความหมายเสมอ 🌸"
];

const affirmationText = document.getElementById('affirmation-text');
if (affirmationText) {
  const randomAffirmation = affirmations[Math.floor(Math.random() * affirmations.length)];
  // Typewriter effect for affirmation
  affirmationText.textContent = '';
  let i = 0;
  function typeWriter() {
    if (i < randomAffirmation.length) {
      affirmationText.textContent += randomAffirmation.charAt(i);
      i++;
      setTimeout(typeWriter, 50);
    }
  }
  setTimeout(typeWriter, 500); // Start after slightly delay
}

// ──────────────── Typing Effect for Welcome Card ────────────────
const welcomeDesc = document.querySelector('.welcome-card__desc');
if (welcomeDesc) {
  const originalHTML = welcomeDesc.innerHTML;
  welcomeDesc.innerHTML = '';
  welcomeDesc.classList.add('typing-effect');
  
  let tempDiv = document.createElement('div');
  tempDiv.innerHTML = originalHTML;
  let textToType = tempDiv.textContent; // simple text typing
  
  // A simple typing effect for the welcome message
  // To preserve HTML tags like <br>, we'll just fade in the card and use CSS animation, 
  // but since we want typing, we will simulate it.
  welcomeDesc.innerHTML = originalHTML; // restore for now, as complex HTML typing is tricky
  welcomeDesc.style.opacity = '0';
  setTimeout(() => {
    welcomeDesc.style.transition = 'opacity 1s ease';
    welcomeDesc.style.opacity = '1';
  }, 500);
}

// ──────────────── 2Q Assessment Logic ────────────────
const questions2Q = [
  "1. ใน 2 สัปดาห์ที่ผ่านมารวมวันนี้ ท่านรู้สึก หดหู่ เศร้า หรือท้อแท้สิ้นหวัง หรือไม่?",
  "2. ใน 2 สัปดาห์ที่ผ่านมารวมวันนี้ ท่านรู้สึก เบื่อ ทำอะไรก็ไม่เพลิดเพลิน หรือไม่?"
];
const options2Q = [
  { value: 0, label: "ไม่มี" },
  { value: 1, label: "มี" }
];

const questionList2Q = document.getElementById('question-list-2q');
if (questionList2Q) {
  questions2Q.forEach((q, index) => {
    const qDiv = document.createElement('div');
    qDiv.className = 'question-item';
    
    const qText = document.createElement('div');
    qText.className = 'question-text';
    qText.textContent = q;
    qDiv.appendChild(qText);

    const optionsGroup = document.createElement('div');
    optionsGroup.className = 'options-group';

    options2Q.forEach(opt => {
      const label = document.createElement('label');
      label.className = 'option-label';
      label.innerHTML = `
        <input type="radio" name="q2q${index}" value="${opt.value}" required>
        <span>${opt.label}</span>
      `;
      optionsGroup.appendChild(label);
    });

    qDiv.appendChild(optionsGroup);
    questionList2Q.appendChild(qDiv);
  });
}

// Assessment UI Switching
function showAssessmentMenu() {
  document.getElementById('assessment-menu').style.display = 'block';
  document.getElementById('assessment-2q-container').style.display = 'none';
  document.getElementById('assessment-9q-container').style.display = 'none';
}

function showAssessment2Q() {
  document.getElementById('assessment-menu').style.display = 'none';
  document.getElementById('assessment-2q-container').style.display = 'block';
  document.getElementById('assessment-9q-container').style.display = 'none';
}

function showAssessment9Q() {
  document.getElementById('assessment-menu').style.display = 'none';
  document.getElementById('assessment-2q-container').style.display = 'none';
  document.getElementById('assessment-9q-container').style.display = 'block';
}

const btnStart2Q = document.querySelector('#btn-start-2q .btn-start-test');
const btnStart9Q = document.querySelector('#btn-start-9q .btn-start-test');
if (btnStart2Q) btnStart2Q.addEventListener('click', showAssessment2Q);
if (btnStart9Q) btnStart9Q.addEventListener('click', showAssessment9Q);

const form2Q = document.getElementById('assessment-form-2q');
const result2Q = document.getElementById('assessment-result-2q');
const text2Q = document.getElementById('result-text-2q');
const action2Q = document.getElementById('result-action-2q');

if (form2Q) {
  form2Q.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(form2Q);
    let totalScore = 0;
    let allAnswered = true;

    for (let i = 0; i < questions2Q.length; i++) {
      const val = formData.get(`q2q${i}`);
      if (val === null) {
        allAnswered = false;
        break;
      }
      totalScore += parseInt(val, 10);
    }

    if (!allAnswered) {
      alert("กรุณาตอบคำถามให้ครบทุกข้อค่ะ");
      return;
    }

    form2Q.style.display = 'none';
    result2Q.style.display = 'block';

    if (totalScore >= 1) {
      text2Q.innerHTML = "พบความเสี่ยงหรือแนวโน้มที่จะเป็นโรคซึมเศร้า 🥺<br><br>แนะนำให้ทำแบบประเมิน 9Q ต่อเพื่อประเมินความรุนแรงของอาการค่ะ";
      action2Q.innerHTML = `<button class="btn-submit" onclick="showAssessment9Q()" style="margin-top: 15px;">ทำแบบประเมิน 9Q ต่อเลย</button>`;
    } else {
      text2Q.innerHTML = "ไม่พบความเสี่ยงของโรคซึมเศร้าค่ะ 💚<br>คุณดูแลจิตใจตัวเองได้ดีมากเลย ขอให้มีความสุขในทุกๆ วันนะคะ";
      action2Q.innerHTML = '';
    }
  });
}

function resetAssessment(type) {
  if (type === '2q') {
    form2Q.reset();
    form2Q.style.display = 'block';
    result2Q.style.display = 'none';
  }
}
// Note: showAssessmentMenu() and resetAssessment() are called from HTML onclick attributes.

// ──────────────── Tools Hub UI Switching ────────────────
function showToolsMenu() {
  document.getElementById('tools-menu').style.display = 'block';
  document.getElementById('tool-mood').style.display = 'none';
  document.getElementById('tool-breathing').style.display = 'none';
  document.getElementById('tool-sounds').style.display = 'none';
  document.getElementById('tool-diary').style.display = 'none';
}

function showTool(toolName) {
  document.getElementById('tools-menu').style.display = 'none';
  document.getElementById('tool-mood').style.display = 'none';
  document.getElementById('tool-breathing').style.display = 'none';
  document.getElementById('tool-sounds').style.display = 'none';
  document.getElementById('tool-diary').style.display = 'none';
  
  const selectedTool = document.getElementById(`tool-${toolName}`);
  if (selectedTool) {
    selectedTool.style.display = 'block';
  }
}

/* ============================================
   PREMIUM FEATURES: Voice, Charts, Polish
   ============================================ */

// ──────────────── Text-to-Speech (TTS) ────────────────
function speakText(text, btn) {
  // Strip HTML tags for clean speech
  const cleanText = text.replace(/<[^>]*>/g, '').replace(/\n/g, ' ');
  
  // If already speaking, stop
  if (window.speechSynthesis.speaking) {
    window.speechSynthesis.cancel();
    if (btn) btn.classList.remove('speaking');
    return;
  }
  
  const utterance = new SpeechSynthesisUtterance(cleanText);
  utterance.lang = 'th-TH';
  utterance.rate = 0.9;
  utterance.pitch = 1.1;
  
  // Try to find a Thai voice
  const voices = window.speechSynthesis.getVoices();
  const thaiVoice = voices.find(v => v.lang.startsWith('th'));
  if (thaiVoice) utterance.voice = thaiVoice;
  
  if (btn) {
    btn.classList.add('speaking');
    btn.innerHTML = '🔊 กำลังพูด...';
  }
  
  utterance.onend = () => {
    if (btn) {
      btn.classList.remove('speaking');
      btn.innerHTML = '🔊 ฟังเสียง';
    }
  };
  utterance.onerror = () => {
    if (btn) {
      btn.classList.remove('speaking');
      btn.innerHTML = '🔊 ฟังเสียง';
    }
  };
  
  window.speechSynthesis.speak(utterance);
}

// Pre-load voices (some browsers need this)
if (window.speechSynthesis) {
  window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
}

// ──────────────── Speech-to-Text (STT) ────────────────
const micBtn = document.getElementById('mic-btn');
let recognition = null;
let isRecording = false;

if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  recognition = new SpeechRecognition();
  recognition.lang = 'th-TH';
  recognition.interimResults = true;
  recognition.continuous = false;
  
  recognition.onresult = (event) => {
    let transcript = '';
    for (let i = event.resultIndex; i < event.results.length; i++) {
      transcript += event.results[i][0].transcript;
    }
    inputField.value = transcript;
    sendButton.disabled = !transcript.trim();
  };
  
  recognition.onend = () => {
    isRecording = false;
    micBtn.classList.remove('recording');
    micBtn.innerHTML = '🎙️';
    // If there's text, auto-send
    if (inputField.value.trim()) {
      handleSend();
    }
  };
  
  recognition.onerror = (event) => {
    isRecording = false;
    micBtn.classList.remove('recording');
    micBtn.innerHTML = '🎙️';
    if (event.error !== 'no-speech') {
      console.error('Speech recognition error:', event.error);
    }
  };
}

if (micBtn) {
  micBtn.addEventListener('click', () => {
    if (!recognition) {
      alert('ขออภัยค่ะ เบราว์เซอร์ของคุณไม่รองรับการพูดด้วยเสียง ลองใช้ Chrome หรือ Edge นะคะ');
      return;
    }
    
    if (isRecording) {
      recognition.stop();
    } else {
      isRecording = true;
      micBtn.classList.add('recording');
      micBtn.innerHTML = '⏹️';
      inputField.value = '';
      recognition.start();
    }
  });
}

// ──────────────── Mood Chart (Chart.js) ────────────────
let moodChart = null;
const moodValueMap = { happy: 5, calm: 4, neutral: 3, sad: 2, angry: 1 };
const moodLabelMap = { 5: 'มีความสุข', 4: 'สงบ', 3: 'เฉยๆ', 2: 'เศร้า', 1: 'เครียด' };

function renderMoodChart() {
  const canvas = document.getElementById('mood-chart');
  if (!canvas || typeof Chart === 'undefined') return;
  
  const history = JSON.parse(localStorage.getItem(MOOD_STORAGE_KEY) || '[]');
  
  // Take last 10 entries (reversed to chronological)
  const recent = history.slice(0, 10).reverse();
  
  const labels = recent.map(item => {
    const d = new Date(item.timestamp);
    return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
  });
  const data = recent.map(item => moodValueMap[item.mood] || 3);
  
  if (moodChart) {
    moodChart.destroy();
  }
  
  const ctx = canvas.getContext('2d');
  
  // Create gradient fill
  const gradient = ctx.createLinearGradient(0, 0, 0, 250);
  gradient.addColorStop(0, 'rgba(183, 148, 246, 0.4)');
  gradient.addColorStop(1, 'rgba(183, 148, 246, 0.02)');
  
  moodChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'ระดับอารมณ์',
        data: data,
        borderColor: '#b794f6',
        backgroundColor: gradient,
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#b794f6',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(26, 26, 46, 0.9)',
          titleColor: '#b794f6',
          bodyColor: '#e2e8f0',
          borderColor: 'rgba(183, 148, 246, 0.3)',
          borderWidth: 1,
          cornerRadius: 8,
          callbacks: {
            label: (ctx) => moodLabelMap[ctx.raw] || ctx.raw
          }
        }
      },
      scales: {
        y: {
          min: 0.5,
          max: 5.5,
          ticks: {
            stepSize: 1,
            color: 'rgba(255,255,255,0.4)',
            callback: (val) => moodLabelMap[val] || '',
            font: { family: 'Noto Sans Thai' }
          },
          grid: { color: 'rgba(255,255,255,0.05)' }
        },
        x: {
          ticks: {
            color: 'rgba(255,255,255,0.4)',
            maxRotation: 45,
            font: { size: 10, family: 'Noto Sans Thai' }
          },
          grid: { display: false }
        }
      }
    }
  });
}

// Render chart on load and after mood save
renderMoodChart();

// Hook into mood button clicks to also update chart
const originalMoodBtnHandlers = document.querySelectorAll('.mood-btn');
originalMoodBtnHandlers.forEach(btn => {
  btn.addEventListener('click', () => {
    setTimeout(renderMoodChart, 200); // After localStorage is updated
  });
});

// Also update chart when clearing history
if (clearMoodBtn) {
  clearMoodBtn.addEventListener('click', () => {
    setTimeout(renderMoodChart, 200);
  });
}

// ──────────────── Cursor Glow Effect ────────────────
const cursorGlow = document.createElement('div');
cursorGlow.className = 'cursor-glow';
document.body.appendChild(cursorGlow);

document.addEventListener('mousemove', (e) => {
  cursorGlow.style.left = e.clientX + 'px';
  cursorGlow.style.top = e.clientY + 'px';
});

// ──────────────── UI Click Sound Effect ────────────────
function playClickSound() {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(800, audioCtx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(600, audioCtx.currentTime + 0.05);
    
    gainNode.gain.setValueAtTime(0.08, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
    
    oscillator.start(audioCtx.currentTime);
    oscillator.stop(audioCtx.currentTime + 0.1);
  } catch (e) {
    // Silently fail if AudioContext is not available
  }
}

// Attach click sound to tab buttons and main action buttons
document.querySelectorAll('.tab-btn, .btn-start-test, .btn-submit, .input-area__send, .input-area__mic, .mood-btn').forEach(btn => {
  btn.addEventListener('click', playClickSound);
});
