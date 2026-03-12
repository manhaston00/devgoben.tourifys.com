<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS ร้านข้าวต้ม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f5f7fb;">
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height:100vh;">
        <div class="col-lg-4">
            <div class="card border-0 shadow rounded-4">
                <div class="card-body p-4">
                    <h3 class="mb-3 text-center">POS ร้านข้าวต้ม</h3>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('login') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= esc(old('username')) ?>" placeholder="เช่น kob_admin" required>
                            <div class="form-text">
                                ใช้ username สำหรับ login จริง เช่น <strong>kob_admin</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button class="btn btn-dark w-100">Login</button>
                    </form>

                    <div class="mt-3 text-muted small text-center">
                        ตัวอย่างชื่อเข้าใช้งาน: kob_admin / kob_cashier1
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>