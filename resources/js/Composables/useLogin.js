import { ref, reactive } from 'vue'
import axios from 'axios' 
import Swal from 'sweetalert2' 

export function useLogin(emit) {
  const form = reactive({
    username: '', 
    password: ''
  })

  const isLoading = ref(false)
  const errorMessage = ref('') 

  // Toast konfig untuk sukses login
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true
  });

  const handleLupaPassword = () => {
    Swal.fire({
      icon: 'info',
      title: 'Lupa Password?',
      text: 'Silakan hubungi Administrator IT (Superadmin) RS Pindad untuk mereset password akun Anda.',
      confirmButtonColor: '#10b981',
      confirmButtonText: 'Tutup'
    });
  }

  const handleLogin = async () => {
    isLoading.value = true
    errorMessage.value = ''
    
    try {
      // 🌟 Relative path /login sudah benar, aman untuk Ngrok
      const response = await axios.post('/login', {
        username: form.username,
        password: form.password
      })

      if (response.data.status === 'success') {
        // 🌟 Kasih Toast sukses biar keren sebelum dilempar ke dashboard
        await Toast.fire({
          icon: 'success',
          title: 'Login Berhasil!',
          text: 'Mengalihkan halaman...'
        });
        
        // Kirim ROLE dari database ke App.vue
        emit('login-success', response.data.user.role)
      }
    } catch (error) {
      const pesanError = error.response?.data?.message || 'Gagal terhubung ke server'
      errorMessage.value = pesanError
      
      // 🌟 Tambahan SweetAlert modal biar kalau gagal petugas langsung ngeh
      Swal.fire({
        icon: 'error',
        title: 'Akses Ditolak',
        text: pesanError,
        confirmButtonColor: '#ef4444'
      });
    } finally {
      isLoading.value = false
    }
  }

  return {
    form,
    isLoading,
    errorMessage,
    handleLupaPassword,
    handleLogin
  }
}
