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
            <AdminRekapExcel v-if="activeTab === 'rekap_excel'" />
            <AdminDaftarWajah v-if="activeTab === 'daftar_wajah'" />
            <AdminLaporan v-if="activeTab === 'laporan_pendaftaran'" />
        </template>

      </main>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '../Components/Sidebar.vue'
import Header from '../Components/Header.vue'
import Main from '../Components/Main.vue'
import RiwayatPendaftaran from '../Layouts/RiwayatPendaftaran.vue'
import AntrianPoli from '../Layouts/AntrianPoli.vue'
import DokterDashboard from '../Layouts/DashboardDokter.vue' 
import RiwayatMedis from '../Layouts/RiwayatMedis.vue'
import AdminDashboard from '../Layouts/AdminDashboard.vue'
import AdminDaftarWajah from '../Layouts/AdminDaftarWajah.vue'
import AdminRekapExcel from '../Layouts/AdminRekapExcel.vue'
import AdminLaporan from '../Layouts/AdminLaporan.vue'
import { useDashboard } from '../Composables/useDashboard'

const props = defineProps({
  userRole: {
    type: String,
    default: 'loket'
  }
});

const emit = defineEmits(['do-logout']);

const {
  isSidebarOpen,
  activeTab,
  handleTabChange,
  toggleSidebar,
  headerTitle
} = useDashboard(props)
</script>