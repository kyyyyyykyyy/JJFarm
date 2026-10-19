<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import * as THREE from 'three';
import gsap from 'gsap';

defineProps({
    canResetPassword: { type: Boolean, default: true },
    status: { type: String, default: '' },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const canvasContainer = ref(null);
const loginCard = ref(null);

let scene, camera, renderer, animationFrameId;
let windmillRotor, windmillVane, grassMesh, grassMaterial;
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

// 1. GENERATOR TEKSTUR PAPAN KAYU KANDANG
const createBarnPlankTexture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 1024;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#472b1e';
    ctx.fillRect(0, 0, 1024, 1024);

    const shades = ['#361f14', '#523425', '#2c170d', '#5a3827', '#25130b'];
    for (let i = 0; i < 3500; i++) {
        ctx.strokeStyle = shades[Math.floor(Math.random() * shades.length)];
        ctx.lineWidth = 1 + Math.random() * 2.5;
        const x = Math.random() * 1024;
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x + (Math.random() - 0.5) * 12, 1024);
        ctx.stroke();
    }

    ctx.strokeStyle = '#150a05';
    ctx.lineWidth = 5;
    for (let x = 64; x < 1024; x += 64) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, 1024);
        ctx.stroke();
    }

    const tex = new THREE.CanvasTexture(canvas);
    tex.wrapS = THREE.RepeatWrapping;
    tex.wrapT = THREE.RepeatWrapping;
    return tex;
};

// 2. GENERATOR TEKSTUR ATAP SENG BERGELOMBANG
const createRoofTexture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 512;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#34383a';
    ctx.fillRect(0, 0, 512, 512);

    for (let y = 0; y < 512; y += 16) {
        const grad = ctx.createLinearGradient(0, y, 0, y + 16);
        grad.addColorStop(0, '#4a5054');
        grad.addColorStop(0.5, '#222527');
        grad.addColorStop(1, '#575e63');
        ctx.fillStyle = grad;
        ctx.fillRect(0, y, 512, 16);
    }

    const tex = new THREE.CanvasTexture(canvas);
    tex.wrapS = THREE.RepeatWrapping;
    tex.wrapT = THREE.RepeatWrapping;
    return tex;
};

// 3. GENERATOR TEKSTUR BAJA KINCIR
const createMetalTexture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 512;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#6b7278';
    ctx.fillRect(0, 0, 512, 512);

    for (let i = 0; i < 4000; i++) {
        const c = Math.floor(80 + Math.random() * 80);
        ctx.fillStyle = `rgb(${c}, ${c + 3}, ${c + 6})`;
        ctx.fillRect(Math.random() * 512, Math.random() * 512, 2, 2);
    }

    for (let i = 0; i < 800; i++) {
        ctx.fillStyle = '#63442a';
        ctx.fillRect(Math.random() * 512, Math.random() * 512, 3, 2);
    }

    const tex = new THREE.CanvasTexture(canvas);
    tex.wrapS = THREE.RepeatWrapping;
    tex.wrapT = THREE.RepeatWrapping;
    return tex;
};

// 4. BANGUNAN KANDANG (DIMENSI & POSISI DIPERBAIKI)
const buildRealisticBarn = (woodTex, roofTex) => {
    const barn = new THREE.Group();

    const barnWoodMat = new THREE.MeshStandardMaterial({
        map: woodTex,
        roughness: 0.9,
        metalness: 0.04
    });

    const roofMat = new THREE.MeshStandardMaterial({
        map: roofTex,
        roughness: 0.65,
        metalness: 0.45
    });

    const trimMat = new THREE.MeshStandardMaterial({
        color: 0x22150e,
        roughness: 0.85
    });

    const width = 7.5;
    const height = 4.2;
    const depth = 8.0;

    const wallGeo = new THREE.BoxGeometry(width, height, depth);
    wallGeo.translate(0, height / 2, 0);
    const walls = new THREE.Mesh(wallGeo, barnWoodMat);
    walls.castShadow = true;
    walls.receiveShadow = true;
    barn.add(walls);

    const roofPitchGeo = new THREE.ConeGeometry(width * 0.78, 2.5, 4);
    roofPitchGeo.rotateY(Math.PI / 4);
    roofPitchGeo.scale(1.0, 1.0, (depth / (width * 0.78)) * 1.08);
    const roof = new THREE.Mesh(roofPitchGeo, roofMat);
    roof.position.set(0, height + 1.1, 0);
    roof.castShadow = true;
    roof.receiveShadow = true;
    barn.add(roof);

    const beamMat = trimMat;
    const postGeo = new THREE.BoxGeometry(0.22, height + 0.1, 0.22);
    postGeo.translate(0, (height + 0.1) / 2, 0);

    [-width / 2, width / 2].forEach((px) => {
        [-depth / 2, depth / 2].forEach((pz) => {
            const cornerPost = new THREE.Mesh(postGeo, beamMat);
            cornerPost.position.set(px, 0, pz);
            cornerPost.castShadow = true;
            barn.add(cornerPost);
        });
    });

    const doorFrameGeo = new THREE.BoxGeometry(2.8, 2.9, 0.18);
    doorFrameGeo.translate(0, 1.45, depth / 2 + 0.05);
    const doorFrame = new THREE.Mesh(doorFrameGeo, beamMat);
    doorFrame.castShadow = true;
    barn.add(doorFrame);

    const doorLeafMat = new THREE.MeshStandardMaterial({ color: 0x331e15, roughness: 0.88 });
    const leafGeo = new THREE.BoxGeometry(1.25, 2.7, 0.08);
    leafGeo.translate(0, 1.35, depth / 2 + 0.12);

    const leafL = new THREE.Mesh(leafGeo, doorLeafMat);
    leafL.position.x = -0.65;
    leafL.castShadow = true;
    barn.add(leafL);

    const leafR = new THREE.Mesh(leafGeo, doorLeafMat);
    leafR.position.x = 0.65;
    leafR.castShadow = true;
    barn.add(leafR);

    const zBarGeo = new THREE.BoxGeometry(1.5, 0.09, 0.04);
    zBarGeo.rotateZ(0.8);
    const zBar1 = new THREE.Mesh(zBarGeo, beamMat);
    zBar1.position.set(-0.65, 1.35, depth / 2 + 0.18);
    barn.add(zBar1);

    const zBar2 = new THREE.Mesh(zBarGeo, beamMat);
    zBar2.position.set(0.65, 1.35, depth / 2 + 0.18);
    barn.add(zBar2);

    return barn;
};

// 5. KINCIR ANGIN PROPORSIONAL (TINGGI SESUAI BINGKAI KAMERA)
const buildRealisticWindmill = (steelTex) => {
    const windmill = new THREE.Group();

    const steelMat = new THREE.MeshStandardMaterial({
        map: steelTex,
        roughness: 0.6,
        metalness: 0.65
    });

    const darkSteelMat = new THREE.MeshStandardMaterial({
        color: 0x2e3236,
        roughness: 0.5,
        metalness: 0.75
    });

    // Tinggi menara diturunkan ke 6.5m agar seluruh rotor masuk frame kamera
    const towerHeight = 6.5;
    const baseW = 1.9;
    const topW = 0.65;

    const legGeo = new THREE.CylinderGeometry(0.035, 0.048, towerHeight, 8);
    const legOffsets = [
        [baseW / 2, baseW / 2, topW / 2, topW / 2],
        [-baseW / 2, baseW / 2, -topW / 2, topW / 2],
        [baseW / 2, -baseW / 2, topW / 2, -topW / 2],
        [-baseW / 2, -baseW / 2, -topW / 2, -topW / 2]
    ];

    legOffsets.forEach(([bx, bz, tx, tz]) => {
        const leg = new THREE.Mesh(legGeo, steelMat);
        leg.position.set((bx + tx) / 2, towerHeight / 2, (bz + tz) / 2);
        leg.lookAt(tx, towerHeight, tz);
        leg.rotateX(Math.PI / 2);
        leg.castShadow = true;
        windmill.add(leg);
    });

    for (let h = 1.3; h < towerHeight; h += 1.3) {
        const factor = h / towerHeight;
        const w = baseW * (1 - factor) + topW * factor;

        const hGeo = new THREE.BoxGeometry(w, 0.04, 0.04);
        const b1 = new THREE.Mesh(hGeo, steelMat);
        b1.position.set(0, h, w / 2);
        windmill.add(b1);

        const b2 = new THREE.Mesh(hGeo, steelMat);
        b2.position.set(0, h, -w / 2);
        windmill.add(b2);

        const sGeo = new THREE.BoxGeometry(0.04, 0.04, w);
        const b3 = new THREE.Mesh(sGeo, steelMat);
        b3.position.set(w / 2, h, 0);
        windmill.add(b3);

        const b4 = new THREE.Mesh(sGeo, steelMat);
        b4.position.set(-w / 2, h, 0);
        windmill.add(b4);
    }

    const headPlatformGeo = new THREE.BoxGeometry(0.95, 0.28, 1.1);
    const headPlatform = new THREE.Mesh(headPlatformGeo, darkSteelMat);
    headPlatform.position.set(0, towerHeight + 0.14, 0);
    headPlatform.castShadow = true;
    windmill.add(headPlatform);

    windmillVane = new THREE.Group();
    windmillVane.position.set(0, towerHeight + 0.45, 0);

    const tailPipeGeo = new THREE.CylinderGeometry(0.03, 0.03, 2.4, 8);
    tailPipeGeo.rotateX(Math.PI / 2);
    const tailPipe = new THREE.Mesh(tailPipeGeo, darkSteelMat);
    tailPipe.position.set(0, 0, -1.2);
    windmillVane.add(tailPipe);

    const rudderGeo = new THREE.BoxGeometry(0.02, 0.9, 1.3);
    rudderGeo.translate(0, 0, -2.1);
    const rudder = new THREE.Mesh(rudderGeo, steelMat);
    rudder.castShadow = true;
    windmillVane.add(rudder);
    windmill.add(windmillVane);

    windmillRotor = new THREE.Group();
    windmillRotor.position.set(0, towerHeight + 0.45, 0.65);

    const hubGeo = new THREE.CylinderGeometry(0.24, 0.24, 0.22, 16);
    hubGeo.rotateX(Math.PI / 2);
    const hub = new THREE.Mesh(hubGeo, darkSteelMat);
    windmillRotor.add(hub);

    const ringGeo = new THREE.TorusGeometry(1.4, 0.016, 8, 24);
    const ring = new THREE.Mesh(ringGeo, steelMat);
    windmillRotor.add(ring);

    const bladeCount = 12;
    const bladeLength = 1.7;
    const bladeWidth = 0.26;

    for (let i = 0; i < bladeCount; i++) {
        const ang = (i * Math.PI * 2) / bladeCount;
        const bGroup = new THREE.Group();
        bGroup.rotation.z = ang;

        const rodGeo = new THREE.CylinderGeometry(0.015, 0.015, bladeLength, 6);
        rodGeo.translate(0, bladeLength / 2, 0);
        const rod = new THREE.Mesh(rodGeo, darkSteelMat);
        bGroup.add(rod);

        const finGeo = new THREE.BoxGeometry(bladeWidth, bladeLength * 0.72, 0.012);
        finGeo.translate(bladeWidth / 2, bladeLength * 0.6, 0);
        const fin = new THREE.Mesh(finGeo, steelMat);
        fin.rotation.y = 0.35;
        fin.castShadow = true;
        bGroup.add(fin);

        windmillRotor.add(bGroup);
    }

    windmill.add(windmillRotor);
    return windmill;
};

// 6. PAGAR PETERNAKAN DENGAN GAP PINTU MASUK (TIDAK MENEMBUS RUMAH)
const createFarmFence = () => {
    const group = new THREE.Group();
    const woodMat = new THREE.MeshStandardMaterial({ color: 0x422d1f, roughness: 0.9 });

    const postGeo = new THREE.CylinderGeometry(0.06, 0.07, 1.2, 8);
    const postY = 0.6;

    // Sisi Pagar Kiri (Di depan kiri, tidak menembus dinding kandang)
    const leftRailGeo = new THREE.CylinderGeometry(0.035, 0.035, 7.5, 6);
    leftRailGeo.rotateZ(Math.PI / 2);

    const leftR1 = new THREE.Mesh(leftRailGeo, woodMat);
    leftR1.position.set(-8.5, 0.85, 0);
    leftR1.castShadow = true;
    group.add(leftR1);

    const leftR2 = new THREE.Mesh(leftRailGeo, woodMat);
    leftR2.position.set(-8.5, 0.45, 0);
    leftR2.castShadow = true;
    group.add(leftR2);

    for (let x = -12; x <= -5; x += 2.3) {
        const post = new THREE.Mesh(postGeo, woodMat);
        post.position.set(x, postY, 0);
        post.castShadow = true;
        group.add(post);
    }

    // Sisi Pagar Kanan (Membentang melewati kincir)
    const rightRailGeo = new THREE.CylinderGeometry(0.035, 0.035, 11.5, 6);
    rightRailGeo.rotateZ(Math.PI / 2);

    const rightR1 = new THREE.Mesh(rightRailGeo, woodMat);
    rightR1.position.set(7.5, 0.85, 0);
    rightR1.castShadow = true;
    group.add(rightR1);

    const rightR2 = new THREE.Mesh(rightRailGeo, woodMat);
    rightR2.position.set(7.5, 0.45, 0);
    rightR2.castShadow = true;
    group.add(rightR2);

    for (let x = 2; x <= 13; x += 2.2) {
        const post = new THREE.Mesh(postGeo, woodMat);
        post.position.set(x, postY, 0);
        post.castShadow = true;
        group.add(post);
    }

    return group;
};

onMounted(() => {
    // 1. SETUP SCENE & SUDUT KAMERA MENGHADAP KINCIR UTUH
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x3e525d);
    scene.fog = new THREE.FogExp2(0x3e525d, 0.015);

    camera = new THREE.PerspectiveCamera(46, window.innerWidth / window.innerHeight, 0.1, 140);
    // Kamera sedikit lebih mundur dan pas untuk melihat kincir dari bawah sampai ujung baling-baling
    camera.position.set(0, 3.6, 17.5);
    camera.lookAt(0, 3.2, 0);

    // 2. RENDERER
    renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    if (canvasContainer.value) {
        canvasContainer.value.appendChild(renderer.domElement);
    }

    // 3. PENCAHAYAAN MATAHARI ALAMI
    const ambientLight = new THREE.AmbientLight(0xfff5ea, 0.95);
    scene.add(ambientLight);

    const sunLight = new THREE.DirectionalLight(0xffe6c2, 2.8);
    sunLight.position.set(22, 35, 16);
    sunLight.castShadow = true;
    sunLight.shadow.mapSize.width = 2048;
    sunLight.shadow.mapSize.height = 2048;
    sunLight.shadow.bias = -0.0004;
    scene.add(sunLight);

    const skyLight = new THREE.HemisphereLight(0x739bb0, 0x1f361a, 0.85);
    scene.add(skyLight);

    // 4. PERMUKAAN TANAH PADANG RUMPUT
    const groundGeo = new THREE.PlaneGeometry(85, 70, 30, 30);
    groundGeo.rotateX(-Math.PI / 2);
    const groundMat = new THREE.MeshStandardMaterial({
        color: 0x1d3618,
        roughness: 0.95,
        metalness: 0.02
    });
    const ground = new THREE.Mesh(groundGeo, groundMat);
    ground.receiveShadow = true;
    scene.add(ground);

    // 5. HELAI RUMPUT 3D DENGAN SHADER ANGIN
    const bladeW = 0.05;
    const bladeH = 0.42;
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
    const indices = [0, 1, 2, 1, 3, 2, 2, 3, 4, 5, 6, 7, 6, 8, 7, 7, 8, 9];
    clumpGeo.setIndex(indices);
    clumpGeo.setAttribute('position', new THREE.BufferAttribute(verts, 3));
    clumpGeo.computeVertexNormals();

    grassMaterial = new THREE.ShaderMaterial({
        uniforms: {
            uTime: { value: 0 },
            uBaseColor: { value: new THREE.Color(0x133814) },
            uTipColor: { value: new THREE.Color(0x61a830) },
            uSunDir: { value: new THREE.Vector3(0.5, 0.8, 0.3).normalize() }
        },
        vertexShader: `
            uniform float uTime;
            varying float vHeight;
            varying vec3 vNormal;

            void main() {
                vHeight = position.y;
                vec4 worldPos = instanceMatrix * vec4(position, 1.0);

                float wind = sin(uTime * 2.2 + worldPos.x * 0.5 + worldPos.z * 0.4) * 0.16;
                float bend = (position.y / 0.42);
                worldPos.x += wind * bend;

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
                vec3 col = mix(uBaseColor, uTipColor, vHeight / 0.42);
                float diff = max(dot(vNormal, uSunDir), 0.35);
                col *= (diff + 0.5);
                gl_FragColor = vec4(col, 1.0);
            }
        `,
        side: THREE.DoubleSide
    });

    const totalGrass = 18000;
    grassMesh = new THREE.InstancedMesh(clumpGeo, grassMaterial, totalGrass);
    const dummy = new THREE.Object3D();

    for (let i = 0; i < totalGrass; i++) {
        const x = (Math.random() - 0.5) * 55;
        const z = (Math.random() - 0.5) * 40;
        dummy.position.set(x, 0, z);
        dummy.rotation.y = Math.random() * Math.PI * 2;
        const s = 0.75 + Math.random() * 0.45;
        dummy.scale.set(s, s, s);
        dummy.updateMatrix();
        grassMesh.setMatrixAt(i, dummy.matrix);
    }
    grassMesh.instanceMatrix.needsUpdate = true;
    scene.add(grassMesh);

    // 6. PENEMPATAN KANDANG (DIMUNDURKAN KE BELAKANG PAGAR)
    const barnWoodTex = createBarnPlankTexture();
    const roofTex = createRoofTexture();
    const steelTex = createMetalTexture();

    const barn = buildRealisticBarn(barnWoodTex, roofTex);
    // Z = -4.5 membuat kandang berada aman di belakang pagar (Z = 2.0)
    barn.position.set(-8.2, 0, -4.5);
    barn.rotation.y = Math.PI / 6;
    scene.add(barn);

    // 7. PENEMPATAN KINCIR ANGIN (KANAN, POSISI PRESISI)
    const windmill = buildRealisticWindmill(steelTex);
    windmill.position.set(7.5, 0, -1.8);
    windmill.rotation.y = -Math.PI / 5.5;
    scene.add(windmill);

    // 8. PAGAR DI DEPAN KANDANG (TIDAK MENEMBUS RUMAH)
    const fence = createFarmFence();
    fence.position.set(0, 0, 2.5);
    scene.add(fence);

    window.addEventListener('resize', onResize);
    window.addEventListener('mousemove', onMouseMove);

    // 9. ANIMASI GSAP KONSOL MASUK
    gsap.fromTo(loginCard.value,
        { opacity: 0, y: 35, scale: 0.95 },
        { opacity: 1, y: 0, scale: 1, duration: 1.0, ease: 'power3.out' }
    );

    // 10. ANIMASI LOOP REALTIME
    const animate = () => {
        animationFrameId = requestAnimationFrame(animate);
        const elapsed = clock.getElapsedTime();

        grassMaterial.uniforms.uTime.value = elapsed;

        if (windmillRotor) {
            windmillRotor.rotation.z -= 0.024;
        }

        if (windmillVane) {
            windmillVane.rotation.y = Math.sin(elapsed * 0.8) * 0.04;
        }

        mouseX += (targetX - mouseX) * 0.035;
        mouseY += (targetY - mouseY) * 0.035;

        camera.position.x = mouseX * 1.2;
        camera.position.y = 3.6 - mouseY * 0.3;
        camera.lookAt(0, 3.2, 0);

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
    <Head title="Masuk Portal Petugas - JJFarm" />

    <div class="relative min-h-screen font-sans select-none overflow-x-hidden flex flex-col justify-between p-4 sm:p-6">
        
        <!-- CANVAS THREE.JS 3D -->
        <div ref="canvasContainer" class="fixed inset-0 z-0 pointer-events-none"></div>

        <!-- HEADER ATAS KONTRAST TINGGI -->
        <header class="relative z-10 max-w-7xl mx-auto w-full flex justify-between items-center py-2">
            <Link href="/" class="flex items-center gap-3.5 group">
                <div class="w-11 h-11 rounded-2xl bg-slate-950 text-white flex items-center justify-center font-black text-lg shadow-2xl border border-white/20 group-hover:scale-105 transition">
                    JJ
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white block leading-tight drop-shadow-[0_2px_12px_rgba(0,0,0,0.95)]">
                        JJFARM
                    </span>
                    <span class="text-[10px] text-emerald-200 font-extrabold uppercase tracking-widest drop-shadow-[0_1px_6px_rgba(0,0,0,0.95)]">
                        Gerbang Akses Peternakan
                    </span>
                </div>
            </Link>

            <Link href="/" class="text-xs font-black text-white hover:text-emerald-200 transition drop-shadow-[0_2px_8px_rgba(0,0,0,0.95)] flex items-center gap-2 bg-slate-950/80 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/25 shadow-xl">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali ke Beranda
            </Link>
        </header>

        <!-- KONSOL LOGIN ELEGAN DI TENGAH -->
        <main class="relative z-10 w-full max-w-md mx-auto my-auto py-6">
            
            <div ref="loginCard" 
                 class="bg-slate-950/85 backdrop-blur-3xl rounded-3xl p-8 sm:p-9 shadow-[0_30px_90px_rgba(0,0,0,0.85)] border border-white/15 space-y-6">
                
                <div class="text-center pb-1">
                    <div class="inline-block px-3.5 py-1 bg-emerald-500/20 border border-emerald-400/30 rounded-full text-[10px] font-black text-emerald-300 uppercase tracking-widest mb-2 shadow-xs">
                        Portal Otentikasi Petugas
                    </div>
                    <h2 class="text-3xl font-black text-white tracking-tight drop-shadow-[0_2px_10px_rgba(0,0,0,0.8)]">
                        Akses Pintu Masuk
                    </h2>
                    <p class="text-xs text-slate-300 font-semibold mt-1">
                        Sistem manajemen ternak, timbangan bobot, dan kasir
                    </p>
                </div>

                <div v-if="status" class="p-3 bg-emerald-900/60 border border-emerald-400 rounded-xl text-xs font-black text-emerald-200">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- ALAMAT EMAIL -->
                    <div>
                        <label class="block text-xs font-black text-slate-200 uppercase tracking-wider mb-1.5">
                            Alamat Email Petugas
                        </label>
                        <input v-model="form.email" type="email" required autofocus placeholder="petugas@jjfarm.com"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-xs font-bold text-white placeholder-slate-400 focus:bg-white/20 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none transition">
                        <span v-if="form.errors.email" class="text-[11px] text-rose-400 font-bold block mt-1">
                            {{ form.errors.email }}
                        </span>
                    </div>

                    <!-- KATA SANDI DENGAN SATU TOGGLE MATA -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-xs font-black text-slate-200 uppercase tracking-wider">
                                Kata Sandi
                            </label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-[11px] font-bold text-emerald-300 hover:underline">
                                Lupa sandi?
                            </Link>
                        </div>
                        
                        <div class="relative flex items-center">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   v-model="form.password" 
                                   required 
                                   placeholder="••••••••"
                                   class="no-reveal w-full px-4 py-3 pr-11 bg-white/10 border border-white/20 rounded-xl text-xs font-bold text-white placeholder-slate-400 focus:bg-white/20 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none transition">
                            
                            <button type="button" 
                                    @click="togglePasswordVisibility"
                                    class="absolute right-3 p-1.5 text-slate-300 hover:text-white focus:outline-none transition rounded-lg hover:bg-white/10 cursor-pointer"
                                    title="Tampilkan / Sembunyikan Sandi">
                                <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>

                        <span v-if="form.errors.password" class="text-[11px] text-rose-400 font-bold block mt-1">
                            {{ form.errors.password }}
                        </span>
                    </div>

                    <!-- INGAT SESI -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.remember" class="w-4 h-4 rounded text-emerald-600 bg-white/10 border-white/30 focus:ring-emerald-400">
                            <span class="ml-2 text-xs text-slate-300 font-bold">Ingat sesi di perangkat ini</span>
                        </label>
                    </div>

                    <!-- TOMBOL SUBMIT -->
                    <div class="pt-2">
                        <button type="submit" :disabled="form.processing"
                                class="w-full py-3.5 bg-emerald-800 hover:bg-emerald-900 text-white font-black rounded-xl text-xs uppercase tracking-wider transition shadow-2xl shadow-emerald-950/80 hover:scale-[1.02] active:scale-[0.98] border border-emerald-400/40 disabled:opacity-50 cursor-pointer">
                            Masuk ke Sistem
                        </button>
                    </div>

                </form>

            </div>

        </main>

        <!-- FOOTER SEJAJAR -->
        <footer class="relative z-10 max-w-7xl mx-auto w-full text-center py-2 text-xs text-white font-extrabold drop-shadow-[0_2px_10px_rgba(0,0,0,0.95)]">
            &copy; 2026 JJFarm. Sistem Pengelolaan Peternakan Kambing & Domba Terpadu.
        </footer>

    </div>
</template>

<style scoped>
.no-reveal::-ms-reveal,
.no-reveal::-ms-clear {
    display: none;
}
</style>