<template>
  <AdminLayout>
    <div class="space-y-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-emerald-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>
        
        <div class="relative z-10">
          <h4 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <span class="bg-indigo-600 text-white p-2 rounded-xl shadow-md">🧑‍🤝‍🧑</span>
            Daftar Wajah Dikenal AI
          </h4>
          <p class="text-[12px] text-slate-500 mt-2 max-w-md">Pemantauan visual seluruh data pasien yang profil biometrik (Super DNA) telah terkunci dan aktif di dalam sistem RS Pindad.</p>
        </div>
        
        <div class="relative z-10 w-full sm:w-80">
          <div class="bg-indigo-50/50 p-1.5 rounded-2xl flex items-center shadow-inner border border-indigo-100/50">
            <span class="pl-3 pr-2 text-indigo-400">🔍</span>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Cari nama, RM, atau JPPK..." 
              class="w-full bg-transparent border-none text-xs font-bold text-slate-700 focus:ring-0 placeholder-indigo-300"
            >
          </div>
          <p class="text-[10px] text-right text-indigo-600 font-bold mt-2 pr-2">Total Terkunci: {{ wajahDikenalPeserta.length }} Pasien</p>
        </div>
      </div>

      <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="i in 8" :key="i" class="bg-white rounded-3xl border border-slate-100 h-80 animate-pulse">
           <div class="h-48 bg-slate-200 rounded-t-3xl"></div>
           <div class="p-4 space-y-3">
             <div class="h-4 bg-slate-200 rounded w-3/4"></div>
             <div class="h-3 bg-slate-200 rounded w-1/2"></div>
           </div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div 
          v-for="p in wajahDikenalPeserta" 
          :key="p.no_jppk" 
          class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group transform hover:-translate-y-1"
        >
          <div class="relative h-56 w-full bg-slate-100 overflow-hidden">
            <img 
              :src="`/api/dataset_wajah/${p.face_image_path ? p.face_image_path.replace('dataset_wajah/', '') : ''}`" 
              class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
              alt="Wajah Pasien" 
              @error="(e) => e.target.src='https://ui-avatars.com/api/?name=' + encodeURIComponent(p.nama_peserta) + '&background=random&color=fff&size=512'" 
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent opacity-80"></div>
            
            <div class="absolute top-4 right-4 bg-white/20 backdrop-blur-md text-white text-[9px] font-black px-2.5 py-1.5 rounded-lg shadow-lg uppercase tracking-widest flex items-center gap-1.5 border border-white/30">
              <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span> AI Aktif
            </div>

            <div class="absolute bottom-4 left-4 right-4">
              <h5 class="text-white font-black text-lg drop-shadow-md truncate tracking-tight">{{ p.nama_peserta }}</h5>
              <div class="flex items-center gap-2 mt-1">
                <span class="bg-indigo-500/80 backdrop-blur text-white text-[10px] font-bold px-2 py-0.5 rounded-md">{{ p.no_jppk }}</span>
                <span class="bg-slate-700/80 backdrop-blur text-slate-200 text-[10px] font-bold px-2 py-0.5 rounded-md">{{ p.jenis_kelamin }}</span>
              </div>
            </div>
          </div>
          
          <div class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <span class="block text-[10px] text-slate-400 font-bold mb-1 uppercase tracking-wider">No WhatsApp</span>
                <span class="text-slate-800 text-xs font-black truncate block bg-slate-50 px-2 py-1.5 rounded-lg border border-slate-100">{{ p.no_telp || '-' }}</span>
              </div>
              <div>
                <span class="block text-[10px] text-slate-400 font-bold mb-1 uppercase tracking-wider">Status Pasien</span>
                <span class="text-indigo-600 text-xs font-black truncate block bg-indigo-50 px-2 py-1.5 rounded-lg border border-indigo-100">{{ p.status || '-' }}</span>
              </div>
            </div>
            
            <div class="flex gap-2">
              <button 
                @click="bukaModalRegistrasiMuka(p)" 
                class="flex-1 py-2.5 bg-emerald-50 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-xl text-xs font-black transition-colors duration-300 shadow-sm border border-emerald-100 hover:border-transparent flex items-center justify-center gap-1.5"
              >
                <span>📷</span> Edit Wajah
              </button>
              
              <button 
                @click="hapusWajah(p.no_jppk)" 
                class="flex-1 py-2.5 bg-rose-50 hover:bg-rose-500 text-rose-600 hover:text-white rounded-xl text-xs font-black transition-colors duration-300 shadow-sm border border-rose-100 hover:border-transparent flex items-center justify-center gap-1.5"
              >
                <span>♻️</span> Reset AI
              </button>
            </div>
          </div>
        </div>
        
        <div v-if="wajahDikenalPeserta.length === 0" class="col-span-full py-24 bg-white rounded-3xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-center shadow-sm">
          <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
            <span class="text-4xl opacity-50">🤷‍♂️</span>
          </div>
          <h5 class="text-slate-700 font-black text-lg">Belum Ada Wajah Dikenal</h5>
          <p class="text-xs text-slate-400 mt-2 max-w-sm font-medium leading-relaxed">Cari data di tab Kelola & Kamera AI, lalu Daftarkan wajah pasien untuk memunculkannya di galeri ini.</p>
        </div>
      </div>
    </div>


    <Teleport to="body">
      <div v-if="modalMukaOpen" class="modal-overlay-paksa">
        <div class="modal-content-paksa" style="max-width: 400px !important;">
          <button @click="tutupModalMuka" class="absolute top-4 right-4 text-slate-400 hover:text-rose-500 bg-slate-100 hover:bg-rose-50 rounded-full w-8 h-8 flex items-center justify-center transition-all font-bold z-50">
            ✕
          </button>

          <div class="text-center mb-4">
            <h4 class="font-black text-slate-800 text-lg">Pindai Biometrik Baru</h4>
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
              Selesai & Perbarui Biometrik
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAdminDaftarWajah } from '../Composables/useAdminDaftarWajah'

const {
  listSemua,
  searchQuery,
  isLoading,
  wajahDikenalPeserta,
  fetchPasien,
  hapusWajah,
  modalMukaOpen,
  formMuka,
  isCameraOpen,
  isRecording,
  isVideoReady,
  videoRef,
  countdown,
  teksPemandu,
  bukaModalRegistrasiMuka,
  matikanKamera,
  tutupModalMuka,
  aktifkanKamera,
  rekamVideoSuperDNA,
  handleUploadFoto,
  simpanRegistrasiMuka
} = useAdminDaftarWajah()

onMounted(() => {
  fetchPasien()
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

/* Animasi Cincin CSS Murni (Berjalan tepat 10 detik) */
.animate-face-id-fill {
  animation: faceIdFill 10s linear forwards;
}
@keyframes faceIdFill {
  0% { stroke-dashoffset: 302; }
  100% { stroke-dashoffset: 0; }
}

.animate-fade-in {
  animation: fadeInEffect 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes fadeInEffect {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
