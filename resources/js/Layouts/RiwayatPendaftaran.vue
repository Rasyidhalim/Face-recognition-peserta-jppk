<template>
  <div class="h-full bg-white rounded-3xl shadow-xl border border-slate-200 flex flex-col overflow-hidden">
    
    <div class="p-5 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
      <div>
        <h2 class="text-xl font-black text-emerald-950 tracking-tight">Riwayat Pendaftaran</h2>
        <p class="text-[10px] text-slate-500 mt-0.5 uppercase tracking-widest font-bold">Daftar Pasien Terverifikasi Hari Ini</p>
      </div>
      
      <button @click="fetchRiwayat(true)" :disabled="isLoading" class="p-2.5 bg-emerald-100 text-emerald-700 rounded-xl hover:bg-emerald-200 transition-all active:scale-95 disabled:opacity-50" title="Perbarui Data">
        <svg :class="{'animate-spin': isLoading}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
      </button>
    </div>

    <div class="px-5 py-3 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="text-[11px] font-black text-slate-500 uppercase tracking-wider">
        Total Ditemukan: <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">{{ filteredRiwayat.length }} Pasien</span>
      </div>
      
      <div class="relative w-full sm:max-w-xs">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs">🔍</span>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Cari nama, JPPK, atau Poli..." 
          class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:outline-emerald-200 transition-all"
        >
      </div>
    </div>

    <div class="flex-1 overflow-auto p-5 relative">
      
      <div v-if="isLoading" class="absolute inset-0 bg-white/80 z-10 flex flex-col items-center justify-center space-y-3">
        <div class="w-8 h-8 border-4 border-emerald-100 border-t-emerald-600 rounded-full animate-spin"></div>
        <p class="text-slate-500 text-xs font-bold tracking-wide">Memuat riwayat...</p>
      </div>

      <div v-else-if="filteredRiwayat.length === 0" class="flex flex-col items-center justify-center h-full text-center py-8">
        <div class="w-14 h-14 bg-slate-50 border border-dashed border-slate-200 rounded-full flex items-center justify-center mb-3 text-slate-300">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-sm font-bold text-slate-600">Data Tidak Ditemukan</p>
        <p class="text-slate-400 text-[11px] mt-0.5">Riwayat pendaftaran yang Anda cari tidak ada atau kosong.</p>
      </div>

      <div v-else class="overflow-hidden rounded-xl border border-slate-200">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
              <th class="py-2.5 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu</th>
              <th class="py-2.5 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">No Antrian</th>
              <th class="py-2.5 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Peserta</th>
              <th class="py-2.5 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Poliklinik</th>
              <th class="py-2.5 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100">
            <tr v-for="(item, index) in paginatedRiwayat" :key="index" class="hover:bg-slate-50/80 transition-colors">
              
              <td class="py-2 px-4 text-[11px] text-slate-500 font-medium whitespace-nowrap">
                {{ formatWaktu(item.created_at) }}
              </td>

              <td class="py-2 px-4 text-center">
                <span class="inline-block px-2.5 py-1 bg-orange-50 border border-orange-100 rounded text-xs font-black text-orange-600">
                  {{ item.no_antrian }}
                </span>
              </td>

              <td class="py-2 px-4">
                <div class="text-xs font-bold text-slate-700 uppercase tracking-tight truncate max-w-[150px] sm:max-w-none">
                  {{ item.nama_peserta }} 
                </div>
                <div class="text-[10px] font-medium text-slate-400 mt-0.5">
                  ID: {{ item.no_jppk }}
                </div>
              </td>

              <td class="py-2 px-4 text-xs font-bold text-emerald-700 uppercase tracking-wide">
                {{ item.nama_poli || 'Poli Tidak Diketahui' }}
              </td>

              <td class="py-2 px-4">
                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[9px] font-bold rounded border border-emerald-100 uppercase tracking-wider">
                  {{ item.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="filteredRiwayat.length > 0" class="flex items-center justify-between border-t border-slate-100 pt-4 mt-4">
        <div class="text-[11px] text-slate-400 font-medium">
          Menampilkan <span class="font-bold text-slate-600">{{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredRiwayat.length) }}</span> dari {{ filteredRiwayat.length }}
        </div>
        <div class="flex items-center gap-1.5">
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-[11px] font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
          >
            ← Prev
          </button>
          <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100">
            {{ currentPage }} / {{ totalPages }}
          </span>
          <button 
            @click="currentPage++" 
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-[11px] font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
          >
            Next →
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'; 
import axios from 'axios';
import Swal from 'sweetalert2';

const listRiwayat = ref([]);
const isLoading = ref(true);
const searchQuery = ref('');

// ==========================================
// 🔥 LOGIKA PAGINATION & SEARCH BAR
// ==========================================
const currentPage = ref(1);
const itemsPerPage = 10;

// Filter data secara realtime berdasarkan ketikan user
const filteredRiwayat = computed(() => {
  if (!searchQuery.value) return listRiwayat.value;
  const keyword = searchQuery.value.toLowerCase();
  return listRiwayat.value.filter(item => {
    return (
      (item.nama_peserta && item.nama_peserta.toLowerCase().includes(keyword)) ||
      (item.no_jppk && item.no_jppk.toLowerCase().includes(keyword)) ||
      (item.nama_poli && item.nama_poli.toLowerCase().includes(keyword)) ||
      (item.no_antrian && String(item.no_antrian).includes(keyword))
    );
  });
});

// Hitung total halaman pembagi
const totalPages = computed(() => {
  return Math.ceil(filteredRiwayat.value.length / itemsPerPage) || 1;
});

// Iris data menjadi hanya 10 item per halaman aktif
const paginatedRiwayat = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredRiwayat.value.slice(start, end);
});

// Otomatis balik ke halaman 1 saat user mengetik pencarian baru
watch(searchQuery, () => {
  currentPage.value = 1;
});
// ==========================================

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

const fetchRiwayat = async (isManual = false) => {
  isLoading.value = true;
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/riwayat-pendaftaran');
    if (response.data.status === 'success') {
      listRiwayat.value = response.data.data;
      if (isManual) {
        Toast.fire({ icon: 'success', title: 'Riwayat berhasil diperbarui' });
      }
    }
  } catch (error) {
    console.error("Gagal mengambil data riwayat:", error);
    listRiwayat.value = []; 
    if (isManual) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal Memuat',
        text: 'Koneksi ke server bermasalah, Mang!',
        confirmButtonColor: '#ef4444'
      });
    }
  } finally {
    isLoading.value = false;
  }
};

const formatWaktu = (waktu) => {
  if (!waktu) return '-';
  const date = new Date(waktu);
  const tanggal = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  const jam = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  return `${tanggal} | ${jam} WIB`;
};

onMounted(() => {
  fetchRiwayat(false);
});
</script>