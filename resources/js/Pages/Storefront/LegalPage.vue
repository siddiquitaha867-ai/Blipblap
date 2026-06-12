<script setup>
import SiteFooter from '@/Components/SiteFooter.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { computed } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  type: {
    type: String,
    required: true,
  },
});

const updatedDate = '12 June 2026';

const page = computed(() => {
  if (props.type === 'privacy') {
    return {
      eyebrow: 'Privacy Policy',
      title: 'Privacy Policy',
      summary: 'How BlipBlap collects, uses, and protects customer information for eSIM purchases and support.',
      sections: [
        {
          title: 'Information we collect',
          body: 'We collect the information needed to provide eSIM services, including your name, email address, phone number, billing or delivery details, account details, order history, payment status, support messages, and technical information such as device, browser, and site usage data.',
        },
        {
          title: 'How we use information',
          body: 'We use customer information to create accounts, process orders, deliver eSIM installation details, provide customer support, prevent fraud, maintain site security, improve our services, and send service messages related to purchases.',
        },
        {
          title: 'Payments and partners',
          body: 'Payments are processed by Stripe. eSIM fulfilment may require sharing order details with our connectivity and provisioning partners. These providers only receive information needed to perform their services.',
        },
        {
          title: 'Cookies and analytics',
          body: 'Essential cookies are used for login, checkout, security, and session management. Analytics, advertising, or personalization cookies should only be used after consent where required by law.',
        },
        {
          title: 'International users',
          body: 'If you access BlipBlap from the United Kingdom, European Union, or European Economic Area, you may have rights to access, correct, delete, restrict, or object to certain processing of your personal data.',
        },
        {
          title: 'Data retention',
          body: 'We keep personal information only as long as needed for the purposes described here, including order fulfilment, legal, tax, accounting, fraud prevention, and support obligations.',
        },
        {
          title: 'Contact',
          body: 'For privacy questions or requests, contact BlipBlap support through the contact options on this website.',
        },
      ],
    };
  }

  return {
    eyebrow: 'Terms and Conditions',
    title: 'Terms and Conditions',
    summary: 'The purchase terms that apply when customers buy and use BlipBlap eSIM plans.',
    sections: [
      {
        title: 'Using BlipBlap',
        body: 'By creating an account, placing an order, or using a BlipBlap eSIM, you agree to these terms. You must provide accurate information and keep your account access secure.',
      },
      {
        title: 'eSIM compatibility',
        body: 'Customers are responsible for confirming that their device is unlocked and eSIM-compatible before purchase. Network availability, speed, and coverage depend on destination, local operators, device settings, and plan type.',
      },
      {
        title: 'Orders and delivery',
        body: 'After successful payment, BlipBlap prepares eSIM installation details and sends them to the delivery email on the order. Some orders may require fraud, payment, or provisioning checks before fulfilment.',
      },
      {
        title: 'Prices, taxes, and payment',
        body: 'Prices are shown before checkout and may include applicable taxes or fees where configured. Payment is processed through our payment provider, and an order is not complete until payment is confirmed.',
      },
      {
        title: 'Refunds',
        body: 'Refund eligibility may depend on whether the eSIM has been issued, installed, activated, or used. Contact support promptly if you purchased the wrong plan, cannot install your eSIM, or believe there is a service issue.',
      },
      {
        title: 'Acceptable use',
        body: 'You must not misuse the service, attempt unauthorized access, resell plans without permission, or use the service in a way that breaks applicable law or operator policies.',
      },
      {
        title: 'Changes',
        body: 'BlipBlap may update these terms as services, laws, and provider requirements change. The version shown on this page applies from the listed effective date.',
      },
    ],
  };
});
</script>

<template>
  <section class="legal-page">
    <section class="legal-hero">
      <div>
        <span>{{ page.eyebrow }}</span>
        <h1>{{ page.title }}</h1>
        <p>{{ page.summary }}</p>
      </div>
      <p class="legal-updated">Effective date: {{ updatedDate }}</p>
    </section>

    <section class="legal-body" aria-label="Legal content">
      <article v-for="section in page.sections" :key="section.title" class="legal-section">
        <h2>{{ section.title }}</h2>
        <p>{{ section.body }}</p>
      </article>
    </section>

    <section class="legal-note">
      <p>
        This page is a practical website policy draft and should be reviewed by a qualified legal adviser before launch.
      </p>
    </section>

    <SiteFooter />
  </section>
</template>
