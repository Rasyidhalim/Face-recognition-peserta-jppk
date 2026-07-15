<template>
  <div class="h-full bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row border border-slate-200">
    
    <div class="w-full md:w-1/2 bg-emerald-950 p-8 flex flex-col items-center justify-center relative">
      <div class="mb-6 text-center z-10">
        <h2 class="text-white text-2xl font-bold tracking-tight">Verifikasi Wajah Peserta</h2>
        <p class="text-emerald-400 text-sm mt-1">Posisikan wajah tepat di tengah layar</p>
      </div>

      <div class="relative w-full max-w-sm bg-black rounded-3xl overflow-hidden border-2 border-emerald-800 shadow-2xl">
        <video 
          ref="videoElement" 
          autoplay 
          playsinline 
          muted 
          @loadedmetadata="calculateScale"
          class="w-full h-auto block"
        ></video>
        
        <canvas ref="canvasElement" class="hidden"></canvas>

        <div
          v-for="(box, index) in drawnBoxes"
          :key="index"
          :style="{
            position: 'absolute',
            border: `3px solid ${box.label === 'TIDAK DIKENAL' ? '#FF3333' : '#34D399'}`,
            left: box.x + 'px',
            top: box.y + 'px',
            width: box.width + 'px',
            height: box.height + 'px',
            pointerEvents: 'none',
            transition: 'all 0.15s ease-out',
            zIndex: 25
          }"
        >
          <div :style="{
            background: box.label === 'TIDAK DIKENAL' ? '#FF3333' : '#34D399', 
            color: box.label === 'TIDAK DIKENAL' ? 'white' : '#064E3B', 
            fontSize: '12px', 
            fontWeight: 'bold', 
            padding: '4px 8px', 
            position: 'absolute', 
            top: '0px', 
            left: '0px', 
            whiteSpace: 'nowrap',
            borderRadius: '0 0 8px 0',
            display: 'flex',
            flexDirection: 'column'
          }">
            <span>{{ box.label === 'TIDAK DIKENAL' ? 'TIDAK DIKENAL' : 'DIKENALI' }}</span>
            <span style="font-size: 9px; font-weight: normal; margin-top: 2px;">Acc: {{ box.confidence ? (box.confidence * 100).toFixed(1) : 0 }}% | Thr: 70%</span>
          </div>
        </div>
        
        <div v-if="!cameraActive" class="absolute inset-0 bg-emerald-900/80 flex flex-col items-center justify-center text-emerald-500 z-10">
          <p class="text-sm font-medium animate-pulse">Menyalakan Kamera...</p>
        </div>
        
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
           <div class="w-56 h-56 border-4 border-dashed border-emerald-400/50 rounded-full"></div>
        </div>

        <div v-if="isScanning" class="absolute top-0 left-0 w-full h-1.5 bg-emerald-400 shadow-[0_0_20px_rgba(52,211,153,1)] animate-scan z-30"></div>
      </div>

      <button @click="toggleScanning" :disabled="!cameraActive"
        :class="isAutoDetecting ? 'bg-orange-500 hover:bg-orange-600' : 'bg-emerald-500 hover:bg-emerald-600'"
        class="mt-8 px-10 py-4 disabled:bg-slate-800 disabled:text-slate-500 text-white rounded-full font-black text-lg transition-all shadow-xl active:scale-95 z-10">
        {{ isAutoDetecting ? '⏹️ BATALKAN SCAN' : '▶️ MULAI SCAN WAJAH' }}
      </button>
    </div>

    <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-start bg-white overflow-y-auto">
      <div class="flex items-center justify-between mb-10">
        <div>
          <h1 class="text-3xl font-black text-emerald-950 tracking-tighter">RS Pindad<span class="text-emerald-500">.</span></h1>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em]">Loket Mandiri</p>
        </div>
        <img src="/logo-rs-pindad.jpg" alt="Logo RS Pindad" class="w-12 h-12 object-contain" />
      </div>

      <div v-if="!userData" class="flex-1 flex flex-col items-center justify-center text-center space-y-4">
        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 border-2 border-dashed border-slate-200">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
        <p class="text-lg font-bold text-slate-400">Silakan Mulai Scan Wajah</p>
      </div>

      <div v-else class="space-y-6 animate-fade-in-up">
        
        <div class="bg-emerald-50 border-2 border-emerald-100 p-6 rounded-[2rem]">
          <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest mb-2">Peserta Terverifikasi ✅</p>
          <h2 class="text-3xl font-black text-slate-800 leading-tight">{{ userData.nama }}</h2>
          <div class="mt-3 flex gap-2 flex-wrap">
            <span class="px-3 py-1 bg-white text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">No JPPK: {{ userData.no_jppk }}</span>
            <span class="px-3 py-1 bg-white text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">Unit: {{ userData.unit }}</span>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="text-lg font-black text-slate-700 flex items-center">
              <span class="w-2 h-6 bg-orange-500 rounded-full mr-3"></span>Pilih Poliklinik
            </h3>
            
            <div class="relative w-full sm:w-1/2">
              <input 
                v-model="searchQueryPoli" 
                type="text" 
                placeholder="Cari poli..." 
                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:border-emerald-500 transition-all text-slate-700"
              >
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
          </div>

          <div v-if="filteredPoliklinik.length === 0" class="text-center py-4 bg-slate-50 rounded-xl border border-slate-100">
            <p class="text-xs font-bold text-slate-400">Maaf, Poliklinik tidak tersedia hari ini.</p>
          </div>

          <div class="grid grid-cols-2 gap-3 max-h-48 overflow-y-auto pr-1 pb-1">
            <button v-for="poli in filteredPoliklinik" :key="poli.id"
              @click="pilihPoliklinik(poli)"
              :class="selectedPoli?.id === poli.id ? 'bg-orange-500 text-white border-orange-500 scale-[1.02] shadow-lg' : 'bg-white text-slate-600 border-slate-200'"
              class="py-4 px-2 rounded-2xl text-[11px] font-black transition-all border-2 text-center uppercase tracking-tighter">
              {{ poli.nama_poli }}
            </button>
          </div>

          <div v-if="selectedPoli" class="space-y-3 mt-6 animate-fade-in-up">
            <h3 class="text-lg font-black text-slate-700 flex items-center">
              <span class="w-2 h-6 bg-emerald-500 rounded-full mr-3"></span>Pilih Dokter & Jam Praktek
            </h3>
            
            <p v-if="listDokter.length === 0" class="text-sm text-slate-400 italic">Tidak ada jadwal dokter untuk poliklinik ini hari ini.</p>
            
            <div v-else class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto pr-1">
              <button v-for="dokter in listDokter" :key="dokter.dokter_id"
                @click="selectedDokter = dokter"
                :disabled="dokter.status_loket !== 'BUKA'"
                :class="[
                  selectedDokter?.dokter_id === dokter.dokter_id ? 'bg-emerald-600 text-white border-emerald-600 shadow-md' : 'bg-white text-slate-700 border-slate-200',
                  dokter.status_loket !== 'BUKA' ? 'opacity-50 bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed' : 'hover:border-emerald-300'
                ]"
                class="p-4 rounded-2xl border-2 text-left transition-all flex justify-between items-center">
                <div>
                  <p class="font-black text-sm">{{ dokter.nama_dokter }}</p>
                  <p class="text-xs font-medium opacity-80">{{ dokter.hari }} | {{ dokter.jam_mulai.substring(0,5) }} - {{ dokter.jam_selesai.substring(0,5) }} WIB</p>
                  <p class="text-[10px] mt-0.5 font-bold">Kuota: {{ dokter.kuota_terisi }}/{{ dokter.kuota_maksimal }}</p>
                </div>
                <span :class="dokter.status_loket === 'BUKA' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'" class="text-[10px] font-black px-2 py-1 rounded-md">
                  {{ dokter.status_loket }}
                </span>
              </button>
            </div>
          </div>

          <button v-if="selectedPoli && selectedDokter" @click="finishRegistration" class="w-full mt-4 py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black text-xl shadow-xl transition-all active:scale-95">
            DAFTAR KE {{ selectedPoli.nama_poli.toUpperCase() }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// STATE UTUK KAMERA & DATA
const videoElement = ref(null);
const canvasElement = ref(null);

const isScanning = ref(false);
const isAutoDetecting = ref(false);
const cameraActive = ref(false);

const userData = ref(null);
const scale = ref({ x: 1, y: 1 });
const results = ref(null);

const listPoliklinik = ref([]);
const selectedPoli = ref(null);
const listDokter = ref([]);
const selectedDokter = ref(null);

// STATE UNTUK SEARCH POLI
const searchQueryPoli = ref('');

let mediaStream = null;
let autoDetectInterval = null;

// ==========================================
// FILTER POLIKLINIK BERDASARKAN SEARCH
// ==========================================
const filteredPoliklinik = computed(() => {
  if (!searchQueryPoli.value) return listPoliklinik.value;
  return listPoliklinik.value.filter(poli => 
    poli.nama_poli.toLowerCase().includes(searchQueryPoli.value.toLowerCase())
  );
});

// ==========================================
// 1. LOGIKA KAMERA & AI
// ==========================================
const startCamera = async () => {
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 1280, height: 720 } });
    if (videoElement.value) {
      videoElement.value.srcObject = mediaStream;
      cameraActive.value = true;
    }
  } catch (err) {
    Swal.fire({
      icon: 'warning',
      title: 'Kamera Tidak Terdeteksi',
      text: 'Izin kamera diperlukan untuk fitur pengenalan wajah!',
      confirmButtonColor: '#059669'
    });
  }
};

const calculateScale = () => {
  if (videoElement.value && videoElement.value.videoWidth > 0) {
    scale.value.x = videoElement.value.clientWidth / videoElement.value.videoWidth;
    scale.value.y = videoElement.value.clientHeight / videoElement.value.videoHeight;
  }
};

const scanFrame = () => {
  if (!videoElement.value || !canvasElement.value || isScanning.value || userData.value) return;

  isScanning.value = true;

  const video = videoElement.value;
  const canvas = canvasElement.value;
  const context = canvas.getContext('2d');

  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  context.drawImage(video, 0, 0, canvas.width, canvas.height);

  canvas.toBlob(async (blob) => {
    const formData = new FormData();
    formData.append('image', new File([blob], "scan.jpg", { type: "image/jpeg" }));

    try {
      const response = await axios.post('http://127.0.0.1:8001/recognize', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      
      results.value = response.data;

      if (response.data && response.data.data) {
        const dikenali = response.data.data.find(wajah => wajah.label !== 'TIDAK DIKENAL');
        
        if (dikenali && dikenali.info_pindad) {
          userData.value = {
            nama: dikenali.info_pindad.nama_peserta, 
            no_jppk: dikenali.label, 
            unit: dikenali.info_pindad.nama_unit
          };
          
          clearInterval(autoDetectInterval);
          isAutoDetecting.value = false;
          // Mempertahankan results.value agar bounding box wajah yang dikenal tetap tampil
        }
      }

    } catch (error) {
      console.error("Gagal koneksi ke API Python:", error);
    } finally {
      isScanning.value = false;
    }
  }, 'image/jpeg', 0.8);
};

const toggleScanning = () => {
  if (isAutoDetecting.value) {
    clearInterval(autoDetectInterval);
    isAutoDetecting.value = false;
    results.value = null;
    userData.value = null;
    selectedPoli.value = null;
    selectedDokter.value = null;
    searchQueryPoli.value = ''; 
  } else {
    userData.value = null; 
    selectedPoli.value = null;
    selectedDokter.value = null;
    searchQueryPoli.value = ''; 
    isAutoDetecting.value = true;
    
    scanFrame(); 
    autoDetectInterval = setInterval(scanFrame, 1500); 
  }
};

const drawnBoxes = computed(() => {
  if (!results.value || !results.value.data) return [];
  calculateScale(); 
  return results.value.data.map(item => {
    const [x1, y1, x2, y2] = item.box;
    return {
      label: item.label,
      confidence: item.confidence,
      x: x1 * scale.value.x,
      y: y1 * scale.value.y,
      width: (x2 - x1) * scale.value.x,
      height: (y2 - y1) * scale.value.y
    };
  });
});

// ==========================================
// 2. LOGIKA POLI & DOKTER
// ==========================================
const fetchPoliklinik = async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/kiosk/poliklinik');
    listPoliklinik.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil poliklinik dari DB:", error);
  }
};

const pilihPoliklinik = async (poli) => {
  selectedPoli.value = poli;
  selectedDokter.value = null; 
  listDokter.value = [];       

  try {
    const response = await axios.get(`http://127.0.0.1:8000/api/kiosk/dokter/${poli.id}`);
    listDokter.value = response.data;
  } catch (error) {
    console.error("Gagal memuat jadwal dokter:", error);
  }
};

const finishRegistration = async () => {
  if (!selectedPoli.value || !selectedDokter.value) return;

  const konfirmasi = await Swal.fire({
    title: 'Konfirmasi Pendaftaran',
    html: `Anda akan mendaftar ke poli <b>${selectedPoli.value.nama_poli}</b> dengan dokter <b>${selectedDokter.value.nama_dokter}</b>.<br><br>Lanjutkan?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#ef4444', 
    confirmButtonText: 'Ya, Daftar Sekarang!',
    cancelButtonText: 'Batal'
  });

  if (!konfirmasi.isConfirmed) return;

  Swal.fire({
    title: 'Memproses Pendaftaran...',
    text: 'Sedang menyimpan data ke server',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const response = await axios.post('http://127.0.0.1:8000/api/daftar-antrian', {
      no_jppk: userData.value.no_jppk, 
      jadwal_dokter_id: selectedDokter.value.jadwal_dokter_id 
    });

    if (response.data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'PENDAFTARAN BERHASIL!',
        html: `
          <div class="text-left mt-4 text-sm text-slate-600 space-y-1">
            <p><b>Nama Pasien:</b> ${userData.value.nama}</p>
            <p><b>Poliklinik:</b> ${selectedPoli.value.nama_poli}</p>
            <p><b>Nama Dokter:</b> ${selectedDokter.value.nama_dokter}</p>
            
            <div class="mt-5 bg-emerald-50 p-4 rounded-xl border border-emerald-200 text-center">
              <p class="text-xs text-emerald-600 font-bold uppercase tracking-widest">No Antrian Anda</p>
              <p class="text-4xl font-black text-emerald-800 mt-1">${response.data.antrian}</p>
            </div>
          </div>
        `,
        confirmButtonColor: '#059669',
        confirmButtonText: 'Selesai'
      });
      
      // Reset State
      userData.value = null;
      selectedPoli.value = null;
      selectedDokter.value = null;
      listDokter.value = [];
      searchQueryPoli.value = '';
    }
    
  } catch (error) {
    console.error("Detail Error Pendaftaran:", error.response);
    Swal.fire({
      icon: 'error',
      title: 'Pendaftaran Gagal',
      text: error.response?.data?.message || "Server tidak merespon. Silakan coba lagi.",
      confirmButtonColor: '#ef4444'
    });
  }
};

// ==========================================
// LIFECYCLE VUE
// ==========================================
onMounted(() => {
  startCamera();
  fetchPoliklinik(); 
  window.addEventListener('resize', calculateScale);
});

onBeforeUnmount(() => { 
  if (mediaStream) mediaStream.getTracks().forEach(t => t.stop()); 
  if (autoDetectInterval) clearInterval(autoDetectInterval);
  window.removeEventListener('resize', calculateScale);
});
</script>

<style>
@keyframes scan { 0% { top: 0; opacity: 0; } 50% { opacity: 1; } 100% { top: 100%; opacity: 0; } }
.animate-scan { animation: scan 2s infinite linear; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>