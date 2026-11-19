@if($e?->foto_url)
    <img src="{{ $e->foto_url }}" class="avatar {{ $clase ?? '' }}" alt="">
@else
    <span class="avatar {{ $clase ?? '' }}">{{ $e?->iniciales ?: '?' }}</span>
@endif
