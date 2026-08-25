<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
  csrfToken: {
    type: String,
    required: true,
  },
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
  database: {
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

const apiTest = ref(null);
const syncResult = ref(null);
const testingApi = ref(false);
const syncingCatalogue = ref(false);

const csrfTokenFromCookie = () => document.cookie
  .split('; ')
  .find((row) => row.startsWith('XSRF-TOKEN='))
  ?.split('=')
  .slice(1)
  .join('=');

const csrfToken = () => {
  const token = props.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfTokenFromCookie() || '';

  try {
    return decodeURIComponent(token);
  } catch {
    return token;
  }
};

const postDiagnosticAction = async (url) => {
  const token = csrfToken();
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({ _token: token }),
  });
  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    return {
      ok: false,
      status: response.status,
      ...data,
    };
  }

  return data;
};

const testApiConnection = async () => {
  testingApi.value = true;
  apiTest.value = null;

  try {
    apiTest.value = await postDiagnosticAction('/admin/diagnostics/test-esim-api');
  } catch (error) {
    apiTest.value = {
      ok: false,
      message: error.message || 'Request failed.',
    };
  } finally {
    testingApi.value = false;
  }
};

const syncCatalogue = async () => {
  syncingCatalogue.value = true;
  syncResult.value = null;

  try {
    syncResult.value = await postDiagnosticAction('/admin/diagnostics/sync-catalogue');
  } catch (error) {
    syncResult.value = {
      ok: false,
      message: error.message || 'Sync request failed.',
    };
  } finally {
    syncingCatalogue.value = false;
  }
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

    <section class="admin-panel diagnostic-actions">
      <div>
        <h2>eSIM API tools</h2>
        <p>Test the configured API key from this server, then sync every available catalogue page when the connection is healthy.</p>
      </div>
      <div class="diagnostic-action-buttons">
        <button type="button" :disabled="testingApi" @click="testApiConnection">
          {{ testingApi ? 'Testing...' : 'Test eSIM API' }}
        </button>
        <button type="button" :disabled="syncingCatalogue" @click="syncCatalogue">
          {{ syncingCatalogue ? 'Syncing all plans...' : 'Sync all plans' }}
        </button>
      </div>
      <div v-if="apiTest" :class="['diagnostic-result', apiTest.ok ? 'ok' : 'warn']">
        <strong>Connection test: {{ apiTest.message }}</strong>
        <span v-if="apiTest.status">Status {{ apiTest.status }}</span>
        <pre v-if="apiTest.provider_response">{{ JSON.stringify(apiTest.provider_response, null, 2) }}</pre>
      </div>
      <div v-if="syncResult" :class="['diagnostic-result', syncResult.ok ? 'ok' : 'warn']">
        <strong>Catalogue sync: {{ syncResult.message }}</strong>
        <span v-if="syncResult.status">Status {{ syncResult.status }}</span>
        <span v-if="syncResult.ok">
          Synced {{ syncResult.synced }} of {{ syncResult.source_count }} items across {{ syncResult.pages_fetched }} pages.
          Skipped {{ syncResult.skipped }}. Local plans: {{ syncResult.local_plan_count }}.
        </span>
        <pre v-if="syncResult.provider_response">{{ JSON.stringify(syncResult.provider_response, null, 2) }}</pre>
      </div>
    </section>

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
      <article :class="{ ok: network.public_outbound_ip, warn: !network.public_outbound_ip }">
        <span>API request IP</span>
        <strong>{{ network.public_outbound_ip || 'Unavailable' }}</strong>
      </article>
      <article :class="{ ok: database.ok, warn: !database.ok }">
        <span>Database</span>
        <strong>{{ database.ok ? 'Healthy' : 'Needs attention' }}</strong>
      </article>
    </div>

    <div class="diagnostic-grid">
      <section class="admin-panel diagnostic-panel">
        <h2>Database & migrations</h2>
        <dl class="admin-dl">
          <div>
            <dt>Connection</dt>
            <dd>{{ formatValue(database.connection) }}</dd>
          </div>
          <div>
            <dt>Database</dt>
            <dd>{{ formatValue(database.database) }}</dd>
          </div>
          <div>
            <dt>Pending migrations</dt>
            <dd>
              <template v-if="database.pending_migrations?.length">
                <ul class="diagnostic-list">
                  <li v-for="migration in database.pending_migrations" :key="migration">{{ migration }}</li>
                </ul>
              </template>
              <template v-else>None</template>
            </dd>
          </div>
          <div>
            <dt>Missing tables</dt>
            <dd>
              <template v-if="database.missing_tables?.length">
                <ul class="diagnostic-list">
                  <li v-for="table in database.missing_tables" :key="table">{{ table }}</li>
                </ul>
              </template>
              <template v-else>None</template>
            </dd>
          </div>
          <div>
            <dt>Missing columns</dt>
            <dd>
              <template v-if="database.column_issues?.length">
                <ul class="diagnostic-list">
                  <li v-for="issue in database.column_issues" :key="issue.table">
                    {{ issue.table }}: {{ issue.missing_columns.join(', ') }}
                  </li>
                </ul>
              </template>
              <template v-else>None</template>
            </dd>
          </div>
          <div v-if="database.error">
            <dt>Error</dt>
            <dd>{{ database.error }}</dd>
          </div>
        </dl>
      </section>

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
            <dt>Public outbound IP</dt>
            <dd class="diagnostic-copy-value">{{ formatValue(network.public_outbound_ip) }}</dd>
          </div>
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
