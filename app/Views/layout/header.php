<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Koben POS') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .table-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }
        .status-available { background: #d1fae5; }
        .status-occupied { background: #fee2e2; }
        .status-waiting_payment { background: #fef3c7; }
        .status-disabled { background: #e5e7eb; }
        .menu-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
            height: 100%;
        }
        .sticky-bottom-cart {
            position: sticky;
            bottom: 0;
            z-index: 50;
        }
        .kitchen-card {
            border-radius: 18px;
            box-shadow: 0 8px 22px rgba(0,0,0,.08);
        }
        .badge-status {
            font-size: 12px;
            padding: 6px 10px;
        }
        @media (max-width: 768px) {
            .mobile-bottom-space {
                padding-bottom: 110px;
            }
        }
    </style>
</head>
<body>