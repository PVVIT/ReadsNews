<script setup>
import { computed } from 'vue'
import BulkActionBar from './BulkActionBar.vue'
import PaginationFooter from './PaginationFooter.vue'

const props = defineProps({
    voices: {
        type: Array,
        default: () => []
    },
    isLoading: {
        type: Boolean,
        default: false
    },
    meta: {
        type: Object,
        default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10, active_count: 0, available_providers: [] })
    },
    search: {
        type: String,
        default: ''
    },
    filterProvider: {
        type: String,
        default: 'all'
    },
    filterGender: {
        type: String,
        default: 'all'
    },
    filterStatus: {
        type: String,
        default: 'all'
    },
    selectedVoices: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits([
    'update:search',
    'update:filterProvider',
    'update:filterGender',
    'update:filterStatus',
    'update:selectedVoices',
    'open-create-modal',
    'edit-voice',
    'delete-voice',
    'bulk-delete',
    'toggle-status',
    'change-page'
])

const isAllSelected = computed(() => {
    return props.voices.length > 0 && props.voices.every(v => props.selectedVoices.includes(v.id))
})

function toggleSelectAll(e) {
    if (e.target.checked) {
        const pageIds = props.voices.map(v => v.id)
        emit('update:selectedVoices', Array.from(new Set([...props.selectedVoices, ...pageIds])))
    } else {
        const pageIds = new Set(props.voices.map(v => v.id))
        emit('update:selectedVoices', props.selectedVoices.filter(id => !pageIds.has(id)))
    }
}

function handleCheckbox(id, isChecked) {
    if (isChecked) {
        emit('update:selectedVoices', [...props.selectedVoices, id])
    } else {
        emit('update:selectedVoices', props.selectedVoices.filter(item => item !== id))
    }
}

function getProviderBadgeClass(provider) {
    const p = (provider || '').toLowerCase()
    if (p.includes('google')) return 'bg-blue-50 text-blue-700 border-blue-200'
    if (p.includes('fpt')) return 'bg-orange-50 text-orange-700 border-orange-200'
    if (p.includes('viettel')) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    if (p.includes('zalo')) return 'bg-cyan-50 text-cyan-700 border-cyan-200'
    if (p.includes('web') || p.includes('speech')) return 'bg-violet-50 text-violet-700 border-violet-200'
    return 'bg-slate-100 text-slate-700 border-slate-200'
}

function getGenderLabel(gender) {
    if (gender === 'female') return { text: 'Nữ', class: 'bg-rose-50 text-rose-700 border-rose-200' }
    if (gender === 'male') return { text: 'Nam', class: 'bg-sky-50 text-sky-700 border-sky-200' }
    return { text: 'Trung tính', class: 'bg-slate-100 text-slate-600 border-slate-200' }
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
        <!-- Header & Filters -->
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between lg:px-6">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-display text-base font-bold text-slate-900">Quản lý giọng nói AI (TTS)</h3>
                    <span v-if="meta.active_count !== undefined" class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                        {{ meta.active_count }} hoạt động
                    </span>
                </div>
                <p class="text-xs text-slate-400">Cấu hình danh sách giọng đọc phục vụ chuyển văn bản thành tiếng nói</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Search input -->
                <div class="relative w-44 sm:w-52">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </span>
                    <input :value="search" @input="$emit('update:search', $event.target.value)" type="text"
                        class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 ps-8.5 pe-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        placeholder="Tìm giọng, mã code..." />
                </div>

                <!-- Provider Filter -->
                <select :value="filterProvider" @change="$emit('update:filterProvider', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả nhà cung cấp</option>
                    <option v-for="prov in (meta.available_providers || ['google', 'fpt', 'viettel', 'zalo', 'web-speech'])" :key="prov" :value="prov">
                        {{ prov.toUpperCase() }}
                    </option>
                </select>

                <!-- Gender Filter -->
                <select :value="filterGender" @change="$emit('update:filterGender', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả giới tính</option>
                    <option value="female">Nữ</option>
                    <option value="male">Nam</option>
                    <option value="neutral">Trung tính</option>
                </select>

                <!-- Status Filter -->
                <select :value="filterStatus" @change="$emit('update:filterStatus', $event.target.value)"
                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="1">Đang kích hoạt</option>
                    <option value="0">Tạm vô hiệu</option>
                </select>

            </div>
        </div>

        <!-- Bulk Action Bar -->
        <BulkActionBar
            :count="selectedVoices.length"
            label="giọng nói"
            actionLabel="giọng"
            @clear="$emit('update:selectedVoices', [])"
            @delete="$emit('bulk-delete')"
        />

        <!-- Voices Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs text-slate-600">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="w-10 px-4 py-3 text-center">
                            <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </th>
                        <th class="px-4 py-3 text-start">Tên giọng đọc & Mã định danh</th>
                        <th class="px-4 py-3 text-start">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-center">Giới tính</th>
                        <th class="px-4 py-3 text-center">Ngôn ngữ</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="isLoading">
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <div class="inline-flex items-center gap-2">
                                <svg class="size-5 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                <span>Đang tải danh sách giọng nói AI...</span>
                            </div>
                        </td>
                    </tr>
                    <template v-else>
                        <tr v-if="voices.length === 0">
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                            <line x1="12" y1="19" x2="12" y2="22"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium">Không tìm thấy giọng nói AI nào phù hợp</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="voice in voices" :key="voice.id"
                            :class="['transition hover:bg-slate-50/70', selectedVoices.includes(voice.id) ? 'bg-indigo-50/40' : '']">
                        <!-- Checkbox -->
                        <td class="w-10 px-4 py-3.5 text-center">
                            <input type="checkbox" :checked="selectedVoices.includes(voice.id)"
                                @change="handleCheckbox(voice.id, $event.target.checked)"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        </td>

                        <!-- Name & Code -->
                        <td class="px-4 py-3.5 font-medium text-slate-900">
                            <div class="flex items-center gap-2.5">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ voice.name }}</div>
                                    <div class="font-mono text-[11px] text-slate-400">{{ voice.code }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Provider -->
                        <td class="px-4 py-3.5">
                            <span :class="['inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold border uppercase tracking-wide', getProviderBadgeClass(voice.provider)]">
                                {{ voice.provider }}
                            </span>
                        </td>

                        <!-- Gender -->
                        <td class="px-4 py-3.5 text-center">
                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium border', getGenderLabel(voice.gender).class]">
                                {{ getGenderLabel(voice.gender).text }}
                            </span>
                        </td>

                        <!-- Language -->
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600 font-semibold">
                                {{ voice.language || 'vi-VN' }}
                            </span>
                        </td>

                        <!-- Status Toggle Button -->
                        <td class="px-4 py-3.5 text-center">
                            <button type="button" @click="$emit('toggle-status', voice.id)"
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition',
                                    voice.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                        : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'
                                ]">
                                <span :class="['size-1.5 rounded-full', voice.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ voice.is_active ? 'Kích hoạt' : 'Tắt' }}
                            </button>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3.5 text-end whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" @click="$emit('edit-voice', voice)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                    title="Chỉnh sửa giọng nói">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <button type="button" @click="$emit('delete-voice', voice)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition"
                                    title="Xóa giọng nói">
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
            label="giọng nói"
            :is-loading="isLoading"
            @change-page="$emit('change-page', $event)"
        />
    </section>
</template>

