<template>
  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans">
    <Sidebar 
      @change-menu="handleTabChange" 
      :active-menu="activeTab"
      :user-role="userRole" 
      v-model:isOpen="isSidebarOpen"
    />

    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
      <Header 
        :current-page-title="headerTitle" 
        :user-role="userRole"
        @toggle-sidebar="toggleSidebar"
        @logout="$emit('do-logout')" 
      />

      <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
        
        <template v-if="userRole === 'loket' || userRole === 'superadmin'">
            <Main v-if="activeTab === 'verifikasi'" />
            <RiwayatPendaftaran v-else-if="activeTab === 'riwayat'" />
        </template>

        <template v-if="userRole === 'poli' || userRole === 'superadmin'">
            <AntrianPoli v-if="activeTab === 'antrian_poli'" />
            <RiwayatPendaftaran v-else-if="activeTab === 'data_peserta'" />
        </template>

        <template v-if="userRole === 'farmasi' || userRole === 'superadmin'">
            </template>

        <template v-if="userRole === 'dokter' || userRole === 'superadmin'">
            <DokterDashboard v-if="activeTab === 'pemeriksaan_dokter'" />
            <RiwayatMedis v-else-if="activeTab === 'riwayat_medis_dokter'" />
        </template>

        <template v-if="userRole === 'admin' || userRole === 'superadmin'">
            <AdminDashboard v-if="activeTab === 'kelola_peserta'" />
            <AdminLaporan v-if="activeTab === 'laporan_pendaftaran'" />
        </template>

      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue'

import Sidebar from '../Components/Sidebar.vue'
import Header from '../Components/Header.vue'
import Main from '../Components/Main.vue'
import RiwayatPendaftaran from '../Layouts/RiwayatPendaftaran.vue'
import AntrianPoli from '../Layouts/AntrianPoli.vue'
import DokterDashboard from '../Layouts/DashboardDokter.vue' 
import RiwayatMedis from '../Layouts/RiwayatMedis.vue'
import AdminDashboard from '../Layouts/AdminDashboard.vue'
import AdminLaporan from '../Layouts/AdminLaporan.vue'

const props = defineProps({
  userRole: {
    type: String,
    default: 'loket'
  }
});

const emit = defineEmits(['do-logout']);

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
    'laporan_pendaftaran': 'Laporan & Rekapitulasi Pendaftaran Antrean' 
  }
  return titles[activeTab.value] || 'Dashboard'
})
</script>