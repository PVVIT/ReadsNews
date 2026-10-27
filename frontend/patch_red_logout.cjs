const fs = require('fs');

let content = fs.readFileSync('d:/ThucTap/ReadsNews/frontend/src/components/ReaderHome.vue', 'utf8');

// 1. Sửa hàm confirmLogout
const searchOldFn = 'async function confirmLogout()';
const idxFn = content.indexOf(searchOldFn);
if (idxFn !== -1) {
  const closeBraceIdx = content.indexOf('\n}', idxFn);
  const newFn = `async function confirmLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true
  try {
    await authService.logout()
  } catch (e) {
    // Không gián đoạn nếu network có lỗi
  }
  auth.clearAuth()
  toast.success('Đã đăng xuất tài khoản thành công!', {
    autoClose: 2000,
  })
  setTimeout(() => {
    showLogoutModal.value = false
    window.location.href = '/'
  }, 800)
}`;
  content = content.substring(0, idxFn) + newFn + content.substring(closeBraceIdx + 2);
}

// 2. Sửa nút modal Đăng xuất màu đỏ
const searchOldModal = 'class="mt-5 flex items-center justify-center gap-3"';
const idxModal = content.indexOf(searchOldModal);
if (idxModal !== -1) {
  const endModalIdx = content.indexOf('</div>', idxModal);
  const newModalButtons = `class="mt-5 flex items-center justify-center gap-3">
            <button @click="showLogoutModal = false" :disabled="isLoggingOut"
              class="h-10 rounded-xl border border-slate-200 bg-white px-5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer">
              Ở lại
            </button>
            <button @click="confirmLogout" :disabled="isLoggingOut"
              class="inline-flex items-center justify-center gap-2 h-10 min-w-[125px] rounded-xl bg-rose-600 px-5 text-xs font-bold text-white shadow-md shadow-rose-600/25 transition disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer hover:bg-rose-700 active:scale-98">
              <svg v-if="isLoggingOut" class="size-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
              </svg>
              <span>{{ isLoggingOut ? 'Đang thoát...' : 'Đăng xuất' }}</span>
            </button>`;
  content = content.substring(0, idxModal) + newModalButtons + content.substring(endModalIdx);
}

fs.writeFileSync('d:/ThucTap/ReadsNews/frontend/src/components/ReaderHome.vue', content, 'utf8');
console.log('Successfully updated ReaderHome with loading on the RED logout button!');

