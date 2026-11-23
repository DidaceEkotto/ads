<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StorePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentMethodController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $methods = $request->user()->paymentMethods()
            ->orderByDesc('is_default')
            ->latest()
            ->get()
            ->map(fn (PaymentMethod $m) => [
                'id' => $m->id,
                'provider' => $m->provider,
                'provider_label' => $m->providerLabel(),
                'account_number' => $m->account_number,
                'account_name' => $m->account_name,
                'is_default' => $m->is_default,
                'is_verified' => $m->is_verified,
            ]);

        return Inertia::render('Publisher/PaymentMethods/Index', [
            'methods' => $methods,
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $isDefault = $request->boolean('is_default')
                || ! $user->paymentMethods()->exists();

            if ($isDefault) {
                $user->paymentMethods()->update(['is_default' => false]);
            }

            $user->paymentMethods()->create([
                'provider' => $request->validated('provider'),
                'account_number' => $request->validated('account_number'),
                'account_name' => $request->validated('account_name'),
                'is_default' => $isDefault,
            ]);
        });

        return back()->with('success', __('Moyen de paiement ajouté avec succès.'));
    }

    public function setDefault(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        Gate::authorize('update', $paymentMethod);

        DB::transaction(function () use ($request, $paymentMethod): void {
            $request->user()->paymentMethods()->update(['is_default' => false]);
            $paymentMethod->update(['is_default' => true]);
        });

        return back()->with('success', __('Compte par défaut mis à jour.'));
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        Gate::authorize('delete', $paymentMethod);
        $paymentMethod->delete();

        return back()->with('success', __('Moyen de paiement supprimé.'));
    }
}
