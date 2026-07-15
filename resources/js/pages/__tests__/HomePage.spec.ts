import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import HomePage from '@/pages/HomePage.vue';

describe('HomePage', () => {
    it('renders the product name', () => {
        const wrapper = mount(HomePage);

        expect(wrapper.text()).toContain('Pulseboard');
    });
});
