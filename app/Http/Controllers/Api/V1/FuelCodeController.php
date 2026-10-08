<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFuelCodeRequest;
use App\Http\Requests\StoreFuelCodeRequest;
use App\Http\Resources\FuelCodeResource;
use App\Models\BuyerWallet;
use App\Models\FuelCode;
use App\Models\Station;
use App\Models\WalletAccount;
use App\Models\WalletHold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FuelCodeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = request()->user();

        abort_unless(
            $user->hasRole('BUYER'),
            403,
            'Buyer role required.'
        );

        $codes = FuelCode::query()
            ->where(
                'buyer_user_id',
                $user->id
            )
            ->where(
                'tenant_id',
                $user->tenant_id
            )
            ->with([
                'station',
                'fuelProduct',
            ])
            ->latest()
            ->paginate(25);

        return FuelCodeResource::collection($codes);
    }

    public function store(StoreFuelCodeRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('BUYER'),
            403,
            'Buyer role required.'
        );

        $station = Station::query()
            ->where(
                'id',
                $request->station_id
            )
            ->where(
                'tenant_id',
                $user->tenant_id
            )
            ->first();

        if (! $station) {
            return response()->json([
                'error' => [
                    'code' => 'STATION_ACCESS_DENIED',
                    'message' => 'The selected station is not available to this buyer.',
                    'details' => [],
                ],
            ], 403);
        }

        if ($station->status !== 'ACTIVE') {
            return response()->json([
                'error' => [
                    'code' => 'STATION_NOT_ACTIVE',
                    'message' => 'Fuel authorization can only be created for an active station.',
                    'details' => [],
                ],
            ], 409);
        }

        $wallet = BuyerWallet::query()
            ->where(
                'tenant_id',
                $user->tenant_id
            )
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->first();

        if (! $wallet) {
            return response()->json([
                'error' => [
                    'code' => 'ACTIVE_WALLET_REQUIRED',
                    'message' => 'An active buyer wallet is required.',
                    'details' => [],
                ],
            ], 409);
        }

        $existing = FuelCode::query()
            ->where(
                'tenant_id',
                $user->tenant_id
            )
            ->where(
                'idempotency_key',
                $request->idempotency_key
            )
            ->with([
                'station',
                'fuelProduct',
            ])
            ->first();

        if ($existing) {
            return response()->json([
                'data' => new FuelCodeResource($existing),
                'meta' => [
                    'idempotent_replay' => true,
                    'credentials_reissued' => false,
                ],
            ]);
        }

        $result = DB::transaction(function () use ($request, $user, $wallet, $station) {
            $mode = $request->authorization_mode;

            $accountQuery = WalletAccount::query()
                ->where(
                    'wallet_id',
                    $wallet->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                );

            if ($mode === 'AMOUNT') {
                $accountQuery
                    ->where(
                        'asset_type',
                        'NGN'
                    )
                    ->whereNull(
                        'fuel_product_id'
                    );
                $assetType = 'NGN';
            } else {
                $accountQuery
                    ->where(
                        'asset_type',
                        'LITRE'
                    )
                    ->where(
                        'fuel_product_id',
                        $request->fuel_product_id
                    );
                $assetType = 'LITRE';
            }

            $account = $accountQuery
                ->lockForUpdate()
                ->first();

            if (! $account) {
                abort(
                    422,
                    'Required wallet account does not exist.'
                );
            }

            WalletHold::query()
                ->where(
                    'wallet_account_id',
                    $account->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->where(
                    'expires_at',
                    '<=',
                    now()
                )
                ->update([
                    'status' => 'EXPIRED',
                    'released_at' => now(),
                ]);

            $activeHolds = WalletHold::query()
                ->where(
                    'wallet_account_id',
                    $account->id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->where(
                    'expires_at',
                    '>',
                    now()
                );

            if ($mode === 'AMOUNT') {
                $heldMinor = (int) (clone $activeHolds)->sum('amount_minor');
                $availableMinor = (int) $account->balance_minor - $heldMinor;

                if ($availableMinor < (int) $request->amount_minor) {
                    abort(
                        422,
                        'Insufficient available NGN wallet balance.'
                    );
                }
            } else {
                $heldLitres = (float) (clone $activeHolds)->sum('volume_litres');
                $availableLitres = (float) $account->balance_litres - $heldLitres;

                if ($availableLitres < (float) $request->volume_litres) {
                    abort(
                        422,
                        'Insufficient available litre balance.'
                    );
                }
            }

            $ttl = (int) ($request->ttl_minutes ?? 15);
            $expiresAt = now()->addMinutes($ttl);

            do {
                $code = 'FS-' . strtoupper(Str::random(8));
            } while (
                FuelCode::where(
                    'code',
                    $code
                )->exists()
            );

            $pin = (string) random_int(100000, 999999);
            $qrToken = Str::random(64);

            $hold = WalletHold::create([
                'tenant_id' => $user->tenant_id,
                'wallet_id' => $wallet->id,
                'wallet_account_id' => $account->id,
                'asset_type' => $assetType,
                'fuel_product_id' => $request->fuel_product_id,
                'amount_minor' => $mode === 'AMOUNT' ? $request->amount_minor : null,
                'volume_litres' => $mode === 'LITRES' ? $request->volume_litres : null,
                'status' => 'ACTIVE',
                'reference_type' => 'FUEL_CODE',
                'reference_id' => null,
                'expires_at' => $expiresAt,
            ]);

            $fuelCode = FuelCode::create([
                'tenant_id' => $user->tenant_id,
                'buyer_user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'wallet_account_id' => $account->id,
                'wallet_hold_id' => $hold->id,
                'station_id' => $station->id,
                'fuel_product_id' => $request->fuel_product_id,
                'asset_type' => $assetType,
                'authorization_mode' => $mode,
                'authorized_amount_minor' => $mode === 'AMOUNT' ? $request->amount_minor : null,
                'authorized_volume_litres' => $mode === 'LITRES' ? $request->volume_litres : null,
                'code' => $code,
                'qr_token_hash' => hash('sha256', $qrToken),
                'pin_hash' => Hash::make($pin),
                'status' => 'ACTIVE',
                'expires_at' => $expiresAt,
                'idempotency_key' => $request->idempotency_key,
            ]);

            $hold->update([
                'reference_id' => $fuelCode->id,
            ]);

            $fuelCode->load([
                'station',
                'fuelProduct',
            ]);

            return [
                'fuel_code' => $fuelCode,
                'pin' => $pin,
                'qr_token' => $qrToken,
            ];
        });

        $fuelCode = $result['fuel_code'];
        $qrPayload = sprintf(
            'FUELSTAMP|%s|%s',
            $fuelCode->code,
            $result['qr_token']
        );

        return response()->json([
            'data' => new FuelCodeResource($fuelCode),
            'credentials' => [
                'pin' => $result['pin'],
                'qr_token' => $result['qr_token'],
                'qr_payload' => $qrPayload,
            ],
        ], 201);
    }

    public function show(FuelCode $fuelCode): JsonResponse
    {
        $user = request()->user();

        abort_if(
            $fuelCode->buyer_user_id !== $user->id,
            403,
            'You do not own this fuel authorization.'
        );

        $this->expireIfNeeded($fuelCode);
        $fuelCode->load([
            'station',
            'fuelProduct',
        ]);

        return response()->json([
            'data' => new FuelCodeResource($fuelCode),
        ]);
    }

    public function cancel(CancelFuelCodeRequest $request, FuelCode $fuelCode): JsonResponse
    {
        $user = $request->user();

        abort_if(
            $fuelCode->buyer_user_id !== $user->id,
            403,
            'You do not own this fuel authorization.'
        );

        $this->expireIfNeeded($fuelCode);

        if ($fuelCode->status !== 'ACTIVE') {
            return response()->json([
                'error' => [
                    'code' => 'FUEL_CODE_NOT_CANCELLABLE',
                    'message' => 'Only an active fuel code can be cancelled.',
                    'details' => [
                        'status' => $fuelCode->status,
                    ],
                ],
            ], 409);
        }

        DB::transaction(function () use ($request, $user, $fuelCode) {
            $fuelCode->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $user->id,
                'cancellation_reason' => $request->reason,
            ]);

            WalletHold::query()
                ->where(
                    'id',
                    $fuelCode->wallet_hold_id
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->update([
                    'status' => 'RELEASED',
                    'released_at' => now(),
                ]);
        });

        return response()->json([
            'data' => new FuelCodeResource(
                $fuelCode->fresh([
                    'station',
                    'fuelProduct',
                ])
            ),
        ]);
    }

    private function expireIfNeeded(FuelCode $fuelCode): void
    {
        if (
            $fuelCode->status === 'ACTIVE'
            && $fuelCode->expires_at->lte(now())
        ) {
            DB::transaction(function () use ($fuelCode) {
                $fuelCode->update([
                    'status' => 'EXPIRED',
                ]);

                WalletHold::query()
                    ->where(
                        'id',
                        $fuelCode->wallet_hold_id
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->update([
                        'status' => 'EXPIRED',
                        'released_at' => now(),
                    ]);
            });
        }
    }
}
