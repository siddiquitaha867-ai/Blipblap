<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
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
  bulkScopes: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const bulkForm = useForm({
  scope_type: 'all',
  scope_value: '',
  duration_days: '',
  unlimited: '',
  margin_percent: 20,
  fixed_markup: 0,
  tax_percent: 0,
  featured_only: false,
  active_only: true,
});

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
  if (localFlagMap[group.iso]) {
    return `/images/blipblap/${localFlagMap[group.iso]}`;
  }

  if (group.iso && group.iso.length === 2) {
    return `https://flagcdn.com/w80/${group.iso.toLowerCase()}.png`;
  }

  return '';
};

const submit = () => {
  router.get('/admin/plans', { search: search.value }, { preserveState: true, replace: true });
};

const applyBulkPricing = () => {
  bulkForm.post('/admin/plans/bulk-update', {
    preserveScroll: true,
  });
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Catalogue & pricing</p>
        <h1>Plans</h1>
      </div>
    </div>

    <form class="admin-search" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Search plan, supplier code, country" />
      <button type="submit">Search</button>
    </form>

    <section class="admin-panel admin-form-panel">
      <h2>Bulk pricing and margin control</h2>
      <form class="admin-create-grid" @submit.prevent="applyBulkPricing">
        <label>
          <span>Apply to</span>
          <select v-model="bulkForm.scope_type">
            <option v-for="(label, value) in bulkScopes" :key="value" :value="value">{{ label }}</option>
          </select>
        </label>
        <label>
          <span>Country / region / title filter</span>
          <input v-model="bulkForm.scope_value" type="text" placeholder="Africa, United Arab Emirates, Unlimited..." />
        </label>
        <label>
          <span>Duration days</span>
          <input v-model="bulkForm.duration_days" type="number" min="1" placeholder="Optional" />
        </label>
        <label>
          <span>Unlimited only</span>
          <select v-model="bulkForm.unlimited">
            <option value="">All plans</option>
            <option :value="true">Unlimited only</option>
            <option :value="false">Limited only</option>
          </select>
        </label>
        <label>
          <span>Margin %</span>
          <input v-model="bulkForm.margin_percent" type="number" min="0" step="0.01" />
        </label>
        <label>
          <span>Fixed markup</span>
          <input v-model="bulkForm.fixed_markup" type="number" min="0" step="0.01" />
        </label>
        <label>
          <span>Tax %</span>
          <input v-model="bulkForm.tax_percent" type="number" min="0" step="0.01" />
        </label>
        <label class="admin-check-row">
          <input v-model="bulkForm.active_only" type="checkbox" />
          Active only
        </label>
        <label class="admin-check-row">
          <input v-model="bulkForm.featured_only" type="checkbox" />
          Featured only
        </label>
        <button type="submit" :disabled="bulkForm.processing">Apply pricing</button>
      </form>
    </section>

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
            <span v-else>{{ group.country.slice(0, 2).toUpperCase() }}</span>
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
