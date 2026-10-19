<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    kandangs: { type: Array, default: () => [] },
    notifikasi_siklus: { type: Array, default: () => [] },
    semua_ternak: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_kandang: 0,
            total_populasi: 0,
            total_kapasitas: 0,
            total_alert_siklus: 0
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

// Filter & Pencarian Kandang
const filterTipe = ref('All');
const searchQuery = ref('');

// Helper Deteksi Bunting
const cekIsBunting = (status) => {
    if (!status) return false;
    const s = String(status).toUpperCase();
    return s.includes('HAMIL') || s.includes('BUNTING');
};

const filteredKandangs = computed(() => {
    return props.kandangs.filter(k => {
        const matchTipe = filterTipe.value === 'All' ? true : k.tipe_kandang === filterTipe.value;
        const q = (searchQuery.value || '').toLowerCase();
        const kode = (k.kode_kandang || '').toLowerCase();
        const nama = (k.nama_kandang || '').toLowerCase();
        const tipe = (k.tipe_kandang || '').toLowerCase();
        const matchSearch = kode.includes(q) || nama.includes(q) || tipe.includes(q);
        return matchTipe && matchSearch;
    });
});

// Target Hari Berdasarkan Tipe Kandang
const getTargetHariSop = (kandangNama) => {
    const k = (kandangNama || '').toLowerCase();
    if (k.includes('a1') || k.includes('kawin')) return { target: 36, label: 'USG Kebuntingan' };
    if (k.includes('bunting') || k.includes('b1') || k.includes('blok b')) return { target: 100, label: 'Persiapan Lahir' };
    if (k.includes('melahirkan') || k.includes('c1')) return { target: 37, label: 'Pindah Menyusui' };
    if (k.includes('menyusui') || k.includes('d1')) return { target: 30, label: 'Pemisahan Sapih' };
    if (k.includes('sapih') || k.includes('e1')) return { target: 240, label: 'Lulus Sapih Dewasa' };
    if (k.includes('krt') || k.includes('karantina')) return { target: 14, label: 'Karantina Medis' };
    return { target: 90, label: 'Evaluasi Pembesaran' };
};

// MODAL 1: FORM TAMBAH / EDIT KANDANG
const isModalKandangOpen = ref(false);
const isEditKandang = ref(false);
const formKandang = useForm({
    id: null,
    kode_kandang: '',
    nama_kandang: '',
    tipe_kandang: 'Kawin',
    kapasitas: 20,
    keterangan: ''
});

const bukaModalTambahKandang = () => {
    isEditKandang.value = false;
    formKandang.reset();
    formKandang.tipe_kandang = 'Kawin';
    formKandang.kapasitas = 20;
    isModalKandangOpen.value = true;
};

const bukaModalEditKandang = (k) => {
    isEditKandang.value = true;
    formKandang.id = k.id;
    formKandang.kode_kandang = k.kode_kandang;
    formKandang.nama_kandang = k.nama_kandang;
    formKandang.tipe_kandang = k.tipe_kandang;
    formKandang.kapasitas = k.kapasitas;
    formKandang.keterangan = k.keterangan === '-' ? '' : k.keterangan;
    isModalKandangOpen.value = true;
};

const submitKandang = () => {
    if (isEditKandang.value) {
        formKandang.put(route('operator.kandang.update', formKandang.id), {
            preserveScroll: true,
            onSuccess: () => isModalKandangOpen.value = false
        });
    } else {
        formKandang.post(route('operator.kandang.store'), {
            preserveScroll: true,
            onSuccess: () => isModalKandangOpen.value = false
        });
    }
};

const hapusKandang = (k) => {
    if (k.total_ekor > 0) {
        alert(`Kandang ${k.nama_kandang} masih berisi ${k.total_ekor} ekor ternak! Pindahkan ternak terlebih dahulu sebelum menghapus kandang.`);
        return;
    }
    if (confirm(`Yakin ingin menghapus ${k.nama_kandang} (${k.kode_kandang})?`)) {
        router.delete(route('operator.kandang.destroy', k.id), { preserveScroll: true });
    }
};

// MODAL 2: INSPEKSI PENGHUNI KANDANG
const isModalPenghuniOpen = ref(false);
const selectedKandangPenghuni = ref(null);

const bukaModalPenghuni = (k) => {
    selectedKandangPenghuni.value = k;
    isModalPenghuniOpen.value = true;
};

// MODAL 3: MUTASI KANDANG
const isModalMutasiOpen = ref(false);
const ternakTerpilihMutasi = ref(null);
const formMutasi = useForm({
    kandang_tujuan: '',
    program_tujuan: '',
    tgl_masuk_kandang: new Date().toISOString().split('T')[0],
    catatan: ''
});

const bukaModalMutasi = (t, defaultTujuan = '') => {
    ternakTerpilihMutasi.value = t;
    formMutasi.kandang_tujuan = defaultTujuan || '';
    formMutasi.program_tujuan = t.program || 'Produksi';
    formMutasi.tgl_masuk_kandang = new Date().toISOString().split('T')[0];
    formMutasi.catatan = `Mutasi dari ${t.kandang} (Hari ke-${t.hari_di_kandang}).`;
    isModalMutasiOpen.value = true;
};

const setHariSimulasi = (jumlahHariLalu) => {
    const d = new Date();
    d.setDate(d.getDate() - jumlahHariLalu);
    formMutasi.tgl_masuk_kandang = d.toISOString().split('T')[0];
};

const submitMutasi = () => {
    if (!ternakTerpilihMutasi.value) return;
    formMutasi.post(route('operator.kandang.mutasi', ternakTerpilihMutasi.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isModalMutasiOpen.value = false;
            if (isModalPenghuniOpen.value) isModalPenghuniOpen.value = false;
        }
    });
};

// Auto-Polling Real-time 5 Detik
let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['kandangs', 'notifikasi_siklus', 'stats', 'semua_ternak']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Pipeline Siklus Kandang - Operator" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR OPERATOR LENGKAP -->
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
                <Link :href="route('dashboard')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Pusat Kendali Lapangan
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Data & Inventaris</div>
                <Link :href="route('operator.katalog')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    Katalog Data Ternak
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">SOP Kandang & Program</div>
                <Link :href="route('operator.program')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    Program Pembibitan & Gemuk
                </Link>

                <!-- MENU PIPELINE AKTIF HIJAU -->
                <Link :href="route('operator.kandang')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Pipeline Siklus Kandang
                </Link>
                
                <Link :href="route('operator.karantina')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Pipeline Siklus & Manajemen Kandang</h2>
                        <p class="text-xs text-slate-400">Pengaturan kapasitas kandang, alur 6-stage biologis, dan pelacakan hari tinggal ternak</p>
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
                            <p class="text-xs font-bold text-slate-800">{{ user?.name }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ user?.email }}</p>
                            <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-700 rounded-md">Operator Lapangan</span>
                        </div>
                        <div class="py-1">
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Profil & Ubah Akun</Link>
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Sesuaikan Password</Link>
                        </div>
                        <div class="border-t border-slate-100 pt-1">
                            <Link :href="route('logout')" method="post" as="button" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Logout Keluar</Link>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- ROW 1: KARTU METRIK KANDANG & POPULASI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm">
                        <span class="text-xs font-bold text-emerald-700 uppercase">Jumlah Unit Kandang</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_kandang }} <span class="text-xs font-normal text-slate-400">Kandang</span></h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Dapat Ditambah Dinamis</p>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm">
                        <span class="text-xs font-bold text-sky-700 uppercase">Ternak Aktif di Kandang</span>
                        <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.total_populasi }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-sky-600 font-medium mt-0.5">Populasi Hidup Terkelola</p>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm">
                        <span class="text-xs font-bold text-indigo-700 uppercase">Total Daya Tampung</span>
                        <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ stats.total_kapasitas }} <span class="text-xs font-normal text-slate-400">Kapasitas Ekor</span></h3>
                        <p class="text-[11px] text-indigo-600 font-medium mt-0.5">Akumulasi Seluruh Kandang</p>
                    </div>

                    <div class="rounded-2xl p-5 border shadow-sm transition-all"
                         :class="stats.total_alert_siklus > 0 ? 'bg-gradient-to-br from-amber-50/90 via-white to-white border-amber-300 ring-2 ring-amber-200/50' : 'bg-gradient-to-br from-slate-50/80 via-white to-white border-slate-200/60'">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold uppercase" :class="stats.total_alert_siklus > 0 ? 'text-amber-700' : 'text-slate-500'">Alert Pindah Siklus</span>
                            <span v-if="stats.total_alert_siklus > 0" class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                        </div>
                        <h3 class="text-2xl font-black mt-1" :class="stats.total_alert_siklus > 0 ? 'text-amber-700' : 'text-slate-700'">
                            {{ stats.total_alert_siklus }} <span class="text-xs font-normal text-slate-400">Peringatan</span>
                        </h3>
                        <p class="text-[11px] font-semibold mt-0.5" :class="stats.total_alert_siklus > 0 ? 'text-amber-600' : 'text-slate-400'">
                            {{ stats.total_alert_siklus > 0 ? 'Tindakan SOP Diperlukan!' : 'Semua Siklus Terkendali' }}
                        </p>
                    </div>
                </div>

                <!-- ROW 2: PUSAT NOTIFIKASI REAL-TIME SIKLUS PINTAR -->
                <div class="space-y-3">
                    <div v-if="notifikasi_siklus.length > 0" class="space-y-3">
                        <div class="flex justify-between items-center px-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <h4 class="text-xs font-extrabold uppercase text-slate-700 tracking-wider">
                                    Peringatan Real-Time Tindakan Lapangan ({{ notifikasi_siklus.length }})
                                </h4>
                            </div>
                            <span class="text-[11px] font-bold text-amber-700 bg-amber-100 border border-amber-200 px-3 py-0.5 rounded-full">
                                SOP Menunggu Eksekusi
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="notif in notifikasi_siklus" :key="notif.id" 
                                 class="p-4 rounded-2xl border flex flex-col justify-between gap-3 shadow-xs bg-white"
                                 :class="notif.level === 'danger' ? 'border-rose-300 ring-1 ring-rose-200' : 'border-amber-300 ring-1 ring-amber-200'">
                                <div>
                                    <div class="flex justify-between items-start gap-2">
                                        <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-md"
                                              :class="notif.level === 'danger' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'">
                                            {{ notif.stage }}
                                        </span>
                                        <span v-if="notif.ternak" class="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                            Hari ke-{{ notif.ternak.hari_di_kandang }}
                                        </span>
                                    </div>
                                    <h5 class="text-xs font-bold text-slate-800 mt-2 flex items-center gap-1.5">
                                        <svg class="w-4 h-4" :class="notif.level === 'danger' ? 'text-rose-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        {{ notif.judul }}
                                    </h5>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ notif.pesan }}</p>
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                                    <span class="text-[11px] text-slate-500">
                                        Saran Lokasi: <b class="text-emerald-700">{{ notif.rekomendasi_kandang }}</b>
                                    </span>
                                    <button v-if="notif.ternak" 
                                            @click="bukaModalMutasi(notif.ternak, notif.rekomendasi_kandang)" 
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs">
                                        Pindahkan Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50 to-white border border-emerald-200/80 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Siklus Seluruh Ternak Terpantau Sesuai SOP</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Tidak ada indukan jatuh tempo atau anomali lokasi penempatan kandang.</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700 bg-white border border-emerald-200 px-3 py-1 rounded-xl shadow-xs">
                            Sistem Siaga Real-Time
                        </span>
                    </div>
                </div>

                <!-- ROW 3: KONTROL FILTER & TOMBOL BUAT KANDANG BARU -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <select v-model="filterTipe" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-emerald-200 outline-none">
                            <option value="All">Semua Jenis Kandang</option>
                            <option value="Kawin">Kandang Kawin (A1)</option>
                            <option value="Bunting">Kandang Bunting (B1)</option>
                            <option value="Melahirkan">Kandang Melahirkan (C1)</option>
                            <option value="Menyusui">Kandang Menyusui (D1)</option>
                            <option value="Sapih">Kandang Sapih / Cempe (E1)</option>
                            <option value="Penggemukan">Kandang Penggemukan</option>
                            <option value="Karantina">Kandang Karantina Medis</option>
                            <option value="Umum">Kandang Umum</option>
                        </select>

                        <input v-model="searchQuery" type="text" placeholder="Cari kode / nama kandang..." 
                               class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none w-56">
                    </div>

                    <button @click="bukaModalTambahKandang" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition shadow-sm shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Buat Kandang Baru
                    </button>
                </div>

                <!-- ROW 4: PIPELINE KARTU KANDANG (KOTAK-KOTAK LENGKAP) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="k in filteredKandangs" :key="k.id" 
                         class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col justify-between transition-all hover:shadow-md hover:border-emerald-200">
                        
                        <div>
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="font-mono text-xs font-black px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
                                        {{ k.kode_kandang }}
                                    </span>
                                    <span class="ml-2 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md border"
                                          :class="{
                                              'bg-pink-50 border-pink-200 text-pink-700': k.tipe_kandang === 'Kawin',
                                              'bg-sky-50 border-sky-200 text-sky-700': k.tipe_kandang === 'Bunting',
                                              'bg-purple-50 border-purple-200 text-purple-700': k.tipe_kandang === 'Melahirkan',
                                              'bg-teal-50 border-teal-200 text-teal-700': k.tipe_kandang === 'Menyusui',
                                              'bg-amber-50 border-amber-200 text-amber-700': k.tipe_kandang === 'Sapih',
                                              'bg-indigo-50 border-indigo-200 text-indigo-700': k.tipe_kandang === 'Penggemukan',
                                              'bg-rose-50 border-rose-200 text-rose-700': k.tipe_kandang === 'Karantina',
                                              'bg-slate-50 border-slate-200 text-slate-700': k.tipe_kandang === 'Umum',
                                          }">
                                        {{ k.tipe_kandang }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button @click="bukaModalEditKandang(k)" class="p-1 text-slate-400 hover:text-emerald-600 transition" title="Edit Kandang">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                    <button @click="hapusKandang(k)" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Hapus Kandang">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>

                            <h3 class="font-bold text-slate-800 text-base mt-2">{{ k.nama_kandang }}</h3>
                            <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ k.keterangan || '-' }}</p>

                            <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-medium">Kapasitas Terisi:</span>
                                    <span class="font-black font-mono text-slate-800">{{ k.total_ekor }} / {{ k.kapasitas }} Ekor</span>
                                </div>
                                <div class="w-full bg-slate-200 h-2 rounded-full mt-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         :class="k.persen_kapasitas >= 90 ? 'bg-rose-500' : (k.persen_kapasitas >= 70 ? 'bg-amber-500' : 'bg-emerald-500')"
                                         :style="{ width: `${k.persen_kapasitas}%` }"></div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] text-slate-400 mt-1">
                                    <span>Kepadatan {{ k.persen_kapasitas }}%</span>
                                    <span v-if="k.persen_kapasitas >= 100" class="text-rose-600 font-bold">Kandang Penuh</span>
                                    <span v-else class="text-emerald-600 font-medium">Sisa {{ k.kapasitas - k.total_ekor }} slot</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                            <span class="text-xs font-semibold text-slate-600">
                                {{ k.total_ekor }} Ekor di dalam
                            </span>
                            <button @click="bukaModalPenghuni(k)" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                                Lihat Penghuni
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>

                    </div>

                    <div v-if="filteredKandangs.length === 0" class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <h4 class="font-bold text-slate-800 text-sm">Belum Ada Unit Kandang</h4>
                        <p class="text-xs text-slate-400 mt-1 mb-4">Tambahkan unit kandang baru untuk membagi penempatan siklus kambing.</p>
                        <button @click="bukaModalTambahKandang" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition">
                            + Buat Kandang Pertama
                        </button>
                    </div>
                </div>

                <!-- ROW 5: TIMELINE PELACAKAN SIKLUS HARI SELURUH TERNAK -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                        <div>
                            <h4 class="font-bold text-slate-800 text-base">Pelacakan Siklus Hari Seluruh Ternak</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Pemantauan durasi tinggal real-time menuju target evaluasi SOP berikutnya</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Kandang Saat Ini</th>
                                    <th class="px-6 py-4 font-semibold">Durasi Berjalan</th>
                                    <th class="px-6 py-4 font-semibold">Target Evaluasi SOP</th>
                                    <th class="px-6 py-4 font-semibold">Status Reproduksi & Medis</th>
                                    <th class="px-6 py-4 font-semibold text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="t in semua_ternak" :key="t.id" class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-bold font-mono text-slate-800">{{ t.rfid_tag }}</div>
                                        <div class="text-[11px] text-slate-400">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-700">
                                        {{ t.kandang }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-black font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                            Hari ke-{{ t.hari_di_kandang }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-700">
                                            {{ getTargetHariSop(t.kandang).label }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            Target: {{ getTargetHariSop(t.kandang).target }} Hari
                                            <span v-if="t.hari_di_kandang >= getTargetHariSop(t.kandang).target" class="text-rose-600 font-bold ml-1">
                                                (Jatuh Tempo!)
                                            </span>
                                            <span v-else class="text-slate-400 ml-1">
                                                (Sisa {{ getTargetHariSop(t.kandang).target - t.hari_di_kandang }} hari lagi)
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <!-- Status Reproduksi -->
                                        <span v-if="cekIsBunting(t.status_kehamilan)" class="bg-sky-50 text-sky-700 border border-sky-200 px-2 py-0.5 rounded text-[11px] font-bold mr-1.5">
                                            {{ t.status_kehamilan }}
                                        </span>
                                        <span v-else class="bg-slate-50 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[11px] font-medium mr-1.5">
                                            Non-Bunting
                                        </span>

                                        <!-- Status Medis (Sehat, Sakit, Karantina) -->
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border border-emerald-200': (t.kondisi_kesehatan === 'Sehat' || !t.kondisi_kesehatan),
                                                  'bg-rose-50 text-rose-700 border border-rose-200': t.kondisi_kesehatan === 'Sakit',
                                                  'bg-red-50 text-red-700 border border-red-200': t.kondisi_kesehatan === 'Karantina'
                                              }">
                                            {{ t.kondisi_kesehatan || 'Sehat' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="bukaModalMutasi(t)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-xl transition shadow-xs">
                                            Mutasi Kandang
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="semua_ternak.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">Belum ada ternak terdaftar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL 1: FORM TAMBAH / EDIT KANDANG -->
                <div v-if="isModalKandangOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">
                                    {{ isEditKandang ? 'Perbarui Data Kandang' : 'Tambah Unit Kandang Baru' }}
                                </h3>
                                <p class="text-xs text-slate-400">Kapasitas dan peruntukan fungsi siklus SOP</p>
                            </div>
                            <button @click="isModalKandangOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitKandang" class="p-6 space-y-4 text-xs">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Kode Kandang</label>
                                    <input v-model="formKandang.kode_kandang" type="text" required placeholder="Contoh: Blok A, KRT-01"
                                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 font-mono font-bold uppercase focus:ring-2 focus:ring-emerald-200 outline-none">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Kapasitas (Ekor)</label>
                                    <input v-model.number="formKandang.kapasitas" type="number" min="1" required placeholder="20"
                                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 font-mono font-bold focus:ring-2 focus:ring-emerald-200 outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Nama Kandang</label>
                                <input v-model="formKandang.nama_kandang" type="text" required placeholder="Contoh: Kandang Blok A (IoT Timbangan)"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 font-medium focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Peruntukan Siklus (Tipe)</label>
                                <select v-model="formKandang.tipe_kandang" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Penggemukan">Penggemukan (Fattening)</option>
                                    <option value="Bunting">Bunting (Indukan Hamil)</option>
                                    <option value="Karantina">Karantina Medis (Isolasi Sakit)</option>
                                    <option value="Kawin">Kawin (Siklus 1 - 1,2 Bulan)</option>
                                    <option value="Melahirkan">Melahirkan (Siklus 1 Bulan 1 Minggu)</option>
                                    <option value="Menyusui">Menyusui (Siklus 1 Bulan)</option>
                                    <option value="Sapih">Sapih / Cempe (Siklus 8 Bulan)</option>
                                    <option value="Umum">Kandang Umum / Transit</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Keterangan Tambahan</label>
                                <textarea v-model="formKandang.keterangan" rows="2" placeholder="Catatan lokasi kandang atau perlengkapan..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-emerald-200 outline-none"></textarea>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalKandangOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formKandang.processing" class="px-5 py-2 text-white bg-emerald-600 hover:bg-emerald-700 font-bold rounded-xl transition shadow-sm">
                                    {{ isEditKandang ? 'Simpan Perubahan' : 'Buat Kandang' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL 2: INSPEKSI DAFTAR PENGHUNI KANDANG -->
                <div v-if="isModalPenghuniOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden border border-slate-100 my-8">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-black bg-slate-200 text-slate-800 px-2 py-0.5 rounded">
                                        {{ selectedKandangPenghuni?.kode_kandang }}
                                    </span>
                                    <h3 class="font-bold text-slate-800 text-base">{{ selectedKandangPenghuni?.nama_kandang }}</h3>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">Daftar ternak aktif dan durasi tinggal di kandang ini</p>
                            </div>
                            <button @click="isModalPenghuniOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs whitespace-nowrap">
                                    <thead class="text-slate-400 uppercase bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="py-2.5 px-4 font-semibold">Tag RFID & Ras</th>
                                            <th class="py-2.5 px-4 font-semibold">Lama Tinggal</th>
                                            <th class="py-2.5 px-4 font-semibold">Bobot</th>
                                            <th class="py-2.5 px-4 font-semibold">Kondisi Medis</th>
                                            <th class="py-2.5 px-4 font-semibold text-center">Mutasikan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="t in selectedKandangPenghuni?.penghuni" :key="t.id" class="hover:bg-slate-50">
                                            <td class="py-3 px-4">
                                                <div class="font-mono font-bold text-slate-800">{{ t.rfid_tag }}</div>
                                                <div class="text-[11px] text-slate-400">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-black font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                                                    Hari ke-{{ t.hari_di_kandang }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 font-black font-mono text-slate-800">
                                                {{ t.berat_kg }} Kg
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                      :class="{
                                                          'bg-emerald-50 text-emerald-700 border border-emerald-200': (t.kondisi_kesehatan === 'Sehat' || !t.kondisi_kesehatan),
                                                          'bg-rose-50 text-rose-700 border border-rose-200': t.kondisi_kesehatan === 'Sakit',
                                                          'bg-red-50 text-red-700 border border-red-200': t.kondisi_kesehatan === 'Karantina'
                                                      }">
                                                    {{ t.kondisi_kesehatan || 'Sehat' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <button @click="bukaModalMutasi(t)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1 rounded-xl text-xs transition shadow-xs">
                                                    Pindah Kandang
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="!selectedKandangPenghuni?.penghuni || selectedKandangPenghuni?.penghuni.length === 0">
                                            <td colspan="5" class="py-8 text-center text-slate-400">Kandang ini sedang kosong.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
                                <button type="button" @click="isModalPenghuniOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL 3: MUTASI KANDANG LENGKAP -->
                <div v-if="isModalMutasiOpen" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Pindahkan Lokasi Kandang</h3>
                                <p class="text-xs text-slate-400">Atur kandang tujuan dan tanggal masuk kandang</p>
                            </div>
                            <button @click="isModalMutasiOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitMutasi" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihMutasi?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihMutasi?.ras }} (Kandang asal: {{ ternakTerpilihMutasi?.kandang }}, Durasi: Hari ke-{{ ternakTerpilihMutasi?.hari_di_kandang }})</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Pilih Kandang Tujuan</label>
                                <select v-model="formMutasi.kandang_tujuan" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="" disabled>-- Pilih Kandang Tujuan --</option>
                                    <option v-for="k in kandangs" :key="k.id" :value="k.kode_kandang">
                                        {{ k.kode_kandang }} - {{ k.nama_kandang }} (Sisa {{ k.kapasitas - k.total_ekor }} slot)
                                    </option>
                                </select>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-bold text-slate-600 uppercase">Tanggal Masuk Kandang Baru</label>
                                    <div class="flex gap-1">
                                        <button type="button" @click="setHariSimulasi(0)" class="text-[10px] text-emerald-700 font-bold hover:underline">Hari Ini</button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" @click="setHariSimulasi(36)" class="text-[10px] text-amber-700 font-bold hover:underline">Simulasi 36 Hari</button>
                                    </div>
                                </div>
                                <input v-model="formMutasi.tgl_masuk_kandang" type="date" required
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Sesuaikan Program Ternak</label>
                                <select v-model="formMutasi.program_tujuan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Produksi">Produksi</option>
                                    <option value="Pembibitan">Pembibitan</option>
                                    <option value="Penggemukan">Penggemukan</option>
                                    <option value="Siap Jual">Siap Jual</option>
                                    <option value="Karantina">Karantina</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Catatan Mutasi</label>
                                <input v-model="formMutasi.catatan" type="text" placeholder="Catatan alasan mutasi..."
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalMutasiOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formMutasi.processing" class="px-5 py-2 text-white bg-emerald-600 hover:bg-emerald-700 font-bold rounded-xl transition shadow-sm">
                                    Konfirmasi Pindah Kandang
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>