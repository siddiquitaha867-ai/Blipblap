<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { computed } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  order: {
    type: Object,
    default: null,
  },
  esim: {
    type: Object,
    default: null,
  },
  provisioningError: {
    type: String,
    default: null,
  },
});

const installStatus = computed(() => props.esim?.status || props.order?.fulfillment_status || props.order?.status || 'Waiting for payment');
const hasInstallDetails = computed(() => Boolean(props.esim?.qr_code_url || props.esim?.activation_code || props.esim?.smdp_address));
</script>

<template>
  <section class="success-page">
    <p class="eyebrow">{{ hasInstallDetails ? 'Ready to install' : 'Provisioning status' }}</p>
    <h1>{{ hasInstallDetails ? 'Scan your eSIM QR code' : 'Your eSIM is being prepared' }}</h1>
    <p v-if="hasInstallDetails">
      Open your phone camera or cellular settings, scan this QR code, then follow the on-screen eSIM installation prompts.
    </p>
    <p v-else>
      Payment is confirmed when Stripe returns successfully. We are now waiting for eSIM Go to return install details.
    </p>

    <div v-if="provisioningError" class="install-alert">
      {{ provisioningError }}
    </div>

    <div class="install-preview" :class="{ 'install-preview--ready': hasInstallDetails }">
      <div class="install-qr">
        <img v-if="esim?.qr_code_url" :src="esim.qr_code_url" alt="eSIM installation QR code">
        <span v-else>QR</span>
      </div>
      <dl>
        <div>
          <dt>Plan</dt>
          <dd>{{ plan.title }}</dd>
        </div>
        <div>
          <dt>ICCID</dt>
          <dd>{{ esim?.iccid || order?.iccid || 'Available after provisioning' }}</dd>
        </div>
        <div v-if="esim?.smdp_address">
          <dt>SM-DP+ address</dt>
          <dd>{{ esim.smdp_address }}</dd>
        </div>
        <div v-if="esim?.matching_id">
          <dt>Matching ID</dt>
          <dd>{{ esim.matching_id }}</dd>
        </div>
        <div v-if="esim?.activation_code">
          <dt>Activation code</dt>
          <dd class="install-code">{{ esim.activation_code }}</dd>
        </div>
        <div>
          <dt>Status</dt>
          <dd>{{ installStatus }}</dd>
        </div>
        <div v-if="order">
          <dt>Order reference</dt>
          <dd>{{ order.order_reference }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>
