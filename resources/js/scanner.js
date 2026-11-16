import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

/**
 * Camera barcode scanner for the Scan page (EAN-13, EAN-8, UPC-A, UPC-E).
 * Browsers only allow the camera on HTTPS pages (or localhost).
 */
const hints = new Map([
    [DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.EAN_13, BarcodeFormat.EAN_8, BarcodeFormat.UPC_A, BarcodeFormat.UPC_E]],
    [DecodeHintType.TRY_HARDER, true],
]);

window.holdoutScanner = {
    /** Why the camera cannot be used here, or null when it can. */
    unavailableReason() {
        if (!window.isSecureContext) {
            return 'insecure';
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            return 'unsupported';
        }

        return null;
    },

    /**
     * Start scanning into the video element; calls onCode once per new code.
     * Returns the controls (call stop() to release the camera).
     */
    async start(video, onCode) {
        const reader = new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 150 });
        let last = null;

        return reader.decodeFromConstraints(
            { video: { facingMode: { ideal: 'environment' } }, audio: false },
            video,
            (result) => {
                if (!result) {
                    return;
                }

                const code = result.getText();

                if (code !== last) {
                    last = code;
                    // Browsers allow vibration only after the user has tapped the page.
                    if (navigator.userActivation?.hasBeenActive) {
                        navigator.vibrate?.(80);
                    }
                    onCode(code);
                }
            },
        );
    },
};
