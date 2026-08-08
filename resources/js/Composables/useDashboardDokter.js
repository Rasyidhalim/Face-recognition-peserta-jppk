import { ref, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2' 

export function useDashboardDokter() {
  const isLoading = ref(false)
  const antrianAktif = ref([])

  const loadDataAntrian = async () => {
    isLoading.value = true
    try {
      const response = await axios.get('http://127.0.0.1:8000/api/antrian-dokter')
      if (response.data) {
        antrianAktif.value = response.data.aktif || []
      }
    } catch (error) {
      console.error("Gagal memuat data antrian dokter:", error)
    } finally {
      isLoading.value = false
    }
  }

  const prosesSelesai = async (pasien) => {
    const konfirmasi = await Swal.fire({
      title: 'Selesai Pemeriksaan?',
      text: `Selesaikan pemeriksaan untuk pasien ${pasien.nama_peserta}?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e11d48', // Warna merah rose menyesuaikan tombol
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Selesai!',
      cancelButtonText: 'Batal'
    });

    if (konfirmasi.isConfirmed) {
      // Loading State saat memproses API
      Swal.fire({
        title: 'Memproses data...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        const response = await axios.post('http://127.0.0.1:8000/api/selesai-periksa', {
          id: pasien.id
        })
        
        if (response.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Pemeriksaan Selesai',
            text: response.data.message,
            confirmButtonColor: '#059669',
            timer: 1500
          });
          loadDataAntrian() 
        }
      } catch (error) {
        console.error("Gagal menyelesaikan pemeriksaan:", error)
        Swal.fire({
          icon: 'error',
          title: 'Gagal Memproses',
          text: 'Gagal memproses penyelesaian medis ke server, Mang!',
          confirmButtonColor: '#ef4444'
        });
      }
    }
  }

  return {
    isLoading,
    antrianAktif,
    loadDataAntrian,
    prosesSelesai
  }
}
