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