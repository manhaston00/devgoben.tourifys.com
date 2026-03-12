<?php

namespace App\Models;

class QuickNoteModel extends TenantScopedModel
{
    protected $table            = 'quick_notes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'note_name',
        'note_name_th',
        'note_name_en',
        'sort_order',
        'status',
    ];
}