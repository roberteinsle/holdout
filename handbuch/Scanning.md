# Scanning

The **Scan** page is the quickest way to put shopping away and to take something out: point the phone camera at a barcode, and Holdout shows what you can do.

| The barcode belongs to … | Holdout shows … |
|---|---|
| a product in stock | the product with its batches: [take packages out](#taking-packages-out) or add a batch |
| a product you know, but with nothing in stock | **Add batch**, with the product selected: enter how many packages and the best-before date |
| no product yet | **Add product**, with the barcode filled in and looked up in Open Food Facts. **Put into stock right away** is switched on: enter the packages, best-before date and location, and everything is saved together. |

Supported barcodes: EAN-13, EAN-8, UPC-A and UPC-E, the codes on almost all groceries.

## Taking packages out

For a product in stock, its [batches](Batches.md) are listed with the earliest best-before date first, each with packages, date, location and note:

- **Take out** next to a batch opens the usual dialog: packages, reason (consumed or discarded) and note, see [History](History.md#taking-packages-out). Pick the batch with the date on the package in your hand.
- **Add batch** opens the form for new packages of this product.
- **Scan next** starts the camera again for the next product.

The camera is off while a product is shown. If someone else has emptied the batch in the meantime, Holdout says so and changes nothing.

## Using the camera

1. Open **Scan** (top of the menu).
2. Allow camera access when the browser asks.
3. Hold the barcode inside the white frame. The phone vibrates briefly when it recognises the code (on devices that support it).

Browsers only allow the camera on pages opened over **HTTPS** (or on the device itself via `localhost`). When Holdout is opened over plain HTTP, the page says so and offers the input field instead. See [Installation](Installation.md): Holdout should always be served over HTTPS.

If camera access was refused, allow it in the browser's settings for this page. On devices without a camera the page also shows the input field.

## Typing the barcode

Below the camera, **Type the barcode** always works, also without HTTPS or a camera: enter the digits under the barcode and choose **Search**. Spaces and dashes are ignored; short codes (UPC, EAN-8) match products saved with 13 digits.
