<?php
/**
 * HiTune Music Distribution - Premium 404 Page
 */
$pageTitle = 'Page Not Found - HiTune Music Distribution';
http_response_code(404);
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .error-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 140px 40px;
        position: relative;
        overflow: hidden;
    }
    
    .error-page::before {
        content: '';
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(0, 183, 255, 0.2) 0%, transparent 60%);
        top: -200px;
        left: -200px;
        animation: glow1 8s ease-in-out infinite;
            pointer-events: none;
    }
    
    .error-page::after {
        content: '';
        position: absolute;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.15) 0%, transparent 60%);
        bottom: -150px;
        right: -150px;
        animation: glow2 10s ease-in-out infinite reverse;
            pointer-events: none;
    }
    
    @keyframes glow1 {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.3); opacity: 0.8; }
    }
    
    @keyframes glow2 {
        0%, 100% { transform: scale(1); opacity: 0.4; }
        50% { transform: scale(1.2); opacity: 0.7; }
    }
    
    .error-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 40px;
        padding: 80px 60px;
        position: relative;
        z-index: 1;
        animation: fadeInUp 0.8s ease-out;
        max-width: 600px;
    }
    
    .error-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            pointer-events: none;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .error-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 40px;
        font-size: 60px;
        color: white;
        box-shadow: 0 20px 50px rgba(0, 183, 255, 0.4);
        animation: iconFloat 3s ease-in-out infinite;
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    
    .error-code {
        font-size: clamp(100px, 15vw, 160px);
        font-weight: 800;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6, #ff6b9d);
        background-size: 200% 200%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1;
        margin-bottom: 20px;
        animation: gradientShift 5s ease infinite;
    }
    
    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .error-card h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .error-card p {
        font-size: 18px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 50px;
        line-height: 1.6;
    }
    
    .error-actions {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .btn-error {
        padding: 16px 35px;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-primary-error {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: white;
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
    }
    
    .btn-primary-error::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.6s;
            pointer-events: none;
    }
    
    .btn-primary-error:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
    }
    
    .btn-primary-error:hover::before {
        left: 100%;
    }
    
    .btn-outline-error {
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .btn-outline-error:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(0, 183, 255, 0.5);
        transform: translateY(-3px);
    }
    
    .search-box {
        margin-top: 40px;
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    
    .search-input {
        padding: 16px 24px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50px;
        color: white;
        font-size: 15px;
        width: 100%;
        max-width: 300px;
        outline: none;
        transition: all 0.3s;
    }
    
    .search-input:focus {
        border-color: rgba(0, 183, 255, 0.5);
        box-shadow: 0 0 25px rgba(0, 183, 255, 0.1);
    }
    
    .search-input::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }
    
    .search-btn {
        padding: 16px 28px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50px;
        color: white;
        cursor: pointer;
        font-size: 20px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .search-btn:hover {
        background: rgba(0, 183, 255, 0.2);
        border-color: rgba(0, 183, 255, 0.5);
    }
    
    @media (max-width: 600px) {
        .error-card {
            padding: 50px 30px;
            border-radius: 30px;
        }
        
        .error-icon {
            width: 100px;
            height: 100px;
            font-size: 50px;
        }
        
        .error-card h1 {
            font-size: 26px;
        }
        
        .search-box {
            flex-direction: column;
            align-items: center;
        }
        
        .search-input {
            max-width: 100%;
        }
    }
</style>

<div class="error-page">
    <div class="error-card">
        <div class="error-icon">
            <span class="mdi mdi-alert-circle-outline"></span>
        </div>
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>Oops! The page you're looking for seems to have taken a break. It might have been moved, deleted, or never existed.</p>
        <div class="error-actions">
            <a href="/index.php?q=home" class="btn-error btn-primary-error">
                <span class="mdi mdi-home"></span>
                Back to Home
            </a>
            <a href="/index.php?q=pricing" class="btn-error btn-outline-error">
                <span class="mdi mdi-rocket-launch"></span>
                Get Started
            </a>
        </div>
        <div class="search-box">
            <input type="text" class="search-input" placeholder="Search for what you need..." id="searchInput">
            <button class="search-btn" onclick="search()">
                <span class="mdi mdi-magnify"></span>
            </button>
        </div>
    </div>
</div>

<script>
    function search() {
        const query = document.getElementById('searchInput').value;
        if (query) {
            window.location.href = '/index.php?q=home';
        }
    }
    
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            search();
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
