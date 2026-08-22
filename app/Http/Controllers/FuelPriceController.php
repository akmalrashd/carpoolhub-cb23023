<?php

namespace App\Http\Controllers;

use App\Services\FuelPriceService;
use Illuminate\Http\JsonResponse;

class FuelPriceController extends Controller
{
    public function __construct(private readonly FuelPriceService $fuelPriceService)
    {
    }

    public function current(): JsonResponse
    {
        return response()->json($this->fuelPriceService->current());
    }
}
