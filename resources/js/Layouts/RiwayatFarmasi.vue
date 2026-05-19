<template>
  <div class="p-8 bg-slate-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-800">Riwayat Pengambilan Obat</h1>
        <p class="text-slate-500 font-medium mt-1">Daftar pasien yang obatnya sudah siap dan dinotifikasi via WA.</p>
      </div>

      <div v-if="listRiwayat.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="p-5">No. Antrian</th>
              <th class="p-5">Data Pasien</th>
              <th class="p-5">Tujuan Poli</th>
              <th class="p-5">Obat Yang Diberikan</th>
              <th class="p-5 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in listRiwayat" :key="item.id" class="hover:bg-slate-50 transition-colors">
              <td class="p-5 font-black text-xl text-slate-400">
                {{ item.no_antrian }}
              </td>
              <td class="p-5">
                <div class="font-bold text-slate-800 text-base">{{ item.nama_pasien }}</div>
                <div class="text-sm text-slate-400">{{ item.no_telp }}</div>
              </td>
              <td class="p-5">
                <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-bold rounded-lg border border-slate-200">
                   {{ item.poli }}
                </span>
              </td>
              <td class="p-5 font-bold text-emerald-600">
                {{ item.nama_obat }}
              </td>
              <td class="p-5 text-center">
                <span class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-600 border border-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  Sudah Diambil
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="border-2 border-dashed border-slate-300 rounded-2xl p-16 flex flex-col items-center justify-center text-center bg-white/50">
        <p class="text-slate-400 font-medium">Belum ada riwayat pengambilan obat hari ini.</p>
        <button @click="fetchRiwayat" class="mt-4 px-4 py-2 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100">
          🔄 Segarkan Data
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const listRiwayat = ref([]);

const fetchRiwayat = async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/farmasi/riwayat');
    listRiwayat.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data riwayat:", error);
  }
};

onMounted(() => {
  fetchRiwayat();
});
</script>