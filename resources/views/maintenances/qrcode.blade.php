<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label QR - {{ $equipment->serial_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fca;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #e9ecef;
        }
        .print-area {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 350px;
            border: 2px solid #343a40;
        }
        .rs-header {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 12px;
            color: #212529;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        .equipment-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .equipment-details {
            font-size: 14px;
            color: #495057;
            margin-bottom: 15px;
        }
        .sn-box {
            background: #212529;
            color: #fff;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .qr-code {
            padding: 15px;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 8px;
            display: inline-block;
        }
        .footer-note {
            font-size: 11px;
            color: #6c757d;
            margin-top: 15px;
        }
        @media print {
            body { background: transparent; }
            .print-area { box-shadow: none; border: 1px solid #000; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="print-area">
        <div class="rs-header">
            RS UNIVERSITAS INDONESIA<br>
            <span style="font-size: 11px; font-weight: bold; color: #1F3BB3;">UNIT REKAM MEDIS & PASIEN JOURNEY</span>
        </div>
        
        <div class="equipment-name">{{ $equipment->merk }}</div>
        <div class="equipment-details">Diagnosis/Gejala: <b>{{ $equipment->type }}</b></div>
        
        <div class="sn-box">NO. RM: {{ $equipment->serial_number }}</div>

        <div class="qr-code">
            {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($url) !!}
        </div>

        <div class="footer-note">Pindai kode QR untuk melihat riwayat medis<br>dan rekam pelayanan pasien secara aman.</div>
    </div>

</body>
</html>
