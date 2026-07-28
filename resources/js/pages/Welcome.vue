<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
}>();
</script>

<template>
    <div>
        <Head title="MovieTinder - Swipe Right on Great Movies" />

        <div class="relative min-h-screen overflow-hidden bg-slate-950 text-white selection:bg-indigo-500 selection:text-white">
            <!-- Background glow effects -->
            <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-600/30 blur-[120px] pointer-events-none"></div>
            <div class="absolute top-1/2 -right-40 h-96 w-96 rounded-full bg-rose-600/20 blur-[140px] pointer-events-none"></div>

            <!-- Navbar -->
            <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-rose-500 font-black text-xl shadow-lg shadow-indigo-500/20">
                        🎬
                    </div>
                    <span class="text-xl font-black tracking-tight text-white">MovieTinder</span>
                </div>

                <nav class="flex items-center gap-4">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            href="/swipe"
                            class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition hover:bg-indigo-500"
                        >
                            Swipe App
                        </Link>
                        <Link
                            href="/watchlist"
                            class="rounded-xl border border-slate-700 bg-slate-900/60 px-5 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-800"
                        >
                            Watchlist
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="text-sm font-semibold text-slate-300 transition hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            href="/register"
                            class="rounded-xl bg-gradient-to-r from-indigo-600 to-rose-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 transition hover:opacity-95"
                        >
                            Get Started
                        </Link>
                    </template>
                </nav>
            </header>

            <!-- Hero Section -->
            <main class="relative z-10 mx-auto max-w-7xl px-6 pt-12 pb-24 lg:px-8 lg:pt-20 text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-4 py-1.5 text-xs font-semibold text-indigo-300 backdrop-blur-md mb-8">
                    ✨ Powered by TMDB & Inertia v2
                </div>

                <h1 class="mx-auto max-w-4xl text-5xl font-black tracking-tight sm:text-7xl">
                    Swipe Right on Your Next <span class="bg-gradient-to-r from-indigo-400 via-rose-400 to-amber-300 bg-clip-text text-transparent">Favorite Movie</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-400 leading-relaxed">
                    Stop endlessly scrolling through streaming catalogs. Swipe through daily trending movies, pass on what you don't like, and build your personalized watchlist in seconds.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            href="/swipe"
                            class="w-full sm:w-auto rounded-2xl bg-indigo-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-600/40 transition hover:bg-indigo-500 active:scale-95"
                        >
                            Start Swiping Now →
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/register"
                            class="w-full sm:w-auto rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-600/30 transition hover:scale-105 active:scale-95"
                        >
                            Create Free Account
                        </Link>
                        <Link
                            href="/login"
                            class="w-full sm:w-auto rounded-2xl border border-slate-800 bg-slate-900/80 px-8 py-4 text-base font-bold text-slate-200 backdrop-blur-md transition hover:bg-slate-800"
                        >
                            Log In
                        </Link>
                    </template>
                </div>

                <!-- App Preview Mockup Card -->
                <div class="mt-16 mx-auto max-w-sm rounded-3xl border border-slate-800 bg-slate-900/90 p-4 shadow-2xl backdrop-blur-xl">
                    <div class="relative aspect-[2/3] overflow-hidden rounded-2xl bg-slate-800">
                        <img
                            src="https://image.tmdb.org/t/p/w500/pB8OverwYvKG8MsTX2PfaKVyTKV.jpg"
                            alt="Movie Poster Preview"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-5 text-left text-white">
                            <span class="inline-block rounded-full bg-amber-500/90 px-2.5 py-0.5 text-xs font-bold text-slate-950 mb-2">★ 8.4</span>
                            <h3 class="text-xl font-bold">Fight Club</h3>
                            <p class="text-xs text-slate-300 line-clamp-2 mt-1">An insomniac office worker and a devil-may-care soap maker form an underground fight club that evolves into much more.</p>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-center gap-6 pb-2">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full border border-rose-900/60 bg-slate-950 text-rose-400 shadow-md">
                            ✕
                        </div>
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            ✓
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
