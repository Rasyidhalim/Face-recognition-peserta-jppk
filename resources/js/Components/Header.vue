<script setup>
import { computed } from 'vue'; 

// Sekarang hanya emit 'logout' saja karena hamburger sudah dihapus
defineEmits(['logout']);

const props = defineProps({
  currentPageTitle: {
    type: String,
    default: 'Verifikasi JPPK'
  },
  userRole: {
    type: String,
    default: 'loket'
  }
});

// Logika nama role utama dinamis
const tampilanNamaRole = computed(() => {
  if (props.userRole === 'farmasi') return 'Petugas Farmasi';
  if (props.userRole === 'poli') return 'Petugas Poliklinik';
  if (props.userRole === 'dokter') return 'Dokter Spesialis';
  if (props.userRole === 'admin') return 'Super Admin Eksekutif'; 
  if (props.userRole === 'superadmin') return 'Super Admin Eksekutif'; 
  return 'Admin Loket JPPK'; 
});

// Logika sub-nama/bagian dinamis
const tampilanSubRole = computed(() => {
  if (props.userRole === 'farmasi') return 'Instalasi Farmasi';
  if (props.userRole === 'poli') return 'Pelayanan Poli';
  if (props.userRole === 'dokter') return 'Ruang Pemeriksaan Medis'; 
  if (props.userRole === 'admin') return 'Direktorat IT RS Pindad';
  if (props.userRole === 'superadmin') return 'Direktorat IT RS Pindad';
  return 'Petugas Pendaftaran'; 
});
</script>

<template>
  <header class="h-20 bg-white border-b border-slate-100 px-8 flex items-center justify-between z-10 transition-all">
    
    <div class="flex items-center">
      <h2 class="text-slate-500 font-medium text-sm">
        Dashboard / <span class="text-emerald-600 font-bold">{{ currentPageTitle }}</span>
      </h2>
    </div>

    <div class="flex items-center space-x-6">
      <div class="text-right hidden md:block">
        <p class="text-sm font-bold text-slate-800 leading-none">
          {{ tampilanNamaRole }}
        </p>
        <p class="text-[11px] text-emerald-600 font-bold mt-1">
          {{ tampilanSubRole }}
        </p>
      </div>
      
      <div class="relative group">
        <button 
          @click="$emit('logout')" 
          type="button"
          class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600 border-2 border-transparent hover:border-rose-500 hover:bg-rose-100 transition-all shadow-sm"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </button>
      </div>
    </div>
  </header>
</template>