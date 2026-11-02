<x-mail::message>
# Welcome, {{ $user->name }}!

Thank you for registering with **{{ config('app.name') }}**. We're excited to have you on board.

You can now create posts, explore content, and engage with the community.

<x-mail::button :url="config('app.url')">
Visit {{ config('app.name') }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
