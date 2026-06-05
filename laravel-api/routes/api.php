<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use Illuminate\Support\Facades\Route;

/*
Rutas API para la gestión de contratos con autenticación.
*/

// Endpoint Público, Permitir a cualquiera de los 3 usuarios iniciar sesión
Route::post('/login', [AuthController::class, 'login']);

// Endpoints Protegidos, Resguardados con el Middleware de Sanctum (Se Exige Bearer Token válido)
Route::middleware('auth:sanctum')->group(function () {
    
    // Cerrar sesión destruyendo el token activo
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Operaciones del Negocio de Contratos (CRUD Aislado)
    Route::get('/documents', [DocumentController::class, 'index']);                  // Listar paginado (solo los suyos)
    Route::post('/documents', [DocumentController::class, 'store']);                 // Subir, procesar con Python y guardar
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']); // Descargar PDF físico
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);         // Eliminar de PostgreSQL y del disco
});