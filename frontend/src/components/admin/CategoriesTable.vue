<script setup>
import { computed } from 'vue'
import BulkActionBar from './BulkActionBar.vue'
import PaginationFooter from './PaginationFooter.vue'

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    allCategories: {
        type: Array,
        default: () => []
    },
    articles: {
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
    selectedCategories: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits([
    'update:search',
    'update:selectedCategories',
    'edit-category',
    'delete-category',
    'bulk-delete',
    'toggle-status',
    'change-page'
])

function categoryName(id) {
    if (!id) return 'Chưa phân loại'
    const list = props.allCategories.length ? props.allCategories : props.categories
    return list.find((category) => category.id === Number(id))?.name || 'Chưa phân loại'
}

function categoryCount(id) {
    return props.articles.filter((article) => article.category_id === id).length
}

const isAllCategoriesSelected = computed(() => {
    return props.categories.length > 0 && props.categories.every(c => props.selectedCategories.includes(c.id))
})

function toggleSelectAll(e) {
    if (e.target.checked) {
        const pageIds = props.categories.map(c => c.id)
        emit('update:selectedCategories', Array.from(new Set([...props.selectedCategories, ...pageIds])))
    } else {
        const pageIds = new Set(props.categories.map(c => c.id))
        emit('update:selectedCategories', props.selectedCategories.filter(id => !pageIds.has(id)))
    }
}

function handleCategoryCheckbox(id, isChecked) {
    if (isChecked) {
        emit('update:selectedCategories', [...props.selectedCategories, id])
    } else {
        emit('update:selectedCategories', props.selectedCategories.filter(item => item !== id))
    }
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
        <div
            class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="font-display text-base font-bold text-slate-900">Danh mục tin tức</h3>
                <p class="text-xs text-slate-400">Cấu trúc các chuyên mục hiển thị trên thanh menu chính
                </p>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative w-60">
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
                        placeholder="Tìm danh mục..." />
                </div>
            </div>
        </div>

        <!-- Thanh tác vụ xóa nhiều danh mục -->
        <BulkActionBar
            :count="selectedCategories.length"
            label="danh mục"
            actionLabel="danh mục"
            @clear="$emit('update:selectedCategories', [])"
            @delete="$emit('bulk-delete')"
        />

        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs">
                <thead>
                    <tr
                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="w-11 px-4 py-3.5 text-center">
                            <input type="checkbox" :checked="isAllCategoriesSelected" @change="toggleSelectAll"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </th>
                        <th class="px-6 py-3.5 text-start">Tên danh mục</th>
                        <th class="px-4 py-3.5 text-start">Slug</th>
                        <th class="px-4 py-3.5 text-start">Danh mục cha</th>
                        <th class="px-4 py-3.5 text-start">Số bài viết</th>
                        <th class="px-4 py-3.5 text-start">Thứ tự</th>
                        <th class="px-4 py-3.5 text-start">Trạng thái</th>
                        <th class="px-6 py-3.5 text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="isLoading">
                        <td colspan="8" class="py-14 text-center text-slate-400">
                            <div class="inline-flex items-center gap-2 font-medium">
                                <svg class="size-4 animate-spin text-sky-500" viewBox="0 0 24 24"
                                    fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Đang tải dữ liệu danh mục...
                            </div>
                        </td>
                    </tr>

                    <template v-else>
                        <tr v-for="category in categories" :key="category.id"
                            :class="['transition hover:bg-slate-50/80', selectedCategories.includes(category.id) ? 'bg-indigo-50/30' : '']">
                            <td class="w-11 px-4 py-4 text-center">
                                <input type="checkbox" :checked="selectedCategories.includes(category.id)"
                                    @change="handleCategoryCheckbox(category.id, $event.target.checked)"
                                    class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ category.name }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400 max-w-xs truncate">{{
                                    category.description || 'Chưa có mô tả' }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span
                                    class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">/{{
                                        category.slug }}</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-slate-500">
                                {{ category.parent_id ? categoryName(category.parent_id) : '— (Gốc)' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-bold text-indigo-600 tabular-nums">{{
                                    category.articles_count ?? categoryCount(category.id) }} bài</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium text-slate-600 tabular-nums">
                                {{ category.sort_order }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <button @click="$emit('toggle-status', category)" :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80 cursor-pointer',
                                    category.is_active
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60'
                                        : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200/60'
                                ]">
                                    <span class="size-1.5 rounded-full"
                                        :class="category.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    {{ category.is_active ? 'Đang hiển thị' : 'Đã ẩn' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-end whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="$emit('edit-category', category)" title="Sửa danh mục"
                                        class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                        </svg>
                                    </button>
                                    <button @click="$emit('delete-category', category)" title="Xóa danh mục"
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

                        <tr v-if="!categories.length">
                            <td colspan="8" class="py-14 text-center text-slate-400">
                                Chưa có danh mục nào phù hợp với tìm kiếm.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Phân trang Footer - Categories -->
        <PaginationFooter
            :current-page="meta.current_page"
            :last-page="meta.last_page"
            :total="meta.total"
            label="danh mục"
            :is-loading="isLoading"
            @change-page="$emit('change-page', $event)"
        />
    </section>
</template>

