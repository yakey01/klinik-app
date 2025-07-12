<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pegawai Modern - {{ $card->employee_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            width: 86mm;
            height: 54mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .card {
            width: 86mm;
            height: 54mm;
            background: linear-gradient(45deg, {{ $template['colors']['primary'] }} 0%, {{ $template['colors']['secondary'] }} 50%, {{ $template['colors']['primary'] }} 100%);
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            margin: 2mm;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .card-header {
            background: linear-gradient(90deg, {{ $template['colors']['primary'] }}, {{ $template['colors']['accent'] }});
            padding: 2mm 3mm;
            color: white;
            border-radius: 8px 8px 0 0;
        }
        
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .card-subtitle {
            font-size: 7pt;
            text-align: center;
            opacity: 0.9;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .card-content {
            display: flex;
            padding: 3mm;
            height: calc(54mm - 8mm - 8mm);
            position: relative;
        }
        
        .photo-section {
            width: 22mm;
            margin-right: 3mm;
            text-align: center;
        }
        
        .employee-photo {
            width: 20mm;
            height: 25mm;
            border-radius: 8px;
            border: 3px solid {{ $template['colors']['primary'] }};
            object-fit: cover;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28pt;
            color: {{ $template['colors']['primary'] }};
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .info-section {
            flex: 1;
            position: relative;
        }
        
        .employee-name {
            font-size: 12pt;
            font-weight: bold;
            color: {{ $template['colors']['text'] }};
            margin-bottom: 2mm;
            line-height: 1.2;
            background: linear-gradient(90deg, {{ $template['colors']['primary'] }}, {{ $template['colors']['accent'] }});
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .employee-id {
            font-size: 8pt;
            color: white;
            background: linear-gradient(90deg, {{ $template['colors']['accent'] }}, {{ $template['colors']['primary'] }});
            padding: 1.5mm 3mm;
            border-radius: 15px;
            display: inline-block;
            margin-bottom: 2mm;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .job-info {
            background: rgba({{ hexdec(substr($template['colors']['primary'], 1, 2)) }}, {{ hexdec(substr($template['colors']['primary'], 3, 2)) }}, {{ hexdec(substr($template['colors']['primary'], 5, 2)) }}, 0.1);
            padding: 2mm;
            border-radius: 6px;
            margin-bottom: 1mm;
            border-left: 3px solid {{ $template['colors']['primary'] }};
        }
        
        .position {
            font-size: 9pt;
            font-weight: bold;
            color: {{ $template['colors']['primary'] }};
            margin-bottom: 0.5mm;
        }
        
        .department {
            font-size: 7pt;
            color: {{ $template['colors']['text'] }};
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }
        
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(90deg, {{ $template['colors']['primary'] }}, {{ $template['colors']['secondary'] }});
            color: white;
            padding: 1.5mm 3mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6pt;
            border-radius: 0 0 8px 8px;
        }
        
        .card-number {
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .valid-date {
            font-size: 6pt;
            opacity: 0.9;
        }
        
        .floating-elements {
            position: absolute;
            top: 2mm;
            right: 2mm;
            width: 8mm;
            height: 8mm;
        }
        
        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: {{ $template['colors']['accent'] }};
            opacity: 0.2;
        }
        
        .circle-1 {
            width: 6mm;
            height: 6mm;
            top: 0;
            right: 0;
            animation: float 3s ease-in-out infinite;
        }
        
        .circle-2 {
            width: 3mm;
            height: 3mm;
            bottom: 0;
            left: 0;
            background: {{ $template['colors']['primary'] }};
            animation: float 3s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
        }
        
        .tech-pattern {
            position: absolute;
            bottom: 2mm;
            right: 2mm;
            width: 12mm;
            height: 6mm;
            background: 
                linear-gradient(90deg, transparent 49%, {{ $template['colors']['primary'] }}20 50%, transparent 51%),
                linear-gradient(0deg, transparent 49%, {{ $template['colors']['primary'] }}20 50%, transparent 51%);
            background-size: 2mm 2mm;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-overlay">
            <!-- Floating Elements -->
            <div class="floating-elements">
                <div class="floating-circle circle-1"></div>
                <div class="floating-circle circle-2"></div>
            </div>
            
            <!-- Header -->
            <div class="card-header">
                <div class="company-name">DOKTERKU CLINIC</div>
                <div class="card-subtitle">Digital ID Card</div>
            </div>
            
            <!-- Main Content -->
            <div class="card-content">
                <!-- Photo Section -->
                <div class="photo-section">
                    @if($card->photo_path && file_exists(storage_path('app/public/' . $card->photo_path)))
                        <img src="{{ storage_path('app/public/' . $card->photo_path) }}" alt="Photo" class="employee-photo">
                    @else
                        <div class="employee-photo">
                            {{ strtoupper(substr($card->employee_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                
                <!-- Information Section -->
                <div class="info-section">
                    <div class="employee-name">{{ $card->employee_name }}</div>
                    <div class="employee-id">{{ $card->employee_id }}</div>
                    
                    <div class="job-info">
                        <div class="position">{{ $card->position }}</div>
                        <div class="department">{{ $card->department }}</div>
                    </div>
                    
                    @if($card->role_name)
                    <div style="font-size: 7pt; color: {{ $template['colors']['accent'] }}; margin-top: 1mm; font-weight: bold;">
                        🔹 {{ $card->role_name }}
                    </div>
                    @endif
                    
                    @if($card->join_date)
                    <div style="font-size: 6pt; color: {{ $template['colors']['text'] }}; margin-top: 1mm; opacity: 0.7;">
                        Member since {{ $card->join_date->format('M Y') }}
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Tech Pattern -->
            <div class="tech-pattern"></div>
            
            <!-- Footer -->
            <div class="card-footer">
                <div class="card-number">{{ $card->card_number }}</div>
                <div class="valid-date">
                    @if($card->valid_until)
                        Expires {{ $card->valid_until->format('m/Y') }}
                    @else
                        Permanent
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>