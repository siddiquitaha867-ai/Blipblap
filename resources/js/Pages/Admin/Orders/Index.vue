<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  orders: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');

const submit = () => {
  router.get('/admin/orders', { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Sales</p>
        <h1>Orders</h1>
      </div>
    </div>

    <form class="admin-search" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Search email, order, payment, bundle, ICCID" />
      <button type="submit">Search</button>
    </form>

    <section class="admin-panel">
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Bundle</th>
              <th>Status</th>
              <th>Fulfillment</th>
              <th>Total</th>
              <th>Paid</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders.data" :key="order.id">
              <td>
                <strong>#{{ order.id }}</strong>
                <small>{{ order.order_reference || order.payment_reference || 'No reference' }}</small>
              </td>
              <td>{{ order.customer_email }}</td>
              <td>{{ order.bundle_code }}</td>
              <td><span class="admin-badge">{{ order.status }}</span></td>
              <td>{{ order.fulfillment_status }}</td>
              <td>{{ order.currency }} {{ Number(order.total).toFixed(2) }}</td>
              <td>{{ order.paid_at || 'Pending' }}</td>
              <td>{{ order.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <Link
          v-for="link in orders.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="{ active: link.active, disabled: !link.url }"
          v-html="link.label"
        />
      </div>
    </section>
  </section>
</template>
