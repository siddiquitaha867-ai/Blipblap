<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  esim: {
    type: Object,
    required: true,
  },
  sourcePlan: {
    type: Object,
    default: null,
  },
  packages: {
    type: Array,
    default: () => [],
  },
  csrfToken: {
    type: String,
    required: true,
  },
  compatibilityChecked: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const selectedPlanId = ref(props.packages[0]?.id || '');
const termsAccepted = ref(false);
const selectedPlan = computed(() => props.packages.find((plan) => plan.id === selectedPlanId.value) || null);
const stripeAction = computed(() => `/my-esims/${props.esim.id}/top-up/stripe`);

const dataLabel = (plan) => {
  if (!plan) {
    return 'Data package';
  }

  return plan.unlimited ? 'Unlimited data' : `${Number(plan.data_amount || 0)} ${plan.data_unit || 'GB'}`;
};
</script>

<template>
  <section class="topup-page">
    <div class="topup-copy">
      <p class="eyebrow">Top up eSIM</p>
      <h1>Add data to your existing eSIM</h1>
      <p>
        Choose a compatible package and we will apply it to the same ICCID after secure payment.
        You will keep using your installed BlipBlap eSIM.
      </p>

      <div class="topup-current">
        <span>Current eSIM</span>
        <strong>{{ sourcePlan?.title || esim.nickname || 'BlipBlap eSIM' }}</strong>
        <small>ICCID {{ esim.iccid }}</small>
      </div>
    </div>

    <form class="topup-panel" method="post" :action="stripeAction">
      <p v-if="page.props.flash.status" class="payment-error-note">
        {{ page.props.flash.status }}
      </p>

      <input type="hidden" name="_token" :value="csrfToken">
      <input type="hidden" name="plan_id" :value="selectedPlanId">
      <input type="hidden" name="terms_accepted" :value="termsAccepted ? '1' : '0'">

      <div class="payment-card-top">
        <span>Compatible top-ups</span>
        <small>
          {{ packages.length }} packages
          <template v-if="compatibilityChecked"> · API verified</template>
        </small>
      </div>

      <div v-if="packages.length" class="topup-options" role="radiogroup" aria-label="Top-up packages">
        <button
          v-for="plan in packages"
          :key="plan.id"
          type="button"
          class="topup-option"
          :class="{ active: selectedPlanId === plan.id }"
          role="radio"
          :aria-checked="selectedPlanId === plan.id"
          @click="selectedPlanId = plan.id"
        >
          <span>{{ dataLabel(plan) }}</span>
          <strong>{{ plan.duration_days }} days</strong>
          <em>{{ plan.currency }} {{ Number(plan.total_price).toFixed(2) }}</em>
        </button>
      </div>

      <div v-else class="topup-empty">
        <strong>No compatible top-ups found</strong>
        <p>
          {{
            compatibilityChecked
              ? 'The provider API did not return a compatible top-up for this ICCID.'
              : 'We could not find an active top-up package for this eSIM destination.'
          }}
        </p>
      </div>

      <dl v-if="selectedPlan" class="topup-summary">
        <div>
          <dt>Selected package</dt>
          <dd>{{ selectedPlan.title }}</dd>
        </div>
        <div>
          <dt>Data</dt>
          <dd>{{ dataLabel(selectedPlan) }}</dd>
        </div>
        <div>
          <dt>Validity</dt>
          <dd>{{ selectedPlan.duration_days }} days</dd>
        </div>
        <div>
          <dt>Total</dt>
          <dd>{{ selectedPlan.currency }} {{ Number(selectedPlan.total_price).toFixed(2) }}</dd>
        </div>
      </dl>

      <label class="checkout-legal-check">
        <input v-model="termsAccepted" type="checkbox" required>
        <span>
          I agree to the
          <Link href="/terms-and-conditions">Terms and Conditions</Link>
          and
          <Link href="/privacy-policy">Privacy Policy</Link>.
        </span>
      </label>

      <button type="submit" class="payment-submit" :disabled="!selectedPlan || !termsAccepted">
        Continue to secure payment
      </button>
      <p class="payment-secure-note">After payment, this top-up is applied to your existing ICCID.</p>
    </form>
  </section>
</template>
