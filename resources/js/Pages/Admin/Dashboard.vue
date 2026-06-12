<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
  stats: {
    type: Object,
    required: true,
  },
  recentUsers: {
    type: Array,
    default: () => [],
  },
  recentRequests: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Overview</p>
        <h1>BlipBlap control room</h1>
      </div>
      <Link href="/admin/users">View all users</Link>
    </div>

    <div class="stat-grid">
      <article>
        <span>Total users</span>
        <strong>{{ stats.users }}</strong>
      </article>
      <article>
        <span>Verified users</span>
        <strong>{{ stats.verified_users }}</strong>
      </article>
      <article>
        <span>Plans</span>
        <strong>{{ stats.plans }}</strong>
      </article>
      <article>
        <span>Orders</span>
        <strong>{{ stats.orders }}</strong>
      </article>
      <article>
        <span>Promotions</span>
        <strong>{{ stats.promotions }}</strong>
      </article>
      <article>
        <span>Open support</span>
        <strong>{{ stats.support_open }}</strong>
      </article>
    </div>

    <section class="admin-panel">
      <div class="admin-user-panel-head">
        <h2>Recent support requests</h2>
        <Link href="/admin/support">Open inbox</Link>
      </div>
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>Topic</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Order</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="request in recentRequests" :key="request.id">
              <td>{{ request.topic }}</td>
              <td>
                <strong>{{ request.name }}</strong>
                <small>{{ request.email }}</small>
              </td>
              <td>{{ request.status }}</td>
              <td>{{ request.order_reference || '-' }}</td>
            </tr>
            <tr v-if="!recentRequests.length">
              <td colspan="4">No contact requests yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="admin-panel">
      <h2>Recent users</h2>
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Verified</th>
              <th>Last login</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in recentUsers" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.email_verified_at ? 'Yes' : 'No' }}</td>
              <td>{{ user.last_login_at || 'Never' }}</td>
              <td><Link :href="`/admin/users/${user.id}`">Open</Link></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>
