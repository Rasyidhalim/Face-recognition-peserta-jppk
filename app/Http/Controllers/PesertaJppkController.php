<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PesertaJppk;
use Illuminate\Support\Facades\Http;

class PesertaJppkController extends Controller
{
    public function recognize(Request $request)
    {
        // 1. Validasi input
        if (!$request->hasFile('image')) {
            return response()->json(['status' => 'error', 'message' => 'Gambar tidak ditemukan']);
        }

        try {
            $image = $request->file('image');

            // 2. Kirim gambar ke Python FastAPI (Port 8000)
            $response = Http::timeout(10)
                ->attach('image', file_get_contents($image), 'scan.jpg')
                ->post('http://127.0.0.1:8000/recognize');

            if ($response->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Server AI tidak merespon']);
            }

            $aiData = $response->json(); // Ambil hasil deteksi wajah

            // 3. Cari wajah yang statusnya bukan "TIDAK DIKENAL"
            $recognized = collect($aiData['data'])->firstWhere('label', '!=', 'TIDAK DIKENAL');

            if ($recognized) {
                // Cari data lengkap di database MySQL berdasarkan label (No JPPK / Nama File)
                $pasien = PesertaJppk::with(['unit', 'plan'])
                            ->where('no_jppk', $recognized['label'])
                            ->first();

                if ($pasien) {
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'nama' => $pasien->nama_peserta,
                            'no_jppk' => $pasien->no_jppk,
                            'unit' => $pasien->unit->nama_unit,
                            'plan' => $pasien->plan->nama_plan,
                            'tgl_lahir' => $pasien->tgl_lahir->format('d-m-Y')
                        ]
                    ]);
                }
            }

            return response()->json(['status' => 'failed', 'message' => 'Wajah tidak terdaftar']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}