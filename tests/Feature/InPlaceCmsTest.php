<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Mitra;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\Ulasan;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InPlaceCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_halaman_depan_tampil_untuk_tamu_tanpa_tombol_admin(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Restoran Abdussalam')
            ->assertSee('Salam Coffee')
            ->assertSee('Salam Bakery')
            ->assertSee('Airsa')
            ->assertSee('Toserba Abdussalam')
            ->assertSee('Torasera')
            ->assertSee('images/logo/torasera.png')
            ->assertDontSee('id="mitra"', false)      // belum ada mitra => section disembunyikan dari tamu
            ->assertSee('Pengunjung Contoh A')        // approved
            ->assertDontSee('Pengunjung Contoh D')    // pending
            ->assertDontSee('Mode edit')
            ->assertDontSee('Tambah unit')
            ->assertDontSee('Kelola ulasan');
    }

    public function test_admin_melihat_kontrol_edit_dan_modal(): void
    {
        $this->seed();

        $this->actingAs(User::first())->get('/')
            ->assertOk()
            ->assertSee('Mode edit')
            ->assertSee('id="mitra"', false)          // admin tetap melihat section mitra untuk menambah
            ->assertSee('Pengunjung Contoh D')        // tampil di panel moderasi
            ->assertSee('name="_token"', false);
    }

    public function test_route_admin_menolak_tamu(): void
    {
        $this->post('/admin/unit', ['nama_unit' => 'X'])->assertRedirect('/login');
        $this->assertDatabaseCount('units', 0);
    }

    public function test_ulasan_publik_masuk_sebagai_pending_dan_dibatasi_3_per_jam(): void
    {
        RateLimiter::clear('ulasan');
        $data = ['nama_pengunjung' => 'Budi', 'rating' => 5, 'komentar' => 'Kopinya enak sekali, tempatnya nyaman.', 'status' => 'approved'];

        for ($i = 0; $i < 3; $i++) {
            $this->from('/')->post('/ulasan', $data)->assertRedirectContains('#ulasan')->assertSessionHas('ulasan_terkirim');
        }

        $this->assertSame(3, Ulasan::pending()->count()); // status dari form diabaikan

        $this->from('/')->post('/ulasan', $data)->assertRedirectContains('#ulasan')->assertSessionHasErrors('ulasan');
        $this->assertSame(3, Ulasan::count());
    }

    public function test_crud_unit_dan_produk_dengan_foto(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->from('/')->post('/admin/unit', ['nama_unit' => 'Salam Coffee'])->assertRedirectContains('#unit');
        $induk = Unit::first();

        $this->post('/admin/unit', ['nama_unit' => 'Minuman Dingin', 'parent_id' => $induk->id])->assertSessionHasNoErrors();
        $sub = Unit::where('parent_id', $induk->id)->first();

        // Sub-unit tidak boleh jadi induk sub-unit lain
        $this->post('/admin/unit', ['nama_unit' => 'Cucu', 'parent_id' => $sub->id])->assertSessionHasErrors('parent_id');

        $this->post('/admin/produk', [
            'unit_id' => $sub->id, 'nama_produk' => 'Es Kopi', 'harga' => 'Rp18.000',
            'foto' => UploadedFile::fake()->image('es.jpg'),
        ])->assertSessionHasNoErrors()->assertSessionHas('tab', $induk->id);

        $produk = Produk::first();
        $this->assertSame(18000, $produk->harga);
        Storage::disk('public')->assertExists($produk->foto_path);

        // Foto > 2 MB ditolak
        $this->post('/admin/produk', [
            'unit_id' => $sub->id, 'nama_produk' => 'Besar', 'harga' => 1,
            'foto' => UploadedFile::fake()->image('besar.jpg')->size(3000), '_modal' => 'produk',
        ])->assertSessionHasErrors('foto');

        // Ganti foto -> foto lama terhapus
        $fotoLama = $produk->foto_path;
        $this->put("/admin/produk/{$produk->id}", [
            'unit_id' => $sub->id, 'nama_produk' => 'Es Kopi Susu', 'harga' => 20000,
            'foto' => UploadedFile::fake()->image('baru.png'),
        ])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($fotoLama);
        $fotoBaru = $produk->fresh()->foto_path;

        // Hapus unit induk -> sub-unit, produk & foto ikut terhapus
        $this->delete("/admin/unit/{$induk->id}")->assertSessionHas('status');
        $this->assertDatabaseCount('units', 0);
        $this->assertDatabaseCount('produks', 0);
        Storage::disk('public')->assertMissing($fotoBaru);
    }

    public function test_cabang_media_file_dan_link(): void
    {
        $this->actingAs(User::factory()->create());

        $dasar = ['nama_cabang' => 'Toserba Pusat', 'alamat' => 'Kubu Raya', 'no_whatsapp' => '0812-3456-7890'];

        // file wajib saat tipe file
        $this->post('/admin/cabang', $dasar + ['media_type' => 'file'])->assertSessionHasErrors('media_file');

        // gambar > 2 MB ditolak walau di bawah batas video
        $this->post('/admin/cabang', $dasar + ['media_type' => 'file', 'media_file' => UploadedFile::fake()->image('a.jpg')->size(3000)])
            ->assertSessionHasErrors('media_file');

        $this->post('/admin/cabang', $dasar + ['media_type' => 'file', 'media_file' => UploadedFile::fake()->image('a.jpg')])
            ->assertSessionHasNoErrors();
        $cabang = Cabang::first();
        $this->assertSame('6281234567890', $cabang->no_whatsapp);
        Storage::disk('public')->assertExists($cabang->media_path);
        $file = $cabang->media_path;

        // update tanpa unggahan baru -> file lama dipertahankan
        $this->put("/admin/cabang/{$cabang->id}", $dasar + ['media_type' => 'file', 'nama_cabang' => 'Toserba 1'])->assertSessionHasNoErrors();
        $this->assertSame($file, $cabang->fresh()->media_path);

        // ganti ke link -> simpan URL, file lama dihapus
        $this->put("/admin/cabang/{$cabang->id}", $dasar + ['media_type' => 'link', 'media_link' => 'https://youtu.be/dQw4w9WgXcQ'])->assertSessionHasNoErrors();
        $cabang->refresh();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $cabang->media_path);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $cabang->media_url);
        Storage::disk('public')->assertMissing($file);

        $this->delete("/admin/cabang/{$cabang->id}");
        $this->assertDatabaseCount('cabangs', 0);
    }

    public function test_pengaturan_mitra_dan_moderasi_ulasan(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put('/admin/pengaturan', ['visi' => 'Visi baru', 'misi' => "Satu\nDua"])->assertRedirect();
        $this->assertSame('Visi baru', Pengaturan::ambil()['visi']);

        $this->post('/admin/mitra', ['nama_mitra' => 'Airsa'])->assertSessionHasErrors('logo');
        $this->post('/admin/mitra', ['nama_mitra' => 'Airsa', 'logo' => UploadedFile::fake()->image('l.png')])->assertSessionHasNoErrors();
        $mitra = Mitra::first();
        $this->delete("/admin/mitra/{$mitra->id}");
        Storage::disk('public')->assertMissing($mitra->logo_path);

        $ulasan = Ulasan::create(['nama_pengunjung' => 'Sari', 'rating' => 4, 'komentar' => 'Rotinya lembut dan wangi.']);
        $this->patch("/admin/ulasan/{$ulasan->id}/setujui");
        $this->assertSame('approved', $ulasan->fresh()->status);

        $this->get('/')->assertSee('Rotinya lembut dan wangi.');
    }

    public function test_login_admin_dan_pembatasan_percobaan(): void
    {
        $admin = User::factory()->create(['email' => 'admin@abdussalam.test', 'password' => 'rahasia123']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['email' => $admin->email, 'password' => 'salah'])
                ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        }

        // Percobaan ke-6 ditolak dengan pesan, walau sandi benar
        $this->from('/login')->post('/login', ['email' => $admin->email, 'password' => 'rahasia123'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();

        RateLimiter::clear('login:admin@abdussalam.test|127.0.0.1');
        $this->post('/login', ['email' => $admin->email, 'password' => 'rahasia123'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_pesan_validasi_berbahasa_indonesia(): void
    {
        app()->setLocale('id');

        $this->from('/')->post('/ulasan', ['nama_pengunjung' => '', 'rating' => 9, 'komentar' => 'pendek'])
            ->assertSessionHasErrors([
                'nama_pengunjung' => 'Nama wajib diisi.',
                'rating'          => 'Rating harus bernilai 1 sampai 5.',
                'komentar'        => 'Ulasan minimal 10 karakter.',
            ]);
    }

    public function test_validasi_gagal_membuka_ulang_modal(): void
    {
        $this->seed();
        $admin = User::first();

        $this->actingAs($admin)->from('/')
            ->post('/admin/produk', ['_modal' => 'produk', 'nama_produk' => '', 'harga' => ''])
            ->assertRedirectContains('#unit');

        $this->get('/')->assertSee("Alpine.store('modal').open(lama._modal, lama)", false);
    }
}
