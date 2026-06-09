<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  country: {
    type: String,
    required: true,
  },
  plans: {
    type: Array,
    required: true,
  },
});

const openDays = ref(new Set());
const drafts = ref({});

const dataLabel = (plan) => {
  if (plan.unlimited) {
    return 'Unlimited';
  }

  return `${Number(plan.data_amount || 0)} ${plan.data_unit || 'GB'}`;
};

const draftFor = (plan) => ({
  title: plan.title || '',
  retail_price: Number(plan.retail_price || 0),
  tax_amount: Number(plan.tax_amount || 0),
  duration_days: Number(plan.duration_days || 1),
  unlimited: Boolean(plan.unlimited),
  data_amount: plan.data_amount === null ? '' : Number(plan.data_amount || 0),
  data_unit: plan.data_unit || 'GB',
  is_active: Boolean(plan.is_active),
  is_featured: Boolean(plan.is_featured),
});

const dayGroups = computed(() => {
  const days = new Map();

  props.plans.forEach((plan) => {
    const key = Number(plan.duration_days || 0);

    if (!days.has(key)) {
      days.set(key, []);
    }

    days.get(key).push(plan);
  });

  return Array.from(days.entries())
    .sort(([a], [b]) => a - b)
    .map(([daysKey, plans]) => ({
      days: daysKey,
      plans: plans.slice().sort((a, b) => {
        if (Boolean(a.unlimited) !== Boolean(b.unlimited)) {
          return a.unlimited ? 1 : -1;
        }

        return Number(a.data_amount || 0) - Number(b.data_amount || 0);
      }),
    }));
});

watchEffect(() => {
  const nextDrafts = {};

  props.plans.forEach((plan) => {
    nextDrafts[plan.id] = drafts.value[plan.id] || draftFor(plan);
  });

  drafts.value = nextDrafts;
});

const isDayOpen = (days) => openDays.value.has(days);

const toggleDay = (days) => {
  const next = new Set(openDays.value);

  if (next.has(days)) {
    next.delete(days);
  } else {
    next.add(days);
  }

  openDays.value = next;
};

const updatePlan = (plan) => {
  const draft = drafts.value[plan.id];

  router.patch(`/admin/plans/${plan.id}`, {
    title: draft.title,
    retail_price: draft.retail_price,
    tax_amount: draft.tax_amount,
    duration_days: draft.duration_days,
    unlimited: Boolean(draft.unlimited),
    data_amount: draft.unlimited ? null : draft.data_amount,
    data_unit: draft.unlimited ? null : draft.data_unit,
    is_active: Boolean(draft.is_active),
    is_featured: Boolean(draft.is_featured),
  }, {
    preserveScroll: true,
  });
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Plans by country</p>
        <h1>{{ country }}</h1>
      </div>
      <Link href="/admin/plans">All countries</Link>
    </div>

    <section class="admin-panel">
      <div class="admin-selected-plans">
        <header>
          <div>
            <p>Selected country / region</p>
            <h2>{{ country }}</h2>
          </div>
          <strong>{{ plans.length }} plans</strong>
        </header>

        <section v-for="dayGroup in dayGroups" :key="dayGroup.days" class="admin-day-row">
          <button type="button" class="admin-day-trigger" @click="toggleDay(dayGroup.days)">
            <span>{{ dayGroup.days }} {{ dayGroup.days === 1 ? 'day' : 'days' }}</span>
            <small>{{ dayGroup.plans.length }} plans</small>
            <em :class="{ open: isDayOpen(dayGroup.days) }">v</em>
          </button>

          <div v-if="isDayOpen(dayGroup.days)" class="admin-plan-lines">
            <article v-for="plan in dayGroup.plans" :key="plan.id" class="admin-plan-line">
              <div class="admin-plan-summary">
                <strong>{{ dataLabel(plan) }}</strong>
                <span>{{ plan.title }}</span>
                <small>{{ plan.supplier_code }}</small>
              </div>

              <div class="admin-plan-edit-line">
                <label class="admin-plan-title-field">
                  <span>Plan name</span>
                  <input v-model="drafts[plan.id].title" type="text" />
                </label>
                <label>
                  <span>Cost</span>
                  <input :value="Number(plan.supplier_price || 0).toFixed(2)" type="text" readonly />
                </label>
                <label>
                  <span>Retail</span>
                  <input v-model="drafts[plan.id].retail_price" type="number" min="0" step="0.01" />
                </label>
                <label>
                  <span>Tax</span>
                  <input v-model="drafts[plan.id].tax_amount" type="number" min="0" step="0.01" />
                </label>
                <label>
                  <span>Margin</span>
                  <input :value="Number(plan.margin_amount || 0).toFixed(2)" type="text" readonly />
                </label>
                <label>
                  <span>Profit after tax</span>
                  <input :value="Number(plan.net_profit || 0).toFixed(2)" type="text" readonly />
                </label>
                <label>
                  <span>Margin %</span>
                  <input :value="plan.margin_percent === null ? 'n/a' : `${Number(plan.margin_percent).toFixed(2)}%`" type="text" readonly />
                </label>
                <label>
                  <span>Days</span>
                  <input v-model="drafts[plan.id].duration_days" type="number" min="1" step="1" />
                </label>
                <label class="admin-check-row">
                  <input v-model="drafts[plan.id].unlimited" type="checkbox" />
                  Unlimited
                </label>
                <label v-if="!drafts[plan.id].unlimited">
                  <span>Limit</span>
                  <input v-model="drafts[plan.id].data_amount" type="number" min="0" step="0.01" />
                </label>
                <label v-if="!drafts[plan.id].unlimited">
                  <span>Unit</span>
                  <select v-model="drafts[plan.id].data_unit">
                    <option value="GB">GB</option>
                    <option value="MB">MB</option>
                  </select>
                </label>
                <div class="admin-plan-status-stack">
                  <label class="admin-check-row">
                    <input v-model="drafts[plan.id].is_active" type="checkbox" />
                    Active
                  </label>
                  <label class="admin-check-row">
                    <input v-model="drafts[plan.id].is_featured" type="checkbox" />
                    Featured
                  </label>
                </div>
                <button type="button" class="admin-mini-button" @click="updatePlan(plan)">Save</button>
              </div>
            </article>
          </div>
        </section>
      </div>
    </section>
  </section>
</template>
