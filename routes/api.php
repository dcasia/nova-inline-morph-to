<?php

use DigitalCreative\InlineMorphTo\Http\Controllers\MorphableController;
use Illuminate\Support\Facades\Route;

Route::get('/{resource}/morphable/{field}', MorphableController::class);
