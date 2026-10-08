<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div class="relative flex lg:flex-row w-full h-screen justify-center flex-col dark:bg-gray-900">
        <!-- Form Bên Trái -->
        <div class="flex flex-col flex-1 lg:w-1/2 w-full">
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto p-4 sm:p-0">
            <div>
              <div class="mb-6 sm:mb-8">
                <h1 class="mb-2 font-display font-extrabold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                  Đăng Nhập
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Chào mừng trở lại! Vui lòng nhập email và mật khẩu để tiếp tục.
                </p>
              </div>

              <form @submit.prevent="handleSubmit">
                <div class="space-y-4">
                  <!-- Email -->
                  <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Email <span class="text-error-500">*</span>
                    </label>
                    <input
                      v-model="email"
                      type="email"
                      id="email"
                      name="email"
                      required
                      placeholder="admin@readsnews.vn hoặc email của bạn"
                      class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                    />
                  </div>

                  <!-- Mật khẩu -->
                  <div>
                    <label for="password" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Mật khẩu <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        required
                        placeholder="Nhập mật khẩu"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 px-4 pe-11 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                      />
                      <button
                        type="button"
                        @click="togglePasswordVisibility"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-xs text-gray-400 hover:text-gray-600 dark:text-gray-400"
                      >
                        {{ showPassword ? 'Ẩn' : 'Hiện' }}
                      </button>
                    </div>
                  </div>

                  <!-- Tùy chọn nhớ mật khẩu & Quên mật khẩu -->
                  <div class="flex items-center justify-between pt-1">
                    <label for="keepLoggedIn" class="flex items-center text-xs font-medium text-gray-600 cursor-pointer select-none dark:text-gray-400">
                      <input
                        v-model="keepLoggedIn"
                        type="checkbox"
                        id="keepLoggedIn"
                        class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 me-2"
                      />
                      Ghi nhớ đăng nhập
                    </label>

                    <router-link
                      to="/forgot-password"
                      class="text-xs font-semibold text-brand-500 hover:text-brand-600 dark:text-brand-400 transition"
                    >
                      Quên mật khẩu?
                    </router-link>
                  </div>

                  <!-- Nút Đăng nhập -->
                  <div class="pt-2">
                    <button
                      type="submit"
                      :disabled="isLoading || isRedirecting"
                      class="flex items-center justify-center w-full px-4 py-3 text-sm font-bold text-white transition rounded-xl bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                      <svg v-if="isLoading" class="animate-spin -ms-1 me-2 size-4 text-white" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                      </svg>
                      <span>{{ isLoading ? 'Đang xác thực...' : 'Đăng Nhập' }}</span>
                    </button>
                  </div>
                </div>
              </form>

              <!-- Link đăng ký -->
              <div class="mt-6 text-center text-xs text-gray-600 dark:text-gray-400">
                Chưa có tài khoản ReadsNews?
                <router-link
                  to="/signup"
                  class="font-bold text-brand-500 hover:text-brand-600 dark:text-brand-400 ms-1 transition"
                >
                  Đăng ký ngay
                </router-link>
              </div>

              <!-- Tài khoản test mẫu gợi ý -->
              <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-3.5 text-[11px] text-slate-500 dark:border-gray-800 dark:bg-gray-800/40">
                <p class="font-bold text-slate-700 dark:text-slate-300 mb-1">Tài khoản demo sẵn có:</p>
                <div class="flex flex-col gap-0.5 font-mono">
                  <span>• Admin: <strong>admin@readsnews.vn</strong> / <strong>password</strong></span>
                  <span>• Người dùng: <strong>vanvu31205@gmail.com</strong> / <strong>newpassword888</strong></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cột Phải Minh Họa -->
        <div class="lg:w-1/2 w-full h-full bg-brand-950 dark:bg-white/5 lg:grid items-center hidden relative">
          <div class="items-center justify-center flex z-1">
            <common-grid-shape />
            <div class="flex flex-col items-center max-w-sm text-center px-6">
              <router-link to="/" class="block mb-5">
                <div class="flex items-center gap-3">
                  <div class="flex size-12 items-center justify-center rounded-2xl bg-brand-500 text-white font-extrabold shadow-lg shadow-brand-500/30">
                    RN
                  </div>
                  <span class="font-display text-2xl font-black text-white tracking-tight">ReadsNews AI</span>
                </div>
              </router-link>
              <h2 class="text-lg font-bold text-white mb-2">Đọc báo thông minh với AI Audio</h2>
              <p class="text-xs text-gray-400 leading-relaxed">
                Đăng nhập để lưu bài viết yêu thích, nghe tin tức tự động và tùy chỉnh giọng đọc AI theo phong cách của bạn.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Màn hình Loading Chuyển Hướng 3 Giây Khi Đăng Nhập Thành Công -->
    <Transition name="fade">
      <div v-if="isRedirecting" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/85 backdrop-blur-md text-white p-6">
        <div class="relative flex size-20 items-center justify-center mb-6">
          <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20"></div>
          <div class="absolute inset-0 rounded-full border-4 border-indigo-400 border-t-transparent animate-spin"></div>
          <span class="text-2xl font-bold animate-pulse">✨</span>
        </div>
        <h3 class="font-display text-xl sm:text-2xl font-extrabold mb-2 text-center text-white">Đăng nhập thành công!</h3>
        <p class="text-xs sm:text-sm text-slate-300 text-center max-w-sm mb-6 leading-relaxed">
          Đang khởi tạo phiên làm việc và chuẩn bị giao diện cho bạn...
        </p>
        
        <!-- Thanh tiến trình 3 giây -->
        <div class="w-72 max-w-full h-2.5 bg-slate-800 rounded-full overflow-hidden border border-slate-700/80 shadow-inner">
          <div class="h-full bg-gradient-to-r from-indigo-500 via-indigo-400 to-violet-500 rounded-full transition-all duration-75 ease-linear" :style="{ width: `${redirectProgress}%` }"></div>
        </div>
        
        <div class="mt-4 flex items-center gap-2 text-xs text-indigo-300 font-medium">
          <span class="size-2 rounded-full bg-indigo-400 animate-ping"></span>
          <span>Tự động chuyển hướng sau <strong>{{ remainingSeconds }}s</strong>...</span>
        </div>
      </div>
    </Transition>
  </FullScreenLayout>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import { authService } from '@/services/authService'
import { auth } from '@/utils/auth'
import { toast } from 'vue3-toastify'

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const keepLoggedIn = ref(true)
const isLoading = ref(false)
const isRedirecting = ref(false)
const redirectProgress = ref(0)
const remainingSeconds = ref(3)

let progressTimer: any = null

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleSubmit = async () => {
  if (!email.value || !password.value) return

  isLoading.value = true

  try {
    const res = await authService.login({
      email: email.value,
      password: password.value,
    })

    if (res.data?.token && res.data?.user) {
      auth.setAuth(res.data.user, res.data.token)

      // Kích hoạt thông báo Toast thành công
      toast.success('Đăng nhập thành công! Hệ thống đang chuyển hướng sau 3 giây...', {
        autoClose: 3000,
      })

      // Kích hoạt hiệu ứng loading 3s
      isRedirecting.value = true
      redirectProgress.value = 0
      remainingSeconds.value = 3

      const startTime = Date.now()
      const duration = 3000

      progressTimer = setInterval(() => {
        const elapsed = Date.now() - startTime
        redirectProgress.value = Math.min(100, Math.floor((elapsed / duration) * 100))
        remainingSeconds.value = Math.max(1, Math.ceil((duration - elapsed) / 1000))

        if (elapsed >= duration) {
          clearInterval(progressTimer)
          redirectProgress.value = 100
          if (res.data.user.role === 'admin') {
            window.location.href = '/admin'
          } else {
            window.location.href = '/'
          }
        }
      }, 50)
    }
  } catch (err: any) {
    // Axios interceptor tự động hiển thị toast lỗi (401, 403, 422...)
    isLoading.value = false
  }
}

onBeforeUnmount(() => {
  if (progressTimer) clearInterval(progressTimer)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
