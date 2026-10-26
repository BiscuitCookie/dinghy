<?php

use Illuminate\Support\Facades\Route;

Route::permanentRedirect('/', '/app');

Route::get('app/{any?}', fn () => view('spa'))->where('any', '.*')->name('spa');
