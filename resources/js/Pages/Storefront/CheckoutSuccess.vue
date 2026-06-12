<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import * as QRCode from 'qrcode';
import { computed, ref, watchEffect } from 'vue';

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
const generatedQrCodeUrl = ref('');
const qrCodeUrl = computed(() => props.esim?.qr_code_url || generatedQrCodeUrl.value);
const hasInstallDetails = computed(() => Boolean(qrCodeUrl.value || props.esim?.activation_code || props.esim?.smdp_address));
const activationCode = computed(() => {
  if (props.esim?.activation_code) {
    return props.esim.activation_code;
  }

  if (props.esim?.smdp_address && props.esim?.matching_id) {
    return `LPA:1$${props.esim.smdp_address}$${props.esim.matching_id}`;
  }

  return '';
});
const appleInstallUrl = computed(() => (
  props.esim?.install_details?.assignment?.iosInstallUrl
  || props.esim?.install_details?.response?.iosInstallUrl
  || (activationCode.value ? `https://esimsetup.apple.com/esim_qrcode_provisioning?carddata=${encodeURIComponent(activationCode.value)}` : '')
));
const androidInstallUrl = computed(() => (
  props.esim?.install_details?.assignment?.androidInstallUrl
  || props.esim?.install_details?.response?.androidInstallUrl
  || (activationCode.value ? `https://esimsetup.android.com/esim_qrcode_provisioning?carddata=${encodeURIComponent(activationCode.value)}` : '')
));
const hasDirectInstallLink = computed(() => Boolean(appleInstallUrl.value || androidInstallUrl.value));

watchEffect(async () => {
  if (props.esim?.qr_code_url || !props.esim?.activation_code) {
    generatedQrCodeUrl.value = '';

    return;
  }

  try {
    const svg = await QRCode.toString(props.esim.activation_code, {
      type: 'svg',
      errorCorrectionLevel: 'M',
      margin: 2,
      color: {
        dark: '#111827',
        light: '#ffffff',
      },
    });

    generatedQrCodeUrl.value = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
  } catch {
    generatedQrCodeUrl.value = '';
  }
});
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
      <div class="install-qr-panel">
        <div class="install-qr">
          <img v-if="qrCodeUrl" :src="qrCodeUrl" alt="eSIM installation QR code">
          <span v-else>QR</span>
        </div>
        <div v-if="hasInstallDetails" class="install-link-panel">
          <strong>Install without scanning</strong>
          <p v-if="hasDirectInstallLink">Tap the right install link on the phone that will use this eSIM.</p>
          <p v-else>Direct install links are not available for this eSIM. Use the QR code or manual details.</p>
          <div v-if="hasDirectInstallLink" class="install-link-actions">
            <a v-if="appleInstallUrl" :href="appleInstallUrl" target="_blank" rel="noopener">Install on iPhone / iPad</a>
            <a v-if="androidInstallUrl" :href="androidInstallUrl" target="_blank" rel="noopener">Install on Android</a>
          </div>
        </div>
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
        <div v-if="appleInstallUrl">
          <dt>Apple install URL</dt>
          <dd>
            <a :href="appleInstallUrl" target="_blank" rel="noopener">
              Open iPhone / iPad install link
            </a>
          </dd>
        </div>
        <div v-if="androidInstallUrl">
          <dt>Android install URL</dt>
          <dd>
            <a :href="androidInstallUrl" target="_blank" rel="noopener">
              Open Android install link
            </a>
          </dd>
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

    <div class="auth-required-actions" style="margin-top: 24px;">
      <a href="/my-esims" class="auth-required-primary">Open My eSIMs</a>
      <a href="/esim-plans" class="auth-required-secondary">Buy another eSIM</a>
    </div>
  </section>
</template>
