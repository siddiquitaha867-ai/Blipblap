<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  profile: {
    type: Object,
    required: true,
  },
  loyalty: {
    type: Object,
    default: null,
  },
});

const page = usePage();
const form = useForm({
  first_name: props.profile.first_name || '',
  last_name: props.profile.last_name || '',
  phone_number: props.profile.phone_number || '',
  address_line1: props.profile.address_line1 || '',
  town: props.profile.town || '',
  city: props.profile.city || '',
  country: props.profile.country || '',
});

const initials = computed(() => {
  const first = form.first_name.trim().charAt(0);
  const last = form.last_name.trim().charAt(0);

  return `${first}${last || ''}`.toUpperCase() || 'BB';
});

const loyaltyBalance = computed(() => props.loyalty?.balance || {});
const loyaltyHistory = computed(() => props.loyalty?.history || []);
const redeemReady = computed(() => Number(loyaltyBalance.value.redeemable_rewards || 0) > 0);
const pointsLabel = (value) => `${Number(value || 0)} pts`;
const orderTypeLabel = (type) => type === 'topup' ? 'Top-up' : 'New eSIM';

const saveProfile = () => {
  form.patch('/my-account', {
    preserveScroll: true,
  });
};
</script>

<template>
  <section class="my-account-page">
    <div class="my-account-hero">
      <div class="my-account-avatar">{{ initials }}</div>
      <div>
        <span>My account</span>
        <h1>{{ form.first_name || profile.name || 'Your profile' }}</h1>
        <p>Keep your contact and delivery details ready for faster eSIM checkout.</p>
      </div>
      <Link href="/my-esims">My eSIMs</Link>
    </div>

    <form class="my-account-form" @submit.prevent="saveProfile">
      <p v-if="page.props.flash.status" class="my-account-status">
        {{ page.props.flash.status }}
      </p>

      <section class="my-account-section">
        <div>
          <span>Profile</span>
          <h2>Personal details</h2>
        </div>
        <div class="my-account-grid">
          <label>
            <span>First name</span>
            <input v-model="form.first_name" type="text" autocomplete="given-name" required>
            <small v-if="form.errors.first_name">{{ form.errors.first_name }}</small>
          </label>
          <label>
            <span>Last name</span>
            <input v-model="form.last_name" type="text" autocomplete="family-name">
            <small v-if="form.errors.last_name">{{ form.errors.last_name }}</small>
          </label>
          <label>
            <span>Email</span>
            <input :value="profile.email" type="email" autocomplete="email" readonly>
          </label>
          <label>
            <span>Phone number</span>
            <input v-model="form.phone_number" type="text" autocomplete="tel" placeholder="+971 50 000 0000">
            <small v-if="form.errors.phone_number">{{ form.errors.phone_number }}</small>
          </label>
        </div>
      </section>

      <section class="my-account-section">
        <div>
          <span>Location</span>
          <h2>Address details</h2>
        </div>
        <div class="my-account-grid">
          <label class="my-account-wide">
            <span>Address</span>
            <input v-model="form.address_line1" type="text" autocomplete="street-address" placeholder="Street, building, apartment">
            <small v-if="form.errors.address_line1">{{ form.errors.address_line1 }}</small>
          </label>
          <label>
            <span>Country</span>
            <input v-model="form.country" type="text" autocomplete="country-name" placeholder="United Arab Emirates">
            <small v-if="form.errors.country">{{ form.errors.country }}</small>
          </label>
          <label>
            <span>Town</span>
            <input v-model="form.town" type="text" placeholder="Town / area">
            <small v-if="form.errors.town">{{ form.errors.town }}</small>
          </label>
          <label>
            <span>City</span>
            <input v-model="form.city" type="text" autocomplete="address-level2" placeholder="Dubai">
            <small v-if="form.errors.city">{{ form.errors.city }}</small>
          </label>
        </div>
      </section>

      <section class="my-account-section">
        <div>
          <span>Loyalty</span>
          <h2>Points and rewards</h2>
        </div>
        <div class="my-account-loyalty">
          <div class="my-account-loyalty-band">
            <div>
              <strong>{{ pointsLabel(loyaltyBalance.points_balance) }}</strong>
              <span>Current balance</span>
            </div>
            <div>
              <strong>{{ pointsLabel(loyalty?.points_per_purchase) }}</strong>
              <span>Per purchase</span>
            </div>
            <div>
              <strong>{{ pointsLabel(loyalty?.redeem_threshold) }}</strong>
              <span>Redeem threshold</span>
            </div>
          </div>

          <div class="my-account-loyalty-stats">
            <article>
              <span>Lifetime earned</span>
              <strong>{{ pointsLabel(loyaltyBalance.lifetime_points_earned) }}</strong>
            </article>
            <article>
              <span>Redeemed</span>
              <strong>{{ pointsLabel(loyaltyBalance.lifetime_points_redeemed) }}</strong>
            </article>
            <article>
              <span>Available rewards</span>
              <strong>{{ loyaltyBalance.redeemable_rewards || 0 }}</strong>
            </article>
          </div>

          <p class="my-account-loyalty-note">
            <template v-if="redeemReady">
              {{ pointsLabel(loyaltyBalance.redeemable_points) }} are ready to redeem.
            </template>
            <template v-else>
              {{ pointsLabel(loyaltyBalance.points_to_next_redeem) }} left until your next redemption.
            </template>
          </p>

          <div class="my-account-loyalty-table-wrap">
            <table class="my-account-loyalty-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Activity</th>
                  <th>Order</th>
                  <th>Total</th>
                  <th>Points</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!loyaltyHistory.length">
                  <td colspan="5">Points will appear here after the first paid purchase.</td>
                </tr>
                <tr v-for="event in loyaltyHistory" :key="event.id">
                  <td>{{ event.created_at ? new Date(event.created_at).toLocaleDateString() : 'Pending' }}</td>
                  <td>{{ event.event_type === 'purchase_award' ? orderTypeLabel(event.order_type) : event.event_type }}</td>
                  <td>{{ event.order_reference || 'Not linked' }}</td>
                  <td>{{ event.currency && event.total !== null ? `${event.currency} ${Number(event.total).toFixed(2)}` : '—' }}</td>
                  <td>+{{ pointsLabel(event.points) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <div class="my-account-actions">
        <button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Saving...' : 'Save changes' }}
        </button>
        <Link href="/logout" method="post" as="button">Logout</Link>
      </div>
    </form>
  </section>
</template>
