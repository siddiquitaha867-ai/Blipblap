<?php

use App\Http\Controllers\Api\CatalogueSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/catalogue/sync', CatalogueSyncController::class)->name('api.admin.catalogue.sync');
