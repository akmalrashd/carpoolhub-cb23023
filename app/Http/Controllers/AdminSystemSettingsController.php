<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateGatewaySettingsRequest;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Models\SystemSetting;
use App\Services\AdminAuditService;
use App\Services\FuelPriceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminSystemSettingsController extends Controller
{
    private const FUEL_KEYS = [
        'fuel_price_ron95_budi',
        'fuel_price_ron95_market',
        'fuel_price_ron97_market',
        'fuel_price_diesel_market',
    ];

    private const GATEWAY_KEYS = [
        'gateway_fee_flat_amount',
        'wallet_min_withdrawal_amount',
    ];

    public function __construct(
        private readonly AdminAuditService $adminAuditService,
        private readonly FuelPriceService $fuelPriceService,
    ) {
    }

    public function index(): View
    {
        $fuelFallback = [];
        foreach (self::FUEL_KEYS as $key) {
            $fuelFallback[$key] = SystemSetting::get($key);
        }

        $gatewaySettings = [];
        foreach (self::GATEWAY_KEYS as $key) {
            $gatewaySettings[$key] = SystemSetting::get($key);
        }

        $livePrices = $this->fuelPriceService->current();

        return view('admin.system-settings.index', compact('fuelFallback', 'gatewaySettings', 'livePrices'));
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::FUEL_KEYS as $key) {
            SystemSetting::set($key, (string) $data[$key], $request->user()->id);
        }

        FuelPriceService::clearCache();

        $this->adminAuditService->log(
            $request->user(),
            'settings.fuel_price_updated',
            null,
            null,
            "RON95 budi={$data['fuel_price_ron95_budi']} market={$data['fuel_price_ron95_market']}, RON97={$data['fuel_price_ron97_market']}, Diesel={$data['fuel_price_diesel_market']}"
        );

        return redirect()
            ->route('admin.system-settings.index')
            ->with('status', 'Fuel price fallback updated.');
    }

    public function updateGateway(UpdateGatewaySettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::GATEWAY_KEYS as $key) {
            SystemSetting::set($key, (string) $data[$key], $request->user()->id);
        }

        $this->adminAuditService->log(
            $request->user(),
            'settings.gateway_updated',
            null,
            null,
            "Gateway fee=RM{$data['gateway_fee_flat_amount']}, Min withdrawal=RM{$data['wallet_min_withdrawal_amount']}"
        );

        return redirect()
            ->route('admin.system-settings.index')
            ->with('status', 'Online payment & wallet settings updated.');
    }
}
