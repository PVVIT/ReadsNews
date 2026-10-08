<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { auth } from './utils/auth.js'
import ReaderHome from './components/ReaderHome.vue'
import AdminDashboard from './components/AdminDashboard.vue'
import Signin from './views/Auth/Signin.vue'
import Signup from './views/Auth/Signup.vue'
import ForgotPassword from './views/Auth/ForgotPassword.vue'
import ResetPassword from './views/Auth/ResetPassword.vue'

// ─── Routing ────────────────────────────────────────────────────────────────
const currentPath = ref(window.location.pathname.replace(/\/+$/, '') || '/')

const updatePath = () => {
  currentPath.value = window.location.pathname.replace(/\/+$/, '') || '/'
}

onMounted(() => {
  window.addEventListener('popstate', updatePath)
  // Chặn /admin khi load trang lần đầu
  guardAdminRoute()
})

onBeforeUnmount(() => {
  window.removeEventListener('popstate', updatePath)
})

// ─── Route Guard Phân Quyền ──────────────────────────────────────────────────
/**
 * Chỉ admin và editor mới được truy cập /admin.
 * Nếu không có quyền → redirect về trang chủ và set cờ unauthorizedAccess.
 */
const unauthorizedAccess = ref(false)

function guardAdminRoute() {
  const path = window.location.pathname.replace(/\/+$/, '') || '/'

  if (path === '/admin') {
    // Chưa đăng nhập → redirect về /signin
    if (!auth.isAuthenticated()) {
      window.history.replaceState(null, '', '/signin')
      currentPath.value = '/signin'
      return
    }

    // Đã đăng nhập nhưng không có quyền admin/editor → redirect về trang chủ
    if (!auth.hasRole(['admin', 'editor'])) {
      window.history.replaceState(null, '', '/')
      currentPath.value = '/'
      unauthorizedAccess.value = true
      // Xóa cờ sau 4 giây
      setTimeout(() => { unauthorizedAccess.value = false }, 4000)
    }
  }
}

// Cũng guard khi navigate bằng popstate (nút back/forward trình duyệt)
const originalUpdatePath = updatePath
const updatePathWithGuard = () => {
  originalUpdatePath()
  guardAdminRoute()
}

onMounted(() => {
  window.removeEventListener('popstate', updatePath)
  window.addEventListener('popstate', updatePathWithGuard)
})

// Computed: route hiện tại có được phép hiển thị AdminDashboard không?
const canAccessAdmin = computed(() => {
  return currentPath.value === '/admin' && auth.hasRole(['admin', 'editor'])
})
</script>

<template>
  <!-- Toast cảnh báo không có quyền truy cập -->
  <Transition name="slide-down">
    <div
      v-if="unauthorizedAccess"
      class="fixed top-4 left-1/2 z-[9999] -translate-x-1/2 flex items-center gap-3 rounded-2xl border border-rose-200 bg-white px-5 py-3.5 shadow-xl shadow-rose-100/50 text-sm font-semibold text-rose-700"
      style="min-width: 320px; max-width: 90vw;"
    >
      <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </span>
      <div>
        <p class="font-bold text-rose-800">Không có quyền truy cập</p>
      </div>
    </div>
  </Transition>

  <!-- Router view thủ công -->
  <AdminDashboard v-if="canAccessAdmin" />
  <Signin v-else-if="currentPath === '/signin' || currentPath === '/login'" />
  <Signup v-else-if="currentPath === '/signup' || currentPath === '/register'" />
  <ForgotPassword v-else-if="currentPath === '/forgot-password' || currentPath === '/reset-password-request'" />
  <ResetPassword v-else-if="currentPath === '/reset-password'" />
  <ReaderHome v-else />
</template>

<style>
/* Animation toast cảnh báo từ trên trượt xuống */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-16px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px);
}
</style>
