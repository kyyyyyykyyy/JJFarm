<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import * as THREE from 'three';
import gsap from 'gsap';

defineProps({
    canLogin: { type: Boolean, default: true },
});

const canvasContainer = ref(null);
const heroTitle = ref(null);
const heroDesc = ref(null);
const heroBtn = ref(null);
const heroStats = ref(null);

let scene, camera, renderer, animationFrameId;
let instancedGrass, groundMesh, grassMaterial;
let cloudGroup, sheepFlock = [];
let clock = new THREE.Clock();

let mouseX = 0, mouseY = 0;
let targetX = 0, targetY = 0;

const onMouseMove = (e) => {
    targetX = (e.clientX / window.innerWidth - 0.5) * 2;
    targetY = (e.clientY / window.innerHeight - 0.5) * 2;
};

const onResize = () => {
    if (!camera || !renderer) return;
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
};

// 1. ELEVASI PERBUKITAN ALAMI
const getHillHeight = (x, z) => {
    return Math.sin(x * 0.07) * Math.cos(z * 0.08) * 1.6 +
           Math.sin(x * 0.035 + z * 0.045) * 2.0 -
           Math.cos(x * 0.12) * 0.4 - 0.5;
};

// 2. TEKSTUR AWAN ATMOSFERIK LEMBUT
const createSoftCloudTexture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const ctx = canvas.getContext('2d');

    const grad = ctx.createRadialGradient(128, 128, 0, 128, 128, 128);
    grad.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
    grad.addColorStop(0.35, 'rgba(240, 248, 255, 0.7)');
    grad.addColorStop(0.65, 'rgba(220, 240, 255, 0.25)');
    grad.addColorStop(1, 'rgba(200, 230, 255, 0)');

    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.arc(128, 128, 128, 0, Math.PI * 2);
    ctx.fill();

    return new THREE.CanvasTexture(canvas);
};

// 3. PEMBUATAN DOMBA 3D DENGAN PANGGUL KAKI YANG DAPAT MELANGKAH
const buildPastureSheep = (scale = 0.6) => {
    const group = new THREE.Group();

    const woolMat = new THREE.MeshStandardMaterial({
        color: 0xf5f3ec,
        roughness: 0.95,
        metalness: 0.02
    });

    const skinMat = new THREE.MeshStandardMaterial({
        color: 0x1a1512,
        roughness: 0.8
    });

    // PANGGUL & KAKI (Pivot di sambungan atas panggul)
    const legHeight = 0.54 * scale;
    const legGeo = new THREE.CylinderGeometry(0.045 * scale, 0.035 * scale, legHeight, 8);
    legGeo.translate(0, -legHeight / 2, 0); // Pindahkan pivot rotasi ke pangkal paha atas

    const legFL = new THREE.Mesh(legGeo, skinMat);
    legFL.position.set(0.42 * scale, legHeight, 0.24 * scale);
    group.add(legFL);

    const legFR = new THREE.Mesh(legGeo, skinMat);
    legFR.position.set(0.42 * scale, legHeight, -0.24 * scale);
    group.add(legFR);

    const legBL = new THREE.Mesh(legGeo, skinMat);
    legBL.position.set(-0.42 * scale, legHeight, 0.24 * scale);
    group.add(legBL);

    const legBR = new THREE.Mesh(legGeo, skinMat);
    legBR.position.set(-0.42 * scale, legHeight, -0.24 * scale);
    group.add(legBR);

    // Badan wol
    const bodyGeo = new THREE.SphereGeometry(0.62 * scale, 14, 14);
    bodyGeo.scale(1.45, 0.95, 0.95);
    const body = new THREE.Mesh(bodyGeo, woolMat);
    body.position.y = legHeight + 0.45 * scale;
    group.add(body);

    // Kepala domba
    const headGroup = new THREE.Group();
    headGroup.position.set(0.72 * scale, legHeight + 0.52 * scale, 0);

    const headGeo = new THREE.SphereGeometry(0.26 * scale, 12, 12);
    headGeo.scale(1.25, 0.9, 0.8);
    const head = new THREE.Mesh(headGeo, skinMat);
    head.rotation.z = -0.25;
    headGroup.add(head);

    // Tanduk melengkung
    const hornMat = new THREE.MeshStandardMaterial({ color: 0x766654, roughness: 0.65 });
    const hornGeo = new THREE.TorusGeometry(0.16 * scale, 0.035 * scale, 6, 12, Math.PI * 0.8);

    const hornL = new THREE.Mesh(hornGeo, hornMat);
    hornL.position.set(-0.02 * scale, 0.12 * scale, 0.2 * scale);
    hornL.rotation.set(0.3, 0.35, 0.15);
    headGroup.add(hornL);

    const hornR = new THREE.Mesh(hornGeo, hornMat);
    hornR.position.set(-0.02 * scale, 0.12 * scale, -0.2 * scale);
    hornR.rotation.set(-0.3, -0.35, 0.15);
    headGroup.add(hornR);

    // Telinga
    const earGeo = new THREE.ConeGeometry(0.06 * scale, 0.26 * scale, 6);
    earGeo.rotateZ(Math.PI / 2);
    const earL = new THREE.Mesh(earGeo, skinMat);
    earL.position.set(0, 0.05 * scale, 0.24 * scale);
    earL.rotation.y = 0.3;
    headGroup.add(earL);

    const earR = new THREE.Mesh(earGeo, skinMat);
    earR.position.set(0, 0.05 * scale, -0.24 * scale);
    earR.rotation.y = -0.3;
    headGroup.add(earR);

    group.add(headGroup);

    // Ekor
    const tailGeo = new THREE.SphereGeometry(0.12 * scale, 8, 8);
    tailGeo.scale(0.8, 1.4, 0.8);
    const tail = new THREE.Mesh(tailGeo, woolMat);
    tail.position.set(-0.84 * scale, legHeight + 0.46 * scale, 0);
    group.add(tail);

    return { 
        root: group, 
        head: headGroup, 
        tail, 
        legs: { fl: legFL, fr: legFR, bl: legBL, br: legBR } 
    };
};

onMounted(() => {
    // 1. SCENE & CAMERA
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x4b93dc);
    scene.fog = new THREE.FogExp2(0x7fb6e8, 0.012);

    camera = new THREE.PerspectiveCamera(46, window.innerWidth / window.innerHeight, 0.1, 160);
    camera.position.set(0, 3.4, 13.5);
    camera.lookAt(0, 1.6, -4);

    // 2. RENDERER
    renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    if (canvasContainer.value) {
        canvasContainer.value.appendChild(renderer.domElement);
    }

    // 3. PENCAHAYAAN
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.95);
    scene.add(ambientLight);

    const sunLight = new THREE.DirectionalLight(0xfffae8, 2.5);
    sunLight.position.set(25, 45, -15);
    scene.add(sunLight);

    const hemiLight = new THREE.HemisphereLight(0x4b93dc, 0x163b19, 0.9);
    scene.add(hemiLight);

    // 4. AWAN ATMOSFERIK
    cloudGroup = new THREE.Group();
    const cloudTexture = createSoftCloudTexture();
    const cloudMat = new THREE.MeshBasicMaterial({
        map: cloudTexture,
        transparent: true,
        opacity: 0.78,
        depthWrite: false
    });

    for (let c = 0; c < 32; c++) {
        const size = 8.0 + Math.random() * 10.0;
        const cGeo = new THREE.PlaneGeometry(size, size * 0.5);
        const cMesh = new THREE.Mesh(cGeo, cloudMat);
        cMesh.position.set(
            (Math.random() - 0.5) * 85,
            8.5 + Math.random() * 8.5,
            -18 - Math.random() * 25
        );
        cMesh.userData = { speed: 0.006 + Math.random() * 0.01 };
        cloudGroup.add(cMesh);
    }
    scene.add(cloudGroup);

    // 5. PERMUKAAN TANAH
    const groundGeo = new THREE.PlaneGeometry(85, 75, 90, 90);
    groundGeo.rotateX(-Math.PI / 2);

    const groundPos = groundGeo.attributes.position;
    for (let i = 0; i < groundPos.count; i++) {
        const x = groundPos.getX(i);
        const z = groundPos.getZ(i);
        groundPos.setY(i, getHillHeight(x, z));
    }
    groundGeo.computeVertexNormals();

    const groundMat = new THREE.MeshStandardMaterial({
        color: 0x163e19,
        roughness: 0.95,
        metalness: 0.02
    });
    groundMesh = new THREE.Mesh(groundGeo, groundMat);
    groundMesh.position.set(0, 0, 0);
    scene.add(groundMesh);

    // 6. RUMPUN RUMPUT PADAT (CROSS-BLADE)
    const bladeW = 0.055;
    const bladeH = 0.46;
    const clumpGeo = new THREE.BufferGeometry();

    const verts = new Float32Array([
        -bladeW, 0, 0,
         bladeW, 0, 0,
        -bladeW * 0.6, bladeH * 0.5, 0.03,
         bladeW * 0.6, bladeH * 0.5, 0.03,
         0, bladeH, 0.06,

        0, 0, -bladeW,
        0, 0,  bladeW,
        0.03, bladeH * 0.5, -bladeW * 0.6,
        0.03, bladeH * 0.5,  bladeW * 0.6,
        0.06, bladeH, 0
    ]);

    const indices = [
        0, 1, 2,  1, 3, 2,  2, 3, 4,
        5, 6, 7,  6, 8, 7,  7, 8, 9
    ];

    clumpGeo.setIndex(indices);
    clumpGeo.setAttribute('position', new THREE.BufferAttribute(verts, 3));
    clumpGeo.computeVertexNormals();

    grassMaterial = new THREE.ShaderMaterial({
        uniforms: {
            uTime: { value: 0 },
            uBaseColor: { value: new THREE.Color(0x113615) },
            uTipColor: { value: new THREE.Color(0x6bc435) },
            uSunDir: { value: new THREE.Vector3(0.5, 0.8, -0.3).normalize() }
        },
        vertexShader: `
            uniform float uTime;
            varying float vHeight;
            varying vec3 vNormal;

            void main() {
                vHeight = position.y;
                vec4 worldPos = instanceMatrix * vec4(position, 1.0);

                float wind = sin(uTime * 2.3 + worldPos.x * 0.5 + worldPos.z * 0.4) * 0.15;
                wind += cos(uTime * 1.4 + worldPos.z * 0.6) * 0.08;

                float bend = (position.y / 0.46);
                worldPos.x += wind * bend;
                worldPos.z += (wind * 0.5) * bend;

                vNormal = normalize(mat3(instanceMatrix) * normal);
                gl_Position = projectionMatrix * viewMatrix * worldPos;
            }
        `,
        fragmentShader: `
            uniform vec3 uBaseColor;
            uniform vec3 uTipColor;
            uniform vec3 uSunDir;
            varying float vHeight;
            varying vec3 vNormal;

            void main() {
                vec3 col = mix(uBaseColor, uTipColor, vHeight / 0.46);
                float diff = max(dot(vNormal, uSunDir), 0.35);
                col *= (diff + 0.52);
                gl_FragColor = vec4(col, 1.0);
            }
        `,
        side: THREE.DoubleSide
    });

    const gridCols = 240;
    const gridRows = 200;
    const totalGrass = gridCols * gridRows;

    instancedGrass = new THREE.InstancedMesh(clumpGeo, grassMaterial, totalGrass);
    instancedGrass.frustumCulled = false;
    const dummy = new THREE.Object3D();

    const xMin = -34, xMax = 34;
    const zMin = -28, zMax = 8;
    const xStep = (xMax - xMin) / gridCols;
    const zStep = (zMax - zMin) / gridRows;

    let idx = 0;
    for (let i = 0; i < gridCols; i++) {
        for (let j = 0; j < gridRows; j++) {
            const x = xMin + i * xStep + (Math.random() - 0.5) * xStep * 0.95;
            const z = zMin + j * zStep + (Math.random() - 0.5) * zStep * 0.95;
            const y = getHillHeight(x, z);

            dummy.position.set(x, y, z);
            dummy.rotation.y = Math.random() * Math.PI * 2;
            dummy.rotation.x = (Math.random() - 0.5) * 0.15;
            dummy.rotation.z = (Math.random() - 0.5) * 0.15;

            const scale = 0.75 + Math.random() * 0.45;
            dummy.scale.set(scale, scale * (0.85 + Math.random() * 0.35), scale);

            dummy.updateMatrix();
            instancedGrass.setMatrixAt(idx++, dummy.matrix);
        }
    }
    instancedGrass.instanceMatrix.needsUpdate = true;
    scene.add(instancedGrass);

    // 7. PENYEBARAN DOMBA ALAMI & PARAMETER JALAN-JALAN DI LERENG
    const sheepCoordinates = [
        // Depan Kiri & Kanan
        [-11.5, 1.5, 0.95],
        [-8.2, 0.5, 0.85],
        [9.5, 1.2, 0.92],
        [12.5, 0.2, 0.85],

        // Lereng Tengah Kiri
        [-15.5, -4.5, 0.75],
        [-12.0, -8.5, 0.68],
        [-17.0, -12.5, 0.60],
        [-6.8, -7.0, 0.70],

        // Lereng Tengah Kanan
        [14.5, -5.2, 0.75],
        [11.0, -9.0, 0.68],
        [16.5, -13.0, 0.60],
        [6.5, -7.5, 0.70],

        // Kejauhan & Horizon
        [-9.5, -17.5, 0.48],
        [-4.5, -19.5, 0.45],
        [0.5, -18.0, 0.46],
        [5.5, -19.0, 0.45],
        [10.5, -18.2, 0.48],
        [-2.0, -23.5, 0.38],
        [3.2, -24.0, 0.38]
    ];

    sheepCoordinates.forEach(([sx, sz, scale], sIdx) => {
        const sheep = buildPastureSheep(scale);
        const sy = getHillHeight(sx, sz);
        sheep.root.position.set(sx, sy + 0.02, sz);

        // Parameter pergerakan jelajah masing-masing domba
        sheep.baseX = sx;
        sheep.baseZ = sz;
        sheep.scale = scale;
        sheep.walkSpeed = 0.25 + (sIdx % 4) * 0.08;
        sheep.radiusX = 1.0 + (sIdx % 3) * 0.5;
        sheep.radiusZ = 0.8 + (sIdx % 4) * 0.4;
        sheep.phase = sIdx * 1.45;

        scene.add(sheep.root);
        sheepFlock.push(sheep);
    });

    window.addEventListener('resize', onResize);
    window.addEventListener('mousemove', onMouseMove);

    // 8. ANIMASI GSAP SAAT DIBUKA
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
    tl.fromTo(heroTitle.value, { opacity: 0, y: 35, scale: 0.96 }, { opacity: 1, y: 0, scale: 1, duration: 1.1 })
      .fromTo(heroDesc.value, { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 0.9 }, '-=0.7')
      .fromTo(heroBtn.value, { opacity: 0, scale: 0.88 }, { opacity: 1, scale: 1, duration: 0.7 }, '-=0.5')
      .fromTo(heroStats.value, { opacity: 0, y: 15 }, { opacity: 1, y: 0, duration: 0.8 }, '-=0.4');

    // 9. ANIMASI LOOP REALTIME (TERMASUK LANGKAH KAKI & JELAJAH DOMBA)
    const animate = () => {
        animationFrameId = requestAnimationFrame(animate);
        const elapsed = clock.getElapsedTime();

        grassMaterial.uniforms.uTime.value = elapsed;

        // Gerak awan
        if (cloudGroup) {
            cloudGroup.children.forEach((c) => {
                c.position.x += c.userData.speed;
                if (c.position.x > 42) c.position.x = -42;
            });
        }

        // ANIMASI JALAN-JALAN KAWANAN DOMBA
        sheepFlock.forEach((s, sIdx) => {
            const wanderTime = elapsed * s.walkSpeed + s.phase;

            // Lintasan elips jelajah halus
            const targetX = s.baseX + Math.sin(wanderTime * 0.8) * s.radiusX;
            const targetZ = s.baseZ + Math.cos(wanderTime * 0.6) * s.radiusZ;

            // Vektor arah langkah
            const vx = Math.cos(wanderTime * 0.8) * 0.8 * s.radiusX;
            const vz = -Math.sin(wanderTime * 0.6) * 0.6 * s.radiusZ;

            s.root.position.x = targetX;
            s.root.position.z = targetZ;

            // Rekalkulasi elevasi bukit real-time + efek pantulan langkah (step bounce)
            const groundY = getHillHeight(targetX, targetZ);
            const stepBounce = Math.abs(Math.sin(elapsed * 5.5 * s.walkSpeed + s.phase)) * 0.02 * s.scale;
            s.root.position.y = groundY + 0.02 + stepBounce;

            // Belok halus menghadap arah jalan
            const targetRotY = -Math.atan2(vz, vx);
            let diff = targetRotY - s.root.rotation.y;
            diff = Math.atan2(Math.sin(diff), Math.cos(diff));
            s.root.rotation.y += diff * 0.06;

            // Ayunan kaki melangkah alami (quadruped diagonal trot)
            const walkCycle = elapsed * 6.5 * s.walkSpeed + s.phase;
            const swingAngle = Math.sin(walkCycle) * 0.38;

            s.legs.fl.rotation.z = swingAngle;
            s.legs.br.rotation.z = swingAngle;
            s.legs.fr.rotation.z = -swingAngle;
            s.legs.bl.rotation.z = -swingAngle;

            // Kepala menunduk merumput sesekali
            s.head.rotation.x = Math.sin(elapsed * 1.3 + sIdx) * 0.14 + 0.12;
            s.head.rotation.y = Math.cos(elapsed * 0.7 + sIdx) * 0.07;

            // Ekor bergoyang santai
            s.tail.rotation.y = Math.sin(elapsed * 5.0 + sIdx) * 0.28;
        });

        // Parallax kamera
        mouseX += (targetX - mouseX) * 0.035;
        mouseY += (targetY - mouseY) * 0.035;

        camera.position.x = mouseX * 1.4;
        camera.position.y = 3.4 - mouseY * 0.35;
        camera.lookAt(0, 1.6, -4);

        renderer.render(scene, camera);
    };

    animate();
});

onUnmounted(() => {
    window.removeEventListener('resize', onResize);
    window.removeEventListener('mousemove', onMouseMove);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
});
</script>

<template>
    <Head title="JJFarm - Peternakan Kambing & Domba Terpadu" />

    <div class="relative min-h-screen font-sans select-none overflow-x-hidden flex flex-col justify-between">
        
        <!-- CANVAS THREE.JS -->
        <div ref="canvasContainer" class="fixed inset-0 z-0 pointer-events-none"></div>

        <!-- HEADER ATAS -->
        <header class="relative z-10 max-w-7xl mx-auto w-full px-6 py-6 flex justify-between items-center">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-slate-950 text-white flex items-center justify-center font-black text-lg shadow-2xl border border-white/25">
                    JJ
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white block leading-tight drop-shadow-[0_2px_12px_rgba(0,0,0,0.9)]">
                        JJFARM
                    </span>
                    <span class="text-[10px] text-emerald-200 font-extrabold uppercase tracking-widest drop-shadow-[0_1px_6px_rgba(0,0,0,0.9)]">
                        Peternakan Kambing & Domba
                    </span>
                </div>
            </div>

            <div>
                <Link v-if="$page.props.auth.user" 
                      :href="route('dashboard')" 
                      class="px-5 py-2.5 rounded-xl bg-slate-950/80 hover:bg-black text-white text-xs font-black transition shadow-xl border border-white/30 backdrop-blur-md">
                    Buka Dashboard &rarr;
                </Link>
                <Link v-else 
                      :href="route('login')" 
                      class="px-5 py-2.5 rounded-xl bg-slate-950/80 hover:bg-black text-white text-xs font-black transition shadow-xl border border-white/30 backdrop-blur-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                    Masuk Portal Petugas
                </Link>
            </div>
        </header>

        <!-- KONTEN TENGAH TRANSPARAN -->
        <main class="relative z-10 max-w-5xl mx-auto w-full px-6 py-6 flex flex-col items-center justify-center text-center my-auto space-y-7">
            
            <h1 ref="heroTitle" 
                class="text-5xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.12] drop-shadow-[0_6px_36px_rgba(0,0,0,0.95)] max-w-4xl">
                Sistem Operasional Peternakan JJFarm
            </h1>

            <p ref="heroDesc" 
               class="text-base sm:text-lg lg:text-xl text-white font-extrabold max-w-2xl mx-auto leading-relaxed drop-shadow-[0_4px_18px_rgba(0,0,0,0.95)]">
                Pusat pendataan terpadu kawanan kambing dan domba. Integrasi identitas RFID, alur siklus biologis kandang, evaluasi kenaikan bobot rutin, serta administrasi kasir serah terima langsung di lapangan.
            </p>

            <div ref="heroBtn" class="pt-2">
                <Link :href="route('login')" 
                      class="inline-flex items-center gap-2.5 px-9 py-4 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white text-sm font-black tracking-wider uppercase transition shadow-2xl shadow-emerald-950/80 hover:scale-105 active:scale-95 border border-emerald-400/40">
                    Masuk Portal Petugas
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </Link>
            </div>

            <div ref="heroStats" class="grid grid-cols-1 sm:grid-cols-3 gap-8 pt-4 w-full max-w-xl mx-auto">
                <div>
                    <div class="text-3xl sm:text-4xl font-black text-white font-mono drop-shadow-[0_4px_14px_rgba(0,0,0,0.95)]">
                        100%
                    </div>
                    <div class="text-xs text-emerald-200 font-black mt-1 uppercase tracking-wider drop-shadow-[0_2px_8px_rgba(0,0,0,0.95)]">
                        Ketertelusuran RFID
                    </div>
                </div>

                <div>
                    <div class="text-3xl sm:text-4xl font-black text-white font-mono drop-shadow-[0_4px_14px_rgba(0,0,0,0.95)]">
                        6 Tahap
                    </div>
                    <div class="text-xs text-emerald-200 font-black mt-1 uppercase tracking-wider drop-shadow-[0_2px_8px_rgba(0,0,0,0.95)]">
                        Siklus Kandang SOP
                    </div>
                </div>

                <div>
                    <div class="text-3xl sm:text-4xl font-black text-white font-mono drop-shadow-[0_4px_14px_rgba(0,0,0,0.95)]">
                        Terhubung
                    </div>
                    <div class="text-xs text-emerald-200 font-black mt-1 uppercase tracking-wider drop-shadow-[0_2px_8px_rgba(0,0,0,0.95)]">
                        Kasir & Laporan Kas
                    </div>
                </div>
            </div>

        </main>

        <!-- FOOTER RESMI -->
        <footer class="relative z-10 max-w-7xl mx-auto w-full px-6 py-6 border-t border-white/25 flex flex-col sm:flex-row justify-between items-center text-xs text-white font-extrabold gap-2 drop-shadow-[0_2px_10px_rgba(0,0,0,0.95)]">
            <div>&copy; 2026 JJFarm. Pengelolaan Peternakan Kambing & Domba.</div>
            <div class="text-emerald-200">Standard Operasional Peternakan Terpadu</div>
        </footer>

    </div>
</template>