<script setup>
defineProps({
    count: {
        type: Number,
        required: true,
        default: 0
    },
    label: {
        type: String,
        default: 'mục'
    },
    actionLabel: {
        type: String,
        default: 'mục'
    }
})

defineEmits(['clear', 'delete'])
</script>

<template>
    <div v-if="count > 0"
        class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50/70 px-6 py-2.5 text-xs transition-all">
        <div class="flex items-center gap-2 text-indigo-950 font-semibold">
            <span class="flex size-5 items-center justify-center rounded-full bg-indigo-600 text-white text-[10px] font-bold">{{ count }}</span>
            <span>{{ label }} đã được chọn</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="$emit('clear')" class="px-2.5 py-1 text-slate-500 hover:text-slate-700 font-medium transition">
                Bỏ chọn
            </button>
            <button type="button" @click="$emit('delete')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition active:scale-95">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    <line x1="10" y1="11" x2="10" y2="17" />
                    <line x1="14" y1="11" x2="14" y2="17" />
                </svg>
                Xóa {{ count }} {{ actionLabel }} đã chọn
            </button>
        </div>
    </div>
</template>

