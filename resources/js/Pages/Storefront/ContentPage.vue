<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  page: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <section class="how-page">
    <section class="plans-index-hero">
      <div>
        <span>Custom page</span>
        <h1>{{ page.title }}</h1>
        <p>{{ page.excerpt || page.meta_description || 'BlipBlap custom content page.' }}</p>
      </div>
    </section>

    <section class="plans-index-section">
      <div class="admin-panel" style="max-width: 960px; margin: 0 auto;">
        <div v-html="page.body_html"></div>
      </div>
    </section>
  </section>
</template>
