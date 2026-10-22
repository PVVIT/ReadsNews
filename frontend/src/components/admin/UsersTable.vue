<script setup>
import { computed } from 'vue'
import BulkActionBar from './BulkActionBar.vue'
import PaginationFooter from './PaginationFooter.vue'

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    currentUser: {
        type: Object,
        default: () => ({ id: null })
    },
    isLoading: {
        type: Boolean,
        default: false
    },
    meta: {
        type: Object,
        default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 })
    },
    search: {
        type: String,
        default: ''
    },
    selectedRole: {
        type: String,
        default: 'all'
    },
    selectedUsers: {
        type: Array,
        default: () => []
    },
    activeUserCount: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits([
    'update:search',
    'update:selectedRole',
    'update:selectedUsers',
    'refresh',
    'change-role',
    'toggle-status',
    'delete-user',
    'bulk-delete',
    'change-page'
])

function formatDateShort(value) {
    if (!value) return 'Bản nháp'
    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value))
}

function roleBadgeClasses(role) {
    if (role === 'admin') return 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200/60'
    if (role === 'editor') return 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200/60'
    return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200/60'
}

const isAllUsersSelected = computed(() => {
    const selectable = props.users.filter(u => u.id !== props.currentUser?.id)
    return selectable.length > 0 && selectable.every(u => props.selectedUsers.includes(u.id))
})

function toggleSelectAll(e) {
    if (e.target.checked) {
        const pageIds = props.users.filter(u => u.id !== props.currentUser?.id).map(u => u.id)
        emit('update:selectedUsers', Array.from(new Set([...props.selectedUsers, ...pageIds])))
    } else {
        const pageIds = new Set(props.users.map(u => u.id))
        emit('update:selectedUsers', props.selectedUsers.filter(id => !pageIds.has(id)))
    }
}

function handleUserCheckbox(id, isChecked) {
    if (isChecked) {
        emit('update:selectedUsers', [...props.selectedUsers, id])
    } else {
        emit('update:selectedUsers', props.selectedUsers.filter(item => item !== id))
    }
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
        <div
            class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="font-display text-base font-bold text-slate-900">Danh sách tài khoản</h3>
                <p class="text-xs text-slate-400">{{ meta.total }} tài khoản trong hệ thống · {{
                    activeUserCount }} đang hoạt động</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Nút Làm mới danh sách -->
                <button @click="$emit('refresh')" :disabled="isLoading"
                    title="Tải lại danh sách người dùng từ máy chủ"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 cursor-pointer">
                    <svg class="size-3.5" :class="{ 'animate-spin text-indigo-600': isLoading }"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                        <path d="M3 3v5h5" />
                        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                        <path d="M16 21h5v-5" />
                    </svg>
                    <span class="hidden sm:inline">Làm mới</span>
                </button>

                <!-- Ô tìm kiếm -->
                <div class="relative w-52 sm:w-56">
                    <span
                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </span>
                    <input :value="search" @input="$emit('update:search', $event.target.value)" type="text"
                        class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 ps-8.5 pe-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        placeholder="Tìm tên, email..." />
                </div>

                <!-- Lọc theo vai trò -->
                <select :value="selectedRole" @change="$emit('update:selectedRole', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                    <option value="user">User</option>
                </select>
            </div>
        </div>

        <!-- Trạng thái loading ban đầu -->
        <div v-if="isLoading && !users.length" class="p-12 text-center text-slate-400">
            <div class="inline-flex items-center gap-2 text-xs font-semibold text-indigo-600">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25" />
                    <path d="M12 2a10 10 0 0 1 10 10" />
                </svg>
                <span>Đang tải danh sách người dùng từ hệ thống...</span>
            </div>
        </div>

        <template v-else>
            <!-- Thanh tác vụ xóa nhiều người dùng -->
            <BulkActionBar
                :count="selectedUsers.length"
                label="tài khoản người dùng"
                actionLabel="tài khoản"
                @clear="$emit('update:selectedUsers', [])"
                @delete="$emit('bulk-delete')"
            />

            <!-- Bảng dữ liệu người dùng -->
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="w-11 px-4 py-3.5 text-center">
                                <input type="checkbox" :checked="isAllUsersSelected" @change="toggleSelectAll"
                                    class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                            </th>
                            <th class="px-6 py-3.5 text-start">Thành viên</th>
                            <th class="px-4 py-3.5 text-start">Vai trò</th>
                            <th class="px-4 py-3.5 text-start">Trạng thái</th>
                            <th class="px-4 py-3.5 text-start">Ngày tham gia</th>
                            <th class="px-4 py-3.5 text-center">Thao tác</th>
                            <th class="px-6 py-3.5 text-end">Mã định danh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in users" :key="user.id"
                            :class="['transition hover:bg-slate-50/80', selectedUsers.includes(user.id) ? 'bg-indigo-50/30' : '']">
                            <td class="w-11 px-4 py-4 text-center">
                                <input v-if="user.id !== currentUser?.id" type="checkbox"
                                    :checked="selectedUsers.includes(user.id)"
                                    @change="handleUserCheckbox(user.id, $event.target.checked)"
                                    class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                <span v-else class="text-slate-300 text-xs">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-9.5 items-center justify-center rounded-xl bg-slate-100 font-bold text-slate-700 border border-slate-200">
                                        {{ user.name.split(' ').map((p) => p[0]).slice(-2).join('') }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <p class="font-bold text-slate-900">{{ user.name }}</p>
                                            <span v-if="user.id === currentUser?.id"
                                                class="rounded bg-indigo-50 px-1.5 py-0.2 text-[9px] font-bold text-indigo-600 ring-1 ring-indigo-200/50">
                                                Bạn
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <select :value="user.role" :disabled="user.id === currentUser?.id"
                                    @change="$emit('change-role', { user, role: $event.target.value })" :class="[
                                        'h-7 rounded-lg px-2 py-0.5 text-[11px] font-bold border-0 cursor-pointer capitalize focus:ring-2 focus:ring-indigo-300 disabled:opacity-60 disabled:cursor-not-allowed',
                                        roleBadgeClasses(user.role)
                                    ]">
                                    <option value="user">User</option>
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <button @click="$emit('toggle-status', user)"
                                    :disabled="user.id === currentUser?.id"
                                    :title="user.id === currentUser?.id ? 'Không thể tự khóa tài khoản của bạn' : (user.is_active ? 'Nhấp để khóa tài khoản' : 'Nhấp để kích hoạt tài khoản')"
                                    :class="[
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer',
                                        user.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60'
                                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/60'
                                    ]">
                                    <span class="size-1.5 rounded-full"
                                        :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    {{ user.is_active ? 'Hoạt động' : 'Đã khóa' }}
                                </button>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-slate-500 text-[11px]">
                                {{ formatDateShort(user.created_at) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <button v-if="user.id !== currentUser?.id" @click="$emit('delete-user', user)"
                                    title="Xóa tài khoản người dùng"
                                    class="inline-flex size-7 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path
                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                                <span v-else class="text-[11px] text-slate-400 italic">—</span>
                            </td>
                            <td class="px-6 py-4 text-end font-mono text-[11px] text-slate-400">
                                #US-{{ String(user.id).padStart(3, '0') }}
                            </td>
                        </tr>

                        <tr v-if="!users.length">
                            <td colspan="7" class="py-14 text-center text-slate-400">
                                Không tìm thấy tài khoản người dùng phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Phân trang Footer - Users -->
            <PaginationFooter
                :current-page="meta.current_page"
                :last-page="meta.last_page"
                :total="meta.total"
                label="người dùng"
                :is-loading="isLoading"
                @change-page="$emit('change-page', $event)"
            />
        </template>
    </section>
</template>

