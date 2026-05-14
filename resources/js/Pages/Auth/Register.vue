<script setup>
import AuthModalLayout from '@/Layouts/AuthModalLayout.vue';
import AuthModal from '@/Components/AuthModal.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthModalLayout });

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  referral_code: '',
  marketing_opt_in: false,
});

const submit = () => {
  form.post('/register', {
    preserveScroll: true,
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <AuthModal mode="signup">
    <form class="auth-form" @submit.prevent="submit">
      <div class="auth-field-grid">
        <label>
          <input v-model="form.first_name" type="text" placeholder="First name" autofocus />
          <small v-if="form.errors.first_name">{{ form.errors.first_name }}</small>
        </label>

        <label>
          <input v-model="form.last_name" type="text" placeholder="Last name (Optional)" />
          <small v-if="form.errors.last_name">{{ form.errors.last_name }}</small>
        </label>
      </div>

      <label>
        <input v-model="form.email" type="email" placeholder="Email" />
        <small v-if="form.errors.email">{{ form.errors.email }}</small>
      </label>

      <label>
        <input v-model="form.password" type="password" placeholder="Password" />
        <small class="hint">Use 8+ characters with uppercase, lowercase, and number.</small>
        <small v-if="form.errors.password">{{ form.errors.password }}</small>
      </label>

      <label>
        <input v-model="form.password_confirmation" type="password" placeholder="Confirm password" />
      </label>

      <label>
        <input v-model="form.referral_code" type="text" placeholder="Referral or voucher code" />
        <small v-if="form.errors.referral_code">{{ form.errors.referral_code }}</small>
      </label>

      <label class="check-row">
        <input v-model="form.marketing_opt_in" type="checkbox" />
        <span>Send me promotions, product updates, and account rewards via email.</span>
      </label>

      <button type="submit" class="auth-submit" :disabled="form.processing">
        {{ form.processing ? 'Creating account...' : 'Create account' }}
      </button>
    </form>
  </AuthModal>
</template>
