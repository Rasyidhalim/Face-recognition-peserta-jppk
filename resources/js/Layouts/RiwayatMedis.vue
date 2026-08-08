<template>
  <div class="max-w-5xl mx-auto space-y-3 p-4 animate-in fade-in duration-500">
    <div class="bg-gradient-to-r from-slate-700 to-slate-900 p-4 rounded-2xl shadow-lg flex justify-between items-center relative overflow-hidden">
      <div class="relative z-10 text-left">
        <h2 class="text-xl font-black text-white tracking-tight">Riwayat Medis</h2>
        <p class="text-slate-300 font-medium opacity-80 uppercase text-[9px] tracking-[0.2em] mt-0.5">Pasien Selesai Diperiksa</p>
      </div>
      <button 
        type="button"
        @click="loadRiwayat(true)" 
        class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 border border-white/10 active:scale-95 z-10"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Refresh
      </button>
    </div>

    <div class="flex items-center gap-2 border-b border-slate-200 pb-1">
      <span class="text-slate-500 font-bold uppercase tracking-wider text-[10px]">Total Selesai Hari Ini:</span>
      <span class="bg-slate-200 text-slate-700 font-black text-xs px-2 py-0.5 rounded">{{ riwayatSelesai.length }} Pasien</span>
    </div>

    <div>
      <div v-if="riwayatSelesai.length === 0" class="bg-white rounded-2xl p-8 text-center border-2 border-dashed border-slate-200">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Belum Ada Riwayat Hari Ini</h3>
      </div>

      <div v-else class="grid gap-2">
        <div 
          v-for="item in riwayatSelesai" 
          :key="item.id" 
          class="bg-white p-2.5 rounded-xl border border-slate-200 hover:border-slate-400 shadow-sm flex items-center justify-between transition-all"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-lg flex flex-col items-center justify-center border border-slate-200 shrink-0">
               <span class="text-[7px] font-black opacity-60 leading-none mb-0.5">NO</span>
               <span class="text-xs font-black leading-none">{{ item.no_antrian }}</span>
            </div>
            <div class="text-left">
              <h5 class="text-sm font-black text-slate-700 uppercase">{{ item.nama_peserta }}</h5>
              <p class="text-[9px] text-slate-400 font-mono mt-0.5">
                {{ item.no_jppk }} &bull; Selesai: {{ new Date(item.updated_at).toLocaleTimeString('id-ID') }} WIB
              </p>
            </div>
          </div>
          <span class="bg-green-100 text-green-700 text-[8px] font-bold px-2 py-1 rounded-md uppercase border border-green-200 shadow-sm shrink-0">Selesai Medis</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRiwayatMedis } from '../Composables/useRiwayatMedis'

const {
  riwayatSelesai,
  isLoading,
  loadRiwayat
} = useRiwayatMedis()

onMounted(() => {
  loadRiwayat(false) // Load awal tanpa munculin popup
})
</script>