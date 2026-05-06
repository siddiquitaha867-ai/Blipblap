<script setup>
import AuthModalLayout from '@/Layouts/AuthModalLayout.vue';
import AuthModal from '@/Components/AuthModal.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthModalLayout });

const form = useForm({
  email: '',
  password: '',
  remember: true,
});

const submit = () => {
  form.post('/login', {
    preserveScroll: true,
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <AuthModal mode="login">
    <form class="auth-form" @submit.prevent="submit">
      <label>
        <input v-model="form.email" type="email" placeholder="Email" autofocus />
        <small v-if="form.errors.email">{{ form.errors.email }}</small>
      </label>

      <label>
        <input v-model="form.password" type="password" placeholder="Password" />
        <small v-if="form.errors.password">{{ form.errors.password }}</small>
      </label>

      <div class="auth-options">
        <label class="switch-row">
          <input v-model="form.remember" type="checkbox" />
          <span></span>
          Remember me
        </label>
        <a href="/forgot-password">Forgot password</a>
      </div>

      <button type="submit" class="auth-submit" :disabled="form.processing">
        {{ form.processing ? 'Logging in...' : 'Log in' }}
      </button>

      <p class="auth-bottom">
        New to BlipBlap?
        <a href="/auth/signup">Create an account</a>
      </p>
    </form>
  </AuthModal>
</template>
