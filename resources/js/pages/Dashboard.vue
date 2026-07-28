<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface Movie {
    id: number;
    tmdb_id: number;
    title: string;
    poster_path: string | null;
}

interface Stats {
    total_swipes: number;
    total_liked: number;
    total_passed: number;
}

const props = defineProps<{
    stats: Stats;
    recentWatchlist: Movie[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const getImageUrl = (path: string | null) => {
    if (!path) {
        return 'https://via.placeholder.com/500x750?text=No+Poster';
    }
    return `https://image.tmdb.org/t/p/w500${path}`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard" />

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
            <!-- Greeting & Hero -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        MovieTinder Dashboard
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Overview of your movie swiping activity and saved watchlist.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/swipe"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500 active:scale-95"
                    >
                        <span>Start Swiping</span>
                        <span>🎬</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Total Swipes -->
                <div class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-2xl text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                        🎬
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Swipes</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ props.stats.total_swipes }}</p>
                    </div>
                </div>

                <!-- Watchlist (Liked) -->
                <div class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-2xl text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        ❤️
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Watchlist Movies</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ props.stats.total_liked }}</p>
                    </div>
                </div>

                <!-- Passed -->
                <div class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-2xl text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                        ✕
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Movies Passed</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ props.stats.total_passed }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Watchlist Preview -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Recent Watchlist Movies</h2>
                    <Link href="/watchlist" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        View Full Watchlist →
                    </Link>
                </div>

                <div v-if="props.recentWatchlist.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div
                        v-for="movie in props.recentWatchlist"
                        :key="movie.id"
                        class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition hover:shadow-lg dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="aspect-[2/3] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            <img
                                :src="getImageUrl(movie.poster_path)"
                                :alt="movie.title"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                            />
                        </div>
                        <div class="p-3">
                            <p class="truncate text-xs font-bold text-slate-900 dark:text-slate-100">{{ movie.title }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-2xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-800">
                    <p class="text-xs text-slate-500 dark:text-slate-400">No movies saved in your watchlist yet.</p>
                    <Link href="/swipe" class="mt-3 inline-block text-xs font-bold text-indigo-600 hover:underline">
                        Start Swiping Now →
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
