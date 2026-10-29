<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import * as THREE from 'three'

const props = defineProps({
  isAudioPlaying: {
    type: Boolean,
    default: false,
  },
})

const canvasContainer = ref(null)

let scene = null
let camera = null
let renderer = null
let animationFrameId = null
let particlesMesh = null
let linesMesh = null
let sphereWireframe = null

let mouseX = 0
let mouseY = 0
let targetMouseX = 0
let targetMouseY = 0

const PARTICLE_COUNT = 140
const positions = new Float32Array(PARTICLE_COUNT * 3)
const originalPositions = new Float32Array(PARTICLE_COUNT * 3)
const velocities = []

function initThree() {
  if (!canvasContainer.value) return

  const width = canvasContainer.value.clientWidth || window.innerWidth
  const height = canvasContainer.value.clientHeight || window.innerHeight

  // 1. Scene
  scene = new THREE.Scene()

  // 2. Camera
  camera = new THREE.PerspectiveCamera(60, width / height, 1, 1000)
  camera.position.z = 320

  // 3. Renderer (Nền trong suốt hòa quyện với theme trắng)
  renderer = new THREE.WebGLRenderer({
    alpha: true,
    antialias: true,
    powerPreference: 'high-performance',
  })
  renderer.setSize(width, height)
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  renderer.setClearColor(0x000000, 0) // hoàn toàn trong suốt

  canvasContainer.value.appendChild(renderer.domElement)

  // 4. Create Constellation Particles (Xanh da trời & Xanh dương nhạt cho nền sáng)
  const colors = new Float32Array(PARTICLE_COUNT * 3)
  const color1 = new THREE.Color(0x0284c7) // Sky blue đậm
  const color2 = new THREE.Color(0x38bdf8) // Sky blue da trời sáng
  const color3 = new THREE.Color(0x60a5fa) // Light blue

  for (let i = 0; i < PARTICLE_COUNT; i++) {
    const x = (Math.random() - 0.5) * 650
    const y = (Math.random() - 0.5) * 450
    const z = (Math.random() - 0.5) * 350

    positions[i * 3] = x
    positions[i * 3 + 1] = y
    positions[i * 3 + 2] = z

    originalPositions[i * 3] = x
    originalPositions[i * 3 + 1] = y
    originalPositions[i * 3 + 2] = z

    velocities.push({
      x: (Math.random() - 0.5) * 0.35,
      y: (Math.random() - 0.5) * 0.35,
      z: (Math.random() - 0.5) * 0.35,
    })

    const chosenColor = i % 3 === 0 ? color1 : i % 3 === 1 ? color2 : color3
    colors[i * 3] = chosenColor.r
    colors[i * 3 + 1] = chosenColor.g
    colors[i * 3 + 2] = chosenColor.b
  }

  const particlesGeo = new THREE.BufferGeometry()
  particlesGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3))
  particlesGeo.setAttribute('color', new THREE.BufferAttribute(colors, 3))

  const particlesMat = new THREE.PointsMaterial({
    size: 4.5,
    vertexColors: true,
    transparent: true,
    opacity: 0.55,
  })

  particlesMesh = new THREE.Points(particlesGeo, particlesMat)
  scene.add(particlesMesh)

  // 5. Line Connections (Tia liên kết xanh nhạt)
  const lineMaterial = new THREE.LineBasicMaterial({
    color: 0x38bdf8,
    transparent: true,
    opacity: 0.3,
  })

  const maxLines = PARTICLE_COUNT * 5
  const linePositions = new Float32Array(maxLines * 6)
  const lineGeo = new THREE.BufferGeometry()
  lineGeo.setAttribute('position', new THREE.BufferAttribute(linePositions, 3))
  linesMesh = new THREE.LineSegments(lineGeo, lineMaterial)
  scene.add(linesMesh)

  // 6. Central Tech Sphere (Icosahedron Wireframe xanh ngọc/da trời nhạt)
  const sphereGeo = new THREE.IcosahedronGeometry(95, 2)
  const sphereMat = new THREE.MeshBasicMaterial({
    color: 0x0284c7,
    wireframe: true,
    transparent: true,
    opacity: 0.18,
  })
  sphereWireframe = new THREE.Mesh(sphereGeo, sphereMat)
  sphereWireframe.position.set(160, -20, -50)
  scene.add(sphereWireframe)

  // Listeners
  window.addEventListener('resize', handleResize)
  window.addEventListener('mousemove', handleMouseMove)

  animate()
}

function handleResize() {
  if (!canvasContainer.value || !camera || !renderer) return
  const width = canvasContainer.value.clientWidth
  const height = canvasContainer.value.clientHeight
  camera.aspect = width / height
  camera.updateProjectionMatrix()
  renderer.setSize(width, height)
}

function handleMouseMove(e) {
  const windowHalfX = window.innerWidth / 2
  const windowHalfY = window.innerHeight / 2
  targetMouseX = (e.clientX - windowHalfX) * 0.08
  targetMouseY = (e.clientY - windowHalfY) * 0.08
}

let clock = 0
function animate() {
  animationFrameId = requestAnimationFrame(animate)
  clock += 0.015

  // Smooth mouse inertia
  mouseX += (targetMouseX - mouseX) * 0.05
  mouseY += (targetMouseY - mouseY) * 0.05

  if (camera) {
    camera.position.x = mouseX
    camera.position.y = -mouseY
    camera.lookAt(scene.position)
  }

  // Rotate Central Sphere
  if (sphereWireframe) {
    sphereWireframe.rotation.x += 0.002
    sphereWireframe.rotation.y += 0.003
    const scale = props.isAudioPlaying
      ? 1 + Math.sin(clock * 5) * 0.08
      : 1 + Math.sin(clock * 1.5) * 0.02
    sphereWireframe.scale.set(scale, scale, scale)
  }

  // Update particles
  if (particlesMesh) {
    const pos = particlesMesh.geometry.attributes.position.array
    const audioMultiplier = props.isAudioPlaying ? 2.5 : 1

    for (let i = 0; i < PARTICLE_COUNT; i++) {
      pos[i * 3] += velocities[i].x * audioMultiplier
      pos[i * 3 + 1] += velocities[i].y * audioMultiplier
      pos[i * 3 + 2] += velocities[i].z * audioMultiplier

      // Bounce limits
      if (Math.abs(pos[i * 3]) > 340) velocities[i].x *= -1
      if (Math.abs(pos[i * 3 + 1]) > 240) velocities[i].y *= -1
      if (Math.abs(pos[i * 3 + 2]) > 180) velocities[i].z *= -1
    }
    particlesMesh.geometry.attributes.position.needsUpdate = true

    // Rebuild constellation lines between close particles
    if (linesMesh) {
      const linePositions = linesMesh.geometry.attributes.position.array
      let lineIndex = 0
      const connectDist = 80

      for (let i = 0; i < PARTICLE_COUNT; i++) {
        for (let j = i + 1; j < PARTICLE_COUNT; j++) {
          const dx = pos[i * 3] - pos[j * 3]
          const dy = pos[i * 3 + 1] - pos[j * 3 + 1]
          const dz = pos[i * 3 + 2] - pos[j * 3 + 2]
          const dist = Math.sqrt(dx * dx + dy * dy + dz * dz)

          if (dist < connectDist && lineIndex < linePositions.length - 6) {
            linePositions[lineIndex++] = pos[i * 3]
            linePositions[lineIndex++] = pos[i * 3 + 1]
            linePositions[lineIndex++] = pos[i * 3 + 2]

            linePositions[lineIndex++] = pos[j * 3]
            linePositions[lineIndex++] = pos[j * 3 + 1]
            linePositions[lineIndex++] = pos[j * 3 + 2]
          }
        }
      }

      for (let k = lineIndex; k < linePositions.length; k++) {
        linePositions[k] = 0
      }
      linesMesh.geometry.attributes.position.needsUpdate = true
    }
  }

  if (renderer && scene && camera) {
    renderer.render(scene, camera)
  }
}

onMounted(() => {
  initThree()
})

onBeforeUnmount(() => {
  if (animationFrameId) cancelAnimationFrame(animationFrameId)
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('mousemove', handleMouseMove)

  if (renderer && renderer.domElement && canvasContainer.value) {
    canvasContainer.value.removeChild(renderer.domElement)
    renderer.dispose()
  }
})
</script>

<template>
  <div ref="canvasContainer" class="pointer-events-none fixed inset-0 -z-10 h-full w-full overflow-hidden">
    <!-- Ambient light sky gradient -->
    <div class="absolute inset-0 bg-gradient-to-b from-sky-50/40 via-white/80 to-slate-100/90 pointer-events-none"></div>
    <div class="absolute -top-40 -left-40 size-96 rounded-full bg-sky-300/20 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -right-40 size-96 rounded-full bg-blue-200/25 blur-3xl pointer-events-none"></div>
  </div>
</template>
