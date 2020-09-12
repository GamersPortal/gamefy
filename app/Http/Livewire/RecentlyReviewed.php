<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RecentlyReviewed extends Component
{
    public $recentlyReviewed = [];

    public function loadRecentlyReviewed()
    {        
        $recentlyReviewedUnformatted = Http::withHeaders([
            'user-key' => config('services.igdb.key')
        ])->withOptions([
            'body' => "
                fields name, cover.url, first_release_date, popularity, platforms.abbreviation, rating, rating_count, summary, slug;
                where platforms = (48,49,130,6) 
                & (first_release_date >= " . now()->subYear()->timestamp . "
                & first_release_date < " . now()->timestamp . "
                & rating_count > 5);
                sort popularity desc;
                limit 3;
            "
        ])->get(config('services.igdb.endpoint'))->json();

        $this->recentlyReviewed = $this->formatForView($recentlyReviewedUnformatted);
    }

    public function render()
    {
        return view('livewire.recently-reviewed');
    }

    protected function formatForView($games)
    {
        return collect($games)->map(function($game) {
            return collect($game)->merge([
                'cover_image_url' => isset($game['cover']) ? Str::replaceFirst('thumb', 'cover_big', $game['cover']['url']) : asset('images/sample-game-cover.png'), 
                'rating' => isset($game['rating']) ? round($game['rating']) . '%' : null, 
                'platforms' => implode(', ', collect($game['platforms'])->pluck('abbreviation')->toArray()), 
            ]);
        });
    }
}