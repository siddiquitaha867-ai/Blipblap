<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  users: {
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
  router.get('/admin/users', { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Accounts</p>
        <h1>Users</h1>
      </div>
    </div>

    <form class="admin-search" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Search name or email" />
      <button type="submit">Search</button>
    </form>

    <section class="admin-panel">
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Admin</th>
              <th>Status</th>
              <th>Verified</th>
              <th>Joined</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <td>#{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.is_admin ? 'Yes' : 'No' }}</td>
              <td>{{ user.is_banned ? 'Banned' : 'Active' }}</td>
              <td>{{ user.email_verified_at ? 'Yes' : 'No' }}</td>
              <td>{{ user.created_at }}</td>
              <td><Link :href="`/admin/users/${user.id}`">Details</Link></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <Link
          v-for="link in users.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="{ active: link.active, disabled: !link.url }"
          v-html="link.label"
        />
      </div>
    </section>
  </section>
</template>
