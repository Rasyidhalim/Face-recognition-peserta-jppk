<template>
  <aside 
    @mouseenter="$emit('update:isOpen', true)"
    @mouseleave="$emit('update:isOpen', false)"
    :class="[isOpen ? 'w-72 translate-x-0' : 'w-0 -translate-x-full md:w-20 md:translate-x-0 overflow-hidden']"
    class="bg-emerald-950 text-white flex flex-col shadow-2xl relative z-20 transition-all duration-300 ease-in-out transform shrink-0"
  >
   <div class="p-8 flex items-center gap-3 transition-all duration-300" :class="{ 'md:p-5 md:justify-center': !isOpen }">
    <div class="w-10 h-10 bg-white p-1 rounded-xl flex items-center justify-center shadow-lg font-black text-xl overflow-hidden shrink-0">
        <img src="/logo-rs-pindad-removebg-preview.png" alt="Logo" class="w-full h-full object-contain">
    </div>
        <div v-if="isOpen" class="transition-all duration-300 whitespace-nowrap">
            <h1 class="text-xl font-black tracking-tighter leading-none">Rs <span class="text-emerald-500">Pindad</span></h1>
            <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-widest mt-1">Smart Health System</p>
        </div>
    </div>

    <nav class="flex-1 px-4 space-y-1 mt-4">
        
        <div v-if="userRole === 'loket' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Pendaftaran</p>
            
            <button @click="$emit('change-menu', 'verifikasi')" :class="[activeMenu === 'verifikasi' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Verifikasi JPPK">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Verifikasi JPPK</span>
            </button>
            
            <button @click="$emit('change-menu', 'riwayat')" :class="[activeMenu === 'riwayat' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Riwayat Daftar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Riwayat Daftar</span>
            </button>
        </div>

        <div v-if="userRole === 'poli' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2 mt-4">Pelayanan Poli</p>
            
            <button @click="$emit('change-menu', 'antrian_poli')" :class="[activeMenu === 'antrian_poli' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Panggilan Antrian">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Panggilan Antrian</span>
            </button>
            
            <button @click="$emit('change-menu', 'data_peserta')" :class="[activeMenu === 'data_peserta' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Riwayat Antrian Poli">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Riwayat Antrian Poli</span>
            </button>
        </div>

        <div v-if="userRole === 'admin' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2 mt-4">Manajemen Sistem</p>
            
            <button @click="$emit('change-menu', 'kelola_peserta')" :class="[activeMenu === 'kelola_peserta' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Kelola & Registrasi Pasien">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Kelola & Registrasi Pasien</span>
            </button>

            <button @click="$emit('change-menu', 'daftar_wajah')" :class="[activeMenu === 'daftar_wajah' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Daftar Wajah Dikenal">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Daftar Wajah Dikenal</span>
            </button>
            
            <button @click="$emit('change-menu', 'laporan_pendaftaran')" :class="[activeMenu === 'laporan_pendaftaran' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Laporan Pendaftaran">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Laporan Pendaftaran</span>
            </button>

            <button @click="$emit('change-menu', 'rekap_excel')" :class="[activeMenu === 'rekap_excel' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Rekap & Export Excel">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0 1 18 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0 1 18 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-3.75C20.625 8.25 21 8.754 21 9.375v1.5m-2.625 0v5.25m-10.5-5.25h10.5m-10.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m10.5-3.75c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-10.5 0h10.5m-10.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Rekap & Export Excel</span>
            </button>
        </div>

        <div v-if="userRole === 'dokter' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2 mt-4">Medis Dokter</p>
            
            <button @click="$emit('change-menu', 'pemeriksaan_dokter')" :class="[activeMenu === 'pemeriksaan_dokter' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Pemeriksaan Pasien">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Pemeriksaan Pasien</span>
            </button>

            <button @click="$emit('change-menu', 'riwayat_medis_dokter')" :class="[activeMenu === 'riwayat_medis_dokter' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group" title="Riwayat Medis">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Riwayat Medis</span>
            </button>
        </div>

    </nav>

    <div v-if="isOpen" class="p-6 border-t border-emerald-900/50 transition-all duration-300">
        <div class="bg-emerald-900/50 rounded-2xl p-4 border border-emerald-800/50">
            <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Status Server</p>
            <div class="flex items-center gap-2 mt-1">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <p class="text-xs font-bold text-emerald-100">Online</p>
            </div>
        </div>
    </div>
  </aside>
</template>

<script setup>
defineProps({
    userRole: String,
    activeMenu: String,
    isOpen: {
        type: Boolean,
        default: false // Set default ke false agar saat pertama kali buka langsung mode kecil
    }
});

// Daftarkan event 'update:isOpen' agar bisa berkomunikasi dengan Layout Utama
defineEmits(['change-menu', 'update:isOpen']);
</script>