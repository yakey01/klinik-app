<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pegawai Minimalist - {{ $card->employee_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
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
            background: {{ $template['colors']['background'] }};
            position: relative;
            border: 1px solid #E5E5E5;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .card-strip {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4mm;
            background: linear-gradient(90deg, {{ $template['colors']['primary'] }}, {{ $template['colors']['secondary'] }});
        }
        
        .card-content {
            display: flex;
            padding: 6mm 4mm 4mm 4mm;
            height: calc(54mm - 4mm);
        }
        
        .photo-section {
            width: 18mm;
            margin-right: 4mm;
            text-align: center;
        }
        
        .employee-photo {
            width: 16mm;
            height: 20mm;
            border-radius: 1px;
            border: 1px solid {{ $template['colors']['primary'] }};
            object-fit: cover;
            background: #FAFAFA;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20pt;
            color: {{ $template['colors']['primary'] }};
            font-weight: 300;
        }
        
        .info-section {
            flex: 1;
        }
        
        .company-name {
            font-size: 8pt;
            color: {{ $template['colors']['secondary'] }};
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2mm;
            font-weight: 400;
        }
        
        .employee-name {
            font-size: 11pt;
            font-weight: 600;
            color: {{ $template['colors']['text'] }};
            margin-bottom: 1mm;
            line-height: 1.2;
        }
        
        .employee-id {
            font-size: 7pt;
            color: {{ $template['colors']['secondary'] }};
            margin-bottom: 3mm;
            font-family: 'Courier New', monospace;
            font-weight: 400;
        }
        
        .job-info {
            margin-bottom: 2mm;
        }
        
        .position {
            font-size: 8pt;
            font-weight: 500;
            color: {{ $template['colors']['primary'] }};
            margin-bottom: 0.5mm;
        }
        
        .department {
            font-size: 7pt;
            color: {{ $template['colors']['secondary'] }};
            font-weight: 300;
        }
        
        .meta-info {
            font-size: 6pt;
            color: {{ $template['colors']['secondary'] }};
            line-height: 1.4;
            margin-top: 2mm;
        }
        
        .card-footer {
            position: absolute;
            bottom: 1mm;
            left: 4mm;
            right: 4mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 5pt;
            color: {{ $template['colors']['secondary'] }};
            border-top: 1px solid #F0F0F0;
            padding-top: 1mm;
        }
        
        .card-number {
            font-family: 'Courier New', monospace;
            font-weight: 400;
        }
        
        .valid-date {
            font-weight: 300;
        }
        
        .qr-placeholder {
            position: absolute;
            bottom: 4mm;
            right: 4mm;
            width: 6mm;
            height: 6mm;
            border: 1px solid {{ $template['colors']['primary'] }};
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4pt;
            color: {{ $template['colors']['primary'] }};
        }
        
        .accent-dot {
            position: absolute;
            top: 8mm;
            right: 4mm;
            width: 2mm;
            height: 2mm;
            background: {{ $template['colors']['accent'] }};
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Top Strip -->
        <div class="card-strip"></div>
        
        <!-- Accent Dot -->
        <div class="accent-dot"></div>
        
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
                <div class="company-name">DOKTERKU CLINIC</div>
                
                <div class="employee-name">{{ $card->employee_name }}</div>
                <div class="employee-id">{{ $card->employee_id }}</div>
                
                <div class="job-info">
                    <div class="position">{{ $card->position }}</div>
                    <div class="department">{{ $card->department }}</div>
                </div>
                
                <div class="meta-info">
                    @if($card->role_name)
                    <div>{{ $card->role_name }}</div>
                    @endif
                    
                    @if($card->join_date)
                    <div>Joined {{ $card->join_date->format('M Y') }}</div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- QR Code Placeholder -->
        <div class="qr-placeholder">QR</div>
        
        <!-- Footer -->
        <div class="card-footer">
            <div class="card-number">{{ $card->card_number }}</div>
            <div class="valid-date">
                @if($card->valid_until)
                    {{ $card->valid_until->format('m/Y') }}
                @else
                    ∞
                @endif
            </div>
        </div>
    </div>
</body>
</html>