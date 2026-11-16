# Holdout handbook

Holdout is a mobile-first web app that helps families build and keep an emergency food and water supply, and eat it before it expires. It runs on your home network: one installation serves one household, without accounts or sign-in.

> This handbook is the source of the project wiki. Edit the files in `handbuch/` and rebuild the wiki with `scripts/build-wiki.py`; never edit the wiki by hand.

## Pages

- [Dashboard](Dashboard.md): the start page with days of supply, "eat soon" and recent activity
- [Installation](Installation.md): run Holdout at home with Docker and Portainer, HTTPS, emergency power, backups
- [Household](Household.md): household size, target days, "eat soon" window, stock against target
- [Categories](Categories.md): food and water categories and the daily need per person (BLE values)
- [Scanning](Scanning.md): scan a barcode with the phone camera to add a batch or a new product
- [Products](Products.md): the products in your supply, with category and package size
- [Batches](Batches.md): how many packages you have and when they expire
- [Locations](Locations.md): where the supply is kept
- [History](History.md): taking packages out, and who changed what
- [Family](Family.md): household setup, "Who are you?", managing people
- [Design](Design.md): colours and contrast rules
- [Development](Development.md): local setup, tests, conventions, documentation workflow

## Status

Version 0.1 is in progress. Available so far: household setup, household size and target days with the resulting target amounts per category, products with barcode lookup and camera scanning, batches with best-before dates, storage locations, taking packages out with a history of who did what, the stock compared with the targets, the dashboard, and the family list. The shopping list, the printout and offline use on phones follow.
