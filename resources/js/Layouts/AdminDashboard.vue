<template>
  <AdminLayout>
    <div class="space-y-6 animate-fade-in">
      
      <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between bg-white p-6 rounded-2xl border border-slate-100 shadow-sm gap-4">
        <div>
          <h3 class="text-lg font-black text-slate-800 tracking-tight">Pusat Data Pasien JPPK</h3>
          <p class="text-[11px] text-slate-400 font-medium mt-0.5">Manajemen Sinkronisasi Face Recognition FaceNet & WhatsApp RS Pindad</p>
        </div>

      </div>

      <div class="space-y-4 animate-fade-in">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-white p-4 rounded-xl border border-slate-100 shadow-sm gap-4">
          <div class="flex flex-col sm:flex-row sm:items-center gap-4 w-full">
            <div class="text-sm font-black text-slate-700 ml-2 whitespace-nowrap shrink-0">
              Total Pasien: <span class="text-emerald-600">{{ filteredPeserta.length }}</span>
            </div>
            
            <div class="relative w-full sm:max-w-xs">
              <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">🔍</span>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Cari Nama, No JPPK, atau RM..." 
                class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-emerald-200 transition-all shadow-inner"
              >
            </div>
          </div>
          
          <button @click="bukaModalTambah()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-sm transition-all flex items-center gap-1.5 shrink-0 justify-center w-full sm:w-auto">
            <span>+</span> Daftarkan Pasien
          </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-50 border-b border-slate-100 font-bold text-slate-500">
                <tr>
                  <th class="p-3">No JPPK</th>
                  <th class="p-3 text-emerald-700">No RM</th>
                  <th class="p-3">NPP</th>
                  <th class="p-3">Nama Lengkap</th>
                  <th class="p-3">Status & Penanggung</th>
                  <th class="p-3 text-center">Gender</th>
                  <th class="p-3">Status Biometrik</th>
                  <th class="p-3 text-center">Aksi (AI & Data)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in paginatedPeserta" :key="p.no_jppk" class="border-b border-slate-50 hover:bg-slate-50/50">
                  <td class="p-3 font-bold text-slate-700">{{ p.no_jppk }}</td>
                  <td class="p-3 font-bold text-emerald-600 bg-emerald-50/30">{{ p.no_rm || '-' }}</td>
                  <td class="p-3 text-slate-600 font-mono">{{ p.npp }}</td>
                  <td class="p-3 font-medium text-slate-800">{{ p.nama_peserta }}</td>
                  <td class="p-3">
                    <div class="flex flex-col">
                      <span class="font-bold text-blue-700">{{ p.status || '-' }}</span>
                      <span class="text-[10px] text-slate-400 mt-0.5">Penanggung: {{ p.nama_penanggung || '-' }}</span>
                    </div>
                  </td>
                  <td class="p-3 font-bold text-center">{{ p.jenis_kelamin }}</td>
                  <td class="p-3">
                    <span v-if="p.face_embedding" class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-bold text-[10px]">🟢 AI Terkunci</span>
                    <span v-else class="bg-rose-50 text-rose-600 px-2 py-0.5 rounded-md font-bold text-[10px]">🔴 Belum Aktif</span>
                  </td>
                  <td class="p-3 text-center space-x-3 whitespace-nowrap">
                    <button v-if="p.face_embedding" @click="hapusWajah(p.no_jppk)" class="text-amber-600 font-bold hover:underline">Reset AI</button>
                    <button v-else @click="bukaModalRegistrasiMuka(p)" class="text-emerald-600 font-bold hover:underline">📸 Rekam Wajah</button>
                    
                    <span class="text-slate-300">|</span>

                    <button @click="bukaModalEdit(p)" class="text-blue-600 font-bold hover:underline">Edit</button>
                    <button @click="hapusPeserta(p.no_jppk)" class="text-rose-600 font-bold hover:underline">Hapus</button>
                  </td>
                </tr>
                <tr v-if="filteredPeserta.length === 0">
                  <td colspan="8" class="text-center p-8 text-slate-500 font-bold">
                    Data tidak ditemukan di sistem.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="filteredPeserta.length > 0" class="flex items-center justify-between border-t border-slate-100 pt-4 mt-4">
            <div class="text-[11px] text-slate-500 font-bold">
              Menampilkan {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredPeserta.length) }} dari {{ filteredPeserta.length }} Pasien
            </div>
            <div class="flex items-center gap-1.5">
              <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed transition-all">Sebelumnya</button>
              <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100">Halaman {{ currentPage }} / {{ totalPages }}</span>
              <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed transition-all">Selanjutnya</button>
            </div>
          </div>
        </div>
      </div>


    </div>

    <Teleport to="body">
      
      <div v-if="modalCrudOpen" class="modal-overlay-paksa">
        <div class="modal-content-paksa">
          <h4 class="font-black text-slate-800 text-base mb-4 shrink-0 border-b pb-2">{{ isEditMode ? 'Edit Data Pasien JPPK' : 'Tambah Pasien JPPK Baru' }}</h4>
          
          <div class="space-y-3 overflow-y-auto pr-2 flex-grow" style="max-height: 60vh;">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">No JPPK</label>
                <input v-model="formCrud.no_jppk" type="text" :disabled="isEditMode" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700 disabled:opacity-60 focus:outline-emerald-50">
              </div>
              <div>
                <label class="text-[11px] font-bold text-emerald-600 block mb-1">No RM</label>
                <input v-model="formCrud.no_rm" type="text" class="w-full px-4 py-2.5 bg-emerald-50/50 border border-emerald-100 rounded-xl text-xs font-bold text-slate-700 focus:outline-emerald-100">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">NPP</label>
                <input v-model="formCrud.npp" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
              </div>
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">Gender (L/P)</label>
                <select v-model="formCrud.jenis_kelamin" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
                  <option value="L">Laki-Laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Nama Lengkap Pasien</label>
              <input v-model="formCrud.nama_peserta" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">Status (Peserta/Istri/Anak)</label>
                <input v-model="formCrud.status" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
              </div>
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">Nama Penanggung</label>
                <input v-model="formCrud.nama_penanggung" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">Tanggal Lahir</label>
                <input v-model="formCrud.tgl_lahir" type="date" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
              </div>
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">No WhatsApp</label>
                <input v-model="formCrud.no_telp" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
              </div>
            </div>

            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Divisi Kerja</label>
              <input v-model="formCrud.divisi" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>
            
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">Unit</label>
                <select v-model="formCrud.unit_id" class="w-full px-4 py-2 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
                  <option v-for="unit in listUnits" :key="unit.id" :value="unit.id">{{ unit.nama_unit }}</option>
                </select>
              </div>
              <div>
                <label class="text-[11px] font-bold text-slate-400 block mb-1">Plan</label>
                <select v-model="formCrud.plan_id" class="w-full px-4 py-2 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
                  <option v-for="plan in listPlans" :key="plan.id" :value="plan.id">{{ plan.nama_plan }} ({{ plan.eselon_range }})</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-2 shrink-0 pt-3 border-t border-slate-100">
            <button @click="modalCrudOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200">Batal</button>
            <button @click="simpanAksiCrud" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700">Simpan Data</button>
          </div>
        </div>
      </div>

      <div v-if="modalMukaOpen" class="modal-overlay-paksa">
        <div class="modal-content-paksa" style="max-width: 400px !important;">
          <button @click="tutupModalMuka" class="absolute top-4 right-4 text-slate-400 hover:text-rose-500 bg-slate-100 hover:bg-rose-50 rounded-full w-8 h-8 flex items-center justify-center transition-all font-bold z-50">
            ✕
          </button>

          <div class="text-center mb-4">
            <h4 class="font-black text-slate-800 text-lg">Pindai Biometrik</h4>
            <p class="text-xs text-slate-500 font-medium">Pasien: <span class="text-emerald-600 font-bold">{{ formMuka.nama_peserta }}</span></p>
          </div>
          
          <div class="mt-2 space-y-5">
            <div>
              <input v-model="formMuka.no_telp" type="text" placeholder="No. WA Aktif: 0812XXXXXXXX" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-full text-xs font-bold text-slate-700 focus:outline-emerald-200 text-center shadow-inner">
            </div>
            
            <div class="relative w-56 h-56 mx-auto mb-2">
              <div class="w-full h-full rounded-full overflow-hidden shadow-2xl relative z-0 bg-slate-900 transition-all duration-300" :class="isRecording ? 'shadow-emerald-500/40 scale-[0.98]' : ''">
                <video v-show="isCameraOpen" ref="videoRef" autoplay playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                
                <div v-if="!isCameraOpen && !isVideoReady" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                  <span class="text-4xl block mb-1">🎥</span>
                  <p class="text-[10px] font-bold uppercase tracking-wider">Kamera Mati</p>
                </div>

                <div v-if="!isCameraOpen && isVideoReady" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/90 p-4 text-center">
                  <span class="text-4xl block mb-2">✅</span>
                  <p class="text-xs font-black text-emerald-500 uppercase tracking-wide">VIDEO SIAP</p>
                </div>
              </div>

              <svg v-if="isRecording" class="absolute inset-0 w-full h-full z-10 pointer-events-none" viewBox="0 0 100 100">
                <defs>
                  <mask id="faceid-mask">
                    <circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-width="6" stroke-dasharray="6 8" stroke-linecap="round" />
                  </mask>
                </defs>
                <circle cx="50" cy="50" r="48" fill="none" stroke="#e2e8f0" stroke-width="3" class="opacity-50" mask="url(#faceid-mask)" />
                <circle cx="50" cy="50" r="48" fill="none" stroke="#10b981" stroke-width="5" mask="url(#faceid-mask)" class="animate-face-id-fill" stroke-dasharray="302" stroke-dashoffset="302" transform="rotate(-90 50 50)" stroke-linecap="round" />
              </svg>
            </div>

            <div class="flex flex-col gap-2">
              <button type="button" @click="isCameraOpen ? matikanKamera() : aktifkanKamera()" :class="isCameraOpen ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white'" class="w-full py-2.5 font-bold text-xs rounded-full transition-all shadow-md text-center">
                {{ isCameraOpen ? '❌ Matikan Kamera' : '📷 1. Nyalakan Kamera' }}
              </button>
              
              <button v-if="isCameraOpen" type="button" @click="rekamVideoSuperDNA" :disabled="isRecording" :class="isRecording ? 'bg-slate-900 text-emerald-400 font-bold border-emerald-500' : 'bg-rose-600 hover:bg-rose-700 text-white border-rose-400 animate-pulse'" class="w-full py-3 text-xs rounded-full transition-all shadow-lg text-center border-2">
                <span v-if="isRecording" class="flex items-center justify-center gap-2">
                  <span class="animate-ping w-2 h-2 rounded-full bg-emerald-400 absolute left-8"></span>
                  {{ teksPemandu }} ({{ countdown }}s)
                </span>
                <span v-else>🎥 2. Mulai Pindai Kepala</span>
              </button>
            </div>
            
            <div class="relative flex py-1 items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-slate-400 font-bold text-[9px] uppercase tracking-widest">Atau Manual Upload</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
            <input @change="handleUploadFoto" type="file" accept="video/*,image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-emerald-50 file:text-emerald-700 cursor-pointer hover:file:bg-emerald-100 transition-all text-center" />
          </div>

          <div class="mt-5 flex justify-center gap-2 pt-4 border-t border-slate-100">
            <button @click="simpanRegistrasiMuka" :disabled="!isVideoReady" :class="!isVideoReady ? 'bg-slate-200 text-slate-400 cursor-not-allowed w-full' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md w-full'" class="py-3 text-sm font-black rounded-full transition-all uppercase tracking-wide">
              Selesai & Simpan Data Biometrik
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAdminDashboard } from '../Composables/useAdminDashboard'

const {
  menuUtama,
  listSemua,
  listUnits,
  listPlans,
  searchQuery,
  currentPage,
  itemsPerPage,
  filteredPeserta,
  totalPages,
  paginatedPeserta,
  wajahDikenalPeserta,
  modalCrudOpen,
  isEditMode,
  modalMukaOpen,
  formCrud,
  formMuka,
  videoRef,
  isCameraOpen,
  kameraStream,
  isRecording,
  isVideoReady,
  teksPemandu,
  countdown,
  fetchPasien,
  fetchRefData,
  prosesExportExcel,
  bukaModalTambah,
  bukaModalEdit,
  simpanAksiCrud,
  hapusWajah,
  hapusPeserta,
  bukaModalRegistrasiMuka,
  matikanKamera,
  tutupModalMuka,
  aktifkanKamera,
  rekamVideoSuperDNA,
  handleUploadFoto,
  simpanRegistrasiMuka
} = useAdminDashboard()

onMounted(async () => {
  await fetchPasien()
  await fetchRefData()
})
</script>

<style scoped>
.modal-overlay-paksa {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  background-color: rgba(15, 23, 42, 0.75) !important;
  z-index: 1050 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  backdrop-filter: blur(4px) !important;
}

.modal-content-paksa {
  background-color: #ffffff !important;
  border-radius: 24px !important;
  width: 100% !important;
  max-width: 440px !important;
  padding: 24px !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
  position: relative !important;
  margin: 0 16px !important;
  display: flex !important;
  flex-direction: column !important;
  max-height: 90vh !important;
}

.animate-fade-in {
  animation: fadeInEffect 0.35s ease-out;
}
@keyframes fadeInEffect {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

/* 🔥 Animasi Cincin CSS Murni (Berjalan tepat 10 detik) 🔥 */
.animate-face-id-fill {
  animation: faceIdFill 10s linear forwards;
}
@keyframes faceIdFill {
  0% { stroke-dashoffset: 302; }
  100% { stroke-dashoffset: 0; }
}
</style>