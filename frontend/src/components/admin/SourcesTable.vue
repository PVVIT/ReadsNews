<script setup>
import { computed } from 'vue'
import BulkActionBar from './BulkActionBar.vue'
import PaginationFooter from './PaginationFooter.vue'

const props = defineProps({
    sources: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    },
    isLoading: {
        type: Boolean,
        default: false
    },
    crawlingSourceId: {
        type: [Number, String],
        default: null
    },
    meta: {
        type: Object,
        default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10, active_count: 0 })
    },
    search: {
        type: String,
        default: ''
    },
    filterCategory: {
        type: String,
        default: ''
    },
    filterStatus: {
        type: String,
        default: 'all'
    },
    selectedSources: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits([
    'update:search',
    'update:filterCategory',
    'update:filterStatus',
    'update:selectedSources',
    'open-create-modal',
    'edit-source',
    'delete-source',
    'crawl-source',
    'bulk-delete',
    'toggle-status',
    'change-page'
])

const isAllSelected = computed(() => {
    return props.sources.length > 0 && props.sources.every(s => props.selectedSources.includes(s.id))
})

function toggleSelectAll(e) {
    if (e.target.checked) {
        const pageIds = props.sources.map(s => s.id)
        emit('update:selectedSources', Array.from(new Set([...props.selectedSources, ...pageIds])))
    } else {
        const pageIds = new Set(props.sources.map(s => s.id))
        emit('update:selectedSources', props.selectedSources.filter(id => !pageIds.has(id)))
    }
}

function handleCheckbox(id, isChecked) {
    if (isChecked) {
        emit('update:selectedSources', [...props.selectedSources, id])
    } else {
        emit('update:selectedSources', props.selectedSources.filter(item => item !== id))
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Chưa crawl'
    const d = new Date(dateString)
    if (isNaN(d.getTime())) return 'Chưa crawl'
    return d.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
        <!-- Header & Filters -->
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between lg:px-6">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-display text-base font-bold text-slate-900">Quản lý nguồn báo</h3>
                    <span v-if="meta.active_count !== undefined" class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                        {{ meta.active_count }} hoạt động
                    </span>
                </div>
                <p class="text-xs text-slate-400">Quản lý các nguồn tin tức RSS, crawler và liên kết báo chí</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Search input -->
                <div class="relative w-48 sm:w-56">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </span>
                    <input :value="search" @input="$emit('update:search', $event.target.value)" type="text"
                        class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 ps-8.5 pe-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        placeholder="Tìm nguồn, domain, RSS..." />
                </div>

                <!-- Category Filter -->
                <select :value="filterCategory" @change="$emit('update:filterCategory', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả chuyên mục</option>
                    <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                        {{ cat.name }}
                    </option>
                </select>

                <!-- Status Filter -->
                <select :value="filterStatus" @change="$emit('update:filterStatus', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Tạm vô hiệu</option>
                </select>

            </div>
        </div>

        <!-- Bulk Action Bar -->
        <BulkActionBar
            :count="selectedSources.length"
            label="nguồn báo"
            actionLabel="nguồn"
            @clear="$emit('update:selectedSources', [])"
            @delete="$emit('bulk-delete')"
        />

        <!-- Sources Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs text-slate-600">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="w-10 px-4 py-3 text-center">
                            <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </th>
                        <th class="px-4 py-3 text-start">Nguồn báo</th>
                        <th class="px-4 py-3 text-start">Website / RSS Feed</th>
                        <th class="px-4 py-3 text-start">Chuyên mục mặc định</th>
                        <th class="px-4 py-3 text-center">Số bài viết</th>
                        <th class="px-4 py-3 text-start">Crawl gần nhất</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="isLoading">
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <div class="inline-flex items-center gap-2">
                                <svg class="size-5 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                <span>Đang tải danh sách nguồn báo...</span>
                            </div>
                        </td>
                    </tr>
                    <template v-else>
                        <tr v-if="sources.length === 0">
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="2" y1="12" x2="22" y2="12"/>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium">Không tìm thấy nguồn báo nào phù hợp</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="source in sources" :key="source.id"
                            :class="['transition hover:bg-slate-50/70', selectedSources.includes(source.id) ? 'bg-indigo-50/40' : '']">
                        <!-- Checkbox -->
                        <td class="w-10 px-4 py-3.5 text-center">
                            <input type="checkbox" :checked="selectedSources.includes(source.id)"
                                @change="handleCheckbox(source.id, $event.target.checked)"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </td>

                        <!-- Name & Slug -->
                        <td class="px-4 py-3.5 font-medium text-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ source.name }}</div>
                                    <div class="font-mono text-[11px] text-slate-400">{{ source.slug }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Website & RSS -->
                        <td class="px-4 py-3.5">
                            <div class="space-y-1">
                                <a :href="source.website_url" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-slate-600 hover:text-indigo-600 hover:underline max-w-[220px] truncate"
                                    :title="source.website_url">
                                    <svg class="size-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="2" y1="12" x2="22" y2="12"/>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                    </svg>
                                    <span class="truncate">{{ source.website_url }}</span>
                                </a>

                                <div v-if="source.rss_url" class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 border border-amber-200">
                                        RSS
                                    </span>
                                    <span class="max-w-[180px] truncate text-[11px] text-slate-400" :title="source.rss_url">
                                        {{ source.rss_url }}
                                    </span>
                                    <!-- Crawl Button -->
                                    <button type="button" @click="$emit('crawl-source', source.id)"
                                        :disabled="crawlingSourceId === source.id"
                                        class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 disabled:opacity-50 transition"
                                        title="Kích hoạt crawl RSS ngay">
                                        <svg :class="['size-3', crawlingSourceId === source.id ? 'animate-spin' : '']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                                        </svg>
                                        {{ crawlingSourceId === source.id ? 'Đang crawl...' : 'Crawl' }}
                                    </button>
                                </div>
                                <div v-else class="text-[11px] text-slate-400 italic">
                                    Không có RSS
                                </div>
                            </div>
                        </td>

                        <!-- Default Category -->
                        <td class="px-4 py-3.5">
                            <span v-if="source.category" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700">
                                {{ source.category.name }}
                            </span>
                            <span v-else class="text-slate-400 italic text-[11px]">Chưa gán</span>
                        </td>

                        <!-- Article Count -->
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                {{ source.articles_count ?? 0 }} bài
                            </span>
                        </td>

                        <!-- Last Crawled At -->
                        <td class="px-4 py-3.5 text-slate-500 whitespace-nowrap text-[11px]">
                            {{ formatDate(source.last_crawled_at) }}
                        </td>

                        <!-- Status Toggle Button -->
                        <td class="px-4 py-3.5 text-center">
                            <button type="button" @click="$emit('toggle-status', source.id)"
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition',
                                    source.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                        : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'
                                ]">
                                <span :class="['size-1.5 rounded-full', source.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ source.is_active ? 'Hoạt động' : 'Tạm ẩn' }}
                            </button>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3.5 text-end whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" @click="$emit('edit-source', source)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                    title="Chỉnh sửa nguồn báo">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <button type="button" @click="$emit('delete-source', source)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition"
                                    title="Xóa nguồn báo">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <PaginationFooter
            :current-page="meta.current_page || 1"
            :last-page="meta.last_page || 1"
            :total="meta.total || 0"
            label="nguồn báo"
            :is-loading="isLoading"
            @change-page="$emit('change-page', $event)"
        />
    </section>
</template>

