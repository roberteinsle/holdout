<section>
    <h2>{{ __('Stock by location') }}</h2>
    @if ($groups->isEmpty())
        <p>{{ __('No batches yet.') }}</p>
    @else
        <p class="small">{{ __('Make a mark under "Taken" for every package you take out; update the app afterwards.') }}</p>
        @foreach ($groups as $location => $batches)
            <h3>{{ $location !== '' ? $location : __('No location') }}</h3>
            <table class="fixed">
                <thead>
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th class="num">{{ __('Packages') }}</th>
                        <th class="date">{{ __('Best before') }}</th>
                        <th class="tally">{{ __('Taken') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $batch)
                        <tr>
                            <td>
                                {{ $batch->product->name }}@if ($batch->product->brand) <span class="muted">· {{ $batch->product->brand }}</span>@endif
                                <div class="muted">{{ __($batch->product->category->name) }}@if ($batch->note) · {{ $batch->note }}@endif</div>
                            </td>
                            <td class="num">{{ $batch->quantityLabel() }}</td>
                            <td class="date">@include('inventory::printout.partials.date', ['eatSoonDays' => $eatSoonDays])</td>
                            <td class="tally"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</section>
