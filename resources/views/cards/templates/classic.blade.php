<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pegawai Classic - {{ $card->employee_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
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
            border: 3px solid {{ $template['colors']['primary'] }};
            border-radius: 6px;
            overflow: hidden;
        }
        
        .card-header {
            background: {{ $template['colors']['primary'] }};
            color: white;
            padding: 3mm;
            text-align: center;
            border-bottom: 2px solid {{ $template['colors']['secondary'] }};
        }
        
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .card-title {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        
        .card-content {
            display: flex;
            padding: 3mm;
            height: calc(54mm - 12mm);
            background: white;
        }
        
        .photo-section {
            width: 22mm;
            margin-right: 3mm;
            text-align: center;
        }
        
        .employee-photo {
            width: 20mm;
            height: 24mm;
            border: 2px solid {{ $template['colors']['primary'] }};
            border-radius: 4px;
            object-fit: cover;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24pt;
            color: {{ $template['colors']['primary'] }};
            font-weight: bold;
        }
        
        .info-section {
            flex: 1;
            padding-left: 2mm;
        }
        
        .employee-name {
            font-size: 10pt;
            font-weight: bold;
            color: {{ $template['colors']['text'] }};
            margin-bottom: 2mm;
            line-height: 1.2;
            text-transform: uppercase;
        }
        
        .employee-id {
            font-size: 8pt;
            color: {{ $template['colors']['secondary'] }};
            background: {{ $template['colors']['background'] }};
            border: 1px solid {{ $template['colors']['primary'] }};
            padding: 1mm 2mm;
            border-radius: 2px;
            display: inline-block;
            margin-bottom: 2mm;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .job-info {
            border-left: 3px solid {{ $template['colors']['accent'] }};
            padding-left: 2mm;
            margin-bottom: 2mm;
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
            font-style: italic;
        }
        
        .additional-info {
            font-size: 6pt;
            color: {{ $template['colors']['text'] }};
            line-height: 1.3;
        }
        
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: {{ $template['colors']['secondary'] }};
            color: white;
            padding: 1.5mm 3mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6pt;
            border-top: 1px solid {{ $template['colors']['accent'] }};
        }
        
        .card-number {
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .valid-date {
            font-size: 6pt;
            font-style: italic;
        }
        
        .ornament {
            position: absolute;
            top: 2mm;
            right: 2mm;
            width: 6mm;
            height: 6mm;
            border: 2px solid {{ $template['colors']['accent'] }};
            border-radius: 50%;
            background: rgba({{ hexdec(substr($template['colors']['accent'], 1, 2)) }}, {{ hexdec(substr($template['colors']['accent'], 3, 2)) }}, {{ hexdec(substr($template['colors']['accent'], 5, 2)) }}, 0.1);
        }
        
        .signature-line {
            position: absolute;
            bottom: 8mm;
            right: 3mm;
            width: 15mm;
            height: 1px;
            border-bottom: 1px solid {{ $template['colors']['text'] }};
        }
        
        .signature-text {
            position: absolute;
            bottom: 6mm;
            right: 3mm;
            font-size: 5pt;
            color: {{ $template['colors']['text'] }};
            text-align: center;
            width: 15mm;
        }
        
        .seal {
            position: absolute;
            top: 50%;
            right: 1mm;
            transform: translateY(-50%);
            width: 12mm;
            height: 12mm;
            border: 2px solid {{ $template['colors']['primary'] }};
            border-radius: 50%;
            opacity: 0.1;
            background: radial-gradient(circle, {{ $template['colors']['primary'] }}20, transparent);
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Ornament -->
        <div class="ornament"></div>
        
        <!-- Official Seal -->
        <div class="seal"></div>
        
        <!-- Header -->
        <div class="card-header">
            <div class="company-name">DOKTERKU CLINIC</div>
            <div class="card-title">Official Employee Identification</div>
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
                <div class="employee-id">ID: {{ $card->employee_id }}</div>
                
                <div class="job-info">
                    <div class="position">{{ $card->position }}</div>
                    <div class="department">{{ $card->department }} Department</div>
                </div>
                
                <div class="additional-info">
                    @if($card->role_name)
                    <div>Role: {{ $card->role_name }}</div>
                    @endif
                    
                    @if($card->join_date)
                    <div>Since: {{ $card->join_date->format('M Y') }}</div>
                    @endif
                    
                    <div>Issued: {{ $card->issued_date->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        
        <!-- Signature -->
        <div class="signature-line"></div>
        <div class="signature-text">Authorized</div>
        
        <!-- Footer -->
        <div class="card-footer">
            <div class="card-number">{{ $card->card_number }}</div>
            <div class="valid-date">
                @if($card->valid_until)
                    Valid until {{ $card->valid_until->format('M Y') }}
                @else
                    Permanent Card
                @endif
            </div>
        </div>
    </div>
</body>
</html>