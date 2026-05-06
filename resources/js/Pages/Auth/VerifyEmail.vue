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

const form = useForm({});

const resend = () => {
  form.post('/email/verification-notification', { preserveScroll: true });
};
</script>

<template>
  <section class="auth-modal verify-modal">
    <div class="auth-modal-top">
      <Link href="/" class="auth-close" aria-label="Close">×</Link>
    </div>
    <h1>Verify your email</h1>
    <p>
      We sent a verification link to your email. Open it to unlock your account dashboard,
      order history, and My eSIMs area.
    </p>
    <p v-if="status === 'verification-link-sent'" class="success-note">
      A fresh verification link has been sent.
    </p>
    <button class="auth-submit" type="button" :disabled="form.processing" @click="resend">
      {{ form.processing ? 'Sending...' : 'Resend verification email' }}
    </button>
    <Link href="/" class="auth-secondary">Continue browsing</Link>
  </section>
</template>
