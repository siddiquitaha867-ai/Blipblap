<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/dateTime';
import { Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  logs: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const switchType = (type) => {
  router.get('/admin/logs', { type }, { preserveState: true, replace: true });
};

const eventStatus = (log) => log.event_status || (log.esim_order_id ? `Order #${log.esim_order_id}` : 'Recorded');
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Events</p>
        <h1>Logs</h1>
      </div>
    </div>

    <div class="admin-tabs">
      <button type="button" :class="{ active: props.filters.type === 'esim' }" @click="switchType('esim')">eSIM events</button>
      <button type="button" :class="{ active: props.filters.type === 'campaign' }" @click="switchType('campaign')">Campaign events</button>
    </div>

    <section class="admin-panel">
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th>Status</th>
              <th>Customer</th>
              <th>Order</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id">
              <td>#{{ log.id }}</td>
              <td>{{ log.event_type }}</td>
              <td>{{ eventStatus(log) }}</td>
              <td>{{ log.customer_email || '-' }}</td>
              <td>{{ log.esim_order_id ? `#${log.esim_order_id}` : '-' }}</td>
              <td>{{ formatDateTime(log.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <Link
          v-for="link in logs.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="{ active: link.active, disabled: !link.url }"
          v-html="link.label"
        />
      </div>
    </section>
  </section>
</template>
