import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2'; 

export function useAntrianPoli() {
  const list = ref([]);
  const searchQuery = ref('');

  // Computed Property untuk memfilter data berdasarkan pencarian
  const filteredAntrian = computed(() => {
    if (!searchQuery.value) return list.value;
    
    const query = searchQuery.value.toLowerCase();
    
    return list.value.filter(item => {
      // Pastikan ngecek aman walau datanya null/undefined dari API
      const noAntrian = item.no_antrian ? item.no_antrian.toLowerCase() : '';
      const poliName = item.poli ? item.poli.toLowerCase() : 'poli umum';
      
      return noAntrian.includes(query) || poliName.includes(query);
    });
  });

  const loadData = async () => {
    try {
      const res = await axios.get('http://127.0.0.1:8000/api/data-antrian-poli');
      list.value = res.data;
    } catch (err) {
      console.error("Gagal ambil data:", err);
    }
  };

  const panggilPasien = async (item) => {
    // Ganti confirm biasa dengan SweetAlert2
    const result = await Swal.fire({
      title: 'Panggil Pasien?',
      text: `Kirim notifikasi panggilan ke ${item.nama_peserta}?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981', // Warna emerald-500 biar senada
      cancelButtonColor: '#ef4444',
      confirmButtonText: 'Ya, Panggil!',
      cancelButtonText: 'Batal'
    });

    if (result.isConfirmed) {
      try {
        // Munculkan loading saat proses tembak API
        Swal.fire({
          title: 'Memproses...',
          text: 'Sedang mengirim notifikasi WhatsApp...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        const res = await axios.post('http://127.0.0.1:8000/api/panggil-poli', { id: item.id });
        
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'Berhasil!',
            text: `Notif WA berhasil dikirim ke ${item.nama_peserta}`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });
          loadData();
        } else {
          Swal.fire('Gagal!', "Gagal: " + res.data.message, 'error');
        }
      } catch (err) {
        console.error("Error panggil:", err);
        Swal.fire('Error!', 'Koneksi bermasalah atau Token Fonnte salah Mang!', 'error');
      }
    }
  };

  return {
    list,
    searchQuery,
    filteredAntrian,
    loadData,
    panggilPasien
  }
}
