import { ref, computed, watchEffect } from 'vue'

export function useDashboard(props) {
  // 2. DI SINI PERUBAHANNYA: Diubah ke false agar default awal-nya mengecil (mode ikon)
  const isSidebarOpen = ref(false)

  const getDefaultTab = () => {
      if (props.userRole === 'superadmin') return 'kelola_peserta'; 
      if (props.userRole === 'admin') return 'kelola_peserta'; 
      if (props.userRole === 'farmasi') return 'antrian_obat';
      if (props.userRole === 'poli') return 'antrian_poli';
      if (props.userRole === 'dokter') return 'pemeriksaan_dokter'; 
      return 'verifikasi';
  }

  const activeTab = ref(getDefaultTab())

  watchEffect(() => {
    activeTab.value = getDefaultTab()
  })

  const handleTabChange = (tabName) => {
    activeTab.value = tabName
  }

  // Tombol klik di Header tetap berfungsi normal untuk toggle manual
  const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value
  }

  const headerTitle = computed(() => {
    const titles = {
      'verifikasi': 'Verifikasi JPPK',
      'data_peserta': 'Riwayat Pasien Terdaftar',
      'riwayat': 'Riwayat Pendaftaran',
      'antrian_obat': 'Antrian Obat Farmasi',
      'riwayat_obat': 'Riwayat Pengambilan Obat',
      'antrian_poli': 'Panggilan Antrian Poli',
      'pemeriksaan_dokter': 'Ruang Pemeriksaan Medis Dokter',
      'riwayat_medis_dokter': 'Riwayat Medis Pasien Selesai',
      'kelola_peserta': 'Panel Kontrol Admin & Registrasi Biometrik Pasien',
      'rekap_excel': 'Rekapitulasi & Export Excel Pasien',
      'daftar_wajah': 'Pusat Biometrik Pasien Dikenal',
      'laporan_pendaftaran': 'Laporan & Rekapitulasi Pendaftaran Antrean' 
    }
    return titles[activeTab.value] || 'Dashboard'
  })

  return {
    isSidebarOpen,
    activeTab,
    handleTabChange,
    toggleSidebar,
    headerTitle
  }
}
