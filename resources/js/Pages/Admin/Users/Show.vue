<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  customer: {
    type: Object,
    required: true,
  },
  orders: {
    type: Array,
    default: () => [],
  },
  esims: {
    type: Array,
    default: () => [],
  },
});

const initials = computed(() => {
  const first = (props.customer.first_name || props.customer.name || '').trim().charAt(0);
  const last = (props.customer.last_name || '').trim().charAt(0);

  return `${first}${last || ''}`.toUpperCase() || 'BB';
});

const accountDetails = computed(() => [
  ['ID', `#${props.customer.id}`],
  ['First name', props.customer.first_name || '-'],
  ['Last name', props.customer.last_name || '-'],
  ['Email', props.customer.email],
  ['Verified', props.customer.email_verified_at ? 'Yes' : 'No', props.customer.email_verified_at ? 'success' : 'warning'],
  ['Admin', props.customer.is_admin ? 'Yes' : 'No', props.customer.is_admin ? 'info' : 'neutral'],
  ['Status', props.customer.is_banned ? 'Banned' : 'Active', props.customer.is_banned ? 'danger' : 'success'],
  ['Marketing', props.customer.marketing_opt_in ? 'Opted in' : 'No', props.customer.marketing_opt_in ? 'info' : 'neutral'],
  ['Referral', props.customer.referral_code || '-'],
  ['Last login', props.customer.last_login_at || 'Never'],
  ['Joined', props.customer.created_at],
]);

const paidOrdersCount = computed(() => props.orders.filter((order) => order.paid_at).length);

const toggleBan = (customer) => {
  router.patch(`/admin/users/${customer.id}/${customer.is_banned ? 'unban' : 'ban'}`, {}, {
    preserveScroll: true,
  });
};

const sendResetPassword = (customer) => {
  router.post(`/admin/users/${customer.id}/reset-password`, {}, {
    preserveScroll: true,
  });
};

const deleteUser = (customer) => {
  if (!window.confirm(`Delete ${customer.email}? This cannot be undone.`)) {
    return;
  }

  router.delete(`/admin/users/${customer.id}`);
};

const badgeClass = (tone) => `admin-user-badge admin-user-badge--${tone || 'neutral'}`;
</script>

<template>
  <section class="admin-page admin-user-page">
    <div class="admin-user-hero">
      <div class="admin-user-avatar">{{ initials }}</div>
      <div>
        <p>User detail</p>
        <h1>{{ customer.name }}</h1>
        <span>{{ customer.email }}</span>
      </div>
      <div class="admin-user-hero-actions">
        <Link href="/admin/users">Back to users</Link>
      </div>
    </div>

    <div class="admin-user-stats">
      <article>
        <span>Orders</span>
        <strong>{{ orders.length }}</strong>
      </article>
      <article>
        <span>Paid orders</span>
        <strong>{{ paidOrdersCount }}</strong>
      </article>
      <article>
        <span>Customer eSIMs</span>
        <strong>{{ esims.length }}</strong>
      </article>
    </div>

    <div class="detail-grid">
      <section class="admin-panel admin-user-panel">
        <div class="admin-user-panel-head">
          <div>
            <span>Account</span>
            <h2>Customer profile</h2>
          </div>
          <span :class="badgeClass(customer.is_banned ? 'danger' : 'success')">
            {{ customer.is_banned ? 'Banned' : 'Active' }}
          </span>
        </div>

        <dl class="admin-user-detail-list">
          <div v-for="[label, value, tone] in accountDetails" :key="label">
            <dt>{{ label }}</dt>
            <dd>
              <span v-if="tone" :class="badgeClass(tone)">{{ value }}</span>
              <span v-else>{{ value }}</span>
            </dd>
          </div>
        </dl>

        <div class="admin-user-actions">
          <button type="button" class="admin-action-button admin-action-button--soft" @click="sendResetPassword(customer)">Send reset password</button>
          <button type="button" class="admin-action-button admin-action-button--warning" @click="toggleBan(customer)">
            {{ customer.is_banned ? 'Unban account' : 'Ban account' }}
          </button>
          <button type="button" class="admin-action-button admin-action-button--danger" @click="deleteUser(customer)">Delete account</button>
        </div>
      </section>

      <section class="admin-panel admin-user-panel">
        <div class="admin-user-panel-head">
          <div>
            <span>Inventory</span>
            <h2>Customer eSIMs</h2>
          </div>
        </div>
        <div v-if="esims.length === 0" class="empty-admin">No eSIMs yet.</div>
        <div v-else class="admin-esim-list">
          <article v-for="esim in esims" :key="esim.id">
            <span :class="badgeClass('info')">{{ esim.status }}</span>
            <strong>{{ esim.current_bundle_code || 'No bundle' }}</strong>
            <small>{{ esim.iccid }}</small>
          </article>
        </div>
      </section>
    </div>

    <section class="admin-panel admin-user-panel">
      <div class="admin-user-panel-head">
        <div>
          <span>Activity</span>
          <h2>Orders</h2>
        </div>
      </div>
      <div v-if="orders.length === 0" class="empty-admin">No orders yet.</div>
      <div v-else class="admin-table admin-user-orders-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Bundle</th>
              <th>Status</th>
              <th>Total</th>
              <th>Paid</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id">
              <td>#{{ order.id }}</td>
              <td><strong>{{ order.bundle_code }}</strong></td>
              <td><span :class="badgeClass(order.paid_at ? 'success' : 'warning')">{{ order.fulfillment_status }}</span></td>
              <td>{{ order.currency }} {{ Number(order.total).toFixed(2) }}</td>
              <td>{{ order.paid_at || 'No' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>
