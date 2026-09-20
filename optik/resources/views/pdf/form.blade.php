<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $exam->title }} - Optik Form</title>
    <style>
        @page {
            size: 210mm 297mm;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
            position: relative;
            background: #fff;
            color: #000;
        }

        /* 4 Corner Markers: Exact 9x9mm, centers at (0.04, 0.04), (0.96, 0.04), (0.04, 0.96), (0.96, 0.96) */
        .corner-marker {
            position: absolute;
            width: 9mm;
            height: 9mm;
            background: #000;
        }
        .marker-tl { left: 3.9mm; top: 7.38mm; }
        .marker-tr { left: 197.1mm; top: 7.38mm; }
        .marker-bl { left: 3.9mm; top: 280.62mm; }
        .marker-br { left: 197.1mm; top: 280.62mm; }

        /* Header Zone: (0.06, 0.04) -> (0.72, 0.12) => X: 12.6mm - 151.2mm, Y: 11.88mm - 35.64mm */
        .header-zone {
            position: absolute;
            left: 12.6mm;
            top: 11.88mm;
            width: 138.6mm;
            height: 23.76mm;
            overflow: hidden;
        }
        .exam-title {
            font-size: 11pt;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }
        .exam-meta {
            font-size: 8pt;
            color: #333;
            margin-bottom: 2mm;
        }
        .student-info-row {
            font-size: 8pt;
            line-height: 1.4;
        }

        /* QR Zone: (0.74, 0.04) -> (0.94, 0.12) => X: 155.4mm - 197.4mm, Y: 11.88mm - 35.64mm */
        .qr-zone {
            position: absolute;
            left: 155.4mm;
            top: 11.88mm;
            width: 42mm;
            height: 23.76mm;
            text-align: center;
        }
        .qr-img {
            width: 23.76mm;
            height: 23.76mm;
            display: inline-block;
        }

        /* Student No Zone: (0.06, 0.13) -> (0.58, 0.31) => X: 12.6mm - 121.8mm, Y: 38.61mm - 92.07mm */
        .student-zone {
            position: absolute;
            left: 12.6mm;
            top: 38.61mm;
            width: 109.2mm;
            height: 53.46mm;
            border: 0.3mm solid #999;
            padding: 1mm 1.5mm;
        }
        .zone-title {
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            margin-bottom: 1mm;
            color: #222;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .student-box-cell {
            text-align: center;
            height: 5mm;
            border: 0.3mm solid #444;
            font-size: 7.5pt;
            font-weight: bold;
            background: #fafafa;
        }
        .student-bubble-cell {
            text-align: center;
            height: 3.8mm;
            padding: 0;
            vertical-align: middle;
        }
        .bubble-circle {
            width: 3mm;
            height: 3mm;
            border: 0.35mm solid #000;
            border-radius: 50%;
            display: inline-block;
            line-height: 2.8mm;
            text-align: center;
            font-size: 5.5pt;
            color: #666;
            vertical-align: middle;
        }

        /* Booklet Zone: (0.62, 0.13) -> (0.94, 0.22) => X: 130.2mm - 197.4mm, Y: 38.61mm - 65.34mm */
        .booklet-zone {
            position: absolute;
            left: 130.2mm;
            top: 38.61mm;
            width: 67.2mm;
            height: 26.73mm;
            border: 0.3mm solid #999;
            padding: 1.5mm;
            text-align: center;
        }
        .booklet-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
        }
        .booklet-cell {
            text-align: center;
            vertical-align: middle;
        }
        .booklet-bubble-circle {
            width: 4.5mm;
            height: 4.5mm;
            border: 0.4mm solid #000;
            border-radius: 50%;
            display: inline-block;
            line-height: 4.3mm;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            color: #333;
            vertical-align: middle;
        }
        .booklet-bubble-circle.active-booklet {
            background: #000;
            color: #fff;
        }

        /* Instruction Box: X: 130.2mm - 197.4mm, Y: 68.31mm - 92.07mm (height: 23.76mm) */
        .instruction-zone {
            position: absolute;
            left: 130.2mm;
            top: 68.31mm;
            width: 67.2mm;
            height: 23.76mm;
            border: 0.3mm dashed #888;
            padding: 2mm;
            font-size: 6.5pt;
            line-height: 1.35;
            color: #333;
            background: #fdfdfd;
        }
        .instruction-zone strong {
            color: #000;
        }

        /* Questions Zone: (0.06, 0.33) -> (0.94, 0.94) => X: 12.6mm - 197.4mm, Y: 98.01mm - 279.18mm */
        .questions-zone {
            position: absolute;
            left: 12.6mm;
            top: 98.01mm;
            width: 184.8mm;
            height: 181.17mm;
            border: 0.3mm solid #999;
            padding: 1mm 1.5mm;
        }
        .questions-grid-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .question-col-td {
            vertical-align: top;
            padding: 0 1.5mm;
            border-right: 0.2mm dashed #bbb;
        }
        .question-col-td:last-child {
            border-right: none;
        }
        .question-col-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .question-row {
            height: {{ round(177 / max(1, $rowsPerColumn), 2) }}mm;
        }
        .qnum-cell {
            width: 18%;
            font-size: 6.8pt;
            font-weight: bold;
            text-align: right;
            padding-right: 1.2mm;
            vertical-align: middle;
            color: #111;
        }
        .qbubble-cell {
            width: {{ round(82 / max(1, count($options)), 2) }}%;
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }
        .qbubble-circle {
            width: 3.2mm;
            height: 3.2mm;
            border: 0.35mm solid #000;
            border-radius: 50%;
            display: inline-block;
            line-height: 3mm;
            text-align: center;
            font-size: 6pt;
            color: #555;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- 4 Corner Markers -->
    <div class="corner-marker marker-tl"></div>
    <div class="corner-marker marker-tr"></div>
    <div class="corner-marker marker-bl"></div>
    <div class="corner-marker marker-br"></div>

    <!-- Header Zone -->
    <div class="header-zone">
        <div class="exam-title">{{ $exam->title }}</div>
        <div class="exam-meta">
            {{ $exam->course ? $exam->course.' | ' : '' }}Kitapçık: <strong>{{ $booklet }}</strong> | Form v{{ $version }}
        </div>
        <div class="student-info-row">
            <strong>Adı Soyadı:</strong> ............................................................................
            &nbsp; <strong>İmza:</strong> .................
        </div>
    </div>

    <!-- QR Code Zone -->
    <div class="qr-zone">
        <img class="qr-img" src="data:image/png;base64,{{ $qrBase64 }}" alt="QR">
    </div>

    <!-- Student No Zone (8 digits x 10 rows) -->
    <div class="student-zone">
        <div class="zone-title">ÖĞRENCİ NUMARASI</div>
        <table class="student-table">
            <thead>
                <tr>
                    @for ($d = 1; $d <= 8; $d++)
                        <td class="student-box-cell"></td>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @for ($digit = 0; $digit <= 9; $digit++)
                    <tr>
                        @for ($d = 1; $d <= 8; $d++)
                            <td class="student-bubble-cell">
                                <span class="bubble-circle">{{ $digit }}</span>
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <!-- Booklet Zone -->
    <div class="booklet-zone">
        <div class="zone-title">KİTAPÇIK TÜRÜ</div>
        <table class="booklet-table">
            <tr>
                @foreach (['A', 'B', 'C', 'D'] as $choice)
                    <td class="booklet-cell">
                        <span class="booklet-bubble-circle {{ $choice === $booklet ? 'active-booklet' : '' }}">
                            {{ $choice }}
                        </span>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <!-- Instruction Zone -->
    <div class="instruction-zone">
        <strong>UYARILAR:</strong><br>
        • Cevaplarınızı yumuşak kurşun kalemle doldurunuz.<br>
        • İlgili kutucuğun/dairenin dışına taşırmayınız.<br>
        • Değiştirmek istediğiniz cevabı iz bırakmadan siliniz.
    </div>

    <!-- Dynamic Questions Zone -->
    <div class="questions-zone">
        <table class="questions-grid-table">
            <tr>
                @for ($col = 0; $col < $columns; $col++)
                    <td class="question-col-td" style="width: {{ round(100 / $columns, 2) }}%;">
                        <table class="question-col-table">
                            @for ($row = 0; $row < $rowsPerColumn; $row++)
                                @php($q = $col * $rowsPerColumn + $row + 1)
                                @if ($q <= $exam->question_count)
                                    <tr class="question-row">
                                        <td class="qnum-cell">{{ $q }}</td>
                                        @foreach ($options as $opt)
                                            <td class="qbubble-cell">
                                                <span class="qbubble-circle">{{ $opt }}</span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endfor
                        </table>
                    </td>
                @endfor
            </tr>
        </table>
    </div>
</body>
</html>