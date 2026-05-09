<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
  app: {
    type: Object,
    required: true,
  },
  esim: {
    type: Object,
    required: true,
  },
  stripe: {
    type: Object,
    required: true,
  },
  network: {
    type: Object,
    required: true,
  },
});

const formatValue = (value) => {
  if (Array.isArray(value)) {
    return value.length ? value.join(', ') : 'None';
  }

  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No';
  }

  return value ?? 'Not configured';
};
</script>

<template>
  <section class="admin-page">
    <div class="admin-heading">
      <div>
        <p>Diagnostics</p>
        <h1>Configuration health</h1>
      </div>
      <a href="/admin/diagnostics">Refresh</a>
    </div>

    <div class="diagnostic-status-grid">
      <article :class="{ ok: esim.api_key_present, warn: !esim.api_key_present }">
        <span>eSIM API key</span>
        <strong>{{ esim.api_key_present ? 'Configured' : 'Missing' }}</strong>
      </article>
      <article :class="{ ok: app.url, warn: !app.url }">
        <span>App URL</span>
        <strong>{{ app.url || 'Missing' }}</strong>
      </article>
      <article :class="{ ok: !app.debug, warn: app.debug }">
        <span>Debug mode</span>
        <strong>{{ app.debug ? 'Enabled' : 'Disabled' }}</strong>
      </article>
      <article :class="{ ok: stripe.publishable_key_present && stripe.secret_key_present, warn: !stripe.publishable_key_present || !stripe.secret_key_present }">
        <span>Stripe keys</span>
        <strong>{{ stripe.publishable_key_present && stripe.secret_key_present ? 'Configured' : 'Incomplete' }}</strong>
      </article>
    </div>

    <div class="diagnostic-grid">
      <section class="admin-panel diagnostic-panel">
        <h2>eSIM Go API</h2>
        <dl class="admin-dl">
          <div>
            <dt>Base URL</dt>
            <dd>{{ formatValue(esim.base_url) }}</dd>
          </div>
          <div>
            <dt>API key</dt>
            <dd>{{ formatValue(esim.api_key_masked) }}</dd>
          </div>
          <div>
            <dt>Key fingerprint</dt>
            <dd>{{ formatValue(esim.api_key_fingerprint) }}</dd>
          </div>
          <div>
            <dt>Timeout</dt>
            <dd>{{ esim.timeout }} seconds</dd>
          </div>
          <div>
            <dt>Retries</dt>
            <dd>{{ esim.retry_times }} attempts, {{ esim.retry_sleep }}ms sleep</dd>
          </div>
          <div>
            <dt>Pricing</dt>
            <dd>{{ esim.currency }} with {{ esim.markup_percentage }}% markup</dd>
          </div>
        </dl>
      </section>

      <section class="admin-panel diagnostic-panel">
        <h2>Network</h2>
        <dl class="admin-dl">
          <div>
            <dt>Request IP</dt>
            <dd>{{ formatValue(network.request_ip) }}</dd>
          </div>
          <div>
            <dt>Client IP chain</dt>
            <dd>{{ formatValue(network.client_ips) }}</dd>
          </div>
          <div>
            <dt>Remote address</dt>
            <dd>{{ formatValue(network.remote_addr) }}</dd>
          </div>
          <div>
            <dt>Server address</dt>
            <dd>{{ formatValue(network.server_addr) }}</dd>
          </div>
          <div>
            <dt>Host</dt>
            <dd>{{ network.scheme }}://{{ network.host }}:{{ network.port }}</dd>
          </div>
          <div>
            <dt>Server name</dt>
            <dd>{{ formatValue(network.server_name) }}</dd>
          </div>
        </dl>
      </section>

      <section class="admin-panel diagnostic-panel">
        <h2>Application</h2>
        <dl class="admin-dl">
          <div>
            <dt>Name</dt>
            <dd>{{ formatValue(app.name) }}</dd>
          </div>
          <div>
            <dt>Environment</dt>
            <dd>{{ formatValue(app.environment) }}</dd>
          </div>
          <div>
            <dt>Laravel / PHP</dt>
            <dd>{{ app.laravel_version }} / {{ app.php_version }}</dd>
          </div>
          <div>
            <dt>Timezone</dt>
            <dd>{{ formatValue(app.timezone) }}</dd>
          </div>
          <div>
            <dt>Config cache</dt>
            <dd>{{ formatValue(app.config_cached) }}</dd>
          </div>
          <div>
            <dt>Route cache</dt>
            <dd>{{ formatValue(app.routes_cached) }}</dd>
          </div>
        </dl>
      </section>

      <section class="admin-panel diagnostic-panel">
        <h2>Payments</h2>
        <dl class="admin-dl">
          <div>
            <dt>Publishable key</dt>
            <dd>{{ formatValue(stripe.publishable_key_masked) }}</dd>
          </div>
          <div>
            <dt>Secret key</dt>
            <dd>{{ formatValue(stripe.secret_key_masked) }}</dd>
          </div>
          <div>
            <dt>Webhook secret</dt>
            <dd>{{ stripe.webhook_secret_present ? 'Configured' : 'Missing' }}</dd>
          </div>
          <div>
            <dt>User agent</dt>
            <dd>{{ formatValue(network.user_agent) }}</dd>
          </div>
        </dl>
      </section>
    </div>
  </section>
</template>
