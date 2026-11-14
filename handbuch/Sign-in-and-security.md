# Sign-in and security

Holdout has no passwords. People sign in with one of three methods:

| Method | When to use |
|---|---|
| **Passkey** | Everyday sign-in with fingerprint, face or device PIN. Recommended. |
| **Email link** | First sign-in, new device, or when no passkey is available |
| **Authenticator app** | Backup when neither a passkey nor email works |

Every user manages their methods on the page **Sign-in & security**.

## Email link

1. Enter your email address on the sign-in page and choose **Email me a sign-in link**.
2. Open the link from the email and tap **Sign in now**.

- A link is valid for 15 minutes and works exactly once.
- The answer is the same whether or not an account exists for the address, so the form cannot be used to find out who has an account.
- At most 5 requests per minute per address and per IP address.
- Opening the link only shows a button; the sign-in happens when you tap it. Email security scanners that open links automatically therefore cannot use it up.

After the first sign-in, Holdout suggests setting up a passkey.

## Passkeys

On **Sign-in & security**, enter a name for the device (for example "My phone") and choose **Add passkey**. The browser or password manager then stores the passkey.

To sign in, choose **Sign in with passkey** on the sign-in page. Browsers that support it also offer saved passkeys directly in the email field.

Passkeys are bound to the domain in `APP_URL`. If the domain changes, existing passkeys stop working and have to be added again.

## Authenticator app

A backup for when you cannot use a passkey and email does not arrive. It works with any TOTP app, for example Aegis, 2FAS, Google Authenticator or a password manager.

### Set up

1. On **Sign-in & security**, choose **Set up authenticator app**.
2. Scan the QR code with the app, or type in the key shown below it.
3. Enter the 6-digit code the app shows and choose **Confirm**.
4. Holdout shows 8 **recovery codes**. Print them or write them down and put them in your emergency folder. They are shown only once.

### Sign in

On the sign-in page, choose **Sign in with authenticator app** and enter your email address and the current 6-digit code, or one of your recovery codes.

- Each code works only once; an already used code is rejected.
- Each recovery code works once. **New recovery codes** replaces all old ones.
- Recovery codes use only unambiguous characters (no 0/O or 1/I/L).
- At most 5 attempts per minute and 20 per hour per address.
- A wrong code, an unknown address and an account without an authenticator app all get the same error message.
- The secret is stored encrypted with `APP_KEY`.

## Changing sign-in methods

Adding or removing a passkey, setting up or removing the authenticator app and creating new recovery codes require a recent sign-in (within the last 3 hours). A sign-in with a passkey, an email link or the authenticator app counts.

If the last sign-in is older:

- Users with a passkey are asked to confirm with it; the action then continues automatically.
- Users without a passkey are asked to sign out and sign in again with an email link.
