<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokterku - Content Test</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            max-width: 800px;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        .logo {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .feature {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .cta-button {
            display: inline-block;
            padding: 1rem 2rem;
            margin-top: 2rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🏥</div>
        <h1 class="title">Dokterku</h1>
        <p class="subtitle">Healthcare Management System</p>
        <p>Sistem manajemen klinik yang komprehensif dengan teknologi terdepan untuk melayani kebutuhan administrasi dan operasional fasilitas kesehatan modern.</p>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">👨‍💼</div>
                <h3>Admin Panel</h3>
                <p>Manajemen sistem lengkap</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Dashboard Analytics</h3>
                <p>KPI dan monitoring real-time</p>
            </div>
            <div class="feature">
                <div class="feature-icon">💰</div>
                <h3>Financial Management</h3>
                <p>Tracking keuangan terintegrasi</p>
            </div>
            <div class="feature">
                <div class="feature-icon">👩‍⚕️</div>
                <h3>Staff Management</h3>
                <p>Manajemen pegawai & jadwal</p>
            </div>
        </div>
        
        <a href="/admin" class="cta-button">Akses Dashboard</a>
    </div>
</body>
</html>