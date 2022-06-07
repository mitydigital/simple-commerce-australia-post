import SimpleCommerceAustraliaPostField from './fieldtypes/australia-post.vue';

Statamic.booting(() => {
    Statamic.$components.register('australia_post_shipping_methods-fieldtype', SimpleCommerceAustraliaPostField);
});
