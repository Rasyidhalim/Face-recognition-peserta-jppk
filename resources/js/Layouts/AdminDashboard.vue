<template>
  <div class="space-y-6 animate-fade-in">
    
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between bg-white p-6 rounded-2xl border border-slate-100 shadow-sm gap-4">
      <div>
        <h3 class="text-lg font-black text-slate-800 tracking-tight">Pusat Data Pasien JPPK</h3>
        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Manajemen Sinkronisasi Face Recognition FaceNet & WhatsApp RS Pindad</p>
      </div>

      <div class="flex bg-slate-100 p-1 rounded-xl self-start xl:self-auto shadow-inner">
        <button 
          @click="menuUtama = 'manajemen'" 
          :class="menuUtama === 'manajemen' ? 'bg-white text-emerald-700 shadow-sm font-black' : 'text-slate-500 font-bold hover:text-slate-800'"
          class="px-5 py-2.5 text-xs rounded-lg transition-all flex items-center gap-2 whitespace-nowrap"
        >
          👥 Kelola & Kamera AI
        </button>
        <button 
          @click="menuUtama = 'rekap_excel'" 
          :class="menuUtama === 'rekap_excel' ? 'bg-emerald-600 text-white shadow-md font-black' : 'text-slate-500 font-bold hover:text-slate-800'"
          class="px-5 py-2.5 text-xs rounded-lg transition-all flex items-center gap-2 ml-1 whitespace-nowrap"
        >
          📊 Rekap & Export Excel
        </button>
      </div>
    </div>

    <div v-if="menuUtama === 'manajemen'" class="space-y-4 animate-fade-in">
      
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-100 shadow-sm gap-3">
        <div class="flex bg-slate-100 p-1 rounded-lg self-start">
          <button @click="subTab = 'semua'" :class="subTab === 'semua' ? 'bg-white text-slate-800 shadow-sm font-bold' : 'text-slate-500 text-xs'" class="px-4 py-1.5 text-xs rounded-md transition-all">
            📁 Semua Peserta ({{ listSemua.length }})
          </button>
          <button @click="subTab = 'belum_lengkap'" :class="subTab === 'belum_lengkap' ? 'bg-rose-500 text-white shadow-sm font-bold' : 'text-slate-500 text-xs'" class="px-4 py-1.5 text-xs rounded-md transition-all ml-1">
            ⚠️ Belum Aktivasi ({{ listBelumLengkap.length }})
          </button>
        </div>
        
        <button v-if="subTab === 'semua'" @click="bukaModalTambah()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all flex items-center gap-1.5">
          <span>+</span> Daftarkan Pasien Baru
        </button>
      </div>

      <div v-if="subTab === 'semua'" class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 font-bold text-slate-500">
              <tr>
                <th class="p-3">No JPPK</th>
                <th class="p-3 text-emerald-700">No RM</th>
                <th class="p-3">NPP</th>
                <th class="p-3">Nama Lengkap</th>
                <th class="p-3">Status & Penanggung</th>
                <th class="p-3">Gender</th>
                <th class="p-3">Status Biometrik</th>
                <th class="p-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in listSemua" :key="p.no_jppk" class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="p-3 font-bold text-slate-700">{{ p.no_jppk }}</td>
                <td class="p-3 font-bold text-emerald-600 bg-emerald-50/30">{{ p.no_rm || '-' }}</td>
                <td class="p-3 text-slate-600 font-mono">{{ p.npp }}</td>
                <td class="p-3 font-medium text-slate-800">{{ p.nama_peserta }}</td>
                <td class="p-3">
                  <div class="flex flex-col">
                    <span class="font-bold text-blue-700">{{ p.status || '-' }}</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Penanggung: {{ p.nama_penanggung || '-' }}</span>
                  </div>
                </td>
                <td class="p-3 font-bold text-center">{{ p.jenis_kelamin }}</td>
                <td class="p-3">
                  <span v-if="p.face_embedding" class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-bold text-[10px]">🟢 AI Terkunci</span>
                  <span v-else class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded-md font-bold text-[10px]">🔴 Belum Aktif</span>
                </td>
                <td class="p-3 text-center space-x-3 whitespace-nowrap">
                  <button @click="bukaModalEdit(p)" class="text-blue-600 font-bold hover:underline">Edit</button>
                  <button @click="hapusPeserta(p.no_jppk)" class="text-rose-600 font-bold hover:underline">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="subTab === 'belum_lengkap'" class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-rose-50 border-b border-rose-100 font-bold text-rose-950">
              <tr>
                <th class="p-3">No JPPK</th>
                <th class="p-3">Nama Lengkap Pasien</th>
                <th class="p-3">Divisi Kerja</th>
                <th class="p-3 text-center">Tindakan Scanner</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in listBelumLengkap" :key="p.no_jppk" class="border-b border-slate-50 hover:bg-rose-50/10">
                <td class="p-3 font-bold text-slate-700">{{ p.no_jppk }}</td>
                <td class="p-3 font-medium text-slate-800">{{ p.nama_peserta }}</td>
                <td class="p-3 text-slate-500">{{ p.divisi || '-' }}</td>
                <td class="p-3 text-center">
                  <button @click="bukaModalRegistrasiMuka(p)" class="bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold px-4 py-1.5 rounded-xl shadow-sm transition-all">
                    📸 Ambil Foto Wajah
                  </button>
                </td>
              </tr>
              <tr v-if="listBelumLengkap.length === 0">
                <td colspan="4" class="text-center p-6 text-emerald-600 font-bold">🎉 Semua pasien JPPK telah teraktivasi Biometrik AI Face Recognition!</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <div v-if="menuUtama === 'rekap_excel'" class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 animate-fade-in">
      
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
        <div>
          <h4 class="text-sm font-black text-slate-800">Master Sheet Rekapitulasi Pasien JPPK</h4>
          <p class="text-[11px] text-slate-400 mt-0.5">Total data ekspor: <span class="text-emerald-600 font-bold">{{ listSemua.length }} Baris</span></p>
        </div>
        <button @click="prosesExportExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-3 rounded-xl shadow-md transition-all flex items-center gap-2">
          📥 Download Berkas Excel (.xls)
        </button>
      </div>

      <div class="overflow-x-auto border border-slate-100 rounded-xl">
        <table class="w-full text-left border-collapse text-[11px]">
          <thead class="bg-slate-100 border-b border-slate-200 font-bold text-slate-700 uppercase tracking-wider">
            <tr>
              <th class="p-3">No JPPK</th>
              <th class="p-3 bg-emerald-50/50">No RM</th>
              <th class="p-3">NPP</th>
              <th class="p-3">Nama Lengkap Pasien</th>
              <th class="p-3">Status</th>
              <th class="p-3">Nama Penanggung</th>
              <th class="p-3">Gender</th>
              <th class="p-3">Tgl Lahir</th>
              <th class="p-3">No WhatsApp</th>
              <th class="p-3">Divisi Unit</th>
              <th class="p-3">Unit ID</th>
              <th class="p-3">Plan ID</th>
              <th class="p-3">Status Face AI</th>
            </tr>
          </thead>
          <tbody class="text-slate-600">
            <tr v-for="p in listSemua" :key="p.no_jppk" class="border-b border-slate-100 hover:bg-slate-50/80">
              <td class="p-3 font-bold text-slate-900 bg-slate-50/40">{{ p.no_jppk }}</td>
              <td class="p-3 font-bold text-emerald-600 bg-emerald-50/30">{{ p.no_rm || '-' }}</td>
              <td class="p-3 font-mono font-bold text-slate-700">{{ p.npp }}</td>
              <td class="p-3 font-medium text-slate-800">{{ p.nama_peserta }}</td>
              <td class="p-3 font-bold text-slate-700">{{ p.status || '-' }}</td>
              <td class="p-3 font-medium">{{ p.nama_penanggung || '-' }}</td>
              <td class="p-3 text-center font-bold">{{ p.jenis_kelamin }}</td>
              <td class="p-3 whitespace-nowrap">{{ p.tgl_lahir }}</td>
              <td class="p-3 font-mono text-blue-600">{{ p.no_telp || '-' }}</td>
              <td class="p-3 font-medium">{{ p.divisi || '-' }}</td>
              <td class="p-3 text-center bg-slate-50/20">{{ p.unit_id }}</td>
              <td class="p-3 text-center bg-slate-50/20">{{ p.plan_id }}</td>
              <td class="p-3">
                <span v-if="p.face_embedding" class="text-emerald-600 font-bold">🟢 Aktif</span>
                <span v-else class="text-amber-500 font-bold">🔴 Kosong</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <Teleport to="body">
    
    <div v-if="modalCrudOpen" class="modal-overlay-paksa">
      <div class="modal-content-paksa">
        <h4 class="font-black text-slate-800 text-base mb-4 shrink-0 border-b pb-2">{{ isEditMode ? 'Edit Data Pasien JPPK' : 'Tambah Pasien JPPK Baru' }}</h4>
        
        <div class="space-y-3 overflow-y-auto pr-2 flex-grow" style="max-height: 60vh;">
          
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">No JPPK (Primary Key)</label>
              <input v-model="formCrud.no_jppk" type="text" :disabled="isEditMode" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700 disabled:opacity-60 focus:outline-emerald-50">
            </div>
            <div>
              <label class="text-[11px] font-bold text-emerald-600 block mb-1">No RM (Rekam Medis)</label>
              <input v-model="formCrud.no_rm" type="text" class="w-full px-4 py-2.5 bg-emerald-50/50 border border-emerald-100 rounded-xl text-xs font-bold text-slate-700 focus:outline-emerald-100">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">NPP</label>
              <input v-model="formCrud.npp" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Gender (L/P)</label>
              <select v-model="formCrud.jenis_kelamin" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
                <option value="L">Laki-Laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
          </div>
          
          <div>
            <label class="text-[11px] font-bold text-slate-400 block mb-1">Nama Lengkap Pasien</label>
            <input v-model="formCrud.nama_peserta" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Status (Peserta/Istri/Anak)</label>
              <input v-model="formCrud.status" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Nama Penanggung</label>
              <input v-model="formCrud.nama_penanggung" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>
          </div>

          <div>
            <label class="text-[11px] font-bold text-slate-400 block mb-1">Tanggal Lahir</label>
            <input v-model="formCrud.tgl_lahir" type="date" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
          </div>
          <div>
            <label class="text-[11px] font-bold text-slate-400 block mb-1">Divisi Kerja</label>
            <input v-model="formCrud.divisi" type="text" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Unit ID (Relasi)</label>
              <input v-model="formCrud.unit_id" type="number" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>
            <div>
              <label class="text-[11px] font-bold text-slate-400 block mb-1">Plan ID (Relasi)</label>
              <input v-model="formCrud.plan_id" type="number" class="w-full px-4 py-2.5 bg-slate-50 border rounded-xl text-xs font-bold text-slate-700">
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2 shrink-0 pt-3 border-t border-slate-100">
          <button @click="modalCrudOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200">Batal</button>
          <button @click="simpanAksiCrud" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700">Simpan Ke DB</button>
        </div>
      </div>
    </div>

    <div v-if="modalMukaOpen" class="modal-overlay-paksa">
      <div class="modal-content-paksa">
        <button @click="tutupModalMuka" class="absolute top-4 right-4 text-slate-400 hover:text-rose-500 bg-slate-100 hover:bg-rose-50 rounded-full w-8 h-8 flex items-center justify-center transition-all font-bold">
          ✕
        </button>

        <h4 class="font-black text-slate-800 text-sm pr-6 border-b pb-2">Registrasi Wajah Pasien: <br><span class="text-emerald-600">{{ formMuka.nama_peserta }}</span></h4>
        
        <div class="mt-4 space-y-4">
          <div>
            <label class="text-[11px] font-bold text-slate-400 block mb-1">Nomor WhatsApp Aktif Pasien</label>
            <input v-model="formMuka.no_telp" type="text" placeholder="Contoh: 0812XXXXXXXX" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-emerald-100">
          </div>
          
          <div class="relative w-full h-56 bg-slate-900 rounded-2xl overflow-hidden flex items-center justify-center shadow-inner ring-4 ring-slate-100">
            <video v-show="isCameraOpen" ref="videoRef" autoplay playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
            <img v-if="formMuka.face_image_path && !isCameraOpen" :src="formMuka.face_image_path" class="w-full h-full object-cover" />
            <div v-if="!isCameraOpen && !formMuka.face_image_path" class="text-center text-slate-500">
              <span class="text-4xl block mb-1">📸</span>
              <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kamera Non-Aktif</p>
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <button type="button" @click="isCameraOpen ? matikanKamera() : aktifkanKamera()" :class="isCameraOpen ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-slate-800 hover:bg-slate-900 text-white'" class="w-full py-2.5 font-bold text-xs rounded-xl transition-all shadow-md text-center">
              {{ isCameraOpen ? '❌ Matikan Kamera' : '📷 1. NYALAKAN KAMERA TERLEBIH DAHULU' }}
            </button>
            <button v-if="isCameraOpen" type="button" @click="ambilFotoKontan" class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl transition-all shadow-lg animate-pulse text-center border-2 border-rose-400">
              📸 2. JEPRET / TANGKAP FOTO WAJAH!
            </button>
          </div>
          
          <div class="relative flex py-1 items-center">
              <div class="flex-grow border-t border-slate-200"></div>
              <span class="flex-shrink mx-3 text-slate-400 font-bold text-[9px] uppercase tracking-widest">Atau Manual Upload</span>
              <div class="flex-grow border-t border-slate-200"></div>
          </div>
          <input @change="handleUploadFoto" type="file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 cursor-pointer hover:file:bg-emerald-100 transition-all" />
        </div>

        <div class="mt-6 flex justify-end gap-2 pt-4 border-t border-slate-100">
          <button @click="tutupModalMuka" class="px-4 py-2 text-xs font-bold text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200">Batal</button>
          <button @click="simpanRegistrasiMuka" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Kunci Embedding & Kirim</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'

const menuUtama = ref('manajemen') 
const subTab = ref('semua')       

const listSemua = ref([])
const listBelumLengkap = ref([])

const modalCrudOpen = ref(false)
const isEditMode = ref(false)
const modalMukaOpen = ref(false)

// ⚡ State Baru: Ditambahkan no_rm, status, dan nama_penanggung
const formCrud = ref({ no_jppk: '', no_rm: '', npp: '', nama_peserta: '', status: '', nama_penanggung: '', jenis_kelamin: 'L', tgl_lahir: '', divisi: '', unit_id: 1, plan_id: 2 })
const formMuka = ref({ no_jppk: '', nama_peserta: '', no_telp: '', face_image_path: '' })

const videoRef = ref(null)
const isCameraOpen = ref(false)
const kameraStream = ref(null)

const fetchPasien = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/admin/peserta')
    listSemua.value = res.data.semua
    listBelumLengkap.value = res.data.belumLengkap
  } catch (error) {
    console.error("Database gagal terkoneksi:", error)
  }
}
onMounted(fetchPasien)

const prosesExportExcel = () => {
  if (listSemua.value.length === 0) return alert("Data kosong, ekspor dibatalkan!")

  // ⚡ Update Header Excel
  const headers = ['NO JPPK', 'NO RM', 'NPP', 'NAMA LENGKAP PASIEN', 'STATUS', 'NAMA PENANGGUNG', 'GENDER', 'TANGGAL LAHIR', 'NO WHATSAPP', 'DIVISI UNIT', 'UNIT ID', 'PLAN ID', 'STATUS FACE AI']
  
  // ⚡ Update Baris Excel
  const rows = listSemua.value.map(p => [
    p.no_jppk,
    p.no_rm || '-',
    p.npp,
    p.nama_peserta,
    p.status || '-',
    p.nama_penanggung || '-',
    p.jenis_kelamin,
    p.tgl_lahir,
    p.no_telp || '-',
    p.divisi || '-',
    p.unit_id,
    p.plan_id,
    p.face_embedding ? '🟢 AKTIF (Terkunci)' : '🔴 BELUM AKTIF'
  ])
  
  const barisHeader = headers.join('\t')
  const barisData = rows.map(r => r.join('\t')).join('\n')
  const kontenMentahExcel = barisHeader + '\n' + barisData
  
  const blob = new Blob(['\uFEFF' + kontenMentahExcel], { type: 'application/vnd.ms-excel;charset=utf-8' })
  const linkUnduh = document.createElement('a')
  const urlSakti = URL.createObjectURL(blob)
  
  const tglHariIni = new Date().toISOString().slice(0, 10)
  linkUnduh.href = urlSakti
  linkUnduh.setAttribute('download', `REKAP_PASIEN_JPPK_PINDAD_${tglHariIni}.xls`)
  
  document.body.appendChild(linkUnduh)
  linkUnduh.click()
  document.body.removeChild(linkUnduh)
}

const bukaModalTambah = () => {
  isEditMode.value = false
  // ⚡ Reset dengan field baru
  formCrud.value = { no_jppk: '', no_rm: '', npp: '', nama_peserta: '', status: '', nama_penanggung: '', jenis_kelamin: 'L', tgl_lahir: '', divisi: '', unit_id: 1, plan_id: 2 }
  modalCrudOpen.value = true
}

const bukaModalEdit = (p) => {
  isEditMode.value = true
  // ⚡ Masukkan field baru saat edit
  formCrud.value = { 
    no_jppk: p.no_jppk, 
    no_rm: p.no_rm, 
    npp: p.npp, 
    nama_peserta: p.nama_peserta, 
    status: p.status, 
    nama_penanggung: p.nama_penanggung, 
    jenis_kelamin: p.jenis_kelamin, 
    tgl_lahir: p.tgl_lahir, 
    divisi: p.divisi, 
    unit_id: p.unit_id, 
    plan_id: p.plan_id 
  }
  modalCrudOpen.value = true
}

const simpanAksiCrud = async () => {
  try {
    if (isEditMode.value) {
      await axios.put(`http://localhost:8000/api/admin/peserta/${formCrud.value.no_jppk}`, formCrud.value)
    } else {
      await axios.post('http://localhost:8000/api/admin/peserta', formCrud.value)
    }
    modalCrudOpen.value = false
    fetchPasien()
    alert("Operasi database sukses diperbarui!")
  } catch (error) {
    alert("Gagal memproses data: " + (error.response?.data?.message || error.message))
  }
}

const hapusPeserta = async (no_jppk) => {
  if (confirm("Hapus permanen pasien dengan Nomor JPPK " + no_jppk + "?")) {
    try {
      await axios.delete(`http://localhost:8000/api/admin/peserta/${no_jppk}`)
      fetchPasien()
      alert("Sukses dibuang dari database.")
    } catch (error) {
      alert("Gagal menghapus data.")
    }
  }
}

const bukaModalRegistrasiMuka = (p) => {
  formMuka.value = { no_jppk: p.no_jppk, nama_peserta: p.nama_peserta, no_telp: p.no_telp || '', face_image_path: '' }
  modalMukaOpen.value = true
}

const tutupModalMuka = () => {
  matikanKamera()
  modalMukaOpen.value = false
}

const aktifkanKamera = async () => {
  try {
    isCameraOpen.value = true
    formMuka.value.face_image_path = ''
    await nextTick()
    kameraStream.value = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
    if (videoRef.value) videoRef.value.srcObject = kameraStream.value
  } catch (err) {
    alert("Izin kamera ditolak oleh browser!")
    isCameraOpen.value = false
  }
}

const matikanKamera = () => {
  if (kameraStream.value) {
    kameraStream.value.getTracks().forEach(track => track.stop())
  }
  isCameraOpen.value = false
}

const ambilFotoKontan = () => {
  const video = videoRef.value
  if (!video) return

  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  
  ctx.translate(canvas.width, 0)
  ctx.scale(-1, 1)
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
  
  formMuka.value.face_image_path = canvas.toDataURL('image/jpeg')
  matikanKamera()
}

const handleUploadFoto = (e) => {
  const file = e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => formMuka.value.face_image_path = event.target.result
  reader.readAsDataURL(file)
}

const simpanRegistrasiMuka = async () => {
  if (!formMuka.value.face_image_path) return alert("Tangkap foto wajah dahulu, Mang!")
  try {
    const res = await axios.post(`http://localhost:8000/api/admin/peserta/registrasi-muka/${formMuka.value.no_jppk}`, {
      no_telp: formMuka.value.no_telp,
      face_image_path: formMuka.value.face_image_path
    })
    if (res.data.status === 'success') {
      alert(res.data.message)
      tutupModalMuka()
      fetchPasien()
    } else {
      alert("Penolakan AI: " + res.data.message)
    }
  } catch (error) {
    const errorReal = error.response?.data?.message || error.message
    alert("Detail Gangguan Server:\n" + errorReal + "\n\nSolusi: Cek apakah uvicorn Python port 8001 aktif!")
  }
}
</script>

<style scoped>
/* PENGUNCI OVERLAY HITAM TRANSLUCENT KESELURUHAN LAYAR */
.modal-overlay-paksa {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  background-color: rgba(15, 23, 42, 0.75) !important;
  z-index: 999999 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  backdrop-filter: blur(4px) !important;
}

/* KOTAK POP-UP KONTEN PUTIH DI TENGAH */
.modal-content-paksa {
  background-color: #ffffff !important;
  border-radius: 24px !important;
  width: 100% !important;
  max-width: 440px !important;
  padding: 24px !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
  position: relative !important;
  margin: 0 16px !important;
  display: flex !important;
  flex-direction: column !important;
  max-height: 90vh !important;
}

/* Transisi halus pergantian halaman */
.animate-fade-in {
  animation: fadeInEffect 0.35s ease-out;
}
@keyframes fadeInEffect {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>