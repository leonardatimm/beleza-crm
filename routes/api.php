<?php

use Illuminate\Support\Facades\Route;

// Namespace reservado para o aplicativo móvel. Nenhum token ou recurso é
// exposto até existir um consumidor e o fluxo Sanctum ser habilitado.
Route::prefix('v1')->group(function (): void {
    // Route::middleware('auth:sanctum')->group(...);
});
