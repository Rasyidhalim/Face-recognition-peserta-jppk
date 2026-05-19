<template>
  <div class="h-full bg-white rounded-3xl shadow-xl border border-slate-200 flex flex-col overflow-hidden">
    
    <div class="p-6 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-black text-emerald-950 tracking-tight">Antrian Pendaftaran</h2>
        <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">Daftar Pasien Terdaftar Hari Ini</p>
      </div>
      
      <button 
        @click="fetchPeserta" 
        :disabled="isLoading"
        class="p-3 bg-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-200 transition-all active:scale-95 disabled:opacity-50"
        title="Perbarui Data"
      >
        <svg :class="{'animate-spin': isLoading}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
      </button>
    </div>

    <div class="flex-1 overflow-auto p-6 relative">
      
      <div v-if="isLoading" class="absolute inset-0 bg-white/80 z-10 flex flex-col items-center justify-center space-y-4">
        <div class="w-12 h-12 border-4 border-emerald-100 border-t-emerald-600 rounded-full animate-spin"></div>
        <p class="text-slate-500 font-bold tracking-wide">Menarik data dari server...</p>
      </div>

      <div v-else-if="listPeserta.length === 0" class="flex flex-col items-center justify-center h-full text-center">
        <div class="w-20 h-20 bg-slate-50 border-2 border-dashed border-slate-200 rounded-full flex items-center justify-center mb-4 text-slate-300">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
        </div>
        <p class="text-xl font-bold text-slate-600">Antrian Kosong</p>
        <p class="text-slate-400 text-sm mt-1">Belum ada pasien yang terdaftar.</p>
      </div>

      <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-emerald-950">
                <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest w-16 text-center">No</th>
                <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Waktu</th>
                <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">No. Antrian</th>
                <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Nama & Kontak</th>
                <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest">Tujuan</th> 
                <th class="py-4 px-6 text-xs font-black text-emerald-100 uppercase tracking-widest text-center">Aksi / Status</th> 
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100">
            <tr v-for="(peserta, index) in listPeserta" :key="peserta.id" class="hover:bg-slate-50 transition-colors">
              <td class="py-4 px-6 text-sm text-slate-400 font-bold text-center">{{ index + 1 }}</td>
              
              <td class="py-4 px-6 text-sm text-slate-500 font-medium">
                {{ formatTime(peserta.created_at) }}
              </td>

              <td class="py-4 px-6">
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-black bg-emerald-100 text-emerald-700 border border-emerald-200">
                  {{ peserta.no_antrian }}
                </span>
              </td>
              
              <td class="py-4 px-6">
                <div class="text-sm font-bold text-slate-700">{{ peserta.nama_pasien }}</div>
                <div class="text-[11px] text-slate-400 font-medium mt-0.5">{{ peserta.no_telp }}</div>
              </td>
              
              <td class="py-4 px-6 text-sm text-slate-600 font-bold">
                {{ peserta.poli }}
              </td>

              <td class="py-4 px-6 text-center">
                <div v-if="peserta.status_panggilan === 'sudah'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-200 text-xs font-bold">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  Sudah Dipanggil
                </div>

                <button 
                  v-else
                  @click="panggilPeserta(peserta)"
                  class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg font-bold text-xs shadow-md shadow-blue-200 active:scale-95 transition-all"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                  Panggil Poli
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const listPeserta = ref([]);
const isLoading = ref(true);

const fetchPeserta = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/data-peserta');
    if (response.data.status === 'success') {
      listPeserta.value = response.data.data;
    }
  } catch (error) {
    console.error("Gagal mengambil data dari server:", error);
  } finally {
    isLoading.value = false;
  }
};

const panggilPeserta = async (peserta) => {
  // Tambahkan konfirmasi agar tidak tidak sengaja kepencet
  if(!confirm(`Yakin ingin memanggil pasien ${peserta.nama_pasien} via WA?`)) return;

  // Ubah UI sementara selagi nunggu WA terkirim biar petugas tau tombol lagi loading
  peserta.status_panggilan = 'loading'; 

  try {
    const res = await axios.post('http://127.0.0.1:8000/api/fonnte/panggil-poli', {
      id: peserta.id
    });
    
    if(res.data.status === 'success') {
      // Ubah jadi sukses
      peserta.status_panggilan = 'sudah'; 
    }
  } catch (error) {
    alert("❌ Gagal mengirim notifikasi panggil.");
    peserta.status_panggilan = 'belum'; // Balikin jika error
  }
};

const formatTime = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
  fetchPeserta();
});
</script>