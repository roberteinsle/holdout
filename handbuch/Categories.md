# Categories

Holdout sorts the supply into categories. Each category has an amount one person needs per day; together with the household size and the target days (see [Household](Household.md)) this gives the target amount per category.

## Values

The values come from the stock calculator of the German Federal Office for Agriculture and Food (Bundesanstalt für Landwirtschaft und Ernährung, BLE) at [ernaehrungsvorsorge.de](https://www.ernaehrungsvorsorge.de/private-vorsorge/notvorrat/vorratskalkulator/). They are based on 2,200 kcal per person and day.

| Category | Adult per day | Child per day |
|---|---|---|
| Drinks | 2.0 l | 1.5 l |
| Grains, bread, potatoes | 0.33 kg | 0.33 kg |
| Vegetables, pulses | 0.40 kg | 0.40 kg |
| Fruit, nuts | 0.25 kg | 0.25 kg |
| Milk, dairy products | 0.25 kg | 0.25 kg |
| Eggs, meat, fish | 0.12 kg | 0.12 kg |
| Fats, oil | 0.033 kg (33 g) | 0.033 kg |

### Children

The BLE uses one value per person for food and does not distinguish children, so Holdout counts children like adults there. This errs on the safe side.

For drinks the BLE does distinguish: adults need 1.5 l for drinking plus 0.5 l for cooking; children up to 12 years (not infants) drink 1 l on average. Holdout adds the same 0.5 l for cooking to the children's value, giving 1.5 l. The 0.5 l addition is Holdout's assumption, not a BLE figure.

Infants are not covered by these values; plan their food separately.

### Differences to the BBK checklist

The checklist of the Federal Office of Civil Protection and Disaster Assistance (BBK) uses slightly different 10-day amounts for some groups (for example 3.5 kg of grains per person). Holdout follows the BLE calculator, which gives values per day.

## How categories get into the app

Categories are registered by modules (the food and water categories come from the supply module) and stored in the database on every start of the container. Updated values therefore arrive with an update; nothing has to be entered by hand.
