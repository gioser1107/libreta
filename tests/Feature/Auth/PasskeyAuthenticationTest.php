<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasskeyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_offers_face_id(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Face ID');
    }

    public function test_profile_offers_face_id_activation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSeeVolt('profile.manage-passkeys')
            ->assertSee('Activar Face ID');
    }

    public function test_login_options_require_biometrics(): void
    {
        $this->getJson(route('passkey.login-options'))
            ->assertOk()
            ->assertJsonPath('options.userVerification', 'required')
            ->assertJsonStructure([
                'options' => ['challenge', 'rpId', 'timeout'],
            ]);
    }

    public function test_authenticated_user_cannot_request_login_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('passkey.login-options'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_guest_cannot_request_registration_options(): void
    {
        $this->getJson(route('passkey.registration-options'))
            ->assertUnauthorized();
    }

    public function test_registration_options_require_a_confirmed_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('passkey.registration-options'))
            ->assertStatus(423);
    }

    public function test_registration_options_ask_for_this_devices_biometrics(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson(route('passkey.registration-options'))
            ->assertOk()
            ->assertJsonPath('options.authenticatorSelection.authenticatorAttachment', 'platform')
            ->assertJsonPath('options.authenticatorSelection.userVerification', 'required')
            ->assertJsonPath('options.authenticatorSelection.residentKey', 'required');
    }

    public function test_invalid_face_id_login_stays_signed_out(): void
    {
        $this->postJson(route('passkey.login'), [
            'credential' => [
                'id' => 'not-a-passkey',
                'rawId' => 'not-a-passkey',
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => 'not-a-passkey',
                    'authenticatorData' => 'not-a-passkey',
                    'signature' => 'not-a-passkey',
                ],
            ],
            'remember' => false,
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_user_cannot_delete_another_users_face_id(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $passkey = $owner->passkeys()->create([
            'name' => 'iPhone',
            'credential_id' => 'owner-credential',
            'credential' => ['id' => 'owner-credential'],
        ]);

        $this->actingAs($intruder)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('passkey.destroy', $passkey))
            ->assertForbidden();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_wrong_password_does_not_start_face_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('profile.manage-passkeys')
            ->call('startRegistration')
            ->set('password', 'incorrecta')
            ->call('confirmPassword')
            ->assertHasErrors(['password' => 'La contraseña no coincide.'])
            ->assertNotDispatched('passkey-register');
    }

    public function test_correct_password_starts_face_id_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('profile.manage-passkeys')
            ->call('startRegistration')
            ->assertSet('confirming', true)
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertHasNoErrors()
            ->assertSet('confirming', false)
            ->assertDispatched('passkey-register');
    }

    public function test_confirmed_password_starts_face_id_without_asking_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.manage-passkeys')
            ->call('startRegistration')
            ->set('password', 'password')
            ->call('confirmPassword');

        $component->call('startRegistration')
            ->assertSet('confirming', false)
            ->assertDispatched('passkey-register');
    }

    public function test_user_can_remove_their_face_id(): void
    {
        $user = User::factory()->create();
        $passkey = $user->passkeys()->create([
            'name' => 'Este dispositivo',
            'credential_id' => 'own-credential',
            'credential' => ['id' => 'own-credential'],
        ]);

        $this->actingAs($user);

        Volt::test('profile.manage-passkeys')
            ->call('deletePasskey', $passkey->id)
            ->assertSet('confirming', true)
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
    }

    public function test_user_cannot_remove_a_missing_face_id_from_the_account_screen(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $passkey = $owner->passkeys()->create([
            'name' => 'iPhone',
            'credential_id' => 'foreign-credential',
            'credential' => ['id' => 'foreign-credential'],
        ]);

        $this->actingAs($intruder);

        Volt::test('profile.manage-passkeys')
            ->call('deletePasskey', $passkey->id)
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertNotFound();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }
}
