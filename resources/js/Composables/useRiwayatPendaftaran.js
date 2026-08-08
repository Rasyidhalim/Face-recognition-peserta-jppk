import { ref, computed, watch } from 'vue'; 
import axios from 'axios';
import Swal from 'sweetalert2';

export function useRiwayatPendaftaran() {
  const listRiwayat = ref([]);
  const isLoading = ref(true);
  const searchQuery = ref('');

  // ==========================================
  // 🔥 LOGIKA PAGINATION & SEARCH BAR
  // ==========================================
  const currentPage = ref(1);
  const itemsPerPage = 10;

  // Filter data secara realtime berdasarkan ketikan user
  const filteredRiwayat = computed(() => {
    if (!searchQuery.value) return listRiwayat.value;
    const keyword = searchQuery.value.toLowerCase();
    return listRiwayat.value.filter(item => {
      return (
        (item.nama_peserta && item.nama_peserta.toLowerCase().includes(keyword)) ||
        (item.no_jppk && item.no_jppk.toLowerCase().includes(keyword)) ||
        (item.nama_poli && item.nama_poli.toLowerCase().includes(keyword)) ||
        (item.no_antrian && String(item.no_antrian).includes(keyword))
      );
    });
  });

  // Hitung total halaman pembagi
  const totalPages = computed(() => {
    return Math.ceil(filteredRiwayat.value.length / itemsPerPage) || 1;
  });

  // Iris data menjadi hanya 10 item per halaman aktif
  const paginatedRiwayat = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredRiwayat.value.slice(start, end);
  });

  // Otomatis balik ke halaman 1 saat user mengetik pencarian baru
  watch(searchQuery, () => {
    currentPage.value = 1;
  });
  // ==========================================

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

  const fetchRiwayat = async (isManual = false) => {
    isLoading.value = true;
    try {
      const response = await axios.get('http://127.0.0.1:8000/api/riwayat-pendaftaran');
      if (response.data.status === 'success') {
        listRiwayat.value = response.data.data;
        if (isManual) {
          Toast.fire({ icon: 'success', title: 'Riwayat berhasil diperbarui' });
        }
      }
    } catch (error) {
      console.error("Gagal mengambil data riwayat:", error);
      listRiwayat.value = []; 
      if (isManual) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal Memuat',
          text: 'Koneksi ke server bermasalah, Mang!',
          confirmButtonColor: '#ef4444'
        });
      }
    } finally {
      isLoading.value = false;
    }
  };

  const formatWaktu = (waktu) => {
    if (!waktu) return '-';
    const date = new Date(waktu);
    const tanggal = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const jam = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    return `${tanggal} | ${jam} WIB`;
  };

  return {
    listRiwayat,
    isLoading,
    searchQuery,
    currentPage,
    totalPages,
    paginatedRiwayat,
    filteredRiwayat,
    fetchRiwayat,
    formatWaktu
  }
}
