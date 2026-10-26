<?php

namespace Tests\Feature;

use App\Enums\RetroPhase;
use App\Models\Retro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RetroApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeRetro(User $facilitator, array $overrides = []): Retro
    {
        $retro = Retro::create(array_merge([
            'user_id'        => $facilitator->id,
            'title'          => 'API Retro',
            'is_anonymous'   => true,
            'votes_per_user' => 2,
        ], $overrides));

        foreach (['Хорошо', 'Плохо'] as $position => $title) {
            $retro->columns()->create(['title' => $title, 'position' => $position]);
        }

        $retro->participants()->attach($facilitator->id, ['role' => Retro::ROLE_FACILITATOR]);

        return $retro;
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_rejects_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrong',
        ])->assertUnprocessable();
    }

    public function test_register_returns_token_and_creates_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'New User',
            'email'                 => 'new@dinghy.tst',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
        $this->assertDatabaseHas('users', ['email'                       => 'new@dinghy.tst']);
    }

    public function test_register_validates_duplicates(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/register', [
            'name'                  => 'Dup',
            'email'                 => $user->email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_creates_card_via_api(): void
    {
        $user = User::factory()->create();
        $retro = $this->makeRetro($user);
        $retro->update(['phase' => RetroPhase::Collect]);
        $column = $retro->columns->first();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/columns/{$column->id}/cards", ['body' => 'Всё ок']);

        $response->assertCreated()->assertJsonPath('data.body', 'Всё ок');
        $this->assertDatabaseHas('cards', ['retro_column_id' => $column->id, 'body' => 'Всё ок']);
    }

    public function test_forbids_card_creation_outside_collect_phase(): void
    {
        $user = User::factory()->create();
        $retro = $this->makeRetro($user);
        $column = $retro->columns->first();

        Sanctum::actingAs($user);
        $this->postJson("/api/columns/{$column->id}/cards", ['body' => 'Поздно'])
            ->assertStatus(409);
    }

    public function test_vote_toggle_and_limit(): void
    {
        $user = User::factory()->create();
        $retro = $this->makeRetro($user, ['votes_per_user' => 2]);
        $retro->update(['phase'                            => RetroPhase::Vote]);
        $cards = $retro->columns->first()->cards()
            ->createMany([
                ['user_id' => $user->id, 'body' => 'К1'],
                ['user_id' => $user->id, 'body' => 'К2'],
                ['user_id' => $user->id, 'body' => 'К3'],
            ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/cards/{$cards[0]->id}/vote")->assertOk();
        $this->postJson("/api/cards/{$cards[1]->id}/vote")->assertOk();
        $this->postJson("/api/cards/{$cards[2]->id}/vote")->assertStatus(409);

        $this->assertSame(1, $cards[0]->votes()->count());

        $this->postJson("/api/cards/{$cards[0]->id}/vote")->assertOk();
        $this->assertSame(0, $cards[0]->fresh()->votes()->count());
    }

    public function test_stranger_cannot_create_card(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $retro = $this->makeRetro($owner);
        $retro->update(['phase' => RetroPhase::Collect]);
        $column = $retro->columns->first();

        Sanctum::actingAs($stranger);
        $this->postJson("/api/columns/{$column->id}/cards", ['body' => 'Взлом'])
            ->assertForbidden();
    }

    public function test_participant_cannot_delete_others_card_but_facilitator_can(): void
    {
        $facilitator = User::factory()->create();
        $participant = User::factory()->create();
        $retro = $this->makeRetro($facilitator);
        $card = $retro->columns->first()->cards()->create(['user_id' => $facilitator->id, 'body' => 'Чужая']);
        $retro->participants()->attach($participant->id, ['role'     => Retro::ROLE_PARTICIPANT]);

        Sanctum::actingAs($participant);
        $this->deleteJson("/api/cards/{$card->id}")->assertForbidden();
        $this->assertDatabaseHas('cards', ['id' => $card->id]);

        Sanctum::actingAs($facilitator);
        $this->deleteJson("/api/cards/{$card->id}")->assertNoContent();
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_action_items_lifecycle(): void
    {
        $user = User::factory()->create();
        $retro = $this->makeRetro($user);

        Sanctum::actingAs($user);
        $this->postJson("/api/retros/{$retro->id}/action-items", ['body' => 'Раньше мёржить'])
            ->assertStatus(409);

        $retro->update(['phase' => RetroPhase::Discuss]);

        $response = $this->postJson("/api/retros/{$retro->id}/action-items", [
            'body'        => 'Раньше мёржить',
            'assignee_id' => $user->id,
        ]);
        $response->assertCreated()->assertJsonPath('data.status', 'open');

        $itemId = $response->json('data.id');
        $this->patchJson("/api/action-items/{$itemId}", ['status' => 'done'])->assertOk();
        $this->assertDatabaseHas('action_items', ['id'            => $itemId, 'status' => 'done']);

        $this->deleteJson("/api/action-items/{$itemId}")->assertNoContent();
    }

    public function test_action_item_assignment_dispatches_queued_mail(): void
    {
        $assignee = User::factory()->create();
        $user = User::factory()->create();
        $retro = $this->makeRetro($user);
        $retro->update(['phase' => RetroPhase::Discuss]);

        Queue::fake();

        Sanctum::actingAs($user);
        $this->postJson("/api/retros/{$retro->id}/action-items", [
            'body'        => 'Прочитать метрики',
            'assignee_id' => $assignee->id,
        ])->assertCreated();

        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_meta_exposes_phases_and_templates(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/meta')
            ->assertOk()
            ->assertJsonCount(5, 'phases')
            ->assertJsonPath('phases.1.value', 'collect')
            ->assertJsonPath('phases.1.label', 'Сбор карточек')
            ->assertJsonCount(3, 'templates')
            ->assertJsonPath('templates.0.key', 'classic');
    }

    public function test_retro_resource_always_contains_counters(): void
    {
        $user = User::factory()->create();
        $retro = $this->makeRetro($user);
        $retro->columns->first()->cards()->create(['user_id' => $user->id, 'body' => 'Карточка']);

        Sanctum::actingAs($user);

        // index
        $this->getJson('/api/retros')
            ->assertOk()
            ->assertJsonPath('owned.0.participants_count', 1)
            ->assertJsonPath('owned.0.cards_count', 1);

        // show
        $this->getJson("/api/retros/{$retro->id}")
            ->assertOk()
            ->assertJsonPath('retro.participants_count', 1)
            ->assertJsonPath('retro.cards_count', 1);

        // join (ответ без withCount)
        $guest = User::factory()->create();
        Sanctum::actingAs($guest);
        $this->postJson('/api/retros/join', ['invite_code' => $retro->invite_code])
            ->assertOk()
            ->assertJsonStructure(['data' => ['participants_count', 'cards_count']]);
    }

    public function test_show_includes_action_items_only_in_discussion(): void
    {
        $user = User::factory()->create();
        $retro = $this->makeRetro($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/retros/{$retro->id}")
            ->assertOk()
            ->assertJsonPath('action_items', []);

        $retro->update(['phase'               => RetroPhase::Discuss]);
        $retro->actionItems()->create(['body' => 'Действие', 'position' => 0]);

        $this->getJson("/api/retros/{$retro->id}")
            ->assertOk()
            ->assertJsonCount(1, 'action_items');
    }
}
