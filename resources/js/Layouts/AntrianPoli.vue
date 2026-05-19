<template>
  <div class="max-w-5xl mx-auto space-y-6 animate-in fade-in duration-500">
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-950 p-8 rounded-[2rem] shadow-2xl relative overflow-hidden">
      <div class="relative z-10 flex justify-between items-center">
        <div>
          <h2 class="text-3xl font-black text-white tracking-tight">Panggilan Pasien</h2>
          <p class="text-emerald-200 font-medium opacity-80 uppercase text-xs tracking-[0.3em] mt-1 text-left">Antrian Poli Hari Ini</p>
        </div>
        <button 
          type="button"
          @click="loadData" 
          class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white px-6 py-3 rounded-2xl font-bold transition-all flex items-center gap-2 border border-white/10 active:scale-95"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          Refresh Data
        </button>
      </div>
      <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
    </div>

    <div v-if="list.length === 0" class="bg-white rounded-[2rem] p-20 text-center border-2 border-dashed border-slate-200">
      <div class="bg-slate-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
      <h3 class="text-xl font-bold text-slate-400 uppercase tracking-widest">Belum Ada Antrian Mang</h3>
      <p class="text-slate-400 text-sm mt-2">Data pasien yang daftar di loket akan muncul di sini.</p>
    </div>

    <div v-else class="grid gap-4">
      <div 
        v-for="item in list" 
        :key="item.id" 
        class="group bg-white p-6 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:border-emerald-300 transition-all duration-300 flex items-center justify-between"
      >
        <div class="flex items-center gap-6">
          <div class="relative">
            <div class="w-20 h-20 bg-emerald-50 text-emerald-700 rounded-2xl flex flex-col items-center justify-center border-2 border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
              <span class="text-[10px] font-black opacity-60 leading-none mb-1">NO</span>
              <span class="text-3xl font-black">{{ item.no_antrian }}</span>
            </div>
          </div>

          <div class="text-left">
            <div class="flex items-center gap-2 mb-1">
               <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-0.5 rounded-md uppercase">{{ item.poli }}</span>
               <span class="text-slate-300 text-[10px] font-bold tracking-tighter">{{ item.no_jppk }}</span>
               <span v-if="item.status_panggilan === 'sudah'" class="bg-blue-100 text-blue-600 text-[9px] font-black px-2 py-0.5 rounded-md uppercase">Sudah Dipanggil</span>
            </div>
            <h4 class="text-2xl font-black text-slate-800 uppercase tracking-tight">{{ item.nama_pasien }}</h4>
            <div class="flex items-center gap-2 mt-1 text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
              <span class="text-xs font-bold">{{ item.no_telp }}</span>
            </div>
          </div>
        </div>

        <button 
          type="button"
          @click="panggilPasien(item)" 
          class="flex items-center gap-3 bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-emerald-200 hover:shadow-emerald-300 transition-all active:scale-95 group"
        >
          <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.438 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884 0 2.225.569 3.946 1.694 5.492l-.999 3.647 3.794-.994z"/></svg>
          {{ item.status_panggilan === 'sudah' ? 'PANGGIL ULANG' : 'PANGGIL SEKARANG' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const list = ref([]);

const loadData = async () => {
  try {
    const res = await axios.get('/api/data-antrian-poli');
    list.value = res.data;
  } catch (err) {
    console.error("Gagal ambil data:", err);
  }
};

const panggilPasien = async (item) => {
  try {
    // 1. Kasih loading dikit atau konfirmasi
    if (!confirm(`Kirim notifikasi panggilan ke ${item.nama_pasien}?`)) return;

    // 2. Tembak ke route yang mengarah ke FonnteController@panggilPoli
    // Sesuaikan URL '/api/panggil-poli' dengan route di api.php Mang
    const res = await axios.post('/api/panggil-poli', {
      id: item.id
    });

    if (res.data.status === 'success') {
      alert(`✅ Notif WA berhasil dikirim ke ${item.nama_pasien}`);
      loadData(); // Refresh list biar status_panggilan berubah
    } else {
      alert("Gagal: " + res.data.message);
    }
  } catch (err) {
    console.error("Error panggil:", err);
    alert("Koneksi bermasalah atau Token Fonnte salah Mang!");
  }
};

onMounted(loadData);
</script>