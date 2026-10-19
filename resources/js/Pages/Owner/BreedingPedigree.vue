<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    ternaks: { type: Array, default: () => [] },
    stats: { 
        type: Object, 
        default: () => ({ total_indukan: 0, total_bunting: 0, total_anakan: 0, pejantan_aktif: 0 }) 
    }
});

const user = computed(() => usePage().props.auth.user);
const isSidebarOpen = ref(false);
const isDropdownOpen = ref(false);

const selectedTernak = ref(props.ternaks.length > 0 ? props.ternaks[0] : null);

const selectTernak = (t) => {
    selectedTernak.value = t;
};

watch(() => props.ternaks, (newTernaks) => {
    if (!newTernaks || newTernaks.length === 0) {
        selectedTernak.value = null;
        return;
    }
    if (!selectedTernak.value) {
        selectedTernak.value = newTernaks[0];
        return;
    }
    const found = newTernaks.find(t => t.rfid_tag === selectedTernak.value.rfid_tag);
    selectedTernak.value = found || newTernaks[0];
}, { deep: true });

let pollingTimer = null;
onMounted(() => {
    pollingTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['ternaks', 'stats']
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer);
});

const searchQuery = ref('');
const filterStatus = ref('All');

const filteredTernaks = computed(() => {
    return props.ternaks.filter(t => {
        const matchSearch = t.rfid_tag.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.ras.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.bapak.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            t.mamak.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const isBunting = t.status_kehamilan && t.status_kehamilan.toUpperCase().includes('HAMIL');
        const matchStatus = filterStatus.value === 'All' ? true : 
                            filterStatus.value === 'Hamil' ? isBunting : 
                            !isBunting;
                            
        return matchSearch && matchStatus;
    });
});
</script>

<template>
    <Head title="Silsilah & Breeding - Owner" />

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
                <Link :href="route('owner.breeding')" class="flex items-center px-3.5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-semibold transition border border-emerald-100/50">
                    <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
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
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">Registri Silsilah & Pemuliaan Ternak</h2>
                        <p class="text-xs text-slate-400">Pemantauan garis keturunan, reproduksi indukan, dan regenerasi bibit</p>
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
                        <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 transition">Profil Akun</Link>
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition font-medium">Logout Keluar</Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-20 space-y-6">
                
                <!-- ROW 1: KARTU METRIK REPRODUKSI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <div class="bg-gradient-to-br from-pink-50/70 via-white to-white rounded-2xl p-5 border border-pink-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-pink-700 uppercase tracking-wider">Indukan Produktif</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_indukan }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-pink-600 font-semibold mt-0.5">Populasi Betina Aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-sky-50/70 via-white to-white rounded-2xl p-5 border border-sky-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-sky-700 uppercase tracking-wider">Status Bunting</span>
                            <h3 class="text-2xl font-black text-sky-700 mt-1">{{ stats.total_bunting }} <span class="text-xs font-normal text-slate-400">Ekor</span></h3>
                            <p class="text-[11px] text-sky-600 font-semibold mt-0.5">Potensi Kelahiran Baru</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50/70 via-white to-white rounded-2xl p-5 border border-emerald-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Total Generasi (F1)</span>
                            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats.total_anakan }} <span class="text-xs font-normal text-slate-400">Anakan</span></h3>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Hasil Perkawinan Kandang</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50/70 via-white to-white rounded-2xl p-5 border border-indigo-200/60 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Pejantan (Sire) Terdata</span>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ stats.pejantan_aktif }} <span class="text-xs font-normal text-slate-400">Sire</span></h3>
                            <p class="text-[11px] text-indigo-500 font-semibold mt-0.5">Garis Trah Pejantan</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                    </div>

                </div>

                <!-- ROW 2: POHON SILSILAH VISUAL -->
                <div v-if="selectedTernak" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_4px_20px_0_rgba(67,89,113,0.06)] relative overflow-hidden">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                    Diagram Silsilah 3 Generasi
                                </span>
                                <span class="text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">
                                    Ras: {{ selectedTernak.ras }}
                                </span>
                            </div>
                            <h3 class="text-2xl font-black text-slate-800 font-mono tracking-tight mt-2 flex items-center gap-2">
                                {{ selectedTernak.rfid_tag }}
                                <span class="text-xs font-sans font-semibold text-slate-400">({{ selectedTernak.umur }})</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Silsilah keturunan hasil perkawinan terdata di peternakan.</p>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100 self-start sm:self-auto">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block leading-tight">Lokasi Kandang</span>
                                <span class="text-xs font-bold text-slate-700">{{ selectedTernak.kandang }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-stretch mt-6">
                        
                        <!-- GENERASI 1: TETUA -->
                        <div class="flex flex-col justify-between gap-3.5">
                            <div class="bg-gradient-to-br from-sky-50 via-sky-50/40 to-white p-4 rounded-xl border border-sky-200 shadow-sm">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-sky-700">
                                        Pejantan (Sire / Bapak)
                                    </span>
                                    <span class="text-[11px] bg-sky-200/60 text-sky-800 px-2 py-0.5 rounded-md font-semibold">Tetua Jantan</span>
                                </div>
                                <div class="text-base font-bold font-mono text-sky-900 mt-1">{{ selectedTernak.bapak }}</div>
                                <p class="text-[11px] text-sky-600 mt-0.5">Garis Trah Pejantan</p>
                            </div>

                            <div class="bg-gradient-to-br from-pink-50 via-pink-50/40 to-white p-4 rounded-xl border border-pink-200 shadow-sm">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-pink-700">
                                        Indukan (Dam / Mamak)
                                    </span>
                                    <span class="text-[11px] bg-pink-200/60 text-pink-800 px-2 py-0.5 rounded-md font-semibold">Tetua Betina</span>
                                </div>
                                <div class="text-base font-bold font-mono text-pink-900 mt-1">{{ selectedTernak.mamak }}</div>
                                <p class="text-[11px] text-pink-600 mt-0.5">Garis Trah Indukan</p>
                            </div>
                        </div>

                        <!-- GENERASI 2: TERNAK FOKUS -->
                        <div class="bg-gradient-to-br from-emerald-50/90 via-teal-50/40 to-white border-2 border-emerald-400/80 rounded-2xl p-5 text-center shadow-sm flex flex-col justify-center items-center relative">
                            <span class="inline-block bg-emerald-600 text-white font-bold text-[10px] tracking-wider uppercase px-3 py-1 rounded-full shadow-xs mb-2">
                                Ternak Terpilih
                            </span>
                            <div class="text-xl font-black font-mono text-slate-800 tracking-tight mt-1">{{ selectedTernak.rfid_tag }}</div>
                            <div class="text-xs font-semibold text-emerald-800 mt-1 bg-emerald-100/70 px-3 py-0.5 rounded-full">
                                {{ selectedTernak.ras }} • {{ selectedTernak.jenis_kelamin }} ({{ selectedTernak.berat_kg }} Kg)
                            </div>
                            
                            <!-- BADGE STATUS BUNTING TERPILIH -->
                            <div class="mt-4 pt-3 border-t border-emerald-200/60 w-full flex justify-center">
                                <span v-if="selectedTernak.status_kehamilan && selectedTernak.status_kehamilan.toUpperCase().includes('HAMIL')" class="bg-sky-50 text-sky-700 border border-sky-300 px-3 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                    Sedang Bunting
                                </span>
                                <span v-else class="bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1 rounded-lg text-xs font-medium">
                                    Non-Bunting
                                </span>
                            </div>
                        </div>

                        <!-- GENERASI 3: ANAKAN (F1) -->
                        <div class="bg-gradient-to-br from-amber-50 via-amber-50/40 to-white border border-amber-200 rounded-xl p-4 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-2 pb-2 border-b border-amber-200/60">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-amber-800">
                                        Keturunan (Generasi F1)
                                    </span>
                                    <span class="text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">
                                        {{ selectedTernak.jumlah_anak }} Ekor
                                    </span>
                                </div>
                                <p class="text-[11px] text-amber-700 mb-3">Daftar anakan langsung yang tercatat:</p>
                                
                                <div v-if="selectedTernak.daftar_anak && selectedTernak.daftar_anak.length > 0" class="flex flex-wrap gap-2">
                                    <span v-for="(anak, idx) in selectedTernak.daftar_anak" :key="idx" 
                                          class="bg-white border border-amber-300 text-amber-900 px-3 py-1.5 rounded-lg text-xs font-mono font-semibold shadow-xs">
                                        {{ anak.trim() }}
                                    </span>
                                </div>
                                <div v-else class="text-xs text-slate-400 italic py-4 text-center">
                                    Belum ada anakan tercatat.
                                </div>
                            </div>
                            <div class="text-[10px] text-amber-700/80 font-medium mt-3 pt-2 border-t border-amber-200/40">
                                Regenerasi bibit kandang
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ROW 3: TABEL REGISTRI PEMULIAAN -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_6px_0_rgba(67,89,113,0.05)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h4 class="font-bold text-slate-800 text-base">Buku Registri Pemuliaan Ternak</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Database silsilah, umur reproduksi, dan trah keturunan</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                            <input v-model="searchQuery" type="text" placeholder="Cari RFID / Ras / Indukan..." 
                                   class="px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none w-full md:w-56">
                            <select v-model="filterStatus" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-200 outline-none">
                                <option value="All">Semua Status</option>
                                <option value="Hamil">Bunting Saja</option>
                                <option value="Non-Hamil">Non-Bunting</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-slate-400 text-xs uppercase bg-slate-50/70 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Ternak & Umur</th>
                                    <th class="px-6 py-4 font-semibold">Pejantan (Bapak)</th>
                                    <th class="px-6 py-4 font-semibold">Indukan (Mamak)</th>
                                    <th class="px-6 py-4 font-semibold">Anakan Terdaftar</th>
                                    <th class="px-6 py-4 font-semibold">Status Reproduksi</th>
                                    <th class="px-6 py-4 font-semibold text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="t in filteredTernaks" :key="t.id" 
                                    :class="selectedTernak?.id === t.id ? 'bg-emerald-50/50 border-l-4 border-emerald-500' : 'hover:bg-slate-50/60'"
                                    class="transition-colors cursor-pointer"
                                    @click="selectTernak(t)">
                                    
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 font-mono">{{ t.rfid_tag }}</div>
                                        <div class="text-slate-500 text-xs mt-0.5 font-medium">
                                            {{ t.ras }} • {{ t.jenis_kelamin }} ({{ t.umur }})
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-mono text-xs">
                                        <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 font-semibold">
                                            {{ t.bapak }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 font-mono text-xs">
                                        <span class="px-2.5 py-1 rounded-lg bg-pink-50 text-pink-700 border border-pink-200 font-semibold">
                                            {{ t.mamak }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-amber-800 text-xs bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                                {{ t.jumlah_anak }} Ekor
                                            </span>
                                            <span v-if="t.daftar_anak && t.daftar_anak.length > 0" class="text-[11px] text-slate-400 font-mono truncate max-w-[140px]" :title="t.daftar_anak.join(', ')">
                                                ({{ t.daftar_anak.join(', ') }})
                                            </span>
                                        </div>
                                    </td>

                                    <!-- STATUS REPRODUKSI BUNTING FLEKSIBEL -->
                                    <td class="px-6 py-4">
                                        <span v-if="t.status_kehamilan && t.status_kehamilan.toUpperCase().includes('HAMIL')" class="bg-sky-50 text-sky-700 border border-sky-200 px-2.5 py-1 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            Bunting
                                        </span>
                                        <span v-else class="bg-slate-50 text-slate-500 border border-slate-200 px-2.5 py-1 rounded-lg text-xs font-medium">
                                            Non-Bunting
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <button @click.stop="selectTernak(t)" 
                                                class="px-3.5 py-1.5 text-xs font-semibold rounded-xl transition shadow-xs"
                                                :class="selectedTernak?.id === t.id ? 'bg-emerald-600 text-white shadow-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700'">
                                            {{ selectedTernak?.id === t.id ? 'Aktif' : 'Lihat Silsilah' }}
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="filteredTernaks.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400 text-xs">
                                        Data silsilah tidak ditemukan.
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