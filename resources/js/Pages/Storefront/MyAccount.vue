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

      <div class="my-account-actions">
        <button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Saving...' : 'Save changes' }}
        </button>
        <Link href="/logout" method="post" as="button">Logout</Link>
      </div>
    </form>
  </section>
</template>
