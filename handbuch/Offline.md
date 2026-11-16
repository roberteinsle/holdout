# Offline copy

When the server cannot be reached (a power cut, the Wi-Fi is down, the server is off), the app cannot work. So every phone keeps an **offline copy** of the supply: the same content as the [printout](Printout.md), saved on the device.

## How it works

- Open Holdout once on the phone over the **HTTPS address** (see [Installation](Installation.md#3-https-with-nginx-proxy-manager-recommended)) and choose who you are. From then on, the phone keeps the copy.
- The copy **updates itself** every time Holdout is opened while the server is reachable (at most every five minutes).
- When the server cannot be reached, **every page** of Holdout opens the offline copy instead, with a banner: *Offline copy · The server cannot be reached …*. The header shows when the copy was made (**As of …**).
- Add Holdout to the **home screen** (browser menu → *Add to Home screen* / *Install app*), so it opens like an app, also offline.
- **Print** works offline too; on a phone it can save the copy as a PDF.

Each phone keeps its own copy, so open Holdout once on every phone of the family. The copy is read-only: write down what you take out (the printout has a **Taken** column for tally marks) and record it in the app when the server is back.

## Checking the copy

The **Emergency copy** page (menu) shows under **Offline copy on this device** whether this phone has a copy and when it was saved. **Update now** saves a fresh one right away.

If it says **Not available here**, the page was opened over plain HTTP (e.g. `http://<server-ip>:8090`): browsers allow offline copies (service workers) only on HTTPS pages. Use the HTTPS address, or rely on the paper printout.

## What it does not do

The offline copy is not an offline app: nothing can be added, taken out or changed without the server. Only the copy (and the logo) is stored on the device; everything else always comes from the server.

## For developers

`public/sw.js` is the service worker, registered from `resources/views/partials/head-icons.blade.php` for signed-in people on secure origins (HTTPS or localhost). It caches only `/printout`, and only real printouts (response header `X-Holdout-Printout: 1`, not a redirect to "Who are you?"), with the time saved in `X-Holdout-Saved-At`. Page loads send it `refresh` (throttled to five minutes); `refresh-now` and `status` answer over a `MessageChannel` with `savedAt`. Navigations always go to the network first; only when that fails does it serve the copy, switching on the banner by replacing `data-offline-banner hidden` in the HTML. Other requests are not touched. Bump `CACHE` (`holdout-offline-v1`) to drop old copies.
