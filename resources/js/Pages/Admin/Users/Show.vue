<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
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
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>User detail</p>
        <h1>{{ customer.name }}</h1>
      </div>
      <Link href="/admin/users">Back to users</Link>
    </div>

    <div class="detail-grid">
      <section class="admin-panel">
        <h2>Account</h2>
        <dl class="admin-dl">
          <div><dt>ID</dt><dd>#{{ customer.id }}</dd></div>
          <div><dt>First name</dt><dd>{{ customer.first_name }}</dd></div>
          <div><dt>Last name</dt><dd>{{ customer.last_name || '—' }}</dd></div>
          <div><dt>Email</dt><dd>{{ customer.email }}</dd></div>
          <div><dt>Verified</dt><dd>{{ customer.email_verified_at ? 'Yes' : 'No' }}</dd></div>
          <div><dt>Admin</dt><dd>{{ customer.is_admin ? 'Yes' : 'No' }}</dd></div>
          <div><dt>Status</dt><dd>{{ customer.is_banned ? 'Banned' : 'Active' }}</dd></div>
          <div><dt>Marketing</dt><dd>{{ customer.marketing_opt_in ? 'Opted in' : 'No' }}</dd></div>
          <div><dt>Referral</dt><dd>{{ customer.referral_code || '—' }}</dd></div>
          <div><dt>Last login</dt><dd>{{ customer.last_login_at || 'Never' }}</dd></div>
          <div><dt>Joined</dt><dd>{{ customer.created_at }}</dd></div>
        </dl>
        <div class="auth-required-actions" style="margin-top: 20px;">
          <button type="button" class="auth-required-secondary" @click="sendResetPassword(customer)">Send reset password</button>
          <button type="button" class="auth-required-secondary" @click="toggleBan(customer)">
            {{ customer.is_banned ? 'Unban account' : 'Ban account' }}
          </button>
          <button type="button" class="auth-required-secondary" @click="deleteUser(customer)">Delete account</button>
        </div>
      </section>

      <section class="admin-panel">
        <h2>Customer eSIMs</h2>
        <div v-if="esims.length === 0" class="empty-admin">No eSIMs yet.</div>
        <dl v-else class="admin-dl">
          <div v-for="esim in esims" :key="esim.id">
            <dt>{{ esim.iccid }}</dt>
            <dd>{{ esim.status }} · {{ esim.current_bundle_code || 'No bundle' }}</dd>
          </div>
        </dl>
      </section>
    </div>

    <section class="admin-panel">
      <h2>Orders</h2>
      <div v-if="orders.length === 0" class="empty-admin">No orders yet.</div>
      <div v-else class="admin-table">
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
              <td>{{ order.bundle_code }}</td>
              <td>{{ order.fulfillment_status }}</td>
              <td>{{ order.currency }} {{ Number(order.total).toFixed(2) }}</td>
              <td>{{ order.paid_at || 'No' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>
