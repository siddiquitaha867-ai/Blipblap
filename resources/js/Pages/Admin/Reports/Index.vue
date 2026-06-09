<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const range = ref(props.filters.range || 'last_30_days');
const sort = ref(props.filters.sort || 'sales_desc');

const rangeOptions = computed(() => props.filters.range_options || {});
const sortOptions = computed(() => props.filters.sort_options || {});

const money = (value, currency = 'USD') => `${currency} ${Number(value || 0).toFixed(2)}`;

const submit = () => {
  router.get('/admin/reports', {
    search: search.value,
    range: range.value,
    sort: sort.value,
  }, {
    preserveState: true,
    replace: true,
  });
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Performance</p>
        <h1>Reports</h1>
      </div>
    </div>

    <form class="admin-search admin-report-filters" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Search plan, bundle, country" />
      <select v-model="range" class="admin-range-select" @change="submit">
        <option v-for="(label, value) in rangeOptions" :key="value" :value="value">
          {{ label }}
        </option>
      </select>
      <select v-model="sort" class="admin-range-select" @change="submit">
        <option v-for="(label, value) in sortOptions" :key="value" :value="value">
          {{ label }}
        </option>
      </select>
      <button type="submit">Apply</button>
    </form>

    <div class="stat-grid admin-report-stats">
      <article>
        <span>Paid orders</span>
        <strong>{{ summary.orders }}</strong>
      </article>
      <article>
        <span>Plans sold</span>
        <strong>{{ summary.plans_sold }}</strong>
      </article>
      <article>
        <span>Revenue</span>
        <strong>{{ money(summary.revenue) }}</strong>
      </article>
      <article>
        <span>Profit</span>
        <strong>{{ money(summary.profit) }}</strong>
      </article>
      <article>
        <span>Tax</span>
        <strong>{{ money(summary.tax) }}</strong>
      </article>
      <article>
        <span>Supplier cost</span>
        <strong>{{ money(summary.supplier_cost) }}</strong>
      </article>
    </div>

    <section class="admin-panel">
      <div class="admin-table admin-report-table">
        <table>
          <thead>
            <tr>
              <th>Plan</th>
              <th>Country</th>
              <th>Sold</th>
              <th>Plan price</th>
              <th>Tax / unit</th>
              <th>Revenue</th>
              <th>Supplier cost</th>
              <th>Tax total</th>
              <th>Profit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="`${row.plan_id || row.bundle_code}`">
              <td>
                <strong>{{ row.plan_title }}</strong>
                <small>{{ row.bundle_code }}</small>
              </td>
              <td>{{ row.country }}</td>
              <td><span class="admin-badge">{{ row.sales }}</span></td>
              <td>{{ money(row.unit_price, row.currency) }}</td>
              <td>{{ money(row.unit_tax, row.currency) }}</td>
              <td>{{ money(row.revenue, row.currency) }}</td>
              <td>{{ money(row.supplier_cost, row.currency) }}</td>
              <td>{{ money(row.tax, row.currency) }}</td>
              <td>
                <strong>{{ money(row.profit, row.currency) }}</strong>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="9">
                <strong>No sales found</strong>
                <small>Try a wider date range or a different search term.</small>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>
