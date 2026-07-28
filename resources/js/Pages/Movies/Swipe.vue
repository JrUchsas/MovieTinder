<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Provider {
    name: string;
    logo: string | null;
}

interface Movie {
    id: number;
    title: string;
    poster_path: string | null;
    overview?: string;
    vote_average?: number;
    release_date?: string;
    trailer_key?: string | null;
    providers?: Provider[];
}

interface Genre {
    id: number | string | null;
    name: string;
}

const props = defineProps<{
    movies: Movie[];
    genres?: Genre[];
    selectedGenreId?: number | string | null;
    searchQuery?: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Swipe Movies',
        href: '/swipe',
    },
];

const movieList = ref<Movie[]>([...props.movies]);
const isSubmitting = ref(false);
const currentGenreId = ref<number | string | null>(props.selectedGenreId ?? null);
const activeTrailerKey = ref<string | null>(null);

// Watch for prop changes when genre filter or search changes
watch(
    () => props.movies,
    (newMovies) => {
        movieList.value = [...newMovies];
        cardTransform.value = { x: 0, y: 0, rotate: 0 };
    }
);

// Mouse & Touch Drag Physics
const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);
const cardTransform = ref({ x: 0, y: 0, rotate: 0 });
const animState = ref<'like' | 'pass' | null>(null);

const currentMovie = computed(() => movieList.value[0] || null);

const getImageUrl = (path: string | null) => {
    if (!path) {
        return 'https://via.placeholder.com/500x750?text=No+Poster+Available';
    }
    return `https://image.tmdb.org/t/p/w500${path}`;
};

const isMovieReleased = (dateString?: string | null) => {
    if (!dateString) return false;
    const releaseDate = new Date(dateString);
    if (isNaN(releaseDate.getTime())) return false;
    const today = new Date();
    return releaseDate <= today;
};

const formatFullReleaseDate = (dateString?: string | null) => {
    if (!dateString) return 'Unknown Date';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const swipeBadge = computed(() => {
    if (animState.value === 'like' || (isDragging.value && cardTransform.value.x > 40)) {
        return 'LIKE';
    }
    if (animState.value === 'pass' || (isDragging.value && cardTransform.value.x < -40)) {
        return 'PASS';
    }
    return null;
});

const filterByGenre = (genreId: number | string | null) => {
    currentGenreId.value = genreId;
    router.get(
        '/swipe',
        genreId ? { genre_id: genreId } : {},
        { preserveState: true, preserveScroll: true }
    );
};

const openTrailerModal = (key?: string | null) => {
    if (key) {
        activeTrailerKey.value = key;
    }
};

const closeTrailerModal = () => {
    activeTrailerKey.value = null;
};

// Trigger swipe choice
const handleSwipe = (isLiked: boolean) => {
    if (!currentMovie.value || isSubmitting.value) return;

    isSubmitting.value = true;
    animState.value = isLiked ? 'like' : 'pass';

    // Animate card off screen
    cardTransform.value = {
        x: isLiked ? 600 : -600,
        y: 40,
        rotate: isLiked ? 30 : -30,
    };

    const movie = currentMovie.value;

    // Optimistically remove card from stack after animation trigger
    setTimeout(() => {
        movieList.value.shift();
        cardTransform.value = { x: 0, y: 0, rotate: 0 };
        animState.value = null;
        isSubmitting.value = false;
    }, 200);

    // Send POST request via Inertia router with fallback to axios
    const payload = {
        tmdb_id: movie.id,
        title: movie.title,
        poster_path: movie.poster_path,
        is_liked: isLiked,
    };

    router.post('/swipe', payload, {
        preserveState: true,
        preserveScroll: true,
        only: [],
        onError: () => {
            axios.post('/swipe', payload).catch(console.error);
        },
    });
};

// Drag event listeners
const startDrag = (event: MouseEvent | TouchEvent) => {
    if (isSubmitting.value || !currentMovie.value) return;
    isDragging.value = true;
    const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX;
    const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;
    startX.value = clientX;
    startY.value = clientY;
};

const onDrag = (event: MouseEvent | TouchEvent) => {
    if (!isDragging.value) return;
    const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX;
    const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;

    const deltaX = clientX - startX.value;
    const deltaY = clientY - startY.value;
    const rotate = deltaX * 0.08;

    cardTransform.value = { x: deltaX, y: deltaY, rotate };
};

const endDrag = () => {
    if (!isDragging.value) return;
    isDragging.value = false;
    const deltaX = cardTransform.value.x;

    const SWIPE_THRESHOLD = 80;
    if (deltaX > SWIPE_THRESHOLD) {
        handleSwipe(true);
    } else if (deltaX < -SWIPE_THRESHOLD) {
        handleSwipe(false);
    } else {
        cardTransform.value = { x: 0, y: 0, rotate: 0 };
    }
};

onMounted(() => {
    window.addEventListener('mousemove', onDrag);
    window.addEventListener('mouseup', endDrag);
    window.addEventListener('touchmove', onDrag);
    window.addEventListener('touchend', endDrag);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onDrag);
    window.removeEventListener('mouseup', endDrag);
    window.removeEventListener('touchmove', onDrag);
    window.removeEventListener('touchend', endDrag);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Swipe Movies" />

        <div class="flex min-h-[85vh] w-full flex-col items-center justify-center p-4 select-none">
            <!-- Header Navigation -->
            <div class="mb-4 flex w-full max-w-md items-center justify-between px-2">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">MovieTinder</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Search, filter by category or drag card to swipe</p>
                </div>
                <Link
                    href="/watchlist"
                    class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-4 py-2 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/80"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    Watchlist
                </Link>
            </div>

            <!-- Active Search Filter Badge -->
            <div v-if="props.searchQuery" class="mb-4 flex items-center justify-between w-full max-w-md bg-indigo-50 dark:bg-indigo-950/60 p-3 rounded-2xl border border-indigo-200 dark:border-indigo-900">
                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                    🔍 Search: <strong>"{{ props.searchQuery }}"</strong>
                </span>
                <button
                    type="button"
                    @click="filterByGenre(null)"
                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200"
                >
                    ✕ Clear Search
                </button>
            </div>

            <!-- Category / Genre Filter Pills Bar -->
            <div v-if="props.genres && props.genres.length > 0" class="mb-6 flex w-full max-w-md items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    v-for="genre in props.genres"
                    :key="genre.name"
                    type="button"
                    @click="filterByGenre(genre.id)"
                    class="whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                    :class="{
                        'bg-indigo-600 text-white shadow-md shadow-indigo-600/30': currentGenreId === genre.id,
                        'bg-white text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800': currentGenreId !== genre.id,
                    }"
                >
                    {{ genre.name }}
                </button>
            </div>

            <!-- Card Stack Container -->
            <div v-if="currentMovie" class="relative flex w-full max-w-md flex-col items-center">
                <!-- Stacked background cards for depth -->
                <div v-if="movieList.length > 1" class="absolute -bottom-3 h-full w-[92%] rounded-3xl bg-slate-200/80 shadow-md dark:bg-slate-800/50"></div>
                <div v-if="movieList.length > 2" class="absolute -bottom-6 h-full w-[84%] rounded-3xl bg-slate-300/50 shadow-sm dark:bg-slate-900/30"></div>

                <!-- Interactive Main Card -->
                <div
                    @mousedown="startDrag"
                    @touchstart="startDrag"
                    class="relative aspect-[2/3] w-full cursor-grab overflow-hidden rounded-3xl border border-slate-200/60 bg-slate-900 shadow-2xl active:cursor-grabbing dark:border-slate-800"
                    :style="{
                        transform: `translate3d(${cardTransform.x}px, ${cardTransform.y}px, 0px) rotate(${cardTransform.rotate}deg)`,
                        transition: isDragging ? 'none' : 'transform 0.25s ease, opacity 0.25s ease',
                    }"
                >
                    <!-- Visual Swipe Stamp Badge -->
                    <div
                        v-if="swipeBadge"
                        class="absolute top-8 z-30 rounded-2xl border-4 px-6 py-2 text-2xl font-black tracking-wider uppercase transition"
                        :class="{
                            'right-8 border-emerald-500 text-emerald-500 rotate-12 bg-slate-950/80': swipeBadge === 'LIKE',
                            'left-8 border-rose-500 text-rose-500 -rotate-12 bg-slate-950/80': swipeBadge === 'PASS',
                        }"
                    >
                        {{ swipeBadge }}
                    </div>

                    <!-- Watch Trailer Button (Top Left) -->
                    <button
                        v-if="currentMovie.trailer_key"
                        type="button"
                        @click.stop="openTrailerModal(currentMovie.trailer_key)"
                        class="absolute top-4 left-4 z-30 flex items-center gap-1.5 rounded-full bg-slate-950/80 px-3.5 py-1.5 text-xs font-bold text-white backdrop-blur-md border border-slate-700/80 transition hover:bg-rose-600 hover:border-rose-500 shadow-lg"
                    >
                        <span>🎬 Trailer</span>
                    </button>

                    <!-- Poster Image -->
                    <img
                        :src="getImageUrl(currentMovie.poster_path)"
                        :alt="currentMovie.title"
                        class="h-full w-full object-cover object-center pointer-events-none"
                    />

                    <!-- Dark Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent pointer-events-none"></div>

                    <!-- Movie Info -->
                    <div class="absolute bottom-0 left-0 right-0 p-6 text-white pointer-events-none">
                        <!-- Streaming Providers Logos -->
                        <div v-if="currentMovie.providers && currentMovie.providers.length > 0" class="mb-3 flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Stream on:</span>
                            <div class="flex items-center gap-1.5">
                                <img
                                    v-for="provider in currentMovie.providers"
                                    :key="provider.name"
                                    :src="provider.logo || 'https://via.placeholder.com/92'"
                                    :alt="provider.name"
                                    :title="`Available on ${provider.name}`"
                                    class="h-6 w-6 rounded-md object-cover border border-slate-700 shadow-sm"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span v-if="currentMovie.vote_average" class="inline-flex items-center gap-1 rounded-full bg-amber-500/90 px-2.5 py-0.5 text-xs font-bold text-slate-950 backdrop-blur-md">
                                ★ {{ currentMovie.vote_average.toFixed(1) }}
                            </span>

                            <!-- Release Status Tick Badge -->
                            <span
                                v-if="currentMovie.release_date"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-extrabold text-white backdrop-blur-md shadow-md"
                                :class="isMovieReleased(currentMovie.release_date) ? 'bg-emerald-600/90' : 'bg-amber-600/90'"
                            >
                                <template v-if="isMovieReleased(currentMovie.release_date)">
                                    ✅ Released ({{ formatFullReleaseDate(currentMovie.release_date) }})
                                </template>
                                <template v-else>
                                    ⏳ Upcoming ({{ formatFullReleaseDate(currentMovie.release_date) }})
                                </template>
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold leading-tight drop-shadow-md">
                            {{ currentMovie.title }}
                        </h2>
                        <p v-if="currentMovie.overview" class="mt-2 line-clamp-3 text-xs leading-relaxed text-slate-300/90">
                            {{ currentMovie.overview }}
                        </p>
                    </div>
                </div>

                <!-- Action Controls (Pass ✕ / Like ✓ Buttons) -->
                <div class="relative z-50 mt-8 flex items-center justify-center gap-8">
                    <!-- Pass (✕) Button -->
                    <button
                        type="button"
                        @click.prevent.stop="handleSwipe(false)"
                        :disabled="isSubmitting"
                        aria-label="Pass"
                        class="relative z-50 flex h-16 w-16 cursor-pointer items-center justify-center rounded-full border border-rose-200 bg-white text-rose-500 shadow-xl transition duration-200 hover:scale-110 hover:bg-rose-50 active:scale-95 disabled:opacity-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/40"
                    >
                        <svg class="h-8 w-8 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Like (✓) Button -->
                    <button
                        type="button"
                        @click.prevent.stop="handleSwipe(true)"
                        :disabled="isSubmitting"
                        aria-label="Like"
                        class="relative z-50 flex h-16 w-16 cursor-pointer items-center justify-center rounded-full bg-emerald-500 text-white shadow-xl shadow-emerald-500/30 transition duration-200 hover:scale-110 hover:bg-emerald-600 active:scale-95 disabled:opacity-50"
                    >
                        <svg class="h-8 w-8 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Empty State when all movies swiped or no search results -->
            <div v-else class="flex max-w-md flex-col items-center justify-center text-center p-8 rounded-3xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-indigo-50 text-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                    {{ props.searchQuery ? 'No Movies Found' : 'All Swiped Out!' }}
                </h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ props.searchQuery ? `No unseen movies match "${props.searchQuery}". Try searching another title!` : 'You have swiped through all movies in this deck.' }}
                </p>
                <div class="mt-6 flex flex-col sm:flex-row gap-3 w-full">
                    <button
                        type="button"
                        @click="filterByGenre(null)"
                        class="flex-1 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-md transition hover:bg-indigo-700"
                    >
                        Show All Categories
                    </button>
                    <Link
                        href="/watchlist"
                        class="flex-1 rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        View Watchlist
                    </Link>
                </div>
            </div>
        </div>

        <!-- YouTube Trailer Modal Player -->
        <div v-if="activeTrailerKey" @click="closeTrailerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-md">
            <div @click.stop class="relative w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 shadow-2xl">
                <!-- Close Button -->
                <button
                    type="button"
                    @click="closeTrailerModal"
                    class="absolute top-4 right-4 z-30 flex h-10 w-10 items-center justify-center rounded-full bg-slate-900/80 text-slate-300 transition hover:bg-rose-600 hover:text-white"
                >
                    ✕
                </button>
                <div class="aspect-video w-full">
                    <iframe
                        :src="`https://www.youtube.com/embed/${activeTrailerKey}?autoplay=1`"
                        title="Official Movie Trailer"
                        class="h-full w-full border-0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
