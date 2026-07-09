import { ref, onBeforeUnmount } from 'vue';

/**
 * Transient inline notification (success/error) with auto-dismiss,
 * rendered with the shared <Alert> component.
 */
export function useNotice(timeout = 5000) {
    const notice = ref(null);
    let timer = null;

    function show(variant, text) {
        notice.value = { variant, text };
        clearTimeout(timer);
        timer = setTimeout(dismiss, timeout);
    }

    function dismiss() {
        notice.value = null;
        clearTimeout(timer);
    }

    onBeforeUnmount(() => clearTimeout(timer));

    return { notice, show, dismiss };
}
