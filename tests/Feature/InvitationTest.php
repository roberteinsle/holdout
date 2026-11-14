<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function inviteAs(User $owner, string $email): string
    {
        Mail::fake();

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->callAction('invite', ['email' => $email, 'name' => 'Alex'])
            ->assertHasNoActionErrors();

        $url = null;
        Mail::assertQueued(InvitationMail::class, function (InvitationMail $mail) use (&$url) {
            $url = $mail->url;

            return true;
        });

        auth()->logout();

        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_an_owner_invites_and_the_invitee_joins_as_member(): void
    {
        $owner = User::factory()->owner()->create();
        $token = $this->inviteAs($owner, 'alex@example.com');

        $this->get(route('invitations.show', $token))->assertOk()->assertSee($owner->household->name);

        $this->post(route('invitations.accept', $token), ['name' => 'Alex Miller'])
            ->assertRedirect(route('filament.app.pages.security', ['welcome' => 1]));

        $member = User::query()->where('email', 'alex@example.com')->sole();
        $this->assertAuthenticatedAs($member);
        $this->assertSame($owner->household_id, $member->household_id);
        $this->assertSame(Role::Member, $member->role);
        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_an_invitation_works_only_once_and_expires(): void
    {
        $owner = User::factory()->owner()->create();
        $token = $this->inviteAs($owner, 'alex@example.com');

        $this->travel(Invitation::LIFETIME_DAYS + 1)->days();

        $this->post(route('invitations.accept', $token), ['name' => 'Alex'])->assertRedirect(route('invitations.show', $token));
        $this->assertGuest();
        $this->get(route('invitations.show', $token))->assertSee('This invitation no longer works');
    }

    public function test_members_cannot_invite_or_see_invitations(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member);
        Livewire::test(ListUsers::class)->assertActionHidden('invite');
        $this->get(ListInvitations::getUrl())->assertForbidden();
    }

    public function test_existing_accounts_cannot_be_invited(): void
    {
        $owner = User::factory()->owner()->create();
        $other = User::factory()->create();

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->callAction('invite', ['email' => $other->email])
            ->assertHasActionErrors(['email']);
    }

    public function test_owners_can_remove_members_but_not_themselves(): void
    {
        $owner = User::factory()->owner()->create();
        $member = User::factory()->for($owner->household)->create();
        $stranger = User::factory()->create();

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$owner, $member])
            ->assertCanNotSeeTableRecords([$stranger])
            ->assertActionHidden(TestAction::make('delete')->table($owner))
            ->callAction(TestAction::make('delete')->table($member));

        $this->assertModelMissing($member);
    }
}
