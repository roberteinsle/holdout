import { Passkeys, ServerError } from '@laravel/passkeys';

const confirmRoutes = { options: '/passkeys/confirm/options', submit: '/passkeys/confirm' };

/**
 * Small wrapper used by the login page and the Security page.
 * Errors keep their `name` (UserCancelledError, PasskeyExistsError, ...) so
 * the page can show a translated message.
 */
window.holdoutPasskeys = {
    isSupported: () => Passkeys.isSupported(),

    async login() {
        // Stop the pending autofill request first, or both ceremonies race for the same session options.
        Passkeys.cancel();
        const response = await Passkeys.verify({ remember: true });
        window.location.href = response.redirect ?? '/';
    },

    async autofill() {
        if (!(await Passkeys.isAutofillSupported())) {
            return;
        }

        const response = await Passkeys.autofill({ remember: true });

        if (response) {
            window.location.href = response.redirect ?? '/';
        }
    },

    /**
     * Adding a passkey needs a recent sign-in (HTTP 423 otherwise).
     * Users who already have a passkey can confirm with it and continue.
     */
    async register(name) {
        try {
            return await Passkeys.register({ name });
        } catch (error) {
            if (!(error instanceof ServerError) || error.status !== 423) {
                throw error;
            }

            try {
                await Passkeys.verify({ routes: confirmRoutes });
            } catch {
                throw Object.assign(new Error('Recent sign-in required'), { name: 'ReauthRequired' });
            }

            return await Passkeys.register({ name });
        }
    },
};

// The guest pages mark their passkey button with data-passkey-login.
document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-passkey-login]');

    if (!button) {
        return;
    }

    const error = document.querySelector('[data-passkey-error]');

    if (!Passkeys.isSupported()) {
        button.hidden = true;
        return;
    }

    button.addEventListener('click', async () => {
        error.hidden = true;
        button.disabled = true;

        try {
            await window.holdoutPasskeys.login();
        } catch (e) {
            if (e.name !== 'UserCancelledError') {
                error.hidden = false;
            }
        } finally {
            button.disabled = false;
        }
    });

    window.holdoutPasskeys.autofill().catch(() => {});
});
