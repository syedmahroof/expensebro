<x-mail::message>
# Verify your email address

Please use the following OTP code to verify your email address. This code will expire in 15 minutes.

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
