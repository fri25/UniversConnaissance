<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FakePaymentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ThankYouController;
use Illuminate\Support\Facades\Route;

// --- Vitrine -----------------------------------------------------------------
Route::get('/', HomeController::class)->name('home');
Route::get('/livres', [CatalogController::class, 'index'])->name('books.index');
Route::get('/categorie/{category:slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/product/{book:slug}', [BookController::class, 'show'])->name('books.show');
Route::get('/product/{book:slug}/extrait', [BookController::class, 'sample'])
    ->middleware('throttle:30,1')->name('books.sample');

Route::view('/aide', 'pages.help')->name('pages.help');
Route::view('/mentions-legales', 'pages.legal')->name('pages.legal');
Route::view('/cgv', 'pages.terms')->name('pages.terms');
Route::view('/confidentialite', 'pages.privacy')->name('pages.privacy');

// --- Achat sans connexion -----------------------------------------------------
// Formulaire (nom, email, téléphone) puis paiement créé via l'API Chariow.
Route::get('/acheter/{book:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/acheter/{book:slug}', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')->name('checkout.store');
// Retour après paiement : lien signé transmis au prestataire.
Route::get('/merci/{order}', ThankYouController::class)->middleware('signed')->name('checkout.return');

// Page de paiement simulée (local / démo uniquement), par lien signé.
Route::middleware('signed')->group(function () {
    Route::get('/paiement/simulation/{order}', [FakePaymentController::class, 'show'])->name('payment.fake.show');
    Route::post('/paiement/simulation/{order}', [FakePaymentController::class, 'complete'])
        ->middleware('throttle:checkout')->name('payment.fake.complete');
});

// Lien de téléchargement signé envoyé par email : utilisable sans connexion.
Route::get('/telecharger/{download:token}/{format}', DownloadController::class)
    ->whereIn('format', ['pdf', 'epub'])
    ->middleware(['signed', 'throttle:downloads'])->name('downloads.file');

// --- Webhook prestataire de paiement (hors CSRF, signature HMAC) -------------
Route::post('/webhooks/payment', PaymentWebhookController::class)
    ->middleware('throttle:webhook')->name('webhooks.payment');

// --- Espace client -------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [LibraryController::class, 'index'])->name('dashboard');
    Route::get('/mes-achats/{order}/telecharger/{format}', [LibraryController::class, 'download'])
        ->whereIn('format', ['pdf', 'epub'])->name('library.download');

    Route::post('/product/{book:slug}/avis', [ReviewController::class, 'store'])
        ->middleware('throttle:reviews')->name('reviews.store');
    Route::put('/avis/{review}', [ReviewController::class, 'update'])
        ->middleware('throttle:reviews')->name('reviews.update');
    Route::delete('/avis/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- Back-office ---------------------------------------------------------------
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('books/import', [Admin\BookImportController::class, 'create'])->name('books.import');
    Route::post('books/import', [Admin\BookImportController::class, 'store'])->name('books.import.store');
    Route::resource('books', Admin\BookController::class)->except('show');
    Route::post('editor/images', Admin\EditorImageController::class)
        ->middleware('throttle:60,1')->name('editor.images');
    Route::resource('authors', Admin\AuthorController::class)->except('show');
    Route::resource('categories', Admin\CategoryController::class)->except('show');

    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/resend', [Admin\OrderController::class, 'resend'])->name('orders.resend');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/admin', [Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approval', [Admin\ReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
});

require __DIR__.'/auth.php';
