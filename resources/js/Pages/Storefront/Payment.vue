<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  customerName: {
    type: String,
    default: '',
  },
  customerEmail: {
    type: String,
    default: '',
  },
  stripePublishableKey: {
    type: String,
    default: '',
  },
});

const form = useForm({
  customer_name: props.customerName,
  customer_email: props.customerEmail,
});

const pay = () => {
  form.post(`/checkout/${props.plan.slug}/stripe`);
};
</script>

<template>
  <section class="payment-page">
    <div class="payment-copy">
      <p class="eyebrow">Payment</p>
      <h1>Pay securely for your eSIM</h1>
      <p>
        Use this screen to test the payment step. Live Stripe checkout will use
        your Stripe test keys, then redirect back with provisioning status.
      </p>

      <div class="stripe-test-box">
        <span>Stripe test mode</span>
        <strong>{{ stripePublishableKey ? 'Publishable key detected' : 'Add Stripe keys to enable live test checkout' }}</strong>
        <p>Test card: 4242 4242 4242 4242, any future expiry, any CVC.</p>
      </div>
    </div>

    <aside class="payment-card">
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
          <dt>Amount due</dt>
          <dd>{{ plan.currency }} {{ Number(plan.retail_price).toFixed(2) }}</dd>
        </div>
      </dl>

      <div class="payment-method-preview">
        <label>
          <span>Email</span>
          <input v-model="form.customer_email" type="email" placeholder="you@example.com" required>
        </label>
        <label>
          <span>Card number</span>
          <input value="4242 4242 4242 4242" readonly>
        </label>
        <div>
          <label>
            <span>Expiry</span>
            <input value="12 / 34" readonly>
          </label>
          <label>
            <span>CVC</span>
            <input value="123" readonly>
          </label>
        </div>
      </div>

      <button type="button" class="payment-submit" :disabled="form.processing" @click="pay">
        {{ form.processing ? 'Opening Stripe...' : 'Pay test order' }}
      </button>
      <small v-if="form.errors.customer_email">{{ form.errors.customer_email }}</small>
      <p class="payment-secure-note">You will be redirected to Stripe Checkout to complete this test payment securely.</p>
    </aside>
  </section>
</template>
