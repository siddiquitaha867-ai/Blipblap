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
