<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;

class FaceRecognitionController extends Controller
{
    public function recognize(Request $request)
    {
        // 1. Terima gambar dari Vue.js
        $base64Image = $request->input('image');
        $imageParts = explode(";base64,", $base64Image);
        $imageDecoded = base64_decode($imageParts[1]);

        // 2. Simpan sementara
        $fileName = 'temp_scan_' . time() . '.jpg';
        Storage::put('temp/' . $fileName, $imageDecoded);
        $imagePath = storage_path('app/temp/' . $fileName);

        // 3. Jalankan Python (Jika di Mac/Linux, ubah 'python' jadi 'python3')
        $pythonScript = base_path('ai-scripts/face_check.py');
$process = new Process(['python', $pythonScript, $imagePath]);

// Tambahkan baris ini untuk mengatasi error HashRandomization di Windows
$process->setEnv([
    'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\Windows',
    'PATH' => getenv('PATH')
]);

$process->setTimeout(30);
$process->run();$process = new Process(['python', $pythonScript, $imagePath]);

// Tambahkan baris ini untuk mengatasi error HashRandomization di Windows
$process->setEnv([
    'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\Windows',
    'PATH' => getenv('PATH')
]);

$process->setTimeout(30);
$process->run();

        // 4. Hapus foto sementara
        Storage::delete('temp/' . $fileName);

       if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput(); 
            $standardOutput = $process->getOutput();
            $exitCode = $process->getExitCode();

            return response()->json([
                'status' => 'error', 
                'message' => "Exit Code: $exitCode | Stderr: $errorOutput | Stdout: $standardOutput"
            ]);
        }
        $output = json_decode($process->getOutput(), true);

        // 5. Jika AI mengenali wajah (misal outputnya = Rasyid_Halim.jpeg)
        if (isset($output['status']) && $output['status'] === 'success') {
            
            // Cari data di tabel MySQL berdasarkan nama file
            $pasien = DB::table('peserta_jppk')
                        ->where('face_image_path', $output['nama_file'])
                        ->first();

            if ($pasien) {
                return response()->json([
                    'status' => 'success',
                    'nama' => $pasien->nama_peserta,
                    'no_jppk' => $pasien->no_jppk,
                    'unit' => $pasien->nama_unit,
                    'ttl' => $pasien->tgl_lahir
                ]);
            } else {
                return response()->json(['status' => 'failed', 'message' => 'Wajah terdeteksi, tapi data tidak ditemukan di Database.']);
            }
        }

        return response()->json($output); // Kembalikan pesan error/gagal dari Python
    }
}