<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Provider {
    name: string;
    logo: string | null;
}

interface Movie {
    id: number;
    tmdb_id: number;
    title: string;
    poster_path: string | null;
    backdrop_path?: string | null;
    overview?: string | null;
    tagline?: string | null;
    vote_average?: number | null;
    vote_count?: number | null;
    release_date?: string | null;
    runtime?: number | null;
    status?: string | null;
    genres?: string[];
    production_companies?: string[];
    trailer_key?: string | null;
    providers?: Provider[];
    watch_scheduled_at?: string | null;
    is_watched: boolean;
    user_rating?: number | null;
    user_review?: string | null;
}

interface Theatre {
    name: string;
    distance: string;
    formats: string[];
    showtimes: string[];
    price: string;
    bookingUrl: string;
}

const props = defineProps<{
    movies: Movie[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Watchlist',
        href: '/watchlist',
    },
];

const filterTab = ref<'all' | 'unwatched' | 'watched'>('unwatched');
const selectedMovie = ref<Movie | null>(null);
const activeEditMovie = ref<Movie | null>(null);
const activeReviewMovie = ref<Movie | null>(null);
const activeTrailerKey = ref<string | null>(null);

const scheduledDateTimeInput = ref('');
const reviewRatingInput = ref<number>(5);
const reviewTextInput = ref('');

const filteredMovies = computed(() => {
    if (filterTab.value === 'watched') {
        return props.movies.filter((m) => m.is_watched);
    }
    if (filterTab.value === 'unwatched') {
        return props.movies.filter((m) => !m.is_watched);
    }
    return props.movies;
});

const getImageUrl = (path: string | null, size: string = 'w500') => {
    if (!path) {
        return 'https://via.placeholder.com/500x750?text=No+Image';
    }
    return `https://image.tmdb.org/t/p/${size}${path}`;
};

const formatFullReleaseDate = (dateString?: string | null) => {
    if (!dateString) return 'Unknown Release Date';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const isMovieReleased = (dateString?: string | null) => {
    if (!dateString) return false;
    const releaseDate = new Date(dateString);
    if (isNaN(releaseDate.getTime())) return false;
    const today = new Date();
    return releaseDate <= today;
};

const formatScheduledDate = (isoString?: string | null) => {
    if (!isoString) return null;
    const date = new Date(isoString);
    return date.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const formatRuntime = (minutes?: number | null) => {
    if (!minutes) return null;
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hrs}h ${mins}m (${minutes} mins)`;
};

const getTheatresForMovie = (title: string): Theatre[] => {
    const encodedTitle = encodeURIComponent(title);
    return [
        {
            name: 'AMC Star Cinema 16',
            distance: '1.2 miles away',
            formats: ['IMAX 3D', 'Dolby Cinema', 'Standard'],
            showtimes: ['4:15 PM', '7:30 PM', '10:15 PM'],
            price: '$14.50',
            bookingUrl: `https://www.fandango.com/search?q=${encodedTitle}`,
        },
        {
            name: 'Regal Grand Theatre',
            distance: '2.8 miles away',
            formats: ['Standard 2D', 'RPX'],
            showtimes: ['3:45 PM', '6:45 PM', '9:30 PM'],
            price: '$12.99',
            bookingUrl: `https://www.amctheatres.com/search?q=${encodedTitle}`,
        },
        {
            name: 'Cinemark IMAX 3D',
            distance: '4.5 miles away',
            formats: ['XD 3D', 'RealD 3D'],
            showtimes: ['5:00 PM', '8:15 PM'],
            price: '$17.75',
            bookingUrl: `https://www.cinemark.com/search?q=${encodedTitle}`,
        },
    ];
};

const openDetailsModal = (movie: Movie) => {
    selectedMovie.value = movie;
};

const closeDetailsModal = () => {
    selectedMovie.value = null;
};

const openTrailerModal = (key?: string | null) => {
    if (key) {
        activeTrailerKey.value = key;
    }
};

const closeTrailerModal = () => {
    activeTrailerKey.value = null;
};

const openEditModal = (movie: Movie) => {
    activeEditMovie.value = movie;
    if (movie.watch_scheduled_at) {
        const date = new Date(movie.watch_scheduled_at);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        scheduledDateTimeInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    } else {
        scheduledDateTimeInput.value = '';
    }
};

const closeEditModal = () => {
    activeEditMovie.value = null;
    scheduledDateTimeInput.value = '';
};

const openReviewModal = (movie: Movie) => {
    activeReviewMovie.value = movie;
    reviewRatingInput.value = movie.user_rating || 5;
    reviewTextInput.value = movie.user_review || '';
};

const closeReviewModal = () => {
    activeReviewMovie.value = null;
    reviewRatingInput.value = 5;
    reviewTextInput.value = '';
};

const toggleWatchedStatus = (movie: Movie) => {
    if (!movie.is_watched) {
        openReviewModal(movie);
    } else {
        router.patch(
            `/watchlist/${movie.id}/watch-status`,
            {
                is_watched: false,
                user_rating: null,
                user_review: null,
            },
            { preserveScroll: true }
        );
    }
};

const saveWatchStatusAndReview = () => {
    if (!activeReviewMovie.value) return;

    router.patch(
        `/watchlist/${activeReviewMovie.value.id}/watch-status`,
        {
            is_watched: true,
            user_rating: reviewRatingInput.value,
            user_review: reviewTextInput.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => closeReviewModal(),
        }
    );
};

const saveScheduledDate = () => {
    if (!activeEditMovie.value) return;

    router.patch(
        `/watchlist/${activeEditMovie.value.id}`,
        {
            watch_scheduled_at: scheduledDateTimeInput.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => closeEditModal(),
        }
    );
};

const deleteMovie = (movieId: number) => {
    if (confirm('Are you sure you want to remove this movie from your watchlist?')) {
        router.delete(`/watchlist/${movieId}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedMovie.value?.id === movieId) {
                    closeDetailsModal();
                }
            },
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="My Watchlist" />

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header section -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white">My Movie Watchlist</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Movies you've swiped right on • Click any movie tile to view details, release status, trailers, and stream options
                    </p>
                </div>
                <Link
                    href="/swipe"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-indigo-700 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Swipe More Movies
                </Link>
            </div>

            <!-- Filter Tabs (Unwatched / Watched History / All) -->
            <div class="mb-8 flex items-center gap-2 border-b border-slate-200 pb-4 dark:border-slate-800">
                <button
                    type="button"
                    @click="filterTab = 'unwatched'"
                    class="rounded-xl px-4 py-2 text-xs font-bold transition"
                    :class="{
                        'bg-indigo-600 text-white shadow-md': filterTab === 'unwatched',
                        'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300': filterTab !== 'unwatched',
                    }"
                >
                    🎬 To Watch ({{ props.movies.filter(m => !m.is_watched).length }})
                </button>
                <button
                    type="button"
                    @click="filterTab = 'watched'"
                    class="rounded-xl px-4 py-2 text-xs font-bold transition"
                    :class="{
                        'bg-emerald-600 text-white shadow-md': filterTab === 'watched',
                        'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300': filterTab !== 'watched',
                    }"
                >
                    ✅ Watched History ({{ props.movies.filter(m => m.is_watched).length }})
                </button>
                <button
                    type="button"
                    @click="filterTab = 'all'"
                    class="rounded-xl px-4 py-2 text-xs font-bold transition"
                    :class="{
                        'bg-slate-900 text-white shadow-md dark:bg-slate-100 dark:text-slate-900': filterTab === 'all',
                        'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300': filterTab !== 'all',
                    }"
                >
                    All Movies ({{ props.movies.length }})
                </button>
            </div>

            <!-- Grid of liked movies -->
            <div v-if="filteredMovies.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                <div
                    v-for="movie in filteredMovies"
                    :key="movie.id"
                    @click="openDetailsModal(movie)"
                    class="group relative flex cursor-pointer flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <!-- Poster Image -->
                    <div class="relative aspect-[2/3] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <img
                            :src="getImageUrl(movie.poster_path)"
                            :alt="movie.title"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />

                        <!-- Watched Badge -->
                        <div v-if="movie.is_watched" class="absolute top-2 left-2 z-10">
                            <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2 py-0.5 text-[10px] font-extrabold text-white shadow-md">
                                ✅ Watched
                            </span>
                        </div>

                        <!-- Release Status Badge (Released vs Upcoming) -->
                        <div v-else-if="movie.release_date" class="absolute top-2 left-2 z-10">
                            <span
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-extrabold text-white shadow-md backdrop-blur-md"
                                :class="isMovieReleased(movie.release_date) ? 'bg-emerald-600/90' : 'bg-amber-600/90'"
                            >
                                {{ isMovieReleased(movie.release_date) ? '✅ Released' : '⏳ Upcoming' }}
                            </span>
                        </div>

                        <!-- Scheduled Date Badge -->
                        <div v-if="movie.watch_scheduled_at" class="absolute top-8 left-2 right-2 z-10">
                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-950/85 px-2.5 py-1 text-[11px] font-bold text-amber-300 backdrop-blur-md shadow-md">
                                📅 {{ formatScheduledDate(movie.watch_scheduled_at) }}
                            </span>
                        </div>

                        <!-- Action Overlay Buttons -->
                        <div class="absolute inset-0 z-20 flex items-center justify-center gap-2 bg-slate-950/60 opacity-0 transition-opacity duration-300 group-hover:opacity-100 backdrop-blur-[2px]">
                            <!-- Mark as Watched / Review Button -->
                            <button
                                type="button"
                                @click.stop="toggleWatchedStatus(movie)"
                                :title="movie.is_watched ? 'Edit Review / Unmark' : 'Mark as Watched'"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-white shadow-lg transition hover:scale-110"
                                :class="movie.is_watched ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-800 hover:bg-slate-700'"
                            >
                                {{ movie.is_watched ? '⭐' : '✅' }}
                            </button>

                            <!-- Edit Date & Time Button -->
                            <button
                                type="button"
                                @click.stop="openEditModal(movie)"
                                title="Schedule Watch Date & Time"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-900 shadow-lg transition hover:scale-110 hover:bg-indigo-50"
                            >
                                ✏️
                            </button>

                            <!-- Delete Option Button -->
                            <button
                                type="button"
                                @click.stop="deleteMovie(movie.id)"
                                title="Remove from Watchlist"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg transition hover:scale-110 hover:bg-rose-700"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex flex-1 flex-col justify-between p-3">
                        <div>
                            <h3 class="line-clamp-2 text-sm font-bold text-slate-900 group-hover:text-indigo-600 dark:text-slate-100 dark:group-hover:text-indigo-400">
                                {{ movie.title }}
                            </h3>
                            <p class="mt-1 text-[11px] font-semibold flex items-center gap-1" :class="isMovieReleased(movie.release_date) ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                <span>{{ isMovieReleased(movie.release_date) ? 'Released:' : 'Upcoming:' }}</span>
                                <span>{{ formatFullReleaseDate(movie.release_date) }}</span>
                            </p>
                        </div>
                        <div v-if="movie.is_watched" class="mt-2 text-[11px] font-extrabold text-amber-500 flex items-center gap-1">
                            <span>Your Rating:</span>
                            <span>{{ '⭐'.repeat(movie.user_rating || 5) }}</span>
                        </div>
                        <p v-else-if="movie.watch_scheduled_at" class="mt-2 text-[11px] font-medium text-amber-600 dark:text-amber-400 truncate">
                            Scheduled: {{ formatScheduledDate(movie.watch_scheduled_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty Watchlist State -->
            <div v-else class="mt-12 flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-800">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800/80 dark:text-slate-500">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 016 3.75h1.5m9 0h-9" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">No Movies Found</h3>
                <p class="mt-2 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                    No movies match the selected tab filter. Start swiping to add more movies to your watchlist!
                </p>
                <Link
                    href="/swipe"
                    class="mt-6 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition hover:bg-indigo-700"
                >
                    Start Swiping Now
                </Link>
            </div>
        </div>

        <!-- Full Movie Details & Theatre Tickets Modal -->
        <div v-if="selectedMovie" @click="closeDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md overflow-y-auto">
            <div @click.stop class="relative my-8 w-full max-w-3xl overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 text-white shadow-2xl">
                <!-- Close Button -->
                <button
                    type="button"
                    @click="closeDetailsModal"
                    class="absolute top-4 right-4 z-30 flex h-10 w-10 items-center justify-center rounded-full bg-slate-950/70 text-slate-300 backdrop-blur-md transition hover:bg-slate-800 hover:text-white"
                >
                    ✕
                </button>

                <!-- Backdrop Header Image -->
                <div class="relative h-64 w-full bg-slate-950 overflow-hidden sm:h-80">
                    <img
                        :src="getImageUrl(selectedMovie.backdrop_path || selectedMovie.poster_path, 'w1280')"
                        :alt="selectedMovie.title"
                        class="h-full w-full object-cover object-center"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"></div>

                    <!-- Header Content -->
                    <div class="absolute bottom-6 left-6 right-6 flex items-end gap-6">
                        <img
                            :src="getImageUrl(selectedMovie.poster_path)"
                            :alt="selectedMovie.title"
                            class="hidden h-36 w-24 rounded-xl border-2 border-slate-700 object-cover shadow-2xl sm:block"
                        />
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="selectedMovie.vote_average" class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-black text-slate-950">
                                    ★ {{ selectedMovie.vote_average.toFixed(1) }}
                                </span>

                                <!-- Clear Release Status Badge -->
                                <span
                                    v-if="selectedMovie.release_date"
                                    class="text-xs font-bold px-2.5 py-0.5 rounded-full border shadow-sm"
                                    :class="isMovieReleased(selectedMovie.release_date) ? 'bg-emerald-950/90 text-emerald-300 border-emerald-700' : 'bg-amber-950/90 text-amber-300 border-amber-700'"
                                >
                                    <template v-if="isMovieReleased(selectedMovie.release_date)">
                                        ✅ Released: {{ formatFullReleaseDate(selectedMovie.release_date) }}
                                    </template>
                                    <template v-else>
                                        ⏳ Upcoming: {{ formatFullReleaseDate(selectedMovie.release_date) }}
                                    </template>
                                </span>

                                <span v-if="selectedMovie.runtime" class="text-xs font-semibold text-slate-400 bg-slate-800/80 px-2.5 py-0.5 rounded-full border border-slate-700">
                                    ⏱️ {{ formatRuntime(selectedMovie.runtime) }}
                                </span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black leading-tight drop-shadow-md">
                                {{ selectedMovie.title }}
                            </h2>
                            <p v-if="selectedMovie.tagline" class="text-xs italic text-indigo-300">
                                "{{ selectedMovie.tagline }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
                    <!-- Action Row: Watch Trailer & Mark Watched -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            v-if="selectedMovie.trailer_key"
                            type="button"
                            @click="openTrailerModal(selectedMovie.trailer_key)"
                            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg transition hover:bg-rose-500 active:scale-95"
                        >
                            <span>🎬 Watch Trailer</span>
                        </button>

                        <button
                            type="button"
                            @click="toggleWatchedStatus(selectedMovie)"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white shadow-lg transition active:scale-95"
                            :class="selectedMovie.is_watched ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-indigo-600 hover:bg-indigo-500'"
                        >
                            <span>{{ selectedMovie.is_watched ? '✅ Watched (Edit Review)' : 'Mark as Watched ✅' }}</span>
                        </button>
                    </div>

                    <!-- Release Status Banner inside Modal -->
                    <div
                        v-if="selectedMovie.release_date"
                        class="rounded-2xl p-4 border flex items-center justify-between"
                        :class="isMovieReleased(selectedMovie.release_date) ? 'bg-emerald-950/30 border-emerald-900/60' : 'bg-amber-950/30 border-amber-900/60'"
                    >
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider" :class="isMovieReleased(selectedMovie.release_date) ? 'text-emerald-400' : 'text-amber-400'">
                                {{ isMovieReleased(selectedMovie.release_date) ? 'Movie is Officially Released' : 'Upcoming Movie Release' }}
                            </p>
                            <p class="text-sm font-semibold text-white mt-0.5">
                                {{ isMovieReleased(selectedMovie.release_date) ? 'Released on ' : 'Scheduled Release on ' }}
                                <strong>{{ formatFullReleaseDate(selectedMovie.release_date) }}</strong>
                            </p>
                        </div>
                        <span class="text-2xl">
                            {{ isMovieReleased(selectedMovie.release_date) ? '🎬' : '⏳' }}
                        </span>
                    </div>

                    <!-- User Review Box (If Watched) -->
                    <div v-if="selectedMovie.is_watched && (selectedMovie.user_rating || selectedMovie.user_review)" class="rounded-2xl border border-emerald-900/60 bg-emerald-950/30 p-4 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Your Personal Review</span>
                            <span class="text-sm text-amber-400 font-bold">{{ '⭐'.repeat(selectedMovie.user_rating || 5) }}</span>
                        </div>
                        <p v-if="selectedMovie.user_review" class="text-xs italic text-slate-200">
                            "{{ selectedMovie.user_review }}"
                        </p>
                    </div>

                    <!-- Where to Stream (Watch Providers in BD) -->
                    <div v-if="selectedMovie.providers && selectedMovie.providers.length > 0" class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Where to Stream (in Bangladesh 🇧🇩)</h4>
                        <div class="flex flex-wrap items-center gap-3">
                            <div
                                v-for="provider in selectedMovie.providers"
                                :key="provider.name"
                                class="flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-1.5 border border-slate-800"
                            >
                                <img
                                    :src="provider.logo || 'https://via.placeholder.com/92'"
                                    :alt="provider.name"
                                    class="h-6 w-6 rounded-md object-cover"
                                />
                                <span class="text-xs font-semibold text-slate-200">{{ provider.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Genres -->
                    <div v-if="selectedMovie.genres && selectedMovie.genres.length > 0">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Genres</h4>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="genre in selectedMovie.genres"
                                :key="genre"
                                class="rounded-full bg-indigo-950/80 px-3 py-1 text-xs font-semibold text-indigo-300 border border-indigo-900/60"
                            >
                                {{ genre }}
                            </span>
                        </div>
                    </div>

                    <!-- Full Uncut Overview / Plot Synopsis -->
                    <div v-if="selectedMovie.overview">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Full Synopsis & Plot Overview</h4>
                        <p class="text-sm leading-relaxed text-slate-200 bg-slate-950/40 p-4 rounded-2xl border border-slate-800/60">
                            {{ selectedMovie.overview }}
                        </p>
                    </div>

                    <!-- Production Companies -->
                    <div v-if="selectedMovie.production_companies && selectedMovie.production_companies.length > 0">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Production Companies</h4>
                        <p class="text-xs text-slate-300">
                            {{ selectedMovie.production_companies.join(', ') }}
                        </p>
                    </div>

                    <!-- Scheduled Watch Date Banner inside Modal -->
                    <div class="flex items-center justify-between rounded-2xl border border-indigo-900/60 bg-indigo-950/40 p-4">
                        <div>
                            <p class="text-xs font-bold text-indigo-300">Planned Watch Schedule</p>
                            <p class="text-sm font-semibold text-white mt-0.5">
                                {{ formatScheduledDate(selectedMovie.watch_scheduled_at) || 'Not scheduled yet' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="openEditModal(selectedMovie)"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow transition hover:bg-indigo-500"
                        >
                            {{ selectedMovie.watch_scheduled_at ? 'Edit Time ✏️' : 'Set Time 📅' }}
                        </button>
                    </div>

                    <!-- Nearby Theatres & Movie Tickets Section -->
                    <div class="space-y-4 pt-4 border-t border-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                    <span>🎟️</span>
                                    <span>Nearest Theatres & Movie Tickets</span>
                                </h3>
                                <p class="text-xs text-slate-400">Available showtimes and ticket pricing near you</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="theatre in getTheatresForMovie(selectedMovie.title)"
                                :key="theatre.name"
                                class="flex flex-col gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 p-4 transition hover:border-slate-700"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-sm text-white">{{ theatre.name }}</h4>
                                            <span class="text-[11px] font-semibold text-slate-400">({{ theatre.distance }})</span>
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            <span
                                                v-for="fmt in theatre.formats"
                                                :key="fmt"
                                                class="rounded bg-indigo-950/80 px-2 py-0.5 text-[10px] font-bold text-indigo-300 border border-indigo-900/50"
                                            >
                                                {{ fmt }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-4 mt-2 sm:mt-0">
                                        <div class="text-right">
                                            <span class="text-xs font-semibold text-slate-400">Tickets from</span>
                                            <p class="text-base font-extrabold text-emerald-400">{{ theatre.price }}</p>
                                        </div>

                                        <a
                                            :href="theatre.bookingUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:opacity-90 active:scale-95"
                                        >
                                            <span>Get Tickets</span>
                                            <span>🎟️</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- Showtimes -->
                                <div class="flex items-center gap-2 pt-2 border-t border-slate-900">
                                    <span class="text-[11px] font-semibold text-slate-400">Today's Times:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="time in theatre.showtimes"
                                            :key="time"
                                            class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-mono font-semibold text-slate-200"
                                        >
                                            {{ time }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- YouTube Trailer Modal Player -->
        <div v-if="activeTrailerKey" @click="closeTrailerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-md">
            <div @click.stop class="relative w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 shadow-2xl">
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

        <!-- Mark as Watched & Review Modal Dialog -->
        <div v-if="activeReviewMovie" @click="closeReviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div @click.stop class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Mark as Watched & Review</h3>
                    <button type="button" @click="closeReviewModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
                </div>

                <div class="mt-4 space-y-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        How was <strong class="text-slate-900 dark:text-white">{{ activeReviewMovie.title }}</strong>? Rate and review it below:
                    </p>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Your Personal Rating
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="star in [1, 2, 3, 4, 5]"
                                :key="star"
                                type="button"
                                @click="reviewRatingInput = star"
                                class="text-2xl transition hover:scale-125"
                            >
                                {{ star <= reviewRatingInput ? '⭐' : '☆' }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Your Notes / Short Review (Optional)
                        </label>
                        <textarea
                            v-model="reviewTextInput"
                            rows="3"
                            placeholder="What did you think of the movie?"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm font-medium text-slate-900 transition focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeReviewModal"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="saveWatchStatusAndReview"
                        class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-md transition hover:bg-emerald-500"
                    >
                        Save & Mark Watched ✅
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Watch Date & Time Modal Dialog -->
        <div v-if="activeEditMovie" @click="closeEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div @click.stop class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Schedule Movie Watch Time</h3>
                    <button type="button" @click="closeEditModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">✕</button>
                </div>

                <div class="mt-4 space-y-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Set a date and time to watch <strong class="text-slate-900 dark:text-white">{{ activeEditMovie.title }}</strong>:
                    </p>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Planned Watch Date & Time
                        </label>
                        <input
                            type="datetime-local"
                            v-model="scheduledDateTimeInput"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm font-medium text-slate-900 transition focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeEditModal"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="saveScheduledDate"
                        class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-md transition hover:bg-indigo-500"
                    >
                        Save Schedule
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
