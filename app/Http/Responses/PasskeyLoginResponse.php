<?php

namespace App\Http\Responses;

use Laravel\Passkeys\Http\Responses\PasskeyLoginResponse as BaseResponse;

/**
 * A passkey sign-in is a fresh, strong authentication, so it counts as recent
 * confirmation for changing sign-in methods (like an email-link sign-in does).
 */
class PasskeyLoginResponse extends BaseResponse
{
    public function toResponse($request)
    {
        $request->session()->passwordConfirmed();

        return parent::toResponse($request);
    }
}
