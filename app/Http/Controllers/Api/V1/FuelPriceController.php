<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuelPriceRequest;
use App\Http\Resources\FuelPriceResource;
use App\Models\FuelPriceSchedule;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FuelPriceController extends Controller
{
    public function index(
        Station $station
    ): AnonymousResourceCollection {
        $this->ensureStationScope($station);

        $prices = FuelPriceSchedule::query()
            ->where('station_id', $station->id)
            ->with('fuelProduct')
            ->orderByDesc('effective_from')
            ->get();

        return FuelPriceResource::collection($prices);
    }

    public function current(
        Station $station
    ): AnonymousResourceCollection {
        $this->ensureStationScope($station);

        $now = now();

        $prices = FuelPriceSchedule::query()
            ->where('station_id', $station->id)
            ->where('effective_from', '<=', $now)
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $now);
            })
            ->with('fuelProduct')
            ->orderBy('fuel_product_id')
            ->get();

        return FuelPriceResource::collection($prices);
    }

    public function store(
        StoreFuelPriceRequest $request,
        Station $station
    ): JsonResponse {
        $this->ensureStationScope($station);

        $effectiveFrom = Carbon::parse(
            $request->effective_from
        );

        $price = DB::transaction(
            function () use (
                $request,
                $station,
                $effectiveFrom
            ) {
                /*
                 * Prevent duplicate schedules beginning
                 * at exactly the same moment.
                 */
                $duplicate = FuelPriceSchedule::query()
                    ->where('station_id', $station->id)
                    ->where(
                        'fuel_product_id',
                        $request->fuel_product_id
                    )
                    ->where(
                        'effective_from',
                        $effectiveFrom
                    )
                    ->exists();

                if ($duplicate) {
                    abort(
                        422,
                        'A fuel price already starts at this time.'
                    );
                }

                /*
                 * Find the most recent price that starts
                 * before the new price.
                 */
                $previous = FuelPriceSchedule::query()
                    ->where('station_id', $station->id)
                    ->where(
                        'fuel_product_id',
                        $request->fuel_product_id
                    )
                    ->where(
                        'effective_from',
                        '<',
                        $effectiveFrom
                    )
                    ->orderByDesc('effective_from')
                    ->lockForUpdate()
                    ->first();

                /*
                 * Close the previous schedule exactly when
                 * the new schedule starts.
                 */
                if (
                    $previous &&
                    (
                        $previous->effective_to === null ||
                        $previous->effective_to >
                            $effectiveFrom
                    )
                ) {
                    $previous->update([
                        'effective_to' =>
                            $effectiveFrom,
                    ]);
                }

                /*
                 * Check if another future price is already
                 * scheduled.
                 */
                $next = FuelPriceSchedule::query()
                    ->where('station_id', $station->id)
                    ->where(
                        'fuel_product_id',
                        $request->fuel_product_id
                    )
                    ->where(
                        'effective_from',
                        '>',
                        $effectiveFrom
                    )
                    ->orderBy('effective_from')
                    ->lockForUpdate()
                    ->first();

                return FuelPriceSchedule::create([
                    'tenant_id' =>
                        $station->tenant_id,

                    'station_id' =>
                        $station->id,

                    'fuel_product_id' =>
                        $request->fuel_product_id,

                    'price_minor_per_litre' =>
                        $request->price_minor_per_litre,

                    'effective_from' =>
                        $effectiveFrom,

                    'effective_to' =>
                        $next?->effective_from,

                    'created_by_user_id' =>
                        $request->user()->id,
                ]);
            }
        );

        $price->load('fuelProduct');

        return response()->json([
            'data' =>
                new FuelPriceResource($price),
        ], 201);
    }

    private function ensureStationScope(
        Station $station
    ): void {
        $user = request()->user();

        if ($user->hasRole('PLATFORM_ADMIN')) {
            return;
        }

        abort_if(
            $station->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this station.'
        );
    }
}