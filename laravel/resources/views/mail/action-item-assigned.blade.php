<x-mail::message>
# Вам назначено действие

В ходе ретро **«{{ $item->retro->title }}»** за вами закреплено действие:

<x-mail::panel>
{{ $item->body }}
</x-mail::panel>

Откройте доску, чтобы отслеживать статус.

<x-mail::button :url="route('spa') . '/retros/' . $item->retro_id">
Открыть ретро
</x-mail::button>

С уважением,<br>{{ config('app.name') }}
</x-mail::message>
