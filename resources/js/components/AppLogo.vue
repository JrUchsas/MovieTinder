<script setup lang="ts">
interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-gradient-to-tr from-indigo-600 to-rose-500 text-white shadow-md">
        <span class="text-base">🎬</span>
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-extrabold leading-none text-slate-900 dark:text-white">MovieTinder</span>
    </div>
</template>
