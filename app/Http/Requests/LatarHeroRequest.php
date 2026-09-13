<?php

namespace App\Http\Requests;

use App\Models\Cabang;
use App\Models\Pengaturan;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LatarHeroRequest extends ModalFormRequest
{
    protected string $section = 'beranda';

    private const MAKS_FOTO_KB = 5120;    // 5 MB: foto background biasanya lebih besar dari foto produk
    private const MAKS_VIDEO_KB = 20480;  // 20 MB (upload_max_filesize XAMPP 40M)

    public function rules(): array
    {
        $tipe = $this->input('tipe');
        $pakaiMedia = in_array($tipe, ['foto', 'video'], true);
        $mimes = $tipe === 'video' ? 'mimes:mp4,webm' : 'mimes:jpg,jpeg,png,webp';
        $maks = $tipe === 'video' ? self::MAKS_VIDEO_KB : self::MAKS_FOTO_KB;

        return [
            'tipe'   => ['required', Rule::in(['bawaan', 'gradasi', 'foto', 'video'])],
            'warna1' => ['required_if:tipe,gradasi', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'warna2' => ['required_if:tipe,gradasi', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'arah'   => ['nullable', 'integer', 'between:0,360'],
            'sumber' => [Rule::requiredIf($pakaiMedia), 'nullable', Rule::in(['file', 'link'])],
            'file'   => ['nullable', 'file', $mimes, 'max:' . $maks],
            'link'   => ['nullable', 'url:http,https', 'max:2000'],
            'gelap'  => ['nullable', 'integer', 'between:0,80'],
            'teks'   => ['required', Rule::in(['gelap', 'terang'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tipe = $this->input('tipe');
                if (! in_array($tipe, ['foto', 'video'], true) || $validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('sumber') === 'link') {
                    $link = (string) $this->input('link');

                    if ($link === '') {
                        $validator->errors()->add('link', $tipe === 'video' ? 'Isi link video.' : 'Isi link foto.');
                    } elseif ($tipe === 'video' && ! Cabang::youtubeId($link)
                        && ! preg_match('~\.(mp4|webm)$~i', (string) parse_url($link, PHP_URL_PATH))) {
                        $validator->errors()->add('link', 'Gunakan link YouTube atau link langsung ke file .mp4 / .webm.');
                    }

                    return;
                }

                // Sumber file: wajib unggah, kecuali sudah ada file dengan tipe yang sama.
                $lama = Pengaturan::latarHero(Pengaturan::ambil(['hero_background'])['hero_background'] ?? null);
                $adaFileLama = $lama['tipe'] === $tipe && $lama['sumber'] === 'file' && $lama['path'];

                if (! $this->hasFile('file') && ! $adaFileLama) {
                    $validator->errors()->add('file', $tipe === 'video' ? 'Unggah file video.' : 'Unggah file foto.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'warna1' => 'warna pertama',
            'warna2' => 'warna kedua',
            'gelap'  => 'kegelapan lapisan',
        ];
    }

    public function messages(): array
    {
        return [
            'warna1.regex' => 'Warna harus berformat #RRGGBB.',
            'warna2.regex' => 'Warna harus berformat #RRGGBB.',
            'file.mimes'   => $this->input('tipe') === 'video' ? 'Video harus MP4 atau WEBM.' : 'Foto harus JPG, PNG, atau WEBP.',
            'file.max'     => $this->input('tipe') === 'video' ? 'Ukuran video maksimal 20 MB.' : 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
