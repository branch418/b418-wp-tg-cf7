import { inject } from 'vue';

// Provided by the Pro bundle's entry point when it re-mounts this same
// Admin.vue tree. Free builds never provide this, so usePro() resolves to
// null everywhere and every seam below becomes a no-op.
export const PRO_KEY = Symbol('b418TgCf7Pro');

// Registry shape provided by Pro: {
//   version: string,
//   tabs(baseTabs, store) -> tabs array,
//   panels: { [tabKey]: Component },
//   components: { RuleModalExtras, RuleBadges },
// }
export function usePro() {
    return inject(PRO_KEY, null);
}
