<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    arsip: { type: Array, default: () => [] },
    current_filter: { type: String, default: 'All' },
    stats: {
        type: Object,
        default: () => ({
            total_arsip: 0,
            total_aktif: 0,
            total_terjual: 0,
            total_mati: 0,
            omzet_penjualan: 0,
            mortalitas_persen: 0
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// Helper Deteksi Kebuntingan
const cekIsBunting = (status) => {
    if (!status) return false;
    const s = String(status).toUpperCase();
    return s.includes('HAMIL') || s.includes('BUNTING');
};

// Helper Deteksi Status Hidup Aman (String/Boolean Compatible)
const getStatusLabel = (t) => {
    if (t.status_hidup === 'Terjual' || t.program === 'Terjual' || t.tgl_terjual !== '-' || (t.harga_jual && t.harga_jual > 0)) {
        return 'Terjual';
    }
    if (t.status_hidup === 'Mati' || t.program === 'Arsip Kematian' || t.tgl_kematian !== '-' || t.penyebab_kematian !== '-') {
        return 'Mati';
    }
    return 'Aktif';
};

// Filter Tab Navigasi
const activeTab = ref(props.current_filter || 'All');
const searchQuery = ref('');

const gantiTab = (status) => {
    activeTab.value = status;
    router.get(route('operator.arsip'), { status }, { preserveState: true, preserveScroll: true });
};

// Filter Pencarian Lokal
const filteredArsip = computed(() => {
    return props.arsip.filter(t => {
        const q = (searchQuery.value || '').toLowerCase();
        const rfid = (t.rfid_tag || '').toLowerCase();
        const ras = (t.ras || '').toLowerCase();
        const kandang = (t.kandang || '').toLowerCase();
        const pembeli = (t.pembeli || '').toLowerCase();
        const penyebab = (t.penyebab_kematian || '').toLowerCase();
        return rfid.includes(q) || ras.includes(q) || kandang.includes(q) || pembeli.includes(q) || penyebab.includes(q);
    });
});

// Download File CSV / Excel
const exportExcel = () => {
    window.location.href = route('operator.arsip.export', { status: activeTab.value });
};

// Modal Paspor & Detail Rekam Jejak
const isModalDetailOpen = ref(false);
const ternakDetail = ref(null);

const bukaDetail = (t) => {
    ternakDetail.value = t;
    isModalDetailOpen.value = true;
};

// Auto Polling Real-Time
let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['arsip', 'stats']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Gudang Data Arsip - Anak Kandang" />

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
                <Link :href="route('operator.kandang')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Pipeline Siklus Kandang
                </Link>
                <Link :href="route('operator.karantina')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Karantina & Medis
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Kas</div>
                <Link :href="route('operator.kasir')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Kasir Penjualan Lapangan
                </Link>

                <!-- MENU GUDANG ARSIP AKTIF HIJAU -->
                <Link :href="route('operator.arsip')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Gudang Data Arsip Ternak</h2>
                        <p class="text-xs text-slate-400">Pusat bank data permanen seluruh ternak (Aktif, Terjual, dan Kematian) serta export Excel</p>
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
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Profil Akun</Link>
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Sesuaikan Password</Link>
                        </div>
                        <div class="border-t border-slate-100 pt-1">
                            <Link :href="route('logout')" method="post" as="button" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Logout Keluar</Link>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- ROW 1: KARTU METRIK ARSIP LENGKAP -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-slate-50 via-white to-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                        <span class="text-xs font-bold text-slate-600 uppercase">Total Riwayat Masuk</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_arsip }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Semua Record Tercatat</p>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm">
                        <span class="text-xs font-bold text-emerald-700 uppercase">Populasi Masih Aktif</span>
                        <h3 class="text-2xl font-black text-emerald-700 mt-1">{{ stats.total_aktif }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Ada di Kandang</p>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm">
                        <span class="text-xs font-bold text-sky-700 uppercase">Arsip Terjual</span>
                        <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.total_terjual }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-sky-600 font-medium mt-0.5">{{ formatRupiah(stats.omzet_penjualan) }}</p>
                    </div>

                    <div class="bg-gradient-to-br from-rose-50/80 via-white to-white rounded-2xl p-5 border border-rose-200/60 shadow-sm">
                        <span class="text-xs font-bold text-rose-700 uppercase">Arsip Kematian</span>
                        <h3 class="text-2xl font-black text-rose-700 mt-1">{{ stats.total_mati }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-rose-600 font-medium mt-0.5">Mortalitas: {{ stats.mortalitas_persen }}%</p>
                    </div>
                </div>

                <!-- ROW 2: KONTROL FILTER, PENCARIAN & TOMBOL EXPORT EXCEL -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl flex-wrap">
                        <button @click="gantiTab('All')" 
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition"
                                :class="activeTab === 'All' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Semua ({{ stats.total_arsip }})
                        </button>
                        <button @click="gantiTab('Aktif')" 
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition"
                                :class="activeTab === 'Aktif' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Aktif ({{ stats.total_aktif }})
                        </button>
                        <button @click="gantiTab('Terjual')" 
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition"
                                :class="activeTab === 'Terjual' ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Terjual ({{ stats.total_terjual }})
                        </button>
                        <button @click="gantiTab('Mati')" 
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition"
                                :class="activeTab === 'Mati' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Kematian ({{ stats.total_mati }})
                        </button>
                    </div>

                    <div class="flex items-center gap-2.5 w-full md:w-auto">
                        <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras / Pembeli..." 
                               class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs outline-none focus:ring-2 focus:ring-emerald-200 w-full sm:w-56">

                        <!-- TOMBOL EXPORT EXCEL RESMI -->
                        <button @click="exportExcel" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition shadow-sm shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export Excel (.csv)
                        </button>
                    </div>
                </div>

                <!-- ROW 3: TABEL GUDANG DATA ARSIP -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="text-slate-400 uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="py-3 px-5 font-semibold">Tag RFID & Ras</th>
                                    <th class="py-3 px-5 font-semibold">Status Hidup</th>
                                    <th class="py-3 px-5 font-semibold">Kandang Terakhir</th>
                                    <th class="py-3 px-5 font-semibold">Bobot Akhir</th>
                                    <th class="py-3 px-5 font-semibold">Keterangan Khusus</th>
                                    <th class="py-3 px-5 font-semibold text-center">Buka Paspor / Arsip</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredArsip" :key="t.id" class="hover:bg-slate-50/60">
                                    <td class="py-3.5 px-5">
                                        <div class="font-mono font-bold text-slate-800 text-sm">{{ t.rfid_tag }}</div>
                                        <div class="text-[11px] text-slate-400">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span v-if="getStatusLabel(t) === 'Terjual'" class="bg-sky-50 text-sky-700 border border-sky-200 px-2.5 py-1 rounded-lg font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            Terjual
                                        </span>
                                        <span v-else-if="getStatusLabel(t) === 'Mati'" class="bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 rounded-lg font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Mati
                                        </span>
                                        <span v-else class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg font-bold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 font-bold text-slate-700">
                                        {{ t.kandang }}
                                    </td>
                                    <td class="py-3.5 px-5 font-black font-mono text-slate-800">
                                        {{ t.berat_kg }} Kg
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <div v-if="getStatusLabel(t) === 'Terjual'" class="text-sky-800">
                                            <span class="font-bold">{{ formatRupiah(t.harga_jual) }}</span>
                                            <span class="text-slate-400 ml-1 font-medium">({{ t.pembeli }})</span>
                                        </div>
                                        <div v-else-if="getStatusLabel(t) === 'Mati'" class="text-rose-700 font-medium">
                                            <span class="font-bold">Penyebab:</span> {{ t.penyebab_kematian }}
                                        </div>
                                        <div v-else class="text-slate-500 font-medium">
                                            Program: <b class="text-slate-700">{{ t.program }}</b> • Medis: <b class="text-emerald-700">{{ t.kondisi_kesehatan }}</b>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-5 text-center">
                                        <button @click="bukaDetail(t)" class="text-emerald-700 hover:text-emerald-800 font-bold px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition border border-emerald-200 flex items-center gap-1 mx-auto shadow-xs">
                                            <span>Lihat Arsip Lengkap</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredArsip.length === 0">
                                    <td colspan="6" class="py-10 text-center text-slate-400">
                                        Tidak ada data ternak dalam filter ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL DETAIL PASPOR / ARSIP LENGKAP SEMASA HIDUP -->
                <div v-if="isModalDetailOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-100 my-8">
                        
                        <!-- HEADER PASPOR -->
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">
                                    Paspor & Rekam Jejak Ternak
                                </span>
                                <h3 class="font-bold text-slate-800 text-base mt-1">Riwayat Lengkap Semasa di JJFarm</h3>
                            </div>
                            <button @click="isModalDetailOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4 text-xs max-h-[80vh] overflow-y-auto">
                            
                            <!-- KARTU IDENTITAS UTAMA -->
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                <div>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Identitas Tag RFID</span>
                                    <div class="font-mono font-black text-slate-800 text-lg">{{ ternakDetail?.rfid_tag }}</div>
                                    <div class="text-xs text-slate-600 mt-0.5 font-medium">
                                        {{ ternakDetail?.ras }} • {{ ternakDetail?.jenis_kelamin }} (Umur: {{ ternakDetail?.umur }})
                                    </div>
                                </div>
                                <div class="text-left sm:text-right">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Status Akhir</span>
                                    <div>
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold inline-block mt-0.5"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800 border border-emerald-200': getStatusLabel(ternakDetail) === 'Aktif',
                                                  'bg-sky-100 text-sky-800 border border-sky-200': getStatusLabel(ternakDetail) === 'Terjual',
                                                  'bg-rose-100 text-rose-800 border border-rose-200': getStatusLabel(ternakDetail) === 'Mati'
                                              }">
                                            {{ getStatusLabel(ternakDetail) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- BLOK HISTORI TERJUAL -->
                            <div v-if="getStatusLabel(ternakDetail) === 'Terjual'" class="p-4 rounded-2xl bg-sky-50 border border-sky-200 space-y-1.5">
                                <div class="font-extrabold text-sky-900 text-xs flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    Histori Pelepasan & Penjualan (Lunas)
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-slate-700">
                                    <div>Nilai Jual: <b class="text-emerald-700 font-mono text-sm">{{ formatRupiah(ternakDetail?.harga_jual) }}</b></div>
                                    <div>Tanggal Terjual: <b class="font-mono">{{ ternakDetail?.tgl_terjual }}</b></div>
                                    <div class="sm:col-span-2">Pembeli / Keterangan: <b>{{ ternakDetail?.pembeli }}</b></div>
                                </div>
                            </div>

                            <!-- BLOK HISTORI KEMATIAN -->
                            <div v-if="getStatusLabel(ternakDetail) === 'Mati'" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 space-y-1.5">
                                <div class="font-extrabold text-rose-900 text-xs flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    Histori Kematian / Afkir Ternak
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-slate-700">
                                    <div>Tanggal Kematian: <b class="font-mono text-rose-700">{{ ternakDetail?.tgl_kematian }}</b></div>
                                    <div class="sm:col-span-2">Penyebab Kematian: <b class="text-rose-800">{{ ternakDetail?.penyebab_kematian }}</b></div>
                                </div>
                            </div>

                            <!-- 1. DATA BIOLOGIS & SILSILAH SEMASA HIDUP -->
                            <div class="border border-slate-200/80 rounded-2xl p-4 bg-white space-y-2">
                                <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider text-emerald-800">1. Silsilah & Data Fisik</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-1">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Pejantan (Bapak / Sire):</span>
                                        <span class="font-mono font-bold text-slate-700">{{ ternakDetail?.bapak }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Indukan (Mamak / Dam):</span>
                                        <span class="font-mono font-bold text-slate-700">{{ ternakDetail?.mamak }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Jumlah Keturunan:</span>
                                        <span class="font-bold text-slate-700">{{ ternakDetail?.jumlah_anak }} Ekor</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Tanggal Lahir:</span>
                                        <span class="font-mono text-slate-700">{{ ternakDetail?.tgl_lahir }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Tanggal Registrasi:</span>
                                        <span class="font-mono text-slate-700">{{ ternakDetail?.tgl_pendataan }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Kandang Terakhir:</span>
                                        <span class="font-bold text-slate-700">{{ ternakDetail?.kandang }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. REKAM MEDIS & EVALUASI KLINIS SEMASA HIDUP -->
                            <div class="border border-slate-200/80 rounded-2xl p-4 bg-white space-y-2">
                                <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider text-rose-800">2. Rekam Medis & Kesehatan Semasa Hidup</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Kondisi Kesehatan Terakhir:</span>
                                        <span class="font-bold" :class="ternakDetail?.kondisi_kesehatan === 'Sehat' ? 'text-emerald-700' : 'text-rose-700'">
                                            {{ ternakDetail?.kondisi_kesehatan }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Status Kebuntingan:</span>
                                        <span class="font-bold" :class="cekIsBunting(ternakDetail?.status_kehamilan) ? 'text-sky-700' : 'text-slate-600'">
                                            {{ ternakDetail?.status_kehamilan }}
                                        </span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-slate-400 block text-[11px]">Gejala Sakit yang Pernah Dialami:</span>
                                        <span class="text-slate-700 font-medium">{{ ternakDetail?.gejala_sakit }}</span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-slate-400 block text-[11px]">Tindakan Medis / Injeksi Obat:</span>
                                        <span class="text-slate-700 font-medium">{{ ternakDetail?.penanganan_medis }}</span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-slate-400 block text-[11px]">Keputusan & Catatan Pakar:</span>
                                        <span class="text-slate-700 italic">[{{ ternakDetail?.keputusan_pakar }}] {{ ternakDetail?.catatan_pakar }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. HISTORI PENIMBANGAN (LOG PERTUMBUHAN BOBOT SEMASA HIDUP) -->
                            <div class="border border-slate-200/80 rounded-2xl p-4 bg-white space-y-2">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider text-indigo-800">
                                        3. Riwayat Timbangan Semasa Hidup (Log Abadi)
                                    </h4>
                                    <span class="text-[11px] font-black text-slate-800 font-mono">
                                        Bobot Akhir: {{ ternakDetail?.berat_kg }} Kg
                                    </span>
                                </div>

                                <div class="overflow-x-auto pt-1">
                                    <table class="w-full text-left text-[11px] whitespace-nowrap">
                                        <thead class="text-slate-400 uppercase bg-slate-50 border-b border-slate-100">
                                            <tr>
                                                <th class="py-2 px-3 font-semibold">Waktu Timbang</th>
                                                <th class="py-2 px-3 font-semibold">Bobot (Kg)</th>
                                                <th class="py-2 px-3 font-semibold">Kenaikan (Gain)</th>
                                                <th class="py-2 px-3 font-semibold">Sumber Input</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="r in ternakDetail?.riwayat_timbang" :key="r.id" class="hover:bg-slate-50">
                                                <td class="py-2 px-3 text-slate-500 font-mono">{{ r.waktu }}</td>
                                                <td class="py-2 px-3 font-black font-mono text-slate-800">{{ r.berat_kg }} Kg</td>
                                                <td class="py-2 px-3 font-bold" :class="r.selisih_kg >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                                    {{ r.selisih_kg > 0 ? '+' : '' }}{{ r.selisih_kg }} Kg
                                                </td>
                                                <td class="py-2 px-3 text-slate-500">{{ r.sumber_alat }}</td>
                                            </tr>
                                            <tr v-if="!ternakDetail?.riwayat_timbang || ternakDetail?.riwayat_timbang.length === 0">
                                                <td colspan="4" class="py-4 text-center text-slate-400">Belum ada riwayat timbangan terekam.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="button" @click="isModalDetailOpen = false" class="px-5 py-2 text-xs font-bold text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                                    Tutup Paspor
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>
</template>