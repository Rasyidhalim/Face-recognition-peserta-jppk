<template>
  <div class="max-w-5xl mx-auto space-y-3 p-4 animate-in fade-in duration-500">
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-950 p-4 rounded-2xl shadow-lg flex justify-between items-center relative overflow-hidden">
      <div class="relative z-10 text-left">
        <h2 class="text-xl font-black text-white tracking-tight">Dashboard Dokter</h2>
        <p class="text-emerald-200 font-medium opacity-80 uppercase text-[9px] tracking-[0.2em] mt-0.5">Pasien Aktif Hari Ini</p>
      </div>
      <button 
        type="button"
        @click="loadDataAntrian" 
        class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 border border-white/10 active:scale-95 z-10"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Refresh
      </button>
    </div>

    <div class="flex items-center gap-2 border-b border-slate-200 pb-1">
      <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Total Menunggu:</span>
      <span class="bg-blue-100 text-blue-700 font-black text-xs px-2 py-0.5 rounded">{{ antrianAktif.length }} Pasien</span>
    </div>

    <div>
      <div v-if="antrianAktif.length === 0" class="bg-white rounded-2xl p-8 text-center border-2 border-dashed border-slate-200">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Tidak Ada Pasien Aktif</h3>
        <p class="text-slate-400 text-[10px] mt-1">Semua pasien yang dipanggil poli telah selesai diperiksa.</p>
      </div>

      <div v-else class="grid gap-2">
        <div 
          v-for="item in antrianAktif" 
          :key="item.id" 
          class="group bg-white p-2.5 rounded-xl border border-slate-200 hover:border-blue-400 shadow-sm transition-all flex items-center justify-between"
        >
          <div class="flex items-center gap-3 overflow-hidden">
            <div class="w-12 h-12 bg-blue-50 text-blue-700 rounded-lg flex flex-col items-center justify-center border border-blue-100 shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
              <span class="text-[8px] font-black opacity-60 leading-none mb-0.5">NO</span>
              <span class="text-sm font-black leading-none">{{ item.no_antrian }}</span>
            </div>

            <div class="text-left truncate">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="bg-blue-100 text-blue-700 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase">Sedang Diperiksa</span>
                <span class="text-slate-400 text-[9px] font-mono">{{ item.no_jppk }}</span>
              </div>
              <h4 class="text-sm font-black text-slate-800 uppercase truncate">{{ item.nama_peserta }}</h4>
            </div>
          </div>

          <button 
            type="button"
            @click="prosesSelesai(item)" 
            class="shrink-0 flex items-center gap-1 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold shadow-sm transition-all active:scale-95 ml-2"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            SELESAI
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2' // Import SweetAlert2

const isLoading = ref(false)
const antrianAktif = ref([])

const loadDataAntrian = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/antrian-dokter')
    if (response.data) {
      antrianAktif.value = response.data.aktif || []
    }
  } catch (error) {
    console.error("Gagal memuat data antrian dokter:", error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadDataAntrian()
})

const prosesSelesai = async (pasien) => {
  const konfirmasi = await Swal.fire({
    title: 'Selesai Pemeriksaan?',
    text: `Selesaikan pemeriksaan untuk pasien ${pasien.nama_peserta}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48', // Warna merah rose menyesuaikan tombol
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Selesai!',
    cancelButtonText: 'Batal'
  });

  if (konfirmasi.isConfirmed) {
    // Loading State saat memproses API
    Swal.fire({
      title: 'Memproses data...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    try {
      const response = await axios.post('http://127.0.0.1:8000/api/selesai-periksa', {
        id: pasien.id
      })
      
      if (response.data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Pemeriksaan Selesai',
          text: response.data.message,
          confirmButtonColor: '#059669',
          timer: 1500
        });
        loadDataAntrian() 
      }
    } catch (error) {
      console.error("Gagal menyelesaikan pemeriksaan:", error)
      Swal.fire({
        icon: 'error',
        title: 'Gagal Memproses',
        text: 'Gagal memproses penyelesaian medis ke server, Mang!',
        confirmButtonColor: '#ef4444'
      });
    }
  }
}
</script>