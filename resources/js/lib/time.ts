export function plural(n: number, one: string, few: string, many: string): string {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) {
        return one;
    }
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) {
        return few;
    }
    return many;
}

/** Human, Russian "N minutes ago" style relative time. */
export function relativeTime(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    const diffMs = Math.max(0, Date.now() - new Date(iso).getTime());
    const minutes = Math.floor(diffMs / 60_000);

    if (minutes < 1) {
        return 'только что';
    }
    if (minutes < 60) {
        return `${minutes} ${plural(minutes, 'минуту', 'минуты', 'минут')} назад`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours} ${plural(hours, 'час', 'часа', 'часов')} назад`;
    }

    const days = Math.floor(hours / 24);
    return `${days} ${plural(days, 'день', 'дня', 'дней')} назад`;
}

export function formatDateTime(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    return new Date(iso).toLocaleString('ru-RU', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
