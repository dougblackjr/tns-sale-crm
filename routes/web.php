<?php

use App\Http\Controllers\CrmController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/crm');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/crm', [CrmController::class, 'index'])->name('crm.index');
    
    Route::post('/crm/companies', [CrmController::class, 'storeCompany'])->name('crm.companies.store');
    Route::post('/crm/contacts', [CrmController::class, 'storeContact'])->name('crm.contacts.store');
    Route::post('/crm/projects', [CrmController::class, 'storeProject'])->name('crm.projects.store');
    Route::patch('/crm/projects/{project}', [CrmController::class, 'updateProject'])->name('crm.projects.update');
    Route::patch('/crm/projects/{project}/stage', [CrmController::class, 'updateProjectStage'])->name('crm.projects.stage');
    Route::delete('/crm/projects/{project}', [CrmController::class, 'deleteProject'])->name('crm.projects.destroy');
    Route::post('/crm/tags', [CrmController::class, 'storeTag'])->name('crm.tags.store');
});