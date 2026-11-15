# Products

The **Products** page lists the products your household keeps in its supply. A product describes *what* it is (for example "Spaghetti, 500 g"); how much of it you have and when it expires is recorded later as batches.

## Adding a product

Choose **Add product** and fill in:

| Field | Required | Description |
|---|---|---|
| Name | yes | e.g. "Spaghetti" (up to 150 characters) |
| Brand | no | e.g. the manufacturer or store brand |
| Category | yes | One of the [categories](Categories.md); decides which target the product counts towards |
| Package size | yes | Amount in one package, e.g. `500` |
| Unit | yes | `g`, `kg`, `ml` or `l` |
| Barcode | no | The number under the barcode, 8 to 14 digits. Must be unique within the household. Scanning with the camera follows. |

**Create & create another** keeps the form open for the next product.

## Litres and kilograms

The targets are in kg (food) or l (drinks). To count a product towards its category, Holdout converts the package size to kg or l and counts **1 l as 1 kg**. This lets products sold by volume, such as milk, count towards a category measured in kg. For most foods the difference is small.

## The list

- On a phone, each row shows the name with brand and package size underneath. Tap a row to edit the product.
- On wider screens, the category and package size also have their own columns.
- Search finds name, brand and barcode; the filter narrows the list to one category.
- To delete a product, open it and choose **Delete**.
