@php
    $format = \App\Support\ShoppingList::format(...);
@endphp
<section>
    <h2>{{ __('Supply') }}</h2>
    @if ($adults + $children === 0)
        <p>{{ __('Enter how many adults and children live in the household to see the amounts.') }}</p>
    @else
        <p class="lead">
            {{ __('Your supply lasts :days of :target days.', ['days' => $daysOfSupply, 'target' => $targetDays]) }}
            {{ __('For :people.', ['people' => collect([trans_choice(':count adult|:count adults', $adults), $children > 0 ? trans_choice(':count child|:count children', $children) : null])->filter()->implode(', ')]) }}
        </p>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Category') }}</th>
                    <th class="num">{{ __('Need per day') }}</th>
                    <th class="num">{{ __('Stock') }}</th>
                    <th class="num">{{ __('Lasts') }}</th>
                    <th class="num">{{ __('Target') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($status as $item)
                    @php($unit = $item->target->category->unit)
                    <tr>
                        <td>{{ __($item->target->category->name) }}</td>
                        <td class="num strong">{{ $format($item->target->perDay, $unit) }}</td>
                        <td class="num">{{ $format($item->stock, $unit) }}</td>
                        <td class="num">{{ trans_choice(':count day|:count days', $item->days()) }}</td>
                        <td class="num">{{ $format($item->target->total, $unit) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="small">
            {{ __('Need per day = what the whole household needs per day; ration by it in an emergency.') }}
            {{ __('Only batches that have not expired count. 1 l counts as 1 kg.') }}
        </p>
    @endif
</section>
