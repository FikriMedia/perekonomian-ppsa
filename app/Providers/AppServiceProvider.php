<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tanggal ulasan tampil dalam Bahasa Indonesia (set APP_LOCALE=id di .env).
        Carbon::setLocale(config('app.locale'));

        RateLimiter::for('ulasan', function (Request $request) {
            return Limit::perHour(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return back()
                        ->withFragment('ulasan')
                        ->withInput()
                        ->withErrors(['ulasan' => 'Anda sudah mengirim 3 ulasan dalam satu jam terakhir. Silakan coba lagi nanti.'])
                        ->withHeaders($headers);
                });
        });
    }
}
