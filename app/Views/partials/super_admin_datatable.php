<?php
$tableId            = $tableId ?? 'datatable';
$searchInputId      = $searchInputId ?? '';
$statusFilterId     = $statusFilterId ?? '';
$pageLength         = isset($pageLength) ? (int) $pageLength : 10;
$orderColumn        = isset($orderColumn) ? (int) $orderColumn : 1;
$orderDir           = $orderDir ?? 'asc';
$nonSortableColumns = $nonSortableColumns ?? [0];
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
        return;
    }

    var tableSelector = '#<?= esc($tableId) ?>';
    var $table = $(tableSelector);

    if (!$table.length) {
        return;
    }

    var dt = $table.DataTable({
        pageLength: <?= (int) $pageLength ?>,
        order: [[<?= (int) $orderColumn ?>, '<?= esc($orderDir) ?>']],
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= esc(lang('app.all')) ?>"]],
        columnDefs: [
            {
                targets: <?= json_encode(array_values($nonSortableColumns)) ?>,
                orderable: false
            }
        ],
        language: {
            search: "<?= esc(lang('app.search')) ?>",
            lengthMenu: "<?= esc(lang('app.show')) ?> _MENU_",
            info: "<?= esc(lang('app.showing')) ?> _START_ <?= esc(lang('app.to')) ?> _END_ <?= esc(lang('app.of')) ?> _TOTAL_ <?= esc(lang('app.items')) ?>",
            infoEmpty: "<?= esc(lang('app.no_data')) ?>",
            zeroRecords: "<?= esc(lang('app.no_data')) ?>",
            emptyTable: "<?= esc(lang('app.no_data')) ?>",
            paginate: {
                first: "<?= esc(lang('app.first')) ?>",
                last: "<?= esc(lang('app.last')) ?>",
                next: "<?= esc(lang('app.next')) ?>",
                previous: "<?= esc(lang('app.previous')) ?>"
            }
        },
        drawCallback: function () {
            var api = this.api();
            var startIndex = api.page.info().start;

            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = startIndex + i + 1;
            });
        }
    });

    <?php if ($searchInputId !== ''): ?>
    var $searchInput = $('#<?= esc($searchInputId) ?>');
    if ($searchInput.length) {
        $searchInput.on('keyup change', function () {
            dt.search(this.value).draw();
        });
    }
    <?php endif; ?>

    <?php if ($statusFilterId !== ''): ?>
    var $statusFilter = $('#<?= esc($statusFilterId) ?>');
    if ($statusFilter.length) {
        $statusFilter.on('change', function () {
            dt.column($(this).data('column')).search(this.value).draw();
        });
    }
    <?php endif; ?>
});
</script>