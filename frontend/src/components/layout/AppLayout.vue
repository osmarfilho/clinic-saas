<script setup lang="ts">
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] text-[#0F172A]">
    <div class="flex">
      <AppSidebar />
      <div class="min-h-screen flex-1">
        <AppTopbar />
        <main class="p-4 lg:p-6">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
