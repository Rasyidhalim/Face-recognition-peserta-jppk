<template>
  <div class="p-6 space-y-6 animate-fade-in">
    
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
      <h3 class="text-lg font-black text-slate-800 tracking-tight">📋 Laporan Antrean Pendaftaran Pasien</h3>
      <p class="text-[11px] text-slate-400 font-medium mt-0.5">Rekapitulasi data pasien JPPK, filter poliklinik, jadwal, dan dokter RS Pindad</p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
      <h4 class="font-black text-slate-800 text-sm mb-4 border-b pb-2">🔍 Filter Laporan Antrean</h4>
      
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
          <label class="text-[11px] font-bold text-slate-400 block mb-1">Tanggal Berobat</label>
          <input v-model="filterLaporan.tanggal" type="date" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
        </div>
        
        <div>
          <label class="text-[11px] font-bold text-slate-400 block mb-1">Poliklinik Tujuan</label>
          <input v-model="filterLaporan.poli" type="text" placeholder="Contoh: OBGYN / ANAK" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
        </div>

        <div>
          <label class="text-[11px] font-bold text-slate-400 block mb-1">Nama Dokter</label>
          <input v-model="filterLaporan.dokter" type="text" placeholder="Contoh: dr. Rimonta" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700">
        </div>

        <div class="flex items-end gap-2">
          <button @click="fetchLaporan(true)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs px-4 py-2.5 rounded-xl shadow-md transition-all">
            Cari Data
          </button>
          <button @click="resetFilterLaporan" class="bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold text-xs px-4 py-2.5 rounded-xl transition-all">
            Reset
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
      <div class="flex justify-between items-center mb-4">
        <h4 class="font-bold text-sm text-slate-700">Daftar Antrean ({{ listLaporan.length }} Pasien ditemukan)</h4>
      </div>

      <div class="overflow-x-auto border border-slate-100 rounded-xl">
        <table class="w-full text-left border-collapse text-xs">
          <thead class="bg-slate-50 border-b border-slate-200 font-bold text-slate-600">
            <tr>
              <th class="p-3 text-center">No Antrean</th>
              <th class="p-3">Tanggal Berobat</th>
              <th class="p-3">No. Registrasi</th>
              <th class="p-3 text-blue-700">No RM</th>
              <th class="p-3">Nama Pasien</th>
              <th class="p-3">Poli Tujuan</th>
              <th class="p-3">Dokter</th>
              <th class="p-3 text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="listLaporan.length === 0">
              <td colspan="8" class="p-6 text-center text-slate-400 font-bold italic">Tidak ada data pendaftaran antrean.</td>
            </tr>
            <tr v-for="lap in paginatedLaporan" :key="lap.id" class="border-b border-slate-100 hover:bg-slate-50/80">
              <td class="p-3 text-center font-black text-slate-700 bg-slate-50/50 text-sm">{{ lap.no_antrian }}</td>
              <td class="p-3 font-medium text-slate-600 whitespace-nowrap">{{ lap.tanggal_daftar }}</td>
              <td class="p-3 text-slate-500 font-mono">{{ lap.no_registrasi || '-' }}</td>
              <td class="p-3 font-bold text-blue-700 bg-blue-50/20">{{ lap.no_rm || 'Belum Ada' }}</td>
              <td class="p-3 font-bold text-slate-800">{{ lap.nama_pasien }}</td>
              <td class="p-3 font-medium text-slate-700">{{ lap.nama_poli }}</td>
              <td class="p-3 font-medium text-slate-700">{{ lap.nama_dokter }}</td>
              <td class="p-3 text-center">
                <span :class="{
                  'bg-amber-100 text-amber-700': lap.status_antrian === 'menunggu',
                  'bg-blue-100 text-blue-700': lap.status_antrian === 'dipanggil',
                  'bg-emerald-100 text-emerald-700': lap.status_antrian === 'selesai',
                  'bg-rose-100 text-rose-700': lap.status_antrian === 'batal'
                }" class="px-3 py-1 rounded-full font-black text-[10px] capitalize">
                  {{ lap.status_antrian }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="listLaporan.length > 0" class="flex items-center justify-between border-t border-slate-100 pt-5 mt-5">
        <div class="text-[11px] text-slate-500 font-bold">
          Menampilkan {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, listLaporan.length) }} dari {{ listLaporan.length }} Data
        </div>
        <div class="flex items-center gap-2">
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
          >
            Sebelumnya
          </button>
          <span class="text-xs font-black text-blue-800 bg-blue-50 px-4 py-2 rounded-xl border border-blue-100">
            Halaman {{ currentPage }} / {{ totalPages }}
          </span>
          <button 
            @click="currentPage++" 
            :disabled="currentPage === totalPages"
            class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
          >
            Selanjutnya
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
// 🔥 Tambahkan "computed" di import Vue
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2' 

const listLaporan = ref([])
const filterLaporan = ref({ tanggal: '', poli: '', dokter: '' })

// ==========================================
// 🔥 STATE & LOGIKA PAGINATION
// ==========================================
const currentPage = ref(1)
const itemsPerPage = 10 // Tampilkan 10 data per halaman

// Menghitung total jumlah halaman (contoh: 25 data = 3 halaman)
const totalPages = computed(() => {
  return Math.ceil(listLaporan.value.length / itemsPerPage) || 1
})

// Mengiris (slice) array data mentah agar hanya berisi 10 data per halamannya
const paginatedLaporan = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return listLaporan.value.slice(start, end)
})
// ==========================================

// Konfigurasi Toast Custom
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.onmouseenter = Swal.stopTimer;
    toast.onmouseleave = Swal.resumeTimer;
  }
});

onMounted(() => {
  fetchLaporan(false) 
})

// Panggil API Backend (Tambah parameter isManual)
const fetchLaporan = async (isManual = false) => {
  if (isManual) {
    Swal.fire({
      title: 'Mencari Data...',
      text: 'Mohon tunggu sebentar',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading()
      }
    });
  }

  try {
    const res = await axios.get('/api/admin/laporan-pendaftaran', {
      params: {
        tanggal: filterLaporan.value.tanggal,
        poli: filterLaporan.value.poli,
        dokter: filterLaporan.value.dokter
      }
    })
    
    if (res.data.status === 'success') {
      listLaporan.value = res.data.data

      // 🚀 RESET KE HALAMAN 1 JIKA MELAKUKAN PENCARIAN BARU
      currentPage.value = 1

      if (isManual) {
        Swal.close();
        Toast.fire({
          icon: 'success',
          title: `Ditemukan ${listLaporan.value.length} data`
        });
      }
    }
  } catch (error) {
    console.error("Gagal memuat laporan antrean:", error)
    if (isManual) {
      Swal.fire('Error!', 'Gagal menarik data dari server, Mang!', 'error')
    } else {
      Toast.fire({ icon: 'error', title: 'Gagal memuat data' });
    }
  }
}

const resetFilterLaporan = () => {
  filterLaporan.value = { tanggal: '', poli: '', dokter: '' }
  fetchLaporan(false) // Tarik ulang data semua
  
  Toast.fire({
    icon: 'info',
    title: 'Filter dikembalikan ke awal'
  });
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeInEffect 0.3s ease-out;
}
@keyframes fadeInEffect {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>