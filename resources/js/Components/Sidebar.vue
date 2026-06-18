<template>
  <aside 
    :class="[isOpen ? 'w-72 translate-x-0' : 'w-0 -translate-x-full md:w-20 md:translate-x-0 overflow-hidden']"
    class="bg-emerald-950 text-white flex flex-col shadow-2xl relative z-20 transition-all duration-300 ease-in-out transform shrink-0"
  >
    <div class="p-8 flex items-center gap-3 transition-all duration-300" :class="{ 'md:p-5 md:justify-center': !isOpen }">
        <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20 font-black text-xl overflow-hidden shrink-0">
            <img src="/logo-rs-pindad.jpg" alt="Logo" class="w-full h-full object-contain">
        </div>
        <div v-if="isOpen" class="transition-all duration-300 whitespace-nowrap">
            <h1 class="text-xl font-black tracking-tighter leading-none">Rs <span class="text-emerald-500">Pindad</span></h1>
            <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-widest mt-1">Smart Health System</p>
        </div>
    </div>

    <nav class="flex-1 px-4 space-y-1 mt-4">
        
        <!-- 1. Pendaftaran (Loket / Superadmin) -->
        <div v-if="userRole === 'loket' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Pendaftaran</p>
            <button @click="$emit('change-menu', 'verifikasi')" :class="[activeMenu === 'verifikasi' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Verifikasi JPPK</span>
                <span v-else class="font-black text-xs text-emerald-400 hidden md:block" title="Verifikasi JPPK">VJ</span>
            </button>
            <button @click="$emit('change-menu', 'riwayat')" :class="[activeMenu === 'riwayat' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Riwayat Daftar</span>
                <span v-else class="font-black text-xs text-emerald-400 hidden md:block" title="Riwayat Daftar">RD</span>
            </button>
        </div>

        <!-- 2. Pelayanan Poli (Poli / Superadmin) -->
        <div v-if="userRole === 'poli' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Pelayanan Poli</p>
            <button @click="$emit('change-menu', 'antrian_poli')" :class="[activeMenu === 'antrian_poli' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Panggilan Antrian</span>
                <span v-else class="font-black text-xs text-emerald-400 hidden md:block" title="Panggilan Antrian">PA</span>
            </button>
            <button @click="$emit('change-menu', 'data_peserta')" :class="[activeMenu === 'data_peserta' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Riwayat Antrian Poli</span>
                <span v-else class="font-black text-xs text-emerald-400 hidden md:block" title="Riwayat Antrian Poli">RAP</span>
            </button>
        </div>

        <!-- 3. Manajemen Sistem (Admin / Superadmin) -->
        <div v-if="userRole === 'admin' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Manajemen Sistem</p>
            <button @click="$emit('change-menu', 'kelola_peserta')" :class="[activeMenu === 'kelola_peserta' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">⚙️ Kelola & Registrasi Pasien</span>
                <span v-else class="text-base hidden md:block" title="Kelola & Registrasi Pasien">⚙️</span>
            </button>
            <button @click="$emit('change-menu', 'laporan_pendaftaran')" :class="[activeMenu === 'laporan_pendaftaran' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">📋 Laporan Pendaftaran</span>
                <span v-else class="text-base hidden md:block" title="Laporan Pendaftaran">📋</span>
            </button>
        </div>

        <!-- 4. Medis Dokter (Dokter / Superadmin) -->
        <div v-if="userRole === 'dokter' || userRole === 'superadmin'" class="space-y-1">
            <p v-if="isOpen" class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Medis Dokter</p>
            <button @click="$emit('change-menu', 'pemeriksaan_dokter')" :class="[activeMenu === 'pemeriksaan_dokter' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900', !isOpen ? 'md:justify-center md:px-0' : '']" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                <span v-if="isOpen" class="font-bold text-sm whitespace-nowrap">Pemeriksaan Pasien</span>
                <span v-else class="font-black text-xs text-emerald-400 hidden md:block" title="Pemeriksaan Pasien">PP</span>
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
        default: true
    }
});

defineEmits(['change-menu']);
</script>