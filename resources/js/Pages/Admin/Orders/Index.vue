<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/dateTime';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
const range = ref(props.filters.range || 'last_7_days');
const selectedOrder = ref(null);
const copiedReference = ref('');

const rangeOptions = computed(() => props.filters.range_options || {
  last_7_days: 'Last 7 days',
  last_15_days: 'Last 15 days',
  last_1_month: 'Last 1 month',
  last_3_months: 'Last 3 months',
});

const query = computed(() => ({
  search: search.value,
  range: range.value,
}));

const csvUrl = computed(() => {
  const params = new URLSearchParams();

  if (search.value) {
    params.set('search', search.value);
  }

  params.set('range', range.value);

  return `/admin/orders/export?${params.toString()}`;
});

const submit = () => {
  router.get('/admin/orders', query.value, { preserveState: true, replace: true });
};

const addressFor = (order) => [
  order.address_line1,
  order.address_line2,
  order.city,
  order.state,
  order.postal_code,
  order.country,
].filter(Boolean).join(', ');

const paymentFor = (order) => [
  order.payment_method || 'stripe',
  order.payment_brand,
  order.payment_last4 ? `**** ${order.payment_last4}` : '',
].filter(Boolean).join(' ');

const copyReference = async (value) => {
  if (!value) {
    return;
  }

  await navigator.clipboard.writeText(value);
  copiedReference.value = value;
  window.setTimeout(() => {
    if (copiedReference.value === value) {
      copiedReference.value = '';
    }
  }, 1600);
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
      <input v-model="search" type="search" placeholder="Search customer, email, BlipBlap ref, eSIM Go ref, payment, bundle, ICCID" />
      <button type="submit">Search</button>
      <div class="admin-order-actions">
        <select v-model="range" class="admin-range-select" @change="submit">
          <option v-for="(label, value) in rangeOptions" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
        <a class="admin-export-button" :href="csvUrl">CSV</a>
      </div>
    </form>

    <section class="admin-panel">
      <div class="admin-table admin-orders-table">
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Bundle</th>
              <th>Status</th>
              <th>Total</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in orders.data"
              :key="order.id"
              class="admin-order-row"
              @click="selectedOrder = order"
            >
              <td>
                <strong>#{{ order.id }}</strong>
                <small>{{ order.order_reference || 'No BlipBlap reference' }}</small>
                <small v-if="order.esim_go_lookup_reference">
                  eSIM Go: {{ order.esim_go_lookup_reference }}
                </small>
              </td>
              <td>
                <strong>{{ order.customer_name || 'No name' }}</strong>
                <small>{{ order.customer_email }}</small>
              </td>
              <td>{{ order.bundle_code }}</td>
              <td><span class="admin-badge">{{ order.status }}</span></td>
              <td>{{ order.currency }} {{ Number(order.total).toFixed(2) }}</td>
              <td>
                <button type="button" class="admin-mini-button" @click.stop="selectedOrder = order">
                  View
                </button>
              </td>
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

    <Teleport to="body">
      <div
        v-if="selectedOrder"
        class="admin-order-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="order-detail-title"
        @click.self="selectedOrder = null"
      >
        <section class="admin-order-dialog">
          <header>
            <div>
              <span>Order details</span>
              <h2 id="order-detail-title">#{{ selectedOrder.id }}</h2>
              <p>{{ selectedOrder.order_reference || selectedOrder.payment_reference || 'No reference' }}</p>
            </div>
            <button type="button" aria-label="Close order details" @click="selectedOrder = null">x</button>
          </header>

          <div class="admin-order-detail-grid">
            <article>
              <span>Customer</span>
              <strong>{{ selectedOrder.customer_name || 'No name' }}</strong>
              <p>{{ selectedOrder.customer_email }}</p>
              <p>{{ selectedOrder.customer_phone || 'No phone' }}</p>
            </article>
            <article>
              <span>Address</span>
              <strong>{{ addressFor(selectedOrder) || 'No address saved' }}</strong>
            </article>
            <article>
              <span>References</span>
              <strong>{{ selectedOrder.order_reference || 'No BlipBlap reference' }}</strong>
              <button
                v-if="selectedOrder.order_reference"
                type="button"
                class="admin-mini-button reference-copy-button"
                @click="copyReference(selectedOrder.order_reference)"
              >
                {{ copiedReference === selectedOrder.order_reference ? 'Copied' : 'Copy BlipBlap ref' }}
              </button>
              <p>eSIM Go lookup: {{ selectedOrder.esim_go_lookup_reference || 'Not returned yet' }}</p>
              <button
                v-if="selectedOrder.esim_go_lookup_reference"
                type="button"
                class="admin-mini-button reference-copy-button"
                @click="copyReference(selectedOrder.esim_go_lookup_reference)"
              >
                {{ copiedReference === selectedOrder.esim_go_lookup_reference ? 'Copied' : 'Copy eSIM Go ref' }}
              </button>
              <p v-if="selectedOrder.apply_reference && selectedOrder.apply_reference !== selectedOrder.esim_go_lookup_reference">
                Apply ref: {{ selectedOrder.apply_reference }}
              </p>
              <p>Type: {{ selectedOrder.order_type || 'new_esim' }}</p>
            </article>
            <article>
              <span>Payment</span>
              <strong>{{ paymentFor(selectedOrder) }}</strong>
              <p>{{ selectedOrder.payment_reference || 'No payment reference' }}</p>
              <p>{{ selectedOrder.paid_at ? formatDateTime(selectedOrder.paid_at) : 'Pending payment' }}</p>
            </article>
            <article>
              <span>Plan</span>
              <strong>{{ selectedOrder.bundle_code }}</strong>
              <p>ICCID: {{ selectedOrder.iccid || 'Not assigned' }}</p>
            </article>
            <article>
              <span>Status</span>
              <strong>{{ selectedOrder.status }}</strong>
              <p>{{ selectedOrder.fulfillment_status }}</p>
            </article>
            <article>
              <span>Amounts</span>
              <strong>{{ selectedOrder.currency }} {{ Number(selectedOrder.total).toFixed(2) }}</strong>
              <p>Subtotal: {{ selectedOrder.currency }} {{ Number(selectedOrder.subtotal).toFixed(2) }}</p>
              <p>Tax: {{ selectedOrder.currency }} {{ Math.max(0, Number(selectedOrder.total) - Number(selectedOrder.subtotal)).toFixed(2) }}</p>
            </article>
            <article>
              <span>Created</span>
              <strong>{{ formatDateTime(selectedOrder.created_at) }}</strong>
            </article>
          </div>
        </section>
      </div>
    </Teleport>
  </section>
</template>
