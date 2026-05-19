<template>
    <aside class="w-72 bg-emerald-950 text-white flex flex-col shadow-2xl relative z-20">
        <div class="p-8 flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20 font-black text-xl">
            <img src="logo-rs-pindad.jpg">
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tighter leading-none">Rs <span class="text-emerald-500">Pindad</span></h1>
                <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-widest mt-1">Smart Health System</p>
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-1 mt-4">
            
            <div v-if="userRole === 'loket'" class="space-y-1">
                <p class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Pendaftaran</p>
                <button @click="$emit('change-menu', 'verifikasi')" :class="activeMenu === 'verifikasi' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900'" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                    <span class="font-bold text-sm">Verifikasi JPPK</span>
                </button>
                <button @click="$emit('change-menu', 'riwayat')" :class="activeMenu === 'riwayat' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900'" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                    <span class="font-bold text-sm">Riwayat Daftar</span>
                </button>
            </div>

            <div v-if="userRole === 'poli'" class="space-y-1">
                <p class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Pelayanan Poli</p>
                <button @click="$emit('change-menu', 'antrian_poli')" :class="activeMenu === 'antrian_poli' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900'" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                    <span class="font-bold text-sm">Panggilan Antrian</span>
                </button>
                <button @click="$emit('change-menu', 'data_peserta')" :class="activeMenu === 'data_peserta' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900'" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                    <span class="font-bold text-sm">Riwayat Antrian Poli</span>
                </button>
            </div>

            <div v-if="userRole === 'farmasi'" class="space-y-1">
                <p class="px-4 text-[10px] font-black text-emerald-500/40 uppercase tracking-[0.2em] mb-2">Apotek</p>
                <button @click="$emit('change-menu', 'antrian_obat')" :class="activeMenu === 'antrian_obat' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900'" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                    <span class="font-bold text-sm">Antrian Obat</span>
                </button>
                <button @click="$emit('change-menu', 'riwayat_obat')" :class="activeMenu === 'riwayat_obat' ? 'bg-emerald-800 text-white shadow-lg' : 'text-emerald-100/50 hover:bg-emerald-900'" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition-all group">
                    <span class="font-bold text-sm">Riwayat Obat</span>
                </button>
            </div>

        </nav>

        <div class="p-6 border-t border-emerald-900/50">
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
    activeMenu: String
});

defineEmits(['change-menu']);
</script>