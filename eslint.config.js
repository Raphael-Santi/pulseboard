import prettierConfig from '@vue/eslint-config-prettier';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import pluginVue from 'eslint-plugin-vue';

export default defineConfigWithVueTs(
    {
        name: 'app/files-to-lint',
        files: ['**/*.{ts,mts,vue}'],
    },
    {
        name: 'app/files-to-ignore',
        ignores: ['vendor/**', 'node_modules/**', 'public/**', 'storage/**', 'bootstrap/**'],
    },
    pluginVue.configs['flat/recommended'],
    vueTsConfigs.recommended,
    prettierConfig,
);
