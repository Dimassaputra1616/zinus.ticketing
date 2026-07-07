<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompanyEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        $domain = str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : '';

        $allowedDomains = config('company.email_domains', []);
        $allowedExternalEmails = config('company.external_email_allowlist', []);

        if (in_array($email, $allowedExternalEmails, true)) {
            return;
        }

        if ($domain !== '' && in_array($domain, $allowedDomains, true)) {
            return;
        }

        $domainLabel = collect($allowedDomains)
            ->map(fn (string $domain) => '@' . $domain)
            ->join(', ');

        $fail(__('messages.company_email_domain_required', [
            'domains' => $domainLabel ?: '@zinus.com',
        ]));
    }
}
