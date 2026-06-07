<?php

namespace App\Services\SuperAdmin;

use App\Models\Clinic;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClinicService
{
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $perPage = max(10, min($perPage, 50));
        $phoneSearch = PhoneNumber::normalize($search);

        return Clinic::query()
            ->when($search !== '', function ($query) use ($search, $phoneSearch): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
                if ($phoneSearch !== '' && $phoneSearch !== $search) {
                    $query->orWhere('phone', 'like', "%{$phoneSearch}%");
                }
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Clinic
    {
        return Clinic::create([
            'name' => $data['name'],
            'document' => $data['document'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'active' => true,
        ]);
    }

    public function update(Clinic $clinic, array $data): Clinic
    {
        $clinic->fill([
            'name' => $data['name'] ?? $clinic->name,
            'document' => array_key_exists('document', $data) ? $data['document'] : $clinic->document,
            'email' => array_key_exists('email', $data) ? $data['email'] : $clinic->email,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $clinic->phone,
        ]);
        $clinic->save();

        return $clinic;
    }

    public function activate(Clinic $clinic): Clinic
    {
        $clinic->update(['active' => true]);

        return $clinic;
    }

    public function deactivate(Clinic $clinic): Clinic
    {
        $clinic->update(['active' => false]);

        return $clinic;
    }
}
