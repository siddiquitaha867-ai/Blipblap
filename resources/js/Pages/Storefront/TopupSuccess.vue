<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  esim: {
    type: Object,
    required: true,
  },
  plan: {
    type: Object,
    default: null,
  },
  order: {
    type: Object,
    default: null,
  },
  topupError: {
    type: String,
    default: null,
  },
});
</script>

<template>
  <section class="success-page">
    <p class="eyebrow">Top-up status</p>
    <h1>{{ topupError ? 'Top-up needs attention' : 'Your top-up is active' }}</h1>
    <p>
      <template v-if="topupError">{{ topupError }}</template>
      <template v-else>
        {{ plan?.title || order?.bundle_code || 'Your selected package' }} was added to your existing BlipBlap eSIM.
        Keep using the same installed line.
      </template>
    </p>

    <div class="success-actions">
      <a href="/my-esims">Open My eSIMs</a>
      <a href="/esim-plans" class="success-secondary">Browse plans</a>
    </div>

    <section class="install-preview">
      <div>
        <span>ICCID</span>
        <strong>{{ esim.iccid }}</strong>
      </div>
      <div>
        <span>Status</span>
        <strong>{{ esim.status }}</strong>
      </div>
      <div v-if="order">
        <span>Order</span>
        <strong>{{ order.order_reference }}</strong>
      </div>
    </section>
  </section>
</template>
