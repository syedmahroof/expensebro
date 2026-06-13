import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import en from '@/locales/en';
import ur from '@/locales/ur';
import ar from '@/locales/ar';

type DeepPartial<T> = {
    [K in keyof T]?: T[K] extends object ? DeepPartial<T[K]> : T[K];
};
type Messages = typeof en;

const locales: Record<string, DeepPartial<Messages>> = { en, ur, ar };

function deepGet(
    obj: Record<string, unknown>,
    path: string,
): string | undefined {
    const keys = path.split('.');
    let current: unknown = obj;
    for (const key of keys) {
        if (current == null || typeof current !== 'object') {
            return undefined;
        }
        current = (current as Record<string, unknown>)[key];
    }
    return typeof current === 'string' ? current : undefined;
}

export function useI18n() {
    const page = usePage<{ userLocale?: string }>();
    const locale = computed(() => page.props.userLocale ?? 'en');

    function t(key: string, fallback?: string): string {
        const msgs = locales[locale.value] ?? locales.en;
        const val =
            deepGet(msgs as Record<string, unknown>, key) ??
            deepGet(locales.en as Record<string, unknown>, key);
        return val ?? fallback ?? key;
    }

    const isRtl = computed(() =>
        ['ar', 'ur', 'he', 'fa'].includes(locale.value),
    );

    return { t, locale, isRtl };
}
