<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    kambing_ready: { type: Array, default: () => [] },
    transaksi_hari_ini: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_ready: 0,
            terjual_hari_ini: 0,
            omzet_hari_ini: 0,
            avg_harga: 0
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

// Format Rupiah Standar
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// Format Angka Bertitik
const formatAngkaTitik = (val) => {
    if (!val && val !== 0) return '';
    const clean = val.toString().replace(/\D/g, '');
    return clean ? new Intl.NumberFormat('id-ID').format(clean) : '';
};

// Filter & Pencarian
const searchQuery = ref('');
const filterKandang = ref('All');

const filteredKambing = computed(() => {
    return props.kambing_ready.filter(t => {
        const q = (searchQuery.value || '').toLowerCase();
        const rfid = (t.rfid_tag || '').toLowerCase();
        const ras = (t.ras || '').toLowerCase();
        const kandang = (t.kandang || '').toLowerCase();
        const matchSearch = rfid.includes(q) || ras.includes(q) || kandang.includes(q);
        const matchKandang = filterKandang.value === 'All' ? true : t.kandang === filterKandang.value;
        return matchSearch && matchKandang;
    });
});

// MODAL CHECKOUT KASIR LAPANGAN
const isModalKasirOpen = ref(false);
const ternakTerpilih = ref(null);
const modeHitung = ref('kg');

const tarifPerKg = ref(65000);
const displayTarifKg = ref('65.000');
const displayHargaJual = ref('0');

const formKasir = useForm({
    berat_final: 0,
    harga_jual: 0,
    pembeli: '',
    tujuan_transaksi: 'Paket Aqiqah',
    metode_pembayaran: 'Tunai (Cash)',
    catatan: 'Serah terima langsung di kandang.'
});

const hitungOtomatisHarga = () => {
    if (modeHitung.value === 'kg') {
        const total = Math.round((parseFloat(formKasir.berat_final) || 0) * (tarifPerKg.value || 0));
        formKasir.harga_jual = total;
        displayHargaJual.value = formatAngkaTitik(total);
    }
};

const onTarifInput = (e) => {
    const raw = e.target.value.replace(/\D/g, '');
    tarifPerKg.value = raw ? parseInt(raw, 10) : 0;
    displayTarifKg.value = formatAngkaTitik(raw);
    hitungOtomatisHarga();
};

const onHargaManualInput = (e) => {
    const raw = e.target.value.replace(/\D/g, '');
    formKasir.harga_jual = raw ? parseInt(raw, 10) : 0;
    displayHargaJual.value = formatAngkaTitik(raw);
};

const bukaModalKasir = (t) => {
    ternakTerpilih.value = t;
    formKasir.berat_final = t.berat_kg;
    modeHitung.value = 'kg';

    const defaultTarif = (t.ras && (t.ras.toLowerCase().includes('garut') || t.ras.toLowerCase().includes('dorper') || t.ras.toLowerCase().includes('texel'))) ? 70000 : 65000;
    tarifPerKg.value = defaultTarif;
    displayTarifKg.value = formatAngkaTitik(defaultTarif);

    hitungOtomatisHarga();

    formKasir.pembeli = '';
    formKasir.tujuan_transaksi = parseFloat(t.berat_kg) >= 40 ? 'Hewan Qurban' : 'Paket Aqiqah';
    formKasir.metode_pembayaran = 'Tunai (Cash)';
    formKasir.catatan = 'Hewan sehat prima, ditimbang ulang di kasir lapangan.';
    isModalKasirOpen.value = true;
};

// Struk Nota Digital
const notaTransaksi = ref(null);
const isModalNotaOpen = ref(false);

const submitPenjualan = () => {
    if (!ternakTerpilih.value) return;
    formKasir.post(route('operator.kasir.proses', ternakTerpilih.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            notaTransaksi.value = {
                rfid_tag: ternakTerpilih.value.rfid_tag,
                ras: ternakTerpilih.value.ras,
                berat_kg: formKasir.berat_final,
                harga_jual: formKasir.harga_jual,
                pembeli: formKasir.pembeli,
                tujuan: formKasir.tujuan_transaksi,
                metode: formKasir.metode_pembayaran,
                tanggal: new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }),
                petugas: user.value?.name || 'Petugas Lapangan'
            };
            isModalKasirOpen.value = false;
            isModalNotaOpen.value = true;
        }
    });
};

const cetakNota = () => {
    window.print();
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['kambing_ready', 'transaksi_hari_ini', 'stats']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Kasir Penjualan Lapangan - Anak Kandang" />

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
                <Link :href="route('operator.kasir')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Kasir Penjualan Lapangan
                </Link>
                <Link :href="route('operator.katalog')" class="flex items-center px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-emerald-700 rounded-xl font-medium transition">
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Terminal Kasir Penjualan Lapangan</h2>
                        <p class="text-xs text-slate-400">Transaksi jual ternak di kandang, timbang hidup, dan sinkronisasi kas masuk Owner</p>
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
                
                <!-- ROW 1: METRIK PENJUALAN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm">
                        <span class="text-xs font-bold text-emerald-700 uppercase">Kambing Ready Jual</span>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_ready }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Stok Kandang Display</p>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm">
                        <span class="text-xs font-bold text-sky-700 uppercase">Terjual Hari Ini</span>
                        <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.terjual_hari_ini }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                        <p class="text-[11px] text-sky-600 font-medium mt-0.5">Sudah Dikeluarkan</p>
                    </div>

                    <div class="bg-gradient-to-br from-teal-50/80 via-white to-white rounded-2xl p-5 border border-teal-200/60 shadow-sm">
                        <span class="text-xs font-bold text-teal-700 uppercase">Kas Masuk Hari Ini</span>
                        <h3 class="text-2xl font-black text-teal-700 mt-1">{{ formatRupiah(stats.omzet_hari_ini) }}</h3>
                        <p class="text-[11px] text-teal-600 font-medium mt-0.5">Terkirim ke Kas Owner</p>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm">
                        <span class="text-xs font-bold text-indigo-700 uppercase">Rata-rata Penjualan</span>
                        <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ formatRupiah(stats.avg_harga) }}</h3>
                        <p class="text-[11px] text-indigo-600 font-medium mt-0.5">Per Ekor Terjual</p>
                    </div>
                </div>

                <!-- ROW 2: ETALASE KAMBING SIAP JUAL -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-slate-800 text-base">Etalase Kambing Siap Transaksi (Ready Stock)</h4>
                                <span class="text-xs font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">
                                    {{ filteredKambing.length }} Ekor Siap Lepas
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Pilih kambing yang diminati pembeli untuk ditimbang ulang dan diproses pembayarannya</p>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto">
                            <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras..." 
                                   class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs outline-none focus:ring-2 focus:ring-emerald-200 w-full sm:w-56">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="px-6 py-4 font-semibold">Kandang Display</th>
                                    <th class="px-6 py-4 font-semibold">Bobot Terkini</th>
                                    <th class="px-6 py-4 font-semibold">Estimasi Nilai Ekor</th>
                                    <th class="px-6 py-4 font-semibold">Status Kesiapan</th>
                                    <th class="px-6 py-4 font-semibold text-center">Aksi Kasir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="t in filteredKambing" :key="t.id" class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-bold font-mono text-slate-800 text-sm">{{ t.rfid_tag }}</div>
                                        <div class="text-[11px] text-slate-400 font-medium">{{ t.ras }} • {{ t.jenis_kelamin }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-700">
                                        {{ t.kandang }}
                                    </td>
                                    <td class="px-6 py-4 font-black font-mono text-slate-800 text-sm">
                                        {{ t.berat_kg }} Kg
                                    </td>
                                    <td class="px-6 py-4 font-bold font-mono text-emerald-700 text-sm">
                                        {{ formatRupiah(t.estimasi_harga) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            {{ t.program === 'Siap Jual' ? 'Ready di Katalog' : 'Bobot Prima' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="bukaModalKasir(t)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl transition shadow-xs flex items-center gap-1.5 mx-auto">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                            Proses Jual
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredKambing.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        Belum ada kambing dengan status Siap Jual atau bobot >= 30 Kg di kandang.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ROW 3: CATATAN PENJUALAN HARI INI -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Catatan Penjualan Hari Ini</h4>
                            <p class="text-xs text-slate-400">Data otomatis tercatat di Buku Kas dan menu Kambing Terjual Owner</p>
                        </div>
                        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-xl">
                            {{ transaksi_hari_ini.length }} Ekor Keluar
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="text-slate-400 uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="py-2.5 px-4 font-semibold">Tag RFID & Ras</th>
                                    <th class="py-2.5 px-4 font-semibold">Bobot Akhir</th>
                                    <th class="py-2.5 px-4 font-semibold">Pembeli / Keterangan</th>
                                    <th class="py-2.5 px-4 font-semibold text-right">Nilai Pembayaran</th>
                                    <th class="py-2.5 px-4 font-semibold text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in transaksi_hari_ini" :key="t.id" class="hover:bg-slate-50/60">
                                    <td class="py-3 px-4">
                                        <div class="font-mono font-bold text-slate-800">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-400">{{ t.ras }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-bold font-mono text-slate-700">
                                        {{ t.berat_kg }} Kg
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-800">
                                        {{ t.pembeli }}
                                    </td>
                                    <td class="py-3 px-4 font-black font-mono text-emerald-700 text-right text-sm">
                                        +{{ formatRupiah(t.harga_jual) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-lg text-[10px]">
                                            Lunas Keluar
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="transaksi_hari_ini.length === 0">
                                    <td colspan="5" class="py-8 text-center text-slate-400">Belum ada transaksi penjualan ternak hari ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- MODAL CHECKOUT KASIR LAPANGAN -->
                <div v-if="isModalKasirOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 my-8">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-emerald-50">
                            <div>
                                <h3 class="font-bold text-emerald-900 text-base">Kasir Checkout Penjualan Ternak</h3>
                                <p class="text-xs text-emerald-700">Timbang akhir, kalkulasi harga, dan serah terima hewan</p>
                            </div>
                            <button @click="isModalKasirOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitPenjualan" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                                <div>
                                    <div class="font-mono font-bold text-slate-800 text-sm">{{ ternakTerpilih?.rfid_tag }}</div>
                                    <div class="text-slate-500">{{ ternakTerpilih?.ras }} ({{ ternakTerpilih?.jenis_kelamin }}) • {{ ternakTerpilih?.kandang }}</div>
                                </div>
                                <span class="bg-white border border-slate-200 font-bold px-2.5 py-1 rounded-lg text-slate-700">
                                    Terkini: {{ ternakTerpilih?.berat_kg }} Kg
                                </span>
                            </div>

                            <!-- OPSI METODE HITUNG HARGA -->
                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Metode Penentuan Harga</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="modeHitung = 'kg'; hitungOtomatisHarga()"
                                            class="py-2.5 font-bold rounded-xl border transition"
                                            :class="modeHitung === 'kg' ? 'bg-emerald-50 border-emerald-400 text-emerald-700 ring-2 ring-emerald-200' : 'border-slate-200 text-slate-500'">
                                        Timbang Hidup (Per Kg)
                                    </button>
                                    <button type="button" @click="modeHitung = 'borongan'"
                                            class="py-2.5 font-bold rounded-xl border transition"
                                            :class="modeHitung === 'borongan' ? 'bg-indigo-50 border-indigo-400 text-indigo-700 ring-2 ring-indigo-200' : 'border-slate-200 text-slate-500'">
                                        Harga Borongan / Ekoran
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Bobot Timbang Final (Kg)</label>
                                    <input v-model.number="formKasir.berat_final" @input="hitungOtomatisHarga" type="number" step="0.01" min="1" required
                                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 font-mono font-bold text-slate-800 focus:ring-2 focus:ring-emerald-200 outline-none">
                                </div>
                                <div v-if="modeHitung === 'kg'">
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Tarif / Kg (Rp)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 font-bold text-slate-500 text-xs">Rp</span>
                                        <input :value="displayTarifKg" @input="onTarifInput" type="text" required
                                               class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 font-mono font-bold text-slate-800 focus:ring-2 focus:ring-emerald-200 outline-none text-xs">
                                    </div>
                                </div>
                                <div v-else>
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Paket Penjualan</label>
                                    <span class="block bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2 text-slate-500 font-semibold">
                                        Manual / Deal Ekor
                                    </span>
                                </div>
                            </div>

                            <!-- NOMINAL DEAL AKHIR DENGAN FORMAT TITIK REALTIME -->
                            <div class="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-black text-emerald-900 uppercase tracking-wider">Total Harga Deal Akhir</label>
                                    <span class="text-xs font-bold text-emerald-700 font-mono">Rp {{ displayHargaJual || '0' }}</span>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-2.5 font-bold text-emerald-700 text-base">Rp</span>
                                    <input :value="displayHargaJual" @input="onHargaManualInput" type="text" required
                                           placeholder="Contoh: 2.500.000"
                                           class="w-full bg-white border border-emerald-300 rounded-xl pl-11 pr-4 py-2.5 text-lg font-black font-mono text-emerald-800 focus:ring-2 focus:ring-emerald-300 outline-none">
                                </div>
                                <p class="text-[10px] text-emerald-700 mt-1.5 font-medium">*Nominal ini yang akan otomatis tercatat ke kas riil Owner.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Nama Pembeli</label>
                                    <input v-model="formKasir.pembeli" type="text" required placeholder="Contoh: Pak Haji Rahmat"
                                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-emerald-200 outline-none">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 uppercase mb-1">Tujuan Penggunaan</label>
                                    <select v-model="formKasir.tujuan_transaksi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-emerald-200 outline-none">
                                        <option value="Paket Aqiqah">Paket Aqiqah</option>
                                        <option value="Hewan Qurban">Hewan Qurban</option>
                                        <option value="Jagal / Rumah Makan">Jagal / Rumah Makan</option>
                                        <option value="Bibit / Pelihara">Bibit / Pelihara</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Metode Pembayaran</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" @click="formKasir.metode_pembayaran = 'Tunai (Cash)'"
                                            class="py-2 font-bold rounded-xl border transition text-[11px]"
                                            :class="formKasir.metode_pembayaran === 'Tunai (Cash)' ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 text-slate-600'">
                                        Tunai (Cash)
                                    </button>
                                    <button type="button" @click="formKasir.metode_pembayaran = 'Transfer Bank'"
                                            class="py-2 font-bold rounded-xl border transition text-[11px]"
                                            :class="formKasir.metode_pembayaran === 'Transfer Bank' ? 'bg-sky-600 text-white border-sky-600' : 'border-slate-200 text-slate-600'">
                                        Transfer Bank
                                    </button>
                                    <button type="button" @click="formKasir.metode_pembayaran = 'QRIS'"
                                            class="py-2 font-bold rounded-xl border transition text-[11px]"
                                            :class="formKasir.metode_pembayaran === 'QRIS' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-600'">
                                        QRIS
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Catatan Serah Terima</label>
                                <textarea v-model="formKasir.catatan" rows="2" placeholder="Catatan pengambilan hewan..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-emerald-200 outline-none"></textarea>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalKasirOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formKasir.processing" class="px-5 py-2 text-white bg-emerald-600 hover:bg-emerald-700 font-bold rounded-xl transition shadow-sm">
                                    Konfirmasi & Lepas Ternak
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL NOTA / STRUK DIGITAL -->
                <div v-if="isModalNotaOpen" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-100 p-6 space-y-4">
                        <div class="text-center pb-3 border-b border-dashed border-slate-200">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-700 font-black rounded-xl mx-auto flex items-center justify-center mb-1 text-base">
                                JJ
                            </div>
                            <h3 class="font-black text-slate-800 text-base">JJFarm - Bukti Lepas Ternak</h3>
                            <p class="text-[11px] text-slate-400">Terminal Kasir Lapangan Kandang Pintar</p>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tanggal:</span>
                                <span class="font-semibold text-slate-700">{{ notaTransaksi?.tanggal }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Petugas Kasir:</span>
                                <span class="font-semibold text-slate-700">{{ notaTransaksi?.petugas }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Pembeli:</span>
                                <span class="font-bold text-slate-800">{{ notaTransaksi?.pembeli }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Keperluan:</span>
                                <span class="font-semibold text-slate-700">{{ notaTransaksi?.tujuan }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Metode Bayar:</span>
                                <span class="font-bold text-emerald-700">{{ notaTransaksi?.metode }}</span>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tag RFID:</span>
                                <span class="font-mono font-bold text-slate-800">{{ notaTransaksi?.rfid_tag }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Ras Ternak:</span>
                                <span class="font-semibold text-slate-700">{{ notaTransaksi?.ras }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Bobot Timbang:</span>
                                <span class="font-black font-mono text-slate-800">{{ notaTransaksi?.berat_kg }} Kg</span>
                            </div>
                            <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm">
                                <span class="font-black text-slate-800">Total Lunas:</span>
                                <span class="font-black font-mono text-emerald-700">{{ formatRupiah(notaTransaksi?.harga_jual) }}</span>
                            </div>
                        </div>

                        <div class="pt-2 flex gap-2">
                            <button @click="cetakNota" class="flex-1 py-2 text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition">
                                Cetak Nota
                            </button>
                            <button @click="isModalNotaOpen = false" class="px-4 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>