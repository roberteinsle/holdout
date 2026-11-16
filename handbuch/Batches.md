# Batches

A **batch** records how much of a [product](Products.md) you have: a number of packages that share one best-before date. If you buy six packs of oats today and still have two older ones, that is two batches of the same product.

## Adding a batch

There are two ways:

- On the **Batches** page, choose **Add batch** and pick the product.
- On a product's page (tap it in the product list), choose **Add batch** in the **Batches** section. After you create a product, Holdout opens its page so you can add the first batch straight away.

| Field | Required | Description |
|---|---|---|
| Product | yes | One of your household's products (only on the Batches page) |
| Packages | yes | How many packages with the same best-before date, 1 to 10,000 |
| Best before | no | The date printed on the package. Leave it empty for things without a date, such as salt or sugar. Dates already in the past are allowed. |
| Location | no | Where the packages are kept, one of the household's [locations](Locations.md); **+** adds a new one |
| Note | no | Anything worth knowing, e.g. "on offer" (up to 150 characters) |

## Expiry status

Each batch gets a status from its best-before date and the household's ["eat soon" window](Household.md) (60 days unless you change it):

| Status | Meaning | Colour |
|---|---|---|
| Expired | the date has passed | red |
| Eat soon | the date is today or within the "eat soon" window | signal orange |
| Fresh | the date is further away | olive |
| No date | no best-before date recorded | grey |

The date badge in the lists has the status colour; tap or hover it to see the status in words.

## The Batches page

- All batches of the household, **earliest best-before date first**; batches without a date come last. What you should eat next is at the top.
- On a phone, each row shows the product with the amount, location and note underneath (e.g. `4 × 500 g · Cellar shelf`), and the date on the right. Tap a row to edit the batch; delete it there.
- Search finds the product name, brand and barcode. The filters narrow the list to one status (for example **Eat soon** or **Expired**) one [location](Locations.md) or one product.

## On the product page

The **Batches** section below the product form lists the product's batches, with their location under the amount. Tap a row or the pencil to edit a batch in a dialog; the bin deletes it. Deleting a product also deletes its batches.

The product list shows **In stock**: the number of packages over all batches of each product.

Taking things out of the supply (with a history of who did what) and comparing the stock with the [targets](Household.md) follow.
