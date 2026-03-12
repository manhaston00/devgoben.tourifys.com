<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="container py-5">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 720px;">
        <div class="card-body p-5 text-center">
            <h2 class="mb-3 text-danger">แพ็กเกจหมดอายุ</h2>
            <p class="text-muted mb-4">
                ระบบของคุณหมดอายุการใช้งานแล้ว กรุณาต่ออายุแพ็กเกจเพื่อกลับมาใช้งานต่อ
            </p>

            <div class="alert alert-warning">
                หากคุณเป็นผู้ดูแลระบบ กรุณาติดต่อเจ้าของระบบหรือทีมขายเพื่อเปิดใช้งานต่อ
            </div>

            <a href="<?= site_url('/') ?>" class="btn btn-primary">
                กลับหน้าหลัก
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>