<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataPesertaController extends Controller
{
    public function index()
    {
        // Mengambil data pendaftaran antrian terbaru
        $data = DB::table('antrians')->orderBy('id', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}