<?php

use App\Models\Movie;
use App\Models\Swipe;
use App\Models\User;
use App\Services\TMDBService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('movie and swipe relationships work correctly', function () {
    $user = User::factory()->create();
    $movie = Movie::create([
        'tmdb_id' => 550,
        'title' => 'Fight Club',
        'poster_path' => '/pB8OverwYvKG8MsTX2PfaKVyTKV.jpg',
    ]);

    $swipe = Swipe::create([
        'user_id' => $user->id,
        'movie_id' => $movie->id,
        'is_liked' => true,
    ]);

    expect($user->swipes)->toHaveCount(1)
        ->and($user->swipes->first()->id)->toBe($swipe->id);

    expect($movie->swipes)->toHaveCount(1)
        ->and($movie->swipes->first()->id)->toBe($swipe->id);

    expect($swipe->user->id)->toBe($user->id)
        ->and($swipe->movie->id)->toBe($movie->id);
});

test('tmdb service fetches trending movies and filters out swiped movies', function () {
    Http::fake([
        'https://api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'results' => [
                ['id' => 101, 'title' => 'Movie 1', 'poster_path' => '/path1.jpg'],
                ['id' => 102, 'title' => 'Movie 2', 'poster_path' => '/path2.jpg'],
            ],
        ], 200),
        'https://api.themoviedb.org/3/movie/upcoming*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    $user = User::factory()->create();
    $movie = Movie::create([
        'tmdb_id' => 101,
        'title' => 'Movie 1',
        'poster_path' => '/path1.jpg',
    ]);

    Swipe::create([
        'user_id' => $user->id,
        'movie_id' => $movie->id,
        'is_liked' => true,
    ]);

    $service = new TMDBService;
    $unseenMovies = $service->getTrendingMovies($user->id);

    expect($unseenMovies)->toHaveCount(1)
        ->and($unseenMovies[0]['id'])->toBe(102);
});

test('swipe index endpoint returns inertia swipe view with unseen movies', function () {
    Http::fake([
        'https://api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'results' => [
                ['id' => 201, 'title' => 'Trending Movie', 'poster_path' => '/poster.jpg'],
            ],
        ], 200),
        'https://api.themoviedb.org/3/movie/*' => Http::response([], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/swipe');

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Movies/Swipe')
            ->has('movies', 1)
            ->where('movies.0.id', 201)
        );
});

test('swipe store endpoint saves movie and swipe choice', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/swipe', [
        'tmdb_id' => 301,
        'title' => 'Interstellar',
        'poster_path' => '/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
        'is_liked' => true,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Swipe recorded successfully',
        ]);

    $this->assertDatabaseHas('movies', [
        'tmdb_id' => 301,
        'title' => 'Interstellar',
    ]);

    $movie = Movie::where('tmdb_id', 301)->first();

    $this->assertDatabaseHas('swipes', [
        'user_id' => $user->id,
        'movie_id' => $movie->id,
        'is_liked' => true,
    ]);
});

test('watchlist endpoint returns only liked movies for authenticated user', function () {
    Http::fake([
        'https://api.themoviedb.org/3/movie/*' => Http::response([], 200),
    ]);

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $likedMovie = Movie::create([
        'tmdb_id' => 401,
        'title' => 'Inception',
        'poster_path' => '/inception.jpg',
    ]);

    $passedMovie = Movie::create([
        'tmdb_id' => 402,
        'title' => 'Bad Movie',
        'poster_path' => '/bad.jpg',
    ]);

    // Current user likes Inception, passes Bad Movie
    Swipe::create(['user_id' => $user->id, 'movie_id' => $likedMovie->id, 'is_liked' => true]);
    Swipe::create(['user_id' => $user->id, 'movie_id' => $passedMovie->id, 'is_liked' => false]);

    // Other user likes Bad Movie
    Swipe::create(['user_id' => $otherUser->id, 'movie_id' => $passedMovie->id, 'is_liked' => true]);

    $response = $this->actingAs($user)->get('/watchlist');

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Movies/Watchlist')
            ->has('movies', 1)
            ->where('movies.0.tmdb_id', 401)
        );
});

test('user can delete a movie from their watchlist', function () {
    $user = User::factory()->create();
    $movie = Movie::create([
        'tmdb_id' => 501,
        'title' => 'Movie to Delete',
        'poster_path' => '/delete.jpg',
    ]);

    Swipe::create(['user_id' => $user->id, 'movie_id' => $movie->id, 'is_liked' => true]);

    $response = $this->actingAs($user)->deleteJson("/watchlist/{$movie->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('swipes', [
        'user_id' => $user->id,
        'movie_id' => $movie->id,
    ]);
});

test('user can schedule a watch date and time for a movie', function () {
    $user = User::factory()->create();
    $movie = Movie::create([
        'tmdb_id' => 601,
        'title' => 'Scheduled Movie',
        'poster_path' => '/scheduled.jpg',
    ]);

    Swipe::create(['user_id' => $user->id, 'movie_id' => $movie->id, 'is_liked' => true]);

    $scheduledAt = '2026-08-15T20:00:00';

    $response = $this->actingAs($user)->patchJson("/watchlist/{$movie->id}", [
        'watch_scheduled_at' => $scheduledAt,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('swipes', [
        'user_id' => $user->id,
        'movie_id' => $movie->id,
        'watch_scheduled_at' => '2026-08-15T20:00:00',
    ]);
});

test('user can mark movie as watched with rating and review', function () {
    $user = User::factory()->create();
    $movie = Movie::create([
        'tmdb_id' => 701,
        'title' => 'Watched Movie',
        'poster_path' => '/watched.jpg',
    ]);

    Swipe::create(['user_id' => $user->id, 'movie_id' => $movie->id, 'is_liked' => true]);

    $response = $this->actingAs($user)->patchJson("/watchlist/{$movie->id}/watch-status", [
        'is_watched' => true,
        'user_rating' => 5,
        'user_review' => 'Absolute masterpiece!',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('swipes', [
        'user_id' => $user->id,
        'movie_id' => $movie->id,
        'is_watched' => true,
        'user_rating' => 5,
        'user_review' => 'Absolute masterpiece!',
    ]);
});
