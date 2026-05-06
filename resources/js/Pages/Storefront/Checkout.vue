<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  plan: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <section class="checkout-page">
    <div>
      <p class="eyebrow">Secure checkout</p>
      <h1>Review your eSIM</h1>
      <p>Payment gateway integration will attach here. For now this page proves the Airalo-style selection path end-to-end.</p>
    </div>

    <aside class="checkout-summary">
      <span>{{ plan.country_name || plan.coverage_type }}</span>
      <h2>{{ plan.title }}</h2>
      <dl>
        <div>
          <dt>Data</dt>
          <dd>{{ plan.unlimited ? 'Unlimited' : `${plan.data_amount} ${plan.data_unit}` }}</dd>
        </div>
        <div>
          <dt>Validity</dt>
          <dd>{{ plan.duration_days }} days</dd>
        </div>
        <div>
          <dt>Total</dt>
          <dd>{{ plan.currency }} {{ Number(plan.retail_price).toFixed(2) }}</dd>
        </div>
      </dl>
      <a :href="`/checkout/${plan.slug}/success`">Continue to install preview</a>
    </aside>
  </section>
</template>
