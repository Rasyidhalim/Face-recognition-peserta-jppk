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
          <span :style="{
            background: box.label === 'TIDAK DIKENAL' ? '#FF3333' : '#34D399', 
            color: box.label === 'TIDAK DIKENAL' ? 'white' : '#064E3B', 
            fontSize: '12px', 
            fontWeight: 'bold', 
            padding: '4px 8px', 
            position: 'absolute', 
            top: '0px', 
            left: '0px', 
            whiteSpace: 'nowrap',
            borderRadius: '0 0 8px 0'
          }">
            {{ box.label }}
          </span>
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
          <h3 class="text-lg font-black text-slate-700 flex items-center">
            <span class="w-2 h-6 bg-orange-500 rounded-full mr-3"></span>Pilih Poliklinik Tujuan
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <button v-for="poli in ['Poli Umum', 'Poli Gigi', 'Poli Mata', 'Poli Penyakit Dalam', 'Poli Anak']" :key="poli"
              @click="selectedPoli = poli"
              :class="selectedPoli === poli ? 'bg-orange-500 text-white border-orange-500 scale-[1.02] shadow-lg' : 'bg-white text-slate-600 border-slate-200'"
              class="py-4 px-2 rounded-2xl text-[11px] font-black transition-all border-2 text-center uppercase tracking-tighter">
              {{ poli }}
            </button>
          </div>

          <button v-if="selectedPoli" @click="finishRegistration" class="w-full mt-4 py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black text-xl shadow-xl transition-all active:scale-95">
            DAFTAR KE {{ selectedPoli.toUpperCase() }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

// ==========================================
// STATE UNTUK KAMERA & PENDAFTARAN
// ==========================================
const videoElement = ref(null);
const canvasElement = ref(null);

const isScanning = ref(false);
const isAutoDetecting = ref(false);
const cameraActive = ref(false);

const userData = ref(null);
const selectedPoli = ref(null);
const results = ref(null);
const scale = ref({ x: 1, y: 1 });

let mediaStream = null;
let autoDetectInterval = null;

// 1. Inisialisasi Kamera
const startCamera = async () => {
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 1280, height: 720 } });
    if (videoElement.value) {
      videoElement.value.srcObject = mediaStream;
      cameraActive.value = true;
    }
  } catch (err) {
    alert("Izin kamera diperlukan untuk fitur pengenalan wajah!");
  }
};

// 2. Kalkulasi Skala Resolusi
const calculateScale = () => {
  if (videoElement.value && videoElement.value.videoWidth > 0) {
    scale.value.x = videoElement.value.clientWidth / videoElement.value.videoWidth;
    scale.value.y = videoElement.value.clientHeight / videoElement.value.videoHeight;
  }
};

// 3. Eksekusi Pengiriman Gambar ke FastAPI Python
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
          results.value = null; 
        }
      }

    } catch (error) {
      console.error("Gagal koneksi ke API Python:", error);
    } finally {
      isScanning.value = false;
    }
  }, 'image/jpeg', 0.8);
};

// 4. Kontrol Tombol Mulai/Berhenti Scan
const toggleScanning = () => {
  if (isAutoDetecting.value) {
    clearInterval(autoDetectInterval);
    isAutoDetecting.value = false;
    results.value = null;
    userData.value = null;
    selectedPoli.value = null;
  } else {
    userData.value = null; 
    selectedPoli.value = null;
    isAutoDetecting.value = true;
    
    scanFrame(); 
    autoDetectInterval = setInterval(scanFrame, 1500); 
  }
};

// 5. Menggambar Kotak AI di Layar
const drawnBoxes = computed(() => {
  if (!results.value || !results.value.data) return [];
  calculateScale(); 
  return results.value.data.map(item => {
    const [x1, y1, x2, y2] = item.box;
    return {
      label: item.label,
      x: x1 * scale.value.x,
      y: y1 * scale.value.y,
      width: (x2 - x1) * scale.value.x,
      height: (y2 - y1) * scale.value.y
    };
  });
});

// 6. Selesai Pendaftaran & Kirim WA
const finishRegistration = async () => {
  if (!selectedPoli.value) return;

  try {
    // Menembak API Laravel (Port 8000)
    // Data yang dikirim: { no_jppk, poli }
    const response = await axios.post('http://127.0.0.1:8000/api/daftar-antrian', {
      no_jppk: userData.value.no_jppk, 
      poli: selectedPoli.value
    });

    // Cek respon sukses dari server
    if (response.data.status === 'success') {
      
      // --- PERUBAHAN DI SINI Mang ---
      // Kita baca key 'poli' dari Laravel (response.data.poli)
      alert(`✅ PENDAFTARAN BERHASIL!\n\nNama: ${userData.value.nama}\nPoli: ${response.data.poli}\nNo Antrian: ${response.data.antrian}`);
      // --------------------------------
      
      // Reset Tampilan
      userData.value = null;
      selectedPoli.value = null;
    }
    
  } catch (error) {
    console.error("Detail Error:", error.response);
    alert("❌ Gagal daftar: " + (error.response?.data?.message || "Server tidak merespon"));
  }
};

// ==========================================
// LIFECYCLE VUE
// ==========================================
onMounted(() => {
  startCamera();
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
</style>