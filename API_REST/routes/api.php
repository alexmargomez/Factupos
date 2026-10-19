<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleDetailController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WorkerController;

Route::get('/', function () {
    return response()->json(['message' => 'API RESTful Inventory System'], 200);
});

Route::post('/login', [LoginController::class, 'login']); // Ruta para iniciar sesión

Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout']); // Ruta para cerrar sesión

// Rutas protegidas CRUD de productos
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'create']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
    });

    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'create']);
        Route::get('/{id}', [VehicleController::class, 'show']);
        Route::put('/{id}', [VehicleController::class, 'update']);
        Route::delete('/{id}', [VehicleController::class, 'destroy']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'create']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'create']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'create']);
        Route::get('/{id}', [InventoryController::class, 'stock']);
        Route::put('/{id}', [InventoryController::class, 'update']);
    });

    Route::prefix('inventory-movements')->group(function () {
        Route::get('/', [InventoryMovementController::class, 'index']);
        Route::post('/', [InventoryMovementController::class, 'create']);
        Route::get('/{id}', [InventoryMovementController::class, 'show']);
        Route::put('/{id}', [InventoryMovementController::class, 'update']);
        Route::delete('/{id}', [InventoryMovementController::class, 'destroy']);
    });

    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'create']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::get('/pdf/{id}', [InvoiceController::class, 'showPdf']);
        Route::put('/{id}', [InvoiceController::class, 'update']);
        Route::delete('/{id}', [InvoiceController::class, 'destroy']);
    });

    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index']);
        Route::post('/', [SaleController::class, 'create']);
        Route::get('/{id}', [SaleController::class, 'show']);
        Route::put('/{id}', [SaleController::class, 'update']);
        Route::delete('/{id}', [SaleController::class, 'destroy']);
    });

    Route::prefix('sale-details')->group(function () {
        Route::get('/', [SaleDetailController::class, 'index']);
        Route::post('/', [SaleDetailController::class, 'create']);
        Route::get('/{id}', [SaleDetailController::class, 'show']);
        Route::get('/customer/{id}', [SaleDetailController::class, 'showCustomer']);
        Route::put('/{id}', [SaleDetailController::class, 'update']);
        Route::delete('/{id}', [SaleDetailController::class, 'destroy']);
    });

    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::post('/', [ScheduleController::class, 'create']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    });

    Route::prefix('services')->group(function () {
        Route::post('/', [ServiceController::class, 'create']);
        Route::get('/{id}', [ServiceController::class, 'show']);
        Route::get('/customer/{id}', [ServiceController::class, 'showCustomer']);
        Route::get('/vehicle/{id}', [ServiceController::class, 'showVehicle']);
    });
    Route::prefix('workers')->group(function () {
        Route::get('/', [WorkerController::class, 'index']);
        Route::post('/', [WorkerController::class, 'create']);
        Route::get('/{id}', [WorkerController::class, 'show']);
        Route::put('/{id}', [WorkerController::class, 'update']);
        Route::delete('/{id}', [WorkerController::class, 'destroy']);
    });
});

Route::middleware('auth:sanctum')->get('/p', function () {
    return 'Prueba';
});
