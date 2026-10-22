<script setup>
defineProps({
    pageTitle: {
        type: String,
        default: ''
    },
    searchQuery: {
        type: String,
        default: ''
    },
    currentUser: {
        type: Object,
        default: () => ({ name: 'Admin ReadsNews', role: 'admin' })
    },
    sections: {
        type: Array,
        default: () => [
            { id: 'overview', label: 'Tổng quan' },
            { id: 'articles', label: 'Bài báo' },
            { id: 'categories', label: 'Danh mục' },
            { id: 'users', label: 'Người dùng' },
        ]
    },
    activeSection: {
        type: String,
        default: 'overview'
    }
})

defineEmits(['update:searchQuery', 'select-section'])
</script>

<template>
    <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
        <div class="flex h-18 items-center justify-between gap-4 px-4 sm:px-8 xl:px-10">
            <!-- Tiêu đề & Breadcrumb -->
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="lg:hidden flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div
                        class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <span>ReadsNews</span>
                        <span>/</span>
                        <span class="text-indigo-600 font-bold">Admin Portal</span>
                    </div>
                    <h1 class="truncate font-display text-lg font-bold text-slate-900 tracking-tight">{{
                        pageTitle }}</h1>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-3">
                <!-- Quick Search Input -->
                <div class="relative hidden sm:block w-64 md:w-72">
                    <span
                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </span>
                    <input :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)" type="text"
                        class="h-9.5 w-full rounded-xl border border-slate-200 bg-slate-50/80 ps-9 pe-9 text-xs text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        placeholder="Tìm kiếm nhanh bài viết, người dùng..." />
                    <kbd v-if="!searchQuery"
                        class="pointer-events-none absolute inset-y-0 end-0 my-auto me-2.5 flex h-5 items-center rounded border border-slate-200 bg-white px-1.5 font-mono text-[9px] font-medium text-slate-400">⌘K</kbd>
                    <button v-else @click="$emit('update:searchQuery', '')"
                        class="absolute inset-y-0 end-0 my-auto me-2.5 flex size-5 items-center justify-center text-slate-400 hover:text-slate-600">×</button>
                </div>

                <!-- Back to Reader Portal Button -->
                <a href="/"
                    class="inline-flex h-9.5 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-indigo-600">
                    <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                        <polyline points="15 3 21 3 21 9" />
                        <line x1="10" y1="14" x2="21" y2="3" />
                    </svg>
                    <span class="hidden sm:inline">Xem trang báo</span>
                </a>

                <!-- Notification Bell -->
                <div class="relative">
                    <button
                        class="relative flex size-9.5 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                        </svg>
                        <span
                            class="absolute end-2 top-2 size-2 rounded-full bg-indigo-600 ring-2 ring-white"></span>
                    </button>
                </div>

                <!-- User Profile Pill -->
                <div class="flex items-center gap-2.5 ps-1 sm:ps-2">
                    <div
                        class="flex size-9.5 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-xs font-bold text-white shadow-sm">
                        {{ currentUser.name ? currentUser.name.slice(0, 2).toUpperCase() : 'AD' }}
                    </div>
                    <div class="hidden text-start md:block">
                        <p class="text-xs font-bold text-slate-900 leading-tight">{{ currentUser.name }}</p>
                        <p class="text-[10px] font-medium text-slate-400 capitalize">{{ currentUser.role }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs trên Mobile -->
        <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2.5 lg:hidden"
            aria-label="Điều hướng mobile">
            <button v-for="section in sections" :key="section.id" :class="[
                'shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                activeSection === section.id
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'text-slate-600 hover:bg-slate-100'
            ]" @click="$emit('select-section', section.id)">
                {{ section.label }}
            </button>
        </nav>
    </header>
</template>

