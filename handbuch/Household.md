# Household

The **Household** page holds the settings everything else is calculated from. Everyone in the household can change them.

| Setting | Default | Meaning |
|---|---|---|
| Household name | (from setup) | Shown in the app and on the printout |
| Adults | (from setup) | Number of adults living in the household |
| Children | (from setup) | Number of children up to 12 years living in the household |
| Target days | 14 | How many days the supply should last. The German Federal Office of Civil Protection and Disaster Assistance (BBK) recommends a supply for 10 days. |
| "Eat soon" window | 60 days | Items that expire within this many days appear on the "Eat soon" list |

## Who counts

Count everyone who lives in the household and would eat from the supply, including small children. This is not the same as the [Family](Family.md) list, which only contains the people who use the app.

The amounts needed per category (for example water, grains, vegetables) are calculated from the number of adults and children and the target days. The values per person and day, and how children are counted, follow the official German guidance; see [Categories](Categories.md). Children means up to 12 years, as in that guidance.

## What you need

Below the settings, the Household page shows **What you need for N days**: for each category the amount per day and for the target days. It is calculated as

> (adults × need per adult + children × need per child) × target days

with the values per person and day from [Categories](Categories.md). Example for 2 adults, 1 child and 14 days: drinks (2 × 2.0 l + 1 × 1.5 l) × 14 = 77 l; grains 3 × 0.33 kg × 14 ≈ 13.9 kg. The section updates when you save.

## Stock against target

Next to each target, Holdout shows what you have: the packages of all [batches](Batches.md) of the category's products times their package size, in kg or l (1 l counts as 1 kg, see [Products](Products.md)). Products in **Other** have no target and are not shown.

At the top, a summary says how long the whole supply lasts, e.g. **Your supply lasts 4 of 14 days**, and which category **runs out first**. The supply lasts only as long as that category: 30 days of pasta do not help without water.

For each category you see:

- **stock / target**, e.g. `23.5 kg / 13.9 kg`, with a bar for the share covered;
- the status with an icon: **Enough** (olive, target reached), **Too little** (signal orange, some stock) or **Missing** (red, nothing);
- how many **days** the stock lasts for your household, and how much is **missing** to reach the target;
- the need **per day**.

Only batches that have not expired count; a batch is expired from the day after its best-before date. Expired stock is shown below the category as "Plus … expired, not counted", so you know it is still on the shelf. Batches without a date always count.

The numbers change as soon as batches are added or [taken out](History.md); reload the page to see them.

## During setup

On a fresh installation, the setup page asks for the household name, your own name and the number of adults and children. The target days start at 14 and the "eat soon" window at 60 days; change them later on this page.

Installations from before household sizes existed start with no adults and children. Set them on the Household page once.
