# Holdout handbook

Holdout is a mobile-first web app that helps families build and keep an emergency food and water supply, and eat it before it expires. It is self-hosted: one installation serves one household, with an account per person and no passwords. In an emergency it works offline through the paper printout and the offline copy on every phone.

> This handbook is the source of the project wiki. Edit the files in `handbuch/` and rebuild the wiki with `scripts/build-wiki.py`; never edit the wiki by hand.

## Pages

- [Dashboard](Dashboard.md): the start page with days of supply, "eat soon" and recent activity
- [Installation](Installation.md): run Holdout with Docker and Coolify, PostgreSQL, environment variables, updates, backups
- [Sign-in and security](Sign-in-and-security.md): passkeys, email links, authenticator app, recovery codes
- [Household](Household.md): household size, target days, "eat soon" window, stock against target
- [Categories](Categories.md): food and water categories and the daily need per person (BLE values)
- [Scanning](Scanning.md): scan a barcode with the phone camera to add a batch or a new product
- [Products](Products.md): the products in your supply, with category and package size
- [Shopping list](Shopping-list.md): what is still missing to reach the target, to share or copy
- [Batches](Batches.md): how many packages you have and when they expire
- [Printout](Printout.md): the paper emergency copy for a power or network outage
- [Offline copy](Offline.md): the copy of the supply every phone keeps for when the server is unreachable
- [Locations](Locations.md): where the supply is kept
- [History](History.md): taking packages out, and who changed what
- [Equipment](Equipment.md): home pharmacy, power outage, hygiene, documents, cash, grab bag, fire safety: checklists with expiry dates and a check log
- [Family](Family.md): household setup, owner and member roles, invitations, profile and language
- [Design](Design.md): colours and contrast rules
- [Development](Development.md): local setup, tests, conventions, documentation workflow

## Status

Version 0.1 is in progress. Available so far: household setup, household size and target days with the resulting target amounts per category, products with barcode lookup and camera scanning, batches with best-before dates, storage locations, taking packages out with a history of who did what, the stock compared with the targets, the dashboard, the shopping list, the printout (paper emergency copy), the offline copy on phones, equipment checklists with a check log, sign-in without passwords, and family invitations.
