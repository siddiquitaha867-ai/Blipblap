<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  plans: {
    type: Array,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const syncResult = ref(null);
const syncingCatalogue = ref(false);

const coverageName = (plan) => plan.country_name || plan.region_name || plan.coverage_type || 'Other';

const localFlagMap = {
  AE: 'ARE.svg',
  EG: 'EGY.svg',
  GB: 'GBR.svg',
  OM: 'OMN.svg',
  RU: 'RUS.svg',
  SA: 'SAU.svg',
  TR: 'TUR.svg',
  US: 'USA.svg',
  EU: 'EUR.svg',
};

const normalizeCountryName = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/[^a-z0-9]+/gi, ' ')
  .trim()
  .toLowerCase();

const displayNames = typeof Intl !== 'undefined' && Intl.DisplayNames
  ? new Intl.DisplayNames(['en'], { type: 'region' })
  : null;

const regionNameMap = (() => {
  const map = new Map();
  const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const regions = [];

  if (displayNames) {
    for (const first of letters) {
      for (const second of letters) {
        const region = `${first}${second}`;

        try {
          const name = displayNames.of(region);

          if (name && name !== region) {
            regions.push(region);
          }
        } catch {
          // Ignore invalid region codes while building the browser-local lookup.
        }
      }
    }
  }

  regions.forEach((region) => {
    const name = displayNames?.of(region);

    if (name) {
      map.set(normalizeCountryName(name), region);
    }
  });

  Object.entries({
    'aland islands': 'AX',
    bolivia: 'BO',
    'brunei': 'BN',
    'cape verde': 'CV',
    'cote d ivoire': 'CI',
    'iran': 'IR',
    'laos': 'LA',
    'moldova': 'MD',
    'russia': 'RU',
    'south korea': 'KR',
    'syria': 'SY',
    'tanzania': 'TZ',
    'turkey': 'TR',
    'united kingdom': 'GB',
    'united states': 'US',
    'venezuela': 'VE',
    'vietnam': 'VN',
  }).forEach(([name, region]) => map.set(name, region));

  return map;
})();

const resolveIso = (group) => {
  const countryKey = normalizeCountryName(group.country);
  const iso = String(group.iso || '').trim().toUpperCase();
  const displayedCountry = iso.length === 2 ? normalizeCountryName(displayNames?.of(iso)) : '';

  if (iso.length === 2 && (!displayedCountry || displayedCountry === countryKey)) {
    return iso;
  }

  return regionNameMap.get(countryKey) || (iso.length === 2 ? iso : '');
};

const flagEmoji = (iso) => {
  if (!iso || iso.length !== 2 || iso === 'EU') {
    return '';
  }

  return iso
    .toUpperCase()
    .replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
};

const groupedCountries = computed(() => {
  const countries = new Map();

  props.plans.forEach((plan) => {
    const country = coverageName(plan);

    if (!countries.has(country)) {
      countries.set(country, {
        country,
        iso: plan.country_iso,
        total: 0,
      });
    }

    const group = countries.get(country);
    group.total += 1;

    if (!group.iso && plan.country_iso) {
      group.iso = plan.country_iso;
    }
  });

  return Array.from(countries.values()).sort((a, b) => a.country.localeCompare(b.country));
});

const flagUrl = (group) => {
  const iso = resolveIso(group);

  if (localFlagMap[iso]) {
    return `/images/blipblap/${localFlagMap[iso]}`;
  }

  return '';
};

const flagFallback = (group) => {
  const iso = resolveIso(group);

  return flagEmoji(iso) || group.country.slice(0, 2).toUpperCase();
};

const submit = () => {
  router.get('/admin/plans', { search: search.value }, { preserveState: true, replace: true });
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const syncCatalogue = async () => {
  syncingCatalogue.value = true;
  syncResult.value = null;

  try {
    const response = await fetch('/admin/diagnostics/sync-catalogue', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
    });
    const data = await response.json().catch(() => ({}));

    syncResult.value = {
      ok: response.ok,
      status: response.status,
      ...data,
    };

    if (response.ok) {
      router.reload({ only: ['plans'], preserveScroll: true });
    }
  } catch (error) {
    syncResult.value = {
      ok: false,
      message: error.message || 'Sync request failed.',
    };
  } finally {
    syncingCatalogue.value = false;
  }
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Catalogue & pricing</p>
        <h1>Plans</h1>
      </div>
      <button class="admin-sync-button" type="button" :disabled="syncingCatalogue" @click="syncCatalogue">
        {{ syncingCatalogue ? 'Syncing...' : 'Sync plans' }}
      </button>
    </div>

    <form class="admin-search" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Search plan, supplier code, country" />
      <button type="submit">Search</button>
    </form>

    <div v-if="syncResult" :class="['admin-sync-result', syncResult.ok ? 'ok' : 'warn']">
      <strong>{{ syncResult.message || (syncResult.ok ? 'Catalogue sync completed.' : 'Catalogue sync failed.') }}</strong>
      <span v-if="syncResult.ok">
        Synced {{ syncResult.synced }} of {{ syncResult.source_count }} items across {{ syncResult.pages_fetched }} pages.
        Local plans: {{ syncResult.local_plan_count }}.
      </span>
      <span v-else-if="syncResult.status">Status {{ syncResult.status }}</span>
    </div>

    <section class="admin-panel">
      <div class="admin-country-tabs">
        <Link
          v-for="group in groupedCountries"
          :key="group.country"
          class="admin-country-tab"
          :href="`/admin/plans/country/${encodeURIComponent(group.country)}`"
        >
          <span class="admin-country-flag">
            <img v-if="flagUrl(group)" :src="flagUrl(group)" :alt="`${group.country} flag`">
            <span v-else>{{ flagFallback(group) }}</span>
          </span>
          <span class="admin-country-title">
            <small>Country / region</small>
            <strong>{{ group.country }}</strong>
          </span>
          <em>{{ group.total }} plans</em>
        </Link>
      </div>
    </section>
  </section>
</template>
