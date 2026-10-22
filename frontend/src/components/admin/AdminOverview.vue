<script setup>
const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
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
    activeUserCount: {
        type: Number,
        default: 0
    },
    publishedCount: {
        type: Number,
        default: 0
    },
    totalViews: {
        type: Number,
        default: 0
    },
    totalListens: {
        type: Number,
        default: 0
    }
})

defineEmits(['set-section'])

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0)
}

function formatDateShort(value) {
    if (!value) return 'Bản nháp'
    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value))
}

function sourceName(id) {
    return props.sources.find((source) => source.id === Number(id))?.name || 'Chưa chọn nguồn'
}

function categoryName(id) {
    if (!id) return 'Chưa phân loại'
    const list = props.allCategories.length ? props.allCategories : props.categories
    return list.find((category) => category.id === Number(id))?.name || 'Chưa phân loại'
}

function categoryCount(id) {
    return props.articles.filter((article) => article.category_id === id).length
}
</script>

<template>
    <div>
        <!-- 4 Thẻ Thống Kê Chính -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Thống kê tổng quan">
            <!-- Card 1: Người Dùng -->
            <article
                class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Người dùng đã đăng ký</span>
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-200/60 transition group-hover:scale-105">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <strong class="font-display text-2xl font-black text-slate-900">{{
                        formatNumber(users.length) }}</strong>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200/50">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                        {{ activeUserCount }} đang hoạt động
                    </span>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">Tăng trưởng người dùng ổn định</p>
            </article>

            <!-- Card 2: Bài Báo -->
            <article
                class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Tổng số bài báo</span>
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-200/60 transition group-hover:scale-105">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                            <path d="M8 7h8M8 11h8M8 15h5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <strong class="font-display text-2xl font-black text-slate-900">{{
                        formatNumber(articles.length) }}</strong>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 ring-1 ring-indigo-200/50">
                        {{ publishedCount }} đã xuất bản
                    </span>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">{{ articles.length - publishedCount }} bài đang ở
                    dạng bản nháp</p>
            </article>

            <!-- Card 3: Lượt Xem & Lượt Nghe -->
            <article
                class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Lượt đọc & nghe AI</span>
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-200/60 transition group-hover:scale-105">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M3 18v-6a9 9 0 0 1 18 0v6" />
                            <path
                                d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <strong class="font-display text-2xl font-black text-slate-900">{{
                        formatNumber(totalViews) }}</strong>
                    <span class="text-[11px] font-bold text-slate-500">lượt đọc</span>
                </div>
                <p class="mt-2 text-[11px] font-semibold text-amber-600">{{ formatNumber(totalListens) }}
                    lượt nghe audio AI</p>
            </article>

            <!-- Card 4: Danh Mục Tin -->
            <article
                class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Chủ đề & danh mục</span>
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-200/60 transition group-hover:scale-105">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <strong class="font-display text-2xl font-black text-slate-900">{{ categories.length
                    }}</strong>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200/50">
                        {{ categories.filter((item) => item.is_active).length }} đang hiển thị
                    </span>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">Phân loại tin bài đồng bộ</p>
            </article>
        </section>

        <!-- Biểu Đồ & Phân Bổ Nội Dung -->
        <section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <!-- Cột Trái: Biểu Đồ Lượt Đọc & Nghe Tuần Qua (2/3) -->
            <article class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm xl:col-span-2">
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="font-display text-base font-bold text-slate-900">Mức độ tương tác độc giả
                        </h3>
                        <p class="text-xs text-slate-400">Thống kê lượt xem bài viết theo các ngày trong
                            tuần</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600">
                            <span class="size-2.5 rounded-full bg-indigo-600"></span>
                            Lượt xem
                        </span>
                        <select
                            class="h-8 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-xs font-medium text-slate-600 focus:outline-none">
                            <option>7 ngày gần nhất</option>
                            <option>30 ngày gần nhất</option>
                        </select>
                    </div>
                </div>

                <!-- Cột Biểu Đồ Minh Họa Dạng Bar Chart Hiện Đại -->
                <div class="mt-8 flex h-52 items-end justify-between gap-2 sm:gap-4 px-2">
                    <div v-for="(bar, index) in [
                        { day: 'Thứ 2', height: 'h-24', value: '5,240' },
                        { day: 'Thứ 3', height: 'h-32', value: '7,410' },
                        { day: 'Thứ 4', height: 'h-28', value: '6,180' },
                        { day: 'Thứ 5', height: 'h-40', value: '8,850' },
                        { day: 'Thứ 6', height: 'h-36', value: '7,230' },
                        { day: 'Thứ 7', height: 'h-48', value: '11,400' },
                        { day: 'Chủ nhật', height: 'h-44', value: '9,820' },
                    ]" :key="bar.day"
                        class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <span
                            class="text-[10px] font-bold text-slate-400 opacity-0 transition group-hover:opacity-100 group-hover:-translate-y-1">{{
                                bar.value }}</span>
                        <div :class="[
                            'w-full max-w-10 rounded-t-xl transition-all duration-300 group-hover:brightness-110',
                            bar.height,
                            index === 5
                                ? 'bg-gradient-to-t from-indigo-700 to-indigo-500 shadow-md shadow-indigo-500/25'
                                : 'bg-indigo-100 group-hover:bg-indigo-200'
                        ]"></div>
                        <span class="pt-1 text-[11px] font-semibold text-slate-500">{{ bar.day }}</span>
                    </div>
                </div>

                <div
                    class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-400">
                    <span>Trung bình mỗi bài báo đạt 6,800 lượt tiếp cận độc giả</span>
                    <span class="font-semibold text-emerald-600">+18.4% so với tuần trước</span>
                </div>
            </article>

            <!-- Cột Phải: Phân Bổ Theo Danh Mục (1/3) -->
            <article class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="font-display text-base font-bold text-slate-900">Phân bổ danh mục</h3>
                        <p class="text-xs text-slate-400">Số lượng bài theo từng chủ đề</p>
                    </div>
                    <button @click="$emit('set-section', 'categories')"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                        Quản lý →
                    </button>
                </div>

                <div class="mt-5 flex-1 space-y-4">
                    <div v-for="category in categories" :key="category.id">
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">{{ category.name }}</span>
                            <span class="font-bold text-slate-500 tabular-nums">{{
                                categoryCount(category.id) }} bài</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div :class="[
                                'h-full rounded-full transition-all duration-500',
                                category.id % 2 === 1 ? 'bg-gradient-to-r from-indigo-500 to-indigo-600' : 'bg-gradient-to-r from-violet-500 to-violet-600'
                            ]"
                                :style="{ width: `${Math.max(12, (categoryCount(category.id) / Math.max(articles.length, 1)) * 100)}%` }">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-slate-50 p-4 border border-slate-100 text-xs text-slate-500">
                    <div class="flex justify-between font-semibold text-slate-700">
                        <span>Tổng danh mục: {{ categories.length }}</span>
                        <span class="text-indigo-600">{{ publishedCount }} bài đang online</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Danh Sách Bài Báo Cập Nhật Gần Đây -->
        <section class="mt-6 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
            <div
                class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-6 py-4.5">
                <div>
                    <h3 class="font-display text-base font-bold text-slate-900">Bài báo cập nhật mới nhất
                    </h3>
                    <p class="text-xs text-slate-400">Danh sách các bài viết vừa được biên tập hoặc xuất bản
                    </p>
                </div>
                <button @click="$emit('set-section', 'articles')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                    <span>Xem toàn bộ {{ articles.length }} bài</span>
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="px-6 py-3.5 text-start">Bài viết</th>
                            <th class="px-4 py-3.5 text-start">Danh mục</th>
                            <th class="px-4 py-3.5 text-start">Lượt xem / Nghe</th>
                            <th class="px-4 py-3.5 text-start">Trạng thái</th>
                            <th class="px-6 py-3.5 text-end">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="article in articles.slice(0, 5)" :key="article.id"
                            class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4 max-w-md">
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
                                        <p class="truncate font-semibold text-slate-900">{{ article.title }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">Nguồn: {{
                                            sourceName(article.source_id) }} · Tác giả: {{ article.author ||
                                                'Chưa rõ' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span
                                    class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                    {{ categoryName(article.category_id) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800 tabular-nums">{{
                                    formatNumber(article.view_count) }} <span
                                        class="font-normal text-slate-400 text-[10px]">xem</span></div>
                                <div class="text-[11px] text-indigo-600 font-semibold">{{
                                    formatNumber(article.listen_count) }} <span
                                        class="text-slate-400 font-normal text-[10px]">nghe</span></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold',
                                    article.published_at
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60'
                                        : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60'
                                ]">
                                    <span class="size-1.5 rounded-full"
                                        :class="article.published_at ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                    {{ article.published_at ? 'Đã xuất bản' : 'Bản nháp' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-end text-[11px] text-slate-400 whitespace-nowrap">
                                {{ formatDateShort(article.published_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

