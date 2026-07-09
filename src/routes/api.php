<?php

use Illuminate\Support\Facades\Route;
use DevMahmoudi\Blog\Http\Controllers\Api\PostController;

/*
|--------------------------------------------------------------------------
| Blog API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/blog
|
*/

Route::prefix('blog')->name('api.blog.')->group(function () {
    
    // Public routes
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/recent', [PostController::class, 'recent'])->name('posts.recent');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('posts', [PostController::class, 'store'])->name('posts.store');
        Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::patch('posts/{post}', [PostController::class, 'update'])->name('posts.update.partial');
        Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });
});