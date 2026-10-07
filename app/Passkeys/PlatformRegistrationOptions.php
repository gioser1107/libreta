<?php

namespace App\Passkeys;

use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Webauthn\AuthenticatorSelectionCriteria;

class PlatformRegistrationOptions extends GenerateRegistrationOptions
{
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
    }
}
