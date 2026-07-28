<?php

namespace App\Services;

use App\Models\Movie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TMDBService
{
    /**
     * Base URL for the TMDB API.
     */
    protected string $baseUrl = 'https://api.themoviedb.org/3';

    /**
     * Fetch movies (trending or filtered by genre) from TMDB and filter out movies swiped by the user.
     *
     * @param  int|string  $userId
     * @param  int|null  $genreId
     * @return array<int, array<string, mixed>>
     */
    public function getTrendingMovies(int|string $userId, ?int $genreId = null): array
    {
        $cacheKey = ! empty($genreId) ? "tmdb_genre_movies_{$genreId}" : 'tmdb_trending_movies_day';

        $movies = Cache::remember($cacheKey, now()->addHours(6), function () use ($genreId): array {
            $token = config('services.tmdb.token');
            $apiKey = config('services.tmdb.api_key');

            $request = Http::acceptJson();

            if (! empty($token)) {
                $request->withToken($token);
            }

            $queryParams = [];
            if (! empty($apiKey)) {
                $queryParams['api_key'] = $apiKey;
            }

            if (! empty($genreId)) {
                $today = now()->format('Y-m-d');
                $genrePopularParams = array_merge($queryParams, [
                    'with_genres' => $genreId,
                    'sort_by' => 'popularity.desc',
                ]);
                $genreUpcomingParams = array_merge($queryParams, [
                    'with_genres' => $genreId,
                    'primary_release_date.gte' => $today,
                    'sort_by' => 'popularity.desc',
                ]);

                $popRes = $request->get("{$this->baseUrl}/discover/movie", $genrePopularParams);
                $upRes = $request->get("{$this->baseUrl}/discover/movie", $genreUpcomingParams);

                $popular = $popRes->failed() ? [] : $popRes->json('results', []);
                $upcoming = $upRes->failed() ? [] : $upRes->json('results', []);

                $allGenreMovies = array_merge($popular, $upcoming);
                $uniqueMovies = [];
                $seenIds = [];
                foreach ($allGenreMovies as $movie) {
                    if (isset($movie['id']) && ! in_array($movie['id'], $seenIds, true)) {
                        $seenIds[] = $movie['id'];
                        $uniqueMovies[] = $movie;
                    }
                }

                return $uniqueMovies;
            }

            // For All Categories: Fetch both trending and upcoming movies to present a rich mix
            $trendingResponse = $request->get("{$this->baseUrl}/trending/movie/day", $queryParams);
            $upcomingResponse = $request->get("{$this->baseUrl}/movie/upcoming", $queryParams);

            $trending = $trendingResponse->failed() ? [] : $trendingResponse->json('results', []);
            $upcoming = $upcomingResponse->failed() ? [] : $upcomingResponse->json('results', []);

            // Combine and deduplicate
            $allMovies = array_merge($trending, $upcoming);
            $uniqueMovies = [];
            $seenIds = [];
            foreach ($allMovies as $movie) {
                if (isset($movie['id']) && ! in_array($movie['id'], $seenIds, true)) {
                    $seenIds[] = $movie['id'];
                    $uniqueMovies[] = $movie;
                }
            }

            return $uniqueMovies;
        });

        // Get array of TMDB IDs that the specified user has already swiped on
        $swipedTmdbIds = Movie::whereHas('swipes', function ($query) use ($userId): void {
            $query->where('user_id', $userId);
        })->pluck('tmdb_id')->toArray();

        // Filter out movies that the user has swiped on
        $unseenMovies = array_filter($movies, function (array $movie) use ($swipedTmdbIds): bool {
            return isset($movie['id']) && ! in_array((int) $movie['id'], $swipedTmdbIds, true);
        });

        return array_values($unseenMovies);
    }

    /**
     * Fetch upcoming / unreleased movies from TMDB and filter out movies swiped by the user.
     *
     * @param  int|string  $userId
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingMovies(int|string $userId): array
    {
        $movies = Cache::remember('tmdb_upcoming_movies', now()->addHours(6), function (): array {
            $token = config('services.tmdb.token');
            $apiKey = config('services.tmdb.api_key');

            $request = Http::acceptJson();
            if (! empty($token)) {
                $request->withToken($token);
            }
            $queryParams = [];
            if (! empty($apiKey)) {
                $queryParams['api_key'] = $apiKey;
            }

            $response = $request->get("{$this->baseUrl}/movie/upcoming", $queryParams);

            if ($response->failed()) {
                return [];
            }

            return $response->json('results', []);
        });

        // Get array of TMDB IDs that the specified user has already swiped on
        $swipedTmdbIds = Movie::whereHas('swipes', function ($query) use ($userId): void {
            $query->where('user_id', $userId);
        })->pluck('tmdb_id')->toArray();

        $unseenMovies = array_filter($movies, function (array $movie) use ($swipedTmdbIds): bool {
            return isset($movie['id']) && ! in_array((int) $movie['id'], $swipedTmdbIds, true);
        });

        return array_values($unseenMovies);
    }

    /**
     * Search movies by title query from TMDB.
     *
     * @param  int|string  $userId
     * @return array<int, array<string, mixed>>
     */
    public function searchMovies(string $query, int|string $userId): array
    {
        if (trim($query) === '') {
            return $this->getTrendingMovies($userId);
        }

        $token = config('services.tmdb.token');
        $apiKey = config('services.tmdb.api_key');

        $request = Http::acceptJson();
        if (! empty($token)) {
            $request->withToken($token);
        }

        $queryParams = ['query' => $query];
        if (! empty($apiKey)) {
            $queryParams['api_key'] = $apiKey;
        }

        $response = $request->get("{$this->baseUrl}/search/movie", $queryParams);

        if ($response->failed()) {
            return [];
        }

        $results = $response->json('results', []);

        // Filter out swiped movies
        $swipedTmdbIds = Movie::whereHas('swipes', function ($q) use ($userId): void {
            $q->where('user_id', $userId);
        })->pluck('tmdb_id')->toArray();

        $unseenMovies = array_filter($results, function (array $movie) use ($swipedTmdbIds): bool {
            return isset($movie['id']) && ! in_array((int) $movie['id'], $swipedTmdbIds, true);
        });

        return array_values($unseenMovies);
    }

    /**
     * Fetch personalized AI recommended movies based on user's liked watchlist.
     *
     * @param  int|string  $userId
     * @return array<int, array<string, mixed>>
     */
    public function getRecommendedMovies(int|string $userId): array
    {
        $likedMovie = Movie::whereHas('swipes', function ($query) use ($userId): void {
            $query->where('user_id', $userId)->where('is_liked', true);
        })->latest()->first();

        if (! $likedMovie) {
            return $this->getTrendingMovies($userId);
        }

        $tmdbId = $likedMovie->tmdb_id;

        $recommended = Cache::remember("tmdb_recommended_movies_{$tmdbId}", now()->addHours(6), function () use ($tmdbId): array {
            $token = config('services.tmdb.token');
            $apiKey = config('services.tmdb.api_key');

            $request = Http::acceptJson();

            if (! empty($token)) {
                $request->withToken($token);
            }

            $queryParams = [];
            if (! empty($apiKey)) {
                $queryParams['api_key'] = $apiKey;
            }

            $response = $request->get("{$this->baseUrl}/movie/{$tmdbId}/recommendations", $queryParams);

            if ($response->failed()) {
                return [];
            }

            return $response->json('results', []);
        });

        if (empty($recommended)) {
            return $this->getTrendingMovies($userId);
        }

        // Get array of TMDB IDs that the user has already swiped on
        $swipedTmdbIds = Movie::whereHas('swipes', function ($query) use ($userId): void {
            $query->where('user_id', $userId);
        })->pluck('tmdb_id')->toArray();

        $unseen = array_filter($recommended, function (array $movie) use ($swipedTmdbIds): bool {
            return isset($movie['id']) && ! in_array((int) $movie['id'], $swipedTmdbIds, true);
        });

        return array_values($unseen);
    }

    /**
     * Fetch detailed movie information from TMDB.
     *
     * @return array<string, mixed>
     */
    public function getMovieDetails(int $tmdbId): array
    {
        return Cache::remember("tmdb_movie_detail_{$tmdbId}", now()->addDays(1), function () use ($tmdbId): array {
            $token = config('services.tmdb.token');
            $apiKey = config('services.tmdb.api_key');

            $request = Http::acceptJson();

            if (! empty($token)) {
                $request->withToken($token);
            }

            $queryParams = [];
            if (! empty($apiKey)) {
                $queryParams['api_key'] = $apiKey;
            }

            $response = $request->get("{$this->baseUrl}/movie/{$tmdbId}", $queryParams);

            if ($response->failed()) {
                return [];
            }

            return $response->json();
        });
    }

    /**
     * Fetch official YouTube trailer video key for a movie.
     */
    public function getMovieTrailerKey(int $tmdbId): ?string
    {
        return Cache::remember("tmdb_movie_trailer_{$tmdbId}", now()->addDays(1), function () use ($tmdbId): ?string {
            $token = config('services.tmdb.token');
            $apiKey = config('services.tmdb.api_key');

            $request = Http::acceptJson();
            if (! empty($token)) {
                $request->withToken($token);
            }
            $queryParams = [];
            if (! empty($apiKey)) {
                $queryParams['api_key'] = $apiKey;
            }

            $response = $request->get("{$this->baseUrl}/movie/{$tmdbId}/videos", $queryParams);

            if ($response->failed()) {
                return null;
            }

            $results = $response->json('results', []);
            foreach ($results as $video) {
                if (isset($video['site'], $video['type'], $video['key']) && $video['site'] === 'YouTube' && ($video['type'] === 'Trailer' || $video['type'] === 'Teaser')) {
                    return $video['key'];
                }
            }

            return $results[0]['key'] ?? null;
        });
    }

    /**
     * Fetch streaming watch providers for BD (Bangladesh) or global for a movie.
     *
     * @return array<int, array<string, string>>
     */
    public function getWatchProviders(int $tmdbId): array
    {
        return Cache::remember("tmdb_movie_providers_{$tmdbId}", now()->addDays(1), function () use ($tmdbId): array {
            $token = config('services.tmdb.token');
            $apiKey = config('services.tmdb.api_key');

            $request = Http::acceptJson();
            if (! empty($token)) {
                $request->withToken($token);
            }
            $queryParams = [];
            if (! empty($apiKey)) {
                $queryParams['api_key'] = $apiKey;
            }

            $response = $request->get("{$this->baseUrl}/movie/{$tmdbId}/watch/providers", $queryParams);

            if ($response->failed()) {
                return [];
            }

            // Try Bangladesh (BD) providers first (flatrate, buy, or rent)
            $bdResults = array_merge(
                $response->json('results.BD.flatrate', []),
                $response->json('results.BD.rent', []),
                $response->json('results.BD.buy', [])
            );

            if (empty($bdResults)) {
                $bdResults = array_merge(
                    $response->json('results.US.flatrate', []),
                    $response->json('results.US.rent', [])
                );
            }

            if (empty($bdResults)) {
                $results = $response->json('results', []);
                $firstCountry = reset($results);
                $bdResults = array_merge(
                    $firstCountry['flatrate'] ?? [],
                    $firstCountry['rent'] ?? []
                );
            }

            $providers = [];
            $seenNames = [];
            foreach ($bdResults as $provider) {
                if (isset($provider['provider_name']) && ! in_array($provider['provider_name'], $seenNames, true)) {
                    $seenNames[] = $provider['provider_name'];
                    $providers[] = [
                        'name' => $provider['provider_name'],
                        'logo' => isset($provider['logo_path']) ? "https://image.tmdb.org/t/p/w92{$provider['logo_path']}" : null,
                    ];
                }
            }

            return array_slice($providers, 0, 5);
        });
    }
}
