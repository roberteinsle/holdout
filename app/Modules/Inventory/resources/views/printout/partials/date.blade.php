@php($status = $batch->status($eatSoonDays))
@if ($batch->best_before)
    {{ $batch->best_before->locale(app()->getLocale())->isoFormat('L') }}
    @if ($status === \App\Modules\Inventory\Enums\ExpiryStatus::Expired)
        <span class="tag">{{ $status->getLabel() }}</span>
    @elseif ($status === \App\Modules\Inventory\Enums\ExpiryStatus::EatSoon)
        <span class="tag tag-light">{{ $status->getLabel() }}</span>
    @endif
@else
    –
@endif
