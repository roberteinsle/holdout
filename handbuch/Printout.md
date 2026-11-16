# Printout

When the power or the network is down, the app is gone too. The **printout** is the paper emergency copy of your supply: keep it with the supply or in your emergency folder, and it still works when nothing else does.

## Printing

Open **Printout** in the menu and tap **Open printout**. A plain page opens in a new tab, without the app around it; **Print** opens the print dialog (on a phone it can also save the copy as a PDF), **Back** returns to the app.

The copy is A4 portrait, black and white, with page numbers. Status is written out (EXPIRED, EAT SOON), never shown by colour only, so a cheap black-and-white printer is enough. It is in the language of the person who opens it, and the header shows the household name and the date and time it was made (**As of …**).

Print a new copy after shopping, or at least every few months: the paper shows the stock at the time of printing.

## What is on it

1. **Supply**: how many days the supply lasts, for how many adults and children, and per category the **need per day** of the whole household (in bold), the stock, how many days it lasts and the target. In an emergency, ration by the need per day. Only batches that have not expired count, as on the [Household](Household.md#stock-against-target) page.
2. **Eat first**: batches that have expired or expire within the ["eat soon" window](Household.md), earliest date first, with packages and [location](Locations.md).
3. **Stock by location**: every [batch](Batches.md), grouped by storage location (A–Z, **No location** last), sorted by product name and best-before date, with brand, category and note. The empty **Taken** column is for tally marks: during an outage, make a mark for every package you take out, and [take them out](History.md) in the app afterwards.

## For developers

The printable page is the route `printout` (`/printout`, `App\Http\Controllers\PrintoutController`), outside the Filament panel, behind `auth` and `SetLocale`. It uses inline styles only (`resources/views/printout/page.blade.php`), so it prints even without the built assets. The sections come from the `PrintoutRegistry` (`app/Registries/`): each implements `PrintSection` (`sort()`, `render(Household)` returning a view or `null` to leave it out). Core registers `App\Printout\SupplySection` (sort 10); the Inventory module registers `EatFirstSection` (20) and `StockSection` (30). A new module adds its own section by registering one there. The menu entry is the core page `App\Filament\Pages\Printout` (`/print`).
