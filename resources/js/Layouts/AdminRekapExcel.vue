<template>
  <AdminLayout>
    <div class="space-y-6 animate-fade-in">
      <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between bg-white p-8 rounded-3xl border border-slate-100 shadow-sm gap-6 relative overflow-hidden">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-emerald-50 rounded-full blur-3xl opacity-70 pointer-events-none"></div>
        <div class="relative z-10 max-w-2xl">
          <h3 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <span class="bg-emerald-600 text-white p-2.5 rounded-xl shadow-md">📊</span> 
            Master Data & Rekapitulasi Pasien JPPK
          </h3>
          <p class="text-xs text-slate-500 font-medium mt-3 leading-relaxed">Pusat pelaporan dan pencatatan komprehensif data rekam medis pasien. Anda dapat mencari secara spesifik dan mengekspor seluruh tabel ini langsung menjadi dokumen Excel yang rapi dan terstruktur.</p>
        </div>

        <div class="relative z-10 flex flex-col sm:flex-row gap-4 w-full xl:w-auto shrink-0">
          <div class="relative w-full sm:w-64 xl:w-72">
            <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">🔍</span>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Filter data pasien..." 
              class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 transition-all shadow-inner"
            >
          </div>
          
          <button 
            @click="prosesExportExcel" 
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-6 py-3 rounded-2xl shadow-lg hover:shadow-emerald-500/30 transition-all duration-300 flex items-center justify-center gap-2"
          >
            📥 Unduh Excel <span class="bg-emerald-700 text-emerald-100 px-2 py-0.5 rounded-lg ml-1">{{ filteredPeserta.length }}</span>
          </button>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col relative z-10">
        <div class="overflow-x-auto p-1">
          <table class="w-full text-left border-collapse text-[11px]">
            <thead class="bg-slate-50 border-b border-slate-200 font-black text-slate-700 uppercase tracking-widest text-[9px]">
              <tr>
                <th class="p-4 rounded-tl-2xl">No JPPK</th>
                <th class="p-4 text-emerald-700 bg-emerald-50/50">No RM</th>
                <th class="p-4">NPP</th>
                <th class="p-4">Nama Lengkap Pasien</th>
                <th class="p-4">Status & Penanggung</th>
                <th class="p-4 text-center">Gender</th>
                <th class="p-4">Kontak & Tgl Lahir</th>
                <th class="p-4">Unit & Divisi</th>
                <th class="p-4 rounded-tr-2xl text-center">Status AI</th>
              </tr>
            </thead>
            <tbody class="text-slate-600">
              <tr v-if="isLoading" v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="9" class="p-4 border-b border-slate-50">
                  <div class="h-8 bg-slate-100 rounded-xl w-full"></div>
                </td>
              </tr>

              <tr v-else v-for="p in paginatedPeserta" :key="p.no_jppk" class="border-b border-slate-50 hover:bg-slate-50/80 transition-colors">
                <td class="p-4 font-black text-slate-900 bg-slate-50/30 whitespace-nowrap">{{ p.no_jppk }}</td>
                <td class="p-4 font-black text-emerald-600 bg-emerald-50/30">{{ p.no_rm || '-' }}</td>
                <td class="p-4 font-mono font-bold text-slate-600">{{ p.npp }}</td>
                <td class="p-4 font-black text-slate-800">{{ p.nama_peserta }}</td>
                <td class="p-4">
                  <span class="block font-bold text-blue-600 mb-0.5">{{ p.status || '-' }}</span>
                  <span class="text-[9px] text-slate-400">Penanggung: {{ p.nama_penanggung || '-' }}</span>
                </td>
                <td class="p-4 text-center">
                  <span class="px-2 py-1 rounded-md font-bold" :class="p.jenis_kelamin === 'L' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-600'">{{ p.jenis_kelamin }}</span>
                </td>
                <td class="p-4 whitespace-nowrap">
                  <span class="block text-slate-700 font-mono font-bold mb-0.5">{{ p.no_telp || '-' }}</span>
                  <span class="text-[9px] text-slate-400 font-medium">{{ p.tgl_lahir }}</span>
                </td>
                <td class="p-4">
                  <span class="block text-slate-800 font-bold mb-0.5 truncate max-w-[120px]">{{ p.divisi || '-' }}</span>
                  <div class="flex gap-1 mt-1">
                     <span class="text-[8px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-bold uppercase">U: {{ p.unit_id }}</span>
                     <span class="text-[8px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-bold uppercase">P: {{ p.plan_id }}</span>
                  </div>
                </td>
                <td class="p-4 text-center">
                  <span v-if="p.face_embedding" class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-600 font-bold px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Aktif
                  </span>
                  <span v-else class="inline-flex items-center gap-1 bg-slate-50 text-slate-400 font-bold px-2 py-1 rounded-lg">
                    <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span> Kosong
                  </span>
                </td>
              </tr>
              
              <tr v-if="!isLoading && filteredPeserta.length === 0">
                 <td colspan="9" class="text-center p-12 bg-slate-50/50">
                    <span class="text-3xl block mb-2 opacity-40">📂</span>
                    <h5 class="font-black text-slate-600 text-sm">Tidak ada data ditemukan</h5>
                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian Anda.</p>
                 </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredPeserta.length > 0" class="flex flex-col sm:flex-row items-center justify-between p-6 bg-slate-50 border-t border-slate-100 gap-4">
          <div class="text-[11px] text-slate-500 font-bold bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
            Menampilkan {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredPeserta.length) }} dari {{ filteredPeserta.length }} Pasien
          </div>
          
          <div class="flex items-center gap-2 bg-white p-1 rounded-xl border border-slate-200 shadow-sm">
            <button @click="currentPage--" :disabled="currentPage === 1" class="px-4 py-2 text-xs font-black text-slate-600 rounded-lg hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
              ← Prev
            </button>
            <div class="px-4 py-2 bg-emerald-50 text-emerald-700 font-black text-xs rounded-lg border border-emerald-100 shadow-inner">
              Hal {{ currentPage }} / {{ totalPages }}
            </div>
            <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-4 py-2 text-xs font-black text-slate-600 rounded-lg hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
              Next →
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const listSemua = ref([])
const searchQuery = ref('')
const isLoading = ref(true)

const currentPage = ref(1)
const itemsPerPage = 15

const fetchPasien = async () => {
  try {
    isLoading.value = true
    const res = await axios.get('/api/admin/peserta')
    listSemua.value = res.data.semua
  } catch (error) {
    console.error("Database gagal terkoneksi:", error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchPasien()
})

const filteredPeserta = computed(() => {
  if (!searchQuery.value) return listSemua.value
  const keyword = searchQuery.value.toLowerCase()
  return listSemua.value.filter(p => {
    return (
      (p.nama_peserta && p.nama_peserta.toLowerCase().includes(keyword)) ||
      (p.no_jppk && p.no_jppk.toLowerCase().includes(keyword)) ||
      (p.npp && p.npp.toLowerCase().includes(keyword)) ||
      (p.no_rm && p.no_rm.toLowerCase().includes(keyword))
    )
  })
})

const totalPages = computed(() => {
  return Math.ceil(filteredPeserta.value.length / itemsPerPage) || 1
})

const paginatedPeserta = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredPeserta.value.slice(start, end)
})

watch(searchQuery, () => { currentPage.value = 1 })

const prosesExportExcel = () => {
  if (filteredPeserta.value.length === 0) {
    return Swal.fire({
      icon: 'warning',
      title: 'Data Kosong',
      text: 'Tidak ada data pasien untuk diekspor!',
      confirmButtonColor: '#059669'
    })
  }

  const headers = ['NO JPPK', 'NO RM', 'NPP', 'NAMA LENGKAP PASIEN', 'STATUS', 'NAMA PENANGGUNG', 'GENDER', 'TANGGAL LAHIR', 'NO WHATSAPP', 'DIVISI UNIT', 'UNIT ID', 'PLAN ID', 'STATUS FACE AI']
  
  const rows = filteredPeserta.value.map(p => [
    p.no_jppk, p.no_rm || '-', p.npp, p.nama_peserta, p.status || '-', p.nama_penanggung || '-', 
    p.jenis_kelamin, p.tgl_lahir, p.no_telp || '-', p.divisi || '-', p.unit_id, p.plan_id,
    p.face_embedding ? '🟢 AKTIF (Terkunci)' : '🔴 BELUM AKTIF'
  ])
  
  const barisHeader = headers.join('\t')
  const barisData = rows.map(r => r.join('\t')).join('\n')
  const kontenMentahExcel = barisHeader + '\n' + barisData
  
  const blob = new Blob(['\uFEFF' + kontenMentahExcel], { type: 'application/vnd.ms-excel;charset=utf-8' })
  const linkUnduh = document.createElement('a')
  const urlSakti = URL.createObjectURL(blob)
  
  const tglHariIni = new Date().toISOString().slice(0, 10)
  linkUnduh.href = urlSakti
  linkUnduh.setAttribute('download', `REKAP_PASIEN_JPPK_PINDAD_${tglHariIni}.xls`)
  
  document.body.appendChild(linkUnduh)
  linkUnduh.click()
  document.body.removeChild(linkUnduh)
  
  Swal.fire({
      icon: 'success',
      title: 'Excel Berhasil Diunduh!',
      text: `Menyimpan ${filteredPeserta.value.length} baris data pasien.`,
      toast: true,
      position: 'bottom-end',
      showConfirmButton: false,
      timer: 3000
  })
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeInEffect 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes fadeInEffect {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
