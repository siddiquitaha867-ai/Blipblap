<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
});

const page = usePage();
const customerName = ref(page.props.auth.user?.name || '');
const customerEmail = ref(page.props.auth.user?.email || '');
const customerPhone = ref('');
const addressLine1 = ref('');
const addressLine2 = ref('');
const city = ref('');
const state = ref('');
const postalCode = ref('');
const country = ref('');

const goToPayment = () => {
  router.get(`/checkout/${props.plan.slug}/payment`, {
    name: customerName.value,
    email: customerEmail.value,
    phone: customerPhone.value,
    address_line1: addressLine1.value,
    address_line2: addressLine2.value,
    city: city.value,
    state: state.value,
    postal_code: postalCode.value,
    country: country.value,
  });
};
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

      <form class="checkout-form" @submit.prevent="goToPayment">
        <label>
          <span>Full name</span>
          <input v-model="customerName" type="text" placeholder="Your name" autocomplete="name">
        </label>
        <label>
          <span>Email for eSIM delivery</span>
          <input v-model="customerEmail" type="email" placeholder="you@example.com" autocomplete="email" required>
        </label>
        <label>
          <span>Phone</span>
          <input v-model="customerPhone" type="text" placeholder="+1 555 123 4567" autocomplete="tel">
        </label>
        <label>
          <span>Address line 1</span>
          <input v-model="addressLine1" type="text" placeholder="Street address" autocomplete="address-line1">
        </label>
        <label>
          <span>Address line 2</span>
          <input v-model="addressLine2" type="text" placeholder="Apartment, suite, etc." autocomplete="address-line2">
        </label>
        <label>
          <span>City</span>
          <input v-model="city" type="text" placeholder="City" autocomplete="address-level2">
        </label>
        <label>
          <span>State / province</span>
          <input v-model="state" type="text" placeholder="State or province" autocomplete="address-level1">
        </label>
        <label>
          <span>Postal code</span>
          <input v-model="postalCode" type="text" placeholder="Postal code" autocomplete="postal-code">
        </label>
        <label>
          <span>Country</span>
          <input v-model="country" type="text" placeholder="Country" autocomplete="country-name">
        </label>
        <fieldset class="checkout-method-group checkout-wide">
          <legend>Payment method</legend>
          <div class="checkout-method-tabs">
            <button
              type="button"
              class="active"
            >
              Card
            </button>
            <button
              type="button"
              disabled
            >
              Wallet <small>Coming soon</small>
            </button>
          </div>
        </fieldset>
        <div class="checkout-form-footer">
          <button type="submit" class="checkout-inline-submit">Continue to payment</button>
          <p>Secure payment opens on the next step.</p>
        </div>
      </form>
    </div>

    <aside class="checkout-summary">
      <div class="checkout-summary-top">
        <span>Order summary</span>
        <small>{{ plan.country_name || plan.coverage_type }}</small>
      </div>
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
      <p class="checkout-note">Payment gateway will connect here before live launch.</p>
    </aside>
  </section>
</template>
