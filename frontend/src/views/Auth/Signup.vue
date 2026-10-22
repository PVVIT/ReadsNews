<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div class="relative flex lg:flex-row w-full h-screen justify-center flex-col dark:bg-gray-900">
        <!-- Form Đăng ký -->
        <div class="flex flex-col flex-1 lg:w-1/2 w-full">
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto p-4 sm:p-0">
            <div>
              <div class="mb-5 sm:mb-6">
                <h1
                  class="mb-2 font-display font-extrabold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                  Đăng Ký Tài Khoản
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Tạo tài khoản để cá nhân hóa tin tức và trải nghiệm giọng đọc AI.
                </p>
              </div>

              <form @submit.prevent="handleSubmit" novalidate>
                <div class="space-y-4">
                  <!-- Họ tên -->
                  <div>
                    <label for="name" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Họ và tên <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input v-model="name" @input="clearFieldError('name')" @blur="validateField('name')" type="text" id="name" placeholder="Nguyễn Văn A"
                        :class="[
                          'dark:bg-dark-900 h-10.5 w-full rounded-lg border bg-transparent px-4 py-2 text-xs shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 transition-colors',
                          errors.name
                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-500/10 pe-10 dark:border-red-500 dark:text-red-300'
                            : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
                        ]" />
                      <div v-if="errors.name" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                        <svg class="size-4.5" viewBox="0 0 16 16" fill="currentColor">
                          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                          <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                        </svg>
                      </div>
                    </div>
                    <p v-if="errors.name" class="mt-1 text-xs text-red-500 font-medium">
                      {{ errors.name }}
                    </p>
                  </div>

                  <!-- Email -->
                  <div>
                    <label for="email" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Địa chỉ Email <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input v-model="email" @input="clearFieldError('email')" @blur="validateField('email')" type="email" id="email" placeholder="ban@example.com"
                        :class="[
                          'dark:bg-dark-900 h-10.5 w-full rounded-lg border bg-transparent px-4 py-2 text-xs shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 transition-colors',
                          errors.email
                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-500/10 pe-10 dark:border-red-500 dark:text-red-300'
                            : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
                        ]" />
                      <div v-if="errors.email" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                        <svg class="size-4.5" viewBox="0 0 16 16" fill="currentColor">
                          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                          <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                        </svg>
                      </div>
                    </div>
                    <p v-if="errors.email" class="mt-1 text-xs text-red-500 font-medium">
                      {{ errors.email }}
                    </p>
                  </div>

                  <!-- Mật khẩu -->
                  <div>
                    <label for="password" class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Mật khẩu <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input v-model="password" @input="clearFieldError('password')" @blur="validateField('password')" :type="showPassword ? 'text' : 'password'" id="password" 
                        placeholder="Tối thiểu 6 ký tự"
                        :class="[
                          'dark:bg-dark-900 h-10.5 w-full rounded-lg border bg-transparent px-4 py-2 text-xs shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 transition-colors',
                          errors.password
                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-500/10 pe-16 dark:border-red-500 dark:text-red-300'
                            : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 pe-11 dark:border-gray-700 dark:focus:border-brand-800'
                        ]" />
                      <div class="absolute inset-y-0 end-0 flex items-center pe-3 gap-1.5">
                        <span v-if="errors.password" class="text-red-500 pointer-events-none">
                          <svg class="size-4.5" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                          </svg>
                        </span>
                        <button type="button" @click="togglePasswordVisibility"
                          class="text-xs text-gray-400 hover:text-gray-600">
                          {{ showPassword ? 'Ẩn' : 'Hiện' }}
                        </button>
                      </div>
                    </div>
                    <p v-if="errors.password" class="mt-1 text-xs text-red-500 font-medium">
                      {{ errors.password }}
                    </p>
                  </div>

                  <!-- Xác nhận mật khẩu -->
                  <div>
                    <label for="password_confirmation"
                      class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-400">
                      Xác nhận mật khẩu <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                      <input v-model="passwordConfirmation" @input="clearFieldError('passwordConfirmation')" @blur="validateField('passwordConfirmation')" :type="showPassword ? 'text' : 'password'"
                        id="password_confirmation" placeholder="Nhập lại mật khẩu"
                        :class="[
                          'dark:bg-dark-900 h-10.5 w-full rounded-lg border bg-transparent px-4 py-2 text-xs shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90 transition-colors',
                          errors.passwordConfirmation
                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-500/10 pe-10 dark:border-red-500 dark:text-red-300'
                            : 'border-gray-300 text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800'
                        ]" />
                      <div v-if="errors.passwordConfirmation" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                        <svg class="size-4.5" viewBox="0 0 16 16" fill="currentColor">
                          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                          <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                        </svg>
                      </div>
                    </div>
                    <p v-if="errors.passwordConfirmation" class="mt-1 text-xs text-red-500 font-medium">
                      {{ errors.passwordConfirmation }}
                    </p>
                  </div>

                  <!-- Điều khoản -->
                  <div>
                    <div class="flex items-start gap-2 pt-1">
                      <input v-model="agreeToTerms" @change="clearFieldError('agreeToTerms')" type="checkbox" id="terms"
                        :class="[
                          'size-4 mt-0.5 rounded focus:ring-brand-500/20 cursor-pointer',
                          errors.agreeToTerms ? 'border-red-500 text-red-600 focus:ring-red-500' : 'border-gray-300 text-brand-500'
                        ]" />
                      <label for="terms" :class="['text-xs cursor-pointer select-none', errors.agreeToTerms ? 'text-red-600 font-medium' : 'text-gray-600 dark:text-gray-400']">
                        Tôi đồng ý với <span class="font-semibold text-slate-800 dark:text-white">Điều khoản sử
                          dụng</span> và <span class="font-semibold text-slate-800 dark:text-white">Chính sách bảo
                          mật</span> của ReadsNews.
                      </label>
                    </div>
                    <p v-if="errors.agreeToTerms" class="mt-1 text-xs text-red-500 font-medium">
                      {{ errors.agreeToTerms }}
                    </p>
                  </div>

                  <!-- Nút Submit -->
                  <div class="pt-2">
                    <button type="submit" :disabled="isLoading || isRedirecting"
                      class="flex items-center justify-center w-full px-4 py-3 text-sm font-bold text-white transition rounded-xl bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                      <svg v-if="isLoading" class="animate-spin -ms-1 me-2 size-4 text-white" viewBox="0 0 24 24"
                        fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                      </svg>
                      <span>{{ isLoading ? 'Đang tạo tài khoản...' : 'Đăng Ký Tài Khoản' }}</span>
                    </button>
                  </div>
                </div>
              </form>

              <div class="mt-5 text-center text-xs text-gray-600 dark:text-gray-400">
                Đã có tài khoản ReadsNews?
                <router-link to="/signin"
                  class="font-bold text-brand-500 hover:text-brand-600 dark:text-brand-400 ms-1 transition">
                  Đăng nhập ngay
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Cột Phải Minh Họa -->
        <div class="lg:w-1/2 w-full h-full bg-slate-900 dark:bg-white/5 lg:grid items-center hidden relative">
          <div class="items-center justify-center flex z-1">
            <common-grid-shape />
            <div class="flex flex-col items-center max-w-sm text-center px-6">
              <router-link to="/" class="block mb-5">
                <div
                  class="rounded-2xl bg-white p-3 shadow-xl shadow-sky-500/20 inline-block hover:scale-105 transition">
                  <img src="/logo/Khuon-Ngang-removebg.png" alt="ReadVn" class="h-11 w-auto object-contain" />
                </div>
              </router-link>
              <h2 class="text-lg font-bold text-white mb-2">Báo Đọc – Tin Nhanh cùng AI Audio</h2>
              <p class="text-xs text-gray-400 leading-relaxed">
                Tạo tài khoản miễn phí để nghe báo mọi lúc mọi nơi, tóm tắt nhanh nội dung bằng trí tuệ nhân tạo và lưu
                trữ bài viết yêu thích.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </FullScreenLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onBeforeUnmount } from 'vue'
import CommonGridShape from '@/components/common/CommonGridShape.vue'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import { authService } from '@/services/authService'
import { auth } from '@/utils/auth'
import { toast } from 'vue3-toastify'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const agreeToTerms = ref(true)
const isLoading = ref(false)
const isRedirecting = ref(false)
const redirectProgress = ref(0)
const remainingSeconds = ref(3)

const errors = reactive({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
  agreeToTerms: '',
})

let progressTimer: any = null

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const validateField = (field: 'name' | 'email' | 'password' | 'passwordConfirmation' | 'agreeToTerms') => {
  if (field === 'name') {
    const val = name.value.trim()
    if (!val) {
      errors.name = 'Vui lòng nhập họ và tên.'
    } else if (val.length < 2) {
      errors.name = 'Họ và tên phải có ít nhất 2 ký tự.'
    } else if (val.length > 100) {
      errors.name = 'Họ và tên không được vượt quá 100 ký tự.'
    } else {
      errors.name = ''
    }
  } else if (field === 'email') {
    const val = email.value.trim()
    if (!val) {
      errors.email = 'Vui lòng nhập địa chỉ email.'
    } else if (!emailRegex.test(val)) {
      errors.email = 'Địa chỉ email không đúng định dạng.'
    } else {
      errors.email = ''
    }
  } else if (field === 'password') {
    const val = password.value
    if (!val) {
      errors.password = 'Vui lòng nhập mật khẩu.'
    } else if (val.length < 6) {
      errors.password = 'Mật khẩu phải có ít nhất 6 ký tự.'
    } else {
      errors.password = ''
    }
    // Nếu đã nhập password_confirmation thì kiểm tra lại khớp mật khẩu
    if (passwordConfirmation.value) {
      validateField('passwordConfirmation')
    }
  } else if (field === 'passwordConfirmation') {
    const val = passwordConfirmation.value
    if (!val) {
      errors.passwordConfirmation = 'Vui lòng xác nhận lại mật khẩu.'
    } else if (val !== password.value) {
      errors.passwordConfirmation = 'Mật khẩu xác nhận không trùng khớp.'
    } else {
      errors.passwordConfirmation = ''
    }
  } else if (field === 'agreeToTerms') {
    if (!agreeToTerms.value) {
      errors.agreeToTerms = 'Bạn cần đồng ý với Điều khoản sử dụng và Chính sách bảo mật.'
    } else {
      errors.agreeToTerms = ''
    }
  }
}

const clearFieldError = (field: 'name' | 'email' | 'password' | 'passwordConfirmation' | 'agreeToTerms') => {
  if (errors[field]) {
    validateField(field)
  }
}

const validateAll = () => {
  validateField('name')
  validateField('email')
  validateField('password')
  validateField('passwordConfirmation')
  validateField('agreeToTerms')

  return !errors.name && !errors.email && !errors.password && !errors.passwordConfirmation && !errors.agreeToTerms
}

const handleSubmit = async () => {
  if (!validateAll()) {
    // Focus vào ô lỗi đầu tiên
    if (errors.name) {
      document.getElementById('name')?.focus()
    } else if (errors.email) {
      document.getElementById('email')?.focus()
    } else if (errors.password) {
      document.getElementById('password')?.focus()
    } else if (errors.passwordConfirmation) {
      document.getElementById('password_confirmation')?.focus()
    } else if (errors.agreeToTerms) {
      document.getElementById('terms')?.focus()
    }
    return
  }

  isLoading.value = true

  try {
    const res = await authService.register({
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    if (res.data?.token && res.data?.user) {
      auth.setAuth(res.data.user, res.data.token)

      // Kích hoạt thông báo Toast thành công
      toast.success('Đăng ký tài khoản thành công! Đang chuyển hướng sau 1 giây...', {
        autoClose: 1000,
      })

      // Kích hoạt hiệu ứng loading 1.5s
      isRedirecting.value = true
      redirectProgress.value = 0
      remainingSeconds.value = 1.5

      const startTime = Date.now()
      const duration = 1500

      progressTimer = setInterval(() => {
        const elapsed = Date.now() - startTime
        redirectProgress.value = Math.min(100, Math.floor((elapsed / duration) * 100))
        remainingSeconds.value = Math.max(1, Math.ceil((duration - elapsed) / 1000))

        if (elapsed >= duration) {
          clearInterval(progressTimer)
          redirectProgress.value = 100
          window.location.href = '/'
        }
      }, 50)
    }
  } catch (err: any) {
    isLoading.value = false
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      if (serverErrors.name) errors.name = serverErrors.name[0]
      if (serverErrors.email) errors.email = serverErrors.email[0]
      if (serverErrors.password) errors.password = serverErrors.password[0]
      if (serverErrors.password_confirmation) errors.passwordConfirmation = serverErrors.password_confirmation[0]
    }
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
