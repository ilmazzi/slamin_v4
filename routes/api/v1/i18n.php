<?php

use App\Http\Controllers\Api\V1\I18n\ShowI18nController;
use Illuminate\Support\Facades\Route;

Route::get('i18n/{locale}.json', ShowI18nController::class)->name('api.v1.i18n.show');
