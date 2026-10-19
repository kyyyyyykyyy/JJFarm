<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    ternaks: { type: Array, default: () => [] },
    keuangan: {
        type: Object,
        default: () => ({
            uang_masuk: 0,
            uang_keluar: 0,
            pendapatan_kotor: 0,
            pendapatan_bersih: 0,
            estimasi_valuasi: 0
        })
    },
    transaksi_terbaru: { type: Array, default: () => [] }
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

// Hitung hanya populasi yang masih aktif di kandang (belum terjual)
const ternakAktif = computed(() => props.ternaks.filter(t => t.program !== 'Terjual'));
const totalPopulasi = computed(() => ternakAktif.value.length);

// Deteksi status bunting fleksibel
const totalHamil = computed(() => {
    return ternakAktif.value.filter(t => t.status_kehamilan && t.status_kehamilan.toUpperCase().includes('HAMIL')).length;
});

const totalBobotAktif = computed(() => {
    return ternakAktif.value.reduce((sum, t) => sum + (parseFloat(t.berat_kg) || 0), 0).toFixed(1);
});

// Hitung valuasi pasar otomatis dari bobot aktif x Rp 65.000/Kg jika prop bernilai 0
const valuasiPasar = computed(() => {
    if (props.keuangan?.estimasi_valuasi && props.keuangan.estimasi_valuasi > 0) {
        return props.keuangan.estimasi_valuasi;
    }
    return parseFloat(totalBobotAktif.value) * 65000;
});

const rataRataGain = computed(() => {
    if (ternakAktif.value.length === 0) return '0.00';
    const total = ternakAktif.value.reduce((sum, t) => sum + (parseFloat(t.selisih_kg) || 0), 0);
    return (total / ternakAktif.value.length).toFixed(2);
});

const siapPanen = computed(() => {
    return ternakAktif.value.filter(t => 
        t.program === 'Siap Jual' || 
        t.program === 'Penggemukan' || 
        (parseFloat(t.berat_kg) >= 30)
    ).length;
});

// Auto-Polling: Refresh background setiap 5 detik agar selalu sinkron dengan input lapangan
let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Executive Financial & Asset - Owner" />

    <div class="flex h-screen bg-[#f5f5f9] font-sans text-slate-600 overflow-hidden">
        
        <!-- SIDEBAR OWNER -->
        <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-none lg:border-r border-slate-100 lg:static lg:translate-x-0 transition-transform duration-300 flex flex-col">
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-md shadow-emerald-200">
                    O
                </div>
                <span class="text-xl font-bold text-slate-800 tracking-tight">Kandang Pintar</span>
            </div>

            <div class="flex-1 overflow-y-auto py-5 px-3 space-y-1">
                <!-- GROUP 1: RINGKASAN EKSEKUTIF -->
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3">Ringkasan Eksekutif</div>
                <Link :href="route('dashboard')" 
                      :class="$page.url === '/dashboard' ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url === '/dashboard' ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Kinerja Finansial
                </Link>

                <!-- GROUP 2: MANAJEMEN ASET -->
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Aset & Pertumbuhan</div>
                <Link :href="route('owner.monitoring')" 
                      :class="$page.url.startsWith('/owner/monitoring-bobot') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/monitoring-bobot') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    Monitoring Bobot
                </Link>
                <Link :href="route('owner.breeding')" 
                      :class="$page.url.startsWith('/owner/silsilah-breeding') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/silsilah-breeding') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Silsilah & Breeding
                </Link>

                <!-- GROUP 3: KOMERSIAL & KAS -->
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Kas</div>
                <Link :href="route('owner.keuangan')" 
                      :class="$page.url.startsWith('/owner/buku-kas') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/buku-kas') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Buku Kas Kandang
                </Link>
                <Link :href="route('owner.panen')" 
                      :class="$page.url.startsWith('/owner/proyeksi-panen') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/owner/proyeksi-panen') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Proyeksi Panen
                </Link>
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
            <!-- NAVBAR -->
            <nav class="bg-white rounded-xl shadow-[0_2px_6px_0_rgba(67,89,113,0.06)] mx-4 lg:mx-6 mt-4 px-5 py-3.5 flex justify-between items-center z-40">
                <div class="flex items-center gap-3">
                    <button @click="isSidebarOpen = true" class="lg:hidden text-slate-500 mr-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 hidden sm:block leading-tight">Ikhtisar Finansial & Aset Farm</h2>
                        <p class="text-xs text-slate-400 hidden sm:block">Laporan real-time pendapatan, pengeluaran kandang, dan proyeksi panen</p>
                    </div>
                </div>

                <!-- USER PROFILE AREA -->
                <div class="flex items-center gap-4 relative">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-700">{{ user?.name || 'Owner Utama' }}</div>
                        <div class="text-[11px] text-emerald-600 font-semibold uppercase tracking-wider">Pemilik Usaha</div>
                    </div>
                    
                    <button @click="isDropdownOpen = !isDropdownOpen" 
                            @blur="setTimeout(() => isDropdownOpen = false, 250)" 
                            class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold uppercase transition hover:ring-2 hover:ring-emerald-300 border-2 border-white shadow-sm flex items-center justify-center cursor-pointer">
                        {{ user?.name?.charAt(0) || 'O' }}
                    </button>

                    <div v-show="isDropdownOpen" class="absolute right-0 top-12 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 transition-all duration-150">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800 leading-tight">{{ user?.name || 'Owner Utama' }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ user?.email || 'owner@jjfarm.com' }}</p>
                            <span class="inline-block mt-1.5 px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-700 rounded-md border border-emerald-100">
                                Pemilik Usaha (Owner)
                            </span>
                        </div>

                        <div class="py-1">
                            <Link :href="route('profile.edit')" class="flex items-center px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-emerald-700 transition">
                                <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profil & Ubah Akun
                            </Link>

                            <Link :href="route('profile.edit')" class="flex items-center px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-emerald-700 transition">
                                <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Sesuaikan Password
                            </Link>
                        </div>

                        <div class="border-t border-slate-100 pt-1">
                            <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                                <svg class="w-4 h-4 mr-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout Keluar
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- BANNER VALUASI ASET -->
                <div class="rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg shadow-emerald-600/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="inline-block bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-2">Estimasi Nilai Pasar</span>
                        <h3 class="text-3xl font-black tracking-tight">{{ formatRupiah(valuasiPasar) }}</h3>
                        <p class="text-xs text-emerald-100 mt-1">Dihitung otomatis dari akumulasi {{ totalBobotAktif }} Kg bobot hidup ternak aktif di kandang.</p>
                    </div>
                    <div class="flex gap-2">
                        <div class="bg-white/10 backdrop-blur-sm px-4 py-2 rounded-xl text-center">
                            <div class="text-[10px] uppercase text-emerald-200 font-bold">Populasi Aktif</div>
                            <div class="text-lg font-bold">{{ totalPopulasi }} Ekor</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm px-4 py-2 rounded-xl text-center">
                            <div class="text-[10px] uppercase text-emerald-200 font-bold">Siap Regenerasi</div>
                            <div class="text-lg font-bold">{{ totalHamil }} Bunting</div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 1: KARTU ARUS KAS -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Arus Kas & Keuntungan (Cashflow)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <div class="bg-gradient-to-br from-emerald-500/10 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wide">Pendapatan Bersih</span>
                                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shadow-md shadow-emerald-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </div>
                            </div>
                            <h2 class="text-2xl font-black text-slate-800">{{ formatRupiah(keuangan.pendapatan_bersih) }}</h2>
                            <p class="text-[11px] text-slate-400 mt-1">Uang Masuk dikurangi Seluruh Biaya Operasional</p>
                        </div>

                        <div class="bg-gradient-to-br from-indigo-500/10 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-indigo-700 uppercase tracking-wide">Pendapatan Kotor</span>
                                <div class="w-8 h-8 rounded-lg bg-indigo-500 text-white flex items-center justify-center shadow-md shadow-indigo-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                            </div>
                            <h2 class="text-2xl font-black text-slate-800">{{ formatRupiah(keuangan.pendapatan_kotor) }}</h2>
                            <p class="text-[11px] text-slate-400 mt-1">Total Nilai Penjualan Sebelum Potongan</p>
                        </div>

                        <div class="bg-gradient-to-br from-sky-500/10 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-sky-700 uppercase tracking-wide">Uang Masuk (Inflow)</span>
                                <div class="w-8 h-8 rounded-lg bg-sky-500 text-white flex items-center justify-center shadow-md shadow-sky-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                </div>
                            </div>
                            <h2 class="text-2xl font-black text-sky-700">+{{ formatRupiah(keuangan.uang_masuk) }}</h2>
                            <p class="text-[11px] text-slate-400 mt-1">Hasil Jual Ternak, Pupuk, & Anakan</p>
                        </div>

                        <div class="bg-gradient-to-br from-rose-500/10 via-white to-white rounded-2xl p-5 border border-rose-200/60 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-rose-700 uppercase tracking-wide">Uang Keluar (Expenses)</span>
                                <div class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shadow-md shadow-rose-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                </div>
                            </div>
                            <h2 class="text-2xl font-black text-rose-600">-{{ formatRupiah(keuangan.uang_keluar) }}</h2>
                            <p class="text-[11px] text-slate-400 mt-1">Catatan Pakan & Medis dari Lapangan</p>
                        </div>

                    </div>
                </div>

                <!-- SECTION 2: KARTU BIOLOGIS & POTENSI TERNAK -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Kondisi & Potensi Panen Ternak</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center justify-between">
                            <div>
                                <div class="text-xs text-slate-400 font-medium">Laju Tumbuh (Avg Gain)</div>
                                <div class="text-xl font-black text-emerald-600 mt-0.5">+{{ rataRataGain }} <span class="text-xs font-normal text-slate-400">Kg/Bln</span></div>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center justify-between">
                            <div>
                                <div class="text-xs text-slate-400 font-medium">Populasi Aktif</div>
                                <div class="text-xl font-black text-slate-700 mt-0.5">{{ totalPopulasi }} <span class="text-xs font-normal text-slate-400">Ekor</span></div>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center justify-between">
                            <div>
                                <div class="text-xs text-slate-400 font-medium">Proyeksi Anakan (Bunting)</div>
                                <div class="text-xl font-black text-sky-600 mt-0.5">{{ totalHamil }} <span class="text-xs font-normal text-slate-400">Indukan</span></div>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center justify-between">
                            <div>
                                <div class="text-xs text-slate-400 font-medium">Potensi Siap Panen</div>
                                <div class="text-xl font-black text-amber-600 mt-0.5">{{ siapPanen }} <span class="text-xs font-normal text-slate-400">Ekor Siap</span></div>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SECTION 3: TABEL KINERJA ASET -->
                <div class="bg-white rounded-2xl shadow-[0_2px_6px_0_rgba(67,89,113,0.06)] border border-slate-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                        <div>
                            <h4 class="font-bold text-slate-800 text-base">Laporan Inventaris Mutu Genetik & Bobot</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Data terverifikasi silsilah keturunan, status reproduksi, dan nilai jual ternak aktif di kandang</p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 border-b border-slate-100 text-xs uppercase bg-slate-50/50">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Ternak & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Silsilah / Keturunan</th>
                                    <th class="px-6 py-4 font-semibold">Kandang & Program</th>
                                    <th class="px-6 py-4 font-semibold">Bobot Terkini</th>
                                    <th class="px-6 py-4 font-semibold">Status Reproduksi</th>
                                    <th class="px-6 py-4 font-semibold text-right">Estimasi Nilai Ekor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in ternakAktif" :key="t.id" class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 font-mono tracking-tight">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-500 text-xs mt-0.5 font-medium">Ras: {{ t.ras }} • ({{ t.jenis_kelamin }})</div>
                                    </td>
                                    
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-slate-700"><span class="text-slate-400 font-medium">Pejantan:</span> {{ t.bapak || '-' }}</div>
                                        <div class="text-xs text-slate-700 mt-0.5"><span class="text-slate-400 font-medium">Indukan:</span> {{ t.mamak || '-' }}</div>
                                        <div v-if="t.jumlah_anak > 0" class="text-[11px] text-emerald-600 font-semibold mt-0.5">
                                            Anak: {{ t.jumlah_anak }} Ekor
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-700">{{ t.program }}</div>
                                        <div class="text-slate-400 text-xs mt-0.5">{{ t.kandang }}</div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-black text-slate-800">{{ t.berat_kg }} Kg</div>
                                        <div class="mt-0.5">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold" 
                                                  :class="t.selisih_kg >= 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200'">
                                                {{ t.selisih_kg > 0 ? '+' : '' }}{{ t.selisih_kg }} Kg
                                            </span>
                                        </div>
                                    </td>

                                    <!-- BADGE STATUS BUNTING FLEKSIBEL -->
                                    <td class="px-6 py-4">
                                        <span v-if="t.status_kehamilan && t.status_kehamilan.toUpperCase().includes('HAMIL')" class="bg-sky-50 text-sky-700 border border-sky-200 px-2.5 py-1 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            Bunting
                                        </span>
                                        <span v-else class="bg-slate-50 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-lg text-xs font-medium">
                                            Non-Bunting
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono">
                                        {{ formatRupiah((parseFloat(t.berat_kg) || 0) * 65000) }}
                                    </td>
                                </tr>

                                <tr v-if="ternakAktif.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium">
                                        Belum ada aset ternak terdaftar di database.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>