<?php

namespace App\Http\Controllers;

use App\Registries\PrintoutRegistry;
use App\Registries\PrintSection;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The paper emergency copy: a plain A4 page without the app around it, built
 * from the sections in the PrintoutRegistry (core and modules), in the
 * person's language. Inline styles only, so it prints even without the built assets.
 */
class PrintoutController extends Controller
{
    public function __invoke(Request $request, PrintoutRegistry $printout): View
    {
        $household = $request->user()->household;

        $sections = collect($printout->sections())
            ->map(fn (PrintSection $section): ?Renderable => $section->render($household))
            ->filter()
            ->map(fn (Renderable $view): string => $view->render());

        return view('printout.page', [
            'household' => $household,
            'sections' => $sections,
            'printedAt' => now()->locale(app()->getLocale())->isoFormat('L LT'),
            'backUrl' => url('/'),
        ]);
    }
}
