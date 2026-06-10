<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\VerifyEmailRequest as FortifyVerifyEmailRequest;

class VerifyEmailRequest extends FortifyVerifyEmailRequest
{
    public function authorize(): bool
    {
        if (! hash_equals((string) $this->user()->public_id, (string) $this->route('id'))) {
            return false;
        }

        if (! hash_equals(sha1($this->user()->getEmailForVerification()), (string) $this->route('hash'))) {
            return false;
        }

        return true;
    }
}
