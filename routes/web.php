<?php

use Illuminate\Support\Facades\Route;
use Wsmallnews\Profile\Http\Controllers\RegionController;

Route::get('sn-profile/regions', RegionController::class)
    ->name('sn-profile::regions');
