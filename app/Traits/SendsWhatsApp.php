<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait SendsWhatsApp
{
    /**
     * Helper: Fungsi kirim WA
     */
    protected function sendWhatsApp($target, $pesan, $token)
    {
        return Http::withHeaders(['Authorization' => $token])
            ->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $pesan,
                'countryCode' => '62',
            ]);
    }
}
