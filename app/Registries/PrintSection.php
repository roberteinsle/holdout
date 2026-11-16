<?php

namespace App\Registries;

use App\Models\Household;
use Illuminate\Contracts\Support\Renderable;

/**
 * One section of the paper emergency copy (printout). Rendered as plain HTML
 * that prints well in black and white; null leaves the section out.
 */
interface PrintSection
{
    /** Position on the printout, lower first. */
    public function sort(): int;

    public function render(Household $household): ?Renderable;
}
