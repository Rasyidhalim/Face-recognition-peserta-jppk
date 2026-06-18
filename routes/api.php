<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PesertaJppkController;
use App\Http\Controllers\FonnteController;
use App\Http\Controllers\DataPesertaController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\FarmasiController;
use App\Http\Controllers\PoliController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\AdminPesertaController;
use App\Http\Controllers\AdminLaporanController;

// Route untuk mengambil data laporan pendaftaran antrean
Route::get('/admin/laporan-pendaftaran', [AdminLaporanController::class, 'getLaporan']);

Route::get('/admin/peserta', [AdminPesertaController::class, 'index']);

Route::post('/admin/peserta', [AdminPesertaController::class, 'store']);

Route::put('/admin/peserta/{no_jppk}', [AdminPesertaController::class, 'update']);

Route::post('/admin/peserta/registrasi-muka/{no_jppk}', [AdminPesertaController::class, 'registrasiMukaWa']);

Route::delete('/admin/peserta/{no_jppk}', [AdminPesertaController::class, 'destroy']);

Route::get('/antrian-dokter', [FonnteController::class, 'getAntrianDokter']);

Route::post('/selesai-periksa', [FonnteController::class, 'selesaiMedis']);
// Route untuk kebutuhan Kiosk Mandiri (Poli & Dokter)
Route::get('/kiosk/poliklinik', [KioskController::class, 'getPoliklinik']);

Route::get('/kiosk/dokter/{poli_id}', [KioskController::class, 'getDokterByPoli']);

Route::middleware('auth:sanctum')->group(function () {
    // Route biasa untuk ambil data poli
    Route::get('/data-antrian-poli', [PoliController::class, 'getAntrianPoli']);
});

Route::get('/farmasi/riwayat', [FarmasiController::class, 'riwayat']);

Route::get('/farmasi/antrian', [FarmasiController::class, 'index']);

Route::post('/farmasi/update-dan-notif', [FonnteController::class, 'notifObatFarmasi']);

Route::get('/riwayat-pendaftaran', [RiwayatController::class, 'index']);

Route::post('/recognize', [PesertaJppkController::class, 'recognize']);

Route::post('/daftar-antrian', [FonnteController::class, 'daftarAntrian']);

Route::get('/data-peserta', [DataPesertaController::class, 'index']);

Route::post('/fonnte/panggil-poli', [FonnteController::class, 'panggilPoli']);

Route::get('/data-antrian-poli', [PoliController::class, 'getAntrianPoli']);

Route::post('/panggil-poli', [FonnteController::class, 'panggilPoli']);