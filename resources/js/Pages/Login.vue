<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl overflow-hidden border border-white/20">
      
      <div class="p-8 text-center bg-slate-50 border-b border-slate-100">
        <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 rotate-3 hover:rotate-0 transition-transform duration-300 shadow-inner overflow-hidden">
            <img src="/logo-rs-pindad.jpg" alt="Logo RS Pindad" class="w-full h-full object-contain" />
        </div>
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">RS Pindad</h1>
        <p class="text-slate-500 mt-2 text-sm font-medium">Medical Information System</p>
      </div>

      <div class="p-8">
        <div v-if="errorMessage" class="mb-4 p-3 bg-rose-50 border border-rose-100 text-rose-600 text-xs font-bold rounded-xl text-center">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Username</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
              </span>
              <input 
                v-model="form.username" 
                type="text" 
                required
                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white outline-none transition-all duration-200" 
                placeholder="Masukkan username petugas">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              </span>
              <input 
                v-model="form.password"
                type="password" 
                required
                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white outline-none transition-all duration-200" 
                placeholder="••••••••">
            </div>
          </div>

          <button 
            type="submit" 
            :disabled="isLoading"
            class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl transition-all duration-300 shadow-lg shadow-emerald-500/30 flex items-center justify-center space-x-2 active:scale-[0.98] disabled:opacity-50">
            <span v-if="isLoading" class="animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            <span>{{ isLoading ? 'Memverifikasi...' : 'Masuk ke Sistem' }}</span>
          </button>
        </form>
      </div>

      <div class="p-6 bg-slate-50 text-center border-t border-slate-100">
        <p class="text-xs text-slate-400 font-medium">Developed by IT RS Pindad &copy; 2026</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios' // PENTING: Jangan lupa import axios

const emit = defineEmits(['login-success'])

const form = reactive({
  username: '', // Sudah ganti dari email ke username
  password: ''
})

const isLoading = ref(false)
const errorMessage = ref('') // Untuk menampung pesan error dari Laravel

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    // Tembak API Login di Laravel
    const response = await axios.post('/login', {
      username: form.username,
      password: form.password
    })

    if (response.data.status === 'success') {
      // Kirim ROLE dari database ke App.vue
      emit('login-success', response.data.user.role)
    }
  } catch (error) {
    // Jika gagal (401), ambil pesan error dari LoginController
    errorMessage.value = error.response?.data?.message || 'Gagal terhubung ke server'
  } finally {
    isLoading.value = false
  }
}
</script>