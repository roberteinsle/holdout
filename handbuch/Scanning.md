# Scanning

The **Scan** page is the quickest way to put shopping away: point the phone camera at a barcode, and Holdout takes you to the right form.

| The barcode belongs to … | Holdout opens … |
|---|---|
| a product you already have | **Add batch**, with the product selected: enter how many packages and the best-before date |
| no product yet | **Add product**, with the barcode filled in and looked up in Open Food Facts. After saving, the product page opens, where you add the batch. |

Supported barcodes: EAN-13, EAN-8, UPC-A and UPC-E, the codes on almost all groceries.

## Using the camera

1. Open **Scan** (top of the menu).
2. Allow camera access when the browser asks.
3. Hold the barcode inside the white frame. The phone vibrates briefly when it recognises the code (on devices that support it).

Browsers only allow the camera on pages opened over **HTTPS** (or on the device itself via `localhost`). When Holdout is opened over plain HTTP, for example `http://<server-ip>:8090`, the page says so and offers the input field instead. See [Installation](Installation.md) for setting up HTTPS.

If camera access was refused, allow it in the browser's settings for this page. On devices without a camera the page also shows the input field.

## Typing the barcode

Below the camera, **Type the barcode** always works, also without HTTPS or a camera: enter the digits under the barcode and choose **Search**. Spaces and dashes are ignored; short codes (UPC, EAN-8) match products saved with 13 digits.
