import { ref, computed, onMounted, nextTick, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

export function useAdminDashboard() {
  const menuUtama = ref('manajemen') 
  const listSemua = ref([])
  const listUnits = ref([])
  const listPlans = ref([])
  const searchQuery = ref('')

  const currentPage = ref(1)
  const itemsPerPage = 10

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

  const wajahDikenalPeserta = computed(() => {
    return filteredPeserta.value.filter(p => p.face_embedding !== null && p.face_embedding !== '' && p.face_image_path !== null);
  })

  watch(searchQuery, () => { currentPage.value = 1 })
  watch(menuUtama, () => { currentPage.value = 1 })

  const modalCrudOpen = ref(false)
  const isEditMode = ref(false)
  const modalMukaOpen = ref(false)

  const formCrud = ref({ no_jppk: '', no_rm: '', npp: '', nama_peserta: '', status: '', nama_penanggung: '', jenis_kelamin: 'L', tgl_lahir: '', divisi: '', no_telp: '', unit_id: 1, plan_id: 2 })
  const formMuka = ref({ no_jppk: '', nama_peserta: '', no_telp: '', face_image_path: '' })

  const videoRef = ref(null)
  const isCameraOpen = ref(false)
  const kameraStream = ref(null)

  const isRecording = ref(false)
  const isVideoReady = ref(false)
  const videoBlobRef = ref(null)
  const uploadedFileRef = ref(null)

  // State khusus animasi ringan CSS
  const teksPemandu = ref('🟢 Posisikan Wajah Tegak')
  const countdown = ref(0)

  let mediaRecorder = null
  let recordedChunks = []

  const fetchPasien = async () => {
    try {
      const res = await axios.get('/api/admin/peserta')
      listSemua.value = res.data.semua
    } catch (error) {
      console.error("Database gagal terkoneksi:", error)
    }
  }

  const fetchRefData = async () => {
    try {
      const [resUnit, resPlan] = await Promise.all([
        axios.get('/api/admin/units'),
        axios.get('/api/admin/plans')
      ])
      listUnits.value = resUnit.data
      listPlans.value = resPlan.data
    } catch (error) {
      console.error("Gagal mengambil referensi unit/plan:", error)
    }
  }

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
  }

  const bukaModalTambah = () => {
    isEditMode.value = false
    formCrud.value = { no_jppk: '', no_rm: '', npp: '', nama_peserta: '', status: '', nama_penanggung: '', jenis_kelamin: 'L', tgl_lahir: '', divisi: '', no_telp: '', unit_id: 1, plan_id: 2 }
    modalCrudOpen.value = true
  }

  const bukaModalEdit = (p) => {
    isEditMode.value = true
    formCrud.value = { 
      no_jppk: p.no_jppk, no_rm: p.no_rm, npp: p.npp, nama_peserta: p.nama_peserta, status: p.status, 
      nama_penanggung: p.nama_penanggung, jenis_kelamin: p.jenis_kelamin, tgl_lahir: p.tgl_lahir, 
      divisi: p.divisi, no_telp: p.no_telp || '', unit_id: p.unit_id, plan_id: p.plan_id 
    }
    modalCrudOpen.value = true
  }

  const simpanAksiCrud = async () => {
    Swal.fire({ title: 'Menyimpan Data...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    try {
      if (isEditMode.value) {
        await axios.put(`/api/admin/peserta/${formCrud.value.no_jppk}`, formCrud.value)
      } else {
        await axios.post('/api/admin/peserta', formCrud.value)
      }
      modalCrudOpen.value = false
      fetchPasien()
      Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Operasi database sukses diperbarui!', confirmButtonColor: '#059669', timer: 1500 })
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Gagal Memproses Data', text: error.response?.data?.message || error.message, confirmButtonColor: '#ef4444' })
    }
  }

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

  const hapusPeserta = async (no_jppk) => {
    const konfirmasi = await Swal.fire({
      title: 'Hapus Pasien?', text: `Yakin ingin menghapus permanen pasien dengan Nomor JPPK ${no_jppk}?`,
      icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b', confirmButtonText: 'Ya, Hapus!'
    });

    if (konfirmasi.isConfirmed) {
      Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
      try {
        await axios.delete(`/api/admin/peserta/${no_jppk}`)
        fetchPasien()
        Swal.fire({ icon: 'success', title: 'Terhapus!', text: 'Sukses dibuang dari database.', confirmButtonColor: '#059669', timer: 1500 })
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menghapus data dari database.', confirmButtonColor: '#ef4444' })
      }
    }
  }

  const bukaModalRegistrasiMuka = (p) => {
    formMuka.value = { no_jppk: p.no_jppk, nama_peserta: p.nama_peserta, no_telp: p.no_telp || '', face_image_path: '' }
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
        video: { 
          width: 640, 
          height: 480,
          frameRate: { ideal: 30 }
        } 
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
        
        Swal.fire({ icon: 'success', title: 'Video Selesai!', text: 'Proses rekam wajah selesai. Klik Kunci AI.', confirmButtonColor: '#059669', timer: 2000 })
      }
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Akses Ditolak', text: 'Izin kamera ditolak oleh browser!', confirmButtonColor: '#ef4444' })
      isCameraOpen.value = false
    }
  }

  // 🔥 FUNGSI REKAM (10 Detik Ideal Cepat) 🔥
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
      title: 'Memproses Biometrik AI...',
      text: 'Menghitung sudut matriks rotasi & mengirim data ke AI server...',
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
        Swal.fire({ icon: 'success', title: 'Kunci AI Berhasil!', text: res.data.message, confirmButtonColor: '#059669' })
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
  }
}
