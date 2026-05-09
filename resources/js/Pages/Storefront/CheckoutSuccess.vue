<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  plan: {
    type: Object,
    required: true,
  },
  order: {
    type: Object,
    default: null,
  },
});
</script>

<template>
  <section class="success-page">
    <p class="eyebrow">Ready to install</p>
    <h1>Your eSIM install details will appear here</h1>
    <p>
      Once payment and eSIM Go provisioning are connected, this page will show the QR code,
      SM-DP+ address, matching ID, status, and resend/top-up actions.
    </p>
    <div class="install-preview">
      <div class="fake-qr">▦</div>
      <dl>
        <div>
          <dt>Plan</dt>
          <dd>{{ plan.title }}</dd>
        </div>
        <div>
          <dt>ICCID</dt>
          <dd>Available after provisioning</dd>
        </div>
        <div>
          <dt>Status</dt>
          <dd>{{ order?.status || 'Waiting for Stripe webhook' }}</dd>
        </div>
        <div v-if="order">
          <dt>Order reference</dt>
          <dd>{{ order.order_reference }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>
