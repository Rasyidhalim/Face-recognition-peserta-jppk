import { ref, computed, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

export function useAdminDaftarWajah() {
  const listSemua = ref([])
  const searchQuery = ref('')
  const isLoading = ref(true)

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

  const wajahDikenalPeserta = computed(() => {
    let filtered = listSemua.value.filter(p => p.face_embedding !== null && p.face_embedding !== '' && p.face_image_path !== null)
    
    if (searchQuery.value) {
      const keyword = searchQuery.value.toLowerCase()
      filtered = filtered.filter(p => {
        return (
          (p.nama_peserta && p.nama_peserta.toLowerCase().includes(keyword)) ||
          (p.no_jppk && p.no_jppk.toLowerCase().includes(keyword)) ||
          (p.no_rm && p.no_rm.toLowerCase().includes(keyword))
        )
      })
    }
    
    return filtered
  })

  const hapusWajah = async (no_jppk) => {
    const konfirmasi = await Swal.fire({
      title: 'Reset Biometrik AI?', text: `Yakin ingin mereset wajah pasien ${no_jppk}? Pasien akan membutuhkan perekaman wajah ulang.`,
      icon: 'warning', showCancelButton: true, confirmButtonColor: '#f59e0b', cancelButtonColor: '#64748b', confirmButtonText: 'Ya, Reset AI!'
    });

    if (konfirmasi.isConfirmed) {
      Swal.fire({ title: 'Mereset Wajah...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
      try {
        const res = await axios.delete(`/api/peserta/hapus-wajah/${no_jppk}`)
        fetchPasien()
        Swal.fire({ icon: 'success', title: 'Berhasil Reset!', text: res.data.message, confirmButtonColor: '#059669' })
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Gagal Mereset Wajah', text: error.response?.data?.message || error.message, confirmButtonColor: '#ef4444' })
      }
    }
  }

  // === LOGIKA KAMERA & UPLOAD BIOMETRIK ===
  const modalMukaOpen = ref(false)
  const formMuka = ref({ no_jppk: '', nama_peserta: '', no_telp: '', face_image_path: '' })
  const isCameraOpen = ref(false)
  const isRecording = ref(false)
  const isVideoReady = ref(false)
  const videoRef = ref(null)
  const countdown = ref(10)
  const teksPemandu = ref('')
  const kameraStream = ref(null)
  const videoBlobRef = ref(null)
  const uploadedFileRef = ref(null)
  let mediaRecorder = null
  let recordedChunks = []

  const bukaModalRegistrasiMuka = (p) => {
    formMuka.value = { no_jppk: p.no_jppk, nama_peserta: p.nama_peserta, no_telp: p.no_telp || '', face_image_path: p.face_image_path }
    videoBlobRef.value = null
    uploadedFileRef.value = null
    isVideoReady.value = false
    modalMukaOpen.value = true
  }

  const matikanKamera = () => {
    if (kameraStream.value) {
      kameraStream.value.getTracks().forEach(track => track.stop())
    }
    isCameraOpen.value = false
    isRecording.value = false
  }

  const tutupModalMuka = () => {
    matikanKamera()
    modalMukaOpen.value = false
  }

  const aktifkanKamera = async () => {
    try {
      isCameraOpen.value = true
      formMuka.value.face_image_path = ''
      videoBlobRef.value = null
      uploadedFileRef.value = null
      isVideoReady.value = false
      
      await nextTick()
      kameraStream.value = await navigator.mediaDevices.getUserMedia({ 
        video: { width: 640, height: 480, frameRate: { ideal: 30 } } 
      })
      if (videoRef.value) videoRef.value.srcObject = kameraStream.value
      
      mediaRecorder = new MediaRecorder(kameraStream.value, { mimeType: 'video/webm' })
      
      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) recordedChunks.push(event.data)
      }
      
      mediaRecorder.onstop = () => {
        const videoBlob = new Blob(recordedChunks, { type: 'video/webm' })
        recordedChunks = []
        videoBlobRef.value = videoBlob
        isVideoReady.value = true
        Swal.fire({ icon: 'success', title: 'Video Selesai!', text: 'Proses rekam wajah selesai.', confirmButtonColor: '#059669', timer: 2000 })
      }
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Akses Ditolak', text: 'Izin kamera ditolak oleh browser!', confirmButtonColor: '#ef4444' })
      isCameraOpen.value = false
    }
  }

  const rekamVideoSuperDNA = () => {
    if (!mediaRecorder) return
    
    recordedChunks = []
    isRecording.value = true
    isVideoReady.value = false
    countdown.value = 10
    
    mediaRecorder.start()

    teksPemandu.value = '🟢 Posisikan Wajah LURUS Tegak'
    setTimeout(() => { teksPemandu.value = '👈 Nengok KIRI Perlahan' }, 2000)
    setTimeout(() => { teksPemandu.value = '👉 Nengok KANAN Perlahan' }, 4000)
    setTimeout(() => { teksPemandu.value = '👆 Mendongak ATAS Perlahan' }, 6000)
    setTimeout(() => { teksPemandu.value = '👇 Nunduk BAWAH Perlahan' }, 8000)

    const timerInterval = setInterval(() => {
      if(countdown.value > 0) countdown.value--
    }, 1000)
    
    setTimeout(() => {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop()
        isRecording.value = false
        clearInterval(timerInterval)
        matikanKamera() 
      }
    }, 10000) 
  }

  const handleUploadFoto = (e) => {
    const file = e.target.files[0]
    if (!file) return
    uploadedFileRef.value = file
    videoBlobRef.value = null
    isVideoReady.value = true
  }

  const simpanRegistrasiMuka = async () => {
    if (!isVideoReady.value) return

    Swal.fire({
      title: 'Memperbarui Biometrik AI...',
      text: 'Menghitung ulang sudut matriks rotasi...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    try {
      const formData = new FormData()
      formData.append('no_telp', formMuka.value.no_telp)
      
      if (videoBlobRef.value) {
        formData.append('video', videoBlobRef.value, 'rekaman.webm')
      } else if (uploadedFileRef.value) {
        formData.append('video', uploadedFileRef.value, uploadedFileRef.value.name)
      }

      const res = await axios.post(`/api/admin/peserta/registrasi-muka/${formMuka.value.no_jppk}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      
      if (res.data.status === 'success') {
        Swal.fire({ icon: 'success', title: 'Perubahan AI Berhasil!', text: res.data.message, confirmButtonColor: '#059669' })
        tutupModalMuka()
        fetchPasien() 
      } else {
        Swal.fire({ icon: 'error', title: 'Penolakan AI', text: res.data.message, confirmButtonColor: '#ef4444' })
      }
    } catch (error) {
      const errorReal = error.response?.data?.message || error.message
      Swal.fire({ icon: 'error', title: 'Gangguan Server AI', html: `<b>Detail Error:</b> ${errorReal}`, confirmButtonColor: '#ef4444' })
    }
  }

  return {
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
  }
}
