<script setup>
defineProps({
    currentPage: {
        type: Number,
        required: true,
        default: 1
    },
    lastPage: {
        type: Number,
        required: true,
        default: 1
    },
    total: {
        type: Number,
        required: true,
        default: 0
    },
    label: {
        type: String,
        default: 'mục'
    },
    isLoading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['change-page'])

function onPageChange(page) {
    emit('change-page', page)
}
</script>

<template>
    <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-xs text-slate-500">
        <span>
            Trang <strong class="text-slate-700">{{ currentPage }}</strong> /
            {{ lastPage }} —
            <strong class="text-indigo-600">{{ total }}</strong> {{ label }}
        </span>
        <div class="flex items-center gap-1">
            <button @click="onPageChange(1)" :disabled="currentPage === 1 || isLoading"
                class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">«</button>
            <button @click="onPageChange(currentPage - 1)" :disabled="currentPage <= 1 || isLoading"
                class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">‹</button>
            <template v-for="p in lastPage" :key="p">
                <button v-if="Math.abs(p - currentPage) <= 2"
                    @click="onPageChange(p)" :disabled="isLoading"
                    :class="['flex size-8 items-center justify-center rounded-lg border text-xs font-bold transition',
                        p === currentPage
                            ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                            : 'border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40']">
                    {{ p }}
                </button>
            </template>
            <button @click="onPageChange(currentPage + 1)" :disabled="currentPage >= lastPage || isLoading"
                class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">›</button>
            <button @click="onPageChange(lastPage)" :disabled="currentPage === lastPage || isLoading"
                class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">»</button>
        </div>
    </div>
</template>

