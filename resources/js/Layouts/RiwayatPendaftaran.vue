<template>
  <div class="h-full bg-white rounded-3xl shadow-xl border border-slate-200 flex flex-col overflow-hidden">
    
    <div class="p-6 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-black text-emerald-950 tracking-tight">Riwayat Pendaftaran</h2>
        <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">Daftar Pasien Terverifikasi Hari Ini</p>
      </div>
      
      <button @click="fetchRiwayat" :disabled="isLoading" class="p-3 bg-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-200 transition-all active:scale-95 disabled:opacity-50">
        <svg :class="{'animate-spin': isLoading}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
      </button>
    </div>

    <div class="flex-1 overflow-auto p-6 relative">
      
      <div v-if="isLoading" class="absolute inset-0 bg-white/80 z-10 flex flex-col items-center justify-center space-y-4">
        <div class="w-12 h-12 border-4 border-emerald-100 border-t-emerald-600 rounded-full animate-spin"></div>
        <p class="text-slate-500 font-bold tracking-wide">Memuat riwayat...</p>
      </div>

      <div v-else-if="listRiwayat.length === 0" class="flex flex-col items-center justify-center h-full text-center">
        <div class="w-20 h-20 bg-slate-50 border-2 border-dashed border-slate-200 rounded-full flex items-center justify-center mb-4 text-slate-300">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-xl font-bold text-slate-600">Belum Ada Pendaftaran</p>
        <p class="text-slate-400 text-sm mt-1">Riwayat pendaftaran poli hari ini masih kosong.</p>
      </div>

      <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-emerald-950">
              <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Waktu</th>
              <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest text-center">No Antrian</th>
              <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Peserta</th>
              <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Poliklinik</th>
              <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100">
           <tr v-for="(item, index) in listRiwayat" :key="index" class="hover:bg-slate-50 transition-colors">
            <td class="py-4 px-6 text-sm text-slate-500 font-bold">
                {{ formatWaktu(item.created_at) }}
            </td>

            <td class="py-4 px-6 text-center">
                <div class="inline-flex flex-col items-center justify-center w-16 h-16 bg-orange-50 border-2 border-orange-200 rounded-xl shadow-sm">
                <span class="text-[9px] font-black text-orange-500 uppercase tracking-tighter">Antrian</span>
                <span class="text-xl font-black text-orange-700 leading-none">{{ item.no_antrian }}</span>
                </div>
            </td>

            <td class="py-4 px-6">
                <p class="text-sm font-black text-slate-800 uppercase tracking-tight">
                {{ item.nama_pasien }} </p>
                <p class="text-xs font-bold text-slate-400 mt-0.5">
                ID: {{ item.no_jppk }}
                </p>
            </td>

            <td class="py-4 px-6 text-sm font-bold text-emerald-700 uppercase">
                {{ item.poli }}
            </td>

            <td class="py-4 px-6">
                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full border border-emerald-200 uppercase">
                Berhasil
                </span>
            </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'; // Tambahkan onMounted di sini (sudah benar di kode Mang)
import axios from 'axios';

const listRiwayat = ref([]);
const isLoading = ref(true);

// Fungsi mengambil data riwayat dari Laravel
const fetchRiwayat = async () => {
  isLoading.value = true;
  try {
    // Pastikan URL API sudah benar sesuai rute Laravel Mang
    const response = await axios.get('http://127.0.0.1:8000/api/riwayat-pendaftaran');
    
    if (response.data.status === 'success') {
      listRiwayat.value = response.data.data;
    }
  } catch (error) {
    console.error("Gagal mengambil data riwayat:", error);
    // Jika error, pastikan loading dimatikan juga agar tidak stuck
    listRiwayat.value = []; 
  } finally {
    // Bagian ini akan selalu dijalankan (baik sukses maupun error)
    isLoading.value = false;
  }
};

// Merapikan format waktu bawaan database (Ditambah Tanggal)
const formatWaktu = (waktu) => {
  if (!waktu) return '-';
  const date = new Date(waktu);
  
  // Format Tanggal (Contoh: 27 Okt 2023)
  const tanggal = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  // Format Jam (Contoh: 08:30 WIB)
  const jam = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
  
  return `${tanggal} | ${jam}`;
};

// --- INI BAGIAN YANG HILANG DI KODE MANG ---
// Fungsi ini akan berjalan OTOMATIS saat komponen/halaman ini dibuka
onMounted(() => {
  fetchRiwayat(); // Panggil fungsi ambil data di sini
});
// -------------------------------------------
</script>