import { ref, onMounted } from 'vue' 
import axios from 'axios' 
import Swal from 'sweetalert2'

export function useApp() {
  const currentPage = ref('login')
  const currentUserRole = ref('') 

  // --- HANDLE REFRESH ---
  onMounted(() => {
    const storedRole = localStorage.getItem('user_role'); 

    if (storedRole) {
      currentUserRole.value = storedRole;
      currentPage.value = 'dashboard';
    }
  })

  const handleLoginSuccess = (role) => {
    currentUserRole.value = role; 
    localStorage.setItem('user_role', role);
    currentPage.value = 'dashboard';
  }

  // --- PERBAIKAN: LOGOUT DENGAN SWEETALERT ---
  const handleLogout = async () => {
    // 1. Tampilkan Popup SweetAlert Konfirmasi
    const konfirmasi = await Swal.fire({
      title: 'Yakin ingin keluar?',
      text: "Sesi Anda akan diakhiri dan Anda harus login kembali.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444', // Merah
      cancelButtonColor: '#94a3b8',  // Abu-abu
      confirmButtonText: 'Ya, Keluar!',
      cancelButtonText: 'Batal'
    });

    // 2. Jika user klik tombol "Ya, Keluar!"
    if (konfirmasi.isConfirmed) {
      
      // Tampilkan animasi loading biar keren
      Swal.fire({
        title: 'Sedang keluar...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        // Tembak API Logout Laravel
        await axios.post('http://localhost:8000/api/logout'); 

      } catch (error) {
        console.error("Gagal logout di server:", error);
      } finally {
        // 3. Tutup SweetAlert loading
        Swal.close();

        // 4. Bersihkan state dan lempar ke halaman login
        currentPage.value = 'login';
        currentUserRole.value = ''; 
        localStorage.removeItem('user_role'); 
      }
    }
  }

  return {
    currentPage,
    currentUserRole,
    handleLoginSuccess,
    handleLogout
  }
}
