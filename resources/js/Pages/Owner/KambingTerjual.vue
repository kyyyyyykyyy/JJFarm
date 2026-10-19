<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    ternak_terjual: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_ekor: 0,
            total_omzet: 0,
            total_bobot: 0,
            avg_harga_per_kg: 0
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

const searchQuery = ref('');

const filteredTerjual = computed(() => {
    return props.ternak_terjual.filter(t => {
        return t.rfid_tag.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
               t.ras.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
               t.kandang_asal.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
               t.pembeli.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
});

const batalkanPenjualan = (id) => {
    if (confirm('Kembalikan kambing ini ke inventaris aktif di kandang?')) {
        router.post(route('owner.panen.batal', id), {}, { preserveScroll: true });
    }
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['ternak_terjual', 'stats']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Kambing Terjual - Owner" />

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
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3">Ringkasan Eksekutif</div>
                <Link :href="route('dashboard')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Kinerja Finansial
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Aset & Pertumbuhan</div>
                <Link :href="route('owner.monitoring')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    Monitoring Bobot
                </Link>
                <Link :href="route('owner.breeding')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Silsilah & Breeding
                </Link>

                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-3 mt-5">Komersial & Kas</div>
                <Link :href="route('owner.keuangan')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Buku Kas Kandang
                </Link>
                <Link :href="route('owner.panen')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Proyeksi Panen
                </Link>
                <!-- MENU AKTIF: KAMBING TERJUAL -->
                <Link :href="route('owner.terjual')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Laporan Realisasi Kambing Terjual</h2>
                        <p class="text-xs text-slate-400">Rekapitulasi ternak yang telah keluar/dijual oleh petugas lapangan beserta penerimaan kas riil</p>
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
                
                <!-- ROW 1: KARTU METRIK REKAP TERJUAL -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Populasi Terjual</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_ekor }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Realisasi Keluar Kandang</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-sky-700 uppercase tracking-wider">Total Kas Penjualan</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">{{ formatRupiah(stats.total_omzet) }}</h3>
                            <p class="text-[11px] text-sky-600 font-semibold mt-0.5">Tercatat di Buku Kas</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-teal-50/80 via-white to-white rounded-2xl p-5 border border-teal-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-teal-700 uppercase tracking-wider">Volume Daging Terjual</span>
                            <h3 class="text-2xl font-black text-teal-700 mt-1">{{ stats.total_bobot }} <span class="text-xs font-normal text-slate-400">Kg</span></h3>
                            <p class="text-[11px] text-teal-600 font-semibold mt-0.5">Akumulasi Bobot Hidup</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Rata-rata Realisasi / Kg</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ formatRupiah(stats.avg_harga_per_kg) }}</h3>
                            <p class="text-[11px] text-indigo-500 font-semibold mt-0.5">Harga Bersih per Kg</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: TABEL DATA KAMBING TERJUAL -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_6px_0_rgba(67,89,113,0.05)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h4 class="font-bold text-slate-800 text-base">Buku Registri Kambing Terjual</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Daftar transaksi kambing yang divalidasi dan terhubung langsung ke Buku Kas</p>
                        </div>

                        <div class="flex items-center gap-2.5 w-full md:w-auto">
                            <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras / Pembeli..." 
                                   class="px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none w-full sm:w-64">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tanggal Lepas</th>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Kandang Asal</th>
                                    <th class="px-6 py-4 font-semibold">Bobot Akhir</th>
                                    <th class="px-6 py-4 font-semibold">Pembeli / Keterangan</th>
                                    <th class="px-6 py-4 font-semibold text-right">Nilai Transaksi</th>
                                    <th class="px-6 py-4 font-semibold text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredTerjual" :key="t.id" class="hover:bg-slate-50/60 transition-colors">
                                    
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-500">
                                        {{ t.tgl_terjual }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 font-mono">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-500 text-xs mt-0.5 font-medium">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                    </td>

                                    <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                        {{ t.kandang_asal }}
                                    </td>

                                    <td class="px-6 py-4 font-black font-mono text-slate-800 text-sm">
                                        {{ t.berat_kg }} Kg
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="bg-sky-50 text-sky-800 border border-sky-200 px-2.5 py-1 rounded-lg text-xs font-semibold">
                                            {{ t.pembeli }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right font-black text-emerald-700 font-mono text-sm">
                                        +{{ formatRupiah(t.harga_jual) }}
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs font-bold mr-2">
                                            Lunas Terjual
                                        </span>
                                        <button @click="batalkanPenjualan(t.id)" 
                                                class="text-xs text-slate-400 hover:text-rose-600 transition" 
                                                title="Batalkan Penjualan">
                                            Batalkan
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="filteredTerjual.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        Belum ada catatan kambing yang terjual di sistem.
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