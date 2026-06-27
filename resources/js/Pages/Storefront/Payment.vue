<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
  customerPhone: {
    type: String,
    default: '',
  },
  addressLine1: {
    type: String,
    default: '',
  },
  addressLine2: {
    type: String,
    default: '',
  },
  city: {
    type: String,
    default: '',
  },
  state: {
    type: String,
    default: '',
  },
  postalCode: {
    type: String,
    default: '',
  },
  country: {
    type: String,
    default: '',
  },
  csrfToken: {
    type: String,
    required: true,
  },
});

const page = usePage();
const stripeAction = `/checkout/${props.plan.slug}/stripe`;
const taxAmount = computed(() => Number(props.plan.tax_amount || 0));
const totalAmount = computed(() => Number(props.plan.retail_price || 0) + taxAmount.value);
const billingAddressLines = computed(() => [
  props.addressLine1,
  props.addressLine2,
  [props.city, props.state, props.postalCode].filter(Boolean).join(', '),
  props.country,
].filter(Boolean));
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
      <input type="hidden" name="customer_email" :value="props.customerEmail">
      <input type="hidden" name="customer_phone" :value="props.customerPhone">
      <input type="hidden" name="address_line1" :value="props.addressLine1">
      <input type="hidden" name="address_line2" :value="props.addressLine2">
      <input type="hidden" name="city" :value="props.city">
      <input type="hidden" name="state" :value="props.state">
      <input type="hidden" name="postal_code" :value="props.postalCode">
      <input type="hidden" name="country" :value="props.country">
      <input type="hidden" name="terms_accepted" value="1">

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
          <dt>Plan price</dt>
          <dd>{{ plan.currency }} {{ Number(plan.retail_price).toFixed(2) }}</dd>
        </div>
        <div>
          <dt>Tax</dt>
          <dd>{{ plan.currency }} {{ taxAmount.toFixed(2) }}</dd>
        </div>
        <div>
          <dt>Amount due</dt>
          <dd>{{ plan.currency }} {{ totalAmount.toFixed(2) }}</dd>
        </div>
      </dl>

      <div class="payment-method-preview">
        <div>
          <span>Customer</span>
          <strong>{{ props.customerName }}</strong>
          <small>{{ props.customerEmail }}</small>
          <small>{{ props.customerPhone }}</small>
        </div>
        <div>
          <span>Billing Address</span>
          <strong v-for="line in billingAddressLines" :key="line">{{ line }}</strong>
        </div>
      </div>

      <button type="submit" class="payment-submit">
        Continue to secure payment
      </button>
      <p class="payment-secure-note">
        You will be redirected to Stripe Checkout to complete payment securely.
        By continuing, you confirm your agreement to the Terms and Conditions and Privacy Policy.
      </p>
    </form>
  </section>
</template>
