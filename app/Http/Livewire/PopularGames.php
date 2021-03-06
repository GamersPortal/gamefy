<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PopularGames extends Component
{

    public $popularGames = [];

    public function loadPopularGames()
    {
        $popularGamesUnformatted = cache()->remember('popular-games', 7, function() {
            return Http::withHeaders(config('services.igdb.auth'))->withBody(
                "
                    fields name, cover.url, first_release_date, total_rating_count, platforms.abbreviation, rating, slug;
                    where platforms = (48,49,130,6)
                    & (first_release_date >= " . now()->subMonths(2)->timestamp . "
                    & first_release_date < " . now()->subWeek()->timestamp . "
                    & total_rating_count > 5);
                    sort total_rating_count desc;
                    limit 12;", 'text/plain'
                )->post(config('services.igdb.endpoint'))->json();
        });

        $this->popularGames = $this->formatForView($popularGamesUnformatted);
        collect($this->popularGames)->filter(function($game) {
            return $game['rating'];
        })->each(function($game) {
            $this->emit('gameWithRatingFetched', [
                'slug' => $game['slug'], 
                'rating' => $game['rating'] / 100
            ]);
        });

    }

    public function render()
    {
        return view('livewire.popular-games');
    }

    protected function formatForView($games)
    {
        return collect($games)->map(function($game) {
            return collect($game)->merge([
                'cover_image_url' => isset($game['cover']) ? Str::replaceFirst('thumb', 'cover_big', $game['cover']['url']) : asset('images/sample-game-cover.png'), 
                'rating' => isset($game['rating']) ? round($game['rating']) : null, 
                'platforms' => implode(', ', collect($game['platforms'])->pluck('abbreviation')->toArray()), 
            ]);
        });
    }
}
