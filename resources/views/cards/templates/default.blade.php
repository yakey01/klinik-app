<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pegawai - {{ $card->employee_name }}</title>
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
            background: linear-gradient(135deg, {{ $template['colors']['primary'] }} 0%, {{ $template['colors']['secondary'] }} 100%);
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 4mm 3mm 2mm 3mm;
            border-bottom: 2px solid {{ $template['colors']['accent'] }};
        }
        
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            color: {{ $template['colors']['text'] }};
            text-align: center;
            margin-bottom: 1mm;
        }
        
        .card-title {
            font-size: 8pt;
            color: {{ $template['colors']['secondary'] }};
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .card-content {
            display: flex;
            padding: 3mm;
            height: calc(54mm - 15mm);
            background: rgba(255, 255, 255, 0.98);
        }
        
        .photo-section {
            width: 20mm;
            margin-right: 3mm;
            text-align: center;
        }
        
        .employee-photo {
            width: 18mm;
            height: 22mm;
            border-radius: 4px;
            border: 2px solid {{ $template['colors']['primary'] }};
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24pt;
            color: {{ $template['colors']['primary'] }};
        }
        
        .info-section {
            flex: 1;
            padding-left: 2mm;
        }
        
        .employee-name {
            font-size: 11pt;
            font-weight: bold;
            color: {{ $template['colors']['text'] }};
            margin-bottom: 2mm;
            line-height: 1.2;
        }
        
        .employee-id {
            font-size: 8pt;
            color: {{ $template['colors']['secondary'] }};
            background: {{ $template['colors']['accent'] }}20;
            padding: 1mm 2mm;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 2mm;
            font-weight: bold;
        }
        
        .job-info {
            margin-bottom: 1mm;
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
            letter-spacing: 0.5px;
        }
        
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: {{ $template['colors']['primary'] }};
            color: white;
            padding: 1mm 3mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6pt;
        }
        
        .card-number {
            font-weight: bold;
        }
        
        .valid-date {
            font-size: 6pt;
        }
        
        .logo-watermark {
            position: absolute;
            top: 50%;
            right: 2mm;
            transform: translateY(-50%);
            opacity: 0.1;
            font-size: 36pt;
            color: {{ $template['colors']['primary'] }};
            z-index: 1;
        }
        
        .qr-code {
            position: absolute;
            bottom: 8mm;
            right: 2mm;
            width: 8mm;
            height: 8mm;
            background: white;
            border: 1px solid {{ $template['colors']['primary'] }};
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5pt;
            color: {{ $template['colors']['text'] }};
        }
        
        .security-features {
            position: absolute;
            top: 2mm;
            right: 2mm;
            width: 4mm;
            height: 4mm;
            background: radial-gradient(circle, {{ $template['colors']['accent'] }}40, transparent);
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Security Features -->
        <div class="security-features"></div>
        
        <!-- Logo Watermark -->
        <div class="logo-watermark">🏥</div>
        
        <!-- Header -->
        <div class="card-header">
            <div class="company-name">DOKTERKU CLINIC</div>
            <div class="card-title">Employee ID Card</div>
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
                <div style="font-size: 7pt; color: {{ $template['colors']['secondary'] }}; margin-top: 1mm;">
                    {{ $card->role_name }}
                </div>
                @endif
                
                @if($card->join_date)
                <div style="font-size: 6pt; color: {{ $template['colors']['text'] }}; margin-top: 1mm;">
                    Bergabung: {{ $card->join_date->format('d M Y') }}
                </div>
                @endif
            </div>
        </div>
        
        <!-- QR Code -->
        <div class="qr-code">
            QR
        </div>
        
        <!-- Footer -->
        <div class="card-footer">
            <div class="card-number">{{ $card->card_number }}</div>
            <div class="valid-date">
                @if($card->valid_until)
                    Valid: {{ $card->valid_until->format('m/Y') }}
                @else
                    No Expiry
                @endif
            </div>
        </div>
    </div>
</body>
</html>