<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
  promotions: {
    type: Object,
    required: true,
  },
  recentEvents: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  title: '',
  rule_type: 'discount',
  is_active: true,
  starts_at: '',
  ends_at: '',
});

const submit = () => {
  form.post('/admin/promotions', {
    preserveScroll: true,
    onSuccess: () => form.reset('title', 'starts_at', 'ends_at'),
  });
};

const togglePromotion = (promotion) => {
  router.patch(`/admin/promotions/${promotion.id}`, {
    is_active: !promotion.is_active,
  }, { preserveScroll: true });
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Growth</p>
        <h1>Promotions</h1>
      </div>
    </div>

    <section class="admin-panel admin-form-panel">
      <h2>Create promotion</h2>
      <form class="admin-create-grid" @submit.prevent="submit">
        <label>
          <span>Title</span>
          <input v-model="form.title" type="text" placeholder="Summer discount" />
          <small v-if="form.errors.title">{{ form.errors.title }}</small>
        </label>
        <label>
          <span>Type</span>
          <select v-model="form.rule_type">
            <option value="discount">Discount</option>
            <option value="referral">Referral</option>
            <option value="bundle_offer">Bundle offer</option>
            <option value="campaign">Campaign</option>
          </select>
        </label>
        <label>
          <span>Starts</span>
          <input v-model="form.starts_at" type="datetime-local" />
        </label>
        <label>
          <span>Ends</span>
          <input v-model="form.ends_at" type="datetime-local" />
          <small v-if="form.errors.ends_at">{{ form.errors.ends_at }}</small>
        </label>
        <label class="admin-check-row">
          <input v-model="form.is_active" type="checkbox" />
          Active
        </label>
        <button type="submit" :disabled="form.processing">Create</button>
      </form>
    </section>

    <section class="admin-panel">
      <h2>Promotion rules</h2>
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Type</th>
              <th>Status</th>
              <th>Starts</th>
              <th>Ends</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="promotion in promotions.data" :key="promotion.id">
              <td>{{ promotion.title }}</td>
              <td>{{ promotion.rule_type }}</td>
              <td><span class="admin-badge">{{ promotion.is_active ? 'Active' : 'Paused' }}</span></td>
              <td>{{ promotion.starts_at || '-' }}</td>
              <td>{{ promotion.ends_at || '-' }}</td>
              <td>
                <button type="button" class="admin-mini-button" @click="togglePromotion(promotion)">
                  {{ promotion.is_active ? 'Pause' : 'Activate' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <Link
          v-for="link in promotions.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="{ active: link.active, disabled: !link.url }"
          v-html="link.label"
        />
      </div>
    </section>

    <section class="admin-panel">
      <h2>Recent campaign events</h2>
      <div class="admin-table">
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Status</th>
              <th>Customer</th>
              <th>Order</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in recentEvents" :key="event.id">
              <td>{{ event.event_type }}</td>
              <td>{{ event.event_status }}</td>
              <td>{{ event.customer_email || '-' }}</td>
              <td>{{ event.esim_order_id ? `#${event.esim_order_id}` : '-' }}</td>
              <td>{{ event.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>
