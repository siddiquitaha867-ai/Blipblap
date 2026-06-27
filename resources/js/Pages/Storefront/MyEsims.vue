<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { formatDate } from '@/utils/dateTime';
import { ref, watch } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  esims: {
    type: Array,
    default: () => [],
  },
});

const copiedEsimId = ref(null);
const selectedEsim = ref(null);
const localEsims = ref([...props.esims]);
const refreshingUsageIds = ref(new Set());

watch(() => props.esims, (esims) => {
  localEsims.value = [...esims];
}, { deep: true });

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

const openDetails = (esim) => {
  selectedEsim.value = esim;
};

const closeDetails = () => {
  selectedEsim.value = null;
};

const isRefreshingUsage = (esim) => refreshingUsageIds.value.has(esim.id);

const setUsageRefreshing = (esim, refreshing) => {
  const next = new Set(refreshingUsageIds.value);

  if (refreshing) {
    next.add(esim.id);
  } else {
    next.delete(esim.id);
  }

  refreshingUsageIds.value = next;
};

const refreshUsage = async (esim) => {
  if (!esim.id || isRefreshingUsage(esim)) {
    return;
  }

  setUsageRefreshing(esim, true);

  try {
    const response = await fetch(`/my-esims/${esim.id}/usage`, {
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      return;
    }

    const usage = await response.json();
    const updated = { ...esim, ...usage };

    localEsims.value = localEsims.value.map((item) => (item.id === esim.id ? updated : item));

    if (selectedEsim.value?.id === esim.id) {
      selectedEsim.value = updated;
    }
  } finally {
    setUsageRefreshing(esim, false);
  }
};

const hasUsageData = (esim) => (
  esim.usage_percent !== null
  && esim.usage_percent !== undefined
);

const usageWidth = (esim) => `${hasUsageData(esim) ? esim.usage_percent : 0}%`;
</script>

<template>
  <section class="my-esims-page">
    <div class="my-esims-hero">
      <span>My eSIMs</span>
      <h1>Your purchased eSIMs</h1>
      <p>Open your active and recent BlipBlap eSIMs, then copy the install details whenever you need them.</p>
    </div>

    <div v-if="!localEsims.length" class="my-esims-empty">
      <h2>No eSIMs yet</h2>
      <p>Purchased eSIMs will appear here after checkout.</p>
      <a href="/esim-plans">Browse eSIM plans</a>
    </div>

    <div v-else class="my-esims-grid">
      <article v-for="esim in localEsims" :key="esim.id" class="my-esim-card">
        <div class="my-esim-card__top">
          <span>{{ esim.location }}</span>
          <strong>{{ esim.status }}</strong>
        </div>

        <h2>{{ esim.plan_title }}</h2>
        <p>
          {{ esim.data || 'Data package' }}
          <template v-if="esim.duration_days"> - {{ esim.duration_days }} days</template>
        </p>

        <div class="esim-usage-meter">
          <div class="esim-usage-meter__head">
            <span>Data usage</span>
            <button type="button" @click="refreshUsage(esim)" :disabled="isRefreshingUsage(esim)">
              {{ isRefreshingUsage(esim) ? 'Updating' : (hasUsageData(esim) ? `${esim.usage_percent}% used` : 'Refresh') }}
            </button>
          </div>
          <div class="esim-usage-meter__track" :class="{ 'is-empty': !hasUsageData(esim) }">
            <span :style="{ width: usageWidth(esim) }"></span>
          </div>
          <div class="esim-usage-meter__meta">
            <span>Used {{ esim.used_data || '0 MB' }}</span>
            <span>Remaining {{ esim.remaining_data || 'Checking' }}</span>
          </div>
          <small v-if="esim.total_data || esim.days_remaining !== null && esim.days_remaining !== undefined">
            <template v-if="esim.total_data">Total {{ esim.total_data }}</template>
            <template v-if="esim.days_remaining !== null && esim.days_remaining !== undefined"> · {{ esim.days_remaining }} days left</template>
          </small>
          <small v-if="esim.last_synced_at">
            Updated {{ formatDate(esim.last_synced_at) }}
          </small>
        </div>

        <div class="my-esim-install">
          <div class="my-esim-qr-panel">
            <img v-if="esim.qr_code_url" :src="esim.qr_code_url" alt="eSIM QR code">
            <span v-else>QR</span>
          </div>
          <div class="my-esim-install-actions">
            <button type="button" class="my-esim-action-button my-esim-action-button--secondary" @click="openDetails(esim)">View details</button>
            <a v-if="esim.can_topup" :href="esim.topup_url" class="my-esim-action-button my-esim-action-button--primary">Top up</a>
            <a v-if="esim.ios_install_url" :href="esim.ios_install_url" class="my-esim-action-button" target="_blank" rel="noopener">
              Install on Apple
            </a>
            <a v-if="esim.android_install_url" :href="esim.android_install_url" class="my-esim-action-button" target="_blank" rel="noopener">
              Install on Android
            </a>
            <button v-if="esim.activation_code" type="button" class="my-esim-action-button" @click="copyActivationCode(esim)">
              {{ copiedEsimId === esim.id ? 'Copied' : 'Copy activation code' }}
            </button>
          </div>
        </div>
      </article>
    </div>

    <Teleport to="body">
      <div
        v-if="selectedEsim"
        class="my-esim-detail-modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="my-esim-detail-title"
        @click.self="closeDetails"
      >
        <section class="my-esim-detail-modal">
          <button type="button" class="my-esim-detail-close" aria-label="Close eSIM details" @click="closeDetails">x</button>
          <span>{{ selectedEsim.location }}</span>
          <h2 id="my-esim-detail-title">eSIM details</h2>
          <p>{{ selectedEsim.plan_title }}</p>

          <dl>
            <div>
              <dt>ICCID</dt>
              <dd>{{ selectedEsim.iccid || 'Pending' }}</dd>
            </div>
            <div v-if="selectedEsim.smdp_address">
              <dt>SM-DP+</dt>
              <dd>{{ selectedEsim.smdp_address }}</dd>
            </div>
            <div v-if="selectedEsim.matching_id">
              <dt>Matching ID</dt>
              <dd>{{ selectedEsim.matching_id }}</dd>
            </div>
            <div v-if="selectedEsim.activation_code">
              <dt>Activation code</dt>
              <dd class="my-esim-code">{{ selectedEsim.activation_code }}</dd>
            </div>
            <div v-if="selectedEsim.ios_install_url">
              <dt>Apple install</dt>
              <dd><a :href="selectedEsim.ios_install_url" target="_blank" rel="noopener">Open iPhone / iPad install link</a></dd>
            </div>
            <div v-if="selectedEsim.android_install_url">
              <dt>Android install</dt>
              <dd><a :href="selectedEsim.android_install_url" target="_blank" rel="noopener">Open Android install link</a></dd>
            </div>
            <div v-if="selectedEsim.order_reference">
              <dt>Order</dt>
              <dd>{{ selectedEsim.order_reference }}</dd>
            </div>
            <div v-if="selectedEsim.created_at">
              <dt>Purchased</dt>
              <dd>{{ formatDate(selectedEsim.created_at) }}</dd>
            </div>
            <div v-if="selectedEsim.expires_at">
              <dt>Expires</dt>
              <dd>{{ formatDate(selectedEsim.expires_at) }}</dd>
            </div>
            <div v-if="selectedEsim.usage_status">
              <dt>Usage status</dt>
              <dd>{{ selectedEsim.usage_status }}</dd>
            </div>
            <div v-if="selectedEsim.last_synced_at">
              <dt>Usage updated</dt>
              <dd>{{ formatDate(selectedEsim.last_synced_at) }}</dd>
            </div>
            <div v-if="hasUsageData(selectedEsim)">
              <dt>Data used</dt>
              <dd>{{ selectedEsim.used_data || '0 MB' }} of {{ selectedEsim.total_data || 'total data' }}</dd>
            </div>
            <div v-if="selectedEsim.remaining_data">
              <dt>Data left</dt>
              <dd>{{ selectedEsim.remaining_data }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </Teleport>
  </section>
</template>
