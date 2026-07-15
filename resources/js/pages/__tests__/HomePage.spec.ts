import { RouterLinkStub, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import HomePage from '@/pages/HomePage.vue';

describe('HomePage', () => {
    it('renders the product name and auth links', () => {
        const wrapper = mount(HomePage, {
            global: { stubs: { RouterLink: RouterLinkStub } },
        });

        expect(wrapper.text()).toContain('Pulseboard');

        const links = wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'));
        expect(links).toContain('/login');
        expect(links).toContain('/register');
    });
});
