<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Laporan')</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #212529;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #0c8f82;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .header .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #0c8f82;
            text-transform: uppercase;
        }
        .header .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #1a202c;
            margin-top: 4px;
        }
        .header .meta {
            text-align: right;
            font-size: 9px;
            color: #6c757d;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-data th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .table-data td {
            padding: 5px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            vertical-align: top;
        }
        .table-data tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-title">PT. Perseverance HRMS</div>
                    <div class="report-title">@yield('title')</div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 3px;">@yield('subtitle')</div>
                </td>
                <td class="meta">
                    <div>Dicetak pada: {{ date('d/m/Y H:i') }}</div>
                    <div>Oleh: {{ auth()->user()->name ?? 'System' }}</div>
                    <div>Total Data: {{ count($records ?? []) }} baris</div>
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem HRMS Perseverance - Halaman dicetak untuk keperluan arsip internal.
    </div>
</body>
</html>
