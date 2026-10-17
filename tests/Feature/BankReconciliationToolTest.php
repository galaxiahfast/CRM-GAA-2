<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankReconciliationToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_tools_require_authentication(): void
    {
        $this->get(route('bank-reconciliation.show', 'ocr'))->assertRedirect(route('login'));
        $this->get(route('bank-reconciliation.content', 'bancaria'))->assertRedirect(route('login'));
    }

    public function test_every_authenticated_user_can_open_both_tools(): void
    {
        $user = User::factory()->create();

        foreach (['ocr', 'bancaria'] as $tool) {
            $this->actingAs($user)
                ->get(route('bank-reconciliation.show', $tool))
                ->assertOk()
                ->assertSee(route('bank-reconciliation.content', $tool), false);

            $this->actingAs($user)
                ->get(route('bank-reconciliation.content', $tool))
                ->assertOk()
                ->assertHeader('content-type', 'text/html; charset=UTF-8');
        }
    }

    public function test_sidebar_shows_both_tools_to_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('bank-reconciliation.show', 'ocr'))
            ->assertOk()
            ->assertSeeText('Conciliación OCR')
            ->assertSeeText('Conciliación Bancaria');
    }
}
