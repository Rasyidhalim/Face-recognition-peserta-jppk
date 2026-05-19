<template>
  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans">
    <Sidebar 
      @change-menu="handleTabChange" 
      :active-menu="activeTab"
      :user-role="userRole" 
      :is-open="isSidebarOpen"
    />

    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
      <Header 
        :current-page-title="headerTitle" 
        :user-role="userRole"
        @toggle-sidebar="toggleSidebar"
        @logout="$emit('do-logout')" 
      />

      <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
        
        <template v-if="userRole === 'loket'">
          <Main v-if="activeTab === 'verifikasi'" />
          <RiwayatPendaftaran v-else-if="activeTab === 'riwayat'" />
          </template>

        <template v-else-if="userRole === 'poli'">
          <AntrianPoli v-if="activeTab === 'antrian_poli'" />
          <RiwayatPendaftaran v-else-if="activeTab === 'data_peserta'" />
        </template>

        <template v-else-if="userRole === 'farmasi'">
          <AntrianFarmasi v-if="activeTab === 'antrian_obat'" />
          <RiwayatFarmasi v-else-if="activeTab === 'riwayat_obat'" />
        </template>

      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// --- PENTING: Sesuaikan import agar tidak error ---
// Jika Mang tidak pakai Inertia, pastikan library usePage/router dihapus. 
// Tapi kalau pakai Inertia, pastikan sudah jalankan: npm install @inertiajs/vue3

import Sidebar from '../Components/Sidebar.vue'
import Header from '../Components/Header.vue'
import Main from '../Components/Main.vue'
import RiwayatPendaftaran from '../Layouts/RiwayatPendaftaran.vue'
import DataPeserta from '../Layouts/DataPeserta.vue'
import AntrianFarmasi from '../Components/AntrianFarmasi.vue'
import RiwayatFarmasi from '../Layouts/RiwayatFarmasi.vue'
import AntrianPoli from '../Layouts/AntrianPoli.vue' // Komponen Baru Mang

const props = defineProps({
  userRole: {
    type: String,
    default: 'loket'
  }
});

const emit = defineEmits(['do-logout']);

const isSidebarOpen = ref(true)

// LOGIKA DEFAULT TAB: Biar pas login Poli langsung buka Antrian
const getDefaultTab = () => {
    if (props.userRole === 'farmasi') return 'antrian_obat';
    if (props.userRole === 'poli') return 'antrian_poli';
    return 'verifikasi';
}

const activeTab = ref(getDefaultTab())

const handleTabChange = (tabName) => {
  activeTab.value = tabName
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

// JUDUL HEADER: Tambahkan judul untuk menu Poli
const headerTitle = computed(() => {
  const titles = {
    'verifikasi': 'Verifikasi JPPK',
    'data_peserta': 'Riwayat Pasien Terdaftar', // Nama baru buat Poli
    'riwayat': 'Riwayat Pendaftaran',
    'antrian_obat': 'Antrian Obat Farmasi',
    'riwayat_obat': 'Riwayat Pengambilan Obat',
    'antrian_poli': 'Panggilan Antrian Poli' // Judul Baru
  }
  return titles[activeTab.value] || 'Dashboard'
})
</script>