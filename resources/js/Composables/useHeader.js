import { computed } from 'vue'; 

export function useHeader(props) {
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

  return {
    tampilanNamaRole,
    tampilanSubRole
  }
}
