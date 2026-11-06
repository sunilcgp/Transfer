<x-mail::message>
# Files shared with you

{{ $transfer->sender_email }} shared {{ $transfer->file_count }} file(s) with you using My Transfer.

@if($transfer->message)
> {{ $transfer->message }}
@endif

<x-mail::button :url="route('transfers.show', $transfer->token)">
Download files
</x-mail::button>

This link expires {{ $transfer->expires_at->format('F j, Y') }}.
Thanks,<br>
My Transfer
</x-mail::message>
