<section>
    <h2>{{ __('Equipment checklist') }}</h2>
    <p class="small">{{ __('☒ = available, ☐ = missing. Tick "Checked" when you check an item; update the app afterwards.') }}</p>
    @foreach ($groups as $area => $items)
        <h3>{{ \App\Modules\Equipment\Enums\EquipmentArea::from($area)->getLabel() }}</h3>
        <table class="fixed">
            <thead>
                <tr>
                    <th>{{ __('Item') }}</th>
                    <th class="date">{{ __('Expires on') }}</th>
                    <th class="date">{{ __('Last check') }}</th>
                    <th class="tally">{{ __('Checked') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php($status = $item->status())
                    <tr>
                        <td>
                            {{ $item->available ? '☒' : '☐' }} {{ $item->name }}@if ($item->quantity !== null) <span class="muted">· {{ __('Quantity: :count', ['count' => $item->quantity]) }}</span>@endif
                            @if ($status !== \App\Modules\Equipment\Enums\EquipmentStatus::Ok && $status !== \App\Modules\Equipment\Enums\EquipmentStatus::Missing)
                                <span class="tag">{{ $status->getLabel() }}</span>
                            @endif
                            @if ($item->location || $item->note)
                                <div class="muted">{{ collect([$item->location, $item->note])->filter()->implode(' · ') }}</div>
                            @endif
                        </td>
                        <td class="date">{{ $item->expires_on?->locale(app()->getLocale())->isoFormat('L') ?? '–' }}</td>
                        <td class="date">{{ $item->last_checked_at?->locale(app()->getLocale())->isoFormat('L') ?? '–' }}</td>
                        <td class="tally"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</section>
