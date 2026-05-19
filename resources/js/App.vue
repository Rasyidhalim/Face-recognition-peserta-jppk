<template>
  <Login 
    v-if="currentPage === 'login'" 
    @login-success="(role) => handleLoginSuccess(role)" 
  />
  
  <Dashboard 
    v-else-if="currentPage === 'dashboard'" 
    :user-role="currentUserRole"
    @do-logout="handleLogout" 
  />
</template>

<script setup>
import { ref, onMounted } from 'vue' // Tambahkan onMounted
import axios from 'axios' // Impor Axios Mang
import Login from './Pages/Login.vue'
import Dashboard from './Pages/Dashboard.vue'

const currentPage = ref('login')
// STATE BARU: Untuk menampung siapa yang login
const currentUserRole = ref('') 

// --- STATE TAMBAHAN UNTUK HANDLE REFRESH ---
// 1. Fungsi ini berjalan OTOMATIS saat komponen ini dimuat (saat F5)
onMounted(() => {
  const storedRole = localStorage.getItem('user_role'); // Cek apakah ada role yang disimpan

  if (storedRole) {
    // Kalau ada, berarti user sudah login sebelumnya dan session di Laravel mungkin masih hidup
    currentUserRole.value = storedRole;
    currentPage.value = 'dashboard';
  }
})

const handleLoginSuccess = (role) => {
  // Simpan role yang dikirim dari Login.vue (hasil database)
  currentUserRole.value = role; 

  // --- PERBAIKAN: Simpan juga di localStorage agar awet pas direfresh ---
  localStorage.setItem('user_role', role);

  // Baru pindah halaman
  currentPage.value = 'dashboard';
}

const handleLogout = async () => { // Tambahkan async untuk panggilan API
  const yakin = confirm("Apakah anda yakin ingin keluar?");
  if (yakin) {
    try {
      // --- PERBAIKAN: Tembak API Logout Laravel agar Session di Server benar-benar mati ---
      await axios.post('http://localhost:8000/api/logout'); 

    } catch (error) {
      console.error("Gagal logout di server:", error);
      // alert("Gagal keluar dari server. Cek koneksi.");
    } finally {
      // --- FINALISASI: Bagian ini harus selalu dijalankan ---
      currentPage.value = 'login';
      currentUserRole.value = ''; // Kosongkan role saat logout
      localStorage.removeItem('user_role'); // Hapus juga dari localStorage
    }
  }
}
</script>