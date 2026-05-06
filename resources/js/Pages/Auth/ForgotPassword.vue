<script setup>
import AuthModalLayout from '@/Layouts/AuthModalLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthModalLayout });

defineProps({
  status: {
    type: String,
    default: null,
  },
});

const form = useForm({
  email: '',
});

const submit = () => {
  form.post('/forgot-password', { preserveScroll: true });
};
</script>

<template>
  <section class="auth-modal verify-modal">
    <div class="auth-modal-top">
      <Link href="/auth/login" class="auth-close" aria-label="Close">×</Link>
    </div>
    <h1>Reset password</h1>
    <p>Enter your email and we will send a reset link if an account exists.</p>
    <p v-if="status" class="success-note">{{ status }}</p>
    <form class="auth-form" @submit.prevent="submit">
      <label>
        <input v-model="form.email" type="email" placeholder="Email" autofocus />
        <small v-if="form.errors.email">{{ form.errors.email }}</small>
      </label>
      <button class="auth-submit" type="submit" :disabled="form.processing">
        {{ form.processing ? 'Sending...' : 'Send reset link' }}
      </button>
    </form>
    <Link href="/auth/login" class="auth-secondary">Back to login</Link>
  </section>
</template>
