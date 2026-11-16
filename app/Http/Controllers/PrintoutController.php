<?php

namespace App\Http\Controllers;

use App\Registries\PrintoutRegistry;
use App\Registries\PrintSection;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The paper emergency copy: a plain A4 page without the app around it, built
 * from the sections in the PrintoutRegistry (core and modules), in the
 * person's language. Inline styles only, so it prints even without the built assets
 * and works as the offline copy that public/sw.js keeps on the device.
 */
class PrintoutController extends Controller
{
    public function __invoke(Request $request, PrintoutRegistry $printout): Response
    {
        $household = $request->user()->household;

        $sections = collect($printout->sections())
            ->map(fn (PrintSection $section): ?Renderable => $section->render($household))
            ->filter()
            ->map(fn (Renderable $view): string => $view->render());

        return response()->view('printout.page', [
            'household' => $household,
            'sections' => $sections,
            'printedAt' => now()->locale(app()->getLocale())->isoFormat('L LT'),
            'backUrl' => url('/'),
        ])
            // The service worker keeps only real printouts as the offline copy (#14).
            ->header('X-Holdout-Printout', '1')
            ->header('Cache-Control', 'no-store, private');
    }
}
