<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    transaksis: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ saldo_kas: 0, total_masuk: 0, total_keluar: 0, total_transaksi: 0 })
    },
    kategori_breakdown: { type: Array, default: () => [] }
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
const filterTipe = ref('All');
const filterKategori = ref('All');

const listKategori = computed(() => {
    const setKat = new Set(props.transaksis.map(t => t.kategori));
    return Array.from(setKat);
});

const filteredTransaksis = computed(() => {
    return props.transaksis.filter(t => {
        const matchSearch = t.keterangan.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.pj.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.kategori.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchTipe = filterTipe.value === 'All' ? true : t.tipe === filterTipe.value;
        const matchKategori = filterKategori.value === 'All' ? true : t.kategori === filterKategori.value;

        return matchSearch && matchTipe && matchKategori;
    });
});

const isModalOpen = ref(false);
const form = useForm({
    tipe: 'keluar',
    kategori: 'Pakan Ternak',
    nominal: '',
    keterangan: '',
    pj: user.value?.name || 'Owner Utama'
});

const submitTransaksi = () => {
    form.post(route('owner.keuangan.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
            form.pj = user.value?.name || 'Owner Utama';
        }
    });
};

const deleteTransaksi = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus catatan kas ini?')) {
        router.delete(route('owner.keuangan.destroy', id), {
            preserveScroll: true
        });
    }
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['transaksis', 'stats', 'kategori_breakdown']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Buku Kas Kandang - Owner" />

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
                <Link :href="route('owner.keuangan')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Buku Kas Kandang
                </Link>
                <Link :href="route('owner.panen')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Proyeksi Panen
                </Link>
                <Link :href="route('owner.terjual')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Buku Kas & Pengendalian Operasional</h2>
                        <p class="text-xs text-slate-400">Pencatatan arus kas riil, pengeluaran pakan, belanja medis, dan pendapatan kandang</p>
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
                
                <!-- ROW 1: KARTU METRIK KAS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Saldo Kas Bersih</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ formatRupiah(stats.saldo_kas) }}</h3>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Arus Kas Likuid</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-sky-700 uppercase tracking-wider">Total Uang Masuk</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">+{{ formatRupiah(stats.total_masuk) }}</h3>
                            <p class="text-[11px] text-sky-600 font-semibold mt-0.5">Penjualan & Pemasukan</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-rose-50/80 via-white to-white rounded-2xl p-5 border border-rose-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Total Pengeluaran</span>
                            <h3 class="text-2xl font-black text-rose-600 mt-1">-{{ formatRupiah(stats.total_keluar) }}</h3>
                            <p class="text-[11px] text-rose-500 font-semibold mt-0.5">Biaya Pakan & Operasional</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Frekuensi Transaksi</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ stats.total_transaksi }} <span class="text-xs font-normal text-slate-400">Mutasi</span></h3>
                            <p class="text-[11px] text-indigo-500 font-semibold mt-0.5">Total Bukti Terdata</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: BREAKDOWN ALOKASI -->
                <div v-if="kategori_breakdown.length > 0" class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Distribusi Alokasi Biaya Kandang</h4>
                            <p class="text-xs text-slate-400">Persentase pengeluaran dana berdasarkan pos operasional</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">Analisis Beban</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                        <div v-for="(item, idx) in kategori_breakdown" :key="idx" 
                             class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/50 flex flex-col justify-between">
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-bold text-slate-700">{{ item.kategori }}</span>
                                <span class="text-xs font-extrabold text-rose-600">{{ item.persen }}%</span>
                            </div>
                            <div class="mt-2.5">
                                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-rose-500 h-1.5 rounded-full transition-all duration-500" :style="{ width: `${item.persen}%` }"></div>
                                </div>
                                <span class="text-[11px] font-semibold text-slate-500 block mt-1.5">{{ formatRupiah(item.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: TABEL MUTASI -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h4 class="font-bold text-slate-800 text-base">Buku Catatan Kas Operasional</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Rincian mutasi transaksi kas masuk dan pengeluaran harian</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <input v-model="searchQuery" type="text" placeholder="Cari keterangan / PJ..." 
                                   class="px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none w-full sm:w-48">
                            
                            <select v-model="filterTipe" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none">
                                <option value="All">Semua Tipe</option>
                                <option value="masuk">Uang Masuk</option>
                                <option value="keluar">Uang Keluar</option>
                            </select>

                            <select v-model="filterKategori" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none">
                                <option value="All">Semua Kategori</option>
                                <option v-for="kat in listKategori" :key="kat" :value="kat">{{ kat }}</option>
                            </select>

                            <button @click="isModalOpen = true" 
                                    class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Catat Transaksi
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Waktu & Tanggal</th>
                                    <th class="px-6 py-4 font-semibold">Arus Kas</th>
                                    <th class="px-6 py-4 font-semibold">Pos Kategori</th>
                                    <th class="px-6 py-4 font-semibold">Keterangan / Deskripsi</th>
                                    <th class="px-6 py-4 font-semibold">Penanggung Jawab</th>
                                    <th class="px-6 py-4 font-semibold text-right">Nominal</th>
                                    <th class="px-6 py-4 font-semibold text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredTransaksis" :key="t.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-500">{{ t.tanggal }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1"
                                              :class="t.tipe === 'masuk' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="t.tipe === 'masuk' ? 'bg-sky-500' : 'bg-rose-500'"></span>
                                            {{ t.tipe === 'masuk' ? 'Uang Masuk' : 'Uang Keluar' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-xs text-slate-700">{{ t.kategori }}</td>
                                    <td class="px-6 py-4 text-xs text-slate-600 max-w-[220px] truncate" :title="t.keterangan">{{ t.keterangan }}</td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">{{ t.pj }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-sm font-mono" :class="t.tipe === 'masuk' ? 'text-sky-700' : 'text-rose-600'">
                                        {{ t.tipe === 'masuk' ? '+' : '-' }}{{ formatRupiah(t.nominal) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="deleteTransaksi(t.id)" class="text-slate-400 hover:text-rose-600 transition p-1" title="Hapus Catatan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredTransaksis.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">Belum ada catatan mutasi kas yang terdaftar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL TAMBAH KAS -->
                <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <h3 class="font-bold text-slate-800 text-base">Catat Transaksi Kas Baru</h3>
                            <button @click="isModalOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitTransaksi" class="p-6 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Tipe Arus Kas</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="form.tipe = 'masuk'" class="py-2 text-xs font-bold rounded-xl border transition" :class="form.tipe === 'masuk' ? 'bg-sky-50 border-sky-300 text-sky-700' : 'border-slate-200 text-slate-500'">Uang Masuk (Inflow)</button>
                                    <button type="button" @click="form.tipe = 'keluar'" class="py-2 text-xs font-bold rounded-xl border transition" :class="form.tipe === 'keluar' ? 'bg-rose-50 border-rose-300 text-rose-700' : 'border-slate-200 text-slate-500'">Uang Keluar (Expenses)</button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Pos Kategori</label>
                                <select v-model="form.kategori" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Pakan Ternak">Pakan Ternak (Konsentrat/Hijauan)</option>
                                    <option value="Medis & Obat">Medis, Vitamin & Vaksinasi</option>
                                    <option value="Penjualan Ternak">Penjualan Ternak Panen</option>
                                    <option value="Penjualan Pupuk">Penjualan Pupuk Kandang</option>
                                    <option value="Listrik & Operasional">Listrik, Air & Perawatan</option>
                                    <option value="Upah & Tenaga Kerja">Upah / Operasional Lapangan</option>
                                    <option value="Modal / Investasi">Modal / Tambahan Kas</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nominal (Rupiah)</label>
                                <input v-model="form.nominal" type="number" min="1000" step="500" required placeholder="Contoh: 1500000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Keterangan / Rincian</label>
                                <input v-model="form.keterangan" type="text" required placeholder="Contoh: Pembelian pakan konsentrat 10 sak" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Penanggung Jawab (PJ)</label>
                                <input v-model="form.pj" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-xl transition">Batal</button>
                                <button type="submit" :disabled="form.processing" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm disabled:opacity-50">Simpan Catatan Kas</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>