# Products

The **Products** page lists the products your household keeps in its supply. A product describes *what* it is (for example "Spaghetti, 500 g"); how much of it you have and when it expires is recorded as [batches](Batches.md).

## Adding a product

Choose **Add product** and fill in:

| Field | Required | Description |
|---|---|---|
| Barcode | no | The number under the barcode, 8 to 14 digits. Stored with 13 digits (UPC and EAN-8 codes get leading zeros). Must be unique within the household. |
| Photo | no | Take a photo with the phone or choose one (JPEG, PNG or WebP, up to 5 MB; scaled down to 800 px on the device). Found products bring their photo along. |
| Name | yes | e.g. "Spaghetti" (up to 150 characters) |
| Brand | no | e.g. the manufacturer or store brand |
| Category | yes | One of the [categories](Categories.md); decides which target the product counts towards |
| Package size | yes | Amount in one package, e.g. `500` |
| Unit | yes | `g`, `kg`, `ml` or `l` |

After **Create**, the product's page opens so you can add its first batch. **Create & create another** keeps the form open for the next product instead.

## Looking up a barcode

Enter the barcode first. When you leave the field, Holdout looks the product up in [Open Food Facts](https://world.openfoodfacts.org), the free product database, and fills in name, brand, package size and, where it can tell, the category. It only fills fields that are still empty; the magnifier button next to the field looks the code up again and replaces what is there. Always check the result.

- The name comes in your language if Open Food Facts has it.
- If nothing is found, or Open Food Facts cannot be reached (no internet), enter the details by hand. The form always works.
- Every answer is stored locally: a known product is looked up again only after 30 days, an unknown barcode after 7 days. Without internet, any stored answer is used, however old.
- Only the barcode is sent to Open Food Facts, with a generic identification of the app and no personal data. At most 30 lookups per minute leave your installation.
- Product data from Open Food Facts is available under the [Open Database License](https://opendatacommons.org/licenses/odbl/1-0/).

## Photos

A photo makes products easier to recognise at the shelf. The list shows it as a small picture next to the name.

- When a barcode is found in Open Food Facts, Holdout downloads the product's front photo once on the server and gives the product its own copy. Photos from Open Food Facts are licensed CC BY-SA; the product page shows "Photo: Open Food Facts (CC BY-SA)" below them.
- Your own photo replaces it; removing or replacing a photo deletes the old file, deleting a product deletes its photo.
- Photos are stored in the data folder (`product-images/`, downloaded originals in `off-images/`), so they work without internet and are part of your backup. They are only shown through signed, temporary links, never publicly.
- Only photos from `openfoodfacts.org` addresses are downloaded, only real JPEG, PNG or WebP images up to 2 MB. The phones never contact Open Food Facts themselves.

Scanning the barcode with the phone camera follows.

## Litres and kilograms

The targets are in kg (food) or l (drinks). To count a product towards its category, Holdout converts the package size to kg or l and counts **1 l as 1 kg**. This lets products sold by volume, such as milk, count towards a category measured in kg. For most foods the difference is small.

## The list

- On a phone, each row shows the name with brand and package size underneath. Tap a row to edit the product.
- **In stock** shows the number of packages over all of the product's batches.
- On wider screens, the category and package size also have their own columns.
- Search finds name, brand and barcode; the filter narrows the list to one category.
- To delete a product, open it and choose **Delete**. Its batches are deleted too.
