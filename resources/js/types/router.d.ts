import 'vue-router';

declare module 'vue-router' {
    interface RouteMeta {
        /** Route requires an authenticated user. */
        auth?: boolean;
        /** Route is only for guests (login, register). */
        guest?: boolean;
    }
}
