<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/utils/dateTime';
import { Link, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
  promotions: {
    type: Object,
    required: true,
  },
  plans: {
    type: Array,
    default: () => [],
  },
  recentEvents: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  title: '',
  rule_type: 'discount',
  code: '',
  discount_type: 'percent',
  discount_value: '',
  usage_limit: '',
  applies_to: 'all',
  plan_ids: [],
  is_active: true,
  starts_at: '',
  ends_at: '',
});

const generateCode = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let suffix = '';

  for (let index = 0; index < 6; index += 1) {
    suffix += chars[Math.floor(Math.random() * chars.length)];
  }

  form.code = `BLIP${suffix}`;
};

const discountLabel = (promotion) => {
  const type = promotion.actions?.discount_type;
  const value = Number(promotion.actions?.discount_value || 0);

  if (!value) {
    return '-';
  }

  return type === 'fixed' ? `${value.toFixed(2)} off` : `${value}% off`;
};

const scopeLabel = (promotion) => {
  if (promotion.conditions?.applies_to !== 'plans') {
    return 'Whole site';
  }

  const count = promotion.conditions?.plan_ids?.length || 0;
  return `${count} selected ${count === 1 ? 'plan' : 'plans'}`;
};

const submit = () => {
  form.post('/admin/promotions', {
    preserveScroll: true,
    onSuccess: () => form.reset('title', 'code', 'discount_value', 'usage_limit', 'plan_ids', 'starts_at', 'ends_at'),
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
      <form class="admin-create-grid admin-promotion-grid" @submit.prevent="submit">
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
          <span>Promotion code</span>
          <input v-model="form.code" type="text" placeholder="BLIPSAVE20" />
          <button type="button" class="admin-mini-button" @click="generateCode">Generate code</button>
          <small v-if="form.errors.code">{{ form.errors.code }}</small>
        </label>
        <label>
          <span>Discount type</span>
          <select v-model="form.discount_type">
            <option value="percent">Percentage</option>
            <option value="fixed">Fixed amount</option>
          </select>
        </label>
        <label>
          <span>Discount value</span>
          <input v-model="form.discount_value" type="number" min="0.01" step="0.01" :placeholder="form.discount_type === 'percent' ? '20' : '5.00'" />
          <small v-if="form.errors.discount_value">{{ form.errors.discount_value }}</small>
        </label>
        <label>
          <span>Usage limit</span>
          <input v-model="form.usage_limit" type="number" min="1" step="1" placeholder="Optional" />
        </label>
        <label>
          <span>Applies to</span>
          <select v-model="form.applies_to">
            <option value="all">Whole site</option>
            <option value="plans">Selected plans only</option>
          </select>
        </label>
        <label v-if="form.applies_to === 'plans'" class="admin-promotion-plan-picker checkout-wide">
          <span>Selected plans</span>
          <select v-model="form.plan_ids" multiple>
            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
              {{ plan.location }} - {{ plan.title }} ({{ plan.currency }} {{ Number(plan.price).toFixed(2) }})
            </option>
          </select>
          <small v-if="form.errors.plan_ids">{{ form.errors.plan_ids }}</small>
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
              <th>Code</th>
              <th>Type</th>
              <th>Discount</th>
              <th>Scope</th>
              <th>Status</th>
              <th>Starts</th>
              <th>Ends</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="promotion in promotions.data" :key="promotion.id">
              <td>{{ promotion.title }}</td>
              <td><strong>{{ promotion.conditions?.code || '-' }}</strong></td>
              <td>{{ promotion.rule_type }}</td>
              <td>{{ discountLabel(promotion) }}</td>
              <td>{{ scopeLabel(promotion) }}</td>
              <td><span class="admin-badge">{{ promotion.is_active ? 'Active' : 'Paused' }}</span></td>
              <td>{{ formatDateTime(promotion.starts_at) }}</td>
              <td>{{ formatDateTime(promotion.ends_at) }}</td>
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
              <td>{{ formatDateTime(event.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>
