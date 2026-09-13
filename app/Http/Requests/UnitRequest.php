<?php

namespace App\Http\Requests;

use App\Models\Unit;
use App\Support\TataLetakHero;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UnitRequest extends ModalFormRequest
{
    protected string $section = 'unit';

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'nama_unit' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            // Hanya dua tingkat: sub-unit wajib menunjuk ke unit induk (bukan sub-unit lain).
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('units', 'id')->whereNull('parent_id'),
                Rule::notIn(array_filter([$unit?->id])),
            ],
            'logo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
            'hapus_logo'  => ['nullable', 'boolean'],
            'tampil_hero' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $unit = $this->route('unit');

                if ($unit && $this->filled('parent_id') && $unit->children()->exists()) {
                    $validator->errors()->add(
                        'parent_id',
                        'Unit ini masih punya sub-unit, jadi tidak bisa dijadikan sub-unit.'
                    );
                }

                // Hero memuat maksimal 12 logo.
                if ($this->boolean('tampil_hero')) {
                    $terisi = Unit::where('tampil_hero', true)
                        ->when($unit, fn ($q) => $q->whereKeyNot($unit->getKey()))
                        ->get()
                        ->filter(fn (Unit $u) => $u->logo_url)
                        ->count();

                    if ($terisi >= TataLetakHero::MAKS) {
                        $validator->errors()->add(
                            'tampil_hero',
                            'Hero sudah berisi ' . TataLetakHero::MAKS . ' logo. Matikan "Tampilkan di hero" pada unit lain dulu.'
                        );
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_unit' => 'nama unit',
            'parent_id' => 'unit induk',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ];
    }
}
