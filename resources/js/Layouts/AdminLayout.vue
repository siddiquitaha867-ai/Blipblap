<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url || '');

const isActive = (href) => currentPath.value === href || currentPath.value.startsWith(`${href}/`);
</script>

<template>
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <Link href="/" class="admin-brand">
        <img src="/images/blipblap/logo-blue.png" alt="BlipBlap" />
      </Link>
      <nav>
        <Link href="/admin" :class="{ active: isActive('/admin') && !currentPath.startsWith('/admin/plans') && !currentPath.startsWith('/admin/orders') && !currentPath.startsWith('/admin/logs') && !currentPath.startsWith('/admin/diagnostics') && !currentPath.startsWith('/admin/promotions') && !currentPath.startsWith('/admin/content') && !currentPath.startsWith('/admin/users') && !currentPath.startsWith('/admin/storefront') }">Dashboard</Link>
        <Link href="/admin/plans" :class="{ active: isActive('/admin/plans') }">Plans</Link>
        <Link href="/admin/orders" :class="{ active: isActive('/admin/orders') }">Orders</Link>
        <Link href="/admin/logs" :class="{ active: isActive('/admin/logs') }">Logs</Link>
        <Link href="/admin/diagnostics" :class="{ active: isActive('/admin/diagnostics') }">Diagnostics</Link>
        <Link href="/admin/promotions" :class="{ active: isActive('/admin/promotions') }">Promotions</Link>
        <Link href="/admin/content" :class="{ active: isActive('/admin/content') }">Content</Link>
        <Link href="/admin/users" :class="{ active: isActive('/admin/users') }">Users</Link>
        <Link href="/admin/storefront" :class="{ active: isActive('/admin/storefront') }">Storefront</Link>
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
