import { ref, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

export function useAdminRekapExcel() {
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

  return {
    searchQuery,
    isLoading,
    currentPage,
    totalPages,
    paginatedPeserta,
    filteredPeserta,
    fetchPasien,
    prosesExportExcel
  }
}
