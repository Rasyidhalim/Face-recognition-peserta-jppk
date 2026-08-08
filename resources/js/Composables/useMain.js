import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export function useMain() {
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
            if (dikenali.is_lively) {
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
      autoDetectInterval = setInterval(scanFrame, 800); 
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

  return {
    videoElement,
    canvasElement,
    isScanning,
    isAutoDetecting,
    cameraActive,
    userData,
    scale,
    results,
    listPoliklinik,
    selectedPoli,
    listDokter,
    selectedDokter,
    searchQueryPoli,
    filteredPoliklinik,
    startCamera,
    calculateScale,
    scanFrame,
    toggleScanning,
    drawnBoxes,
    fetchPoliklinik,
    pilihPoliklinik,
    finishRegistration,
    mediaStream,
    autoDetectInterval
  }
}
