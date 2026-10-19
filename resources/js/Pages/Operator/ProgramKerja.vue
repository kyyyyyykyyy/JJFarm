<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    pembibitan: { type: Array, default: () => [] },
    penggemukan: { type: Array, default: () => [] },
    kandangs: { type: Array, default: () => [] },
    riwayat_timbang: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_bibit: 0,
            total_bunting: 0,
            butuh_usg: 0,
            total_gemuk: 0,
            siap_panen: 0,
            ready_katalog: 0,
            avg_gain_gemuk: 0
        })
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const activeTab = ref('pembibitan');
const searchQuery = ref('');

// Helper Deteksi Bunting Fleksibel
const cekIsBunting = (status) => {
    if (!status) return false;
    const s = String(status).toUpperCase();
    return s.includes('HAMIL') || s.includes('BUNTING');
};

const filteredPembibitan = computed(() => {
    return props.pembibitan.filter(t => {
        const q = searchQuery.value.toLowerCase();
        return t.rfid_tag.toLowerCase().includes(q) ||
               t.ras.toLowerCase().includes(q) ||
               t.kandang.toLowerCase().includes(q);
    });
});

const filteredPenggemukan = computed(() => {
    return props.penggemukan.filter(t => {
        const q = searchQuery.value.toLowerCase();
        return t.rfid_tag.toLowerCase().includes(q) ||
               t.ras.toLowerCase().includes(q) ||
               t.kandang.toLowerCase().includes(q) ||
               t.program.toLowerCase().includes(q);
    });
});

const isModalUsgOpen = ref(false);
const ternakTerpilihUsg = ref(null);
const formUsg = useForm({
    status_kehamilan: 'Hamil',
    catatan_usg: '',
    pindah_kandang: true
});

const bukaModalUsg = (t) => {
    ternakTerpilihUsg.value = t;
    formUsg.status_kehamilan = cekIsBunting(t.status_kehamilan) ? t.status_kehamilan : 'Hamil';
    formUsg.catatan_usg = '';
    formUsg.pindah_kandang = true;
    isModalUsgOpen.value = true;
};

const submitUsg = () => {
    if (!ternakTerpilihUsg.value) return;
    formUsg.post(route('operator.program.usg', ternakTerpilihUsg.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalUsgOpen.value = false
    });
};

const isModalTimbangOpen = ref(false);
const ternakTerpilihTimbang = ref(null);
const formTimbang = useForm({
    berat_kg: '',
    sumber_alat: 'Timbangan Digital Lapangan'
});

const bukaModalTimbang = (t) => {
    ternakTerpilihTimbang.value = t;
    formTimbang.berat_kg = t.berat_kg;
    formTimbang.sumber_alat = 'Timbangan Digital Lapangan';
    isModalTimbangOpen.value = true;
};

const submitTimbang = () => {
    if (!ternakTerpilihTimbang.value) return;
    formTimbang.post(route('operator.program.timbang', ternakTerpilihTimbang.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalTimbangOpen.value = false
    });
};

const isModalSiapJualOpen = ref(false);
const ternakTerpilihJual = ref(null);
const formSiapJual = useForm({
    berat_kg: '',
    grade_katalog: 'Qurban Super',
    kandang: 'Kandang B2',
    catatan: 'Bobot optimal, nafsu makan prima, postur tegap & siap panen.'
});

const bukaModalSiapJual = (t) => {
    ternakTerpilihJual.value = t;
    formSiapJual.berat_kg = t.berat_kg;
    formSiapJual.kandang = t.kandang || 'Kandang B2';
    formSiapJual.grade_katalog = parseFloat(t.berat_kg) >= 40 ? 'Qurban Super' : (parseFloat(t.berat_kg) >= 32 ? 'Paket Aqiqah Prima' : 'Pedaging / Jagal');
    formSiapJual.catatan = 'Lolos seleksi bobot, siap ditransaksikan oleh Owner.';
    isModalSiapJualOpen.value = true;
};

const submitSiapJual = () => {
    if (!ternakTerpilihJual.value) return;
    formSiapJual.post(route('operator.program.siapJual', ternakTerpilihJual.value.id), {
        preserveScroll: true,
        onSuccess: () => isModalSiapJualOpen.value = false
    });
};

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['pembibitan', 'penggemukan', 'riwayat_timbang', 'stats']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});
</script>

<template>
    <Head title="Program Kerja Lapangan - Anak Kandang" />

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
                
                <Link :href="route('operator.program')" 
                      :class="$page.url.startsWith('/operator/program') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3" :class="$page.url.startsWith('/operator/program') ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    Program Pembibitan & Gemuk
                </Link>
                
                <Link :href="route('operator.kandang')" 
                      :class="$page.url.startsWith('/operator/pipeline-kandang') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Pipeline Siklus Kandang
                </Link>

                <Link :href="route('operator.karantina')" 
                      :class="$page.url.startsWith('/operator/karantina') ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-700 font-medium'"
                      class="flex items-center px-3.5 py-2.5 rounded-xl transition">
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Program Pembibitan & Penggemukan Ternak</h2>
                        <p class="text-xs text-slate-400">Pemeriksaan USG indukan, evaluasi bobot hidup, dan rilis kambing siap jual ke kasir Owner</p>
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

                <!-- SWITCHER TAB PROGRAM -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200/80 pb-4">
                    <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl">
                        <button @click="activeTab = 'pembibitan'" 
                                class="px-5 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                                :class="activeTab === 'pembibitan' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            1. Program Pembibitan & USG ({{ pembibitan.length }})
                        </button>
                        <button @click="activeTab = 'penggemukan'" 
                                class="px-5 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                                :class="activeTab === 'penggemukan' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                            2. Program Penggemukan & Siap Jual ({{ penggemukan.length }})
                        </button>
                    </div>

                    <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras..." 
                           class="px-3.5 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none w-full sm:w-60 shadow-xs">
                </div>

                <!-- TAB 1: PEMBIBITAN -->
                <div v-if="activeTab === 'pembibitan'" class="space-y-6">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-pink-50/80 via-white to-white rounded-2xl p-5 border border-pink-200/60 shadow-sm">
                            <span class="text-xs font-bold text-pink-700 uppercase">Populasi Indukan Pembibitan</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_bibit }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-pink-600 mt-0.5 font-medium">Fokus Regenerasi Trah Unggul</p>
                        </div>

                        <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm">
                            <span class="text-xs font-bold text-sky-700 uppercase">Positif Hamil (Bunting)</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.total_bunting }} <span class="text-xs font-normal text-slate-400">Indukan</span></h3>
                            <p class="text-[11px] text-sky-600 mt-0.5 font-medium">Terdata di Kandang Bunting</p>
                        </div>

                        <div class="bg-gradient-to-br from-amber-50/80 via-white to-white rounded-2xl p-5 border border-amber-200/60 shadow-sm">
                            <span class="text-xs font-bold text-amber-700 uppercase">Jadwal USG (Kandang A1)</span>
                            <h3 class="text-2xl font-black text-amber-700 mt-1">{{ stats.butuh_usg }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-amber-600 mt-0.5 font-medium">Telah >= 36 Hari Masa Kawin</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100">
                            <h4 class="font-bold text-slate-800 text-sm">Daftar Indukan Masa Kawin & Pemeriksaan USG</h4>
                            <p class="text-xs text-slate-400">Verifikasi status kehamilan dan eksekusi pemindahan ke Kandang Bunting (B1)</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                        <th class="px-6 py-4 font-semibold">Kandang & Siklus Hari</th>
                                        <th class="px-6 py-4 font-semibold">Status Kehamilan</th>
                                        <th class="px-6 py-4 font-semibold">Catatan Pemeriksaan</th>
                                        <th class="px-6 py-4 font-semibold text-center">Aksi Seleksi USG</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="t in filteredPembibitan" :key="t.id" class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <div class="font-mono font-bold text-slate-800">{{ t.rfid_tag }}</div>
                                            <div class="text-xs text-slate-500">{{ t.ras }} • Bobot: {{ t.berat_kg }} Kg</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-700 text-xs">{{ t.kandang }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">Hari ke-{{ t.hari_di_kandang }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="cekIsBunting(t.status_kehamilan)" class="bg-sky-50 text-sky-700 border border-sky-200 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                                {{ t.status_kehamilan === 'Hamil' ? 'Bunting (Hamil)' : t.status_kehamilan }}
                                            </span>
                                            <span v-else class="bg-slate-50 text-slate-500 border border-slate-200 px-2.5 py-1 rounded-lg text-xs font-medium">
                                                {{ (!t.status_kehamilan || t.status_kehamilan === 'N/A') ? 'Non-Bunting' : t.status_kehamilan }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-600 max-w-[200px] truncate" :title="t.catatan_pakar">
                                            {{ t.catatan_pakar || 'Pemeriksaan rutin' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button @click="bukaModalUsg(t)" class="bg-pink-600 hover:bg-pink-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs">
                                                Input Hasil USG
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredPembibitan.length === 0">
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-400 text-xs">Belum ada ternak terdaftar di program pembibitan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- TAB 2: PENGGEMUKAN & SIAP JUAL -->
                <div v-else class="space-y-6">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-indigo-50/80 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm">
                            <span class="text-xs font-bold text-indigo-700 uppercase">Populasi Gemuk</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_gemuk }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-indigo-600 mt-0.5 font-medium">Target Pembesaran</p>
                        </div>

                        <div class="bg-gradient-to-br from-emerald-50/80 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm">
                            <span class="text-xs font-bold text-emerald-700 uppercase">Target Bobot (>= 30 Kg)</span>
                            <h3 class="text-2xl font-black text-emerald-700 mt-1">{{ stats.siap_panen }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-emerald-600 mt-0.5 font-medium">Lolos Kriteria Panen</p>
                        </div>

                        <div class="bg-gradient-to-br from-teal-50/80 via-white to-white rounded-2xl p-5 border border-teal-200/60 shadow-sm">
                            <span class="text-xs font-bold text-teal-700 uppercase">Ready di Katalog Jual</span>
                            <h3 class="text-2xl font-black text-teal-700 mt-1">{{ stats.ready_katalog }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-teal-600 mt-0.5 font-medium">Terkoneksi ke Kasir Owner</p>
                        </div>

                        <div class="bg-gradient-to-br from-sky-50/80 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm">
                            <span class="text-xs font-bold text-sky-700 uppercase">Rata-rata Gain</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">+{{ stats.avg_gain_gemuk }} <span class="text-xs font-normal text-slate-400">Kg</span></h3>
                            <p class="text-[11px] text-sky-600 mt-0.5 font-medium">Kenaikan Bobot Harian</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">Evaluasi Bobot & Rilis ke Katalog Penjualan</h4>
                                <p class="text-xs text-slate-400">Ternak yang dirilis ke Siap Jual otomatis terhubung ke Kasir Lapangan dan Proyeksi Panen Owner</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-4 font-semibold">Tag RFID & Ras</th>
                                        <th class="px-6 py-4 font-semibold">Penempatan Kandang</th>
                                        <th class="px-6 py-4 font-semibold">Bobot Terkini</th>
                                        <th class="px-6 py-4 font-semibold">Kenaikan (Gain)</th>
                                        <th class="px-6 py-4 font-semibold">Status Katalog</th>
                                        <th class="px-6 py-4 font-semibold text-center">Tindakan Lapangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="t in filteredPenggemukan" :key="t.id" class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <div class="font-mono font-bold text-slate-800">{{ t.rfid_tag }}</div>
                                            <div class="text-xs text-slate-500">{{ t.ras }}</div>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-xs text-slate-700">
                                            {{ t.kandang }}
                                        </td>
                                        <td class="px-6 py-4 font-mono font-black text-slate-800">
                                            {{ t.berat_kg }} Kg
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold"
                                                  :class="t.selisih_kg >= 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'">
                                                {{ t.selisih_kg > 0 ? '+' : '' }}{{ t.selisih_kg }} Kg
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="t.program === 'Siap Jual'" class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Ready di Katalog Jual
                                            </span>
                                            <span v-else-if="t.berat_kg >= 30" class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                                Siap Rilis Panen (>= 30 Kg)
                                            </span>
                                            <span v-else class="bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-lg text-xs font-medium">
                                                Tahap Pembesaran
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="bukaModalTimbang(t)" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold transition border border-slate-200">
                                                    Timbang
                                                </button>
                                                <button @click="bukaModalSiapJual(t)" 
                                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-xs"
                                                        :class="t.program === 'Siap Jual' ? 'bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100' : 'bg-emerald-600 hover:bg-emerald-700 text-white'">
                                                    {{ t.program === 'Siap Jual' ? 'Update Katalog' : 'Rilis Siap Jual' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredPenggemukan.length === 0">
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400 text-xs">Belum ada ternak di program penggemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RIWAYAT PENIMBANGAN ABADI -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <div class="mb-4">
                            <h4 class="font-bold text-slate-800 text-sm">Histori Penimbangan Digital Terkini (Log Abadi)</h4>
                            <p class="text-xs text-slate-400">Semua data timbangan terekam permanen untuk analisis ADG dan pertumbuhan</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs whitespace-nowrap">
                                <thead class="text-slate-400 uppercase bg-slate-50/70 border-b border-slate-100">
                                    <tr>
                                        <th class="py-2.5 px-4 font-semibold">Waktu Pencatatan</th>
                                        <th class="py-2.5 px-4 font-semibold">Tag RFID</th>
                                        <th class="py-2.5 px-4 font-semibold">Hasil Timbang</th>
                                        <th class="py-2.5 px-4 font-semibold">Kenaikan (Gain)</th>
                                        <th class="py-2.5 px-4 font-semibold">Sumber Input</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="r in riwayat_timbang" :key="r.id" class="hover:bg-slate-50/60">
                                        <td class="py-2.5 px-4 text-slate-500 font-mono">{{ r.waktu }}</td>
                                        <td class="py-2.5 px-4 font-mono font-bold text-slate-800">{{ r.rfid_tag }}</td>
                                        <td class="py-2.5 px-4 font-black text-slate-800 font-mono">{{ r.berat_kg }} Kg</td>
                                        <td class="py-2.5 px-4 font-bold" :class="r.selisih_kg >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ r.selisih_kg > 0 ? '+' : '' }}{{ r.selisih_kg }} Kg
                                        </td>
                                        <td class="py-2.5 px-4 text-slate-500">{{ r.sumber_alat }}</td>
                                    </tr>
                                    <tr v-if="riwayat_timbang.length === 0">
                                        <td colspan="5" class="py-6 text-center text-slate-400">Belum ada riwayat timbangan terekam.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- MODAL USG -->
                <div v-if="isModalUsgOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-pink-50">
                            <div>
                                <h3 class="font-bold text-pink-900 text-base">Verifikasi Pemeriksaan USG</h3>
                                <p class="text-xs text-pink-700">Simpan status kebuntingan indukan ternak</p>
                            </div>
                            <button @click="isModalUsgOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitUsg" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihUsg?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihUsg?.ras }} (Kandang saat ini: {{ ternakTerpilihUsg?.kandang }})</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Hasil Deteksi Kebuntingan</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="formUsg.status_kehamilan = 'Hamil'"
                                            class="py-2.5 font-bold rounded-xl border transition"
                                            :class="cekIsBunting(formUsg.status_kehamilan) ? 'bg-sky-50 border-sky-400 text-sky-700 ring-2 ring-sky-200' : 'border-slate-200 text-slate-500'">
                                        Positif Hamil (Bunting)
                                    </button>
                                    <button type="button" @click="formUsg.status_kehamilan = 'Non-Bunting'"
                                            class="py-2.5 font-bold rounded-xl border transition"
                                            :class="!cekIsBunting(formUsg.status_kehamilan) ? 'bg-slate-100 border-slate-400 text-slate-700 ring-2 ring-slate-200' : 'border-slate-200 text-slate-500'">
                                        Negatif (Non-Bunting)
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Catatan Medis USG</label>
                                <textarea v-model="formUsg.catatan_usg" rows="2" placeholder="Contoh: Deteksi kantung janin usia 45 hari, kondisi normal..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-pink-200 outline-none"></textarea>
                            </div>

                            <div v-if="cekIsBunting(formUsg.status_kehamilan)" class="flex items-center gap-2 pt-1">
                                <input id="pindah_b1" type="checkbox" v-model="formUsg.pindah_kandang" class="rounded text-emerald-600 focus:ring-emerald-200">
                                <label for="pindah_b1" class="text-xs font-semibold text-slate-700">Pindahkan otomatis ke Kandang Bunting (Kandang B1)</label>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalUsgOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl font-semibold">Batal</button>
                                <button type="submit" :disabled="formUsg.processing" class="px-5 py-2 text-white bg-pink-600 hover:bg-pink-700 font-bold rounded-xl transition shadow-sm">
                                    Simpan Hasil USG
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL TIMBANG -->
                <div v-if="isModalTimbangOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-indigo-50">
                            <div>
                                <h3 class="font-bold text-indigo-900 text-base">Input Timbangan Digital</h3>
                                <p class="text-xs text-indigo-700">Pencatatan bobot abadi dan evaluasi gain</p>
                            </div>
                            <button @click="isModalTimbangOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitTimbang" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                                <div>
                                    <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihTimbang?.rfid_tag }}</div>
                                    <div class="text-slate-500">{{ ternakTerpilihTimbang?.ras }} (Bobot lama: {{ ternakTerpilihTimbang?.berat_kg }} Kg)</div>
                                </div>
                                <span class="text-xs font-bold text-slate-700 bg-white border border-slate-200 px-2 py-0.5 rounded">
                                    {{ ternakTerpilihTimbang?.kandang }}
                                </span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Hasil Bobot Baru (Kg)</label>
                                <input v-model.number="formTimbang.berat_kg" type="number" step="0.01" min="1" required placeholder="Contoh: 34.50"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm font-mono font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Sumber Perangkat Input</label>
                                <select v-model="formTimbang.sumber_alat" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-200 outline-none">
                                    <option value="Timbangan Digital Lapangan">Timbangan Digital Lapangan (Load Cell)</option>
                                    <option value="Timbangan Manual Verifikasi">Timbangan Manual Verifikasi</option>
                                    <option value="Gerbang Timbang Sensor Otomatis">Gerbang Timbang Sensor Otomatis</option>
                                </select>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalTimbangOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl transition font-semibold">Batal</button>
                                <button type="submit" :disabled="formTimbang.processing" class="px-5 py-2 text-white bg-indigo-600 hover:bg-indigo-700 font-bold rounded-xl transition shadow-sm">
                                    Simpan & Catat Riwayat
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL SIAP JUAL -->
                <div v-if="isModalSiapJualOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-emerald-50">
                            <div>
                                <h3 class="font-bold text-emerald-900 text-base">Rilis ke Katalog Siap Jual</h3>
                                <p class="text-xs text-emerald-700">Ternak ini akan langsung muncul di Proyeksi Panen & Kasir Owner</p>
                            </div>
                            <button @click="isModalSiapJualOpen = false" class="text-slate-400 hover:text-rose-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitSiapJual" class="p-6 space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="font-mono font-bold text-slate-800">{{ ternakTerpilihJual?.rfid_tag }}</div>
                                <div class="text-slate-500">{{ ternakTerpilihJual?.ras }} (Kandang asal: {{ ternakTerpilihJual?.kandang }})</div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Verifikasi Bobot Final (Kg)</label>
                                <input v-model.number="formSiapJual.berat_kg" type="number" step="0.01" min="1" required
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-200 outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Grade / Kategori Penjualan</label>
                                <select v-model="formSiapJual.grade_katalog" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Qurban Super">Qurban Super (Bobot Jumbo, Fisik Sempurna)</option>
                                    <option value="Paket Aqiqah Prima">Paket Aqiqah Prima (Siap Potong Sehat)</option>
                                    <option value="Pedaging / Jagal">Pedaging / Jagal (Timbang Hidup)</option>
                                    <option value="Bibit Pejantan Unggul">Bibit Pejantan Unggul</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Pindahkan ke Kandang Display / Siap Lepas</label>
                                <select v-model="formSiapJual.kandang" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none">
                                    <option value="Kandang B2">Kandang B2 (Penggemukan / Siap Jual)</option>
                                    <option value="Kandang Display Kasir">Kandang Display Kasir Lapangan</option>
                                    <option v-for="k in kandangs" :key="k.id" :value="k.kode_kandang">
                                        {{ k.kode_kandang }} - {{ k.nama_kandang }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 uppercase mb-1">Catatan Kualitas & Fisik Ternak</label>
                                <textarea v-model="formSiapJual.catatan" rows="2" placeholder="Catatan kondisi fisik untuk pembeli/owner..."
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-emerald-200 outline-none"></textarea>
                            </div>

                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" @click="isModalSiapJualOpen = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl transition font-semibold">Batal</button>
                                <button type="submit" :disabled="formSiapJual.processing" class="px-5 py-2 text-white bg-emerald-600 hover:bg-emerald-700 font-bold rounded-xl transition shadow-sm">
                                    Rilis ke Katalog Siap Jual
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>