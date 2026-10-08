<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div class="relative flex lg:flex-row w-full h-screen justify-center flex-col dark:bg-gray-900">
        <!-- Cột Form bên trái -->
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
                  Quên mật khẩu?
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Nhập địa chỉ email tài khoản của bạn để nhận liên kết đặt lại mật khẩu an toàn.
                </p>
              </div>

              <!-- Hộp thông báo gửi email thành công -->
              <div v-if="successMessage"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                <div class="flex items-start gap-3">
                  <span
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white font-bold">✓</span>
                  <div>
                    <p class="font-bold text-sm mb-1">Email đã được gửi thành công!</p>
                    <p class="leading-relaxed">{{ successMessage }}</p>
                    <p class="mt-2 text-[11px] text-emerald-700/80">Lưu ý kiểm tra cả hòm thư Rác (Spam) nếu không thấy
                      trong hộp thư đến.</p>
                  </div>
                </div>
              </div>

              <!-- Form gửi email -->
              <form v-if="!successMessage" @submit.prevent="handleSubmit">
                <div class="space-y-5">
                  <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                      Email tài khoản <span class="text-error-500">*</span>
                    </label>
                    <input v-model="email" type="email" id="email" required placeholder="ban@example.com"
                      class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800" />
                  </div>

                  <div>
                    <button type="submit" :disabled="isLoading"
                      class="flex items-center justify-center w-full px-4 py-3 text-sm font-bold text-white transition rounded-xl bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-60 disabled:cursor-not-allowed">
                      <svg v-if="isLoading" class="animate-spin -ms-1 me-2 size-4 text-white" viewBox="0 0 24 24"
                        fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                      </svg>
                      <span>{{ isLoading ? 'Đang gửi email xác thực...' : 'Gửi liên kết khôi phục' }}</span>
                    </button>
                  </div>
                </div>
              </form>

              <div class="mt-6 flex flex-col gap-2 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>
                  Chưa có tài khoản ReadsNews?
                  <router-link to="/signup"
                    class="font-semibold text-brand-500 hover:text-brand-600 dark:text-brand-400">Đăng ký
                    ngay</router-link>
                </p>
                <p>
                  Nhớ lại mật khẩu?
                  <router-link to="/signin"
                    class="font-semibold text-brand-500 hover:text-brand-600 dark:text-brand-400">Đăng
                    nhập</router-link>
                </p>
              </div>
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
              <h2 class="text-lg font-bold text-white mb-2">Bảo mật tài khoản của bạn</h2>
              <p class="text-xs text-gray-400 leading-relaxed">
                Hệ thống hỗ trợ gửi mã đặt lại mật khẩu an toàn qua email được mã hóa, bảo vệ quyền riêng tư và danh
                sách bài báo đã lưu của bạn.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import { authService } from '@/services/authService'

const email = ref('')
const isLoading = ref(false)
const successMessage = ref('')

const handleSubmit = async () => {
  if (!email.value) return

  isLoading.value = true
  successMessage.value = ''

  try {
    const res = await authService.forgotPassword({ email: email.value })
    successMessage.value = res.message || 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.'
  } catch (err: any) {
    // Lỗi đã được axios interceptor hiển thị toast thông báo
  } finally {
    isLoading.value = false
  }
}
</script>
