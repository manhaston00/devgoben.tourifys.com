<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationCustomerModel extends Model
{
    protected $table            = 'reservation_customers';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'customer_name',
        'phone',
        'line_id',
        'notes',
        'tags',
        'total_visits',
        'no_show_count',
    ];

    public function findByPhone(?string $phone): ?array
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        return $this->where('phone', $phone)->first();
    }

    public function upsertCustomer(string $name, ?string $phone = null, ?string $lineId = null): int
    {
        $customer = $this->findByPhone($phone);

        if ($customer) {
            $this->update((int) $customer['id'], [
                'customer_name' => $name,
                'line_id'       => $lineId,
            ]);
            return (int) $customer['id'];
        }

        $this->insert([
            'customer_name' => $name,
            'phone'         => $phone,
            'line_id'       => $lineId,
        ]);

        return (int) $this->getInsertID();
    }
}