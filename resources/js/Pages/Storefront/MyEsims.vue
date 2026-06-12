<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { formatDate } from '@/utils/dateTime';
import { ref } from 'vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  esims: {
    type: Array,
    default: () => [],
  },
});

const copiedEsimId = ref(null);
const userAgent = typeof navigator !== 'undefined' ? navigator.userAgent : '';
const isAppleDevice = /iPhone|iPad|iPod/i.test(userAgent);
const isAndroidDevice = /Android/i.test(userAgent);

const copyActivationCode = async (esim) => {
  if (!esim.activation_code || typeof navigator === 'undefined' || !navigator.clipboard) {
    return;
  }

  await navigator.clipboard.writeText(esim.activation_code);
  copiedEsimId.value = esim.id;
  window.setTimeout(() => {
    copiedEsimId.value = null;
  }, 1800);
};
</script>

<template>
  <section class="my-esims-page">
    <div class="my-esims-hero">
      <span>My eSIMs</span>
      <h1>Your purchased eSIMs</h1>
      <p>Open your active and recent BlipBlap eSIMs, then copy the install details whenever you need them.</p>
    </div>

    <div v-if="!esims.length" class="my-esims-empty">
      <h2>No eSIMs yet</h2>
      <p>Purchased eSIMs will appear here after checkout.</p>
      <a href="/esim-plans">Browse eSIM plans</a>
    </div>

    <div v-else class="my-esims-grid">
      <article v-for="esim in esims" :key="esim.id" class="my-esim-card">
        <div class="my-esim-card__top">
          <span>{{ esim.location }}</span>
          <strong>{{ esim.status }}</strong>
        </div>

        <h2>{{ esim.plan_title }}</h2>
        <p>
          {{ esim.data || 'Data package' }}
          <template v-if="esim.duration_days"> - {{ esim.duration_days }} days</template>
        </p>

        <p v-if="esim.remaining_data || esim.days_remaining" class="checkout-note">
          <template v-if="esim.remaining_data">Remaining data: {{ esim.remaining_data }}</template>
          <template v-if="esim.total_data"> / {{ esim.total_data }}</template>
          <template v-if="esim.days_remaining !== null && esim.days_remaining !== undefined"> - {{ esim.days_remaining }} days left</template>
        </p>

        <div class="my-esim-actions">
          <a v-if="esim.can_topup" :href="esim.topup_url" class="my-esim-topup-link">Top up</a>
        </div>

        <div class="my-esim-install">
          <div class="my-esim-qr-panel">
            <img v-if="esim.qr_code_url" :src="esim.qr_code_url" alt="eSIM QR code">
            <span v-else>QR</span>
            <div v-if="esim.ios_install_url || esim.android_install_url" class="my-esim-install-links">
              <a v-if="isAppleDevice && esim.ios_install_url" :href="esim.ios_install_url" target="_blank" rel="noopener">Install on iPhone / iPad</a>
              <a v-if="isAndroidDevice && esim.android_install_url" :href="esim.android_install_url" target="_blank" rel="noopener">Install on Android</a>
              <div v-if="esim.activation_code" class="my-esim-copy-row">
                <button type="button" @click="copyActivationCode(esim)">
                  {{ copiedEsimId === esim.id ? 'Copied' : 'Copy activation code' }}
                </button>
                <button type="button" class="my-esim-info-button" aria-label="Install help">
                  i
                  <span role="tooltip">
                    Open this page on your eSIM phone to use the direct install button. On desktop, scan the QR or copy the activation code and enter it manually.
                  </span>
                </button>
              </div>
            </div>
            <small v-else class="my-esim-install-note">Use QR or manual details</small>
          </div>
          <dl>
            <div>
              <dt>ICCID</dt>
              <dd>{{ esim.iccid || 'Pending' }}</dd>
            </div>
            <div v-if="esim.smdp_address">
              <dt>SM-DP+</dt>
              <dd>{{ esim.smdp_address }}</dd>
            </div>
            <div v-if="esim.matching_id">
              <dt>Matching ID</dt>
              <dd>{{ esim.matching_id }}</dd>
            </div>
            <div v-if="esim.activation_code">
              <dt>Activation code</dt>
              <dd class="my-esim-code">{{ esim.activation_code }}</dd>
            </div>
            <div v-if="esim.ios_install_url">
              <dt>Apple install</dt>
              <dd><a :href="esim.ios_install_url" target="_blank" rel="noopener">Open iPhone / iPad install link</a></dd>
            </div>
            <div v-if="esim.android_install_url">
              <dt>Android install</dt>
              <dd><a :href="esim.android_install_url" target="_blank" rel="noopener">Open Android install link</a></dd>
            </div>
            <div v-if="esim.order_reference">
              <dt>Order</dt>
              <dd>{{ esim.order_reference }}</dd>
            </div>
            <div v-if="esim.created_at">
              <dt>Purchased</dt>
              <dd>{{ formatDate(esim.created_at) }}</dd>
            </div>
            <div v-if="esim.expires_at">
              <dt>Expires</dt>
              <dd>{{ formatDate(esim.expires_at) }}</dd>
            </div>
            <div v-if="esim.usage_status">
              <dt>Usage status</dt>
              <dd>{{ esim.usage_status }}</dd>
            </div>
          </dl>
        </div>
      </article>
    </div>
  </section>
</template>
