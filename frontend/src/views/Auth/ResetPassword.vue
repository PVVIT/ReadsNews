<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div class="relative flex lg:flex-row w-full h-screen justify-center flex-col dark:bg-gray-900">
        <!-- Cột Form Đặt Lại Mật Khẩu -->
        <div class="flex flex-col flex-1 lg:w-1/2 w-full">
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto p-4 sm:p-0">
            <div>
              <div class="mb-6 sm:mb-8">
                <router-link to="/signin"
                  class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-500 hover:text-brand-600 mb-4 transition">
                  <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                  </svg>
                  Quay lại đăng nhập
                </router-link>
                <h1
                  class="mb-2 font-display font-extrabold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                  Tạo Mật Khẩu Mới
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Nhập mật khẩu mới an toàn cho tài khoản <strong>{{ email }}</strong> của bạn.
                </p>
              </div>

              <!-- Cảnh báo nếu thiếu Token -->
              <div v-if="!token"
                class="mb-6 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-800 dark:border-rose-800/40 dark:bg-rose-950/30 dark:text-rose-300">
                <p class="font-bold text-sm mb-1">Mã xác thực không tìm thấy!</p>
                <p class="leading-relaxed">Liên kết này bị thiếu mã token xác thực. Vui lòng mở lại liên kết từ email
                  của bạn hoặc gửi lại yêu cầu quên mật khẩu.</p>
                <router-link to="/forgot-password" class="mt-2 inline-block font-bold underline">Gửi lại yêu cầu quên
                  mật khẩu</router-link>
              </div>

              <!-- Thông báo khi reset thành công -->
              <div v-if="isSuccess"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                <div class="flex items-start gap-3">
                  <span
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white font-bold">✓</span>
                  <div>
                    <p class="font-bold text-sm mb-1">Đặt lại mật khẩu thành công!</p>
                    <p class="leading-relaxed">Bạn có thể sử dụng mật khẩu mới này để đăng nhập ngay bây giờ. Đang
                      chuyển hướng...</p>
                    <router-link to="/signin" class="mt-2 inline-block font-bold text-brand-600 underline">Đến trang
                      đăng nhập ngay</router-link>
                  </div>
                </div>
              </div>

              <!-- Form Nhập Mật Khẩu Mới -->
              <form v-if="token && !isSuccess" @submit.prevent="handleSubmit">
                <div class="space-y-4">
                  <!-- Email (Hiển thị) -->
                  <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Địa chỉ Email
                    </label>
                    <input v-model="email" type="email" id="email" required readonly
                      class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-xs text-gray-500 shadow-theme-xs cursor-not-allowed dark:border-gray-700 dark:bg-gray-800" />
                  </div>

                  <!-- Mật khẩu mới -->
                  <div>
                    <label for="password" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Mật khẩu mới <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input v-model="password" :type="showPassword ? 'text' : 'password'" id="password" required
                        minlength="6" placeholder="Tối thiểu 6 ký tự"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pe-11 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                      <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                        {{ showPassword ? 'Ẩn' : 'Hiện' }}
                      </button>
                    </div>
                  </div>

                  <!-- Nhập lại mật khẩu mới -->
                  <div>
                    <label for="password_confirmation"
                      class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Xác nhận mật khẩu mới <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input v-model="passwordConfirmation" :type="showPassword ? 'text' : 'password'"
                        id="password_confirmation" required minlength="6" placeholder="Nhập lại mật khẩu mới"
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pe-11 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                  </div>

                  <div class="pt-2">
                    <button type="submit" :disabled="isLoading"
                      class="flex items-center justify-center w-full px-4 py-3 text-sm font-bold text-white transition rounded-xl bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-60 disabled:cursor-not-allowed">
                      <svg v-if="isLoading" class="animate-spin -ms-1 me-2 size-4 text-white" viewBox="0 0 24 24"
                        fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                      </svg>
                      <span>{{ isLoading ? 'Đang cập nhật mật khẩu...' : 'Lưu mật khẩu mới' }}</span>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Cột Minh Họa bên phải -->
        <div class="lg:w-1/2 w-full h-full bg-brand-950 dark:bg-white/5 lg:grid items-center hidden relative">
          <div class="items-center justify-center flex z-1">
            <common-grid-shape />
            <div class="flex flex-col items-center max-w-sm text-center px-6">
              <router-link to="/" class="block mb-5">
                <div class="flex items-center gap-3">
                  <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-brand-500 text-white font-extrabold shadow-lg shadow-brand-500/30">
                    RN
                  </div>
                  <span class="font-display text-2xl font-black text-white tracking-tight">ReadsNews AI</span>
                </div>
              </router-link>
              <h2 class="text-lg font-bold text-white mb-2">Đổi mật khẩu nhanh chóng</h2>
              <p class="text-xs text-gray-400 leading-relaxed">
                Sau khi đổi mật khẩu thành công, bạn có thể đăng nhập và tiếp tục trải nghiệm các tính năng nghe đọc báo
                AI cá nhân hóa.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import { authService } from '@/services/authService'
import { toast } from 'vue3-toastify'

const urlParams = new URLSearchParams(window.location.search)
const token = ref(urlParams.get('token') || '')
const email = ref(urlParams.get('email') || '')

const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const isLoading = ref(false)
const isSuccess = ref(false)

const handleSubmit = async () => {
  if (password.value !== passwordConfirmation.value) {
    toast.warning('Xác nhận mật khẩu không trùng khớp!')
    return
  }

  isLoading.value = true

  try {
    const res = await authService.resetPassword({
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    isSuccess.value = true
    setTimeout(() => {
      window.location.href = '/signin'
    }, 1800)
  } catch (err: any) {
    // Error handled by axios toast
  } finally {
    isLoading.value = false
  }
}
</script>
