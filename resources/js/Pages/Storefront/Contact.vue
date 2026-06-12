<script setup>
import SiteFooter from '@/Components/SiteFooter.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: StorefrontLayout });

const page = usePage();
const form = useForm({
  name: page.props.auth.user?.name || '',
  email: page.props.auth.user?.email || '',
  topic: 'eSIM installation help',
  order_reference: '',
  message: '',
});

const submit = () => {
  form.post('/contact-us', {
    preserveScroll: true,
    onSuccess: () => form.reset('topic', 'order_reference', 'message'),
  });
};
</script>

<template>
  <section class="contact-page">
    <section class="contact-hero">
      <div>
        <span>BlipBlap Support</span>
        <h1>Contact Us</h1>
        <p>Send your order, installation, payment, or email delivery question to the BlipBlap team.</p>
      </div>
      <img src="/images/blipblap/trust-support-24-7.svg" alt="BlipBlap customer support">
    </section>

    <section class="contact-workspace">
      <form class="contact-form" @submit.prevent="submit">
        <div class="contact-form-head">
          <span>Contact form</span>
          <h2>Tell us what happened</h2>
          <p v-if="page.props.flash.status" class="contact-status">{{ page.props.flash.status }}</p>
        </div>

        <label>
          <span>Name</span>
          <input v-model="form.name" type="text" autocomplete="name">
          <small v-if="form.errors.name">{{ form.errors.name }}</small>
        </label>

        <label>
          <span>Email</span>
          <input v-model="form.email" type="email" autocomplete="email">
          <small v-if="form.errors.email">{{ form.errors.email }}</small>
        </label>

        <label>
          <span>Topic</span>
          <select v-model="form.topic">
            <option>eSIM installation help</option>
            <option>Payment or checkout issue</option>
            <option>Order email not received</option>
            <option>Top-up support</option>
            <option>Refund question</option>
            <option>General support</option>
          </select>
          <small v-if="form.errors.topic">{{ form.errors.topic }}</small>
        </label>

        <label>
          <span>Order reference</span>
          <input v-model="form.order_reference" type="text" placeholder="BB-2026...">
          <small v-if="form.errors.order_reference">{{ form.errors.order_reference }}</small>
        </label>

        <label class="contact-message-field">
          <span>Message</span>
          <textarea v-model="form.message" rows="7" placeholder="Include destination, device model, ICCID, and what you see on screen."></textarea>
          <small v-if="form.errors.message">{{ form.errors.message }}</small>
        </label>

        <button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Sending...' : 'Send message' }}
        </button>
      </form>

      <aside class="contact-delivery">
        <div class="contact-delivery-panel">
          <span>Support response</span>
          <h2>We route your message to the right team</h2>
          <p>Your request is saved in our support inbox so the admin team can track, reply, and resolve it.</p>
          <dl>
            <div>
              <dt>Best for</dt>
              <dd>Orders, QR codes, top-ups, refunds, and install help</dd>
            </div>
            <div>
              <dt>Include</dt>
              <dd>Order reference, device model, destination, and ICCID if available</dd>
            </div>
            <div>
              <dt>Reply path</dt>
              <dd>We reply to the email address you enter in this form</dd>
            </div>
          </dl>
        </div>

        <div class="contact-routing">
          <article>
            <strong>Order support</strong>
            <p>Share the BB order reference so we can find payment, provisioning, and email logs faster.</p>
          </article>
          <article>
            <strong>Installation help</strong>
            <p>Tell us whether you are scanning the QR, using manual details, or opening the page on your phone.</p>
          </article>
          <article>
            <strong>Useful details</strong>
            <p>Add your phone model, destination, ICCID, and a screenshot description when installation gets stuck.</p>
          </article>
        </div>
      </aside>
    </section>

    <SiteFooter />
  </section>
</template>
