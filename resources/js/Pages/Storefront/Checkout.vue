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
    <div class="checkout-copy">
      <p class="eyebrow">Secure checkout</p>
      <h1>Complete your eSIM order</h1>
      <p>
        Confirm your plan, add your delivery email, then continue to checkout.
        Your install details will be prepared after payment.
      </p>

      <form class="checkout-form">
        <label>
          <span>Full name</span>
          <input type="text" placeholder="Your name" autocomplete="name">
        </label>
        <label>
          <span>Email for eSIM delivery</span>
          <input type="email" placeholder="you@example.com" autocomplete="email">
        </label>
        <label class="checkout-wide">
          <span>Payment method</span>
          <select>
            <option>Card payment</option>
            <option>Wallet payment</option>
          </select>
        </label>
      </form>
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
      <a :href="`/checkout/${plan.slug}/success`">Proceed to checkout</a>
      <p class="checkout-note">Payment gateway will connect here before live launch.</p>
    </aside>
  </section>
</template>
