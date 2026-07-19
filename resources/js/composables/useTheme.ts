import { ref } from 'vue';

type Theme = 'dark' | 'light';

const STORAGE_KEY = 'pb-theme';

function readInitial(): Theme {
    try {
        return localStorage.getItem(STORAGE_KEY) === 'light' ? 'light' : 'dark';
    } catch {
        return 'dark';
    }
}

// Shared, module-level state so every component sees the same theme.
const theme = ref<Theme>(readInitial());

function apply(value: Theme): void {
    document.documentElement.dataset.theme = value;
}

export function useTheme() {
    function toggle(): void {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
        try {
            localStorage.setItem(STORAGE_KEY, theme.value);
        } catch {
            // Storage may be unavailable (private mode); the toggle still works.
        }
        apply(theme.value);
    }

    return { theme, toggle };
}
