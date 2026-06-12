<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime, formatRelativeTime } from '@/utils/dateTime';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  requests: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    required: true,
  },
});

const page = usePage();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'open');
const selectedRequest = ref(props.requests.data[0] || null);

const submit = () => {
  router.get('/admin/support', {
    search: search.value,
    status: status.value,
  }, { preserveState: true, replace: true });
};

const updateStatus = (request, nextStatus) => {
  router.patch(`/admin/support/${request.id}`, { status: nextStatus }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedRequest.value = { ...request, status: nextStatus };
    },
  });
};

const statusLabel = computed(() => ({
  new: 'New',
  in_progress: 'In progress',
  resolved: 'Resolved',
}));
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Customer support</p>
        <h1>Support inbox</h1>
      </div>
    </div>

    <div class="stat-grid">
      <article>
        <span>New</span>
        <strong>{{ stats.new }}</strong>
      </article>
      <article>
        <span>In progress</span>
        <strong>{{ stats.in_progress }}</strong>
      </article>
      <article>
        <span>Resolved</span>
        <strong>{{ stats.resolved }}</strong>
      </article>
    </div>

    <form class="admin-search" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Search name, email, order, topic, or message" />
      <button type="submit">Search</button>
      <select v-model="status" class="admin-range-select" @change="submit">
        <option value="open">Open</option>
        <option value="all">All</option>
        <option value="resolved">Resolved</option>
      </select>
    </form>

    <p v-if="page.props.flash.status" class="admin-status-note">{{ page.props.flash.status }}</p>

    <section class="admin-support-grid">
      <div class="admin-panel">
        <h2>Requests</h2>
        <div class="admin-support-list">
          <button
            v-for="request in requests.data"
            :key="request.id"
            type="button"
            :class="{ active: selectedRequest?.id === request.id }"
            @click="selectedRequest = request"
          >
            <span>{{ request.topic }}</span>
            <strong>{{ request.name }}</strong>
            <small>{{ request.email }} · {{ formatRelativeTime(request.created_at) }}</small>
            <em>{{ statusLabel[request.status] || request.status }}</em>
          </button>
          <p v-if="!requests.data.length" class="admin-empty-note">No support requests found.</p>
        </div>

        <div class="pagination-row">
          <Link
            v-for="link in requests.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="{ active: link.active, disabled: !link.url }"
            v-html="link.label"
          />
        </div>
      </div>

      <aside class="admin-panel admin-support-detail">
        <template v-if="selectedRequest">
          <div class="admin-support-detail-head">
            <div>
              <span>{{ statusLabel[selectedRequest.status] || selectedRequest.status }}</span>
              <h2>{{ selectedRequest.topic }}</h2>
            </div>
            <a :href="`mailto:${selectedRequest.email}`">Reply</a>
          </div>

          <dl class="admin-user-detail-list">
            <div>
              <dt>Name</dt>
              <dd>{{ selectedRequest.name }}</dd>
            </div>
            <div>
              <dt>Email</dt>
              <dd>{{ selectedRequest.email }}</dd>
            </div>
            <div>
              <dt>Order</dt>
              <dd>{{ selectedRequest.order_reference || 'Not provided' }}</dd>
            </div>
            <div>
              <dt>Submitted</dt>
              <dd>{{ formatDateTime(selectedRequest.created_at) }}</dd>
            </div>
          </dl>

          <div class="admin-support-message">
            <span>Problem details</span>
            <p>{{ selectedRequest.message }}</p>
          </div>

          <div class="admin-support-actions">
            <button type="button" class="admin-mini-button" @click="updateStatus(selectedRequest, 'in_progress')">Mark in progress</button>
            <button type="button" class="admin-mini-button" @click="updateStatus(selectedRequest, 'resolved')">Mark resolved</button>
          </div>
        </template>
        <p v-else class="admin-empty-note">Select a request to view details.</p>
      </aside>
    </section>
  </section>
</template>
