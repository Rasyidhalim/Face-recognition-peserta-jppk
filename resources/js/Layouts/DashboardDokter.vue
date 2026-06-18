<template>
  <div class="max-w-5xl mx-auto space-y-4 p-4 animate-in fade-in duration-500">
    <div class="bg-gradient-to-r from-blue-800 to-indigo-950 p-5 rounded-3xl shadow-xl flex justify-between items-center relative overflow-hidden">
      <div class="relative z-10 text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">Dashboard Dokter</h2>
        <p class="text-blue-200 font-medium opacity-80 uppercase text-[10px] tracking-[0.2em] mt-1">Pemeriksaan & Riwayat Medis</p>
      </div>
      <button 
        type="button"
        @click="loadDataAntrian" 
        class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 border border-white/10 active:scale-95"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Refresh
      </button>
    </div>

    <div class="flex gap-4 border-b border-slate-200 pb-1">
      <button 
        @click="activeTab = 'antrian'" 
        :class="activeTab === 'antrian' ? 'border-blue-600 text-blue-600 font-black' : 'border-transparent text-slate-500 font-medium'"
        class="px-4 py-2 border-b-4 transition-all uppercase tracking-wider text-xs"
      >
        Pasien Terdaftar ({{ antrianAktif.length }})
      </button>
      <button 
        @click="activeTab = 'riwayat'" 
        :class="activeTab === 'riwayat' ? 'border-blue-600 text-blue-600 font-black' : 'border-transparent text-slate-500 font-medium'"
        class="px-4 py-2 border-b-4 transition-all uppercase tracking-wider text-xs"
      >
        Riwayat Selesai ({{ riwayatSelesai.length }})
      </button>
    </div>

    <div v-if="activeTab === 'antrian'">
      <div v-if="antrianAktif.length === 0" class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-slate-200">
        <h3 class="text-lg font-bold text-slate-400 uppercase tracking-widest">Tidak Ada Pasien Aktif</h3>
        <p class="text-slate-400 text-xs mt-1">Semua pasien yang dipanggil poli telah selesai diperiksa.</p>
      </div>

      <div v-else class="grid gap-3">
        <div 
          v-for="item in antrianAktif" 
          :key="item.id" 
          class="group bg-white p-4 rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex items-center justify-between"
        >
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-blue-50 text-blue-700 rounded-xl flex flex-col items-center justify-center border-2 border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
              <span class="text-[9px] font-black opacity-60 leading-none mb-1">NO</span>
              <span class="text-2xl font-black">{{ item.no_antrian }}</span>
            </div>

            <div class="text-left">
              <div class="flex items-center gap-2 mb-1">
                <span class="bg-blue-100 text-blue-700 text-[9px] font-black px-2 py-0.5 rounded-md uppercase">Sedang Diperiksa</span>
                <span class="text-slate-400 text-[10px] font-mono">{{ item.no_jppk }}</span>
              </div>
              <h4 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ item.nama_peserta }}</h4>
            </div>
          </div>

          <button 
            type="button"
            @click="prosesSelesai(item)" 
            class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-black shadow-md shadow-blue-100 transition-all active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            SELESAI PERIKSA
          </button>
        </div>
      </div>
    </div>

    <div v-if="activeTab === 'riwayat'">
      <div v-if="riwayatSelesai.length === 0" class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-slate-200">
        <h3 class="text-lg font-bold text-slate-400 uppercase tracking-widest">Belum Ada Riwayat Hari Ini</h3>
      </div>

      <div v-else class="grid gap-3">
        <div 
          v-for="item in riwayatSelesai" 
          :key="item.id" 
          class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex items-center justify-between opacity-80 hover:opacity-100 transition-opacity"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-slate-200 text-slate-700 rounded-lg flex flex-col items-center justify-center font-bold">
              <span class="text-[8px] opacity-60 leading-none">NO</span>
              <span class="text-base font-black leading-none">{{ item.no_antrian }}</span>
            </div>
            <div class="text-left">
              <h5 class="text-sm font-black text-slate-700 uppercase">{{ item.nama_peserta }}</h5>
              <p class="text-[10px] text-slate-400">Selesai: {{ new Date(item.updated_at).toLocaleTimeString('id-ID') }} WIB</p>
            </div>
          </div>
          <span class="bg-slate-200 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-md uppercase">Selesai Medis</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('antrian')
const isLoading = ref(false)

const antrianAktif = ref([])
const riwayatSelesai = ref([])

const loadDataAntrian = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('http://localhost:8000/api/antrian-dokter')
    if (response.data) {
      antrianAktif.value = response.data.aktif || []
      riwayatSelesai.value = response.data.riwayat || []
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
  const yakin = confirm(`Selesaikan pemeriksaan untuk pasien ${pasien.nama_peserta}?`)
  if (yakin) {
    try {
      const response = await axios.post('http://localhost:8000/api/selesai-periksa', {
        id: pasien.id
      })
      
      if (response.data.status === 'success') {
        alert(response.data.message)
        loadDataAntrian() 
      }
    } catch (error) {
      console.error("Gagal menyelesaikan pemeriksaan:", error)
      alert("Gagal memproses penyelesaian medis ke server, Mang!")
    }
  }
}
</script>