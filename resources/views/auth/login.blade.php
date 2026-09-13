<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>Masuk admin | Perekonomian PPSA</title>
</head>
<body class="grid min-h-screen place-items-center px-4 py-10">
    <main class="w-full max-w-sm">
        <a href="{{ route('home') }}" class="mx-auto mb-8 flex w-fit flex-col items-center gap-4">
            <span class="ubin-hijau h-20 w-20 p-3">
                <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-full w-full object-contain drop-shadow">
            </span>
            <span class="text-center font-display text-lg font-semibold leading-tight tracking-rapat">Perekonomian Pondok<br>Pesantren Abdussalam</span>
        </a>

        <div class="kartu p-7">
            <h1 class="font-display text-2xl font-semibold tracking-rapat">Masuk admin</h1>
            <p class="mt-1 text-sm text-redup">Setelah masuk, tombol edit muncul langsung di halaman depan.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field">
                    @error('email') <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="field">
                </div>
                <label class="flex items-center gap-2 text-sm text-redup">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-garis text-hijau focus:ring-hijau">
                    Ingat saya
                </label>
                <button type="submit" class="btn-gelap w-full py-3">Masuk</button>
            </form>
        </div>

        <a href="{{ route('home') }}" class="mt-6 block text-center text-sm text-redup hover:text-tinta">Kembali ke halaman depan</a>
    </main>
</body>
</html>
