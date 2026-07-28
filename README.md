# MovieTinder 🎬🍿

MovieTinder is a modern, high-fidelity web application built using **Laravel 12**, **Inertia.js v2**, **Vue 3**, **TypeScript**, and **TailwindCSS**. It replicates a "Tinder-like" swipe-to-match UX flow for discovering and cataloging movies. 

Users can explore trending, upcoming, or search-query-based movies, swipe cards to "Like" or "Pass" them, manage a custom watchlist, schedule watch dates, and mark movies as watched with custom ratings and reviews.

---

## ✨ Features

- **Tinder-like Swiping Interface**: Swipe movie cards with desktop drag-and-drop physics or touch events.
- **TMDB API Integration**: Dynamically pull real-time popular, trending, and upcoming movies from The Movie Database (TMDB).
- **Personalized Recommendations**: Get custom recommendations tailored to your liked movie history.
- **Full Watchlist Management**:
  - Delete movies from your watchlist.
  - Schedule watch dates and times.
  - Track watch status, ratings (1–5 stars), and detailed text reviews.
- **Embedded Trailers & Streaming Info**: Play official YouTube trailers directly in a premium modal overlay, and view localized streaming watch providers.
- **Comprehensive Test Coverage**: Features a robust, database-driven test suite written using the **Pest PHP** testing framework.

---

## 🛠️ Technology Stack

- **Backend**: [Laravel 12](https://laravel.com/) (PHP 8.2)
- **Frontend SPA**: [Inertia.js v2](https://inertiajs.com/) with [Vue 3](https://vuejs.org/) & [TypeScript](https://www.typescriptlang.org/)
- **Styling**: [TailwindCSS v3](https://tailwindcss.com/)
- **Bundler**: [Vite](https://vite.dev/)
- **Testing**: [Pest PHP v3](https://pestphp.com/) & [PHPUnit v11](https://phpunit.de/)
- **Database**: SQLite / PostgreSQL / MySQL (supported out-of-the-box via Eloquent)
- **API Provider**: [The Movie Database (TMDB) API](https://www.themoviedb.org/documentation/api)

---

## 📦 Getting Started & Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js (v18+) & NPM

### Setup Instructions

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd MovieTinder
   ```

2. **Install Composer Dependencies**
   ```bash
   composer install
   ```

3. **Install NPM Dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   Copy the example environment file and generate the application encryption key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure TMDB API Keys**
   Get your API credentials from [TMDB](https://www.themoviedb.org/) and add them to your `.env` file:
   ```env
   TMDB_API_KEY=your_api_key_here
   TMDB_TOKEN="your_bearer_token_here"
   ```

6. **Database Setup**
   Choose your preferred database config in `.env` and migrate the tables:
   ```bash
   php artisan migrate
   ```

7. **Compile Frontend Assets**
   Build the production assets or run the Vite dev server:
   ```bash
   # Build production assets
   npm run build

   # OR run the Vite dev server for hot-reloading
   npm run dev
   ```

8. **Start the Application**
   Run the artisan serve command:
   ```bash
   php artisan serve
   ```
   Open `http://localhost:8000` in your web browser.

---

## 🏛️ Application Architecture & Core Logic

### Database Schema
The database contains three main tables:
- **`users`**: Manages credentials, verification, and sessions.
- **`movies`**: Caches and references movie metadata fetched from TMDB (`tmdb_id`, `title`, `poster_path`).
- **`swipes`**: Represents the relationship mapping users to movies with additional status fields:
  - `is_liked` (Boolean) - Whether the user liked (✓) or passed (✕) the movie.
  - `watch_scheduled_at` (Datetime) - Date and time set by the user to watch the movie.
  - `is_watched` (Boolean) - Whether the movie has been watched.
  - `user_rating` (Integer) - User rating from 1 to 5.
  - `user_review` (Text) - User review details.

### Core Services
- **`App\Services\TMDBService`**: Wraps the TMDB HTTP Client, implementing robust local cache wrappers (`Cache::remember()`) to limit external API overhead and accelerate load times. It handles:
  - Trending and filtered discover queues.
  - Search queries.
  - Similarity-based recommendations.
  - Trailer and streaming provider lookups.

### Controllers
- **`App\Http\Controllers\SwipeController`**: Manages the swiping deck, record logs, watchlist displays, scheduling, and watched status updates.

---

## 🧪 Testing

The test suite validates relationships, service logic, controller endpoints, and Inertia response assertions.

To run the Pest tests, use:
```bash
php artisan test
```

For a clean, compact test report:
```bash
php artisan test --compact
```

---

## 🚀 Deployment

The fastest way to deploy and scale this application is using **[Laravel Cloud](https://cloud.laravel.com/)**. You can provision background jobs, caching databases, database migrations, and manage deployment updates in a single workflow.
