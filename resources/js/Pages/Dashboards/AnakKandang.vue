<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    ternaks: { type: Array, default: () => [] }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const hitungHariDiKandang = (tglMasuk) => {
    if (!tglMasuk) return 1;
    const masuk = new Date(tglMasuk);
    const now = new Date();
    const diffTime = Math.abs(now - masuk);
    return Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
};

// Helper status bunting (mencakup 'Hamil', 'Bunting', 'POSITIF BUNTING')
const cekIsBunting = (status) => {
    if (!status) return false;
    const s = String(status).toUpperCase();
    return s.includes('HAMIL') || s.includes('BUNTING');
};

// Helper status sakit / karantina
const cekIsSakitAtauKarantina = (t) => {
    const k = String(t.kondisi_kesehatan || '').toUpperCase();
    const p = String(t.prediksi_ai || '').toUpperCase();
    return k === 'SAKIT' || k === 'KARANTINA' || p === 'SUSPECT' || (t.persentase_sembuh !== undefined && t.persentase_sembuh < 100);
};

// FILTER KETAT: Pastikan ternak yang sudah terjual atau mati TIDAK dihitung di populasi kandang
const ternakAktif = computed(() => {
    return props.ternaks.filter(t => {
        const hidup = String(t.status_hidup || '').toLowerCase();
        const prog = String(t.program || '').toLowerCase();

        const isTerjual = hidup === 'terjual' || prog === 'terjual' || Boolean(t.tgl_terjual) || (Number(t.harga_jual) > 0);
        const isMati = hidup === 'mati' || prog === 'arsip kematian' || Boolean(t.tgl_kematian);

        return !isTerjual && !isMati;
    });
});

const totalAktif = computed(() => ternakAktif.value.length);

const totalSakit = computed(() => {
    return ternakAktif.value.filter(t => cekIsSakitAtauKarantina(t)).length;
});

const totalBunting = computed(() => {
    return ternakAktif.value.filter(t => cekIsBunting(t.status_kehamilan)).length;
});

const siapPanen = computed(() => ternakAktif.value.filter(t => parseFloat(t.berat_kg) >= 30 && !cekIsSakitAtauKarantina(t)).length);

const notifikasiPintar = computed(() => {
    const list = [];

    const sakits = ternakAktif.value.filter(t => cekIsSakitAtauKarantina(t));
    if (sakits.length > 0) {
        list.push({
            id: 'notif-medis',
            level: 'danger',
            kategori: 'Perawatan Medis & Isolasi',
            judul: 'Ternak Membutuhkan Penanganan Medis / Karantina',
            pesan: `Terdapat ${sakits.length} ekor kambing sedang sakit / dalam pemantauan karantina. Cek dosis obat dan persentase kesembuhan.`,
            tindakan_sop: 'Pindahkan ke Kandang Karantina (KRT-01), berikan injeksi antibiotik/vitamin B-kompleks, dan pisahkan dari pakan hijauan basah.',
            kandang_tujuan: 'KRT-01 (Karantina Medis)',
            daftar_ternak: sakits
        });
    }

    const butuhUsg = ternakAktif.value.filter(t => {
        const k = (t.kandang || '').toLowerCase();
        const hari = hitungHariDiKandang(t.tgl_masuk_kandang);
        return (k.includes('a1') || k.includes('kawin')) && hari >= 36;
    });
    if (butuhUsg.length > 0) {
        list.push({
            id: 'notif-usg',
            level: 'warning',
            kategori: 'Siklus Perkawinan (SOP A1)',
            judul: 'Waktunya Pemeriksaan USG Kebuntingan',
            pesan: `Terdapat ${butuhUsg.length} ekor indukan telah melewati 1,2 bulan (>= 36 hari) di Kandang Kawin A1. Segera lakukan USG.`,
            tindakan_sop: 'Lakukan USG bersama dokter/pakar. Jika positif bunting, segera pindahkan ke Kandang Bunting (B1). Jika negatif, kembalikan ke masa kawin.',
            kandang_tujuan: 'Kandang B1 (Bunting) jika Hamil',
            daftar_ternak: butuhUsg
        });
    }

    const panens = ternakAktif.value.filter(t => parseFloat(t.berat_kg) >= 30 && !cekIsSakitAtauKarantina(t));
    if (panens.length > 0) {
        list.push({
            id: 'notif-panen',
            level: 'success',
            kategori: 'Komersial & Siap Panen',
            judul: 'Ternak Siap Seleksi Panen / Jual',
            pesan: `Terdapat ${panens.length} ekor kambing telah mencapai bobot komersial prima (>= 30 Kg).`,
            tindakan_sop: 'Lakukan timbang akhir untuk verifikasi bobot, lalu alokasikan status ternak ke program "Siap Jual" agar langsung terbit di katalog kasir Owner.',
            kandang_tujuan: 'Kandang Penggemukan Akhir / Display Kasir',
            daftar_ternak: panens
        });
    }

    return list;
});

const filterAktif = ref('All');
const searchQuery = ref('');

const setFilter = (tipe) => {
    filterAktif.value = filterAktif.value === tipe ? 'All' : tipe;
};

const filteredTernaks = computed(() => {
    return ternakAktif.value.filter(t => {
        const query = searchQuery.value.toLowerCase();
        const matchSearch = (t.rfid_tag || '').toLowerCase().includes(query) ||
                            (t.ras || '').toLowerCase().includes(query) ||
                            (t.kandang || '').toLowerCase().includes(query) ||
                            (t.program || '').toLowerCase().includes(query);

        let matchKategori = true;
        if (filterAktif.value === 'Sakit') {
            matchKategori = cekIsSakitAtauKarantina(t);
        } else if (filterAktif.value === 'Hamil') {
            matchKategori = cekIsBunting(t.status_kehamilan);
        } else if (filterAktif.value === 'SiapPanen') {
            matchKategori = parseFloat(t.berat_kg) >= 30 && !cekIsSakitAtauKarantina(t);
        }

        return matchSearch && matchKategori;
    });
});

const isModalSopOpen = ref(false);
const selectedNotif = ref(null);

const bukaModalNotif = (notif) => {
    selectedNotif.value = notif;
    isModalSopOpen.value = true;
};

const isModalMedisOpen = ref(false);
const ternakTerpilihMedis = ref(null);
const formMedis = useForm({
    kondisi_kesehatan: 'Sakit',
    persentase_sembuh: 50,
    gejala_sakit: '',
    penanganan_medis: '',
    pindah_karantina: true
});

const bukaModalMedis = (t) => {
    ternakTerpilihMedis.value = t;
    formMedis.kondisi_kesehatan = t.kondisi_kesehatan === 'Karantina' ? 'Karantina' : 'Sakit';
    formMedis.persentase_sembuh = t.persentase_sembuh || 50;
    formMedis.gejala_sakit = t.gejala_sakit || '';
    formMedis.penanganan_medis = t.penanganan_medis || '';
    formMedis.pindah_karantina = t.kandang !== 'KRT-01';
    isModalMedisOpen.value = true;
};

const simpanMedis = () => {
    if (!ternakTerpilihMedis.value) return;
    formMedis.post(route('operator.katalog.darurat', ternakTerpilihMedis.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalMedisOpen.value = false
    });
};

const isModalPindahOpen = ref(false);
const ternakTerpilihPindah = ref(null);
const formPindah = useForm({
    kandang: 'Blok A',
    program: 'Produksi'
});

const bukaModalPindah = (t, defaultKandang = '') => {
    ternakTerpilihPindah.value = t;
    formPindah.kandang = defaultKandang || t.kandang || 'Blok A';
    formPindah.program = t.program || 'Produksi';
    isModalPindahOpen.value = true;
};

const simpanPindahKandang = () => {
    if (!ternakTerpilihPindah.value) return;
    formPindah.put(route('operator.katalog.update', ternakTerpilihPindah.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isModalPindahOpen.value = false;
            if (isModalSopOpen.value) isModalSopOpen.value = false;
        }
    });
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['ternaks']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Pusat Kendali - Anak Kandang" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR OPERATOR -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-emerald-200">
                    A
                </div>
                <div>
                    <span class="text-base font-bold text-slate-800 block leading-tight">Kandang Pintar</span>
                    <span class="text-[10px] text-emerald-600 font-semibold tracking-wider uppercase">Portal Anak Kandang</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto py-5 px-3 space-y-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3">Ringkasan Tugas</div>
                
                <Link :href="route('dashboard')" 
                      :class="$page.url === '/dashboard' ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url === '/dashboard' ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Pusat Kendali Lapangan
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Data & Inventaris</div>
                
                <Link :href="route('operator.katalog')" 
                      :class="$page.url.startsWith('/operator/katalog') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/operator/katalog') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    Katalog Data Ternak
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">SOP Kandang & Program</div>
                
                <Link :href="route('operator.program')" 
                      :class="$page.url.startsWith('/operator/program') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/operator/program') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    Program Pembibitan & Gemuk
                </Link>
                <Link :href="route('operator.kandang')" 
                      :class="$page.url.startsWith('/operator/pipeline-kandang') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/operator/pipeline-kandang') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Pipeline Siklus Kandang
                </Link>
                <Link :href="route('operator.karantina')" 
                      :class="$page.url.startsWith('/operator/karantina') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/operator/karantina') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Karantina & Medis
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Arsip</div>
                
                <Link :href="route('operator.kasir')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Kasir Penjualan Lapangan
                </Link>
                <Link :href="route('operator.arsip')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    Gudang Data Arsip
                </Link>
            </div>

            <div class="p-4 border-t border-slate-100 text-center mt-auto">
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 JJFarm.</p>
                <p class="text-[10px] text-slate-400">Operator Field System</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.06)] mx-4 lg:mx-6 mt-4 px-5 py-3.5 flex justify-between items-center z-40">
                <div class="flex items-center gap-3">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Pusat Kendali Operasional Lapangan</h2>
                        <p class="text-xs text-slate-400">Monitoring harian populasi kandang, tindakan karantina medis, dan kesiapan panen ternak</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 relative">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-700">{{ user?.name || 'Petugas Lapangan' }}</div>
                        <div class="text-[11px] text-emerald-600 font-semibold uppercase tracking-wider">Anak Kandang</div>
                    </div>
                    
                    <button @click="isDropdownOpen = !isDropdownOpen" 
                            @blur="setTimeout(() => isDropdownOpen = false, 250)" 
                            class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold uppercase transition hover:ring-2 hover:ring-emerald-300 border-2 border-white shadow-sm flex items-center justify-center cursor-pointer">
                        {{ user?.name?.charAt(0) || 'A' }}
                    </button>

                    <div v-show="isDropdownOpen" class="absolute right-0 top-12 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800">{{ user?.name || 'Petugas Lapangan' }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ user?.email || 'operator@jjfarm.com' }}</p>
                            <span class="inline-block mt-1.5 px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-700 rounded-md border border-emerald-100">
                                Operator Lapangan
                            </span>
                        </div>
                        <div class="py-1">
                            <Link :href="route('profile.edit')" class="flex items-center px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-emerald-700">Profil Akun</Link>
                            <Link :href="route('profile.edit')" class="flex items-center px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-emerald-700">Sesuaikan Password</Link>
                        </div>
                        <div class="border-t border-slate-100 pt-1">
                            <Link :href="route('logout')" method="post" as="button" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Logout Keluar</Link>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <div class="rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg shadow-emerald-600/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="inline-block bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-2">SOP Lapangan Aktif</span>
                        <h3 class="text-2xl font-black tracking-tight">Sistem Monitoring & Tindakan Ternak</h3>
                        <p class="text-xs text-emerald-100 mt-1">Data tersinkronisasi otomatis dengan sensor RFID, timbangan, dan catatan medis karantina.</p>
                    </div>
                    <Link :href="route('operator.katalog')" class="bg-white text-emerald-800 font-bold px-4 py-2 rounded-xl text-xs hover:bg-emerald-50 transition shadow-sm shrink-0">
                        Buka Katalog Lengkap
                    </Link>
                </div>

                <!-- ROW 1: KARTU METRIK INTERAKTIF -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <div @click="setFilter('All')" 
                         class="rounded-2xl p-5 border shadow-sm flex items-center justify-between cursor-pointer transition-all hover:scale-[1.02]"
                         :class="filterAktif === 'All' ? 'bg-white border-emerald-500 ring-2 ring-emerald-200' : 'bg-gradient-to-br from-emerald-50/80 via-white to-white border-emerald-200/60'">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Populasi Kandang</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ totalAktif }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Semua Ternak Aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                    </div>

                    <div @click="setFilter('Sakit')" 
                         class="rounded-2xl p-5 border shadow-sm flex items-center justify-between cursor-pointer transition-all hover:scale-[1.02]"
                         :class="filterAktif === 'Sakit' ? 'bg-white border-rose-500 ring-2 ring-rose-200' : 'bg-gradient-to-br from-rose-50/80 via-white to-white border-rose-200/60'">
                        <div>
                            <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Perhatian Medis</span>
                            <h3 class="text-2xl font-black text-rose-600 mt-1">{{ totalSakit }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-rose-500 font-semibold mt-0.5">Klik untuk Filter Sakit</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                    </div>

                    <div @click="setFilter('Hamil')" 
                         class="rounded-2xl p-5 border shadow-sm flex items-center justify-between cursor-pointer transition-all hover:scale-[1.02]"
                         :class="filterAktif === 'Hamil' ? 'bg-white border-sky-500 ring-2 ring-sky-200' : 'bg-gradient-to-br from-sky-50/80 via-white to-white border-sky-200/60'">
                        <div>
                            <span class="text-xs font-bold text-sky-700 uppercase tracking-wider">Indukan Bunting</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">{{ totalBunting }} <span class="text-xs font-normal text-slate-400">Indukan</span></h3>
                            <p class="text-[11px] text-sky-600 font-semibold mt-0.5">Klik untuk Filter Bunting</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <div @click="setFilter('SiapPanen')" 
                         class="rounded-2xl p-5 border shadow-sm flex items-center justify-between cursor-pointer transition-all hover:scale-[1.02]"
                         :class="filterAktif === 'SiapPanen' ? 'bg-white border-indigo-500 ring-2 ring-indigo-200' : 'bg-gradient-to-br from-indigo-50/80 via-white to-white border-indigo-200/60'">
                        <div>
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Bobot Siap Jual</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ siapPanen }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-indigo-500 font-semibold mt-0.5">Klik untuk Filter >= 30 Kg</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: NOTIFIKASI PINTAR LAPANGAN -->
                <div v-if="notifikasiPintar.length > 0" class="space-y-2.5">
                    <div v-for="(n, idx) in notifikasiPintar" :key="idx" 
                         @click="bukaModalNotif(n)"
                         class="p-4 rounded-2xl border flex items-center justify-between gap-3 shadow-xs cursor-pointer transition-all hover:shadow-md"
                         :class="{
                             'bg-rose-50/90 border-rose-200 hover:bg-rose-100/70 text-rose-900': n.level === 'danger',
                             'bg-amber-50/90 border-amber-200 hover:bg-amber-100/70 text-amber-900': n.level === 'warning',
                             'bg-emerald-50/90 border-emerald-200 hover:bg-emerald-100/70 text-emerald-900': n.level === 'success'
                         }">
                        <div class="flex items-start gap-3">
                            <span class="p-1.5 rounded-lg mt-0.5"
                                  :class="{
                                      'bg-rose-200 text-rose-700': n.level === 'danger',
                                      'bg-amber-200 text-amber-700': n.level === 'warning',
                                      'bg-emerald-200 text-emerald-700': n.level === 'success'
                                  }">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-xs leading-tight">{{ n.judul }}</h4>
                                    <span class="text-[10px] uppercase font-extrabold px-2 py-0.5 rounded-full bg-white/80 shadow-xs">{{ n.kategori }}</span>
                                </div>
                                <p class="text-xs mt-1 opacity-90">{{ n.pesan }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs font-bold bg-white px-3 py-1.5 rounded-xl shadow-xs inline-flex items-center gap-1.5">
                                Tindakan SOP
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: TABEL KENDALI TERNAK -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-slate-800 text-base">Daftar Monitoring & Tindakan Medis Harian</h4>
                                <span v-if="filterAktif !== 'All'" class="text-xs font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">
                                    Filter: {{ filterAktif }} ({{ filteredTernaks.length }} Ekor)
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Pemeriksaan fisik, evaluasi kesehatan, dan instruksi dokter hewan/pakar</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras / Kandang..." 
                                   class="px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none w-full sm:w-56">
                            
                            <button v-if="filterAktif !== 'All'" @click="filterAktif = 'All'" class="text-xs font-semibold text-rose-600 hover:underline">
                                Reset Filter
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Penempatan Kandang</th>
                                    <th class="px-6 py-4 font-semibold">Bobot Terkini</th>
                                    <th class="px-6 py-4 font-semibold">Status Kesehatan</th>
                                    <th class="px-6 py-4 font-semibold">Status Reproduksi</th>
                                    <th class="px-6 py-4 font-semibold">Instruksi & Catatan Pakar</th>
                                    <th class="px-6 py-4 font-semibold text-center">Tindakan Lapangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredTernaks" :key="t.id" class="hover:bg-slate-50/60 transition-colors">
                                    
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 font-mono text-sm">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-500 text-xs mt-0.5 font-medium">
                                            {{ t.ras || 'Lokal' }} • {{ t.jenis_kelamin || 'Betina' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-700 text-xs">{{ t.kandang || 'Blok A' }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            Hari ke-{{ hitungHariDiKandang(t.tgl_masuk_kandang) }} • {{ t.program || 'Produksi' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-black text-slate-800 font-mono">{{ t.berat_kg || 0 }} Kg</div>
                                        <div class="mt-0.5">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold"
                                                  :class="(t.selisih_kg || 0) >= 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'">
                                                {{ (t.selisih_kg || 0) > 0 ? '+' : '' }}{{ t.selisih_kg || 0 }} Kg
                                            </span>
                                        </div>
                                    </td>

                                    <!-- KOLOM STATUS KESEHATAN (DINAMIS & AKURAT) -->
                                    <td class="px-6 py-4">
                                        <div v-if="t.kondisi_kesehatan === 'Karantina'">
                                            <span class="bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                                Karantina / Isolasi
                                            </span>
                                            <div class="text-[11px] text-slate-500 max-w-[150px] truncate mt-1">
                                                {{ t.gejala_sakit || 'Perlu isolasi ketat' }}
                                            </div>
                                        </div>
                                        <div v-else-if="cekIsSakitAtauKarantina(t)">
                                            <span class="bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                Sakit ({{ t.persentase_sembuh || 50 }}% Sembuh)
                                            </span>
                                            <div class="text-[11px] text-slate-500 max-w-[150px] truncate mt-1">
                                                {{ t.gejala_sakit || 'Perlu penanganan medis' }}
                                            </div>
                                        </div>
                                        <div v-else>
                                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Sehat Prima (100%)
                                            </span>
                                        </div>
                                    </td>

                                    <!-- KOLOM STATUS REPRODUKSI -->
                                    <td class="px-6 py-4">
                                        <span v-if="cekIsBunting(t.status_kehamilan)" class="bg-sky-50 text-sky-700 border border-sky-200 px-2.5 py-1 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            {{ t.status_kehamilan }}
                                        </span>
                                        <span v-else class="bg-slate-50 text-slate-500 border border-slate-200 px-2.5 py-1 rounded-lg text-xs font-medium">
                                            {{ (!t.status_kehamilan || t.status_kehamilan === 'N/A') ? 'Non-Bunting' : t.status_kehamilan }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-xs">
                                        <div class="font-semibold text-slate-700">{{ t.keputusan_pakar || 'Pemeriksaan Rutin' }}</div>
                                        <div class="text-slate-400 text-[11px] max-w-[200px] truncate mt-0.5" :title="t.catatan_pakar">
                                            {{ t.catatan_pakar || 'Pemeriksaan fisik normal, ternak sehat.' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button @click="bukaModalPindah(t)" 
                                                    class="px-2.5 py-1.5 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition border border-slate-200">
                                                Pindah Kandang
                                            </button>
                                            <button @click="bukaModalMedis(t)" 
                                                    class="px-2.5 py-1.5 text-xs font-bold rounded-xl transition border"
                                                    :class="cekIsSakitAtauKarantina(t) ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 text-slate-600 hover:bg-rose-50 hover:text-rose-600 border-slate-200'">
                                                Medis
                                            </button>
                                        </div>
                                    </td>

                                </tr>

                                <tr v-if="filteredTernaks.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        Tidak ada catatan ternak yang sesuai dengan filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL SOP -->
                <div v-if="isModalSopOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-100 my-8">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center"
                             :class="{
                                 'bg-rose-50': selectedNotif?.level === 'danger',
                                 'bg-amber-50': selectedNotif?.level === 'warning',
                                 'bg-emerald-50': selectedNotif?.level === 'success'
                             }">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-white shadow-xs">
                                    {{ selectedNotif?.kategori }}
                                </span>
                                <h3 class="font-bold text-slate-800 text-base mt-1">{{ selectedNotif?.judul }}</h3>
                            </div>
                            <button @click="isModalSopOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-2">
                                <div class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Petunjuk Tindakan Petugas Lapangan (SOP)
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">{{ selectedNotif?.tindakan_sop }}</p>
                                <div class="text-xs font-semibold text-slate-800 pt-1">
                                    Rekomendasi Penempatan: <span class="text-emerald-700 font-bold font-mono">{{ selectedNotif?.kandang_tujuan }}</span>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold text-slate-700 uppercase mb-2">
                                    Daftar Ternak yang Perlu Ditindaklanjuti ({{ selectedNotif?.daftar_ternak?.length }} Ekor)
                                </h4>
                                <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl max-h-60 overflow-y-auto">
                                    <div v-for="t in selectedNotif?.daftar_ternak" :key="t.id" class="p-3.5 flex items-center justify-between hover:bg-slate-50 text-xs">
                                        <div>
                                            <span class="font-mono font-bold text-slate-800">{{ t.rfid_tag }}</span>
                                            <span class="text-slate-400 ml-2">{{ t.ras }} • Bobot: {{ t.berat_kg }} Kg</span>
                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                Kandang Saat Ini: <span class="font-semibold text-slate-700">{{ t.kandang }}</span> (Hari ke-{{ hitungHariDiKandang(t.tgl_masuk_kandang) }})
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <button @click="bukaModalPindah(t, selectedNotif?.level === 'danger' ? 'KRT-01' : 'Blok B')" 
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-xs">
                                                Pindahkan Kandang
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="button" @click="isModalSopOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                                    Tutup Panduan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL PINDAH KANDANG -->
                <div v-if="isModalPindahOpen" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Pindahkan Lokasi Kandang</h3>
                                <p class="text-xs text-slate-400">Hari tinggal akan otomatis dihitung mulai hari ke-1</p>
                            </div>
                            <button @click="isModalPindahOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="simpanPindahKandang" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihPindah?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihPindah?.ras }} (Kandang asal: {{ ternakTerpilihPindah?.kandang }})</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Pilih Kandang Tujuan</label>
                                <select v-model="formPindah.kandang" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Blok A">Blok A (Penggemukan IoT)</option>
                                    <option value="Blok B">Blok B (Indukan Bunting)</option>
                                    <option value="KRT-01">KRT-01 (Karantina Medis)</option>
                                    <option value="Kandang A1">Kandang A1 (Kawin)</option>
                                    <option value="Kandang B1">Kandang B1 (Bunting / Hamil)</option>
                                    <option value="Kandang C1">Kandang C1 (Melahirkan)</option>
                                    <option value="Kandang D1">Kandang D1 (Menyusui)</option>
                                    <option value="Kandang E1">Kandang E1 (Sapih / Cempe)</option>
                                    <option value="Kandang B2">Kandang B2 (Penggemukan)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Program Terkait</label>
                                <select v-model="formPindah.program" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Produksi">Produksi</option>
                                    <option value="Pembibitan">Pembibitan</option>
                                    <option value="Penggemukan">Penggemukan</option>
                                    <option value="Karantina">Karantina</option>
                                    <option value="Siap Jual">Siap Jual</option>
                                </select>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalPindahOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl transition font-semibold">Batal</button>
                                <button type="submit" :disabled="formPindah.processing" class="px-5 py-2 text-white bg-emerald-600 hover:bg-emerald-700 font-bold rounded-xl transition shadow-sm">
                                    Konfirmasi Pindah
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL MEDIS DARURAT -->
                <div v-if="isModalMedisOpen" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-rose-50">
                            <div>
                                <h3 class="font-bold text-rose-800 text-base">Tanggap Medis & Karantina</h3>
                                <p class="text-xs text-rose-600">Catat penanganan darurat ternak sakit di lapangan</p>
                            </div>
                            <button @click="isModalMedisOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="simpanMedis" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihMedis?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihMedis?.ras }} (Kandang saat ini: {{ ternakTerpilihMedis?.kandang }})</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Status Kondisi</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="formMedis.kondisi_kesehatan = 'Sakit'"
                                            class="py-2 font-bold rounded-xl border transition"
                                            :class="formMedis.kondisi_kesehatan === 'Sakit' ? 'bg-rose-50 border-rose-300 text-rose-700' : 'border-slate-200 text-slate-500'">
                                        Sakit / Isolasi
                                    </button>
                                    <button type="button" @click="formMedis.kondisi_kesehatan = 'Sehat'; formMedis.persentase_sembuh = 100"
                                            class="py-2 font-bold rounded-xl border transition"
                                            :class="formMedis.kondisi_kesehatan === 'Sehat' ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'border-slate-200 text-slate-500'">
                                        Pulih / Sehat Prima
                                    </button>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between font-bold text-slate-600 uppercase mb-1">
                                    <span>Tingkat Kesembuhan</span>
                                    <span class="text-rose-600 font-mono text-sm font-bold">{{ formMedis.persentase_sembuh }}%</span>
                                </div>
                                <input type="range" min="0" max="100" step="5" v-model.number="formMedis.persentase_sembuh"
                                       class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-rose-600">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Gejala Sakit / Diagnosa Awal</label>
                                <input v-model="formMedis.gejala_sakit" type="text" placeholder="Contoh: Nafsu makan turun, kembung"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-rose-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Tindakan Medis & Obat yang Diberikan</label>
                                <textarea v-model="formMedis.penanganan_medis" rows="2" placeholder="Contoh: Injeksi Vitamin B kompleks 2ml..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-rose-200 outline-none"></textarea>
                            </div>

                            <div v-if="formMedis.kondisi_kesehatan !== 'Sehat'" class="flex items-center gap-2 pt-1">
                                <input id="pindah_krt_cmd" type="checkbox" v-model="formMedis.pindah_karantina" class="rounded text-rose-600 focus:ring-rose-200">
                                <label for="pindah_krt_cmd" class="text-xs font-semibold text-slate-700">Pindahkan otomatis ke Kandang Karantina (KRT-01)</label>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalMedisOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl transition font-semibold">Batal</button>
                                <button type="submit" :disabled="formMedis.processing" class="px-5 py-2 text-white bg-rose-600 hover:bg-rose-700 font-bold rounded-xl transition shadow-sm">
                                    Simpan Laporan Medis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>