# Shopping list

The **Shopping list** shows what you still need to buy so the supply lasts the household's [target days](Household.md). It is worked out from the [stock against target](Household.md#stock-against-target), so there is nothing to tick off: add the bought packages as [batches](Batches.md) (or [scan](Scanning.md) them) and the list shrinks on its own.

## What it shows

One line per category that has not reached its target, in the usual category order:

- the **amount to buy**, in kg or l, **rounded up** to 0.1 so you never buy too little (1 l counts as 1 kg);
- how much you **have of the target**, e.g. *4 kg of 9.9 kg*, and how many **days short** the category is;
- when expired batches of that category are still on the shelf: **plus … expired, not counted**. Expired stock never counts towards the target, so it is already part of the amount to buy. Eat it up or throw it away ([take it out](History.md)).

Categories without a daily need (*Other*) never appear. When every category reaches its target, the page says **Nothing to buy**. Without a household size it asks you to enter one on the [Household](Household.md) page first.

The menu entry shows a badge with the number of categories that are still missing something, and the supply block on the [Dashboard](Dashboard.md) links here while anything is missing.

## Share and copy

Below the list, the same list as plain text, e.g.

```
Shopping list for 10 days
- Drinks: 35.5 l
- Grains, bread, potatoes: 5.9 kg
```

- **Share** opens the phone's share sheet (messenger, notes app, …). It only appears where the browser supports sharing.
- **Copy** puts the text on the clipboard. Without HTTPS some browsers block the clipboard; the text in the box can always be selected and copied by hand.

The list is in the language of the person who opens it.

## For developers

`App\Support\ShoppingList` builds the list from `SupplyStatus` only (targets from the `CategoryRegistry`, stock from the `StockRegistry`), so it needs no module of its own and works for any module that reports stock. The page is the core Filament page `App\Filament\Pages\ShoppingListPage` (`/shopping-list`).
