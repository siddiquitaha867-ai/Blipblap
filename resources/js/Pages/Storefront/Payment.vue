<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

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
  csrfToken: {
    type: String,
    required: true,
  },
});

const page = usePage();
const customerEmail = ref(props.customerEmail);
const stripeAction = `/checkout/${props.plan.slug}/stripe`;
</script>

<template>
  <section class="payment-page">
    <div class="payment-copy">
      <p class="eyebrow">Payment</p>
      <h1>Review and complete payment</h1>
      <p>
        Confirm the delivery email and continue to the secure payment step.
        Your eSIM install details will be sent after payment.
      </p>

      <div class="stripe-test-box">
        <span>Secure checkout</span>
        <strong>Card details are entered on Stripe</strong>
        <p>Your payment is processed on an encrypted checkout page before you return here for order status.</p>
      </div>
    </div>

    <form class="payment-card" method="post" :action="stripeAction">
      <p v-if="page.props.flash.status" class="payment-error-note">
        {{ page.props.flash.status }}
      </p>

      <input type="hidden" name="_token" :value="props.csrfToken">
      <input type="hidden" name="customer_name" :value="props.customerName">

      <div class="payment-card-top">
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
          <dt>Amount due</dt>
          <dd>{{ plan.currency }} {{ Number(plan.retail_price).toFixed(2) }}</dd>
        </div>
      </dl>

      <div class="payment-method-preview">
        <label>
          <span>Delivery email</span>
          <input v-model="customerEmail" name="customer_email" type="email" placeholder="you@example.com" required>
        </label>
      </div>

      <button type="submit" class="payment-submit">
        Continue to secure payment
      </button>
      <p class="payment-secure-note">You will be redirected to Stripe Checkout to complete payment securely.</p>
    </form>
  </section>
</template>
