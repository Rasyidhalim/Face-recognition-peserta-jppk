import { ref, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2' 

export function useRiwayatMedis() {
  const riwayatSelesai = ref([])
  const isLoading = ref(false)

  // 🌟 Konfigurasi Toast Custom
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

  // 🌟 Tambah parameter isManual untuk cek apakah ini klik tombol Refresh
  const loadRiwayat = async (isManual = false) => {
    isLoading.value = true
    
    if (isManual) {
      Swal.fire({
        title: 'Memperbarui Data...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading()
        }
      });
    }

    try {
      const response = await axios.get('http://127.0.0.1:8000/api/antrian-dokter')
      if (response.data) {
        riwayatSelesai.value = response.data.riwayat || []
        
        // Munculkan Toast jika update manual lewat tombol Refresh
        if (isManual) {
          Swal.close();
          Toast.fire({
            icon: 'success',
            title: 'Riwayat berhasil diperbarui'
          });
        }
      }
    } catch (error) {
      console.error("Gagal memuat riwayat medis:", error)
      if (isManual) {
        Swal.fire('Error!', 'Gagal menarik data riwayat dari server, Mang!', 'error')
      }
    } finally {
      isLoading.value = false
    }
  }

  return {
    riwayatSelesai,
    isLoading,
    loadRiwayat
  }
}
