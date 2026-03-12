<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>403 Forbidden</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background:#f5f6fa; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .box { background:#fff; padding:40px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,.08); text-align:center; max-width:420px; }
        h1 { margin:0 0 10px; font-size:48px; }
        p { color:#666; margin-bottom:20px; }
        a { display:inline-block; padding:12px 18px; background:#111827; color:#fff; text-decoration:none; border-radius:10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>403</h1>
        <p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>
        <a href="<?= site_url('dashboard') ?>">กลับไปหน้า Dashboard</a>
    </div>
</body>
</html>