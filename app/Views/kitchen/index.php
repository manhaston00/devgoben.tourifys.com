<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <h3 class="mb-0">หน้าครัว</h3>
    <div class="d-flex gap-2">
        <select id="station-filter" class="form-select">
            <option value="">ทุกสถานี</option>
            <option value="hot_kitchen">ครัวร้อน</option>
            <option value="fry_kitchen">ของทอด</option>
            <option value="drink_bar">เครื่องดื่ม</option>
        </select>
        <button class="btn btn-dark" onclick="loadKitchenOrders()">รีเฟรช</button>
    </div>
</div>

<div class="row g-3" id="kitchen-list"></div>

<script>
function loadKitchenOrders() {
    const station = $('#station-filter').val();

    $.get('<?= site_url('kitchen/list-json') ?>', { station: station }, function(res) {
        if (!res.status) return;

        let html = '';
        if (res.data.length === 0) {
            html = '<div class="col-12"><div class="alert alert-light border">ยังไม่มีรายการในครัว</div></div>';
        }

        res.data.forEach(function(item) {
            let badgeClass = 'bg-secondary';
            if (item.send_kitchen_status === 'sent') badgeClass = 'bg-primary';
            if (item.send_kitchen_status === 'accepted') badgeClass = 'bg-info text-dark';
            if (item.send_kitchen_status === 'cooking') badgeClass = 'bg-warning text-dark';
            if (item.send_kitchen_status === 'done') badgeClass = 'bg-success';

            html += `
                <div class="col-md-6 col-xl-4">
                    <div class="card kitchen-card border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold fs-5">${item.name_th}</div>
                                    <div class="text-muted small">โต๊ะ ${item.table_name} | บิล ${item.order_no}</div>
                                    <div class="text-muted small">พนักงาน: ${item.fullname}</div>
                                </div>
                                <span class="badge ${badgeClass} badge-status">${item.send_kitchen_status}</span>
                            </div>

                            <div class="mb-2">จำนวน: <strong>${item.qty}</strong></div>
                            <div class="mb-2">หมายเหตุ: ${item.note ?? '-'}</div>
                            <div class="mb-3">สถานี: ${item.kitchen_station}</div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-update-status" data-id="${item.id}" data-status="accepted">รับทำ</button>
                                <button class="btn btn-outline-warning btn-update-status" data-id="${item.id}" data-status="cooking">กำลังทำ</button>
                                <button class="btn btn-outline-success btn-update-status" data-id="${item.id}" data-status="done">เสร็จแล้ว</button>
                                <button class="btn btn-outline-dark btn-update-status" data-id="${item.id}" data-status="served">เสิร์ฟแล้ว</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#kitchen-list').html(html);
    }, 'json');
}

$(document).on('click', '.btn-update-status', function() {
    const itemId = $(this).data('id');
    const status = $(this).data('status');

    $.post('<?= site_url('kitchen/update-status') ?>', {
        item_id: itemId,
        status: status
    }, function(res) {
        if (res.status) {
            loadKitchenOrders();
        } else {
            alert(res.message);
        }
    }, 'json');
});

$('#station-filter').on('change', function() {
    loadKitchenOrders();
});

loadKitchenOrders();
setInterval(loadKitchenOrders, 3000);
</script>

<?= $this->endSection() ?>