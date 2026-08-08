import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2' 

export function useAdminLaporan() {
  const listLaporan = ref([])
  const filterLaporan = ref({ tanggal: '', poli: '', dokter: '' })

  // ==========================================
  // 🔥 STATE & LOGIKA PAGINATION
  // ==========================================
  const currentPage = ref(1)
  const itemsPerPage = 10 // Tampilkan 10 data per halaman

  // Menghitung total jumlah halaman (contoh: 25 data = 3 halaman)
  const totalPages = computed(() => {
    return Math.ceil(listLaporan.value.length / itemsPerPage) || 1
  })

  // Mengiris (slice) array data mentah agar hanya berisi 10 data per halamannya
  const paginatedLaporan = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    const end = start + itemsPerPage
    return listLaporan.value.slice(start, end)
  })
  // ==========================================

  // Konfigurasi Toast Custom
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.onmouseenter = Swal.stopTimer;
      toast.onmouseleave = Swal.resumeTimer;
    }
  });

  // Panggil API Backend (Tambah parameter isManual)
  const fetchLaporan = async (isManual = false) => {
    if (isManual) {
      Swal.fire({
        title: 'Mencari Data...',
        text: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading()
        }
      });
    }

    try {
      const res = await axios.get('/api/admin/laporan-pendaftaran', {
        params: {
          tanggal: filterLaporan.value.tanggal,
          poli: filterLaporan.value.poli,
          dokter: filterLaporan.value.dokter
        }
      })
      
      if (res.data.status === 'success') {
        listLaporan.value = res.data.data

        // 🚀 RESET KE HALAMAN 1 JIKA MELAKUKAN PENCARIAN BARU
        currentPage.value = 1

        if (isManual) {
          Swal.close();
          Toast.fire({
            icon: 'success',
            title: `Ditemukan ${listLaporan.value.length} data`
          });
        }
      }
    } catch (error) {
      console.error("Gagal memuat laporan antrean:", error)
      if (isManual) {
        Swal.fire('Error!', 'Gagal menarik data dari server, Mang!', 'error')
      } else {
        Toast.fire({ icon: 'error', title: 'Gagal memuat data' });
      }
    }
  }

  const resetFilterLaporan = () => {
    filterLaporan.value = { tanggal: '', poli: '', dokter: '' }
    fetchLaporan(false) // Tarik ulang data semua
    
    Toast.fire({
      icon: 'info',
      title: 'Filter dikembalikan ke awal'
    });
  }

  return {
    listLaporan,
    filterLaporan,
    currentPage,
    itemsPerPage,
    totalPages,
    paginatedLaporan,
    fetchLaporan,
    resetFilterLaporan
  }
}
