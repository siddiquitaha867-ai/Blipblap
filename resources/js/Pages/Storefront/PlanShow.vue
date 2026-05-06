<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  plan: {
    type: Object,
    required: true,
  },
  relatedPlans: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <section class="airalo-flow-page">
    <div class="detail-hero plan-hero">
      <div>
        <p class="eyebrow">Local eSIM</p>
        <h1>{{ plan.country_name || plan.title }} eSIM</h1>
        <p>
          Pick a prepaid data package, checkout securely, then scan your QR code
          to connect as soon as you land.
        </p>
      </div>

      <aside class="network-panel">
        <span>Network</span>
        <strong>{{ plan.country_name || 'Best available network' }}</strong>
        <a href="#compatibility">Check compatibility</a>
        <dl>
          <div>
            <dt>Plan type</dt>
            <dd>Data only</dd>
          </div>
          <div>
            <dt>Top-up</dt>
            <dd>{{ plan.topup_supported ? 'Available' : 'Available where supported' }}</dd>
          </div>
          <div>
            <dt>Activation</dt>
            <dd>Starts when your eSIM connects to a supported network.</dd>
          </div>
        </dl>
      </aside>
    </div>

    <div class="package-picker">
      <h2>Choose your package</h2>
      <div class="package-grid">
        <a
          v-for="item in relatedPlans"
          :key="item.id"
          :href="`/checkout/${item.slug}`"
          class="package-card"
          :class="{ selected: item.id === plan.id }"
        >
          <span>{{ item.duration_days }} days</span>
          <strong>{{ item.unlimited ? 'Unlimited' : `${item.data_amount} ${item.data_unit}` }}</strong>
          <em>{{ item.currency }} {{ Number(item.retail_price).toFixed(2) }}</em>
        </a>
      </div>
    </div>

    <section id="compatibility" class="support-band">
      <h2>Before you buy</h2>
      <div>
        <article>
          <strong>Check device compatibility</strong>
          <p>Most recent iPhone and Android devices support eSIM. Keep your physical SIM active alongside BlipBlap.</p>
        </article>
        <article>
          <strong>Install in minutes</strong>
          <p>After payment, your account shows QR, SM-DP+ address, and matching ID/manual code.</p>
        </article>
        <article>
          <strong>Need wider coverage?</strong>
          <p>Regional and worldwide eSIM packages follow the same package selection workflow.</p>
        </article>
      </div>
    </section>
  </section>
</template>
