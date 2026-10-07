import { createApp, defineComponent, h } from 'vue'
import App from './App.vue'
import './assets/admin.css'
import { vRole } from './utils/auth'
import Vue3Toastify, { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const app = createApp(App)

const RouterLinkCompat = defineComponent({
  name: 'RouterLink',
  inheritAttrs: false,
  props: {
    to: { type: String, required: true },
  },
  setup(props, { attrs, slots }) {
    return () => h('a', { ...attrs, href: props.to }, slots.default?.())
  },
})

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
app.component('RouterLink', RouterLinkCompat)

app.mount('#app')
