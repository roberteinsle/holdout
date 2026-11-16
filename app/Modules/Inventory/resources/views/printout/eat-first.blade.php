<section>
    <h2>{{ __('Eat first') }}</h2>
    @if ($batches->isEmpty())
        <p>{{ __('No batch expires within the next :days days.', ['days' => $days]) }}</p>
    @else
        <p class="small">{{ __('Expired, or expiring within :days days.', ['days' => $days]) }}</p>
        <table>
            <thead>
                <tr>
                    <th class="date">{{ __('Best before') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th class="num">{{ __('Packages') }}</th>
                    <th>{{ __('Location') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batches as $batch)
                    <tr>
                        <td class="date">@include('inventory::printout.partials.date', ['eatSoonDays' => $days])</td>
                        <td>{{ $batch->product->name }}</td>
                        <td class="num">{{ $batch->quantityLabel() }}</td>
                        <td>{{ $batch->location?->name ?? '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
