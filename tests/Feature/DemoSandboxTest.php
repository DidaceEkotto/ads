<?php

namespace Tests\Feature;

use App\Enums\FraudReason;
use App\Models\FraudFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoSandboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_routes_return_404_when_disabled(): void
    {
        Config::set('demo.enabled', false);

        $this->get('/demo')->assertNotFound();
        $this->post('/demo/login/advertiser')->assertNotFound();
    }

    public function test_demo_sandbox_seeds_data_allows_one_click_login_and_simulations(): void
    {
        Config::set('demo.enabled', true);

        $this->get('/demo')->assertOk();

        $advertiser = User::query()->where('email', 'advertiser@demo.cm')->firstOrFail();
        $initialBalance = $advertiser->wallet->available_balance_xaf;

        // Simulation recharge +10 000 FCFA
        $this->post('/demo/simulate-deposit')->assertRedirect();
        $this->assertSame($initialBalance + 10000, $advertiser->wallet->fresh()->available_balance_xaf);

        // Simulation fraude bot
        $this->post('/demo/simulate-fraud')->assertRedirect();
        $this->assertTrue(FraudFlag::query()->where('reason', FraudReason::BotUserAgent)->exists());

        // Connexion 1 clic en Annonceur
        $this->post('/demo/login/advertiser')->assertRedirect(route('advertiser.dashboard'));
        $this->assertAuthenticatedAs($advertiser);
    }
}
