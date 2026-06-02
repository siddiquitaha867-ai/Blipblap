<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  homeContent: {
    type: Object,
    required: true,
  },
  emailContent: {
    type: Object,
    required: true,
  },
  pages: {
    type: Array,
    default: () => [],
  },
});

const homeForm = useForm({
  hero_eyebrow: props.homeContent.hero_eyebrow || '',
  hero_title: props.homeContent.hero_title || '',
  hero_text: props.homeContent.hero_text || '',
  hero_cta_label: props.homeContent.hero_cta_label || '',
  hero_cta_url: props.homeContent.hero_cta_url || '',
  hero_image_url: props.homeContent.hero_image_url || '',
  trust_heading: props.homeContent.trust_heading || '',
  trust_items: (props.homeContent.trust_items || []).map((item) => ({ ...item })),
  faq_heading: props.homeContent.faq_heading || '',
  faq_intro: props.homeContent.faq_intro || '',
  faqs: (props.homeContent.faqs || []).map((item) => ({ ...item })),
  promo_banners: (props.homeContent.promo_banners || []).map((item) => ({ ...item })),
});

const emailForm = useForm({
  subject: props.emailContent.subject || '',
  heading: props.emailContent.heading || '',
  intro: props.emailContent.intro || '',
  steps: [...(props.emailContent.steps || [])],
  manual_heading: props.emailContent.manual_heading || '',
  manual_intro: props.emailContent.manual_intro || '',
  footer: props.emailContent.footer || '',
  ios_label: props.emailContent.ios_label || '',
  android_label: props.emailContent.android_label || '',
});

const selectedPageId = ref(props.pages[0]?.id || null);
const selectedPage = computed(() => props.pages.find((page) => page.id === selectedPageId.value) || null);

const pageForm = useForm({
  slug: '',
  title: '',
  excerpt: '',
  body_html: '',
  meta_title: '',
  meta_description: '',
  is_published: true,
});

const loadPage = (page) => {
  selectedPageId.value = page?.id || null;
  pageForm.slug = page?.slug || '';
  pageForm.title = page?.title || '';
  pageForm.excerpt = page?.excerpt || '';
  pageForm.body_html = page?.body_html || '';
  pageForm.meta_title = page?.meta_title || '';
  pageForm.meta_description = page?.meta_description || '';
  pageForm.is_published = page?.is_published ?? true;
};

if (selectedPage.value) {
  loadPage(selectedPage.value);
}

const saveHomepage = () => homeForm.patch('/admin/content/homepage', { preserveScroll: true });
const saveEmail = () => emailForm.patch('/admin/content/email', { preserveScroll: true });

const savePage = () => {
  if (selectedPageId.value) {
    pageForm.patch(`/admin/content/pages/${selectedPageId.value}`, { preserveScroll: true });
    return;
  }

  pageForm.post('/admin/content/pages', {
    preserveScroll: true,
    onSuccess: () => pageForm.reset(),
  });
};

const deletePage = () => {
  if (!selectedPageId.value || !window.confirm('Delete this page?')) {
    return;
  }

  router.delete(`/admin/content/pages/${selectedPageId.value}`);
};

const addItem = (field, defaults) => {
  homeForm[field].push({ ...defaults });
};

const removeItem = (field, index) => {
  homeForm[field].splice(index, 1);
};

const addEmailStep = () => emailForm.steps.push('');
const removeEmailStep = (index) => emailForm.steps.splice(index, 1);
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>CMS</p>
        <h1>Content</h1>
      </div>
    </div>

    <section class="admin-panel admin-form-panel">
      <h2>Homepage content and banners</h2>
      <form class="admin-create-grid" @submit.prevent="saveHomepage">
        <label><span>Hero eyebrow</span><input v-model="homeForm.hero_eyebrow" type="text" /></label>
        <label><span>Hero title</span><input v-model="homeForm.hero_title" type="text" /></label>
        <label class="checkout-wide"><span>Hero text</span><textarea v-model="homeForm.hero_text" rows="3"></textarea></label>
        <label><span>Hero CTA label</span><input v-model="homeForm.hero_cta_label" type="text" /></label>
        <label><span>Hero CTA URL</span><input v-model="homeForm.hero_cta_url" type="text" /></label>
        <label class="checkout-wide"><span>Hero image URL</span><input v-model="homeForm.hero_image_url" type="text" /></label>
        <label class="checkout-wide"><span>Trust heading</span><input v-model="homeForm.trust_heading" type="text" /></label>

        <div class="checkout-wide">
          <div class="admin-heading"><div><p>Promotions</p><h2>Promo banners</h2></div></div>
          <div v-for="(banner, index) in homeForm.promo_banners" :key="index" class="admin-plan-line">
            <div class="admin-plan-edit-line">
              <label><span>Title</span><input v-model="banner.title" type="text" /></label>
              <label><span>Text</span><input v-model="banner.text" type="text" /></label>
              <label><span>CTA label</span><input v-model="banner.cta_label" type="text" /></label>
              <label><span>CTA URL</span><input v-model="banner.cta_url" type="text" /></label>
              <button type="button" class="admin-mini-button" @click="removeItem('promo_banners', index)">Remove</button>
            </div>
          </div>
          <button type="button" class="admin-mini-button" @click="addItem('promo_banners', { title: '', text: '', cta_label: '', cta_url: '' })">Add banner</button>
        </div>

        <div class="checkout-wide">
          <div class="admin-heading"><div><p>Trust section</p><h2>Trust cards</h2></div></div>
          <div v-for="(item, index) in homeForm.trust_items" :key="index" class="admin-plan-line">
            <div class="admin-plan-edit-line">
              <label><span>Title</span><input v-model="item.title" type="text" /></label>
              <label><span>Text</span><input v-model="item.text" type="text" /></label>
              <label><span>Image URL</span><input v-model="item.image" type="text" /></label>
              <button type="button" class="admin-mini-button" @click="removeItem('trust_items', index)">Remove</button>
            </div>
          </div>
        </div>

        <label class="checkout-wide"><span>FAQ heading</span><input v-model="homeForm.faq_heading" type="text" /></label>
        <label class="checkout-wide"><span>FAQ intro</span><input v-model="homeForm.faq_intro" type="text" /></label>
        <div class="checkout-wide">
          <div class="admin-heading"><div><p>FAQs</p><h2>Questions and answers</h2></div></div>
          <div v-for="(item, index) in homeForm.faqs" :key="index" class="admin-plan-line">
            <div class="admin-plan-edit-line">
              <label><span>Question</span><input v-model="item.question" type="text" /></label>
              <label class="admin-plan-title-field"><span>Answer</span><input v-model="item.answer" type="text" /></label>
              <button type="button" class="admin-mini-button" @click="removeItem('faqs', index)">Remove</button>
            </div>
          </div>
        </div>

        <button type="submit" :disabled="homeForm.processing">Save homepage</button>
      </form>
    </section>

    <section class="admin-panel admin-form-panel">
      <h2>Automated email control</h2>
      <form class="admin-create-grid" @submit.prevent="saveEmail">
        <label><span>Subject</span><input v-model="emailForm.subject" type="text" /></label>
        <label class="checkout-wide"><span>Heading</span><input v-model="emailForm.heading" type="text" /></label>
        <label class="checkout-wide"><span>Intro</span><textarea v-model="emailForm.intro" rows="3"></textarea></label>
        <label><span>Manual details heading</span><input v-model="emailForm.manual_heading" type="text" /></label>
        <label class="checkout-wide"><span>Manual details intro</span><textarea v-model="emailForm.manual_intro" rows="2"></textarea></label>
        <label><span>Apple button label</span><input v-model="emailForm.ios_label" type="text" /></label>
        <label><span>Android button label</span><input v-model="emailForm.android_label" type="text" /></label>
        <label class="checkout-wide"><span>Footer</span><textarea v-model="emailForm.footer" rows="3"></textarea></label>
        <div class="checkout-wide">
          <div class="admin-heading"><div><p>Instructions</p><h2>Email steps</h2></div></div>
          <div v-for="(step, index) in emailForm.steps" :key="index" class="admin-plan-line">
            <div class="admin-plan-edit-line">
              <label class="admin-plan-title-field"><span>Step {{ index + 1 }}</span><input v-model="emailForm.steps[index]" type="text" /></label>
              <button type="button" class="admin-mini-button" @click="removeEmailStep(index)">Remove</button>
            </div>
          </div>
          <button type="button" class="admin-mini-button" @click="addEmailStep">Add step</button>
        </div>
        <button type="submit" :disabled="emailForm.processing">Save email content</button>
      </form>
    </section>

    <section class="admin-panel admin-form-panel">
      <h2>Add or edit pages</h2>
      <div class="admin-country-tabs" style="margin-bottom: 20px;">
        <button type="button" class="admin-country-tab" @click="loadPage(null)">New page</button>
        <button
          v-for="page in pages"
          :key="page.id"
          type="button"
          class="admin-country-tab"
          @click="loadPage(page)"
        >
          <span class="admin-country-title">
            <small>{{ page.slug }}</small>
            <strong>{{ page.title }}</strong>
          </span>
        </button>
      </div>

      <form class="admin-create-grid" @submit.prevent="savePage">
        <label><span>Slug</span><input v-model="pageForm.slug" type="text" placeholder="special-offer" /></label>
        <label><span>Title</span><input v-model="pageForm.title" type="text" /></label>
        <label class="checkout-wide"><span>Excerpt</span><textarea v-model="pageForm.excerpt" rows="2"></textarea></label>
        <label class="checkout-wide"><span>Body HTML</span><textarea v-model="pageForm.body_html" rows="10"></textarea></label>
        <label><span>Meta title</span><input v-model="pageForm.meta_title" type="text" /></label>
        <label class="checkout-wide"><span>Meta description</span><textarea v-model="pageForm.meta_description" rows="2"></textarea></label>
        <label class="admin-check-row">
          <input v-model="pageForm.is_published" type="checkbox" />
          Published
        </label>
        <button type="submit" :disabled="pageForm.processing">{{ selectedPageId ? 'Update page' : 'Create page' }}</button>
        <button v-if="selectedPageId" type="button" class="admin-mini-button" @click="deletePage">Delete page</button>
      </form>
    </section>
  </section>
</template>
