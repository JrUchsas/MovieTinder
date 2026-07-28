<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\TMDBService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SwipeController extends Controller
{
    public function __construct(
        protected TMDBService $tmdbService
    ) {}

    /**
     * Display a list of unseen trending, upcoming, search, recommended, or genre-filtered movies for swiping.
     */
    public function index(Request $request): Response
    {
        $genres = [
            ['id' => null, 'name' => 'All Categories'],
            ['id' => 'upcoming', 'name' => 'Upcoming Movies ⏳'],
            ['id' => 'recommended', 'name' => 'Recommended For You 🪄'],
            ['id' => 28, 'name' => 'Action'],
            ['id' => 12, 'name' => 'Adventure'],
            ['id' => 16, 'name' => 'Animation'],
            ['id' => 35, 'name' => 'Comedy'],
            ['id' => 80, 'name' => 'Crime'],
            ['id' => 18, 'name' => 'Drama'],
            ['id' => 14, 'name' => 'Fantasy'],
            ['id' => 27, 'name' => 'Horror'],
            ['id' => 10749, 'name' => 'Romance'],
            ['id' => 878, 'name' => 'Sci-Fi'],
            ['id' => 53, 'name' => 'Thriller'],
        ];

        $genreId = $request->query('genre_id');
        $searchQuery = $request->query('search');
        $userId = $request->user()->id;

        if (! empty($searchQuery)) {
            $unseenMovies = $this->tmdbService->searchMovies($searchQuery, $userId);
        } elseif ($genreId === 'upcoming') {
            $unseenMovies = $this->tmdbService->getUpcomingMovies($userId);
        } elseif ($genreId === 'recommended') {
            $unseenMovies = $this->tmdbService->getRecommendedMovies($userId);
        } else {
            $parsedGenreId = $genreId ? (int) $genreId : null;
            $unseenMovies = $this->tmdbService->getTrendingMovies($userId, $parsedGenreId);
        }

        // Attach trailer key and watch providers to each movie card
        $enrichedMovies = array_map(function (array $movie): array {
            $tmdbId = (int) $movie['id'];
            $movie['trailer_key'] = $this->tmdbService->getMovieTrailerKey($tmdbId);
            $movie['providers'] = $this->tmdbService->getWatchProviders($tmdbId);

            return $movie;
        }, $unseenMovies);

        return Inertia::render('Movies/Swipe', [
            'movies' => $enrichedMovies,
            'genres' => $genres,
            'selectedGenreId' => $genreId,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Store a user's swipe choice (like or pass).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'tmdb_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'poster_path' => ['nullable', 'string', 'max:255'],
            'is_liked' => ['required', 'boolean'],
        ]);

        $movie = Movie::firstOrCreate(
            ['tmdb_id' => $validated['tmdb_id']],
            [
                'title' => $validated['title'],
                'poster_path' => $validated['poster_path'],
            ]
        );

        $swipe = $request->user()->swipes()->updateOrCreate(
            ['movie_id' => $movie->id],
            ['is_liked' => $validated['is_liked']]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Swipe recorded successfully',
                'swipe' => $swipe,
            ], 201);
        }

        return redirect()->back();
    }

    /**
     * Display the user's liked movies watchlist with detailed information and scheduled watch dates.
     */
    public function watchlist(Request $request): Response
    {
        $userId = $request->user()->id;

        $movies = Movie::whereHas('swipes', function ($query) use ($userId): void {
            $query->where('user_id', $userId)
                ->where('is_liked', true);
        })
            ->with(['swipes' => function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            }])
            ->latest()
            ->get()
            ->map(function ($movie) {
                $userSwipe = $movie->swipes->first();
                $tmdbId = (int) $movie->tmdb_id;
                $details = $this->tmdbService->getMovieDetails($tmdbId);
                $trailerKey = $this->tmdbService->getMovieTrailerKey($tmdbId);
                $providers = $this->tmdbService->getWatchProviders($tmdbId);

                return [
                    'id' => $movie->id,
                    'tmdb_id' => $movie->tmdb_id,
                    'title' => $movie->title,
                    'poster_path' => $movie->poster_path,
                    'backdrop_path' => $details['backdrop_path'] ?? null,
                    'overview' => $details['overview'] ?? null,
                    'tagline' => $details['tagline'] ?? null,
                    'vote_average' => $details['vote_average'] ?? null,
                    'vote_count' => $details['vote_count'] ?? null,
                    'release_date' => $details['release_date'] ?? null,
                    'runtime' => $details['runtime'] ?? null,
                    'status' => $details['status'] ?? null,
                    'genres' => isset($details['genres']) ? array_column($details['genres'], 'name') : [],
                    'production_companies' => isset($details['production_companies']) ? array_column($details['production_companies'], 'name') : [],
                    'trailer_key' => $trailerKey,
                    'providers' => $providers,
                    'watch_scheduled_at' => $userSwipe?->watch_scheduled_at?->toIso8601String(),
                    'is_watched' => (bool) ($userSwipe?->is_watched ?? false),
                    'user_rating' => $userSwipe?->user_rating,
                    'user_review' => $userSwipe?->user_review,
                ];
            });

        return Inertia::render('Movies/Watchlist', [
            'movies' => $movies,
        ]);
    }

    /**
     * Update scheduled watch date & time for a movie in watchlist.
     */
    public function update(Request $request, Movie $movie): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'watch_scheduled_at' => ['nullable', 'date'],
        ]);

        $request->user()->swipes()->where('movie_id', $movie->id)->update([
            'watch_scheduled_at' => $validated['watch_scheduled_at'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Watch date updated successfully']);
        }

        return redirect()->back();
    }

    /**
     * Update watched status, user rating, and user review for a movie.
     */
    public function updateWatchStatus(Request $request, Movie $movie): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'is_watched' => ['required', 'boolean'],
            'user_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'user_review' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->swipes()->where('movie_id', $movie->id)->update([
            'is_watched' => $validated['is_watched'],
            'user_rating' => $validated['user_rating'] ?? null,
            'user_review' => $validated['user_review'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Watch status updated successfully']);
        }

        return redirect()->back();
    }

    /**
     * Remove a movie from user's watchlist (delete swipe).
     */
    public function destroy(Request $request, Movie $movie): RedirectResponse|JsonResponse
    {
        $request->user()->swipes()->where('movie_id', $movie->id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Movie removed from watchlist']);
        }

        return redirect()->back();
    }
}
