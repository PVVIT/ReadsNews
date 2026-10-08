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
    const handleClick = (e) => {
      if (props.to && props.to.startsWith('/') && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
        e.preventDefault()
        window.history.pushState({}, '', props.to)
        window.dispatchEvent(new PopStateEvent('popstate'))
      }
    }
    return () => h('a', { ...attrs, href: props.to, onClick: handleClick }, slots.default?.())
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
app.component('router-link', RouterLinkCompat)

app.mount('#app')
