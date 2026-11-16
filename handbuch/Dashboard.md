# Dashboard

The **Dashboard** is the start page. It answers the everyday questions at a glance: how long does the supply last, what should be eaten soon, and what happened recently. Everything is laid out for a phone, one block below the other.

## Quick actions

Two big buttons at the top:

- **Scan** opens the [barcode scanner](Scanning.md): a known product goes straight to adding a batch, an unknown one to adding the product.
- **Add batch** opens the form for a new [batch](Batches.md).

## Supply

How many days the whole supply lasts for your household, e.g. **Your supply lasts 4 of 10 days**, and which category **runs out first**. Below, one line per category with its status icon (enough, too little, missing), the share of the target covered and the days it lasts. **Details** opens the [Household](Household.md#stock-against-target) page with the amounts; while anything is missing, **Shopping list** opens the [shopping list](Shopping-list.md). Expired batches are not counted.

## Eat soon

The batches that have **expired** or expire within the household's ["eat soon" window](Household.md), earliest date first, with the date badge in the [status colour](Batches.md#expiry-status). Batches without a date never appear here.

- The **minus** icon [takes packages out](History.md) right there, e.g. when you cook with them.
- Tap a row to open the batch; **All batches** opens the full list.
- Five rows are shown at a time; the selector below shows more.

When nothing expires soon, the block says so.

## Recently

The last five entries of the [history](History.md): what was added, eaten, thrown away or corrected, by whom and when. **Full history** opens the History page.

## For developers

The blocks are Filament widgets registered in the `DashboardRegistry` (`app/Registries/`): the supply block by core, the others by the Inventory module. A new module adds its own block by registering a widget there; the order comes from each widget's `$sort`.
