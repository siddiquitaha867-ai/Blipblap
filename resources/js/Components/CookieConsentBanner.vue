<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const storageKey = 'blipblap_cookie_consent';
const visible = ref(false);

const setConsent = (value) => {
  if (typeof window === 'undefined') {
    return;
  }

  window.localStorage.setItem(storageKey, JSON.stringify({
    value,
    saved_at: new Date().toISOString(),
  }));
  visible.value = false;
  window.dispatchEvent(new CustomEvent('blipblap:cookie-consent', { detail: { value } }));
};

onMounted(() => {
  visible.value = !window.localStorage.getItem(storageKey);
});
</script>

<template>
  <section v-if="visible" class="cookie-consent" aria-label="Cookie consent">
    <div>
      <strong>Cookie choices</strong>
      <p>
        We use essential cookies to run checkout. With your consent, we may also use analytics or marketing cookies to improve BlipBlap.
        Read our <Link href="/privacy-policy">Privacy Policy</Link>.
      </p>
    </div>
    <div class="cookie-consent-actions">
      <button type="button" class="cookie-consent-secondary" @click="setConsent('essential')">
        Essential only
      </button>
      <button type="button" class="cookie-consent-primary" @click="setConsent('all')">
        Accept all
      </button>
    </div>
  </section>
</template>
