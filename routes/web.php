<?php

use App\Livewire\LinkPage;
use Illuminate\Support\Facades\Route;

// Main shortened URL redirect route
Route::get('/l/{short_path}', LinkPage::class)
    ->where('short_path', '.*')
    ->name('link.redirect');
