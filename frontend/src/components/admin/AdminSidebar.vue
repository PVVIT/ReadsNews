<script setup>
defineProps({
    activeSection: {
        type: String,
        default: 'overview'
    },
    sections: {
        type: Array,
        default: () => [
            { id: 'overview',    label: 'Tổng quan',   path: '/admin' },
            { id: 'articles',   label: 'Bài báo',      path: '/admin/articles' },
            { id: 'categories', label: 'Danh mục',     path: '/admin/categories' },
            { id: 'sources',    label: 'Nguồn báo',    path: '/admin/sources' },
            { id: 'voices',     label: 'Giọng nói', path: '/admin/voices' },
            { id: 'users',      label: 'Người dùng',   path: '/admin/users' },
        ]
    },
    articlesCount: {
        type: Number,
        default: 0
    },
    currentUser: {
        type: Object,
        default: () => ({ id: null, name: 'Admin ReadsNews', email: 'admin@readsnews.vn', role: 'admin' })
    }
})

defineEmits(['select-section', 'logout'])
</script>

<template>
    <aside
        class="fixed inset-y-0 start-0 z-30 hidden w-64 flex-col border-r border-slate-800/80 bg-slate-900 text-slate-300 lg:flex shadow-2xl">
        <!-- Brand Logo Header -->
        <div class="flex h-18 items-center justify-between border-b border-slate-800/80 px-5">
            <a href="/admin" @click.prevent="$emit('select-section', 'overview')" class="group flex items-center gap-3">
                <img src="/logo/Khuon-Tron-removebg.png" alt="ReadVn"
                    class="size-10 rounded-xl bg-white p-1 shadow-md shadow-sky-500/20 transition group-hover:scale-105 shrink-0 object-contain" />
                <div>
                    <div
                        class="flex items-center gap-1.5 font-display text-base font-extrabold tracking-tight text-white">
                        <span>Read<span class="text-orange-500">Vn</span></span>
                        <span
                            class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-bold text-sky-300 ring-1 ring-sky-500/30">AI
                            CMS</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Báo Đọc – Tin Nhanh</p>
                </div>
            </a>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto px-3.5 py-6 space-y-6">
            <div>
                <p class="px-3 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Quản trị hệ thống
                </p>
                <nav class="space-y-1" aria-label="Điều hướng chính">
                    <a v-for="section in sections" :key="section.id"
                        :href="section.path"
                        @click.prevent="$emit('select-section', section.id)"
                        :class="[
                        'group flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-start text-xs font-semibold transition-all duration-150',
                        activeSection === section.id
                            ? 'bg-gradient-to-r from-sky-600 to-blue-700 text-white shadow-md shadow-sky-600/25 ring-1 ring-white/10'
                            : 'text-slate-400 hover:bg-slate-800/80 hover:text-white'
                    ]">
                        <!-- SVG Icons tương ứng từng section -->
                        <svg v-if="section.id === 'overview'" class="size-[18px] shrink-0" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                        <svg v-else-if="section.id === 'articles'" class="size-[18px] shrink-0" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                            <path d="M8 7h8M8 11h8M8 15h5" />
                        </svg>
                        <svg v-else-if="section.id === 'categories'" class="size-[18px] shrink-0"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        <svg v-else-if="section.id === 'sources'" class="size-[18px] shrink-0" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="2" y1="12" x2="22" y2="12" />
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                        </svg>
                        <svg v-else-if="section.id === 'voices'" class="size-[18px] shrink-0" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z" />
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                            <line x1="12" y1="19" x2="12" y2="22" />
                        </svg>
                        <svg v-else class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>

                        <span class="flex-1">{{ section.label }}</span>

                        <span v-if="section.id === 'articles'" :class="[
                            'rounded-md px-1.5 py-0.5 text-[10px] font-bold tabular-nums',
                            activeSection === section.id ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400 group-hover:text-slate-300'
                        ]">
                            {{ articlesCount }}
                        </span>
                    </a>
                </nav>
            </div>

            <!-- Thao tác nhanh & Chuyển hướng -->
            <div>
                <p class="px-3 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Liên kết ngoài
                </p>
                <a href="/"
                    class="group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-400 hover:bg-slate-800/80 hover:text-white transition">
                    <span class="flex items-center gap-2.5">
                        <svg class="size-4 text-indigo-400 transition group-hover:-translate-x-0.5"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                        Về trang đọc báo
                    </span>
                    <span
                        class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] text-slate-400 group-hover:text-slate-300">Reader</span>
                </a>
            </div>

            <!-- Widget Trạng thái AI Engine -->
            <div class="rounded-xl border border-slate-800 bg-slate-800/50 p-3.5 text-xs">
                <div class="flex items-center justify-between pb-2">
                    <span class="flex items-center gap-2 font-medium text-slate-300">
                        <span
                            class="size-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                        AI TTS Engine
                    </span>
                    <span class="text-[10px] font-semibold text-emerald-400">Online</span>
                </div>
                <p class="text-[11px] leading-relaxed text-slate-400">5 giọng đọc Tiếng Việt & tóm tắt tự động sẵn
                    sàng.</p>
            </div>
        </div>

        <!-- Profile & Nút Đăng xuất ở chân Sidebar -->
        <div class="border-t border-slate-800/90 p-3.5">
            <div class="flex items-center gap-3 rounded-xl bg-slate-800/60 p-2.5 border border-slate-700/40">
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white shadow-inner">
                    {{ currentUser.name ? currentUser.name.slice(0, 2).toUpperCase() : 'AD' }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-white">{{ currentUser.name }}</p>
                    <p class="truncate text-[10px] text-slate-400 capitalize">{{ currentUser.role === 'admin' ?
                        'Quản trị viên' : currentUser.role }}</p>
                </div>
                <button @click="$emit('logout')" title="Đăng xuất"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                </button>
            </div>
        </div>
    </aside>
</template>

