<template>
  <div class="p-8 bg-slate-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-800">Antrian Resep Farmasi</h1>
        <p class="text-slate-500 font-medium mt-1">Selamat bekerja, Petugas Farmasi RS Pindad.</p>
      </div>

      <div v-if="listAntrian.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="p-5">No. Antrian</th>
              <th class="p-5">Data Pasien</th>
              <th class="p-5">Tujuan Poli</th>
              <th class="p-5">Input Nama Obat</th>
              <th class="p-5 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in listAntrian" :key="item.id" class="hover:bg-slate-50 transition-colors">
              <td class="p-5 font-black text-xl text-emerald-600">
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
              <td class="p-5">
                <input 
                  v-model="item.nama_obat" 
                  type="text" 
                  placeholder="Ketik nama obat..."
                  class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                />
              </td>
              <td class="p-5 text-center">
                <button 
                  @click="kirimNotif(item)"
                  :disabled="!item.nama_obat"
                  class="bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-300 text-white px-5 py-2 rounded-lg font-bold text-xs shadow-md active:scale-95 transition-all whitespace-nowrap"
                >
                  SELESAI & WA
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="border-2 border-dashed border-slate-300 rounded-2xl p-16 flex flex-col items-center justify-center text-center bg-white/50">
        <p class="text-slate-400 font-medium">Tabel Antrian Obat Akan Muncul Di Sini</p>
        <button @click="fetchData" class="mt-4 px-4 py-2 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100">
          🔄 Cek Data Baru
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const listAntrian = ref([]);
let intervalId = null;

const fetchData = async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/farmasi/antrian');
    const dataDariServer = response.data;

    // Kita replace datanya dengan mempertahankan ketikan yang sedang diketik
    listAntrian.value = dataDariServer.map(itemBaru => {
      const itemLama = listAntrian.value.find(old => old.id === itemBaru.id);
      
      // Jika baris ini sudah ada di layar, jangan ubah ketikan nama_obat-nya
      if (itemLama && itemLama.nama_obat !== undefined) {
        itemBaru.nama_obat = itemLama.nama_obat; 
      }
      
      return itemBaru;
    });

  } catch (error) {
    console.error("Gagal mengambil data farmasi:", error);
  }
};

const kirimNotif = async (item) => {
  try {
    // Ubah localhost jadi 127.0.0.1 agar sama dengan fungsi GET di atas
    const res = await axios.post('http://127.0.0.1:8000/api/farmasi/update-dan-notif', {
      id: item.id,
      nama_obat: item.nama_obat
    });
    
    if(res.data.status === 'success') {
      alert("✅ Obat selesai! Notifikasi WA terkirim ke " + item.nama_pasien);
      // Panggil fetchData() agar pasien yang sudah selesai langsung hilang dari tabel
      fetchData();
    }
  } catch (error) {
    alert("❌ Gagal mengirim notifikasi.");
  }
};

onMounted(() => {
  fetchData();
  // Auto-refresh setiap 3 detik supaya kalau ada pasien baru langsung muncul
  intervalId = setInterval(fetchData, 3000);
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});
</script>