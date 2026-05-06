<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <Link href="/" class="admin-brand">
        <img src="/images/blipblap/logo-blue.png" alt="BlipBlap" />
      </Link>
      <nav>
        <Link href="/admin">Dashboard</Link>
        <Link href="/admin/users">Users</Link>
        <Link href="/pakistan-esim">Storefront</Link>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-top">
        <div>
          <span>Admin Panel</span>
          <strong>{{ page.props.auth.user?.name }}</strong>
        </div>
        <Link href="/logout" method="post" as="button">Logout</Link>
      </header>
      <slot />
    </main>
  </div>
</template>
