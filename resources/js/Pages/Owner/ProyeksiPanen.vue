<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    ternaks: { type: Array, default: () => [] },
    ternak_terjual: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_siap: 0,
            total_menuju_panen: 0,
            total_bobot_siap: 0,
            total_terjual: 0,
            total_omzet_terjual: 0,
            harga_acuan_default: 70000
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const activeTab = ref('kandang');

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// SIMULATOR HARGA PASAR DINAMIS (Rp 50.000 s/d Rp 300.000)
const simulasiHargaPerKg = ref(props.stats.harga_acuan_default || 70000);

const setHargaPreset = (nilai) => {
    simulasiHargaPerKg.value = nilai;
};

const omzetSimulasi = computed(() => {
    const harga = Number(simulasiHargaPerKg.value) || 0;
    return props.stats.total_bobot_siap * harga;
});

const searchQuery = ref('');
const filterStatus = ref('All');

const filteredTernaks = computed(() => {
    return props.ternaks.filter(t => {
        const matchSearch = t.rfid_tag.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.ras.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.kandang.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchStatus = filterStatus.value === 'All' ? true : t.status_panen === filterStatus.value;
        return matchSearch && matchStatus;
    });
});

const filteredTerjual = computed(() => {
    return props.ternak_terjual.filter(t => {
        return t.rfid_tag.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
               t.ras.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
               t.pembeli.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
});

// Modal Eksekusi Penjualan
const isModalJualOpen = ref(false);
const selectedTernakJual = ref(null);

const formJual = useForm({
    harga_jual: '',
    tgl_terjual: new Date().toISOString().split('T')[0],
    pembeli: ''
});

const bukaModalJual = (t) => {
    selectedTernakJual.value = t;
    formJual.harga_jual = Math.round(t.berat_kg * (Number(simulasiHargaPerKg.value) || 70000));
    formJual.pembeli = '';
    formJual.tgl_terjual = new Date().toISOString().split('T')[0];
    isModalJualOpen.value = true;
};

const submitJual = () => {
    if (!selectedTernakJual.value) return;
    formJual.post(route('owner.panen.jual', selectedTernakJual.value.id), {
        onSuccess: () => {
            isModalJualOpen.value = false;
            activeTab.value = 'terjual';
        }
    });
};

const batalkanPenjualan = (id) => {
    if (confirm('Kembalikan kambing ini ke status siap panen di kandang?')) {
        router.post(route('owner.panen.batal', id), {}, { preserveScroll: true });
    }
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['ternaks', 'ternak_terjual', 'stats']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Proyeksi Panen & Penjualan - Owner" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR OWNER (DENGAN INDIKATOR MENU HIJAU OTOMATIS BERDASARKAN URL) -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-emerald-200">
                    O
                </div>
                <span class="text-xl font-bold text-slate-800 tracking-tight">Kandang Pintar</span>
            </div>

            <div class="flex-1 overflow-y-auto py-5 px-3 space-y-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3">Ringkasan Eksekutif</div>
                
                <!-- 1. Kinerja Finansial -->
                <Link :href="route('dashboard')" 
                      :class="$page.url === '/dashboard' ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url === '/dashboard' ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Kinerja Finansial
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Aset & Pertumbuhan</div>
                
                <!-- 2. Monitoring Bobot -->
                <Link :href="route('owner.monitoring')" 
                      :class="$page.url.startsWith('/owner/monitoring-bobot') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/monitoring-bobot') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    Monitoring Bobot
                </Link>
                
                <!-- 3. Silsilah & Breeding -->
                <Link :href="route('owner.breeding')" 
                      :class="$page.url.startsWith('/owner/silsilah-breeding') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/silsilah-breeding') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Silsilah & Breeding
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Kas</div>
                
                <!-- 4. Buku Kas Kandang -->
                <Link :href="route('owner.keuangan')" 
                      :class="$page.url.startsWith('/owner/buku-kas') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/buku-kas') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Buku Kas Kandang
                </Link>
                
                <!-- 5. Proyeksi Panen (Otomatis Hijau di Halaman Ini) -->
                <Link :href="route('owner.panen')" 
                      :class="$page.url.startsWith('/owner/proyeksi-panen') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/proyeksi-panen') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Proyeksi Panen
                </Link>
                
                <!-- 6. Kambing Terjual -->
                <Link :href="route('owner.terjual')" 
                      :class="$page.url.startsWith('/owner/kambing-terjual') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/kambing-terjual') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Kambing Terjual
                </Link>
            </div>

            <div class="p-4 border-t border-slate-100 text-center mt-auto">
                <p class="text-xs font-semibold text-slate-400">&copy; 2026 JJFarm.</p>
                <p class="text-[10px] text-slate-400">Executive Portal</p>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.06)] mx-4 lg:mx-6 mt-4 px-5 py-3.5 flex justify-between items-center z-40">
                <div class="flex items-center gap-3">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Proyeksi Panen & Perencanaan Penjualan</h2>
                        <p class="text-xs text-slate-400">Estimasi pendapatan kotor, bobot komersial siap potong, dan simulasi harga pasar</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 relative">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-700">{{ user?.name || 'Owner Utama' }}</div>
                        <div class="text-[11px] text-emerald-600 font-semibold uppercase tracking-wider">Pemilik Usaha</div>
                    </div>
                    <button @click="isDropdownOpen = !isDropdownOpen" @blur="setTimeout(() => isDropdownOpen = false, 200)" class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold uppercase transition hover:ring-2 hover:ring-emerald-300 border-2 border-white shadow-sm flex items-center justify-center">
                        {{ user?.name?.charAt(0) || 'O' }}
                    </button>
                    <div v-show="isDropdownOpen" class="absolute right-0 top-12 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-50">
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition font-medium">Logout Keluar</Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- ROW 1: KARTU METRIK UTAMA PROYEKSI PANEN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- 1. Siap Panen di Kandang -->
                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Siap Panen (Kandang)</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_siap }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">{{ stats.total_bobot_siap }} Kg Bobot Hidup</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                    </div>

                    <!-- 2. Potensi Omzet Kandang -->
                    <div class="bg-gradient-to-br from-teal-50/80 via-white to-white rounded-2xl p-5 border border-teal-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Potensi Omzet Kandang</span>
                            <h3 class="text-2xl font-black text-teal-700 mt-1">{{ formatRupiah(omzetSimulasi) }}</h3>
                            <p class="text-[11px] text-teal-600 font-semibold mt-0.5">Sesuai Simulasi Harga</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <!-- 3. Kambing Terjual -->
                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-sky-700 uppercase tracking-wider">Kambing Terjual</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.total_terjual }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-sky-600 font-semibold mt-0.5">Realisasi Penjualan Riil</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <!-- 4. Total Kas Penjualan -->
                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Total Kas Penjualan</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ formatRupiah(stats.total_omzet_terjual) }}</h3>
                            <p class="text-[11px] text-indigo-500 font-semibold mt-0.5">Tercatat di Buku Kas</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: KALKULATOR HARGA (SLIDER 50K - 300K & INPUT MANUAL) -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-slate-100 gap-4">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Simulator Penjualan Komersial
                            </span>
                            <h3 class="text-lg font-bold text-slate-800 mt-1">Kalkulator Proyeksi Omzet Timbang Hidup</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Ketik harga per kg manual atau gunakan slider (Rp 50.000 s/d Rp 300.000)</p>
                        </div>
                        
                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 px-5 py-3 rounded-xl text-right">
                            <span class="text-[10px] uppercase font-bold text-emerald-700 tracking-wider block">Estimasi Omzet Panen Terkalkulasi</span>
                            <span class="text-2xl font-black text-emerald-700 font-mono">{{ formatRupiah(omzetSimulasi) }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                        <div class="md:col-span-2 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">
                                    Harga Pasar Daging Hidup (Per Kg)
                                </label>
                                
                                <div class="relative w-48">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-bold text-slate-400">Rp</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        v-model.number="simulasiHargaPerKg" 
                                        min="50000" 
                                        max="300000" 
                                        step="1000"
                                        placeholder="70000"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-12 py-1.5 text-xs font-bold font-mono text-emerald-700 focus:ring-2 focus:ring-emerald-200 focus:bg-white outline-none transition"
                                    />
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-[11px] font-semibold text-slate-400">/ Kg</span>
                                    </div>
                                </div>
                            </div>

                            <input 
                                type="range" 
                                min="50000" 
                                max="300000" 
                                step="1000" 
                                v-model.number="simulasiHargaPerKg" 
                                class="w-full h-2.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-emerald-600 border border-slate-200">
                            
                            <div class="flex items-center gap-1.5 flex-wrap pt-1">
                                <span class="text-[11px] text-slate-400 mr-1 font-medium">Preset Cepat:</span>
                                <button type="button" @click="setHargaPreset(65000)" 
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg border transition"
                                        :class="simulasiHargaPerKg === 65000 ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                                    Pasar (Rp 65.000)
                                </button>
                                <button type="button" @click="setHargaPreset(85000)" 
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg border transition"
                                        :class="simulasiHargaPerKg === 85000 ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                                    Idul Adha (Rp 85.000)
                                </button>
                                <button type="button" @click="setHargaPreset(150000)" 
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg border transition"
                                        :class="simulasiHargaPerKg === 150000 ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                                    Bibit Unggul (Rp 150.000)
                                </button>
                                <button type="button" @click="setHargaPreset(250000)" 
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg border transition"
                                        :class="simulasiHargaPerKg === 250000 ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'">
                                    Super / Kontes (Rp 250.000)
                                </button>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-500">
                                <span>Volume Daging Siap:</span>
                                <span class="font-bold text-slate-700 font-mono">{{ stats.total_bobot_siap }} Kg</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Populasi Siap Panen:</span>
                                <span class="font-bold text-slate-700 font-mono">{{ stats.total_siap }} Ekor</span>
                            </div>
                            <div class="flex justify-between text-slate-500 border-t border-slate-200 pt-2">
                                <span>Status Simulasi:</span>
                                <span class="font-bold text-emerald-600">Sinkronisasi Aktif</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: TABEL DENGAN SWITCHER (SIAP PANEN VS RIWAYAT TERJUAL) -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-xl">
                            <button @click="activeTab = 'kandang'"
                                    class="px-4 py-2 text-xs font-bold rounded-lg transition"
                                    :class="activeTab === 'kandang' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                Siap Jual di Kandang ({{ ternaks.length }})
                            </button>
                            <button @click="activeTab = 'terjual'"
                                    class="px-4 py-2 text-xs font-bold rounded-lg transition"
                                    :class="activeTab === 'terjual' ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                Riwayat Kambing Terjual ({{ ternak_terjual.length }})
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras..." 
                                   class="px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none w-full sm:w-52">
                            
                            <select v-if="activeTab === 'kandang'" v-model="filterStatus" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none">
                                <option value="All">Semua Kesiapan</option>
                                <option value="Siap Panen">Siap Panen Saja</option>
                                <option value="Fase Akhir (30 Hari)">Fase Akhir</option>
                            </select>
                        </div>
                    </div>

                    <!-- TABEL 1: SIAP PANEN (DI KANDANG) -->
                    <div v-if="activeTab === 'kandang'" class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Penempatan</th>
                                    <th class="px-6 py-4 font-semibold">Bobot Terkini</th>
                                    <th class="px-6 py-4 font-semibold">Status Kesiapan</th>
                                    <th class="px-6 py-4 font-semibold text-right">Nilai Estimasi</th>
                                    <th class="px-6 py-4 font-semibold text-center">Aksi Jual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredTernaks" :key="t.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 font-mono">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-500 text-xs mt-0.5 font-medium">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-700 text-xs">{{ t.kandang }}</div>
                                        <div class="text-slate-400 text-[11px] mt-0.5">{{ t.program }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-black text-slate-800 text-sm font-mono">{{ t.berat_kg }} Kg</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border border-emerald-200': t.status_panen === 'Siap Panen',
                                                  'bg-amber-50 text-amber-700 border border-amber-200': t.status_panen === 'Fase Akhir (30 Hari)',
                                                  'bg-slate-100 text-slate-600 border border-slate-200': t.status_panen === 'Tahap Pembesaran'
                                              }">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                  :class="{
                                                      'bg-emerald-500': t.status_panen === 'Siap Panen',
                                                      'bg-amber-500': t.status_panen === 'Fase Akhir (30 Hari)',
                                                      'bg-slate-400': t.status_panen === 'Tahap Pembesaran'
                                                  }"></span>
                                            {{ t.status_panen }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-slate-800 font-mono text-sm">
                                        {{ formatRupiah(t.berat_kg * (Number(simulasiHargaPerKg) || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="bukaModalJual(t)" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-xs">
                                            Jual Ekor Ini
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredTernaks.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">Belum ada kambing di daftar siap panen ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TABEL 2: RIWAYAT KAMBING TERJUAL -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tanggal Terjual</th>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Bobot Terjual</th>
                                    <th class="px-6 py-4 font-semibold">Nama Pembeli / Keterangan</th>
                                    <th class="px-6 py-4 font-semibold text-right">Harga Terjual</th>
                                    <th class="px-6 py-4 font-semibold text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredTerjual" :key="t.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-500">{{ t.tgl_terjual }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 font-mono">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-500 text-xs mt-0.5 font-medium">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-black font-mono text-slate-700">{{ t.berat_kg }} Kg</td>
                                    <td class="px-6 py-4">
                                        <span class="bg-sky-50 text-sky-800 border border-sky-200 px-2.5 py-1 rounded-lg text-xs font-semibold">{{ t.pembeli }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-emerald-700 font-mono text-sm">+{{ formatRupiah(t.harga_jual) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="batalkanPenjualan(t.id)" class="text-xs text-slate-400 hover:text-rose-600 transition" title="Batalkan Penjualan">Batalkan</button>
                                    </td>
                                </tr>
                                <tr v-if="filteredTerjual.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">Belum ada riwayat kambing yang terjual.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- MODAL JUAL -->
                <div v-if="isModalJualOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Konfirmasi Penjualan Ternak</h3>
                                <p class="text-xs text-slate-400">Otomatis mencatat pendapatan ke Buku Kas</p>
                            </div>
                            <button @click="isModalJualOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitJual" class="p-6 space-y-4">
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex justify-between items-center text-xs">
                                <div>
                                    <div class="font-mono font-bold text-slate-800">{{ selectedTernakJual?.rfid_tag }}</div>
                                    <div class="text-slate-500">{{ selectedTernakJual?.ras }} ({{ selectedTernakJual?.berat_kg }} Kg)</div>
                                </div>
                                <span class="bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">Siap Lepas</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Harga Kesepakatan Jual (Rp)</label>
                                <input v-model="formJual.harga_jual" type="number" min="1000" step="1000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Tanggal Transaksi Jual</label>
                                <input v-model="formJual.tgl_terjual" type="date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nama Pembeli / Keterangan</label>
                                <input v-model="formJual.pembeli" type="text" placeholder="Contoh: Pak Haji Samsul (Untuk Aqiqah)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalJualOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-xl transition">Batal</button>
                                <button type="submit" :disabled="formJual.processing" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm disabled:opacity-50">Simpan & Masukkan ke Kas</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>