<script setup>
import { computed } from 'vue'
import BulkActionBar from './BulkActionBar.vue'
import PaginationFooter from './PaginationFooter.vue'

const props = defineProps({
    articles: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    },
    allCategories: {
        type: Array,
        default: () => []
    },
    sources: {
        type: Array,
        default: () => []
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
    selectedCategory: {
        type: String,
        default: 'all'
    },
    selectedStatus: {
        type: String,
        default: 'all'
    },
    selectedArticles: {
        type: Array,
        default: () => []
    },
    isCrawling: {
        type: Boolean,
        default: false
    },
    crawlSourceId: {
        type: [Number, String],
        default: null
    }
})

const emit = defineEmits([
    'update:search',
    'update:selectedCategory',
    'update:selectedStatus',
    'update:selectedArticles',
    'crawl-rss',
    'create-article',
    'edit-article',
    'delete-article',
    'bulk-delete',
    'toggle-publish',
    'change-page'
])

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0)
}

function formatDate(value) {
    if (!value) return 'Bản nháp'
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(value))
}

function sourceName(id) {
    return props.sources.find((source) => source.id === Number(id))?.name || 'Chưa chọn nguồn'
}

function categoryName(id) {
    if (!id) return 'Chưa phân loại'
    const list = props.allCategories.length ? props.allCategories : props.categories
    return list.find((category) => category.id === Number(id))?.name || 'Chưa phân loại'
}

const isAllArticlesSelected = computed(() => {
    return props.articles.length > 0 && props.articles.every(a => props.selectedArticles.includes(a.id))
})

function toggleSelectAll(e) {
    if (e.target.checked) {
        const pageIds = props.articles.map(a => a.id)
        emit('update:selectedArticles', Array.from(new Set([...props.selectedArticles, ...pageIds])))
    } else {
        const pageIds = new Set(props.articles.map(a => a.id))
        emit('update:selectedArticles', props.selectedArticles.filter(id => !pageIds.has(id)))
    }
}

function handleArticleCheckbox(id, isChecked) {
    if (isChecked) {
        emit('update:selectedArticles', [...props.selectedArticles, id])
    } else {
        emit('update:selectedArticles', props.selectedArticles.filter(item => item !== id))
    }
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
        <!-- Toolbar Bộ Lọc -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex flex-1 flex-wrap items-center gap-2.5">
                <!-- Search bar -->
                <div class="relative min-w-56 flex-1 sm:max-w-xs">
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
                        placeholder="Tìm theo tiêu đề, tác giả..." />
                </div>

                <!-- Lọc Danh mục -->
                <select :value="selectedCategory" @change="$emit('update:selectedCategory', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả danh mục</option>
                    <option v-for="category in (allCategories.length ? allCategories : categories)" :key="category.id"
                        :value="String(category.id)">{{ category.name }}</option>
                </select>

                <!-- Lọc Trạng thái -->
                <select :value="selectedStatus" @change="$emit('update:selectedStatus', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="published">Đã xuất bản</option>
                    <option value="draft">Bản nháp</option>
                </select>
            </div>

            <!-- Nhóm bên phải: Crawl RSS + Thêm bài -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- Nút Crawl RSS -->
                <div v-if="sources.length" class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold text-slate-500 shrink-0">Crawl RSS:</span>
                    <button v-for="src in sources" :key="src.id" type="button"
                        @click="$emit('crawl-rss', src.id)"
                        :disabled="isCrawling"
                        :title="`Crawl tin mới từ ${src.name}`"
                        class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-bold text-sky-700 hover:bg-sky-100 disabled:opacity-50 transition">
                        <svg v-if="isCrawling && crawlSourceId === src.id"
                            class="size-3 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        <svg v-else class="size-3" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5">
                            <path d="M23 4v6h-6M1 20v-6h6" />
                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" />
                        </svg>
                        {{ src.name }}
                    </button>
                </div>

                <!-- Nút thêm bài -->
                <button @click="$emit('create-article')" type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition active:scale-95">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Thêm bài báo
                </button>

                <div class="text-xs font-semibold text-slate-500 pl-2 border-l border-slate-200">
                    <span class="font-bold text-indigo-600">{{ articles.length }}</span> bài
                </div>
            </div>
        </div>

        <!-- Thanh tác vụ xóa nhiều bài báo -->
        <BulkActionBar
            :count="selectedArticles.length"
            label="bài báo"
            actionLabel="bài"
            @clear="$emit('update:selectedArticles', [])"
            @delete="$emit('bulk-delete')"
        />

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs">
                <thead>
                    <tr
                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="w-11 px-4 py-3.5 text-center">
                            <input type="checkbox" :checked="isAllArticlesSelected" @change="toggleSelectAll"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </th>
                        <th class="px-6 py-3.5 text-start">Bài báo</th>
                        <th class="px-4 py-3.5 text-start">Danh mục</th>
                        <th class="px-4 py-3.5 text-start">Nguồn</th>
                        <th class="px-4 py-3.5 text-start">Tương tác</th>
                        <th class="px-4 py-3.5 text-start">Ngày xuất bản</th>
                        <th class="px-4 py-3.5 text-start">Trạng thái</th>
                        <th class="px-6 py-3.5 text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <!-- Loading state -->
                    <tr v-if="isLoading">
                        <td colspan="8" class="py-14 text-center text-slate-400">
                            <div class="inline-flex items-center gap-2 font-medium">
                                <svg class="size-4 animate-spin text-sky-500" viewBox="0 0 24 24"
                                    fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v8H4z" />
                                </svg>
                                Đang tải dữ liệu bài báo...
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!isLoading" v-for="article in articles" :key="article.id"
                        :class="['transition hover:bg-slate-50/80', selectedArticles.includes(article.id) ? 'bg-indigo-50/30' : '']">
                        <!-- Ô chọn hàng loạt -->
                        <td class="w-11 px-4 py-4 text-center">
                            <input type="checkbox" :checked="selectedArticles.includes(article.id)"
                                @change="handleArticleCheckbox(article.id, $event.target.checked)"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </td>

                        <!-- Tiêu đề + Thumbnail -->
                        <td class="px-6 py-4 max-w-sm">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-11 shrink-0 overflow-hidden rounded-lg bg-slate-100 border border-slate-200/60">
                                    <img v-if="article.thumbnail" :src="article.thumbnail"
                                        :alt="article.title" class="size-full object-cover" />
                                    <div v-else
                                        class="flex size-full items-center justify-center text-slate-400">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.8">
                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                            <path d="m21 15-5-5L5 21" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-900" :title="article.title">
                                        {{ article.title }}</p>
                                    <p class="mt-0.5 truncate font-mono text-[10px] text-slate-400">/{{
                                        article.slug }}</p>
                                    <span v-if="article.ai_summary"
                                        class="mt-1 inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.2 text-[9px] font-bold text-indigo-600">
                                        ✨ Có AI Tóm tắt
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Danh mục -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span
                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                                {{ categoryName(article.category_id) }}
                            </span>
                        </td>

                        <!-- Nguồn -->
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600 font-medium">
                            {{ sourceName(article.source_id) }}
                        </td>

                        <!-- Tương tác -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="font-bold text-slate-800 tabular-nums">{{
                                formatNumber(article.view_count) }} <span
                                    class="font-normal text-slate-400 text-[10px]">xem</span></div>
                            <div class="text-[11px] text-indigo-600 font-semibold">{{
                                formatNumber(article.listen_count) }} <span
                                    class="text-slate-400 font-normal text-[10px]">nghe</span></div>
                        </td>

                        <!-- Ngày xuất bản -->
                        <td class="px-4 py-4 whitespace-nowrap text-[11px] text-slate-500">
                            {{ formatDate(article.published_at) }}
                        </td>

                        <!-- Trạng thái -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <button @click="$emit('toggle-publish', article)"
                                :title="article.published_at ? 'Nhấn để chuyển thành bản nháp' : 'Nhấn để xuất bản'"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold cursor-pointer transition hover:opacity-80 active:scale-95',
                                    article.published_at
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60 hover:bg-emerald-100'
                                        : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60 hover:bg-amber-100'
                                ]">
                                <span class="size-1.5 rounded-full"
                                    :class="article.published_at ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                {{ article.published_at ? 'Đã xuất bản' : 'Bản nháp' }}
                            </button>
                        </td>

                        <!-- Thao tác Sửa / Xóa -->
                        <td class="px-6 py-4 text-end whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="$emit('edit-article', article)" title="Chỉnh sửa bài báo"
                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                    </svg>
                                </button>
                                <button @click="$emit('delete-article', article)" title="Xóa bài báo"
                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path
                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="!isLoading && !articles.length">
                        <td colspan="8" class="py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="size-10 text-slate-300 mb-2" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="m21 21-4.35-4.35" />
                                </svg>
                                <p class="font-semibold text-slate-600">Không tìm thấy bài báo phù hợp
                                </p>
                                <p class="text-xs text-slate-400 mt-1">Thử đổi từ khóa hoặc bộ lọc danh
                                    mục/trạng thái.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Phân trang Footer -->
        <PaginationFooter
            :current-page="meta.current_page"
            :last-page="meta.last_page"
            :total="meta.total"
            label="bài báo"
            :is-loading="isLoading"
            @change-page="$emit('change-page', $event)"
        />
    </section>
</template>

