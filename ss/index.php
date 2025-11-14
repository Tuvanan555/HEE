<?php
// ตั้งค่าภาษาและ encoding
header('Content-Type: text/html; charset=utf-8');

// เริ่ม session สำหรับเก็บข้อมูลผู้ใช้
session_start();

// URL ของ Google Apps Script Web App
$SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbxPxLskjRT2OLp-fFuhUvBNbbcTwhpB3d_K1yZcEAwwuEk9MKQwE3xD5J5hhFZiq8T_/exec';

// ฟังก์ชันบันทึกคะแนนไปยัง Google Sheets
function saveScoreToGoogleSheets($data) {
    global $SCRIPT_URL;
    
    // ตรวจสอบข้อมูลก่อนส่ง
    if (empty($data['student_name']) || !isset($data['score'])) {
        return false;
    }
    
    $postData = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $SCRIPT_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['success'] ?? false;
    }
    
    error_log("Google Sheets API Error: HTTP $httpCode - $response - Curl Error: $curlError");
    return false;
}

// ฟังก์ชันอ่านคะแนนจาก Google Sheets
function getScoresFromGoogleSheets() {
    global $SCRIPT_URL;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $SCRIPT_URL . '?action=get_scores');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200 && !empty($response)) {
        $scores = json_decode($response, true);
        
        // ตรวจสอบว่าเป็น array หรือไม่
        if (is_array($scores)) {
            return $scores;
        }
    }
    
    error_log("Google Sheets API Error: HTTP $httpCode - $response - Curl Error: $curlError");
    return [];
}

// ตรวจสอบการส่งฟอร์มแบบทดสอบ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_score') {
            $scoreData = [
                'student_name' => $_POST['student_name'] ?? '',
                'score' => intval($_POST['score'] ?? 0),
                'total_questions' => intval($_POST['total_questions'] ?? 0),
                'date' => $_POST['date'] ?? date('c'),
                'time_taken' => $_POST['time_taken'] ?? '',
                'grade' => $_POST['grade'] ?? '',
                'chapter' => intval($_POST['chapter'] ?? 0)
            ];
            
            if (saveScoreToGoogleSheets($scoreData)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'ไม่สามารถบันทึกข้อมูลได้']);
            }
            exit;
        }
    }
}

// ตรวจสอบการขอข้อมูลคะแนน
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_scores') {
        header('Content-Type: application/json');
        $scores = getScoresFromGoogleSheets();
        // ตรวจสอบให้แน่ใจว่าส่งกลับเป็น array
        echo json_encode(is_array($scores) ? $scores : []);
        exit;
    }
}

// ตั้งค่าตัวแปรเริ่มต้น
$allScores = getScoresFromGoogleSheets();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คณิตศาสตร์สนุก - สำหรับเด็ก</title>
    <script src="/_sdk/element_sdk.js"></script>
    <script src="/_sdk/data_sdk.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1E88E5;
            --primary-light: #64B5F6;
            --primary-dark: #1565C0;
            --secondary: #42A5F5;
            --secondary-light: #90CAF9;
            --accent: #FF9800;
            --accent-light: #FFB74D;
            --light: #FFFFFF;
            --light-bg: #F5F9FF;
            --light-border: #E3F2FD;
            --dark: #37474F;
            --dark-light: #546E7A;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
            --gradient: linear-gradient(135deg, #1E88E5 0%, #42A5F5 100%);
            --gradient-light: linear-gradient(135deg, #E3F2FD 0%, #F5F9FF 100%);
            --gradient-card: linear-gradient(135deg, #FFFFFF 0%, #F5F9FF 100%);
            --shadow: 0 5px 15px rgba(30, 136, 229, 0.1);
            --shadow-hover: 0 10px 25px rgba(30, 136, 229, 0.15);
            --shadow-card: 0 8px 20px rgba(30, 136, 229, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-light);
            min-height: 100%;
            color: var(--dark);
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(30, 136, 229, 0.03) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(66, 165, 245, 0.03) 0%, transparent 20%);
            pointer-events: none;
            z-index: -1;
        }

        html, body {
            height: 100%;
        }

        .navbar {
            background: var(--light);
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--light-border);
        }

        .navbar.scrolled {
            padding: 0.8rem 2rem;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            color: var(--primary-dark);
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 10px;
            background: var(--primary);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .hamburger:hover {
            background: var(--primary-dark);
        }

        .hamburger span {
            width: 30px;
            height: 3px;
            background: white;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -8px);
        }

        .nav-menu {
            display: flex;
            gap: 1rem;
            list-style: none;
        }

        .nav-menu li a {
            text-decoration: none;
            color: var(--dark);
            font-size: 1.1rem;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-menu li a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            transition: all 0.4s ease;
            z-index: -1;
        }

        .nav-menu li a:hover::before,
        .nav-menu li a.active::before {
            left: 0;
        }

        .nav-menu li a:hover,
        .nav-menu li a.active {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .page.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .home-section {
            text-align: center;
            padding: 3rem 1rem;
        }

        .welcome-box {
            background: var(--gradient-card);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow-card);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--light-border);
        }

        .welcome-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        .welcome-box h1 {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .welcome-box p {
            font-size: 1.5rem;
            color: var(--dark-light);
            position: relative;
            z-index: 1;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-card {
            background: var(--gradient-card);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-card);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid var(--light-border);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .feature-card .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            color: var(--dark-light);
            font-size: 1rem;
        }

        .lessons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .lesson-card {
            background: var(--gradient-card);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-card);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--light-border);
            position: relative;
            overflow: hidden;
        }

        .lesson-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        .lesson-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .lesson-card .lesson-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        .lesson-card h3 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .lesson-card p {
            color: var(--dark-light);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            flex-grow: 1;
        }

        .lesson-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .lesson-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .lesson-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .lesson-btn:hover::before {
            left: 100%;
        }

        .lesson-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .quiz-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .quiz-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .quiz-btn:hover::before {
            left: 100%;
        }

        .quiz-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .quiz-container {
            background: var(--gradient-card);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow-card);
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid var(--light-border);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .quiz-header h2 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .name-input-box {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid var(--light-border);
        }

        .name-input-box h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .name-input-box input {
            width: 100%;
            max-width: 300px;
            padding: 1rem;
            border: 2px solid var(--primary-light);
            border-radius: 10px;
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: var(--light);
        }

        .name-input-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.2);
        }

        .question-box {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--light-border);
        }

        .question-box h3 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 2rem;
            text-align: center;
        }

        .answers {
            display: grid;
            gap: 1rem;
        }

        .answer-btn {
            background: var(--light);
            border: 2px solid var(--primary-light);
            padding: 1.2rem;
            border-radius: 15px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--dark);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .answer-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--primary);
            transition: all 0.3s ease;
            z-index: -1;
        }

        .answer-btn:hover::before {
            width: 100%;
        }

        .answer-btn:hover {
            color: white;
            transform: scale(1.02);
            border-color: var(--primary);
        }

        .answer-btn.correct {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }

        .answer-btn.wrong {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        .quiz-score {
            text-align: center;
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: bold;
            margin-top: 1rem;
        }

        .next-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 25px;
            font-size: 1.2rem;
            cursor: pointer;
            display: block;
            margin: 2rem auto 0;
            transition: all 0.3s ease;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .next-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .next-btn:hover::before {
            left: 100%;
        }

        .next-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .next-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .history-container {
            background: var(--gradient-card);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--light-border);
        }

        .history-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .history-header h2 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .history-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--light-border);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            animation: float 3s ease-in-out infinite;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }

        .stat-card .stat-label {
            color: var(--dark-light);
            font-size: 1rem;
        }

        .history-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .history-item {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid var(--light-border);
        }

        .history-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .history-item .rank {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            min-width: 50px;
            text-align: center;
        }

        .history-item .info {
            flex: 1;
        }

        .history-item .info .name {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 0.3rem;
        }

        .history-item .info .details {
            color: var(--dark-light);
            font-size: 0.95rem;
        }

        .history-item .score-badge {
            background: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-size: 1.3rem;
            font-weight: bold;
            min-width: 100px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .history-item .score-badge:hover {
            transform: scale(1.05);
        }

        .history-item .score-badge.excellent {
            background: var(--success);
        }

        .history-item .score-badge.good {
            background: var(--secondary);
        }

        .history-item .score-badge.average {
            background: var(--warning);
        }

        .history-item .score-badge.poor {
            background: var(--danger);
        }

        .empty-history {
            text-align: center;
            padding: 3rem;
            color: var(--dark-light);
        }

        .empty-history .icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        .empty-history h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .calculator-popup {
            display: none;
            position: fixed;
            background: var(--gradient-card);
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            padding: 1.5rem;
            z-index: 1000;
            min-width: 420px;
            border: 1px solid var(--light-border);
        }

        .calculator-popup.active {
            display: block;
            animation: popIn 0.3s ease;
        }

        @keyframes popIn {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .calculator-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            cursor: move;
            padding: 0.5rem;
            background: var(--light-bg);
            border-radius: 10px;
            border: 1px solid var(--light-border);
        }

        .calculator-header h3 {
            color: var(--primary);
            font-size: 1.3rem;
        }

        .calc-mode-toggle {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .mode-btn {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--light-border);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .mode-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .close-calc {
            background: var(--danger);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-calc:hover {
            background: #d32f2f;
            transform: rotate(90deg);
        }

        .calc-display {
            background: var(--light-bg);
            border: 2px solid var(--primary-light);
            border-radius: 10px;
            padding: 1rem 1.5rem;
            text-align: right;
            margin-bottom: 1rem;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .calc-expression {
            font-size: 1rem;
            color: var(--dark-light);
            min-height: 24px;
            word-break: break-all;
        }

        .calc-result {
            font-size: 2rem;
            color: var(--dark);
            font-weight: bold;
        }

        .calc-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.6rem;
        }

        .calc-buttons.scientific {
            grid-template-columns: repeat(5, 1fr);
        }

        .calc-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            position: relative;
            overflow: hidden;
        }

        .calc-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .calc-btn:hover::before {
            left: 100%;
        }

        .calc-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .calc-btn.operator {
            background: var(--secondary);
        }

        .calc-btn.operator:hover {
            background: var(--primary-dark);
        }

        .calc-btn.function {
            background: var(--accent);
            font-size: 0.9rem;
            padding: 0.8rem 0.5rem;
        }

        .calc-btn.function:hover {
            background: var(--accent-light);
        }

        .calc-btn.clear {
            background: var(--danger);
        }

        .calc-btn.clear:hover {
            background: #d32f2f;
        }

        .calc-btn.equals {
            background: var(--success);
        }

        .calc-btn.equals:hover {
            background: #45a049;
        }

        .calc-btn.span-2 {
            grid-column: span 2;
        }

        .calc-float-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary);
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.8rem;
            cursor: pointer;
            box-shadow: var(--shadow-hover);
            transition: all 0.3s ease;
            z-index: 999;
            animation: float 3s ease-in-out infinite;
        }

        .calc-float-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }

            .nav-menu {
                position: fixed;
                left: -100%;
                top: 70px;
                flex-direction: column;
                background: var(--light);
                width: 100%;
                text-align: center;
                transition: 0.3s;
                box-shadow: 0 10px 27px rgba(0,0,0,0.05);
                padding: 1rem 0;
                border-top: 1px solid var(--light-border);
            }

            .nav-menu.active {
                left: 0;
            }

            .nav-menu li {
                margin: 0.5rem 0;
            }

            .welcome-box h1 {
                font-size: 2rem;
            }

            .welcome-box p {
                font-size: 1.2rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .calculator-popup {
                min-width: 320px;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
            }

            .calc-float-btn {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .history-item {
                grid-template-columns: auto 1fr;
                gap: 1rem;
            }

            .history-item .score-badge {
                grid-column: 1 / -1;
                margin-top: 0.5rem;
            }
        }

        .page-title {
            color: var(--primary);
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(30, 136, 229, 0.1);
        }

        .grade-selector {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .grade-btn {
            background: var(--light);
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 1rem 2.5rem;
            border-radius: 25px;
            font-size: 1.3rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .grade-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .grade-btn:hover::before {
            left: 100%;
        }

        .grade-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.3);
        }

        .grade-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.3);
        }

        .grade-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .grade-content.active {
            display: block;
        }

        .grade-title {
            color: var(--primary);
            font-size: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(30, 136, 229, 0.1);
        }

        .lesson-detail-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 1rem;
        }

        .lesson-detail-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .lesson-detail-content {
            background: var(--gradient-card);
            border-radius: 20px;
            padding: 2rem;
            max-width: 800px;
            max-height: 90%;
            overflow-y: auto;
            box-shadow: var(--shadow-hover);
            border: 1px solid var(--light-border);
            animation: popIn 0.3s ease;
        }

        .lesson-detail-content h2 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .lesson-content-section {
            margin-bottom: 2rem;
        }

        .lesson-content-section h3 {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .lesson-content-section p {
            color: var(--dark);
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .lesson-visual {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 2rem;
            margin: 1.5rem 0;
            text-align: center;
            border: 1px solid var(--light-border);
        }

        .lesson-example {
            background: rgba(255, 243, 224, 0.5);
            border-left: 4px solid var(--accent);
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 8px;
        }

        .lesson-example h4 {
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .close-modal-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-size: 1.1rem;
            cursor: pointer;
            display: block;
            margin: 2rem auto 0;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .close-modal-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .close-modal-btn:hover::before {
            left: 100%;
        }

        .close-modal-btn:hover {
            background: var(--primary-dark);
        }

        .custom-message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .custom-message-box {
            background: var(--gradient-card);
            padding: 2rem 3rem;
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            text-align: center;
            max-width: 450px;
            border: 1px solid var(--light-border);
            animation: popIn 0.3s ease;
        }

        .custom-message-box h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .custom-message-box p {
            color: var(--dark);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .custom-message-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .custom-message-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .custom-message-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s ease;
        }

        .custom-message-btn:hover::before {
            left: 100%;
        }

        .custom-message-btn:hover {
            background: var(--primary-dark);
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(30, 136, 229, 0.1);
            border-radius: 50%;
            animation: float-particle 15s infinite linear;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
    </style>
    <style>@view-transition { navigation: auto; }</style>
    <script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
</head>
<body>
    <div class="particles" id="particles"></div>

    <nav class="navbar" id="navbar">
        <div class="logo"><span>🎓</span> <span id="site-title">คณิตศาสตร์สนุก</span></div>
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-menu" id="navMenu">
            <li><a href="#" class="nav-link active" data-page="home"><span>🏠</span> <span id="menu-home">หน้าแรก</span></a></li>
            <li><a href="#" class="nav-link" data-page="lessons"><span>🧮</span> <span id="menu-lessons">บทเรียน</span></a></li>
            <li><a href="#" class="nav-link" data-page="quiz"><span>📘</span> <span id="menu-quiz">แบบทดสอบ</span></a></li>
            <li><a href="#" class="nav-link" data-page="history"><span>📊</span> <span id="menu-history">ประวัติคะแนน</span></a></li>
            <li><a href="#" class="nav-link" data-page="calculator"><span>🔢</span> <span id="menu-calculator">เครื่องคิดเลข</span></a></li>
        </ul>
    </nav>

    <div class="container">
        <div id="home" class="page active">
            <div class="home-section">
                <div class="welcome-box">
                    <h1 id="welcome-title">🎉 ยินดีต้อนรับสู่โลกแห่งคณิตศาสตร์! 🎉</h1>
                    <p id="welcome-subtitle">เรียนรู้คณิตศาสตร์อย่างสนุกสนานและเข้าใจง่าย</p>
                </div>
                <div class="features">
                    <div class="feature-card" onclick="navigateTo('lessons')">
                        <div class="icon">🧮</div>
                        <h3>บทเรียน</h3>
                        <p>เรียนรู้การบวก ลบ คูณ หาร</p>
                    </div>
                    <div class="feature-card" onclick="navigateTo('quiz')">
                        <div class="icon">📘</div>
                        <h3>แบบทดสอบ</h3>
                        <p>ทดสอบความรู้ของคุณ</p>
                    </div>
                    <div class="feature-card" onclick="navigateTo('history')">
                        <div class="icon">📊</div>
                        <h3>ประวัติคะแนน</h3>
                        <p>ดูผลคะแนนที่ผ่านมา</p>
                    </div>
                    <div class="feature-card" onclick="openCalculator()">
                        <div class="icon">🔢</div>
                        <h3>เครื่องคิดเลข</h3>
                        <p>คำนวณตัวเลขได้ง่ายๆ</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="lessons" class="page">
            <h2 class="page-title">📚 บทเรียนคณิตศาสตร์</h2>
            <div class="grade-selector">
                <button class="grade-btn active" onclick="selectGrade('grade4')">ป.4</button>
                <button class="grade-btn" onclick="selectGrade('grade5')">ป.5</button>
                <button class="grade-btn" onclick="selectGrade('grade6')">ป.6</button>
            </div>
            <div id="grade4" class="grade-content active">
                <h3 class="grade-title">📖 คณิตศาสตร์ ป.4 (13 บท)</h3>
                <div class="lessons-grid" id="grade4Lessons"></div>
            </div>
            <div id="grade5" class="grade-content">
                <h3 class="grade-title">📖 คณิตศาสตร์ ป.5 (13 บท)</h3>
                <div class="lessons-grid" id="grade5Lessons"></div>
            </div>
            <div id="grade6" class="grade-content">
                <h3 class="grade-title">📖 คณิตศาสตร์ ป.6 (13 บท)</h3>
                <div class="lessons-grid" id="grade6Lessons"></div>
            </div>
        </div>

        <div id="quiz" class="page">
            <div class="quiz-container">
                <div class="quiz-header">
                    <h2>🎯 แบบทดสอบคณิตศาสตร์</h2>
                    <div class="quiz-score">คะแนน: <span id="score">0</span></div>
                </div>
                <div id="nameInputSection" class="name-input-box">
                    <h3>👤 กรุณากรอกชื่อของคุณ</h3>
                    <input type="text" id="studentName" placeholder="ชื่อของคุณ" maxlength="50">
                    <button class="lesson-btn" onclick="startQuizWithName()">เริ่มทำแบบทดสอบ</button>
                </div>
                <div id="quizSection" style="display: none;">
                    <div class="question-box">
                        <h3 id="question">กำลังโหลดคำถาม...</h3>
                        <div class="answers" id="answers"></div>
                    </div>
                    <button class="next-btn" id="nextBtn" onclick="nextQuestion()">คำถามถัดไป</button>
                </div>
            </div>
        </div>

        <div id="history" class="page">
            <div class="history-container">
                <div class="history-header">
                    <h2>📊 ประวัติคะแนนแบบทดสอบ</h2>
                    <p style="color: var(--dark-light); margin-top: 0.5rem;">ติดตามความก้าวหน้าของคุณ</p>
                </div>
                <div class="history-stats" id="historyStats">
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-value" id="totalTests">0</div>
                        <div class="stat-label">ครั้งที่ทำแบบทดสอบ</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-value" id="avgScore">0</div>
                        <div class="stat-label">คะแนนเฉลี่ย</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-value" id="bestScore">0</div>
                        <div class="stat-label">คะแนนสูงสุด</div>
                    </div>
                </div>
                <div class="history-list" id="historyList">
                    <div class="empty-history">
                        <div class="icon">📋</div>
                        <h3>ยังไม่มีประวัติคะแนน</h3>
                        <p>เริ่มทำแบบทดสอบเพื่อบันทึกคะแนนของคุณ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="lesson-detail-modal" id="lessonModal">
        <div class="lesson-detail-content" id="lessonModalContent"></div>
    </div>

    <div class="calculator-popup" id="calculatorPopup">
        <div class="calculator-header" id="calcHeader">
            <h3>🔢 เครื่องคิดเลข</h3>
            <div class="calc-mode-toggle">
                <button class="mode-btn active" onclick="switchCalcMode('basic')">พื้นฐาน</button>
                <button class="mode-btn" onclick="switchCalcMode('scientific')">วิทยาศาสตร์</button>
                <button class="close-calc" onclick="closeCalculator()">×</button>
            </div>
        </div>
        <div class="calc-display">
            <div class="calc-expression" id="calcExpression"></div>
            <div class="calc-result" id="calcResult">0</div>
        </div>
        <div class="calc-buttons" id="calcButtons"></div>
    </div>

    <button class="calc-float-btn" onclick="openCalculator()">🔢</button>

    <script>
        // เพิ่มฟังก์ชัน JavaScript สำหรับการทำงานใหม่ๆ
        document.addEventListener('DOMContentLoaded', function() {
            // สร้าง particle effects
            createParticles();
            
            // เพิ่ม scroll effect สำหรับ navbar
            window.addEventListener('scroll', function() {
                const navbar = document.getElementById('navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
            
            // เริ่มต้นโหลดข้อมูลบทเรียน
            loadLessons();
        });

        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // สุ่มขนาดและตำแหน่ง
                const size = Math.random() * 10 + 5;
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 20 + 10;
                const animationDelay = Math.random() * 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}%`;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `${animationDelay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // ฟังก์ชันอื่นๆ ที่มีอยู่แล้ว (navigateTo, selectGrade, openCalculator, closeCalculator, switchCalcMode, startQuizWithName, nextQuestion, loadLessons)
        // สามารถเพิ่มการทำงานเพิ่มเติมได้ตามต้องการ
    </script>



    <script>
        let allScores = [];
        let currentCalcMode = 'basic';
        let calcExpression = '';
        let calcResult = '0';

        const defaultConfig = {
            site_title: "คณิตศาสตร์สนุก",
            welcome_title: "🎉 ยินดีต้อนรับสู่โลกแห่งคณิตศาสตร์! 🎉",
            welcome_subtitle: "เรียนรู้คณิตศาสตร์อย่างสนุกสนานและเข้าใจง่าย",
            menu_lessons: "บทเรียน",
            menu_quiz: "แบบทดสอบ",
            menu_calculator: "เครื่องคิดเลข",
            menu_home: "หน้าแรก",
            menu_history: "ประวัติคะแนน",
            background_color: "#667eea",
            surface_color: "#ffffff",
            text_color: "#333333",
            primary_action_color: "#667eea",
            secondary_action_color: "#764ba2",
            font_family: "Segoe UI",
            font_size: 16
        };

        async function onConfigChange(config) {
            const baseSize = config.font_size || defaultConfig.font_size;
            const customFont = config.font_family || defaultConfig.font_family;
            const baseFontStack = 'Tahoma, Geneva, Verdana, sans-serif';
            const fontFamily = `${customFont}, ${baseFontStack}`;

            document.body.style.fontFamily = fontFamily;

            document.querySelector('.logo').style.fontSize = `${baseSize * 1.125}px`;
            document.querySelectorAll('.nav-menu li a').forEach(el => el.style.fontSize = `${baseSize * 0.6875}px`);
            document.querySelector('.welcome-box h1').style.fontSize = `${baseSize * 1.875}px`;
            document.querySelector('.welcome-box p').style.fontSize = `${baseSize * 0.9375}px`;
            document.querySelectorAll('.feature-card h3').forEach(el => el.style.fontSize = `${baseSize * 0.9375}px`);
            document.querySelectorAll('.feature-card p').forEach(el => el.style.fontSize = `${baseSize * 0.625}px`);
            document.querySelectorAll('.page-title').forEach(el => el.style.fontSize = `${baseSize * 1.5625}px`);

            const bgColor = config.background_color || defaultConfig.background_color;
            const surfaceColor = config.surface_color || defaultConfig.surface_color;
            const textColor = config.text_color || defaultConfig.text_color;
            const primaryColor = config.primary_action_color || defaultConfig.primary_action_color;
            const secondaryColor = config.secondary_action_color || defaultConfig.secondary_action_color;

            document.body.style.background = `linear-gradient(135deg, ${bgColor} 0%, ${secondaryColor} 100%)`;
            document.querySelector('.navbar').style.background = surfaceColor;
            document.querySelectorAll('.welcome-box, .feature-card, .lesson-card, .quiz-container, .calculator-popup, .history-container, .lesson-detail-content').forEach(el => {
                el.style.background = surfaceColor;
            });
            document.body.style.color = textColor;
            document.querySelectorAll('.logo, .feature-card h3, .lesson-card h3, .quiz-header h2, .quiz-score, .calculator-header h3, .history-header h2, .lesson-detail-content h2').forEach(el => {
                el.style.color = primaryColor;
            });
            document.querySelectorAll('.lesson-btn, .next-btn, .calc-btn, .calc-float-btn, .hamburger, .close-modal-btn').forEach(el => {
                el.style.background = primaryColor;
            });
            document.querySelectorAll('.calc-btn.operator, .quiz-btn').forEach(el => {
                el.style.background = secondaryColor;
            });

            document.getElementById('site-title').textContent = config.site_title || defaultConfig.site_title;
            document.getElementById('welcome-title').textContent = config.welcome_title || defaultConfig.welcome_title;
            document.getElementById('welcome-subtitle').textContent = config.welcome_subtitle || defaultConfig.welcome_subtitle;
            document.getElementById('menu-lessons').textContent = config.menu_lessons || defaultConfig.menu_lessons;
            document.getElementById('menu-quiz').textContent = config.menu_quiz || defaultConfig.menu_quiz;
            document.getElementById('menu-calculator').textContent = config.menu_calculator || defaultConfig.menu_calculator;
            document.getElementById('menu-home').textContent = config.menu_home || defaultConfig.menu_home;
            document.getElementById('menu-history').textContent = config.menu_history || defaultConfig.menu_history;
        }

        const dataHandler = {
            onDataChanged(data) {
                allScores = data;
                updateHistoryDisplay();
            }
        };

        async function initDataSdk() {
            if (window.dataSdk) {
                const result = await window.dataSdk.init(dataHandler);
                if (!result.isOk) {
                    console.error("Failed to initialize Data SDK");
                }
            }
        }

        if (window.elementSdk) {
            window.elementSdk.init({
                defaultConfig: defaultConfig,
                onConfigChange: onConfigChange,
                mapToCapabilities: (config) => ({
                    recolorables: [
                        {
                            get: () => config.background_color || defaultConfig.background_color,
                            set: (value) => {
                                config.background_color = value;
                                window.elementSdk.setConfig({ background_color: value });
                            }
                        },
                        {
                            get: () => config.surface_color || defaultConfig.surface_color,
                            set: (value) => {
                                config.surface_color = value;
                                window.elementSdk.setConfig({ surface_color: value });
                            }
                        },
                        {
                            get: () => config.text_color || defaultConfig.text_color,
                            set: (value) => {
                                config.text_color = value;
                                window.elementSdk.setConfig({ text_color: value });
                            }
                        },
                        {
                            get: () => config.primary_action_color || defaultConfig.primary_action_color,
                            set: (value) => {
                                config.primary_action_color = value;
                                window.elementSdk.setConfig({ primary_action_color: value });
                            }
                        },
                        {
                            get: () => config.secondary_action_color || defaultConfig.secondary_action_color,
                            set: (value) => {
                                config.secondary_action_color = value;
                                window.elementSdk.setConfig({ secondary_action_color: value });
                            }
                        }
                    ],
                    borderables: [],
                    fontEditable: {
                        get: () => config.font_family || defaultConfig.font_family,
                        set: (value) => {
                            config.font_family = value;
                            window.elementSdk.setConfig({ font_family: value });
                        }
                    },
                    fontSizeable: {
                        get: () => config.font_size || defaultConfig.font_size,
                        set: (value) => {
                            config.font_size = value;
                            window.elementSdk.setConfig({ font_size: value });
                        }
                    }
                }),
                mapToEditPanelValues: (config) => new Map([
                    ["site_title", config.site_title || defaultConfig.site_title],
                    ["welcome_title", config.welcome_title || defaultConfig.welcome_title],
                    ["welcome_subtitle", config.welcome_subtitle || defaultConfig.welcome_subtitle],
                    ["menu_lessons", config.menu_lessons || defaultConfig.menu_lessons],
                    ["menu_quiz", config.menu_quiz || defaultConfig.menu_quiz],
                    ["menu_calculator", config.menu_calculator || defaultConfig.menu_calculator],
                    ["menu_home", config.menu_home || defaultConfig.menu_home],
                    ["menu_history", config.menu_history || defaultConfig.menu_history]
                ])
            });
        }

        initDataSdk();

        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');

        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        const navLinks = document.querySelectorAll('.nav-link');
        const pages = document.querySelectorAll('.page');

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetPage = link.getAttribute('data-page');
                
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
                
                pages.forEach(page => page.classList.remove('active'));
                document.getElementById(targetPage).classList.add('active');
                
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                if (targetPage === 'quiz') {
                    resetQuiz();
                }

                if (targetPage === 'history') {
                    updateHistoryDisplay();
                }
            });
        });

        function navigateTo(pageName) {
            const targetLink = document.querySelector(`[data-page="${pageName}"]`);
            if (targetLink) {
                targetLink.click();
            }
        }

        function selectGrade(grade) {
            document.querySelectorAll('.grade-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            document.querySelectorAll('.grade-content').forEach(content => content.classList.remove('active'));
            document.getElementById(grade).classList.add('active');
        }

        const lessonData = {
            grade4: [
                { icon: '🔢', title: 'จำนวนนับที่มากกว่า 100,000', desc: 'เรียนรู้การอ่าน เขียน และเปรียบเทียบจำนวนนับ' },
                { icon: '➕➖', title: 'การบวกและการลบ', desc: 'ฝึกทักษะการบวกและลบจำนวนหลายหลัก' },
                { icon: '📐', title: 'เรขาคณิต', desc: 'ศึกษารูปเรขาคณิตพื้นฐาน มุม และเส้น' },
                { icon: '✖️', title: 'การคูณ', desc: 'เรียนรู้การคูณจำนวนหลายหลักและสูตรคูณ' },
                { icon: '➗', title: 'การหาร', desc: 'ทำความเข้าใจการหารและการตรวจสอบคำตอบ' },
                { icon: '📊', title: 'แผนภูมิรูปภาพ แผนภูมิแท่ง และตาราง', desc: 'เรียนรู้การอ่านและสร้างแผนภูมิ' },
                { icon: '📏', title: 'การวัด', desc: 'ศึกษาหน่วยการวัดความยาว น้ำหนัก และปริมาตร' },
                { icon: '📐', title: 'พื้นที่', desc: 'คำนวณพื้นที่รูปสี่เหลี่ยมและรูปสามเหลี่ยม' },
                { icon: '💰', title: 'เงิน', desc: 'เรียนรู้การคำนวณเงินและการแก้ปัญหา' },
                { icon: '🍕', title: 'เศษส่วน', desc: 'ทำความเข้าใจเศษส่วนและการเปรียบเทียบ' },
                { icon: '⏰', title: 'เวลา', desc: 'เรียนรู้การบอกเวลาและคำนวณเวลา' },
                { icon: '🔢', title: 'ทศนิยม', desc: 'ทำความเข้าใจทศนิยมและการคำนวณ' },
                { icon: '🔄', title: 'การบวก ลบ คูณ หารระคน', desc: 'ฝึกทักษะการคำนวณแบบผสม' }
            ],
            grade5: [
                { icon: '🔢', title: 'จำนวนนับ และการบวก การลบ การคูณ การหาร', desc: 'ทบทวนการคำนวณพื้นฐาน' },
                { icon: '📐', title: 'มุม', desc: 'ศึกษาชนิดของมุมและการวัดมุม' },
                { icon: '📏', title: 'เส้นขนาน', desc: 'เรียนรู้เส้นขนานและเส้นตั้งฉาก' },
                { icon: '📊', title: 'สถิติและความน่าจะเป็นเบื้องต้น', desc: 'ทำความเข้าใจข้อมูลและความน่าจะเป็น' },
                { icon: '🍕', title: 'เศษส่วน', desc: 'เรียนรู้เศษส่วนขั้นสูง' },
                { icon: '➕', title: 'การบวก การลบ การคูณ การหารเศษส่วน', desc: 'คำนวณเศษส่วนแบบต่างๆ' },
                { icon: '🔢', title: 'ทศนิยม', desc: 'ทำความเข้าใจทศนิยมขั้นสูง' },
                { icon: '➕', title: 'การบวก การลบ การคูณทศนิยม', desc: 'คำนวณทศนิยมแบบต่างๆ' },
                { icon: '📝', title: 'บทประยุกต์', desc: 'แก้โจทย์ปัญหาในชีวิตจริง' },
                { icon: '▭', title: 'รูปสี่เหลี่ยม', desc: 'ศึกษาคุณสมบัติของรูปสี่เหลี่ยม' },
                { icon: '△', title: 'รูปสามเหลี่ยม', desc: 'เรียนรู้ชนิดของรูปสามเหลี่ยม' },
                { icon: '⭕', title: 'รูปวงกลม', desc: 'ทำความเข้าใจส่วนประกอบของวงกลม' },
                { icon: '📦', title: 'รูปเรขาคณิตสามมิติและปริมาตรของทรงสี่เหลี่ยมมุมฉาก', desc: 'คำนวณปริมาตร' }
            ],
            grade6: [
                { icon: '🔢', title: 'จำนวนนับ และการบวก การลบ การคูณ การหาร', desc: 'ทบทวนการคำนวณขั้นสูง' },
                { icon: '🔢', title: 'ตัวประกอบของจำนวนนับ', desc: 'หาตัวประกอบ ตัวคูณ และตัวหารร่วม' },
                { icon: '🍕', title: 'เศษส่วน และการบวก การลบ การคูณ การหาร', desc: 'คำนวณเศษส่วนขั้นสูง' },
                { icon: '🔢', title: 'ทศนิยม', desc: 'ทำความเข้าใจทศนิยมขั้นสูง' },
                { icon: '➕', title: 'การบวก การลบ การคูณ และการหารทศนิยม', desc: 'คำนวณทศนิยมแบบต่างๆ' },
                { icon: '📏', title: 'เส้นขนาน', desc: 'ศึกษาเส้นขนานและมุมที่เกิดขึ้น' },
                { icon: '🔤', title: 'สมการและการแก้สมการ', desc: 'เรียนรู้การแก้สมการเบื้องต้น' },
                { icon: '🧭', title: 'ทิศ แผนที่และแผนผัง', desc: 'อ่านแผนที่และหาทิศทาง' },
                { icon: '▭', title: 'รูปสี่เหลี่ยม', desc: 'ศึกษาคุณสมบัติของรูปสี่เหลี่ยมขั้นสูง' },
                { icon: '⭕', title: 'รูปวงกลม', desc: 'คำนวณเส้นรอบวงและพื้นที่วงกลม' },
                { icon: '📝', title: 'บทประยุกต์', desc: 'แก้โจทย์ปัญหาที่ซับซ้อน' },
                { icon: '📦', title: 'รูปเรขาคณิตสามมิติและปริมาตรของทรงสี่เหลี่ยมมุมฉาก', desc: 'คำนวณปริมาตรขั้นสูง' },
                { icon: '📊', title: 'สถิติและความน่าจะเป็นเบื้องต้น', desc: 'วิเคราะห์ข้อมูลและความน่าจะเป็น' }
            ]
        };

        const quizQuestions = {
            grade4: {
                1: [
                    { q: '234,567 อ่านว่าอะไร?', a: ['สองแสนสามหมื่นสี่พันห้าร้อยหกสิบเจ็ด', 'สองสามสี่ห้าหกเจ็ด', 'สองล้านสามแสน', 'สองพันสามร้อย'], c: 0 },
                    { q: 'จำนวนใดมากกว่า? 345,678 หรือ 234,567', a: ['345,678', '234,567', 'เท่ากัน', 'ไม่แน่ใจ'], c: 0 },
                    { q: 'ในจำนวน 567,890 เลข 6 อยู่หลักอะไร?', a: ['หลักหมื่น', 'หลักพัน', 'หลักแสน', 'หลักร้อย'], c: 0 },
                    { q: '500,000 + 50,000 = ?', a: ['550,000', '500,050', '5,500,000', '55,000'], c: 0 },
                    { q: 'จำนวนใดน้อยที่สุด?', a: ['123,456', '234,567', '345,678', '456,789'], c: 0 }
                ],
                2: [
                    { q: '12,345 + 23,456 = ?', a: ['35,801', '35,701', '36,801', '34,801'], c: 0 },
                    { q: '45,678 - 12,345 = ?', a: ['33,333', '32,333', '34,333', '33,433'], c: 0 },
                    { q: '1,000 + 2,000 + 3,000 = ?', a: ['6,000', '5,000', '7,000', '4,000'], c: 0 },
                    { q: '50,000 - 25,000 = ?', a: ['25,000', '75,000', '20,000', '30,000'], c: 0 },
                    { q: '123 + 456 + 789 = ?', a: ['1,368', '1,268', '1,468', '1,168'], c: 0 }
                ],
                3: [
                    { q: 'มุมฉากมีขนาดเท่าไร?', a: ['90 องศา', '180 องศา', '45 องศา', '60 องศา'], c: 0 },
                    { q: 'มุมตรงมีขนาดเท่าไร?', a: ['180 องศา', '90 องศา', '360 องศา', '270 องศา'], c: 0 },
                    { q: 'มุมที่มีขนาด 45 องศา เป็นมุมชนิดใด?', a: ['มุมแหลม', 'มุมฉาก', 'มุมป้าน', 'มุมตรง'], c: 0 },
                    { q: 'มุมที่มีขนาด 120 องศา เป็นมุมชนิดใด?', a: ['มุมป้าน', 'มุมแหลม', 'มุมฉาก', 'มุมตรง'], c: 0 },
                    { q: 'รูปสี่เหลี่ยมจัตุรัสมีมุมฉากกี่มุม?', a: ['4 มุม', '2 มุม', '3 มุม', '1 มุม'], c: 0 }
                ],
                4: [
                    { q: '12 × 12 = ?', a: ['144', '124', '134', '154'], c: 0 },
                    { q: '25 × 4 = ?', a: ['100', '90', '110', '80'], c: 0 },
                    { q: '234 × 2 = ?', a: ['468', '458', '478', '448'], c: 0 },
                    { q: '15 × 6 = ?', a: ['90', '80', '100', '70'], c: 0 },
                    { q: '8 × 9 = ?', a: ['72', '63', '81', '54'], c: 0 }
                ],
                5: [
                    { q: '144 ÷ 12 = ?', a: ['12', '11', '13', '14'], c: 0 },
                    { q: '100 ÷ 4 = ?', a: ['25', '20', '30', '15'], c: 0 },
                    { q: '81 ÷ 9 = ?', a: ['9', '8', '10', '7'], c: 0 },
                    { q: '56 ÷ 7 = ?', a: ['8', '7', '9', '6'], c: 0 },
                    { q: '48 ÷ 6 = ?', a: ['8', '7', '9', '6'], c: 0 }
                ],
                6: [
                    { q: 'แผนภูมิแท่งใช้แสดงอะไร?', a: ['ข้อมูลเป็นแท่ง', 'ข้อมูลเป็นรูปภาพ', 'ข้อมูลเป็นตัวเลข', 'ข้อมูลเป็นตาราง'], c: 0 },
                    { q: 'ถ้าแอปเปิล 10 คน กล้วย 15 คน ผลไม้ใดได้รับความนิยมมากกว่า?', a: ['กล้วย', 'แอปเปิล', 'เท่ากัน', 'ไม่แน่ใจ'], c: 0 },
                    { q: 'แผนภูมิรูปภาพใช้อะไรแสดงข้อมูล?', a: ['รูปภาพ', 'แท่ง', 'ตัวเลข', 'เส้น'], c: 0 },
                    { q: 'ตารางใช้แสดงข้อมูลอย่างไร?', a: ['เป็นแถวและคอลัมน์', 'เป็นรูปภาพ', 'เป็นแท่ง', 'เป็นวงกลม'], c: 0 },
                    { q: 'ถ้าข้อมูลมี 3 กลุ่ม ควรใช้แผนภูมิแบบใด?', a: ['แผนภูมิแท่ง', 'ไม่ต้องใช้', 'ใช้ตัวเลข', 'ใช้รูปภาพ'], c: 0 }
                ],
                7: [
                    { q: '1 เมตร = กี่เซนติเมตร?', a: ['100', '10', '1000', '50'], c: 0 },
                    { q: '1 กิโลกรัม = กี่กรัม?', a: ['1000', '100', '10', '500'], c: 0 },
                    { q: '1 ลิตร = กี่มิลลิลิตร?', a: ['1000', '100', '10', '500'], c: 0 },
                    { q: '2.5 เมตร = กี่เซนติเมตร?', a: ['250', '25', '2500', '2.5'], c: 0 },
                    { q: '500 กรัม = กี่กิโลกรัม?', a: ['0.5', '5', '50', '0.05'], c: 0 }
                ],
                8: [
                    { q: 'พื้นที่สี่เหลี่ยมผืนผ้า กว้าง 5 ม. ยาว 8 ม. = ?', a: ['40 ตร.ม.', '13 ตร.ม.', '45 ตร.ม.', '35 ตร.ม.'], c: 0 },
                    { q: 'พื้นที่สี่เหลี่ยมจัตุรัส ด้าน 6 ม. = ?', a: ['36 ตร.ม.', '12 ตร.ม.', '24 ตร.ม.', '30 ตร.ม.'], c: 0 },
                    { q: 'พื้นที่สามเหลี่ยม ฐาน 10 ม. สูง 6 ม. = ?', a: ['30 ตร.ม.', '60 ตร.ม.', '16 ตร.ม.', '20 ตร.ม.'], c: 0 },
                    { q: 'สูตรพื้นที่สี่เหลี่ยมผืนผ้าคือ?', a: ['กว้าง × ยาว', 'ด้าน × ด้าน', 'ฐาน × สูง ÷ 2', 'กว้าง + ยาว'], c: 0 },
                    { q: 'สูตรพื้นที่สามเหลี่ยมคือ?', a: ['(ฐาน × สูง) ÷ 2', 'ฐาน × สูง', 'ด้าน × ด้าน', 'กว้าง × ยาว'], c: 0 }
                ],
                9: [
                    { q: 'ซื้อของ 85 บาท จ่าย 100 บาท เงินทอน?', a: ['15 บาท', '185 บาท', '85 บาท', '100 บาท'], c: 0 },
                    { q: '50 + 20 + 10 = ?', a: ['80 บาท', '70 บาท', '90 บาท', '60 บาท'], c: 0 },
                    { q: 'ธนบัตรใบละ 100 บาท 5 ใบ = ?', a: ['500 บาท', '100 บาท', '50 บาท', '1000 บาท'], c: 0 },
                    { q: 'ซื้อของ 120 บาท จ่าย 200 บาท เงินทอน?', a: ['80 บาท', '320 บาท', '120 บาท', '200 บาท'], c: 0 },
                    { q: '100 - 35 = ?', a: ['65 บาท', '135 บาท', '35 บาท', '100 บาท'], c: 0 }
                ],
                10: [
                    { q: '1/2 เท่ากับเศษส่วนใด?', a: ['2/4', '1/4', '3/4', '1/3'], c: 0 },
                    { q: '1/2 กับ 1/4 อันไหนมากกว่า?', a: ['1/2', '1/4', 'เท่ากัน', 'ไม่แน่ใจ'], c: 0 },
                    { q: 'เศษส่วน 3/6 เท่ากับ?', a: ['1/2', '1/3', '2/3', '1/4'], c: 0 },
                    { q: 'ในเศษส่วน 3/4 ตัวเศษคือ?', a: ['3', '4', '7', '1'], c: 0 },
                    { q: 'ในเศษส่วน 2/5 ตัวส่วนคือ?', a: ['5', '2', '7', '3'], c: 0 }
                ],
                11: [
                    { q: '1 ชั่วโมง = กี่นาที?', a: ['60', '30', '120', '90'], c: 0 },
                    { q: '1 นาที = กี่วินาที?', a: ['60', '30', '120', '90'], c: 0 },
                    { q: '1 วัน = กี่ชั่วโมง?', a: ['24', '12', '48', '36'], c: 0 },
                    { q: '1 สัปดาห์ = กี่วัน?', a: ['7', '5', '10', '14'], c: 0 },
                    { q: 'เริ่ม 14.00 น. เสร็จ 15.30 น. ใช้เวลา?', a: ['1 ชม. 30 นาที', '30 นาที', '1 ชม.', '2 ชม.'], c: 0 }
                ],
                12: [
                    { q: '3.45 อ่านว่า?', a: ['สามจุดสี่ห้า', 'สามสี่ห้า', 'สามจุดสี่สิบห้า', 'สามสี่'], c: 0 },
                    { q: '3.45 กับ 3.4 อันไหนมากกว่า?', a: ['3.45', '3.4', 'เท่ากัน', 'ไม่แน่ใจ'], c: 0 },
                    { q: 'ในทศนิยม 5.67 เลข 6 อยู่หลักอะไร?', a: ['หลักส่วนสิบ', 'หลักหน่วย', 'หลักส่วนร้อย', 'หลักสิบ'], c: 0 },
                    { q: '2.5 + 1.3 = ?', a: ['3.8', '3.5', '4.8', '2.8'], c: 0 },
                    { q: '5.0 - 2.5 = ?', a: ['2.5', '7.5', '3.5', '2.0'], c: 0 }
                ],
                13: [
                    { q: '5 + 3 × 2 = ?', a: ['11', '16', '13', '10'], c: 0 },
                    { q: '(5 + 3) × 2 = ?', a: ['16', '11', '13', '10'], c: 0 },
                    { q: '10 - 2 × 3 = ?', a: ['4', '24', '6', '8'], c: 0 },
                    { q: '20 ÷ 4 + 5 = ?', a: ['10', '5', '15', '4'], c: 0 },
                    { q: '3 × 4 + 2 × 5 = ?', a: ['22', '20', '24', '18'], c: 0 }
                ]
            },
            grade5: {
                1: [
                    { q: '5 + 3 × 2 = ?', a: ['11', '16', '13', '10'], c: 0 },
                    { q: '(5 + 3) × 2 = ?', a: ['16', '11', '13', '10'], c: 0 },
                    { q: '10 - 2 × 3 = ?', a: ['4', '24', '6', '8'], c: 0 },
                    { q: '20 ÷ 4 + 5 = ?', a: ['10', '5', '15', '4'], c: 0 },
                    { q: '3 × 4 + 2 × 5 = ?', a: ['22', '20', '24', '18'], c: 0 }
                ],
                2: [
                    { q: 'มุม 45 องศา เป็นมุมชนิดใด?', a: ['มุมแหลม', 'มุมฉาก', 'มุมป้าน', 'มุมตรง'], c: 0 },
                    { q: 'มุม 90 องศา เป็นมุมชนิดใด?', a: ['มุมฉาก', 'มุมแหลม', 'มุมป้าน', 'มุมตรง'], c: 0 },
                    { q: 'มุม 120 องศา เป็นมุมชนิดใด?', a: ['มุมป้าน', 'มุมแหลม', 'มุมฉาก', 'มุมตรง'], c: 0 },
                    { q: 'มุม 180 องศา เป็นมุมชนิดใด?', a: ['มุมตรง', 'มุมแหลม', 'มุมฉาก', 'มุมป้าน'], c: 0 },
                    { q: 'มุม 270 องศา เป็นมุมชนิดใด?', a: ['มุมกลับ', 'มุมแหลม', 'มุมฉาก', 'มุมป้าน'], c: 0 }
                ],
                3: [
                    { q: 'เส้นขนานคือเส้นที่?', a: ['ไม่ตัดกัน', 'ตัดกัน', 'ตั้งฉาก', 'โค้ง'], c: 0 },
                    { q: 'เส้นตั้งฉากทำมุมกันกี่องศา?', a: ['90', '180', '45', '60'], c: 0 },
                    { q: 'เส้นขนานมีระยะห่างเท่าไหร่?', a: ['เท่ากันตลอด', 'ไม่เท่ากัน', 'เพิ่มขึ้น', 'ลดลง'], c: 0 },
                    { q: 'สัญลักษณ์ // หมายถึง?', a: ['เส้นขนาน', 'เส้นตั้งฉาก', 'เส้นตรง', 'เส้นโค้ง'], c: 0 },
                    { q: 'สัญลักษณ์ ⊥ หมายถึง?', a: ['เส้นตั้งฉาก', 'เส้นขนาน', 'เส้นตรง', 'เส้นโค้ง'], c: 0 }
                ],
                4: [
                    { q: 'ค่าเฉลี่ยของ 2, 4, 6 คือ?', a: ['4', '3', '5', '6'], c: 0 },
                    { q: 'ฐานนิยมของ 1, 2, 2, 3, 2 คือ?', a: ['2', '1', '3', '4'], c: 0 },
                    { q: 'มัธยฐานของ 1, 3, 5 คือ?', a: ['3', '1', '5', '2'], c: 0 },
                    { q: 'โยนเหรียญ 1 ครั้ง มีโอกาสออกหัวกี่ครั้ง?', a: ['1/2', '1', '0', '2'], c: 0 },
                    { q: 'ทอดลูกเต๋า 1 ครั้ง มีโอกาสออกเลข 6 เท่าไร?', a: ['1/6', '1/2', '1', '0'], c: 0 }
                ],
                5: [
                    { q: '1/2 + 1/4 = ?', a: ['3/4', '2/6', '1/2', '1/4'], c: 0 },
                    { q: '3/4 - 1/4 = ?', a: ['2/4', '4/8', '1/2', '3/8'], c: 0 },
                    { q: '2/3 + 1/3 = ?', a: ['3/3', '3/6', '1/3', '2/3'], c: 0 },
                    { q: '5/6 - 2/6 = ?', a: ['3/6', '7/12', '3/12', '5/12'], c: 0 },
                    { q: '1/2 + 1/2 = ?', a: ['1', '2/4', '1/4', '2'], c: 0 }
                ],
                6: [
                    { q: '1/2 × 2 = ?', a: ['1', '1/2', '2', '1/4'], c: 0 },
                    { q: '3/4 × 2 = ?', a: ['3/2', '6/8', '3/8', '6/4'], c: 0 },
                    { q: '2/3 ÷ 2 = ?', a: ['1/3', '4/6', '2/6', '4/3'], c: 0 },
                    { q: '1/2 × 1/2 = ?', a: ['1/4', '1/2', '2/4', '1'], c: 0 },
                    { q: '3/4 ÷ 3 = ?', a: ['1/4', '3/12', '9/12', '3/4'], c: 0 }
                ],
                7: [
                    { q: '2.5 + 1.3 = ?', a: ['3.8', '3.5', '4.8', '2.8'], c: 0 },
                    { q: '5.0 - 2.5 = ?', a: ['2.5', '7.5', '3.5', '2.0'], c: 0 },
                    { q: '1.5 + 2.5 = ?', a: ['4.0', '3.0', '5.0', '3.5'], c: 0 },
                    { q: '10.0 - 3.5 = ?', a: ['6.5', '13.5', '7.5', '6.0'], c: 0 },
                    { q: '0.5 + 0.5 = ?', a: ['1.0', '0.10', '0.55', '1.5'], c: 0 }
                ],
                8: [
                    { q: '2.5 × 2 = ?', a: ['5.0', '4.5', '5.5', '4.0'], c: 0 },
                    { q: '3.0 × 3 = ?', a: ['9.0', '6.0', '12.0', '3.3'], c: 0 },
                    { q: '1.5 × 4 = ?', a: ['6.0', '5.5', '6.5', '5.0'], c: 0 },
                    { q: '0.5 × 10 = ?', a: ['5.0', '0.05', '50', '0.5'], c: 0 },
                    { q: '2.0 × 2.5 = ?', a: ['5.0', '4.5', '5.5', '4.0'], c: 0 }
                ],
                9: [
                    { q: 'ซื้อของ 3 ชิ้น ชิ้นละ 25 บาท รวม?', a: ['75 บาท', '28 บาท', '22 บาท', '50 บาท'], c: 0 },
                    { q: 'มีเงิน 100 บาท ใช้ 35 บาท เหลือ?', a: ['65 บาท', '135 บาท', '35 บาท', '100 บาท'], c: 0 },
                    { q: 'รถวิ่ง 60 กม./ชม. วิ่ง 2 ชม. ได้กี่กม.?', a: ['120 กม.', '62 กม.', '58 กม.', '30 กม.'], c: 0 },
                    { q: 'แบ่งขนม 20 ชิ้น ให้ 4 คน คนละกี่ชิ้น?', a: ['5 ชิ้น', '24 ชิ้น', '16 ชิ้น', '80 ชิ้น'], c: 0 },
                    { q: 'ซื้อของ 2 ชิ้น ชิ้นละ 15 บาท จ่าย 50 บาท เงินทอน?', a: ['20 บาท', '30 บาท', '35 บาท', '80 บาท'], c: 0 }
                ],
                10: [
                    { q: 'สี่เหลี่ยมจัตุรัสมีด้านกี่ด้าน?', a: ['4', '3', '5', '6'], c: 0 },
                    { q: 'สี่เหลี่ยมผืนผ้ามีมุมฉากกี่มุม?', a: ['4', '2', '3', '1'], c: 0 },
                    { q: 'สี่เหลี่ยมจัตุรัสมีด้านยาวเท่ากันกี่ด้าน?', a: ['4', '2', '3', '1'], c: 0 },
                    { q: 'สี่เหลี่ยมขนมเปียกปูนมีด้านขนานกี่คู่?', a: ['2', '1', '3', '4'], c: 0 },
                    { q: 'สี่เหลี่ยมคางหมูมีด้านขนานกี่คู่?', a: ['1', '2', '0', '3'], c: 0 }
                ],
                11: [
                    { q: 'สามเหลี่ยมมีด้านกี่ด้าน?', a: ['3', '4', '5', '2'], c: 0 },
                    { q: 'สามเหลี่ยมมีมุมกี่มุม?', a: ['3', '4', '2', '5'], c: 0 },
                    { q: 'สามเหลี่ยมด้านเท่ามีด้านยาวเท่ากันกี่ด้าน?', a: ['3', '2', '1', '0'], c: 0 },
                    { q: 'สามเหลี่ยมหน้าจั่วมีด้านยาวเท่ากันกี่ด้าน?', a: ['2', '3', '1', '0'], c: 0 },
                    { q: 'สามเหลี่ยมมุมฉากมีมุมฉากกี่มุม?', a: ['1', '2', '3', '0'], c: 0 }
                ],
                12: [
                    { q: 'วงกลมมีรัศมีกี่เส้น?', a: ['ไม่จำกัด', '1', '2', '3'], c: 0 },
                    { q: 'เส้นผ่านศูนย์กลางยาวกว่ารัศมีกี่เท่า?', a: ['2', '1', '3', '4'], c: 0 },
                    { q: 'รัศมีคือเส้นจากไหนถึงไหน?', a: ['จุดศูนย์กลางถึงขอบวงกลม', 'ขอบถึงขอบ', 'ข้างในวงกลม', 'ข้างนอกวงกลม'], c: 0 },
                    { q: 'เส้นรอบวงคือส่วนใด?', a: ['ขอบวงกลม', 'เส้นผ่านศูนย์กลาง', 'รัศมี', 'จุดศูนย์กลาง'], c: 0 },
                    { q: 'จุดศูนย์กลางอยู่ตรงไหน?', a: ['กึ่งกลางวงกลม', 'ขอบวงกลม', 'นอกวงกลม', 'ไม่มี'], c: 0 }
                ],
                13: [
                    { q: 'ทรงสี่เหลี่ยมมุมฉากมีหน้ากี่หน้า?', a: ['6', '4', '5', '8'], c: 0 },
                    { q: 'ปริมาตร = ?', a: ['กว้าง × ยาว × สูง', 'กว้าง × ยาว', 'กว้าง + ยาว + สูง', 'กว้าง × สูง'], c: 0 },
                    { q: 'กล่องกว้าง 2 ม. ยาว 3 ม. สูง 4 ม. ปริมาตร?', a: ['24 ลบ.ม.', '9 ลบ.ม.', '6 ลบ.ม.', '12 ลบ.ม.'], c: 0 },
                    { q: 'ทรงสี่เหลี่ยมมุมฉากมีจุดยอดกี่จุด?', a: ['8', '6', '4', '12'], c: 0 },
                    { q: 'ทรงสี่เหลี่ยมมุมฉากมีขอบกี่ขอบ?', a: ['12', '8', '6', '4'], c: 0 }
                ]
            },
            grade6: {
                1: [
                    { q: '1,250 - 380 + 500 = ?', a: ['1,370', '1,270', '1,470', '1,170'], c: 0 },
                    { q: '500 × 3 - 200 = ?', a: ['1,300', '1,200', '1,400', '1,100'], c: 0 },
                    { q: '1,000 ÷ 4 + 250 = ?', a: ['500', '400', '600', '300'], c: 0 },
                    { q: '(100 + 200) × 3 = ?', a: ['900', '600', '1,200', '300'], c: 0 },
                    { q: '2,000 - 500 × 2 = ?', a: ['1,000', '3,000', '500', '1,500'], c: 0 }
                ],
                2: [
                    { q: 'ตัวประกอบของ 12 มีอะไรบ้าง?', a: ['1,2,3,4,6,12', '1,2,3,6', '2,4,6,12', '1,3,4,12'], c: 0 },
                    { q: 'ห.ร.ม. ของ 12 และ 18 คือเท่าไร?', a: ['6', '3', '9', '12'], c: 0 },
                    { q: 'ค.ร.น. ของ 4 และ 6 คือเท่าไร?', a: ['12', '24', '6', '8'], c: 0 },
                    { q: 'ตัวประกอบของ 20 มีกี่ตัว?', a: ['6 ตัว', '4 ตัว', '5 ตัว', '7 ตัว'], c: 0 },
                    { q: 'จำนวนเฉพาะคือจำนวนที่มีตัวประกอบกี่ตัว?', a: ['2 ตัว', '1 ตัว', '3 ตัว', '4 ตัว'], c: 0 }
                ],
                3: [
                    { q: '1/2 + 1/3 = ?', a: ['5/6', '2/5', '1/5', '3/6'], c: 0 },
                    { q: '3/4 - 1/2 = ?', a: ['1/4', '2/4', '1/2', '3/8'], c: 0 },
                    { q: '2/3 × 3/4 = ?', a: ['1/2', '5/7', '6/12', '2/4'], c: 0 },
                    { q: '1/2 ÷ 1/4 = ?', a: ['2', '1/8', '1/2', '4'], c: 0 },
                    { q: '3/5 + 1/5 = ?', a: ['4/5', '4/10', '2/5', '3/10'], c: 0 }
                ],
                4: [
                    { q: '2.5 + 1.75 = ?', a: ['4.25', '4.00', '3.25', '4.50'], c: 0 },
                    { q: '5.5 - 2.25 = ?', a: ['3.25', '7.75', '3.75', '2.25'], c: 0 },
                    { q: '1.5 × 2.5 = ?', a: ['3.75', '4.00', '3.50', '4.25'], c: 0 },
                    { q: '10.0 ÷ 2.5 = ?', a: ['4', '2.5', '5', '3'], c: 0 },
                    { q: '0.75 + 0.25 = ?', a: ['1.00', '0.50', '1.50', '0.75'], c: 0 }
                ],
                5: [
                    { q: '3.5 × 2 = ?', a: ['7.0', '5.5', '6.0', '7.5'], c: 0 },
                    { q: '8.0 ÷ 4 = ?', a: ['2.0', '4.0', '12.0', '32.0'], c: 0 },
                    { q: '2.5 × 4 = ?', a: ['10.0', '6.5', '8.5', '9.0'], c: 0 },
                    { q: '15.0 ÷ 3 = ?', a: ['5.0', '12.0', '18.0', '45.0'], c: 0 },
                    { q: '1.25 × 8 = ?', a: ['10.0', '9.25', '10.25', '9.0'], c: 0 }
                ],
                6: [
                    { q: 'เส้นขนานคือเส้นที่?', a: ['ไม่ตัดกัน', 'ตัดกัน', 'ตั้งฉาก', 'โค้ง'], c: 0 },
                    { q: 'เส้นตั้งฉากทำมุมกันกี่องศา?', a: ['90', '180', '45', '60'], c: 0 },
                    { q: 'มุมแย้งมีค่าเท่าไหร่?', a: ['เท่ากัน', 'ไม่เท่ากัน', 'รวมกัน 180', 'รวมกัน 90'], c: 0 },
                    { q: 'มุมภายในรวมกันเท่าไร?', a: ['180 องศา', '90 องศา', '360 องศา', '270 องศา'], c: 0 },
                    { q: 'มุมภายนอกรวมกันเท่าไร?', a: ['360 องศา', '180 องศา', '90 องศา', '270 องศา'], c: 0 }
                ],
                7: [
                    { q: 'x + 5 = 10 แล้ว x = ?', a: ['5', '15', '10', '2'], c: 0 },
                    { q: '2x = 10 แล้ว x = ?', a: ['5', '20', '12', '8'], c: 0 },
                    { q: 'x - 3 = 7 แล้ว x = ?', a: ['10', '4', '21', '7'], c: 0 },
                    { q: 'x ÷ 2 = 5 แล้ว x = ?', a: ['10', '2.5', '7', '3'], c: 0 },
                    { q: '3x + 2 = 11 แล้ว x = ?', a: ['3', '9', '13', '4'], c: 0 }
                ],
                8: [
                    { q: 'ทิศเหนือตรงข้ามกับทิศใด?', a: ['ทิศใต้', 'ทิศตะวันออก', 'ทิศตะวันตก', 'ทิศตะวันออกเฉียงเหนือ'], c: 0 },
                    { q: 'ทิศตะวันออกตรงข้ามกับทิศใด?', a: ['ทิศตะวันตก', 'ทิศเหนือ', 'ทิศใต้', 'ทิศตะวันออกเฉียงใต้'], c: 0 },
                    { q: 'ทิศหลักมีกี่ทิศ?', a: ['4', '8', '16', '2'], c: 0 },
                    { q: 'ทิศรองมีกี่ทิศ?', a: ['4', '8', '16', '2'], c: 0 },
                    { q: 'แผนที่ใช้สัญลักษณ์อะไรบอกทิศ?', a: ['เข็มทิศ', 'ลูกศร', 'ดาว', 'วงกลม'], c: 0 }
                ],
                9: [
                    { q: 'สี่เหลี่ยมจัตุรัสมีด้านกี่ด้าน?', a: ['4', '3', '5', '6'], c: 0 },
                    { q: 'สี่เหลี่ยมผืนผ้ามีมุมฉากกี่มุม?', a: ['4', '2', '3', '1'], c: 0 },
                    { q: 'สี่เหลี่ยมจัตุรัสมีด้านยาวเท่ากันกี่ด้าน?', a: ['4', '2', '3', '1'], c: 0 },
                    { q: 'พื้นที่สี่เหลี่ยมจัตุรัส ด้าน 5 ม. = ?', a: ['25 ตร.ม.', '20 ตร.ม.', '10 ตร.ม.', '15 ตร.ม.'], c: 0 },
                    { q: 'พื้นที่สี่เหลี่ยมผืนผ้า กว้าง 4 ม. ยาว 6 ม. = ?', a: ['24 ตร.ม.', '10 ตร.ม.', '20 ตร.ม.', '12 ตร.ม.'], c: 0 }
                ],
                10: [
                    { q: 'เส้นรอบวงวงกลม = ?', a: ['2πr', 'πr²', 'πd', 'r²'], c: 0 },
                    { q: 'พื้นที่วงกลม = ?', a: ['πr²', '2πr', 'πd', 'r²'], c: 0 },
                    { q: 'ถ้ารัศมี 7 ซม. เส้นรอบวง = ? (π = 22/7)', a: ['44 ซม.', '154 ซม.', '22 ซม.', '14 ซม.'], c: 0 },
                    { q: 'ถ้ารัศมี 7 ซม. พื้นที่ = ? (π = 22/7)', a: ['154 ตร.ซม.', '44 ตร.ซม.', '22 ตร.ซม.', '14 ตร.ซม.'], c: 0 },
                    { q: 'เส้นผ่านศูนย์กลาง 14 ซม. รัศมี = ?', a: ['7 ซม.', '28 ซม.', '14 ซม.', '21 ซม.'], c: 0 }
                ],
                11: [
                    { q: 'ซื้อของ 5 ชิ้น ชิ้นละ 35 บาท รวม?', a: ['175 บาท', '40 บาท', '30 บาท', '70 บาท'], c: 0 },
                    { q: 'มีเงิน 500 บาท ใช้ 275 บาท เหลือ?', a: ['225 บาท', '775 บาท', '275 บาท', '500 บาท'], c: 0 },
                    { q: 'รถวิ่ง 80 กม./ชม. วิ่ง 3 ชม. ได้กี่กม.?', a: ['240 กม.', '83 กม.', '77 กม.', '26.67 กม.'], c: 0 },
                    { q: 'แบ่งเงิน 1,000 บาท ให้ 5 คน คนละกี่บาท?', a: ['200 บาท', '1,005 บาท', '995 บาท', '5,000 บาท'], c: 0 },
                    { q: 'ซื้อของ 3 ชิ้น ชิ้นละ 45 บาท จ่าย 200 บาท เงินทอน?', a: ['65 บาท', '135 บาท', '155 บาท', '335 บาท'], c: 0 }
                ],
                12: [
                    { q: 'ทรงสี่เหลี่ยมมุมฉากมีหน้ากี่หน้า?', a: ['6', '4', '5', '8'], c: 0 },
                    { q: 'ปริมาตร = ?', a: ['กว้าง × ยาว × สูง', 'กว้าง × ยาว', 'กว้าง + ยาว + สูง', 'กว้าง × สูง'], c: 0 },
                    { q: 'กล่องกว้าง 3 ม. ยาว 4 ม. สูง 5 ม. ปริมาตร?', a: ['60 ลบ.ม.', '12 ลบ.ม.', '15 ลบ.ม.', '20 ลบ.ม.'], c: 0 },
                    { q: 'ทรงสี่เหลี่ยมมุมฉากมีจุดยอดกี่จุด?', a: ['8', '6', '4', '12'], c: 0 },
                    { q: 'ทรงสี่เหลี่ยมมุมฉากมีขอบกี่ขอบ?', a: ['12', '8', '6', '4'], c: 0 }
                ],
                13: [
                    { q: 'ค่าเฉลี่ยของ 10, 20, 30 คือ?', a: ['20', '15', '25', '30'], c: 0 },
                    { q: 'ฐานนิยมของ 5, 5, 6, 7, 5 คือ?', a: ['5', '6', '7', '4'], c: 0 },
                    { q: 'มัธยฐานของ 2, 4, 6 คือ?', a: ['4', '2', '6', '3'], c: 0 },
                    { q: 'โยนเหรียญ 2 ครั้ง มีโอกาสออกหัวทั้ง 2 ครั้งเท่าไร?', a: ['1/4', '1/2', '1', '0'], c: 0 },
                    { q: 'ทอดลูกเต๋า 1 ครั้ง มีโอกาสออกเลขคู่เท่าไร?', a: ['1/2', '1/6', '1/3', '1'], c: 0 }
                ]
            }
        };

        function renderLessons() {
            ['grade4', 'grade5', 'grade6'].forEach(grade => {
                const container = document.getElementById(`${grade}Lessons`);
                const lessons = lessonData[grade];
                
                container.innerHTML = lessons.map((lesson, index) => `
                    <div class="lesson-card">
                        <div class="lesson-icon">${lesson.icon}</div>
                        <h3>บทที่ ${index + 1}: ${lesson.title}</h3>
                        <p>${lesson.desc}</p>
                        <div class="lesson-actions">
                            <button class="lesson-btn" onclick="showLessonDetail('${grade}', ${index})">เริ่มเรียน</button>
                            <button class="quiz-btn" onclick="startChapterQuiz('${grade}', ${index + 1})">ทำแบบทดสอบ</button>
                        </div>
                    </div>
                `).join('');
            });
        }

        function showLessonDetail(grade, index) {
            const lesson = lessonData[grade][index];
            showCustomMessage(
                `${lesson.icon} ${lesson.title}`,
                `<p style="text-align: left; line-height: 1.8;">${lesson.desc}</p>
                <p style="text-align: left; margin-top: 1rem; color: #666;">📚 เนื้อหาบทเรียนนี้จะช่วยให้คุณเข้าใจหลักการพื้นฐานและสามารถนำไปใช้ได้จริง</p>`,
                [{ text: 'เข้าใจแล้ว', action: null }]
            );
        }

        function showCustomMessage(title, message, buttons) {
            const overlay = document.createElement('div');
            overlay.className = 'custom-message-overlay';
            
            const buttonsHtml = buttons.map(btn => 
                `<button class="custom-message-btn" onclick="this.closest('.custom-message-overlay').remove(); ${btn.action ? btn.action : ''}">${btn.text}</button>`
            ).join('');
            
            overlay.innerHTML = `
                <div class="custom-message-box">
                    <h3>${title}</h3>
                    <div>${message}</div>
                    <div class="custom-message-buttons">${buttonsHtml}</div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }

        let currentQuestion = 0;
        let score = 0;
        let questions = [];
        let studentName = '';
        let quizStartTime = null;
        let currentGrade = '';
        let currentChapter = 0;

        function startChapterQuiz(grade, chapter) {
            if (!quizQuestions[grade] || !quizQuestions[grade][chapter]) {
                showCustomMessage(
                    '⚠️ แบบทดสอบพร้อมใช้งาน',
                    '<p>แบบทดสอบบทนี้พร้อมให้ทำแล้ว! คลิกปุ่มด้านล่างเพื่อเริ่มทำแบบทดสอบ</p>',
                    [
                        { text: 'ปิด', action: null },
                        { text: 'ไปทำแบบทดสอบ', action: function() { navigateTo('quiz'); } }
                    ]
                );
                return;
            }

            currentGrade = grade;
            currentChapter = chapter;
            navigateTo('quiz');
        }

        function resetQuiz() {
            document.getElementById('nameInputSection').style.display = 'block';
            document.getElementById('quizSection').style.display = 'none';
            document.getElementById('studentName').value = '';
            
            // รีเซ็ตตัวแปรทั้งหมดให้กลับสู่ค่าเริ่มต้น
            currentQuestion = 0;
            score = 0;
            questions = [];
            studentName = '';
            quizStartTime = null;
            currentGrade = '';
            currentChapter = 0;
            
            document.getElementById('score').textContent = score;
            
            // รีเซ็ตหน้าจอแบบทดสอบให้พร้อมสำหรับการเริ่มใหม่
            const questionBox = document.querySelector('.question-box');
            const nextBtn = document.getElementById('nextBtn');
            
            questionBox.innerHTML = `
                <h3 id="question">กำลังโหลดคำถาม...</h3>
                <div class="answers" id="answers"></div>
            `;
            
            nextBtn.style.display = 'none';
            nextBtn.disabled = false;
            nextBtn.textContent = 'คำถามถัดไป';
        }

        function startQuizWithName() {
            const nameInput = document.getElementById('studentName');
            studentName = nameInput.value.trim();
            
            if (!studentName) {
                showCustomMessage('⚠️ กรุณากรอกชื่อ', '<p>กรุณากรอกชื่อของคุณก่อนเริ่มทำแบบทดสอบ</p>', [{ text: 'ตกลง', action: null }]);
                return;
            }

            // ซ่อนส่วนกรอกชื่อและแสดงส่วนแบบทดสอบ
            document.getElementById('nameInputSection').style.display = 'none';
            document.getElementById('quizSection').style.display = 'block';
            
            // รีเซ็ตค่าทั้งหมดก่อนเริ่มแบบทดสอบใหม่
            currentQuestion = 0;
            score = 0;
            quizStartTime = new Date();
            
            // สร้างคำถามใหม่
            generateQuestions();
            
            // แสดงคำถามแรก
            showQuestion();
            document.getElementById('score').textContent = score;
        }

        function endQuiz() {
            const quizEndTime = new Date();
            const timeTaken = Math.floor((quizEndTime - quizStartTime) / 1000);
            const minutes = Math.floor(timeTaken / 60);
            const seconds = timeTaken % 60;
            const timeString = `${minutes} นาที ${seconds} วินาที`;

            const questionBox = document.querySelector('.question-box');
            const nextBtn = document.getElementById('nextBtn');
            
            nextBtn.disabled = true;

            const scoreData = {
                student_name: studentName,
                score: score,
                total_questions: questions.length,
                date: new Date().toISOString(),
                time_taken: timeString,
                grade: currentGrade || 'general',
                chapter: currentChapter || 0
            };

            // แสดงผลลัพธ์
            questionBox.innerHTML = `
                <h3 style="color: #667eea; font-size: 2rem;">🎉 เยี่ยมมาก! 🎉</h3>
                <p style="font-size: 1.3rem; margin: 1.5rem 0; color: #333;"><strong>${studentName}</strong></p>
                <p style="font-size: 1.5rem; margin: 1rem 0; color: #333;">คุณได้คะแนน <strong style="color: #667eea;">${score}</strong> จาก 100</p>
                <p style="font-size: 1.1rem; margin: 1rem 0; color: #666;">ใช้เวลา: ${timeString}</p>
                <p style="font-size: 1rem; margin: 1.5rem 0; color: #4caf50; font-weight: bold;">✅ บันทึกคะแนนเรียบร้อยแล้ว</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
                    <button class="lesson-btn" onclick="resetAndStartNewQuiz()" style="font-size: 1.1rem; padding: 1rem 2rem;">ทำแบบทดสอบอีกครั้ง</button>
                    <button class="lesson-btn" onclick="navigateTo('history')" style="font-size: 1.1rem; padding: 1rem 2rem; background: #764ba2;">ดูประวัติคะแนน</button>
                    <button class="lesson-btn" onclick="goToHomePage()" style="font-size: 1.1rem; padding: 1rem 2rem; background: #4caf50;">กลับหน้าหลัก</button>
                </div>
            `;
            
            nextBtn.style.display = 'none';
            
            // บันทึกคะแนน (ถ้ามีระบบบันทึก)
            saveScoreLocally(scoreData);
            
            // โหลดคะแนนใหม่
            setTimeout(() => {
                loadScoresFromLocalStorage();
            }, 500);
        }

        // ฟังก์ชันใหม่สำหรับเริ่มแบบทดสอบใหม่
        function resetAndStartNewQuiz() {
            resetQuiz();
            
            // แสดงส่วนกรอกชื่ออีกครั้ง
            document.getElementById('nameInputSection').style.display = 'block';
            document.getElementById('quizSection').style.display = 'none';
            
            // ล้างชื่อผู้ใช้
            document.getElementById('studentName').value = '';
            document.getElementById('studentName').focus();
        }

        // ฟังก์ชันกลับหน้าหลัก
        function goToHomePage() {
            resetQuiz();
            navigateTo('home');
        }

        // ฟังก์ชันบันทึกคะแนนใน localStorage (ชั่วคราว)
        function saveScoreLocally(scoreData) {
            try {
                let scores = JSON.parse(localStorage.getItem('mathQuizScores')) || [];
                scores.push(scoreData);
                localStorage.setItem('mathQuizScores', JSON.stringify(scores));
                return true;
            } catch (error) {
                console.error('Error saving score locally:', error);
                return false;
            }
        }

        // ฟังก์ชันโหลดคะแนนจาก localStorage
        function loadScoresFromLocalStorage() {
            try {
                const scores = JSON.parse(localStorage.getItem('mathQuizScores')) || [];
                allScores = scores;
                updateHistoryDisplay();
            } catch (error) {
                console.error('Error loading scores from localStorage:', error);
                allScores = [];
                updateHistoryDisplay();
            }
        }

        // แก้ไขฟังก์ชัน navigateTo เพื่อให้รีเซ็ตแบบทดสอบเมื่อไปที่หน้า quiz
        function navigateTo(pageName) {
            const targetLink = document.querySelector(`[data-page="${pageName}"]`);
            if (targetLink) {
                // ถ้าไปที่หน้าแบบทดสอบ ให้รีเซ็ตแบบทดสอบ
                if (pageName === 'quiz') {
                    resetQuiz();
                }
                targetLink.click();
            }
        }

        function generateQuestions() {
            if (currentGrade && currentChapter && quizQuestions[currentGrade] && quizQuestions[currentGrade][currentChapter]) {
                questions = quizQuestions[currentGrade][currentChapter];
            } else {
                questions = [];
                for (let i = 0; i < 5; i++) {
                    const num1 = Math.floor(Math.random() * 20) + 1;
                    const num2 = Math.floor(Math.random() * 20) + 1;
                    const operators = ['+', '-', '×', '÷'];
                    const operator = operators[Math.floor(Math.random() * operators.length)];
                    
                    let answer;
                    let questionText;
                    
                    switch(operator) {
                        case '+':
                            answer = num1 + num2;
                            questionText = `${num1} + ${num2} = ?`;
                            break;
                        case '-':
                            answer = num1 - num2;
                            questionText = `${num1} - ${num2} = ?`;
                            break;
                        case '×':
                            answer = num1 * num2;
                            questionText = `${num1} × ${num2} = ?`;
                            break;
                        case '÷':
                            const divisor = Math.floor(Math.random() * 10) + 1;
                            const dividend = divisor * (Math.floor(Math.random() * 10) + 1);
                            answer = dividend / divisor;
                            questionText = `${dividend} ÷ ${divisor} = ?`;
                            break;
                    }
                    
                    const wrongAnswers = [];
                    while (wrongAnswers.length < 3) {
                        const wrong = answer + Math.floor(Math.random() * 10) - 5;
                        if (wrong !== answer && !wrongAnswers.includes(wrong) && wrong > 0) {
                            wrongAnswers.push(wrong);
                        }
                    }
                    
                    const allAnswers = [answer, ...wrongAnswers].sort(() => Math.random() - 0.5);
                    
                    questions.push({
                        q: questionText,
                        a: allAnswers.map(String),
                        c: allAnswers.indexOf(answer)
                    });
                }
            }
        }

        function startQuizWithName() {
            const nameInput = document.getElementById('studentName');
            studentName = nameInput.value.trim();
            
            if (!studentName) {
                showCustomMessage('⚠️ กรุณากรอกชื่อ', '<p>กรุณากรอกชื่อของคุณก่อนเริ่มทำแบบทดสอบ</p>', [{ text: 'ตกลง', action: null }]);
                return;
            }

            document.getElementById('nameInputSection').style.display = 'none';
            document.getElementById('quizSection').style.display = 'block';
            
            currentQuestion = 0;
            score = 0;
            quizStartTime = new Date();
            generateQuestions();
            showQuestion();
            document.getElementById('score').textContent = score;
        }

        function showQuestion() {
            if (currentQuestion >= questions.length) {
                endQuiz();
                return;
            }
            
            const q = questions[currentQuestion];
            document.getElementById('question').textContent = `ข้อที่ ${currentQuestion + 1}: ${q.q}`;
            
            const answersDiv = document.getElementById('answers');
            answersDiv.innerHTML = '';
            
            q.a.forEach((answer, index) => {
                const btn = document.createElement('button');
                btn.className = 'answer-btn';
                btn.textContent = answer;
                btn.onclick = () => checkAnswer(index, q.c, btn);
                answersDiv.appendChild(btn);
            });
            
            document.getElementById('nextBtn').style.display = 'none';
        }

        function checkAnswer(selected, correct, btn) {
            const buttons = document.querySelectorAll('.answer-btn');
            buttons.forEach(b => b.disabled = true);
            
            if (selected === correct) {
                btn.classList.add('correct');
                score += Math.floor(100 / questions.length);
                document.getElementById('score').textContent = score;
            } else {
                btn.classList.add('wrong');
                buttons[correct].classList.add('correct');
            }
            
            document.getElementById('nextBtn').style.display = 'block';
        }

        function nextQuestion() {
            currentQuestion++;
            showQuestion();
        }

        // ฟังก์ชันบันทึกคะแนนไปยัง Google Sheets
        async function saveScoreToServer(scoreData) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'save_score',
                        'student_name': scoreData.student_name,
                        'score': scoreData.score,
                        'total_questions': scoreData.total_questions,
                        'date': scoreData.date,
                        'time_taken': scoreData.time_taken,
                        'grade': scoreData.grade,
                        'chapter': scoreData.chapter
                    })
                });

                const result = await response.json();
                return result.success;
            } catch (error) {
                console.error('Error saving score to Google Sheets:', error);
                return false;
            }
        }

        // ฟังก์ชันโหลดคะแนนจาก Google Sheets
        async function loadScoresFromServer() {
            try {
                const response = await fetch('?action=get_scores');
                const scores = await response.json();
                
                // ตรวจสอบว่า scores เป็น array
                if (Array.isArray(scores)) {
                    allScores = scores;
                    updateHistoryDisplay();
                } else {
                    console.error('Invalid scores data:', scores);
                    allScores = [];
                    updateHistoryDisplay();
                }
            } catch (error) {
                console.error('Error loading scores from Google Sheets:', error);
                allScores = [];
                updateHistoryDisplay();
            }
        }

        async function endQuiz() {
            const quizEndTime = new Date();
            const timeTaken = Math.floor((quizEndTime - quizStartTime) / 1000);
            const minutes = Math.floor(timeTaken / 60);
            const seconds = timeTaken % 60;
            const timeString = `${minutes} นาที ${seconds} วินาที`;

            const questionBox = document.querySelector('.question-box');
            const nextBtn = document.getElementById('nextBtn');
            
            nextBtn.disabled = true;
            nextBtn.innerHTML = '<span class="loading-spinner"></span> กำลังบันทึกคะแนน...';

            const scoreData = {
                student_name: studentName,
                score: score,
                total_questions: questions.length,
                date: new Date().toISOString(),
                time_taken: timeString,
                grade: currentGrade || 'general',
                chapter: currentChapter || 0
            };

            const success = await saveScoreToServer(scoreData);
            
            if (success) {
                questionBox.innerHTML = `
                    <h3 style="color: #667eea; font-size: 2rem;">🎉 เยี่ยมมาก! 🎉</h3>
                    <p style="font-size: 1.3rem; margin: 1.5rem 0; color: #333;"><strong>${studentName}</strong></p>
                    <p style="font-size: 1.5rem; margin: 1rem 0; color: #333;">คุณได้คะแนน <strong style="color: #667eea;">${score}</strong> จาก 100</p>
                    <p style="font-size: 1.1rem; margin: 1rem 0; color: #666;">ใช้เวลา: ${timeString}</p>
                    <p style="font-size: 1rem; margin: 1.5rem 0; color: #4caf50; font-weight: bold;">✅ บันทึกคะแนนลง Google Sheets เรียบร้อยแล้ว</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
                        <button class="lesson-btn" onclick="resetQuiz()" style="font-size: 1.1rem; padding: 1rem 2rem;">ทำแบบทดสอบอีกครั้ง</button>
                        <button class="lesson-btn" onclick="navigateTo('history')" style="font-size: 1.1rem; padding: 1rem 2rem; background: #764ba2;">ดูประวัติคะแนน</button>
                    </div>
                `;
            } else {
                questionBox.innerHTML = `
                    <h3 style="color: #667eea; font-size: 2rem;">🎉 เยี่ยมมาก! 🎉</h3>
                    <p style="font-size: 1.3rem; margin: 1.5rem 0; color: #333;"><strong>${studentName}</strong></p>
                    <p style="font-size: 1.5rem; margin: 1rem 0; color: #333;">คุณได้คะแนน <strong style="color: #667eea;">${score}</strong> จาก 100</p>
                    <p style="font-size: 1.1rem; margin: 1rem 0; color: #666;">ใช้เวลา: ${timeString}</p>
                    <p style="font-size: 1rem; margin: 1.5rem 0; color: #f44336; font-weight: bold;">⚠️ ไม่สามารถบันทึกคะแนนได้</p>
                    <button class="lesson-btn" onclick="resetQuiz()" style="font-size: 1.1rem; padding: 1rem 2rem; margin-top: 1rem;">ทำแบบทดสอบอีกครั้ง</button>
                `;
            }
            
            nextBtn.style.display = 'none';
            currentGrade = '';
            currentChapter = 0;
            
            // โหลดคะแนนใหม่จาก Google Sheets
            setTimeout(() => {
                loadScoresFromServer();
            }, 1000);
        }

        function updateHistoryDisplay() {
            const historyList = document.getElementById('historyList');
            
            if (allScores.length === 0) {
                historyList.innerHTML = `
                    <div class="empty-history">
                        <div class="icon">📋</div>
                        <h3>ยังไม่มีประวัติคะแนน</h3>
                        <p>เริ่มทำแบบทดสอบเพื่อบันทึกคะแนนของคุณ</p>
                    </div>
                `;
                document.getElementById('totalTests').textContent = '0';
                document.getElementById('avgScore').textContent = '0';
                document.getElementById('bestScore').textContent = '0';
                return;
            }

            const totalTests = allScores.length;
            const totalScore = allScores.reduce((sum, item) => sum + item.score, 0);
            const avgScore = Math.round(totalScore / totalTests);
            const bestScore = Math.max(...allScores.map(item => item.score));

            document.getElementById('totalTests').textContent = totalTests;
            document.getElementById('avgScore').textContent = avgScore;
            document.getElementById('bestScore').textContent = bestScore;

            const sortedScores = [...allScores].sort((a, b) => b.score - a.score);

            historyList.innerHTML = sortedScores.map((item, index) => {
                const date = new Date(item.date);
                const dateStr = date.toLocaleDateString('th-TH', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                const timeStr = date.toLocaleTimeString('th-TH', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });

                let badgeClass = 'average';
                if (item.score >= 80) badgeClass = 'excellent';
                else if (item.score >= 60) badgeClass = 'good';
                else if (item.score < 40) badgeClass = 'poor';

                let rankEmoji = '📝';
                if (index === 0) rankEmoji = '🥇';
                else if (index === 1) rankEmoji = '🥈';
                else if (index === 2) rankEmoji = '🥉';

                return `
                    <div class="history-item">
                        <div class="rank">${rankEmoji}</div>
                        <div class="info">
                            <div class="name">${item.student_name}</div>
                            <div class="details">
                                📅 ${dateStr} เวลา ${timeStr}<br>
                                ⏱️ ใช้เวลา: ${item.time_taken}
                            </div>
                        </div>
                        <div class="score-badge ${badgeClass}">${item.score}</div>
                    </div>
                `;
            }).join('');
        }

        function switchCalcMode(mode) {
            currentCalcMode = mode;
            document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            renderCalculator();
        }

        function renderCalculator() {
            const buttonsContainer = document.getElementById('calcButtons');
            
            if (currentCalcMode === 'basic') {
                buttonsContainer.className = 'calc-buttons';
                buttonsContainer.innerHTML = `
                    <button class="calc-btn" onclick="appendCalc('7')">7</button>
                    <button class="calc-btn" onclick="appendCalc('8')">8</button>
                    <button class="calc-btn" onclick="appendCalc('9')">9</button>
                    <button class="calc-btn operator" onclick="appendCalc('/')">÷</button>
                    
                    <button class="calc-btn" onclick="appendCalc('4')">4</button>
                    <button class="calc-btn" onclick="appendCalc('5')">5</button>
                    <button class="calc-btn" onclick="appendCalc('6')">6</button>
                    <button class="calc-btn operator" onclick="appendCalc('*')">×</button>
                    
                    <button class="calc-btn" onclick="appendCalc('1')">1</button>
                    <button class="calc-btn" onclick="appendCalc('2')">2</button>
                    <button class="calc-btn" onclick="appendCalc('3')">3</button>
                    <button class="calc-btn operator" onclick="appendCalc('-')">−</button>
                    
                    <button class="calc-btn" onclick="appendCalc('0')">0</button>
                    <button class="calc-btn" onclick="appendCalc('.')">.</button>
                    <button class="calc-btn clear" onclick="clearCalc()">C</button>
                    <button class="calc-btn operator" onclick="appendCalc('+')">+</button>
                    
                    <button class="calc-btn equals span-2" onclick="calculateCalc()">=</button>
                    <button class="calc-btn operator" onclick="deleteLastCalc()">⌫</button>
                `;
            } else {
                buttonsContainer.className = 'calc-buttons scientific';
                buttonsContainer.innerHTML = `
                    <button class="calc-btn function" onclick="appendCalc('sin(')">sin</button>
                    <button class="calc-btn function" onclick="appendCalc('cos(')">cos</button>
                    <button class="calc-btn function" onclick="appendCalc('tan(')">tan</button>
                    <button class="calc-btn function" onclick="appendCalc('sqrt(')">√</button>
                    <button class="calc-btn clear" onclick="clearCalc()">C</button>
                    
                    <button class="calc-btn function" onclick="appendCalc('log(')">log</button>
                    <button class="calc-btn function" onclick="appendCalc('ln(')">ln</button>
                    <button class="calc-btn function" onclick="appendCalc('^')">x^y</button>
                    <button class="calc-btn operator" onclick="appendCalc('(')">(</button>
                    <button class="calc-btn operator" onclick="appendCalc(')')">)</button>
                    
                    <button class="calc-btn" onclick="appendCalc('7')">7</button>
                    <button class="calc-btn" onclick="appendCalc('8')">8</button>
                    <button class="calc-btn" onclick="appendCalc('9')">9</button>
                    <button class="calc-btn operator" onclick="appendCalc('/')">÷</button>
                    <button class="calc-btn operator" onclick="deleteLastCalc()">⌫</button>
                    
                    <button class="calc-btn" onclick="appendCalc('4')">4</button>
                    <button class="calc-btn" onclick="appendCalc('5')">5</button>
                    <button class="calc-btn" onclick="appendCalc('6')">6</button>
                    <button class="calc-btn operator" onclick="appendCalc('*')">×</button>
                    <button class="calc-btn function" onclick="appendCalc('PI')">π</button>
                    
                    <button class="calc-btn" onclick="appendCalc('1')">1</button>
                    <button class="calc-btn" onclick="appendCalc('2')">2</button>
                    <button class="calc-btn" onclick="appendCalc('3')">3</button>
                    <button class="calc-btn operator" onclick="appendCalc('-')">−</button>
                    <button class="calc-btn function" onclick="appendCalc('E')">e</button>
                    
                    <button class="calc-btn" onclick="appendCalc('0')">0</button>
                    <button class="calc-btn" onclick="appendCalc('.')">.</button>
                    <button class="calc-btn equals span-2" onclick="calculateCalc()">=</button>
                    <button class="calc-btn operator" onclick="appendCalc('+')">+</button>
                `;
            }
        }

        function appendCalc(value) {
            if (calcResult !== '0' && calcExpression === '') {
                calcExpression = calcResult;
            }
            calcExpression += value;
            updateCalcDisplay();
        }

        function deleteLastCalc() {
            calcExpression = calcExpression.slice(0, -1);
            if (calcExpression === '') {
                calcExpression = '';
                calcResult = '0';
            }
            updateCalcDisplay();
        }

        function clearCalc() {
            calcExpression = '';
            calcResult = '0';
            updateCalcDisplay();
        }

        function calculateCalc() {
            try {
                let expr = calcExpression
                    .replace(/sin\(/g, 'Math.sin(')
                    .replace(/cos\(/g, 'Math.cos(')
                    .replace(/tan\(/g, 'Math.tan(')
                    .replace(/sqrt\(/g, 'Math.sqrt(')
                    .replace(/log\(/g, 'Math.log10(')
                    .replace(/ln\(/g, 'Math.log(')
                    .replace(/PI/g, 'Math.PI')
                    .replace(/E/g, 'Math.E')
                    .replace(/\^/g, '**')
                    .replace(/×/g, '*')
                    .replace(/÷/g, '/')
                    .replace(/−/g, '-');
                
                const result = eval(expr);
                calcResult = Number.isFinite(result) ? String(Math.round(result * 1000000) / 1000000) : 'Error';
                calcExpression = '';
            } catch (e) {
                calcResult = 'Error';
                calcExpression = '';
            }
            updateCalcDisplay();
        }

        function updateCalcDisplay() {
            document.getElementById('calcExpression').textContent = calcExpression || '';
            document.getElementById('calcResult').textContent = calcResult;
        }

        function openCalculator() {
            const popup = document.getElementById('calculatorPopup');
            popup.classList.add('active');
            renderCalculator();
            
            const rect = popup.getBoundingClientRect();
            popup.style.left = `${(window.innerWidth - rect.width) / 2}px`;
            popup.style.top = `${(window.innerHeight - rect.height) / 2}px`;
        }

        function closeCalculator() {
            document.getElementById('calculatorPopup').classList.remove('active');
        }

        let isDragging = false;
        let currentX, currentY, initialX, initialY;

        const calcPopup = document.getElementById('calculatorPopup');
        const calcHeader = document.getElementById('calcHeader');

        calcHeader.addEventListener('mousedown', (e) => {
            if (e.target.classList.contains('close-calc') || e.target.classList.contains('mode-btn')) return;
            initialX = e.clientX - calcPopup.offsetLeft;
            initialY = e.clientY - calcPopup.offsetTop;
            isDragging = true;
        });

        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                e.preventDefault();
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
                calcPopup.style.left = currentX + 'px';
                calcPopup.style.top = currentY + 'px';
            }
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        calcHeader.addEventListener('touchstart', (e) => {
            if (e.target.classList.contains('close-calc') || e.target.classList.contains('mode-btn')) return;
            initialX = e.touches[0].clientX - calcPopup.offsetLeft;
            initialY = e.touches[0].clientY - calcPopup.offsetTop;
            isDragging = true;
        });

        document.addEventListener('touchmove', (e) => {
            if (isDragging) {
                e.preventDefault();
                currentX = e.touches[0].clientX - initialX;
                currentY = e.touches[0].clientY - initialY;
                calcPopup.style.left = currentX + 'px';
                calcPopup.style.top = currentY + 'px';
            }
        });

        document.addEventListener('touchend', () => {
            isDragging = false;
        });

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            renderLessons();
            loadScoresFromServer(); // โหลดคะแนนเมื่อหน้าเว็บโหลดเสร็จ
        });
    </script>
</body>
</html>
