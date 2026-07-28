<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const searchQuery = ref('');

const handleSearch = () => {
    if (!searchQuery.value.trim()) return;
    router.get('/swipe', { search: searchQuery.value.trim() }, { preserveState: true });
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href">
                                        {{ item.title }}
                                    </BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <!-- Top Right Movie Search Bar -->
        <form @submit.prevent="handleSearch" class="relative flex items-center">
            <div class="relative w-44 sm:w-64">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Search movies to swipe..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-9 pr-3 text-xs font-medium text-slate-900 transition focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:focus:border-indigo-500 dark:focus:bg-slate-950"
                />
                <svg class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </form>
    </header>
</template>
