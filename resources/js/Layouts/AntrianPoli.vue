<template>
  <div class="max-w-5xl mx-auto space-y-4 animate-in fade-in duration-500">
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-950 p-4 rounded-2xl shadow-lg relative overflow-hidden flex justify-between items-center">
      <div class="relative z-10">
        <h2 class="text-xl font-black text-white tracking-tight">Panggilan Pasien</h2>
        <p class="text-emerald-200 font-medium opacity-80 uppercase text-[9px] tracking-[0.2em] mt-0.5">Antrian Poli Hari Ini</p>
      </div>
      <button 
        type="button"
        @click="loadData" 
        class="relative z-10 bg-white/10 hover:bg-white/20 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 border border-white/10 active:scale-95"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Refresh
      </button>
      <div class="absolute -right-5 -bottom-5 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
    </div>

    <div class="relative">
      <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
        <svg class="w-4 h-4 text-emerald-600/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </div>
      <input 
        type="text" 
        v-model="searchQuery" 
        class="bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full pl-10 p-2.5 shadow-sm transition-all outline-none" 
        placeholder="Cari berdasarkan No Antrian atau Nama Poli..."
      >
    </div>

    <div v-if="list.length === 0" class="bg-white rounded-2xl p-8 text-center border-2 border-dashed border-slate-200">
      <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Belum Ada Antrian Mang</h3>
    </div>

    <div v-else-if="filteredAntrian.length === 0" class="bg-white rounded-2xl p-8 text-center border-2 border-dashed border-slate-200">
      <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Pasien Tidak Ditemukan</h3>
      <p class="text-[10px] text-slate-400 mt-1 font-medium">Coba cek lagi penulisan nomor antrian atau poli-nya.</p>
    </div>

    <div v-else class="grid gap-2">
      <div 
        v-for="item in filteredAntrian" 
        :key="item.id" 
        class="group bg-white p-2.5 rounded-xl border border-slate-200 hover:border-emerald-400 shadow-sm transition-all flex items-center justify-between"
      >
        <div class="flex items-center gap-3 overflow-hidden">
          <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex flex-col items-center justify-center border border-emerald-100 shrink-0 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
            <span class="text-[8px] font-black opacity-60 leading-none mb-0.5">NO</span>
            <span class="text-sm font-black leading-none">{{ item.no_antrian }}</span>
          </div>

          <div class="text-left truncate">
            <div class="flex items-center gap-2 mb-0.5">
               <span class="bg-emerald-100 text-emerald-700 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">
                 {{ item.poli ? item.poli : 'POLI UMUM' }}
               </span>
               <span class="text-slate-400 text-[9px] font-mono">{{ item.no_jppk }}</span>
               <span v-if="item.status === 'dipanggil'" class="bg-blue-100 text-blue-600 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase">Dipanggil</span>
            </div>
            
            <h4 class="text-sm font-black text-slate-800 uppercase truncate">{{ item.nama_peserta }}</h4>
            
            <div class="flex items-center gap-1 mt-0.5 text-slate-400">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
              <span class="text-[10px] font-semibold">{{ item.no_telp }}</span>
            </div>
          </div>
        </div>

        <button 
          type="button"
          @click="panggilPasien(item)" 
          class="shrink-0 flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-2 rounded-lg text-[10px] font-bold shadow-sm transition-all active:scale-95 ml-2"
        >
          <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.438 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884 0 2.225.569 3.946 1.694 5.492l-.999 3.647 3.794-.994z"/></svg>
          {{ item.status === 'dipanggil' ? 'PANGGIL ULANG' : 'PANGGIL' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAntrianPoli } from '../Composables/useAntrianPoli';

const {
  list,
  searchQuery,
  filteredAntrian,
  loadData,
  panggilPasien
} = useAntrianPoli();

onMounted(loadData);
</script>