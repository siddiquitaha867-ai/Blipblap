<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  esims: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <section class="my-esims-page">
    <div class="my-esims-hero">
      <span>My eSIMs</span>
      <h1>Your purchased eSIMs</h1>
      <p>Open your active and recent BlipBlap eSIMs, then copy the install details whenever you need them.</p>
    </div>

    <div v-if="!esims.length" class="my-esims-empty">
      <h2>No eSIMs yet</h2>
      <p>Purchased eSIMs will appear here after checkout.</p>
      <a href="/esim-plans">Browse eSIM plans</a>
    </div>

    <div v-else class="my-esims-grid">
      <article v-for="esim in esims" :key="esim.id" class="my-esim-card">
        <div class="my-esim-card__top">
          <span>{{ esim.location }}</span>
          <strong>{{ esim.status }}</strong>
        </div>

        <h2>{{ esim.plan_title }}</h2>
        <p>
          {{ esim.data || 'Data package' }}
          <template v-if="esim.duration_days"> · {{ esim.duration_days }} days</template>
        </p>

        <div class="my-esim-install">
          <img v-if="esim.qr_code_url" :src="esim.qr_code_url" alt="eSIM QR code">
          <span v-else>QR</span>
          <dl>
            <div>
              <dt>ICCID</dt>
              <dd>{{ esim.iccid || 'Pending' }}</dd>
            </div>
            <div v-if="esim.smdp_address">
              <dt>SM-DP+</dt>
              <dd>{{ esim.smdp_address }}</dd>
            </div>
            <div v-if="esim.matching_id">
              <dt>Matching ID</dt>
              <dd>{{ esim.matching_id }}</dd>
            </div>
            <div v-if="esim.activation_code">
              <dt>Activation code</dt>
              <dd class="my-esim-code">{{ esim.activation_code }}</dd>
            </div>
            <div v-if="esim.order_reference">
              <dt>Order</dt>
              <dd>{{ esim.order_reference }}</dd>
            </div>
          </dl>
        </div>
      </article>
    </div>
  </section>
</template>
