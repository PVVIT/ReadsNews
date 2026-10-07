import { createApp } from 'vue'
import App from './App.vue'
import { vRole } from './utils/auth'
import Vue3Toastify, { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const app = createApp(App)

// Cấu hình thư viện thông báo Vue3Toastify
app.use(Vue3Toastify, {
  autoClose: 3500,
  position: 'top-right',
  theme: 'colored',
  pauseOnHover: true,
  hideProgressBar: false,
})

// Đăng ký custom directive v-role để kiểm tra quyền hạn giao diện
app.directive('role', vRole)

app.mount('#app')
